<?php

declare(strict_types=1);

namespace WebAlbum\Http\Controllers;

use WebAlbum\Assets\AssetMoveSyncService;
use WebAlbum\Assets\AssetPaths;
use WebAlbum\Assets\MoveTargetResolver;
use WebAlbum\Db\Maria;
use WebAlbum\UserContext;

final class AssetMoveController
{
    private string $configPath;
    private AssetMoveSyncService $moveSync;
    private MoveTargetResolver $targetResolver;

    public function __construct(string $configPath)
    {
        $this->configPath = $configPath;
        $this->moveSync = new AssetMoveSyncService();
        $this->targetResolver = new MoveTargetResolver();
    }

    public function move(int $id): void
    {
        $raw = file_get_contents('php://input') ?: '{}';
        try {
            $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            $result = $this->executeMove($id, is_array($data) ? $data : []);
            $this->json($result['payload'], $result['status']);
        } catch (\JsonException $e) {
            $this->json(['error' => 'Invalid JSON'], 400);
        }
    }

    /**
     * @param array<string, mixed> $data
     * @return array{status:int, payload:array<string, mixed>}
     */
    public function executeMove(int $id, array $data): array
    {
        try {
            if ($id < 1) {
                return ['status' => 400, 'payload' => ['error' => 'Invalid id']];
            }

            [$config, $maria, $user] = $this->authAdmin();
            if ($user === null) {
                return ['status' => 401, 'payload' => ['error' => 'Not authenticated']];
            }

            $targetFolder = $this->normalizeTargetFolder((string)($data['target_rel_path'] ?? ''));
            if ($targetFolder === null) {
                return ['status' => 400, 'payload' => ['error' => 'Invalid destination folder']];
            }

            $rows = $maria->query(
                'SELECT id, rel_path, type, ext, mime, size, mtime FROM wa_assets WHERE id = ? LIMIT 1',
                [$id]
            );
            if ($rows === []) {
                return ['status' => 404, 'payload' => ['error' => 'Asset not found']];
            }
            $asset = $rows[0];
            if (!$this->moveSync->isSupportedAsset($asset)) {
                $type = strtolower(trim((string)($asset['type'] ?? '')));
                return ['status' => 400, 'payload' => ['error' => $type === 'doc' ? 'Only document assets are supported' : 'Only supported assets can be moved']];
            }
            $isDocument = $this->moveSync->isDocumentAsset($asset);

            $sourceRelPath = AssetPaths::normalizeRelPath((string)($asset['rel_path'] ?? ''));
            if ($sourceRelPath === null) {
                return ['status' => 400, 'payload' => ['error' => 'Invalid rel_path']];
            }
            if ($this->isTrashed($maria, $sourceRelPath)) {
                return ['status' => 410, 'payload' => ['error' => 'Trashed']];
            }

            $sourceFolder = $this->folderFromRelPath($sourceRelPath);
            if ($sourceFolder === $targetFolder) {
                return ['status' => 409, 'payload' => ['error' => 'Source and destination folders are the same']];
            }

            $blocker = $this->moveSync->detectMoveBlocker($maria, $id);
            if (is_array($blocker)) {
                $details = [
                    'asset_id' => $id,
                    'asset_type' => (string)($asset['type'] ?? ''),
                    'old_rel_path' => $sourceRelPath,
                    'new_rel_path' => $targetFolder,
                    'actor_user_id' => (int)$user['id'],
                    'stage' => 'guard_check',
                    'blocker' => $blocker,
                ];
                $this->logMove(['success' => false] + $details);
                $this->logAudit($maria, (int)$user['id'], 'asset_move_blocked', $details);
                return ['status' => 409, 'payload' => ['error' => (string)$blocker['error']]];
            }

            $filename = $this->requestedFilename($data, $sourceRelPath);

            $photosRoot = (string)($config['photos']['root'] ?? '');
            $sourcePath = AssetPaths::joinInside($photosRoot, $sourceRelPath);
            if ($sourcePath === null || !is_file($sourcePath)) {
                return ['status' => 404, 'payload' => ['error' => 'Asset file not found']];
            }

            $targetDirPath = $targetFolder === ''
                ? rtrim($photosRoot, DIRECTORY_SEPARATOR)
                : AssetPaths::joinInside($photosRoot, $targetFolder);
            if ($targetDirPath === '' || $targetDirPath === null || !is_dir($targetDirPath)) {
                return ['status' => 400, 'payload' => ['error' => 'Invalid destination folder']];
            }

            try {
                $resolvedTarget = $this->targetResolver->resolve($photosRoot, $targetFolder, $filename);
            } catch (\RuntimeException $e) {
                return ['status' => 409, 'payload' => ['error' => $e->getMessage()]];
            }
            $targetRelPath = $resolvedTarget['rel_path'];
            $targetPath = $resolvedTarget['abs_path'];
            $desiredTargetRelPath = $resolvedTarget['desired_rel_path'];
            $renamedDueToCollision = (bool)$resolvedTarget['renamed'];

            if (!$this->moveFile($sourcePath, $targetPath)) {
                $details = [
                    'asset_id' => $id,
                    'asset_type' => (string)($asset['type'] ?? ''),
                    'old_rel_path' => $sourceRelPath,
                    'desired_new_rel_path' => $desiredTargetRelPath,
                    'new_rel_path' => $targetRelPath,
                    'renamed_due_to_collision' => $renamedDueToCollision,
                    'actor_user_id' => (int)$user['id'],
                    'stage' => 'disk_move',
                    'error' => 'Failed to move file on disk',
                ];
                $this->logMove(['success' => false] + $details);
                $this->logAudit($maria, (int)$user['id'], 'asset_move_failed', $details);
                return ['status' => 500, 'payload' => ['error' => 'Failed to move file on disk']];
            }

            try {
                $remap = $this->moveSync->syncMovedAsset($maria, $id, $sourceRelPath, $targetRelPath, $isDocument);
            } catch (\Throwable $e) {
                $rollbackOk = false;
                if (is_file($targetPath) && !is_file($sourcePath)) {
                    $rollbackOk = $this->moveFile($targetPath, $sourcePath);
                }

                $errorMessage = $rollbackOk
                    ? 'Asset move DB sync failed and original file was restored'
                    : 'Asset move DB sync failed and rollback failed';
                $details = [
                    'asset_id' => $id,
                    'asset_type' => (string)($asset['type'] ?? ''),
                    'old_rel_path' => $sourceRelPath,
                    'desired_new_rel_path' => $desiredTargetRelPath,
                    'new_rel_path' => $targetRelPath,
                    'renamed_due_to_collision' => $renamedDueToCollision,
                    'actor_user_id' => (int)$user['id'],
                    'stage' => 'maria_move_sync',
                    'rollback_attempted' => true,
                    'rollback_ok' => $rollbackOk,
                    'error' => $e->getMessage(),
                ];
                $this->logMove(['success' => false] + $details);
                $this->logAudit($maria, (int)$user['id'], 'asset_move_failed', $details);
                return ['status' => 500, 'payload' => ['error' => $errorMessage]];
            }

            $warnings = [];
            if ($isDocument) {
                $warnings = $this->cleanupDerivativeFiles($remap['derivative_paths_to_cleanup'] ?? []);
            }

            $details = [
                'asset_id' => $id,
                'asset_type' => (string)($asset['type'] ?? ''),
                'old_rel_path' => $sourceRelPath,
                'desired_new_rel_path' => $desiredTargetRelPath,
                'new_rel_path' => $targetRelPath,
                'renamed_due_to_collision' => $renamedDueToCollision,
                'actor_user_id' => (int)$user['id'],
                'derivative_invalidation_performed' => $isDocument,
                'warnings' => $warnings,
            ];
            $this->logMove(['success' => true, 'maria_sync' => $remap] + $details);
            $this->logAudit($maria, (int)$user['id'], 'asset_move', $details);

            return [
                'status' => 200,
                'payload' => [
                    'ok' => true,
                    'asset_id' => $id,
                    'old_rel_path' => $sourceRelPath,
                    'new_rel_path' => $targetRelPath,
                    'desired_new_rel_path' => $desiredTargetRelPath,
                    'renamed_due_to_collision' => $renamedDueToCollision,
                    'remap' => $remap,
                    'warnings' => $warnings,
                ],
            ];
        } catch (\Throwable $e) {
            return ['status' => 500, 'payload' => ['error' => $e->getMessage()]];
        }
    }

    private function authAdmin(): array
    {
        $config = require $this->configPath;
        $maria = new Maria(
            $config['mariadb']['dsn'],
            $config['mariadb']['user'],
            $config['mariadb']['pass']
        );
        $user = UserContext::currentUser($maria);
        if ($user === null) {
            $this->json(['error' => 'Not authenticated'], 401);
            return [$config, $maria, null];
        }
        if ((int)($user['is_admin'] ?? 0) !== 1) {
            $this->json(['error' => 'Forbidden'], 403);
            return [$config, $maria, null];
        }
        return [$config, $maria, $user];
    }

    private function isTrashed(Maria $maria, string $relPath): bool
    {
        $rows = $maria->query(
            "SELECT id FROM wa_media_trash WHERE rel_path = ? AND status = 'trashed' LIMIT 1",
            [$relPath]
        );
        return $rows !== [];
    }

    private function folderFromRelPath(string $relPath): string
    {
        $dir = trim(str_replace('\\', '/', dirname($relPath)), '/');
        return $dir === '.' ? '' : $dir;
    }

    private function moveFile(string $src, string $dst): bool
    {
        if (@rename($src, $dst)) {
            return true;
        }
        if (!@copy($src, $dst)) {
            return false;
        }
        if (!@unlink($src)) {
            @unlink($dst);
            return false;
        }
        return true;
    }

    /**
     * @param array<string,mixed> $data
     */
    private function requestedFilename(array $data, string $sourceRelPath): string
    {
        $raw = trim((string)($data['target_filename'] ?? ''));
        if ($raw === '' || str_contains($raw, '/') || str_contains($raw, '\\')) {
            return basename($sourceRelPath);
        }
        return $raw;
    }

    private function normalizeTargetFolder(string $raw): ?string
    {
        $trimmed = trim(str_replace('\\', '/', $raw), '/');
        if ($trimmed === '') {
            return '';
        }
        return AssetPaths::normalizeRelPath($trimmed);
    }

    /**
     * @param array<int, mixed> $paths
     * @return string[]
     */
    private function cleanupDerivativeFiles(array $paths): array
    {
        $warnings = [];
        foreach ($paths as $value) {
            $path = is_string($value) ? trim($value) : '';
            if ($path === '' || !is_file($path)) {
                continue;
            }
            if (!@unlink($path)) {
                $warnings[] = 'derivative_cleanup_failed';
            }
        }
        return array_values(array_unique($warnings));
    }

    private function logMove(array $details): void
    {
        @error_log('webalbum_asset_move ' . json_encode($details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    private function logAudit(Maria $db, int $actorId, string $action, array $details): void
    {
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $db->exec(
                "INSERT INTO wa_audit_log (actor_user_id, target_user_id, action, source, ip_address, user_agent, details)
                 VALUES (?, NULL, ?, 'web', ?, ?, ?)",
                [
                    $actorId,
                    $action,
                    $ip,
                    $agent,
                    json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]
            );
        } catch (\Throwable $e) {
            // non-blocking
        }
    }

    private function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload);
    }
}

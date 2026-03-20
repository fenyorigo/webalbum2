<?php

declare(strict_types=1);

namespace WebAlbum\Http\Controllers;

use WebAlbum\Assets\AssetPaths;
use WebAlbum\Assets\MoveTargetResolver;
use WebAlbum\AuditLogMetaCache;
use WebAlbum\Db\Maria;
use WebAlbum\Db\SqliteIndex;
use WebAlbum\Media\MediaMoveSyncService;
use WebAlbum\Media\MediaTagSupport;
use WebAlbum\SystemTools;
use WebAlbum\Thumb\ThumbPolicy;
use WebAlbum\UserContext;

final class MediaMoveController
{
    private string $configPath;
    private MediaMoveSyncService $moveSync;
    private MoveTargetResolver $targetResolver;

    public function __construct(string $configPath)
    {
        $this->configPath = $configPath;
        $this->moveSync = new MediaMoveSyncService();
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

            $sqlite = new SqliteIndex((string)$config['sqlite']['path']);
            $rows = $sqlite->query(
                'SELECT id, path, rel_path, type, sha256 FROM files WHERE id = ? LIMIT 1',
                [$id]
            );
            if ($rows === []) {
                return ['status' => 404, 'payload' => ['error' => 'Not Found']];
            }

            $file = $rows[0];
            $type = strtolower(trim((string)($file['type'] ?? '')));
            if (!in_array($type, ['image', 'video'], true)) {
                return ['status' => 400, 'payload' => ['error' => 'Only image/video media files are supported']];
            }

            $sourceRelPath = AssetPaths::normalizeRelPath((string)($file['rel_path'] ?? ''));
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

            $objectId = $this->moveSync->resolveObjectIdBySha($maria, (string)($file['sha256'] ?? ''));
            $blocker = $this->moveSync->detectMoveBlocker($maria, $sourceRelPath, $objectId);
            if (is_array($blocker)) {
                $details = [
                    'media_id' => $id,
                    'old_path' => $sourceRelPath,
                    'new_path' => $targetFolder,
                    'actor_user_id' => (int)$user['id'],
                    'stage' => 'guard_check',
                    'blocker' => $blocker,
                ];
                $this->logMove(['success' => false] + $details);
                $this->logAudit($maria, (int)$user['id'], 'media_move_blocked', $details);
                return ['status' => 409, 'payload' => ['error' => (string)$blocker['error']]];
            }

            $filename = $this->requestedFilename($data, $sourceRelPath);

            $photosRoot = (string)($config['photos']['root'] ?? '');
            $sourcePath = MediaTagSupport::resolveOriginalPath(
                (string)($file['path'] ?? ''),
                $sourceRelPath,
                $photosRoot
            );
            if ($sourcePath === null || !is_file($sourcePath)) {
                return ['status' => 404, 'payload' => ['error' => 'File not found']];
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
                    'media_id' => $id,
                    'old_path' => $sourceRelPath,
                    'desired_new_path' => $desiredTargetRelPath,
                    'new_path' => $targetRelPath,
                    'renamed_due_to_collision' => $renamedDueToCollision,
                    'actor_user_id' => (int)$user['id'],
                    'stage' => 'disk_move',
                    'error' => 'Failed to move file on disk',
                ];
                $this->logMove(['success' => false] + $details);
                $this->logAudit($maria, (int)$user['id'], 'media_move_failed', $details);
                return ['status' => 500, 'payload' => ['error' => 'Failed to move file on disk']];
            }

            $thumbWarnings = $this->invalidateThumbs($config, $sourceRelPath, $targetRelPath);

            try {
                $indexerResult = $this->runIndexerMove($config, $sourceRelPath, $targetRelPath);
            } catch (\Throwable $e) {
                $rollbackOk = false;
                if (is_file($targetPath) && !is_file($sourcePath)) {
                    $rollbackOk = $this->moveFile($targetPath, $sourcePath);
                }

                $errorMessage = $rollbackOk
                    ? 'Indexer move failed and original file was restored'
                    : 'Indexer move failed and rollback failed';
                $details = [
                    'media_id' => $id,
                    'old_path' => $sourceRelPath,
                    'desired_new_path' => $desiredTargetRelPath,
                    'new_path' => $targetRelPath,
                    'renamed_due_to_collision' => $renamedDueToCollision,
                    'actor_user_id' => (int)$user['id'],
                    'stage' => 'indexer_move',
                    'rollback_ok' => $rollbackOk,
                    'thumb_warnings' => $thumbWarnings,
                    'error' => $e->getMessage(),
                ];
                $this->logMove(['success' => false] + $details);
                $this->logAudit($maria, (int)$user['id'], 'media_move_failed', $details);
                return ['status' => 500, 'payload' => ['error' => $errorMessage]];
            }

            $sqliteAfter = new SqliteIndex((string)$config['sqlite']['path']);
            $newRows = $sqliteAfter->query(
                'SELECT id, rel_path FROM files WHERE rel_path = ? LIMIT 1',
                [$targetRelPath]
            );
            $newId = isset($newRows[0]['id']) ? (int)$newRows[0]['id'] : null;

            try {
                $remap = $this->moveSync->syncMovedMedia(
                    $maria,
                    $id,
                    (int)$newId,
                    $sourceRelPath,
                    $targetRelPath,
                    $objectId
                );
            } catch (\Throwable $e) {
                $rollback = $this->rollbackMovedMedia($config, $targetPath, $sourcePath, $targetRelPath, $sourceRelPath);
                $errorMessage = $rollback['ok']
                    ? 'MariaDB move sync failed and original file was restored'
                    : 'MariaDB move sync failed and rollback failed';
                $details = [
                    'media_id' => $id,
                    'new_media_id' => $newId,
                    'old_path' => $sourceRelPath,
                    'desired_new_path' => $desiredTargetRelPath,
                    'new_path' => $targetRelPath,
                    'renamed_due_to_collision' => $renamedDueToCollision,
                    'actor_user_id' => (int)$user['id'],
                    'stage' => 'maria_move_sync',
                    'rollback_ok' => $rollback['ok'],
                    'rollback_indexer_ok' => $rollback['indexer_ok'],
                    'rollback_error' => $rollback['error'],
                    'thumb_warnings' => $thumbWarnings,
                    'error' => $e->getMessage(),
                ];
                $this->logMove(['success' => false] + $details);
                $this->logAudit($maria, (int)$user['id'], 'media_move_failed', $details);
                return ['status' => 500, 'payload' => ['error' => $errorMessage]];
            }

            $details = [
                'media_id' => $id,
                'new_media_id' => $newId,
                'object_id' => $objectId,
                'old_path' => $sourceRelPath,
                'desired_new_path' => $desiredTargetRelPath,
                'new_path' => $targetRelPath,
                'renamed_due_to_collision' => $renamedDueToCollision,
                'actor_user_id' => (int)$user['id'],
                'thumb_warnings' => $thumbWarnings,
            ];
            $this->logMove(['success' => true, 'indexer' => $indexerResult, 'maria_sync' => $remap] + $details);
            $this->logAudit($maria, (int)$user['id'], 'media_move', $details);

            return [
                'status' => 200,
                'payload' => [
                    'ok' => true,
                    'id' => $id,
                    'new_id' => $newId,
                    'old_rel_path' => $sourceRelPath,
                    'new_rel_path' => $targetRelPath,
                    'desired_new_rel_path' => $desiredTargetRelPath,
                    'renamed_due_to_collision' => $renamedDueToCollision,
                    'warnings' => $thumbWarnings,
                    'remap' => $remap,
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

    private function invalidateThumbs(array $config, string $oldRelPath, string $newRelPath): array
    {
        $warnings = [];
        $thumbRoot = trim((string)($config['thumbs']['root'] ?? ''));
        if ($thumbRoot === '') {
            return $warnings;
        }

        $thumbPaths = array_filter([
            ThumbPolicy::thumbPath($thumbRoot, $oldRelPath),
            ThumbPolicy::thumbPath($thumbRoot, $newRelPath),
        ]);

        foreach (array_values(array_unique($thumbPaths)) as $thumbPath) {
            if (!is_string($thumbPath) || !is_file($thumbPath)) {
                continue;
            }
            if (!@unlink($thumbPath)) {
                $warnings[] = 'thumb_cleanup_failed';
            }
        }

        return array_values(array_unique($warnings));
    }

    private function rollbackMovedMedia(
        array $config,
        string $movedPath,
        string $originalPath,
        string $movedRelPath,
        string $originalRelPath
    ): array {
        $rollbackOk = false;
        $rollbackIndexerOk = false;
        $rollbackError = null;

        try {
            if (is_file($movedPath) && !is_file($originalPath)) {
                $rollbackOk = $this->moveFile($movedPath, $originalPath);
            }
            if ($rollbackOk) {
                $this->runIndexerMove($config, $movedRelPath, $originalRelPath);
                $rollbackIndexerOk = true;
            }
        } catch (\Throwable $e) {
            $rollbackError = $e->getMessage();
        }

        return [
            'ok' => $rollbackOk && $rollbackIndexerOk,
            'indexer_ok' => $rollbackIndexerOk,
            'error' => $rollbackError,
        ];
    }

    private function runIndexerMove(array $config, string $oldRelPath, string $newRelPath): array
    {
        $sqlitePath = (string)($config['sqlite']['path'] ?? '');
        $photosRoot = (string)($config['photos']['root'] ?? '');
        $indexerRoot = (string)($config['indexer']['root'] ?? '');
        $toolValues = SystemTools::getConfiguredToolValues($config);
        $python = (string)($toolValues['python3'] ?? ($config['indexer']['python'] ?? 'python3'));
        $configPath = trim((string)($config['indexer']['config_path'] ?? ''));
        if ($configPath === '') {
            $configPath = rtrim($indexerRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'config.yaml';
        }

        if ($sqlitePath === '' || !is_file($sqlitePath)) {
            throw new \RuntimeException('SQLite DB not found for move');
        }
        if ($photosRoot === '' || !is_dir($photosRoot)) {
            throw new \RuntimeException('Photos root is not available for move');
        }
        if ($indexerRoot === '' || !is_dir($indexerRoot)) {
            throw new \RuntimeException('Indexer root is not configured or missing');
        }
        if (!is_file($configPath)) {
            throw new \RuntimeException('Indexer config.yaml not found: ' . $configPath);
        }

        $cmd = [
            $python,
            '-m', 'app',
            '--cli',
            '--db', $sqlitePath,
            '--root', $photosRoot,
            '--config', $configPath,
            '--move-file', $oldRelPath,
            '--to', $newRelPath,
            '--json',
            '--no-progress',
        ];

        [$ok, $stdout, $stderr, $timedOut] = $this->runProcessWithTimeout($cmd, $indexerRoot, 180);
        if (!$ok) {
            if ($timedOut) {
                throw new \RuntimeException('Indexer move timed out');
            }
            $msg = trim($stderr !== '' ? $stderr : $stdout);
            if ($msg === '') {
                $msg = 'Indexer move failed';
            }
            throw new \RuntimeException($msg);
        }

        $decoded = json_decode(trim($stdout), true);
        return is_array($decoded) ? $decoded : ['stdout' => trim($stdout)];
    }

    private function runProcessWithTimeout(array $cmd, ?string $cwd, int $timeoutSec): array
    {
        $descriptors = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $proc = @proc_open($cmd, $descriptors, $pipes, $cwd, null, ['bypass_shell' => true]);
        if (!is_resource($proc)) {
            throw new \RuntimeException('Failed to start child process');
        }

        $stdout = '';
        $stderr = '';
        $timedOut = false;
        foreach ([1, 2] as $idx) {
            if (isset($pipes[$idx]) && is_resource($pipes[$idx])) {
                stream_set_blocking($pipes[$idx], false);
            }
        }

        $start = microtime(true);
        while (true) {
            if (isset($pipes[1]) && is_resource($pipes[1])) {
                $stdout .= (string)stream_get_contents($pipes[1]);
            }
            if (isset($pipes[2]) && is_resource($pipes[2])) {
                $stderr .= (string)stream_get_contents($pipes[2]);
            }
            $status = proc_get_status($proc);
            if (!$status['running']) {
                break;
            }
            if ((microtime(true) - $start) > $timeoutSec) {
                $timedOut = true;
                proc_terminate($proc, 9);
                break;
            }
            usleep(20000);
        }

        foreach ([1, 2] as $idx) {
            if (isset($pipes[$idx]) && is_resource($pipes[$idx])) {
                fclose($pipes[$idx]);
            }
        }

        $exit = proc_close($proc);
        return [$exit === 0 && !$timedOut, trim($stdout), trim($stderr), $timedOut];
    }

    private function logAudit(Maria $db, int $actorId, string $action, array $details = []): void
    {
        try {
            $source = 'web';
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $db->exec(
                "INSERT INTO wa_audit_log (actor_user_id, target_user_id, action, source, ip_address, user_agent, details)\n" .
                "VALUES (?, ?, ?, ?, ?, ?, ?)",
                [
                    $actorId,
                    null,
                    $action,
                    $source,
                    $ip,
                    $agent,
                    $details !== [] ? json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                ]
            );
            AuditLogMetaCache::invalidateIfMissing($action, $source);
        } catch (\Throwable $e) {
            // audit logging must not block operation
        }
    }

    private function logMove(array $details): void
    {
        @error_log('webalbum_move ' . json_encode($details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    private function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}

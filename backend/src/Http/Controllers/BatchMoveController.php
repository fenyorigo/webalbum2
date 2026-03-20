<?php

declare(strict_types=1);

namespace WebAlbum\Http\Controllers;

use WebAlbum\AuditLogMetaCache;
use WebAlbum\Assets\AssetPaths;
use WebAlbum\Db\Maria;
use WebAlbum\Move\BatchMoveCoordinator;
use WebAlbum\UserContext;

final class BatchMoveController
{
    private string $configPath;
    private BatchMoveCoordinator $coordinator;

    public function __construct(string $configPath)
    {
        $this->configPath = $configPath;
        $this->coordinator = new BatchMoveCoordinator($configPath);
    }

    public function move(): void
    {
        try {
            [$config, $maria, $user] = $this->authAdmin();
            if ($user === null) {
                return;
            }

            $body = file_get_contents('php://input') ?: '{}';
            $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($data)) {
                $this->json(['error' => 'Invalid JSON'], 400);
                return;
            }

            $ids = $this->normalizeIds($data['ids'] ?? null);
            if ($ids === []) {
                $this->json(['error' => 'ids are required'], 400);
                return;
            }
            if (count($ids) > BatchMoveCoordinator::MAX_BATCH) {
                $this->json(['error' => 'Too many items. Move in batches of up to ' . BatchMoveCoordinator::MAX_BATCH], 400);
                return;
            }

            $targetFolder = AssetPaths::normalizeRelPath((string)($data['target_rel_path'] ?? ''));
            if ($targetFolder === null) {
                $this->json(['error' => 'Invalid destination folder'], 400);
                return;
            }

            $photosRoot = (string)($config['photos']['root'] ?? '');
            $targetDirPath = AssetPaths::joinInside($photosRoot, $targetFolder);
            if ($targetDirPath === null || !is_dir($targetDirPath)) {
                $this->json(['error' => 'Invalid destination folder'], 400);
                return;
            }

            $this->logBatch([
                'action' => 'start',
                'actor_user_id' => (int)$user['id'],
                'destination_folder' => $targetFolder,
                'requested_count' => count($ids),
                'ids' => $ids,
            ]);

            $summary = $this->coordinator->moveItems($ids, $targetFolder);
            $summary['ok'] = ($summary['blocked_count'] ?? 0) === 0 && ($summary['failed_count'] ?? 0) === 0;
            $summary['destination_folder'] = $targetFolder;
            $summary['batch_count'] = count($ids);

            $auditAction = 'batch_move';
            if (($summary['blocked_count'] ?? 0) > 0 || ($summary['failed_count'] ?? 0) > 0) {
                $auditAction = (($summary['moved_count'] ?? 0) + ($summary['renamed_count'] ?? 0)) > 0
                    ? 'batch_move_partial'
                    : 'batch_move_failed';
            }

            $auditDetails = [
                'destination_folder' => $targetFolder,
                'requested_count' => (int)($summary['requested_count'] ?? 0),
                'moved_count' => (int)($summary['moved_count'] ?? 0),
                'renamed_count' => (int)($summary['renamed_count'] ?? 0),
                'blocked_count' => (int)($summary['blocked_count'] ?? 0),
                'failed_count' => (int)($summary['failed_count'] ?? 0),
                'sample_results' => array_slice(is_array($summary['results'] ?? null) ? $summary['results'] : [], 0, 25),
            ];

            $this->logBatch([
                'action' => 'finish',
                'actor_user_id' => (int)$user['id'],
                'destination_folder' => $targetFolder,
                'summary' => $auditDetails,
            ]);
            $this->logAudit($maria, (int)$user['id'], $auditAction, $auditDetails);

            $httpStatus = (($summary['blocked_count'] ?? 0) > 0 || ($summary['failed_count'] ?? 0) > 0) ? 207 : 200;
            $this->json($summary, $httpStatus);
        } catch (\JsonException $e) {
            $this->json(['error' => 'Invalid JSON'], 400);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 500);
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

    /**
     * @param mixed $raw
     * @return int[]
     */
    private function normalizeIds($raw): array
    {
        if (!is_array($raw)) {
            return [];
        }
        $result = [];
        $seen = [];
        foreach ($raw as $value) {
            $id = null;
            if (is_int($value)) {
                $id = $value;
            } elseif (is_string($value) && preg_match('/^-?\d+$/', trim($value))) {
                $id = (int)trim($value);
            }
            if ($id === null || $id === 0 || isset($seen[$id])) {
                continue;
            }
            $seen[$id] = true;
            $result[] = $id;
        }
        return $result;
    }

    private function logBatch(array $details): void
    {
        @error_log('webalbum_batch_move ' . json_encode($details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    private function logAudit(Maria $maria, int $userId, string $action, array $details): void
    {
        try {
            $source = 'web';
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $maria->exec(
                "INSERT INTO wa_audit_log (actor_user_id, target_user_id, action, source, ip_address, user_agent, details)\n" .
                "VALUES (?, ?, ?, ?, ?, ?, ?)",
                [
                    $userId,
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

    private function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}

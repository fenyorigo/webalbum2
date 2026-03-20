<?php

declare(strict_types=1);

namespace WebAlbum\Http\Controllers;

use WebAlbum\AuditLogMetaCache;
use WebAlbum\Db\Maria;
use WebAlbum\Db\SqliteIndex;
use WebAlbum\Move\MoveUndoService;
use WebAlbum\UserContext;

final class MoveUndoController
{
    private string $configPath;
    private MoveUndoService $undoService;

    public function __construct(string $configPath)
    {
        $this->configPath = $configPath;
        $this->undoService = new MoveUndoService($configPath);
    }

    public function mediaEligibility(int $id): void
    {
        try {
            [$config, $maria, $sqlite, $user] = $this->authAdmin();
            if ($user === null) {
                return;
            }
            $result = $this->undoService->eligibility()->mediaEligibility(
                $maria,
                $sqlite,
                (string)($config['photos']['root'] ?? ''),
                $id
            );
            $status = !empty($result['available']) ? 200 : 409;
            $this->json($result, $status);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    public function assetEligibility(int $id): void
    {
        try {
            [$config, $maria, $sqlite, $user] = $this->authAdmin();
            unset($sqlite);
            if ($user === null) {
                return;
            }
            $result = $this->undoService->eligibility()->assetEligibility(
                $maria,
                (string)($config['photos']['root'] ?? ''),
                $id
            );
            $status = !empty($result['available']) ? 200 : 409;
            $this->json($result, $status);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    public function undoMedia(int $id): void
    {
        try {
            [$config, $maria, $sqlite, $user] = $this->authAdmin();
            if ($user === null) {
                return;
            }
            $eligibility = $this->undoService->eligibility()->mediaEligibility(
                $maria,
                $sqlite,
                (string)($config['photos']['root'] ?? ''),
                $id
            );
            if (empty($eligibility['available'])) {
                $details = [
                    'media_id' => $id,
                    'eligibility' => $eligibility,
                ];
                $this->logUndo(['success' => false, 'entity' => 'media', 'stage' => 'eligibility', 'media_id' => $id, 'eligibility' => $eligibility]);
                $this->logAudit($maria, (int)$user['id'], 'media_move_undo_denied', $details);
                $this->json(['error' => (string)($eligibility['error'] ?? 'Undo failed'), 'eligibility' => $eligibility], 409);
                return;
            }

            $result = $this->undoService->executeMediaUndo($id, $eligibility);
            $payload = $result['payload'];
            $status = $result['status'];

            if ($status >= 200 && $status < 300 && !empty($payload['ok'])) {
                $details = [
                    'media_id' => $id,
                    'source_audit_id' => (int)($eligibility['audit_id'] ?? 0),
                    'current_path' => (string)($eligibility['current_rel_path'] ?? ''),
                    'intended_original_path' => (string)($eligibility['original_rel_path'] ?? ''),
                    'final_restored_path' => (string)($payload['new_rel_path'] ?? ''),
                    'renamed_due_to_collision' => !empty($payload['renamed_due_to_collision']),
                ];
                $this->logUndo(['success' => true, 'entity' => 'media'] + $details);
                $this->logAudit($maria, (int)$user['id'], 'media_move_undo', $details);
                $this->json([
                    'ok' => true,
                    'undo' => true,
                    'source_audit_id' => (int)($eligibility['audit_id'] ?? 0),
                ] + $payload, 200);
                return;
            }

            $details = [
                'media_id' => $id,
                'source_audit_id' => (int)($eligibility['audit_id'] ?? 0),
                'current_path' => (string)($eligibility['current_rel_path'] ?? ''),
                'intended_original_path' => (string)($eligibility['original_rel_path'] ?? ''),
                'error' => (string)($payload['error'] ?? 'Undo failed'),
            ];
            $this->logUndo(['success' => false, 'entity' => 'media'] + $details);
            $this->logAudit($maria, (int)$user['id'], 'media_move_undo_failed', $details);
            $this->json($payload, $status);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    public function undoAsset(int $id): void
    {
        try {
            [$config, $maria, $sqlite, $user] = $this->authAdmin();
            unset($sqlite);
            if ($user === null) {
                return;
            }
            $eligibility = $this->undoService->eligibility()->assetEligibility(
                $maria,
                (string)($config['photos']['root'] ?? ''),
                $id
            );
            if (empty($eligibility['available'])) {
                $details = [
                    'asset_id' => $id,
                    'eligibility' => $eligibility,
                ];
                $this->logUndo(['success' => false, 'entity' => 'asset', 'stage' => 'eligibility', 'asset_id' => $id, 'eligibility' => $eligibility]);
                $this->logAudit($maria, (int)$user['id'], 'asset_move_undo_denied', $details);
                $this->json(['error' => (string)($eligibility['error'] ?? 'Undo failed'), 'eligibility' => $eligibility], 409);
                return;
            }

            $result = $this->undoService->executeAssetUndo($id, $eligibility);
            $payload = $result['payload'];
            $status = $result['status'];

            if ($status >= 200 && $status < 300 && !empty($payload['ok'])) {
                $details = [
                    'asset_id' => $id,
                    'source_audit_id' => (int)($eligibility['audit_id'] ?? 0),
                    'current_path' => (string)($eligibility['current_rel_path'] ?? ''),
                    'intended_original_path' => (string)($eligibility['original_rel_path'] ?? ''),
                    'final_restored_path' => (string)($payload['new_rel_path'] ?? ''),
                    'renamed_due_to_collision' => !empty($payload['renamed_due_to_collision']),
                ];
                $this->logUndo(['success' => true, 'entity' => 'asset'] + $details);
                $this->logAudit($maria, (int)$user['id'], 'asset_move_undo', $details);
                $this->json([
                    'ok' => true,
                    'undo' => true,
                    'source_audit_id' => (int)($eligibility['audit_id'] ?? 0),
                ] + $payload, 200);
                return;
            }

            $details = [
                'asset_id' => $id,
                'source_audit_id' => (int)($eligibility['audit_id'] ?? 0),
                'current_path' => (string)($eligibility['current_rel_path'] ?? ''),
                'intended_original_path' => (string)($eligibility['original_rel_path'] ?? ''),
                'error' => (string)($payload['error'] ?? 'Undo failed'),
            ];
            $this->logUndo(['success' => false, 'entity' => 'asset'] + $details);
            $this->logAudit($maria, (int)$user['id'], 'asset_move_undo_failed', $details);
            $this->json($payload, $status);
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
        $sqlite = new SqliteIndex((string)$config['sqlite']['path']);
        $user = UserContext::currentUser($maria);
        if ($user === null) {
            $this->json(['error' => 'Not authenticated'], 401);
            return [$config, $maria, $sqlite, null];
        }
        if ((int)($user['is_admin'] ?? 0) !== 1) {
            $this->json(['error' => 'Forbidden'], 403);
            return [$config, $maria, $sqlite, null];
        }
        return [$config, $maria, $sqlite, $user];
    }

    private function logAudit(Maria $db, int $actorId, string $action, array $details = []): void
    {
        try {
            $source = 'web';
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $db->exec(
                "INSERT INTO wa_audit_log (actor_user_id, target_user_id, action, source, ip_address, user_agent, details)
                 VALUES (?, ?, ?, ?, ?, ?, ?)",
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

    private function logUndo(array $details): void
    {
        @error_log('webalbum_move_undo ' . json_encode($details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    private function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}

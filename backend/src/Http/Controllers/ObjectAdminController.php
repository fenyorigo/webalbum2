<?php

declare(strict_types=1);

namespace WebAlbum\Http\Controllers;

use WebAlbum\Db\Maria;
use WebAlbum\Db\SqliteIndex;
use WebAlbum\ObjectSyncService;
use WebAlbum\UserContext;

final class ObjectAdminController
{
    private string $configPath;

    public function __construct(string $configPath)
    {
        $this->configPath = $configPath;
    }

    public function sync(): void
    {
        try {
            [$config, $maria, $admin] = $this->authAdmin();
            $sqlite = new SqliteIndex((string)$config['sqlite']['path']);
            $service = new ObjectSyncService();
            $result = $service->sync($sqlite, $maria);
            $this->logAudit($maria, (int)$admin['id'], 'objects_sync', $result);
            $this->json(['ok' => true] + $result);
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
            throw new \RuntimeException('auth');
        }
        if ((int)($user['is_admin'] ?? 0) !== 1) {
            $this->json(['error' => 'Forbidden'], 403);
            throw new \RuntimeException('admin');
        }
        return [$config, $maria, $user];
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

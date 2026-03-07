<?php

declare(strict_types=1);

namespace WebAlbum\Http\Controllers;

use WebAlbum\Db\Maria;
use WebAlbum\Db\SqliteIndex;
use WebAlbum\UserContext;

final class ObjectResolveController
{
    private string $configPath;

    public function __construct(string $configPath)
    {
        $this->configPath = $configPath;
    }

    public function resolve(): void
    {
        try {
            $config = require $this->configPath;
            $maria = new Maria(
                $config['mariadb']['dsn'],
                $config['mariadb']['user'],
                $config['mariadb']['pass']
            );
            $user = UserContext::currentUser($maria);
            if ($user === null) {
                $this->json(['error' => 'Not authenticated'], 401);
                return;
            }

            $fileId = isset($_GET['file_id']) ? (int)$_GET['file_id'] : 0;
            $assetId = isset($_GET['asset_id']) ? (int)$_GET['asset_id'] : 0;
            $shaIn = strtolower(trim((string)($_GET['sha256'] ?? '')));

            if ($shaIn !== '') {
                if (!preg_match('/^[a-f0-9]{64}$/', $shaIn)) {
                    $this->json(['error' => 'Invalid sha256'], 400);
                    return;
                }
                $object = $maria->query(
                    "SELECT id, sha256, status FROM wa_objects WHERE sha256 = ? LIMIT 1",
                    [$shaIn]
                );
                $this->json([
                    'entity' => '',
                    'file_id' => null,
                    'asset_id' => null,
                    'rel_path' => '',
                    'type' => '',
                    'sha256' => $shaIn,
                    'object' => $object[0] ?? null,
                ]);
                return;
            }

            if ($fileId < 1 && $assetId < 1) {
                $this->json(['error' => 'file_id, asset_id or sha256 is required'], 400);
                return;
            }

            $sqlite = new SqliteIndex((string)$config['sqlite']['path']);
            if ($fileId > 0) {
                $row = $sqlite->query(
                    "SELECT id, rel_path, type, sha256 FROM files WHERE id = ? LIMIT 1",
                    [$fileId]
                );
                if ($row === []) {
                    $this->json(['error' => 'File not found'], 404);
                    return;
                }
                $sha = strtolower(trim((string)($row[0]['sha256'] ?? '')));
                if ($sha === '' || !preg_match('/^[a-f0-9]{64}$/', $sha)) {
                    $this->json(['error' => 'SHA-256 not available for this file'], 409);
                    return;
                }
                $object = $maria->query(
                    "SELECT id, sha256, status FROM wa_objects WHERE sha256 = ? LIMIT 1",
                    [$sha]
                );
                $this->json([
                    'entity' => 'media',
                    'file_id' => (int)$row[0]['id'],
                    'asset_id' => null,
                    'rel_path' => (string)($row[0]['rel_path'] ?? ''),
                    'type' => (string)($row[0]['type'] ?? ''),
                    'sha256' => $sha,
                    'object' => $object[0] ?? null,
                ]);
                return;
            }

            $assetRows = $maria->query(
                "SELECT id, rel_path, type, sha256 FROM wa_assets WHERE id = ? LIMIT 1",
                [$assetId]
            );
            if ($assetRows === []) {
                $this->json(['error' => 'Asset not found'], 404);
                return;
            }
            $asset = $assetRows[0];
            $sha = strtolower(trim((string)($asset['sha256'] ?? '')));
            if ($sha === '' || !preg_match('/^[a-f0-9]{64}$/', $sha)) {
                $sq = $sqlite->query(
                    "SELECT sha256 FROM files WHERE rel_path = ? AND sha256 IS NOT NULL AND length(sha256) = 64 ORDER BY id DESC LIMIT 1",
                    [(string)$asset['rel_path']]
                );
                if ($sq !== []) {
                    $sha = strtolower(trim((string)($sq[0]['sha256'] ?? '')));
                    if ($sha !== '' && preg_match('/^[a-f0-9]{64}$/', $sha)) {
                        $maria->exec("UPDATE wa_assets SET sha256 = ? WHERE id = ?", [$sha, (int)$asset['id']]);
                    }
                }
            }
            if ($sha === '' || !preg_match('/^[a-f0-9]{64}$/', $sha)) {
                $this->json(['error' => 'SHA-256 not available for this asset'], 409);
                return;
            }

            $object = $maria->query(
                "SELECT id, sha256, status FROM wa_objects WHERE sha256 = ? LIMIT 1",
                [$sha]
            );
            $this->json([
                'entity' => 'asset',
                'file_id' => null,
                'asset_id' => (int)$asset['id'],
                'rel_path' => (string)($asset['rel_path'] ?? ''),
                'type' => (string)($asset['type'] ?? ''),
                'sha256' => $sha,
                'object' => $object[0] ?? null,
            ]);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    private function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload);
    }
}

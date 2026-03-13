<?php

declare(strict_types=1);

namespace WebAlbum;

use WebAlbum\Db\Maria;
use WebAlbum\Db\SqliteIndex;

final class ObjectSyncService
{
    public function sync(SqliteIndex $sqlite, Maria $maria): array
    {
        $rows = $sqlite->query(
            "SELECT DISTINCT lower(sha256) AS sha256
             FROM files
             WHERE sha256 IS NOT NULL
               AND length(sha256) = 64"
        );

        $hashes = [];
        foreach ($rows as $row) {
            $sha = strtolower(trim((string)($row['sha256'] ?? '')));
            if ($sha === '' || !preg_match('/^[a-f0-9]{64}$/', $sha)) {
                continue;
            }
            $hashes[$sha] = true;
        }
        $hashes = array_keys($hashes);

        $maria->exec("DROP TEMPORARY TABLE IF EXISTS wa_tmp_object_sync");
        $maria->exec(
            "CREATE TEMPORARY TABLE wa_tmp_object_sync (
                sha256 CHAR(64) NOT NULL PRIMARY KEY
            ) ENGINE=MEMORY"
        );

        foreach (array_chunk($hashes, 500) as $chunk) {
            $values = implode(',', array_fill(0, count($chunk), '(?)'));
            $maria->exec("INSERT IGNORE INTO wa_tmp_object_sync (sha256) VALUES {$values}", $chunk);
        }

        $upserted = $maria->exec(
            "INSERT INTO wa_objects (sha256, status, first_seen_at, last_seen_at, orphaned_at, last_synced_at)
             SELECT t.sha256, 'active', NOW(), NOW(), NULL, NOW()
             FROM wa_tmp_object_sync t
             ON DUPLICATE KEY UPDATE
               status = 'active',
               orphaned_at = NULL,
               last_seen_at = NOW(),
               last_synced_at = NOW()"
        );

        $orphaned = $maria->exec(
            "UPDATE wa_objects o
             LEFT JOIN wa_tmp_object_sync t ON t.sha256 = o.sha256
             SET o.status = 'orphaned',
                 o.orphaned_at = IF(o.orphaned_at IS NULL, NOW(), o.orphaned_at),
                 o.last_synced_at = NOW()
             WHERE t.sha256 IS NULL
               AND o.status = 'active'"
        );

        $counts = $maria->query(
            "SELECT status, COUNT(*) AS c
             FROM wa_objects
             GROUP BY status"
        );
        $active = 0;
        $orphans = 0;
        foreach ($counts as $row) {
            $status = (string)($row['status'] ?? '');
            if ($status === 'active') {
                $active = (int)$row['c'];
            } elseif ($status === 'orphaned') {
                $orphans = (int)$row['c'];
            }
        }

        $maria->exec("DROP TEMPORARY TABLE IF EXISTS wa_tmp_object_sync");

        return [
            'sqlite_sha256_count' => count($hashes),
            'objects_upserted' => $upserted,
            'objects_orphaned' => $orphaned,
            'active_total' => $active,
            'orphaned_total' => $orphans,
        ];
    }

    public function syncMediaRelPath(SqliteIndex $sqlite, Maria $maria, string $relPath, ?string $previousSha = null): array
    {
        $relPath = trim($relPath);
        if ($relPath === '') {
            throw new \InvalidArgumentException('relPath is required');
        }

        $rows = $sqlite->query(
            "SELECT LOWER(sha256) AS sha256
             FROM files
             WHERE rel_path = ?
               AND type IN ('image','video')
             ORDER BY id ASC
             LIMIT 1",
            [$relPath]
        );
        if ($rows === []) {
            throw new \RuntimeException('SQLite file row not found for rel_path: ' . $relPath);
        }

        $newSha = strtolower(trim((string)($rows[0]['sha256'] ?? '')));
        if (!preg_match('/^[a-f0-9]{64}$/', $newSha)) {
            throw new \RuntimeException('SQLite file row has invalid sha256 for rel_path: ' . $relPath);
        }

        $oldSha = strtolower(trim((string)($previousSha ?? '')));
        if (!preg_match('/^[a-f0-9]{64}$/', $oldSha)) {
            $oldSha = '';
        }

        $maria->begin();
        try {
            $maria->exec(
                "INSERT INTO wa_objects (sha256, status, first_seen_at, last_seen_at, orphaned_at, last_synced_at)
                 VALUES (?, 'active', NOW(), NOW(), NULL, NOW())
                 ON DUPLICATE KEY UPDATE
                   status = 'active',
                   orphaned_at = NULL,
                   last_seen_at = NOW(),
                   last_synced_at = NOW()",
                [$newSha]
            );

            $newObjectRows = $maria->query("SELECT id FROM wa_objects WHERE sha256 = ? LIMIT 1", [$newSha]);
            $newObjectId = (int)($newObjectRows[0]['id'] ?? 0);

            if ($oldSha !== '' && $oldSha !== $newSha) {
                $remainingRows = $sqlite->query(
                    "SELECT COUNT(*) AS c
                     FROM files
                     WHERE sha256 IS NOT NULL
                       AND LOWER(sha256) = ?",
                    [$oldSha]
                );
                $remaining = (int)($remainingRows[0]['c'] ?? 0);
                if ($remaining === 0) {
                    $maria->exec(
                        "UPDATE wa_objects
                         SET status = 'orphaned',
                             orphaned_at = IF(orphaned_at IS NULL, NOW(), orphaned_at),
                             last_synced_at = NOW()
                         WHERE sha256 = ?
                           AND status = 'active'",
                        [$oldSha]
                    );
                } else {
                    $maria->exec(
                        "UPDATE wa_objects
                         SET status = 'active',
                             orphaned_at = NULL,
                             last_seen_at = NOW(),
                             last_synced_at = NOW()
                         WHERE sha256 = ?",
                        [$oldSha]
                    );
                }
            }

            $maria->commit();
        } catch (\Throwable $e) {
            $maria->rollBack();
            throw $e;
        }

        return [
            'rel_path' => $relPath,
            'object_id' => $newObjectId,
            'old_sha256' => $oldSha !== '' ? $oldSha : null,
            'new_sha256' => $newSha,
        ];
    }
}

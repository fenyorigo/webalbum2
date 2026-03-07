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
}

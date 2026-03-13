<?php

declare(strict_types=1);

namespace WebAlbum\Media;

use WebAlbum\Db\Maria;

final class MediaTagEdits
{
    public static function findBackupByRelPath(Maria $maria, string $relPath): ?array
    {
        $rows = $maria->query(
            "SELECT * FROM wa_object_backups WHERE original_rel_path = ? LIMIT 1",
            [$relPath]
        );
        return $rows[0] ?? null;
    }

    public static function ensureBackupRecord(
        Maria $maria,
        string $relPath,
        string $backupRelPath,
        ?int $objectId,
        ?string $originalSha,
        int $createdBy
    ): array {
        $existing = self::findBackupByRelPath($maria, $relPath);
        if ($existing !== null) {
            if ($objectId !== null && $objectId > 0) {
                $maria->exec(
                    "UPDATE wa_object_backups SET object_id = ?, updated_at = NOW() WHERE id = ?",
                    [$objectId, (int)$existing['id']]
                );
                $existing['object_id'] = $objectId;
            }
            return $existing;
        }

        $sha = strtolower(trim((string)$originalSha));
        if (!preg_match('/^[a-f0-9]{64}$/', $sha)) {
            $sha = null;
        }

        $maria->exec(
            "INSERT INTO wa_object_backups
                (object_id, original_sha256, original_rel_path, backup_rel_path, status, created_by_user_id)
             VALUES (?, ?, ?, ?, 'pending', ?)",
            [
                ($objectId !== null && $objectId > 0) ? $objectId : null,
                $sha,
                $relPath,
                $backupRelPath,
                $createdBy,
            ]
        );

        $id = $maria->lastInsertId();
        $rows = $maria->query("SELECT * FROM wa_object_backups WHERE id = ? LIMIT 1", [$id]);
        return $rows[0] ?? [
            'id' => $id,
            'object_id' => $objectId,
            'original_sha256' => $sha,
            'original_rel_path' => $relPath,
            'backup_rel_path' => $backupRelPath,
            'status' => 'pending',
            'created_by_user_id' => $createdBy,
        ];
    }

    public static function hasOpenEdit(Maria $maria, string $relPath): bool
    {
        $rows = $maria->query(
            "SELECT id FROM wa_object_tag_edits WHERE rel_path = ? AND status = 'open' LIMIT 1",
            [$relPath]
        );
        return $rows !== [];
    }

    public static function insertEdit(
        Maria $maria,
        int $backupId,
        ?int $objectId,
        string $relPath,
        string $actionType,
        ?string $tagValue,
        array $oldTags,
        ?array $newTags,
        int $createdBy
    ): int {
        $maria->exec(
            "INSERT INTO wa_object_tag_edits
                (backup_id, object_id, rel_path, action_type, tag_value, old_tags_json, new_tags_json, status, created_by_user_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'open', ?)",
            [
                $backupId,
                ($objectId !== null && $objectId > 0) ? $objectId : null,
                $relPath,
                $actionType,
                $tagValue,
                json_encode(array_values($oldTags), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                $newTags !== null ? json_encode(array_values($newTags), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                $createdBy,
            ]
        );
        return $maria->lastInsertId();
    }

    public static function getEditById(Maria $maria, int $id): ?array
    {
        $rows = $maria->query(
            "SELECT e.*, b.backup_rel_path, b.original_sha256, b.original_rel_path, b.status AS backup_status
             FROM wa_object_tag_edits e
             LEFT JOIN wa_object_backups b ON b.id = e.backup_id
             WHERE e.id = ?
             LIMIT 1",
            [$id]
        );
        return $rows[0] ?? null;
    }

    public static function markBackupReady(Maria $maria, int $backupId, ?int $objectId = null): void
    {
        $params = [];
        $sql = "UPDATE wa_object_backups SET status = 'ready', last_error = NULL, updated_at = NOW()";
        if ($objectId !== null && $objectId > 0) {
            $sql .= ", object_id = ?";
            $params[] = $objectId;
        }
        $sql .= " WHERE id = ?";
        $params[] = $backupId;
        $maria->exec($sql, $params);
    }

    public static function markBackupError(Maria $maria, int $backupId, string $error): void
    {
        $maria->exec(
            "UPDATE wa_object_backups SET status = 'error', last_error = ?, updated_at = NOW() WHERE id = ?",
            [mb_substr($error, 0, 2000), $backupId]
        );
    }

    public static function markEditApplied(Maria $maria, int $editId, string $status, ?int $objectId, ?string $sha256): void
    {
        $sha = strtolower(trim((string)$sha256));
        if (!preg_match('/^[a-f0-9]{64}$/', $sha)) {
            $sha = null;
        }
        $maria->exec(
            "UPDATE wa_object_tag_edits
             SET status = ?, object_id = ?, resulting_sha256 = ?, applied_at = NOW(), last_error = NULL, updated_at = NOW()
             WHERE id = ?",
            [
                $status,
                ($objectId !== null && $objectId > 0) ? $objectId : null,
                $sha,
                $editId,
            ]
        );
    }

    public static function markEditError(Maria $maria, int $editId, string $error): void
    {
        $maria->exec(
            "UPDATE wa_object_tag_edits
             SET status = 'error', last_error = ?, updated_at = NOW()
             WHERE id = ?",
            [mb_substr($error, 0, 2000), $editId]
        );
    }
}

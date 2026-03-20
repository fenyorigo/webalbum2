<?php

declare(strict_types=1);

namespace WebAlbum\Media;

use WebAlbum\Db\Maria;

final class MediaMoveSyncService
{
    public function resolveObjectIdBySha(Maria $maria, ?string $sha256): ?int
    {
        $sha = strtolower(trim((string)$sha256));
        if (!preg_match('/^[a-f0-9]{64}$/', $sha)) {
            return null;
        }

        $rows = $maria->query('SELECT id FROM wa_objects WHERE sha256 = ? LIMIT 1', [$sha]);
        $id = (int)($rows[0]['id'] ?? 0);
        return $id > 0 ? $id : null;
    }

    public function detectMoveBlocker(Maria $maria, string $oldRelPath, ?int $objectId): ?array
    {
        $openEditRows = $maria->query(
            "SELECT id
             FROM wa_object_tag_edits
             WHERE rel_path = ?
               AND status = 'open'
             ORDER BY id DESC
             LIMIT 1",
            [$oldRelPath]
        );
        if ($openEditRows !== []) {
            return [
                'error' => 'Move blocked: open tag edits exist for this media',
                'reason' => 'open_tag_edits',
                'blocking_edit_id' => (int)($openEditRows[0]['id'] ?? 0),
            ];
        }

        $jobRows = $maria->query(
            "SELECT id
             FROM wa_jobs
             WHERE job_type = 'media_tag_edit'
               AND status IN ('queued', 'running')
               AND JSON_UNQUOTE(JSON_EXTRACT(payload_json, '$.rel_path')) = ?
             ORDER BY id DESC
             LIMIT 1",
            [$oldRelPath]
        );
        if ($jobRows !== []) {
            return [
                'error' => 'Move blocked: a tag-edit job is active for this media',
                'reason' => 'media_tag_job_active',
                'blocking_job_id' => (int)($jobRows[0]['id'] ?? 0),
            ];
        }

        if ($objectId !== null && $objectId > 0) {
            $transformRows = $maria->query(
                "SELECT id, status
                 FROM wa_object_transform_jobs
                 WHERE object_id = ?
                   AND status IN ('queued', 'running')
                 ORDER BY id DESC
                 LIMIT 1",
                [$objectId]
            );
            if ($transformRows !== []) {
                return [
                    'error' => 'Move blocked: a transform job is active for this object',
                    'reason' => 'transform_job_active',
                    'blocking_transform_job_id' => (int)($transformRows[0]['id'] ?? 0),
                    'blocking_transform_job_status' => (string)($transformRows[0]['status'] ?? ''),
                ];
            }
        }

        return null;
    }

    public function syncMovedMedia(
        Maria $maria,
        int $oldFileId,
        int $newFileId,
        string $oldRelPath,
        string $newRelPath,
        ?int $objectId
    ): array {
        if ($oldFileId < 1 || $newFileId < 1) {
            throw new \InvalidArgumentException('Moved file was not found after indexer update');
        }
        if ($oldRelPath === '' || $newRelPath === '') {
            throw new \InvalidArgumentException('Move paths are required for MariaDB sync');
        }

        $oldHash = $this->relPathHash('media', $oldRelPath);
        $newHash = $this->relPathHash('media', $newRelPath);

        $maria->begin();
        try {
            $favoriteCopies = $maria->exec(
                "INSERT IGNORE INTO wa_favorites (user_id, file_id, created_at)
                 SELECT user_id, ?, created_at
                 FROM wa_favorites
                 WHERE file_id = ?",
                [$newFileId, $oldFileId]
            );
            $favoriteDeletes = $maria->exec(
                "DELETE FROM wa_favorites WHERE file_id = ?",
                [$oldFileId]
            );

            $semanticLinks = $this->remapSemanticMediaLinks($maria, $oldRelPath, $newRelPath, $oldHash, $newHash);

            $backupUpdates = $maria->exec(
                "UPDATE wa_object_backups
                 SET original_rel_path = ?, updated_at = NOW()
                 WHERE original_rel_path = ?",
                [$newRelPath, $oldRelPath]
            );

            $tagEditUpdates = $maria->exec(
                "UPDATE wa_object_tag_edits
                 SET rel_path = ?, updated_at = NOW()
                 WHERE rel_path = ?",
                [$newRelPath, $oldRelPath]
            );

            $maria->commit();
        } catch (\Throwable $e) {
            $maria->rollBack();
            throw $e;
        }

        return [
            'object_id' => $objectId,
            'favorites_inserted' => $favoriteCopies,
            'favorites_deleted' => $favoriteDeletes,
            'semantic_links_updated' => $semanticLinks['updated'],
            'semantic_links_deleted' => $semanticLinks['deleted'],
            'object_backups_updated' => $backupUpdates,
            'object_tag_edits_updated' => $tagEditUpdates,
        ];
    }

    private function remapSemanticMediaLinks(
        Maria $maria,
        string $oldRelPath,
        string $newRelPath,
        string $oldHash,
        string $newHash
    ): array {
        $rows = $maria->query(
            "SELECT id, semantic_tag_id, entity_type, relation_source
             FROM wa_semantic_tag_links
             WHERE entity_type = 'media'
               AND rel_path_hash = ?",
            [$oldHash]
        );

        $updated = 0;
        $deleted = 0;
        foreach ($rows as $row) {
            $id = (int)($row['id'] ?? 0);
            if ($id < 1) {
                continue;
            }
            $semanticTagId = (int)($row['semantic_tag_id'] ?? 0);
            $entityType = (string)($row['entity_type'] ?? 'media');
            $relationSource = (string)($row['relation_source'] ?? 'manual');

            $existing = $maria->query(
                "SELECT id
                 FROM wa_semantic_tag_links
                 WHERE semantic_tag_id = ?
                   AND entity_type = ?
                   AND rel_path_hash = ?
                   AND relation_source = ?
                   AND id <> ?
                 LIMIT 1",
                [$semanticTagId, $entityType, $newHash, $relationSource, $id]
            );
            if ($existing !== []) {
                $deleted += $maria->exec('DELETE FROM wa_semantic_tag_links WHERE id = ?', [$id]);
                continue;
            }

            $updated += $maria->exec(
                "UPDATE wa_semantic_tag_links
                 SET rel_path = ?, rel_path_hash = ?, updated_at = NOW()
                 WHERE id = ?",
                [$newRelPath, $newHash, $id]
            );
        }

        return ['updated' => $updated, 'deleted' => $deleted];
    }

    private function relPathHash(string $entityType, string $relPath): string
    {
        return hash('sha256', strtolower(trim($entityType)) . ':' . trim(str_replace('\\', '/', $relPath), '/'));
    }
}

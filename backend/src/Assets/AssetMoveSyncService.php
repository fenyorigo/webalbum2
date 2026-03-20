<?php

declare(strict_types=1);

namespace WebAlbum\Assets;

use WebAlbum\Db\Maria;

final class AssetMoveSyncService
{
    /** @var string[] */
    private const AUDIO_EXTS = ['mp3', 'm4a', 'flac'];
    /** @var string[] */
    private const DOC_EXTS = ['pdf', 'txt', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'];

    public function detectMoveBlocker(Maria $maria, int $assetId): ?array
    {
        if ($assetId < 1) {
            return null;
        }

        $rows = $maria->query(
            "SELECT id, job_type
             FROM wa_jobs
             WHERE status = 'running'
               AND job_type IN ('doc_thumb', 'doc_pdf_preview')
               AND CAST(JSON_UNQUOTE(JSON_EXTRACT(payload_json, '$.asset_id')) AS UNSIGNED) = ?
             ORDER BY id DESC
             LIMIT 1",
            [$assetId]
        );
        if ($rows === []) {
            return null;
        }

        return [
            'error' => 'Asset move blocked: a running derivative job exists for this asset',
            'reason' => 'running_asset_job',
            'blocking_job_id' => (int)($rows[0]['id'] ?? 0),
            'blocking_job_type' => (string)($rows[0]['job_type'] ?? ''),
        ];
    }

    public function isSupportedAsset(array $asset): bool
    {
        $type = strtolower(trim((string)($asset['type'] ?? '')));
        $ext = strtolower(trim((string)($asset['ext'] ?? '')));
        if ($type === 'audio') {
            return in_array($ext, self::AUDIO_EXTS, true);
        }
        if ($type === 'doc') {
            return in_array($ext, self::DOC_EXTS, true);
        }
        return false;
    }

    public function isDocumentAsset(array $asset): bool
    {
        $type = strtolower(trim((string)($asset['type'] ?? '')));
        $ext = strtolower(trim((string)($asset['ext'] ?? '')));
        return $type === 'doc' && in_array($ext, self::DOC_EXTS, true);
    }

    public function syncMovedAsset(Maria $maria, int $assetId, string $oldRelPath, string $newRelPath, bool $invalidateDerivatives = false): array
    {
        if ($assetId < 1) {
            throw new \InvalidArgumentException('asset_id is required for asset move sync');
        }
        if ($oldRelPath === '' || $newRelPath === '') {
            throw new \InvalidArgumentException('Asset move paths are required');
        }

        $oldHash = $this->relPathHash('asset', $oldRelPath);
        $newHash = $this->relPathHash('asset', $newRelPath);
        $oldDerivativeRows = $invalidateDerivatives
            ? $maria->query(
                "SELECT kind, path, status
                 FROM wa_asset_derivatives
                 WHERE asset_id = ?",
                [$assetId]
            )
            : [];

        $maria->begin();
        try {
            $assetUpdates = $maria->exec(
                "UPDATE wa_assets
                 SET rel_path = ?, updated_at = NOW()
                 WHERE id = ? AND rel_path = ?",
                [$newRelPath, $assetId, $oldRelPath]
            );

            $semanticLinks = $this->remapSemanticAssetLinks($maria, $oldRelPath, $newRelPath, $oldHash, $newHash);
            $derivativeUpdates = 0;
            if ($invalidateDerivatives) {
                $derivativeUpdates = $maria->exec(
                    "UPDATE wa_asset_derivatives
                     SET path = NULL, status = 'pending', error_text = NULL, updated_at = NOW()
                     WHERE asset_id = ?",
                    [$assetId]
                );
            }
            $maria->commit();
        } catch (\Throwable $e) {
            $maria->rollBack();
            throw $e;
        }

        return [
            'asset_rows_updated' => $assetUpdates,
            'semantic_links_updated' => $semanticLinks['updated'],
            'semantic_links_deleted' => $semanticLinks['deleted'],
            'derivative_rows_reset' => $derivativeUpdates ?? 0,
            'derivative_paths_to_cleanup' => $this->derivativePathsFromRows($oldDerivativeRows),
        ];
    }

    private function remapSemanticAssetLinks(
        Maria $maria,
        string $oldRelPath,
        string $newRelPath,
        string $oldHash,
        string $newHash
    ): array {
        $rows = $maria->query(
            "SELECT id, semantic_tag_id, entity_type, relation_source
             FROM wa_semantic_tag_links
             WHERE entity_type = 'asset'
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

            $existing = $maria->query(
                "SELECT id
                 FROM wa_semantic_tag_links
                 WHERE semantic_tag_id = ?
                   AND entity_type = ?
                   AND rel_path_hash = ?
                   AND relation_source = ?
                   AND id <> ?
                 LIMIT 1",
                [
                    (int)($row['semantic_tag_id'] ?? 0),
                    (string)($row['entity_type'] ?? 'asset'),
                    $newHash,
                    (string)($row['relation_source'] ?? 'manual'),
                    $id,
                ]
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

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return string[]
     */
    private function derivativePathsFromRows(array $rows): array
    {
        $paths = [];
        foreach ($rows as $row) {
            $path = trim((string)($row['path'] ?? ''));
            if ($path !== '') {
                $paths[$path] = true;
            }
        }
        return array_values(array_keys($paths));
    }
}

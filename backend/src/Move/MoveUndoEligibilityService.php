<?php

declare(strict_types=1);

namespace WebAlbum\Move;

use WebAlbum\Assets\AssetMoveSyncService;
use WebAlbum\Assets\AssetPaths;
use WebAlbum\Db\Maria;
use WebAlbum\Db\SqliteIndex;
use WebAlbum\Media\MediaMoveSyncService;
use WebAlbum\Media\MediaTagSupport;

final class MoveUndoEligibilityService
{
    private MediaMoveSyncService $mediaMoveSync;
    private AssetMoveSyncService $assetMoveSync;

    public function __construct()
    {
        $this->mediaMoveSync = new MediaMoveSyncService();
        $this->assetMoveSync = new AssetMoveSyncService();
    }

    /**
     * @return array<string, mixed>
     */
    public function mediaEligibility(Maria $maria, SqliteIndex $sqlite, string $photosRoot, int $fileId): array
    {
        if ($fileId < 1) {
            return $this->deny('undo.not_available.item_missing', 'Undo not available: item missing');
        }

        $rows = $sqlite->query(
            'SELECT id, path, rel_path, type, sha256 FROM files WHERE id = ? LIMIT 1',
            [$fileId]
        );
        if ($rows === []) {
            return $this->deny('undo.not_available.item_missing', 'Undo not available: item missing');
        }

        $file = $rows[0];
        $type = strtolower(trim((string)($file['type'] ?? '')));
        if (!in_array($type, ['image', 'video'], true)) {
            return $this->deny('undo.not_available.item_missing', 'Undo not available: item missing');
        }

        $currentRelPath = AssetPaths::normalizeRelPath((string)($file['rel_path'] ?? ''));
        if ($currentRelPath === null) {
            return $this->deny('undo.not_available.current_path_mismatch', 'Undo not available: current path mismatch');
        }

        $sourcePath = MediaTagSupport::resolveOriginalPath(
            (string)($file['path'] ?? ''),
            $currentRelPath,
            $photosRoot
        );
        if ($sourcePath === null || !is_file($sourcePath)) {
            return $this->deny('undo.not_available.item_missing', 'Undo not available: item missing');
        }

        if ($this->isTrashed($maria, $currentRelPath)) {
            return $this->deny('undo.not_available.item_missing', 'Undo not available: item missing');
        }

        $objectId = $this->mediaMoveSync->resolveObjectIdBySha($maria, (string)($file['sha256'] ?? ''));
        $audit = $this->latestMediaMoveAudit($maria, $fileId, $currentRelPath, $objectId);
        if ($audit === null) {
            return $this->deny('undo.not_available.no_history', 'Undo not available: no move history');
        }

        $oldPath = trim((string)($audit['old_path'] ?? ''));
        $newPath = trim((string)($audit['new_path'] ?? ''));
        if ($oldPath === '' || $newPath === '') {
            return $this->deny('undo.not_available.no_history', 'Undo not available: no move history');
        }
        if ($newPath !== $currentRelPath) {
            return $this->deny('undo.not_available.current_path_mismatch', 'Undo not available: current path mismatch');
        }

        $auditObjectId = (int)($audit['object_id'] ?? 0);
        if ($auditObjectId > 0 && $objectId !== null && $objectId > 0 && $auditObjectId !== $objectId) {
            return $this->deny('undo.not_available.current_path_mismatch', 'Undo not available: current path mismatch');
        }

        $targetRelPath = AssetPaths::normalizeRelPath($oldPath);
        if ($targetRelPath === null) {
            return $this->deny('undo.not_available.original_destination_missing', 'Undo not available: original destination missing');
        }
        $targetFolder = $this->folderFromRelPath($targetRelPath);
        if (!$this->destinationParentExists($photosRoot, $targetFolder)) {
            return $this->deny('undo.not_available.original_destination_missing', 'Undo not available: original destination missing');
        }

        $blocker = $this->mediaMoveSync->detectMoveBlocker($maria, $currentRelPath, $objectId);
        if (is_array($blocker)) {
            return $this->deny('undo.not_available.blocked_active_work', 'Undo not available: blocked by active work', $blocker);
        }

        return [
            'available' => true,
            'entity' => 'media',
            'item_id' => $fileId,
            'audit_id' => (int)($audit['id'] ?? 0),
            'current_rel_path' => $currentRelPath,
            'original_rel_path' => $targetRelPath,
            'target_folder' => $targetFolder,
            'target_filename' => basename($targetRelPath),
            'reason_code' => null,
            'error' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function assetEligibility(Maria $maria, string $photosRoot, int $assetId): array
    {
        if ($assetId < 1) {
            return $this->deny('undo.not_available.item_missing', 'Undo not available: item missing');
        }

        $rows = $maria->query(
            'SELECT id, rel_path, type, ext FROM wa_assets WHERE id = ? LIMIT 1',
            [$assetId]
        );
        if ($rows === []) {
            return $this->deny('undo.not_available.item_missing', 'Undo not available: item missing');
        }
        $asset = $rows[0];
        if (!$this->assetMoveSync->isSupportedAsset($asset)) {
            return $this->deny('undo.not_available.item_missing', 'Undo not available: item missing');
        }

        $currentRelPath = AssetPaths::normalizeRelPath((string)($asset['rel_path'] ?? ''));
        if ($currentRelPath === null) {
            return $this->deny('undo.not_available.current_path_mismatch', 'Undo not available: current path mismatch');
        }
        $sourcePath = AssetPaths::joinInside($photosRoot, $currentRelPath);
        if ($sourcePath === null || !is_file($sourcePath)) {
            return $this->deny('undo.not_available.item_missing', 'Undo not available: item missing');
        }
        if ($this->isTrashed($maria, $currentRelPath)) {
            return $this->deny('undo.not_available.item_missing', 'Undo not available: item missing');
        }

        $audit = $this->latestAssetMoveAudit($maria, $assetId);
        if ($audit === null) {
            return $this->deny('undo.not_available.no_history', 'Undo not available: no move history');
        }

        $oldPath = trim((string)($audit['old_rel_path'] ?? ''));
        $newPath = trim((string)($audit['new_rel_path'] ?? ''));
        if ($oldPath === '' || $newPath === '') {
            return $this->deny('undo.not_available.no_history', 'Undo not available: no move history');
        }
        if ($newPath !== $currentRelPath) {
            return $this->deny('undo.not_available.current_path_mismatch', 'Undo not available: current path mismatch');
        }

        $targetRelPath = AssetPaths::normalizeRelPath($oldPath);
        if ($targetRelPath === null) {
            return $this->deny('undo.not_available.original_destination_missing', 'Undo not available: original destination missing');
        }
        $targetFolder = $this->folderFromRelPath($targetRelPath);
        if (!$this->destinationParentExists($photosRoot, $targetFolder)) {
            return $this->deny('undo.not_available.original_destination_missing', 'Undo not available: original destination missing');
        }

        $blocker = $this->assetMoveSync->detectMoveBlocker($maria, $assetId);
        if (is_array($blocker)) {
            return $this->deny('undo.not_available.blocked_active_work', 'Undo not available: blocked by active work', $blocker);
        }

        return [
            'available' => true,
            'entity' => 'asset',
            'item_id' => $assetId,
            'audit_id' => (int)($audit['id'] ?? 0),
            'current_rel_path' => $currentRelPath,
            'original_rel_path' => $targetRelPath,
            'target_folder' => $targetFolder,
            'target_filename' => basename($targetRelPath),
            'reason_code' => null,
            'error' => null,
        ];
    }

    private function latestMediaMoveAudit(Maria $maria, int $fileId, string $currentRelPath, ?int $objectId): ?array
    {
        $where = [
            "action = 'media_move'",
            '(' .
            "JSON_UNQUOTE(JSON_EXTRACT(details, '$.new_path')) = ?" .
            ' OR CAST(COALESCE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(details, \'$.new_media_id\')), \'\'), \'0\') AS UNSIGNED) = ?'
        ];
        $params = [$currentRelPath, $fileId];
        if ($objectId !== null && $objectId > 0) {
            $where[1] .= ' OR CAST(COALESCE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(details, \'$.object_id\')), \'\'), \'0\') AS UNSIGNED) = ?';
            $params[] = $objectId;
        }
        $where[1] .= ')';

        $rows = $maria->query(
            "SELECT id,
                    created_at,
                    JSON_UNQUOTE(JSON_EXTRACT(details, '$.old_path')) AS old_path,
                    JSON_UNQUOTE(JSON_EXTRACT(details, '$.new_path')) AS new_path,
                    CAST(COALESCE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(details, '$.new_media_id')), ''), '0') AS UNSIGNED) AS new_media_id,
                    CAST(COALESCE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(details, '$.object_id')), ''), '0') AS UNSIGNED) AS object_id
             FROM wa_audit_log
             WHERE " . implode(' AND ', $where) . "
             ORDER BY created_at DESC, id DESC
             LIMIT 5",
            $params
        );

        return $rows[0] ?? null;
    }

    private function latestAssetMoveAudit(Maria $maria, int $assetId): ?array
    {
        $rows = $maria->query(
            "SELECT id,
                    created_at,
                    JSON_UNQUOTE(JSON_EXTRACT(details, '$.old_rel_path')) AS old_rel_path,
                    JSON_UNQUOTE(JSON_EXTRACT(details, '$.new_rel_path')) AS new_rel_path,
                    CAST(COALESCE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(details, '$.asset_id')), ''), '0') AS UNSIGNED) AS asset_id
             FROM wa_audit_log
             WHERE action = 'asset_move'
               AND CAST(COALESCE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(details, '$.asset_id')), ''), '0') AS UNSIGNED) = ?
             ORDER BY created_at DESC, id DESC
             LIMIT 1",
            [$assetId]
        );

        return $rows[0] ?? null;
    }

    /**
     * @param array<string,mixed> $extra
     * @return array<string,mixed>
     */
    private function deny(string $reasonCode, string $error, array $extra = []): array
    {
        return [
            'available' => false,
            'reason_code' => $reasonCode,
            'error' => $error,
        ] + $extra;
    }

    private function folderFromRelPath(string $relPath): string
    {
        $dir = trim(str_replace('\\', '/', dirname($relPath)), '/');
        return $dir === '.' ? '' : $dir;
    }

    private function destinationParentExists(string $photosRoot, string $targetFolder): bool
    {
        $root = rtrim($photosRoot, DIRECTORY_SEPARATOR);
        if ($root === '' || !is_dir($root)) {
            return false;
        }
        if ($targetFolder === '') {
            return true;
        }
        $targetFolderPath = AssetPaths::joinInside($photosRoot, $targetFolder);
        return $targetFolderPath !== null && is_dir($targetFolderPath);
    }

    private function isTrashed(Maria $maria, string $relPath): bool
    {
        $rows = $maria->query(
            "SELECT id FROM wa_media_trash WHERE rel_path = ? AND status = 'trashed' LIMIT 1",
            [$relPath]
        );
        return $rows !== [];
    }
}

<?php

declare(strict_types=1);

namespace WebAlbum\Move;

use WebAlbum\Http\Controllers\AssetMoveController;
use WebAlbum\Http\Controllers\MediaMoveController;

final class BatchMoveCoordinator
{
    public const MAX_BATCH = 500;

    private string $configPath;
    private MediaMoveController $mediaMoveController;
    private AssetMoveController $assetMoveController;

    public function __construct(string $configPath)
    {
        $this->configPath = $configPath;
        $this->mediaMoveController = new MediaMoveController($configPath);
        $this->assetMoveController = new AssetMoveController($configPath);
    }

    /**
     * @param array<int, int|string> $ids
     * @return array<string, mixed>
     */
    public function moveItems(array $ids, string $targetRelPath): array
    {
        $results = [];
        $movedCount = 0;
        $renamedCount = 0;
        $blockedCount = 0;
        $failedCount = 0;

        foreach ($ids as $position => $rawId) {
            $signedId = $this->normalizeSignedId($rawId);
            if ($signedId === null || $signedId === 0) {
                $failedCount++;
                $results[] = [
                    'position' => $position + 1,
                    'id' => is_numeric((string)$rawId) ? (int)$rawId : 0,
                    'entity' => 'unknown',
                    'status' => 'failed',
                    'error' => 'Unsupported move item id',
                ];
                continue;
            }

            $entity = $signedId > 0 ? 'media' : 'asset';
            $result = $entity === 'media'
                ? $this->mediaMoveController->executeMove($signedId, ['target_rel_path' => $targetRelPath])
                : $this->assetMoveController->executeMove(abs($signedId), ['target_rel_path' => $targetRelPath]);

            $status = (int)($result['status'] ?? 500);
            $payload = is_array($result['payload'] ?? null) ? $result['payload'] : [];
            $renamed = !empty($payload['renamed_due_to_collision']);
            $oldRelPath = (string)($payload['old_rel_path'] ?? '');
            $newRelPath = (string)($payload['new_rel_path'] ?? '');
            $desiredNewRelPath = (string)($payload['desired_new_rel_path'] ?? '');

            if ($status >= 200 && $status < 300 && !empty($payload['ok'])) {
                if ($renamed) {
                    $renamedCount++;
                    $itemStatus = 'moved_with_rename';
                } else {
                    $movedCount++;
                    $itemStatus = 'moved';
                }

                $results[] = [
                    'position' => $position + 1,
                    'id' => $signedId,
                    'entity' => $entity,
                    'status' => $itemStatus,
                    'error' => null,
                    'old_rel_path' => $oldRelPath,
                    'new_rel_path' => $newRelPath,
                    'desired_new_rel_path' => $desiredNewRelPath,
                    'renamed_due_to_collision' => $renamed,
                ];
                continue;
            }

            $error = trim((string)($payload['error'] ?? 'Batch move item failed'));
            $itemStatus = $this->isBlockedError($error) ? 'blocked' : 'failed';
            if ($itemStatus === 'blocked') {
                $blockedCount++;
            } else {
                $failedCount++;
            }

            $results[] = [
                'position' => $position + 1,
                'id' => $signedId,
                'entity' => $entity,
                'status' => $itemStatus,
                'error' => $error,
                'old_rel_path' => $oldRelPath,
                'new_rel_path' => $newRelPath,
                'desired_new_rel_path' => $desiredNewRelPath,
                'renamed_due_to_collision' => $renamed,
            ];
        }

        return [
            'requested_count' => count($ids),
            'moved_count' => $movedCount,
            'renamed_count' => $renamedCount,
            'blocked_count' => $blockedCount,
            'failed_count' => $failedCount,
            'results' => $results,
        ];
    }

    /**
     * @param int|string $rawId
     */
    private function normalizeSignedId($rawId): ?int
    {
        if (is_int($rawId)) {
            return $rawId;
        }
        if (is_string($rawId) && preg_match('/^-?\d+$/', trim($rawId))) {
            return (int)trim($rawId);
        }
        return null;
    }

    private function isBlockedError(string $error): bool
    {
        return in_array($error, [
            'Move blocked: open tag edits exist for this media',
            'Move blocked: a tag-edit job is active for this media',
            'Move blocked: a transform job is active for this object',
            'Asset move blocked: a running derivative job exists for this asset',
        ], true);
    }
}

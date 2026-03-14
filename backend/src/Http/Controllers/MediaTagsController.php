<?php

declare(strict_types=1);

namespace WebAlbum\Http\Controllers;

use WebAlbum\AuditLogMetaCache;
use WebAlbum\Assets\Jobs;
use WebAlbum\Db\Maria;
use WebAlbum\Db\SqliteIndex;
use WebAlbum\Media\MediaTagEdits;
use WebAlbum\Media\MediaTagSupport;
use WebAlbum\UserContext;

final class MediaTagsController
{
    private string $configPath;

    public function __construct(string $configPath)
    {
        $this->configPath = $configPath;
    }

    public function get(int $id): void
    {
        try {
            if ($id < 1) {
                $this->json(['error' => 'Invalid id'], 400);
                return;
            }

            [$config, $maria, $user] = $this->auth(false);
            if ($user === null) {
                return;
            }

            $sqlite = new SqliteIndex($config['sqlite']['path']);
            $file = MediaTagSupport::fetchFile($sqlite, $id);
            if ($file === null) {
                $this->json(['error' => 'Not Found'], 404);
                return;
            }

            $tags = MediaTagSupport::fetchDisplayTags($sqlite, $id);
            $this->json(['id' => $id, 'tags' => $tags]);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    public function save(int $id): void
    {
        try {
            if ($id < 1) {
                $this->json(['error' => 'Invalid id'], 400);
                return;
            }

            [$config, $maria, $user] = $this->auth(true);
            if ($user === null) {
                return;
            }

            $body = file_get_contents('php://input');
            $data = json_decode($body ?: '', true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($data) || !isset($data['tags']) || !is_array($data['tags'])) {
                throw new \InvalidArgumentException('tags array is required');
            }
            $newTags = MediaTagSupport::normalizeTags($data['tags']);
            $result = $this->queueTagEdit($config, $maria, (int)$user['id'], $id, 'set_tags', null, $newTags);
            if ($result === null) {
                return;
            }
            $this->json($result);
        } catch (\JsonException $e) {
            $this->json(['error' => 'Invalid JSON'], 400);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    public function add(int $id): void
    {
        try {
            if ($id < 1) {
                $this->json(['error' => 'Invalid id'], 400);
                return;
            }
            [$config, $maria, $user] = $this->auth(true);
            if ($user === null) {
                return;
            }
            $data = json_decode(file_get_contents('php://input') ?: '', true, 512, JSON_THROW_ON_ERROR);
            $tag = MediaTagSupport::normalizeTag((string)($data['tag'] ?? ''));
            $result = $this->queueTagEdit($config, $maria, (int)$user['id'], $id, 'add_tag', $tag, null);
            if ($result === null) {
                return;
            }
            $this->json($result);
        } catch (\JsonException $e) {
            $this->json(['error' => 'Invalid JSON'], 400);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    public function remove(int $id): void
    {
        try {
            if ($id < 1) {
                $this->json(['error' => 'Invalid id'], 400);
                return;
            }
            [$config, $maria, $user] = $this->auth(true);
            if ($user === null) {
                return;
            }
            $data = json_decode(file_get_contents('php://input') ?: '', true, 512, JSON_THROW_ON_ERROR);
            $tag = MediaTagSupport::normalizeTag((string)($data['tag'] ?? ''));
            $result = $this->queueTagEdit($config, $maria, (int)$user['id'], $id, 'remove_tag', $tag, null);
            if ($result === null) {
                return;
            }
            $this->json($result);
        } catch (\JsonException $e) {
            $this->json(['error' => 'Invalid JSON'], 400);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    public function restore(int $id): void
    {
        try {
            if ($id < 1) {
                $this->json(['error' => 'Invalid id'], 400);
                return;
            }
            [$config, $maria, $user] = $this->auth(true);
            if ($user === null) {
                return;
            }
            $result = $this->queueTagEdit($config, $maria, (int)$user['id'], $id, 'restore_backup', null, null);
            if ($result === null) {
                return;
            }
            $this->json($result);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    public function batchPreview(): void
    {
        try {
            [$config, $maria, $user] = $this->auth(true);
            if ($user === null) {
                return;
            }

            $data = json_decode(file_get_contents('php://input') ?: '', true, 512, JSON_THROW_ON_ERROR);
            $ids = $this->normalizeIds($data['ids'] ?? null);
            if ($ids === []) {
                throw new \InvalidArgumentException('ids array is required');
            }

            $sqlite = new SqliteIndex($config['sqlite']['path']);
            $items = [];
            $tagCounts = [];
            $commonCounts = [];
            $eligibleCount = 0;

            foreach ($ids as $id) {
                $file = MediaTagSupport::fetchFile($sqlite, $id);
                if ($file === null) {
                    $items[] = [
                        'id' => $id,
                        'status' => 'error',
                        'error' => 'Not Found',
                    ];
                    continue;
                }

                $type = strtolower(trim((string)($file['type'] ?? '')));
                if (!in_array($type, ['image', 'video'], true)) {
                    $items[] = [
                        'id' => $id,
                        'status' => 'unsupported',
                        'type' => $type,
                        'rel_path' => (string)($file['rel_path'] ?? ''),
                        'tags' => [],
                    ];
                    continue;
                }

                $tags = MediaTagSupport::fetchDisplayTags($sqlite, $id);
                $eligibleCount++;
                foreach ($tags as $tag) {
                    $tagCounts[$tag] = ($tagCounts[$tag] ?? 0) + 1;
                    $commonCounts[$tag] = ($commonCounts[$tag] ?? 0) + 1;
                }
                $items[] = [
                    'id' => $id,
                    'status' => 'ok',
                    'type' => $type,
                    'rel_path' => (string)($file['rel_path'] ?? ''),
                    'tags' => $tags,
                ];
            }

            ksort($tagCounts, SORT_NATURAL | SORT_FLAG_CASE);
            $commonTags = [];
            foreach ($commonCounts as $tag => $count) {
                if ($eligibleCount > 0 && $count === $eligibleCount) {
                    $commonTags[] = $tag;
                }
            }
            sort($commonTags, SORT_NATURAL | SORT_FLAG_CASE);

            $this->json([
                'ok' => true,
                'count' => count($ids),
                'eligible_count' => $eligibleCount,
                'items' => $items,
                'available_remove_tags' => array_map(
                    static fn (string $tag): array => ['tag' => $tag, 'count' => (int)$tagCounts[$tag]],
                    array_keys($tagCounts)
                ),
                'common_tags' => $commonTags,
            ]);
        } catch (\JsonException $e) {
            $this->json(['error' => 'Invalid JSON'], 400);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    public function batchEdit(): void
    {
        try {
            [$config, $maria, $user] = $this->auth(true);
            if ($user === null) {
                return;
            }

            $data = json_decode(file_get_contents('php://input') ?: '', true, 512, JSON_THROW_ON_ERROR);
            $ids = $this->normalizeIds($data['ids'] ?? null);
            $removeTags = MediaTagSupport::normalizeTags(is_array($data['remove_tags'] ?? null) ? $data['remove_tags'] : []);
            $addTagRaw = isset($data['add_tag']) ? trim((string)$data['add_tag']) : '';
            $addTag = $addTagRaw !== '' ? MediaTagSupport::normalizeTag($addTagRaw) : null;

            if ($ids === []) {
                throw new \InvalidArgumentException('ids array is required');
            }
            if ($removeTags === [] && $addTag === null) {
                throw new \InvalidArgumentException('Batch tag edit cannot be empty');
            }
            if ($addTag !== null && in_array($addTag, $removeTags, true)) {
                throw new \InvalidArgumentException('Same tag cannot be removed and added');
            }

            $batchId = MediaTagEdits::insertBatch($maria, (int)$user['id'], count($ids), $addTag, $removeTags);
            $sqlite = new SqliteIndex($config['sqlite']['path']);
            $results = [];
            $queuedCount = 0;
            $skippedCount = 0;
            $failedCount = 0;

            foreach ($ids as $id) {
                try {
                    $file = MediaTagSupport::fetchFile($sqlite, $id);
                    if ($file === null) {
                        throw new \RuntimeException('Not Found');
                    }

                    $type = strtolower(trim((string)($file['type'] ?? '')));
                    if (!in_array($type, ['image', 'video'], true)) {
                        throw new \RuntimeException('Only image and video tag edits are supported');
                    }

                    $relPath = (string)($file['rel_path'] ?? '');
                    if (MediaTagEdits::hasOpenEdit($maria, $relPath)) {
                        throw new \RuntimeException('An object tag edit is already open for this file');
                    }

                    $currentTags = MediaTagSupport::fetchDisplayTags($sqlite, $id);
                    $targetTags = $this->applyBatchTags($currentTags, $removeTags, $addTag);
                    if ($targetTags === $currentTags) {
                        $skippedCount++;
                        $results[] = [
                            'id' => $id,
                            'rel_path' => $relPath,
                            'status' => 'skipped',
                            'message' => 'No tag change',
                            'old_tags' => $currentTags,
                            'new_tags' => $targetTags,
                        ];
                        continue;
                    }

                    $path = MediaTagSupport::resolveOriginalPath(
                        (string)($file['path'] ?? ''),
                        $relPath,
                        (string)($config['photos']['root'] ?? '')
                    );
                    if ($path === null || !is_file($path)) {
                        throw new \RuntimeException('File not found');
                    }

                    $maria->begin();
                    try {
                        $objectId = $this->objectIdForSha($maria, (string)($file['sha256'] ?? ''));
                        $backup = MediaTagEdits::ensureBackupRecord(
                            $maria,
                            $relPath,
                            $relPath,
                            $objectId,
                            (string)($file['sha256'] ?? ''),
                            (int)$user['id']
                        );
                        $backupId = (int)($backup['id'] ?? 0);
                        if ($backupId < 1) {
                            throw new \RuntimeException('Failed to create backup record');
                        }

                        $editId = MediaTagEdits::insertEdit(
                            $maria,
                            $backupId,
                            $objectId,
                            $relPath,
                            'set_tags',
                            null,
                            $currentTags,
                            $targetTags,
                            (int)$user['id'],
                            $batchId
                        );
                        Jobs::enqueue($maria, 'media_tag_edit', [
                            'edit_id' => $editId,
                            'rel_path' => $relPath,
                        ]);
                        $jobRows = $maria->query(
                            "SELECT id FROM wa_jobs WHERE job_type = 'media_tag_edit' AND status IN ('queued','running') AND JSON_EXTRACT(payload_json, '$.edit_id') = ? ORDER BY id DESC LIMIT 1",
                            [$editId]
                        );
                        $jobId = (int)($jobRows[0]['id'] ?? 0);
                        $maria->commit();
                    } catch (\Throwable $e) {
                        $maria->rollBack();
                        throw $e;
                    }

                    $queuedCount++;
                    $results[] = [
                        'id' => $id,
                        'rel_path' => $relPath,
                        'status' => 'queued',
                        'edit_id' => $editId,
                        'job_id' => $jobId,
                        'backup_id' => $backupId,
                        'old_tags' => $currentTags,
                        'new_tags' => $targetTags,
                    ];

                    $this->logBatchPerTagAudit($maria, (int)$user['id'], $batchId, $id, $relPath, $currentTags, $targetTags, $editId, $jobId);
                } catch (\Throwable $e) {
                    $failedCount++;
                    $results[] = [
                        'id' => $id,
                        'status' => 'error',
                        'error' => $e->getMessage(),
                    ];
                }
            }

            MediaTagEdits::updateBatchSummary($maria, $batchId, $queuedCount, $skippedCount, $failedCount);
            $this->logAudit($maria, (int)$user['id'], 'media_tag_batch_queue', 'web', [
                'batch_id' => $batchId,
                'requested_count' => count($ids),
                'queued_count' => $queuedCount,
                'skipped_count' => $skippedCount,
                'failed_count' => $failedCount,
                'remove_tags' => $removeTags,
                'add_tag' => $addTag,
                'ids' => $ids,
            ]);

            $this->json([
                'ok' => $failedCount === 0,
                'batch_id' => $batchId,
                'requested_count' => count($ids),
                'processed_count' => count($results),
                'queued_count' => $queuedCount,
                'skipped_count' => $skippedCount,
                'failure_count' => $failedCount,
                'results' => $results,
            ], $failedCount > 0 ? 207 : 200);
        } catch (\JsonException $e) {
            $this->json(['error' => 'Invalid JSON'], 400);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    public function history(): void
    {
        try {
            [$config, $maria, $user] = $this->auth(true);
            if ($user === null) {
                return;
            }

            $limit = isset($_GET['limit']) ? max(1, min(500, (int)$_GET['limit'])) : 100;
            $offset = isset($_GET['offset']) ? max(0, (int)$_GET['offset']) : 0;
            $idFilter = $this->parseCsvIds((string)($_GET['ids'] ?? ''));
            $sqlite = new SqliteIndex($config['sqlite']['path']);

            $where = [];
            $params = [];
            if ($idFilter !== []) {
                $resolvedRelPaths = [];
                foreach ($idFilter as $id) {
                    $file = MediaTagSupport::fetchFile($sqlite, $id);
                    if ($file !== null && !empty($file['rel_path'])) {
                        $resolvedRelPaths[] = (string)$file['rel_path'];
                    }
                }
                $resolvedRelPaths = array_values(array_unique($resolvedRelPaths));
                if ($resolvedRelPaths === []) {
                    $this->json([
                        'items' => [],
                        'total' => 0,
                        'offset' => $offset,
                        'limit' => $limit,
                    ]);
                    return;
                }
                $placeholders = implode(',', array_fill(0, count($resolvedRelPaths), '?'));
                $where[] = 'e.rel_path IN (' . $placeholders . ')';
                $params = array_merge($params, $resolvedRelPaths);
            }

            $whereSql = $where === [] ? '1=1' : implode(' AND ', $where);
            $countRows = $maria->query('SELECT COUNT(*) AS c FROM wa_object_tag_edits e WHERE ' . $whereSql, $params);
            $total = (int)($countRows[0]['c'] ?? 0);

            $rows = $maria->query(
                "SELECT e.id, e.batch_id, e.rel_path, e.action_type, e.tag_value, e.old_tags_json, e.new_tags_json,
                        e.status, e.created_at, e.applied_at, e.last_error, e.created_by_user_id,
                        u.username AS created_by_username
                 FROM wa_object_tag_edits e
                 LEFT JOIN wa_users u ON u.id = e.created_by_user_id
                 WHERE " . $whereSql . "
                 ORDER BY e.id DESC
                 LIMIT " . $limit . " OFFSET " . $offset,
                $params
            );

            $items = [];
            foreach ($rows as $row) {
                $file = MediaTagSupport::fetchFileByRelPath($sqlite, (string)($row['rel_path'] ?? ''));
                $currentFileId = (int)($file['id'] ?? 0);
                $items[] = [
                    'id' => (int)($row['id'] ?? 0),
                    'batch_id' => isset($row['batch_id']) ? (int)$row['batch_id'] : null,
                    'rel_path' => (string)($row['rel_path'] ?? ''),
                    'action_type' => (string)($row['action_type'] ?? ''),
                    'tag_value' => $row['tag_value'] !== null ? (string)$row['tag_value'] : null,
                    'old_tags' => $this->decodeTagsJson($row['old_tags_json'] ?? null),
                    'new_tags' => $this->decodeTagsJson($row['new_tags_json'] ?? null),
                    'status' => (string)($row['status'] ?? ''),
                    'created_at' => (string)($row['created_at'] ?? ''),
                    'applied_at' => $row['applied_at'] !== null ? (string)$row['applied_at'] : null,
                    'last_error' => $row['last_error'] !== null ? (string)$row['last_error'] : null,
                    'created_by_user_id' => isset($row['created_by_user_id']) ? (int)$row['created_by_user_id'] : null,
                    'created_by_username' => $row['created_by_username'] !== null ? (string)$row['created_by_username'] : null,
                    'current_file_id' => $currentFileId > 0 ? $currentFileId : null,
                    'current_type' => $file !== null ? (string)($file['type'] ?? '') : null,
                ];
            }

            $this->json([
                'items' => $items,
                'total' => $total,
                'offset' => $offset,
                'limit' => $limit,
            ]);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    private function queueTagEdit(array $config, Maria $maria, int $actorId, int $id, string $action, ?string $tagValue, ?array $explicitTags): ?array
    {
        $sqlite = new SqliteIndex($config['sqlite']['path']);
        $file = MediaTagSupport::fetchFile($sqlite, $id);
        if ($file === null) {
            $this->json(['error' => 'Not Found'], 404);
            return null;
        }

        $type = strtolower(trim((string)($file['type'] ?? '')));
        if (!in_array($type, ['image', 'video'], true)) {
            throw new \RuntimeException('Only image and video tag edits are supported');
        }

        $relPath = (string)($file['rel_path'] ?? '');
        $path = MediaTagSupport::resolveOriginalPath(
            (string)($file['path'] ?? ''),
            $relPath,
            (string)($config['photos']['root'] ?? '')
        );
        if ($path === null || !is_file($path)) {
            throw new \RuntimeException('File not found');
        }

        if (MediaTagEdits::hasOpenEdit($maria, $relPath)) {
            $this->json(['error' => 'An object tag edit is already open for this file'], 409);
            return null;
        }

        $currentTags = MediaTagSupport::fetchDisplayTags($sqlite, $id);
        $targetTags = $this->targetTags($action, $currentTags, $tagValue, $explicitTags);
        if ($action !== 'restore_backup' && $targetTags === $currentTags) {
            return [
                'ok' => true,
                'queued' => false,
                'id' => $id,
                'action' => $action,
                'tags' => $currentTags,
                'message' => 'No tag change',
            ];
        }

        $objectId = $this->objectIdForSha($maria, (string)($file['sha256'] ?? ''));
        $backupRelPath = $relPath;
        $existingBackup = MediaTagEdits::findBackupByRelPath($maria, $relPath);
        if ($action === 'restore_backup' && $existingBackup === null) {
            throw new \RuntimeException('No original backup exists for this file');
        }

        $maria->begin();
        try {
            $backup = $action === 'restore_backup'
                ? $existingBackup
                : MediaTagEdits::ensureBackupRecord(
                    $maria,
                    $relPath,
                    $backupRelPath,
                    $objectId,
                    (string)($file['sha256'] ?? ''),
                    $actorId
                );
            $backupId = (int)($backup['id'] ?? 0);
            if ($backupId < 1) {
                throw new \RuntimeException('Failed to create backup record');
            }

            if ($action === 'restore_backup' && empty($backup['backup_rel_path'])) {
                throw new \RuntimeException('No backup is registered for this file');
            }

            $editId = MediaTagEdits::insertEdit(
                $maria,
                $backupId,
                $objectId,
                $relPath,
                $action,
                $tagValue,
                $currentTags,
                $action === 'restore_backup' ? null : $targetTags,
                $actorId,
                null
            );
            Jobs::enqueue($maria, 'media_tag_edit', [
                'edit_id' => $editId,
                'rel_path' => $relPath,
            ]);
            $jobRows = $maria->query(
                "SELECT id FROM wa_jobs WHERE job_type = 'media_tag_edit' AND status IN ('queued','running') AND JSON_EXTRACT(payload_json, '$.edit_id') = ? ORDER BY id DESC LIMIT 1",
                [$editId]
            );
            $jobId = (int)($jobRows[0]['id'] ?? 0);
            $maria->commit();
        } catch (\Throwable $e) {
            $maria->rollBack();
            throw $e;
        }

        $this->logAudit($maria, $actorId, 'media_tag_edit_queue', 'web', [
            'media_id' => $id,
            'rel_path' => $relPath,
            'action' => $action,
            'tag' => $tagValue,
            'old_tags' => $currentTags,
            'new_tags' => $targetTags,
            'edit_id' => $editId,
            'backup_id' => $backupId,
            'job_id' => $jobId,
        ]);

        return [
            'ok' => true,
            'queued' => true,
            'id' => $id,
            'action' => $action,
            'edit_id' => $editId,
            'job_id' => $jobId,
            'backup_id' => $backupId,
            'tags' => $action === 'restore_backup' ? $currentTags : $targetTags,
        ];
    }

    private function targetTags(string $action, array $currentTags, ?string $tagValue, ?array $explicitTags): array
    {
        return match ($action) {
            'set_tags' => MediaTagSupport::normalizeTags($explicitTags ?? []),
            'add_tag' => MediaTagSupport::normalizeTags(array_merge($currentTags, [$tagValue ?? ''])),
            'remove_tag' => MediaTagSupport::normalizeTags(array_values(array_filter(
                $currentTags,
                static fn (string $tag): bool => $tag !== $tagValue
            ))),
            'restore_backup' => $currentTags,
            default => throw new \InvalidArgumentException('Unsupported action: ' . $action),
        };
    }

    private function applyBatchTags(array $currentTags, array $removeTags, ?string $addTag): array
    {
        $next = array_values(array_filter(
            $currentTags,
            static fn (string $tag): bool => !in_array($tag, $removeTags, true)
        ));
        if ($addTag !== null) {
            $next[] = $addTag;
        }
        return MediaTagSupport::normalizeTags($next);
    }

    private function normalizeIds(mixed $raw): array
    {
        if (!is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $value) {
            $id = (int)$value;
            if ($id > 0) {
                $out[$id] = $id;
            }
        }
        return array_values($out);
    }

    private function parseCsvIds(string $raw): array
    {
        $parts = preg_split('/[\s,;]+/', trim($raw)) ?: [];
        $out = [];
        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }
            $id = (int)$part;
            if ($id > 0) {
                $out[$id] = $id;
            }
        }
        return array_values($out);
    }

    private function decodeTagsJson(mixed $json): array
    {
        if (!is_string($json) || $json === '') {
            return [];
        }
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return [];
        }
        return array_values(array_filter($decoded, static fn ($tag): bool => is_string($tag) && $tag !== ''));
    }

    private function objectIdForSha(Maria $maria, string $sha): ?int
    {
        $sha = strtolower(trim($sha));
        if (!preg_match('/^[a-f0-9]{64}$/', $sha)) {
            return null;
        }
        $rows = $maria->query('SELECT id FROM wa_objects WHERE sha256 = ? LIMIT 1', [$sha]);
        $id = (int)($rows[0]['id'] ?? 0);
        return $id > 0 ? $id : null;
    }

    private function auth(bool $adminRequired): array
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
            return [$config, $maria, null];
        }
        if ($adminRequired && (int)($user['is_admin'] ?? 0) !== 1) {
            $this->json(['error' => 'Forbidden'], 403);
            return [$config, $maria, null];
        }
        return [$config, $maria, $user];
    }

    private function logBatchPerTagAudit(
        Maria $db,
        int $actorId,
        int $batchId,
        int $mediaId,
        string $relPath,
        array $oldTags,
        array $newTags,
        int $editId,
        int $jobId
    ): void {
        $removed = array_values(array_diff($oldTags, $newTags));
        $added = array_values(array_diff($newTags, $oldTags));
        foreach ($removed as $tag) {
            $this->logAudit($db, $actorId, 'media_tag_batch_remove_tag_queue', 'web', [
                'batch_id' => $batchId,
                'media_id' => $mediaId,
                'rel_path' => $relPath,
                'tag' => $tag,
                'operation' => 'remove_tag',
                'edit_id' => $editId,
                'job_id' => $jobId,
            ]);
        }
        foreach ($added as $tag) {
            $this->logAudit($db, $actorId, 'media_tag_batch_add_tag_queue', 'web', [
                'batch_id' => $batchId,
                'media_id' => $mediaId,
                'rel_path' => $relPath,
                'tag' => $tag,
                'operation' => 'add_tag',
                'edit_id' => $editId,
                'job_id' => $jobId,
            ]);
        }
    }

    private function logAudit(Maria $db, int $actorId, string $action, string $source, ?array $details = null): void
    {
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $db->exec(
                "INSERT INTO wa_audit_log (actor_user_id, target_user_id, action, source, ip_address, user_agent, details)\n" .
                "VALUES (?, ?, ?, ?, ?, ?, ?)",
                [
                    $actorId,
                    null,
                    $action,
                    $source,
                    $ip,
                    $agent,
                    $details ? json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                ]
            );
            AuditLogMetaCache::invalidateIfMissing($action, $source);
        } catch (\Throwable $e) {
            // audit logging must not block editing
        }
    }

    private function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload);
    }
}

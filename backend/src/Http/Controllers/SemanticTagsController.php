<?php

declare(strict_types=1);

namespace WebAlbum\Http\Controllers;

use WebAlbum\AuditLogMetaCache;
use WebAlbum\Db\Maria;
use WebAlbum\Db\SqliteIndex;
use WebAlbum\Query\Model;
use WebAlbum\Search\SearchSupport;
use WebAlbum\Tag\SemanticTags;
use WebAlbum\UserContext;

final class SemanticTagsController
{
    private string $configPath;

    public function __construct(string $configPath)
    {
        $this->configPath = $configPath;
    }

    public function list(): void
    {
        try {
            [$config, $db, $admin] = $this->requireAdmin();
            $sqlite = new SqliteIndex((string)($config['sqlite']['path'] ?? ''));
            $payload = SemanticTags::listTags($db, $sqlite, [
                'q' => (string)($_GET['q'] ?? ''),
                'tag_type' => (string)($_GET['tag_type'] ?? ''),
                'active' => (string)($_GET['active'] ?? 'all'),
                'page' => (int)($_GET['page'] ?? 1),
                'page_size' => (int)($_GET['page_size'] ?? 50),
            ]);
            $payload['is_admin'] = true;
            $payload['tag_types'] = SemanticTags::TYPES;
            $this->json($payload);
        } catch (\RuntimeException $e) {
            $this->json(['error' => $e->getMessage()], $this->httpStatus($e->getCode(), 400));
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    public function tree(): void
    {
        try {
            [$config, $db] = $this->requireAdmin();
            $sqlite = new SqliteIndex((string)($config['sqlite']['path'] ?? ''));
            $this->json([
                'items' => SemanticTags::tree($db, $sqlite),
                'tag_types' => SemanticTags::TYPES,
                'is_admin' => true,
            ]);
        } catch (\RuntimeException $e) {
            $this->json(['error' => $e->getMessage()], $this->httpStatus($e->getCode(), 400));
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    public function browseTree(): void
    {
        try {
            [$config, $db] = $this->requireAuthenticated();
            $sqlite = new SqliteIndex((string)($config['sqlite']['path'] ?? ''));
            $items = array_values(array_filter(
                SemanticTags::tree($db, $sqlite),
                static fn (array $row): bool => (string)($row['tag_type'] ?? '') !== 'person'
            ));
            $this->json([
                'items' => $items,
                'tag_types' => array_values(array_filter(SemanticTags::TYPES, static fn (string $type): bool => $type !== 'person')),
            ]);
        } catch (\RuntimeException $e) {
            $this->json(['error' => $e->getMessage()], $this->httpStatus($e->getCode(), 400));
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    public function lookup(): void
    {
        try {
            [, $db] = $this->requireAuthenticated();
            $q = (string)($_GET['q'] ?? '');
            $limit = max(1, min(50, (int)($_GET['limit'] ?? 12)));
            $this->json([
                'items' => SemanticTags::lookup($db, $q, $limit),
                'tag_types' => SemanticTags::TYPES,
            ]);
        } catch (\RuntimeException $e) {
            $this->json(['error' => $e->getMessage()], $this->httpStatus($e->getCode(), 400));
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    public function get(int $id): void
    {
        try {
            if ($id < 1) {
                throw new \RuntimeException('Semantic tag not found', 404);
            }
            [, $db] = $this->requireAuthenticated();
            $rows = $db->query(
                "SELECT st.id, st.name, st.tag_type, st.parent_tag_id, st.is_active, p.name AS parent_tag_name
                 FROM wa_semantic_tags st
                 LEFT JOIN wa_semantic_tags p ON p.id = st.parent_tag_id
                 WHERE st.id = ? LIMIT 1",
                [$id]
            );
            if ($rows === []) {
                throw new \RuntimeException('Semantic tag not found', 404);
            }
            $this->json(['item' => $rows[0]]);
        } catch (\RuntimeException $e) {
            $this->json(['error' => $e->getMessage()], $this->httpStatus($e->getCode(), 400));
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    public function create(): void
    {
        try {
            [, $db, $admin] = $this->requireAdmin();
            $data = $this->decodeJson();
            $row = $this->createOrUpdate($db, (int)$admin['id'], null, $data);
            $this->logAudit($db, (int)$admin['id'], 'semantic_tag_create', [
                'semantic_tag_id' => (int)$row['id'],
                'name' => (string)$row['name'],
                'tag_type' => (string)$row['tag_type'],
                'parent_tag_id' => isset($row['parent_tag_id']) ? (int)$row['parent_tag_id'] : null,
            ]);
            $this->json(['ok' => true, 'item' => $row], 201);
        } catch (\JsonException $e) {
            $this->json(['error' => 'Invalid JSON'], 400);
        } catch (\RuntimeException $e) {
            $this->json(['error' => $e->getMessage()], $this->httpStatus($e->getCode(), 400));
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    public function update(int $id): void
    {
        try {
            if ($id < 1) {
                $this->json(['error' => 'Semantic tag not found'], 404);
                return;
            }
            [, $db, $admin] = $this->requireAdmin();
            $data = $this->decodeJson();
            $row = $this->createOrUpdate($db, (int)$admin['id'], $id, $data);
            $this->logAudit($db, (int)$admin['id'], 'semantic_tag_update', [
                'semantic_tag_id' => (int)$row['id'],
                'name' => (string)$row['name'],
                'tag_type' => (string)$row['tag_type'],
                'parent_tag_id' => isset($row['parent_tag_id']) ? (int)$row['parent_tag_id'] : null,
                'is_active' => (int)($row['is_active'] ?? 1) === 1,
            ]);
            $this->json(['ok' => true, 'item' => $row]);
        } catch (\JsonException $e) {
            $this->json(['error' => 'Invalid JSON'], 400);
        } catch (\RuntimeException $e) {
            $this->json(['error' => $e->getMessage()], $this->httpStatus($e->getCode(), 400));
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    public function delete(int $id): void
    {
        try {
            if ($id < 1) {
                throw new \RuntimeException('Semantic tag not found', 404);
            }
            [$config, $db, $admin] = $this->requireAdmin();
            $sqlite = new SqliteIndex((string)($config['sqlite']['path'] ?? ''));
            $item = SemanticTags::deleteTag($db, $sqlite, $id);
            $this->logAudit($db, (int)$admin['id'], 'semantic_tag_delete', [
                'semantic_tag_id' => (int)$item['id'],
                'name' => (string)$item['name'],
                'tag_type' => (string)$item['tag_type'],
                'usage_count' => (int)($item['usage_count'] ?? 0),
            ]);
            $this->json(['ok' => true, 'item' => $item]);
        } catch (\RuntimeException $e) {
            $this->json(['error' => $e->getMessage()], $this->httpStatus($e->getCode(), 400));
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    public function assignPreview(): void
    {
        try {
            [$config, $db, $admin] = $this->requireAdmin();
            $sqlite = new SqliteIndex((string)($config['sqlite']['path'] ?? ''));
            $data = $this->decodeJson();
            $resolved = $this->resolveAssignmentTargets($db, $sqlite, $config, $admin, $data);
            $this->json([
                'ok' => true,
                'apply_to' => $resolved['apply_to'],
                'count' => count($resolved['targets']),
                'items' => array_slice($resolved['targets'], 0, 50),
            ]);
        } catch (\JsonException $e) {
            $this->json(['error' => 'Invalid JSON'], 400);
        } catch (\RuntimeException $e) {
            $this->json(['error' => $e->getMessage()], $this->httpStatus($e->getCode(), 400));
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    public function assign(): void
    {
        try {
            [$config, $db, $admin] = $this->requireAdmin();
            $sqlite = new SqliteIndex((string)($config['sqlite']['path'] ?? ''));
            $data = $this->decodeJson();
            $semanticTagId = (int)($data['semantic_tag_id'] ?? 0);
            if ($semanticTagId < 1) {
                throw new \RuntimeException('Semantic tag not found', 404);
            }
            $resolved = $this->resolveAssignmentTargets($db, $sqlite, $config, $admin, $data);
            if ($resolved['targets'] === []) {
                throw new \RuntimeException('No matching media found', 404);
            }
            $result = SemanticTags::assignManualTag($db, $semanticTagId, $resolved['targets'], (int)$admin['id']);
            $this->logAudit($db, (int)$admin['id'], 'semantic_tag_assign_batch', [
                'semantic_tag_id' => $semanticTagId,
                'apply_to' => $resolved['apply_to'],
                'requested_count' => count($resolved['targets']),
                'assigned_count' => (int)$result['assigned_count'],
                'skipped_count' => (int)$result['skipped_count'],
                'sample_rel_paths' => array_slice(array_map(static fn (array $t): string => (string)$t['rel_path'], $resolved['targets']), 0, 20),
            ]);
            $this->json([
                'ok' => true,
                'apply_to' => $resolved['apply_to'],
                'requested_count' => count($resolved['targets']),
                'assigned_count' => (int)$result['assigned_count'],
                'skipped_count' => (int)$result['skipped_count'],
                'tag' => $result['tag'],
            ]);
        } catch (\JsonException $e) {
            $this->json(['error' => 'Invalid JSON'], 400);
        } catch (\RuntimeException $e) {
            $this->json(['error' => $e->getMessage()], $this->httpStatus($e->getCode(), 400));
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    public function target(): void
    {
        try {
            [$config, $db] = $this->requireAuthenticated();
            $sqlite = new SqliteIndex((string)($config['sqlite']['path'] ?? ''));
            $entityType = strtolower(trim((string)($_GET['entity_type'] ?? 'media')));
            $sourceId = (int)($_GET['id'] ?? 0);
            $items = SemanticTags::listTargetTags($db, $sqlite, $entityType, $sourceId);
            $this->json(['items' => $items]);
        } catch (\RuntimeException $e) {
            $this->json(['error' => $e->getMessage()], $this->httpStatus($e->getCode(), 400));
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    public function unassign(): void
    {
        try {
            [$config, $db, $admin] = $this->requireAdmin();
            $sqlite = new SqliteIndex((string)($config['sqlite']['path'] ?? ''));
            $data = $this->decodeJson();
            $semanticTagId = (int)($data['semantic_tag_id'] ?? 0);
            if ($semanticTagId < 1) {
                throw new \RuntimeException('Semantic tag not found', 404);
            }
            $targets = SemanticTags::resolveSelectedTargets($db, $sqlite, is_array($data['ids'] ?? null) ? array_map('intval', $data['ids']) : []);
            if ($targets === []) {
                throw new \RuntimeException('No matching media found', 404);
            }
            $result = SemanticTags::unassignManualTag($db, $semanticTagId, $targets);
            $this->logAudit($db, (int)$admin['id'], 'semantic_tag_unassign', [
                'semantic_tag_id' => $semanticTagId,
                'removed_count' => (int)$result['removed_count'],
                'sample_rel_paths' => array_slice(array_map(static fn (array $t): string => (string)$t['rel_path'], $targets), 0, 20),
            ]);
            $this->json(['ok' => true, 'removed_count' => (int)$result['removed_count']]);
        } catch (\JsonException $e) {
            $this->json(['error' => 'Invalid JSON'], 400);
        } catch (\RuntimeException $e) {
            $this->json(['error' => $e->getMessage()], $this->httpStatus($e->getCode(), 400));
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    private function createOrUpdate(Maria $db, int $actorId, ?int $id, array $data): array
    {
        $nameRaw = preg_replace('/\s+/u', ' ', trim((string)($data['name'] ?? '')));
        $nameRaw = is_string($nameRaw) ? $nameRaw : '';
        $normalizedName = SemanticTags::normalizeName($nameRaw);
        $tagType = SemanticTags::validateType((string)($data['tag_type'] ?? 'generic'));
        $parentTagId = isset($data['parent_tag_id']) && $data['parent_tag_id'] !== '' ? (int)$data['parent_tag_id'] : null;
        $parentTagName = preg_replace('/\s+/u', ' ', trim((string)($data['parent_tag_name'] ?? '')));
        $parentTagName = is_string($parentTagName) ? $parentTagName : '';
        $isActive = array_key_exists('is_active', $data) ? (!empty($data['is_active']) ? 1 : 0) : 1;

        if ($parentTagId === null && $parentTagName !== '') {
            $parentRow = SemanticTags::findByName($db, $parentTagName);
            if ($parentRow !== null) {
                $parentTagId = (int)$parentRow['id'];
            } else {
                $parentNormalizedName = SemanticTags::normalizeName($parentTagName);
                $db->exec(
                    "INSERT INTO wa_semantic_tags
                        (name, normalized_name, tag_type, parent_tag_id, is_active, created_by_user_id, updated_by_user_id, created_at, updated_at)
                     VALUES (?, ?, 'generic', NULL, 1, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)",
                    [$parentTagName, $parentNormalizedName, $actorId, $actorId]
                );
                $parentTagId = $db->lastInsertId();
            }
        }

        $existing = $db->query(
            'SELECT id FROM wa_semantic_tags WHERE normalized_name = ? AND (? IS NULL OR id <> ?) LIMIT 1',
            [$normalizedName, $id, $id]
        );
        if ($existing !== []) {
            throw new \RuntimeException('Tag already exists', 409);
        }

        if ($parentTagId !== null) {
            $parentRows = $db->query(
                'SELECT id, name FROM wa_semantic_tags WHERE id = ? LIMIT 1',
                [$parentTagId]
            );
            if ($parentRows === []) {
                throw new \RuntimeException('Parent tag not found', 404);
            }
            if ($id !== null && $parentTagId === $id) {
                throw new \RuntimeException('Parent tag cannot equal tag', 400);
            }
        }

        if ($id === null) {
            $db->exec(
                "INSERT INTO wa_semantic_tags
                    (name, normalized_name, tag_type, parent_tag_id, is_active, created_by_user_id, updated_by_user_id, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)",
                [$nameRaw, $normalizedName, $tagType, $parentTagId, $isActive, $actorId, $actorId]
            );
            $id = $db->lastInsertId();
        } else {
            $rows = $db->query('SELECT id FROM wa_semantic_tags WHERE id = ? LIMIT 1', [$id]);
            if ($rows === []) {
                throw new \RuntimeException('Semantic tag not found', 404);
            }
            $db->exec(
                "UPDATE wa_semantic_tags
                 SET name = ?, normalized_name = ?, tag_type = ?, parent_tag_id = ?, is_active = ?, updated_by_user_id = ?, updated_at = CURRENT_TIMESTAMP
                 WHERE id = ?",
                [$nameRaw, $normalizedName, $tagType, $parentTagId, $isActive, $actorId, $id]
            );
        }

        $rows = $db->query(
            "SELECT st.id, st.name, st.normalized_name, st.tag_type, st.parent_tag_id, st.is_active,
                    st.created_at, st.updated_at, st.created_by_user_id, st.updated_by_user_id,
                    p.name AS parent_tag_name
             FROM wa_semantic_tags st
             LEFT JOIN wa_semantic_tags p ON p.id = st.parent_tag_id
             WHERE st.id = ? LIMIT 1",
            [$id]
        );
        return $rows[0];
    }

    private function resolveAssignmentTargets(Maria $db, SqliteIndex $sqlite, array $config, array $admin, array $data): array
    {
        $applyTo = strtolower(trim((string)($data['apply_to'] ?? 'selected')));
        if ($applyTo === 'all_results') {
            if (!isset($data['search_query']) || !is_array($data['search_query'])) {
                throw new \RuntimeException('search_query is required for all_results', 400);
            }
            $query = Model::validateSearch($data['search_query']);
            $targets = SearchSupport::resolveSearchTargets(
                $sqlite,
                $db,
                $query,
                (int)$admin['id'],
                true,
                (string)($config['photos']['root'] ?? '')
            );
            return ['apply_to' => 'all_results', 'targets' => $this->normalizeTargetRows($targets, (string)($config['photos']['root'] ?? ''))];
        }
        $signedIds = is_array($data['ids'] ?? null) ? array_map('intval', $data['ids']) : [];
        $targets = SemanticTags::resolveSelectedTargets($db, $sqlite, $signedIds);
        return ['apply_to' => 'selected', 'targets' => $this->normalizeTargetRows($targets, (string)($config['photos']['root'] ?? ''))];
    }

    private function normalizeTargetRows(array $rows, string $photosRoot): array
    {
        $out = [];
        foreach ($rows as $row) {
            $entityType = (string)($row['entity_type'] ?? ($row['entity'] ?? ''));
            $relPath = (string)($row['rel_path'] ?? $row['path'] ?? '');
            if ($entityType === 'media') {
                $relPath = $this->normalizeMediaRelPath($relPath, (string)($row['path'] ?? ''), $photosRoot);
            } else {
                $relPath = trim(str_replace('\\', '/', $relPath), '/');
            }
            if ($entityType === 'media' || $entityType === 'asset') {
                if ($relPath === '') {
                    continue;
                }
                $out[$entityType . ':' . $relPath] = [
                    'entity_type' => $entityType,
                    'source_id' => isset($row['source_id']) ? (int)$row['source_id'] : (int)(($entityType === 'asset') ? ($row['asset_id'] ?? 0) : ($row['id'] ?? 0)),
                    'rel_path' => $relPath,
                    'type' => (string)($row['type'] ?? ''),
                    'path' => (string)($row['path'] ?? $relPath),
                ];
            }
        }
        return array_values($out);
    }

    private function normalizeMediaRelPath(string $relPath, string $path, string $photosRoot): string
    {
        $candidate = trim(str_replace('\\', '/', $relPath));
        $fullPath = trim(str_replace('\\', '/', $path));
        $root = trim(str_replace('\\', '/', $photosRoot));
        foreach ([$candidate, $fullPath] as $value) {
            if ($value === '') {
                continue;
            }
            $normalized = ltrim($value, '/');
            if ($root !== '') {
                $rootTrimmed = trim($root, '/');
                if ($rootTrimmed !== '' && str_starts_with($normalized, $rootTrimmed . '/')) {
                    return ltrim(substr($normalized, strlen($rootTrimmed) + 1), '/');
                }
            }
        }
        return trim(ltrim($candidate !== '' ? $candidate : $fullPath, '/'), '/');
    }

    private function requireAdmin(): array
    {
        $config = require $this->configPath;
        $db = new Maria(
            $config['mariadb']['dsn'],
            $config['mariadb']['user'],
            $config['mariadb']['pass']
        );
        $user = UserContext::currentUser($db);
        if ($user === null) {
            throw new \RuntimeException('Not authenticated', 401);
        }
        if ((int)($user['is_admin'] ?? 0) !== 1) {
            throw new \RuntimeException('Forbidden', 403);
        }
        return [$config, $db, $user];
    }

    private function requireAuthenticated(): array
    {
        $config = require $this->configPath;
        $db = new Maria(
            $config['mariadb']['dsn'],
            $config['mariadb']['user'],
            $config['mariadb']['pass']
        );
        $user = UserContext::currentUser($db);
        if ($user === null) {
            throw new \RuntimeException('Not authenticated', 401);
        }
        return [$config, $db, $user];
    }

    private function decodeJson(): array
    {
        $body = file_get_contents('php://input');
        $data = json_decode($body ?: '', true, 512, JSON_THROW_ON_ERROR);
        return is_array($data) ? $data : [];
    }

    private function httpStatus($code, int $fallback): int
    {
        $status = is_int($code) ? $code : (is_numeric($code) ? (int)$code : 0);
        return ($status >= 100 && $status <= 599) ? $status : $fallback;
    }

    private function logAudit(Maria $db, int $actorId, string $action, array $details): void
    {
        try {
            $db->exec(
                "INSERT INTO wa_audit_log (actor_user_id, target_user_id, action, source, ip_address, user_agent, details)
                 VALUES (?, NULL, ?, 'web', ?, ?, ?)",
                [
                    $actorId,
                    $action,
                    $_SERVER['REMOTE_ADDR'] ?? null,
                    $_SERVER['HTTP_USER_AGENT'] ?? null,
                    json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE),
                ]
            );
            AuditLogMetaCache::invalidateIfMissing($action, 'web');
        } catch (\Throwable $e) {
        }
    }

    private function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    }
}

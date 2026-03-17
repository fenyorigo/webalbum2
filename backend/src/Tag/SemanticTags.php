<?php

declare(strict_types=1);

namespace WebAlbum\Tag;

use WebAlbum\Db\Maria;
use WebAlbum\Db\SqliteIndex;

final class SemanticTags
{
    public const TYPES = ['person', 'event', 'category', 'generic'];

    public static function normalizeName(string $raw): string
    {
        $name = preg_replace('/\s+/u', ' ', trim($raw));
        $name = is_string($name) ? $name : '';
        if ($name === '') {
            throw new \InvalidArgumentException('Invalid tag name');
        }
        if (str_contains($name, '|')) {
            throw new \InvalidArgumentException('Invalid tag name');
        }
        $len = function_exists('mb_strlen') ? mb_strlen($name, 'UTF-8') : strlen($name);
        if ($len > 191) {
            throw new \InvalidArgumentException('Invalid tag name');
        }
        $normalized = function_exists('mb_strtolower')
            ? mb_strtolower($name, 'UTF-8')
            : strtolower($name);
        return trim($normalized);
    }

    public static function validateType(string $tagType): string
    {
        $tagType = strtolower(trim($tagType));
        if (!in_array($tagType, self::TYPES, true)) {
            throw new \InvalidArgumentException('Invalid tag type');
        }
        return $tagType;
    }

    public static function findByName(Maria $db, string $name): ?array
    {
        $normalized = self::normalizeName($name);
        $rows = $db->query(
            "SELECT st.id, st.name, st.normalized_name, st.tag_type, st.parent_tag_id, st.is_active,
                    st.created_at, st.updated_at, p.name AS parent_tag_name
             FROM wa_semantic_tags st
             LEFT JOIN wa_semantic_tags p ON p.id = st.parent_tag_id
             WHERE st.normalized_name = ?
             LIMIT 1",
            [$normalized]
        );
        return $rows[0] ?? null;
    }

    public static function ensureMany(Maria $db, array $names, int $actorId): void
    {
        $seen = [];
        foreach ($names as $rawName) {
            if (!is_string($rawName) || trim($rawName) === '') {
                continue;
            }
            $name = preg_replace('/\s+/u', ' ', trim($rawName));
            $name = is_string($name) ? $name : '';
            if ($name === '') {
                continue;
            }
            $normalized = self::normalizeName($name);
            if (isset($seen[$normalized])) {
                continue;
            }
            $seen[$normalized] = true;
            $existing = $db->query('SELECT id FROM wa_semantic_tags WHERE normalized_name = ? LIMIT 1', [$normalized]);
            if ($existing !== []) {
                continue;
            }
            $db->exec(
                "INSERT INTO wa_semantic_tags
                    (name, normalized_name, tag_type, parent_tag_id, is_active, created_by_user_id, updated_by_user_id, created_at, updated_at)
                 VALUES (?, ?, 'generic', NULL, 1, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)",
                [$name, $normalized, $actorId, $actorId]
            );
        }
    }

    public static function listTags(Maria $db, SqliteIndex $sqlite, array $filters): array
    {
        $q = trim((string)($filters['q'] ?? ''));
        $type = trim((string)($filters['tag_type'] ?? ''));
        $active = trim((string)($filters['active'] ?? 'all'));
        $page = max(1, (int)($filters['page'] ?? 1));
        $pageSize = max(1, min(200, (int)($filters['page_size'] ?? 50)));
        $offset = ($page - 1) * $pageSize;

        $params = [];
        $where = [];
        if ($q !== '') {
            $like = '%' . $q . '%';
            $where[] = '(st.name LIKE ? OR st.normalized_name LIKE ? OR COALESCE(p.name, \'\') LIKE ?)';
            array_push($params, $like, $like, $like);
        }
        if ($type !== '') {
            $where[] = 'st.tag_type = ?';
            $params[] = self::validateType($type);
        }
        if ($active === 'active') {
            $where[] = 'st.is_active = 1';
        } elseif ($active === 'inactive') {
            $where[] = 'st.is_active = 0';
        }
        $whereSql = $where === [] ? '' : (' WHERE ' . implode(' AND ', $where));

        $countRows = $db->query(
            "SELECT COUNT(*) AS c
             FROM wa_semantic_tags st
             LEFT JOIN wa_semantic_tags p ON p.id = st.parent_tag_id" . $whereSql,
            $params
        );
        $total = (int)($countRows[0]['c'] ?? 0);

        $rows = $db->query(
            "SELECT st.id, st.name, st.normalized_name, st.tag_type, st.parent_tag_id, st.is_active,
                    st.created_at, st.updated_at, st.created_by_user_id, st.updated_by_user_id,
                    p.name AS parent_tag_name
             FROM wa_semantic_tags st
             LEFT JOIN wa_semantic_tags p ON p.id = st.parent_tag_id" . $whereSql . "
             ORDER BY st.name ASC
             LIMIT " . $pageSize . " OFFSET " . $offset,
            $params
        );

        $usageCounts = self::usageCounts($sqlite, $db);
        foreach ($rows as &$row) {
            $row['usage_count'] = (int)($usageCounts[(int)($row['id'] ?? 0)] ?? 0);
            $row['usage_state'] = ((int)($row['usage_count'] ?? 0) > 0) ? 'used' : 'orphan';
        }
        unset($row);

        return [
            'items' => $rows,
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
            'total_pages' => max(1, (int)ceil($total / $pageSize)),
        ];
    }

    public static function lookup(Maria $db, string $q, int $limit = 20): array
    {
        $limit = max(1, min(100, $limit));
        $q = trim($q);
        if ($q === '') {
            return $db->query(
                "SELECT st.id, st.name, st.tag_type, st.parent_tag_id, st.is_active, p.name AS parent_tag_name
                 FROM wa_semantic_tags st
                 LEFT JOIN wa_semantic_tags p ON p.id = st.parent_tag_id
                 WHERE st.is_active = 1
                 ORDER BY st.name ASC
                 LIMIT " . $limit
            );
        }
        $like = '%' . $q . '%';
        return $db->query(
            "SELECT st.id, st.name, st.tag_type, st.parent_tag_id, st.is_active, p.name AS parent_tag_name
             FROM wa_semantic_tags st
             LEFT JOIN wa_semantic_tags p ON p.id = st.parent_tag_id
             WHERE st.is_active = 1
               AND (st.name LIKE ? OR st.normalized_name LIKE ? OR COALESCE(p.name, '') LIKE ?)
             ORDER BY st.name ASC
             LIMIT " . $limit,
            [$like, $like, $like]
        );
    }

    public static function relPathHash(string $entityType, string $relPath): string
    {
        return hash('sha256', strtolower(trim($entityType)) . ':' . trim(str_replace('\\', '/', $relPath), '/'));
    }

    public static function resolveSelectedTargets(Maria $db, SqliteIndex $sqlite, array $signedIds): array
    {
        $targets = [];
        foreach ($signedIds as $rawId) {
            $id = (int)$rawId;
            if ($id === 0) {
                continue;
            }
            if ($id > 0) {
                $file = \WebAlbum\Media\MediaTagSupport::fetchFile($sqlite, $id);
                if ($file === null) {
                    continue;
                }
                $relPath = (string)($file['rel_path'] ?? '');
                if ($relPath === '') {
                    continue;
                }
                $targets['media:' . $relPath] = [
                    'entity_type' => 'media',
                    'source_id' => $id,
                    'rel_path' => $relPath,
                    'type' => (string)($file['type'] ?? ''),
                    'path' => (string)($file['path'] ?? $relPath),
                ];
                continue;
            }
            $assetId = abs($id);
            $rows = $db->query(
                'SELECT id, rel_path, type FROM wa_assets WHERE id = ? LIMIT 1',
                [$assetId]
            );
            if ($rows === []) {
                continue;
            }
            $relPath = (string)($rows[0]['rel_path'] ?? '');
            if ($relPath === '') {
                continue;
            }
            $targets['asset:' . $relPath] = [
                'entity_type' => 'asset',
                'source_id' => $assetId,
                'rel_path' => $relPath,
                'type' => (string)($rows[0]['type'] ?? ''),
                'path' => $relPath,
            ];
        }
        return array_values($targets);
    }

    public static function assignManualTag(Maria $db, int $semanticTagId, array $targets, int $actorId): array
    {
        $tagRows = $db->query(
            'SELECT id, name, tag_type FROM wa_semantic_tags WHERE id = ? LIMIT 1',
            [$semanticTagId]
        );
        if ($tagRows === []) {
            throw new \RuntimeException('Semantic tag not found', 404);
        }

        $assigned = 0;
        $skipped = 0;
        foreach ($targets as $target) {
            $entityType = (string)($target['entity_type'] ?? '');
            $relPath = trim((string)($target['rel_path'] ?? ''));
            if ($entityType === '' || $relPath === '') {
                $skipped++;
                continue;
            }
            $hash = self::relPathHash($entityType, $relPath);
            $existing = $db->query(
                "SELECT id FROM wa_semantic_tag_links
                 WHERE semantic_tag_id = ? AND entity_type = ? AND rel_path_hash = ? AND relation_source = 'manual'
                 LIMIT 1",
                [$semanticTagId, $entityType, $hash]
            );
            if ($existing !== []) {
                $skipped++;
                continue;
            }
            $db->exec(
                "INSERT INTO wa_semantic_tag_links
                    (semantic_tag_id, entity_type, rel_path, rel_path_hash, relation_source, created_by_user_id, updated_by_user_id, created_at, updated_at)
                 VALUES (?, ?, ?, ?, 'manual', ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)",
                [$semanticTagId, $entityType, $relPath, $hash, $actorId, $actorId]
            );
            $assigned++;
        }

        return [
            'assigned_count' => $assigned,
            'skipped_count' => $skipped,
            'tag' => $tagRows[0],
        ];
    }

    public static function unassignManualTag(Maria $db, int $semanticTagId, array $targets): array
    {
        $removed = 0;
        foreach ($targets as $target) {
            $entityType = (string)($target['entity_type'] ?? '');
            $relPath = trim((string)($target['rel_path'] ?? ''));
            if ($entityType === '' || $relPath === '') {
                continue;
            }
            $hash = self::relPathHash($entityType, $relPath);
            $removed += $db->exec(
                "DELETE FROM wa_semantic_tag_links
                 WHERE semantic_tag_id = ? AND entity_type = ? AND rel_path_hash = ? AND relation_source = 'manual'",
                [$semanticTagId, $entityType, $hash]
            );
        }
        return ['removed_count' => $removed];
    }

    public static function listTargetTags(Maria $db, SqliteIndex $sqlite, string $entityType, int $sourceId): array
    {
        $entityType = strtolower(trim($entityType));
        if (!in_array($entityType, ['media', 'asset'], true) || $sourceId < 1) {
            return [];
        }

        $relPath = '';
        $embeddedNames = [];
        if ($entityType === 'media') {
            $file = \WebAlbum\Media\MediaTagSupport::fetchFile($sqlite, $sourceId);
            if ($file === null) {
                return [];
            }
            $relPath = (string)($file['rel_path'] ?? '');
            $embeddedNames = \WebAlbum\Media\MediaTagSupport::fetchDisplayTags($sqlite, $sourceId);
        } else {
            $rows = $db->query('SELECT rel_path FROM wa_assets WHERE id = ? LIMIT 1', [$sourceId]);
            if ($rows === []) {
                return [];
            }
            $relPath = (string)($rows[0]['rel_path'] ?? '');
        }
        if ($relPath === '') {
            return [];
        }

        $items = [];
        if ($embeddedNames !== []) {
            $normalized = [];
            foreach ($embeddedNames as $name) {
                $normalized[self::normalizeName($name)] = true;
            }
            foreach (array_chunk(array_keys($normalized), 100) as $chunk) {
                $placeholders = implode(',', array_fill(0, count($chunk), '?'));
                $rows = $db->query(
                    "SELECT st.id, st.name, st.tag_type, st.parent_tag_id, p.name AS parent_tag_name
                     FROM wa_semantic_tags st
                     LEFT JOIN wa_semantic_tags p ON p.id = st.parent_tag_id
                     WHERE st.normalized_name IN ($placeholders)",
                    $chunk
                );
                foreach ($rows as $row) {
                    $id = (int)($row['id'] ?? 0);
                    if ($id < 1) {
                        continue;
                    }
                    $items[$id] = [
                        'id' => $id,
                        'name' => (string)($row['name'] ?? ''),
                        'tag_type' => (string)($row['tag_type'] ?? 'generic'),
                        'parent_tag_id' => isset($row['parent_tag_id']) ? (int)$row['parent_tag_id'] : null,
                        'parent_tag_name' => $row['parent_tag_name'] !== null ? (string)$row['parent_tag_name'] : null,
                        'relation_sources' => ['embedded'],
                    ];
                }
            }
        }

        $hash = self::relPathHash($entityType, $relPath);
        $manualRows = $db->query(
            "SELECT st.id, st.name, st.tag_type, st.parent_tag_id, p.name AS parent_tag_name, l.relation_source
             FROM wa_semantic_tag_links l
             JOIN wa_semantic_tags st ON st.id = l.semantic_tag_id
             LEFT JOIN wa_semantic_tags p ON p.id = st.parent_tag_id
             WHERE l.entity_type = ? AND l.rel_path_hash = ?",
            [$entityType, $hash]
        );
        foreach ($manualRows as $row) {
            $id = (int)($row['id'] ?? 0);
            if ($id < 1) {
                continue;
            }
            if (!isset($items[$id])) {
                $items[$id] = [
                    'id' => $id,
                    'name' => (string)($row['name'] ?? ''),
                    'tag_type' => (string)($row['tag_type'] ?? 'generic'),
                    'parent_tag_id' => isset($row['parent_tag_id']) ? (int)$row['parent_tag_id'] : null,
                    'parent_tag_name' => $row['parent_tag_name'] !== null ? (string)$row['parent_tag_name'] : null,
                    'relation_sources' => [],
                ];
            }
            $source = (string)($row['relation_source'] ?? 'manual');
            if (!in_array($source, $items[$id]['relation_sources'], true)) {
                $items[$id]['relation_sources'][] = $source;
            }
        }

        usort($items, static function (array $a, array $b): int {
            return strcasecmp((string)$a['name'], (string)$b['name']);
        });
        return array_values($items);
    }

    private static function usageCounts(SqliteIndex $sqlite, Maria $db): array
    {
        $rows = $sqlite->query(
            "SELECT t.tag, COUNT(DISTINCT ft.file_id) AS usage_count
             FROM tags t
             LEFT JOIN file_tags ft ON ft.tag_id = t.id
             GROUP BY t.tag"
        );
        $embeddedByNormalized = [];
        foreach ($rows as $row) {
            $tag = (string)($row['tag'] ?? '');
            if ($tag === '' || $tag === 'People' || str_starts_with($tag, 'People|')) {
                continue;
            }
            $normalized = self::normalizeName($tag);
            $embeddedByNormalized[$normalized] = (int)($embeddedByNormalized[$normalized] ?? 0) + (int)($row['usage_count'] ?? 0);
        }
        $manualRows = $db->query(
            "SELECT semantic_tag_id, COUNT(DISTINCT CONCAT(entity_type, ':', rel_path_hash)) AS usage_count
             FROM wa_semantic_tag_links
             WHERE relation_source = 'manual'
             GROUP BY semantic_tag_id"
        );
        $manualById = [];
        foreach ($manualRows as $row) {
            $manualById[(int)($row['semantic_tag_id'] ?? 0)] = (int)($row['usage_count'] ?? 0);
        }
        $tagRows = $db->query('SELECT id, normalized_name FROM wa_semantic_tags');
        $out = [];
        foreach ($tagRows as $row) {
            $id = (int)($row['id'] ?? 0);
            $normalized = (string)($row['normalized_name'] ?? '');
            $out[$id] = (int)($embeddedByNormalized[$normalized] ?? 0) + (int)($manualById[$id] ?? 0);
        }
        return $out;
    }
}

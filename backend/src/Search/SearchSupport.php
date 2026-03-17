<?php

declare(strict_types=1);

namespace WebAlbum\Search;

use WebAlbum\Db\Maria;
use WebAlbum\Db\SqliteIndex;
use WebAlbum\Query\Runner;

final class SearchSupport
{
    public static function stripSemanticTagRules(array $group): array
    {
        $items = is_array($group['items'] ?? null) ? $group['items'] : [];
        $filtered = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            if (isset($item['group'])) {
                $filtered[] = self::stripSemanticTagRules($item);
                continue;
            }
            if (($item['field'] ?? null) === 'semantic_tag') {
                continue;
            }
            $filtered[] = $item;
        }
        $group['items'] = $filtered;
        return $group;
    }

    public static function semanticSearchConstraints(SqliteIndex $sqlite, Maria $maria, array $where, bool $includeDescendants = false, string $photosRoot = ''): array
    {
        $filters = self::extractSemanticTagFilters($where);
        $hasInclude = $filters['include'] !== [];
        $mediaIncludeIds = null;
        $assetIncludePaths = null;
        foreach ($filters['include'] as $tagId) {
            $tagIds = $includeDescendants ? self::expandSemanticTagIds($maria, [$tagId]) : [(int)$tagId];
            $match = self::semanticTagGroupMatchSet($sqlite, $maria, $tagIds, $photosRoot);
            $mediaIncludeIds = self::intersectIdLists($mediaIncludeIds, $match['media_ids']);
            $assetIncludePaths = self::intersectStringLists($assetIncludePaths, $match['asset_paths']);
        }

        $mediaExcludeRelPaths = [];
        $assetExcludeRelPaths = [];
        foreach ($filters['exclude'] as $tagId) {
            $tagIds = $includeDescendants ? self::expandSemanticTagIds($maria, [$tagId]) : [(int)$tagId];
            $match = self::semanticTagGroupMatchSet($sqlite, $maria, $tagIds, $photosRoot);
            foreach ($match['media_rel_paths'] as $relPath) {
                $relPath = trim((string)$relPath);
                if ($relPath !== '') {
                    $mediaExcludeRelPaths[$relPath] = true;
                }
            }
            foreach ($match['asset_paths'] as $relPath) {
                $relPath = trim((string)$relPath);
                if ($relPath !== '') {
                    $assetExcludeRelPaths[$relPath] = true;
                }
            }
        }

        return [
            'has_include' => $hasInclude,
            'media_include_ids' => $hasInclude ? array_values(array_map('intval', $mediaIncludeIds ?? [])) : null,
            'asset_include_rel_paths' => $hasInclude ? array_values($assetIncludePaths ?? []) : null,
            'media_exclude_rel_paths' => array_values(array_keys($mediaExcludeRelPaths)),
            'asset_exclude_rel_paths' => array_values(array_keys($assetExcludeRelPaths)),
            'include_descendants' => $includeDescendants,
        ];
    }

    private static function semanticTagGroupMatchSet(SqliteIndex $sqlite, Maria $maria, array $tagIds, string $photosRoot): array
    {
        $mediaIds = [];
        $mediaRelPaths = [];
        $assetPaths = [];
        foreach ($tagIds as $tagId) {
            $match = self::semanticTagMatchSet($sqlite, $maria, (int)$tagId, $photosRoot);
            foreach ($match['media_ids'] as $id) {
                $id = (int)$id;
                if ($id > 0) {
                    $mediaIds[$id] = true;
                }
            }
            foreach ($match['media_rel_paths'] as $relPath) {
                $relPath = trim((string)$relPath);
                if ($relPath !== '') {
                    $mediaRelPaths[$relPath] = true;
                }
            }
            foreach ($match['asset_paths'] as $relPath) {
                $relPath = trim((string)$relPath);
                if ($relPath !== '') {
                    $assetPaths[$relPath] = true;
                }
            }
        }
        return [
            'media_ids' => array_map('intval', array_keys($mediaIds)),
            'media_rel_paths' => array_values(array_keys($mediaRelPaths)),
            'asset_paths' => array_values(array_keys($assetPaths)),
        ];
    }

    public static function resolveSearchTargets(SqliteIndex $sqlite, Maria $maria, array $query, int $userId, bool $isAdmin, string $photosRoot): array
    {
        $semanticConstraints = self::semanticSearchConstraints(
            $sqlite,
            $maria,
            $query['where'],
            !empty($query['semantic_tag_descendants']),
            $photosRoot
        );
        $baseQuery = $query;
        $baseQuery['where'] = self::stripSemanticTagRules($query['where']);
        $requestedType = self::extractRequestedType($baseQuery['where']);
        $extFilters = self::extractExtFilters($baseQuery['where']);
        $hasNotes = !empty($query['has_notes']);

        if ($extFilters !== []) {
            if ($requestedType === 'image' || $requestedType === 'video' || $requestedType === 'other') {
                return [];
            }
            $typeFilter = ($requestedType === 'doc' || $requestedType === 'audio') ? $requestedType : null;
            return self::searchAssetTargets($maria, $sqlite, $baseQuery, $typeFilter, $extFilters, $hasNotes, $semanticConstraints);
        }

        if ($requestedType === 'doc' || $requestedType === 'audio') {
            return self::searchAssetTargets($maria, $sqlite, $baseQuery, $requestedType, [], $hasNotes, $semanticConstraints);
        }

        $mediaItems = self::searchMediaTargets($sqlite, $maria, $baseQuery, $userId, $isAdmin, $hasNotes, $semanticConstraints);
        if ($requestedType === 'image' || $requestedType === 'video' || $requestedType === 'other') {
            return $mediaItems;
        }

        $assetItems = self::searchAssetTargets($maria, $sqlite, $baseQuery, null, [], $hasNotes, $semanticConstraints);
        return self::mergeTargets($mediaItems, $assetItems, $baseQuery, $photosRoot);
    }

    public static function resolveMediaIds(SqliteIndex $sqlite, Maria $maria, array $query, int $userId, bool $isAdmin): array
    {
        $runner = new Runner($sqlite);
        $semanticConstraints = self::semanticSearchConstraints(
            $sqlite,
            $maria,
            $query['where'],
            !empty($query['semantic_tag_descendants'])
        );
        $baseQuery = $query;
        $baseQuery['where'] = self::stripSemanticTagRules($query['where']);

        $restrictIds = null;
        if (!empty($query['only_favorites'])) {
            $favRows = $maria->query(
                'SELECT file_id FROM wa_favorites WHERE user_id = ?',
                [$userId]
            );
            $restrictIds = array_map(fn (array $row): int => (int)$row['file_id'], $favRows);
        }
        if (!empty($query['has_notes'])) {
            $noteIds = self::mediaIdsWithNotes($sqlite, $maria);
            if ($noteIds === []) {
                return [];
            }
            $restrictIds = self::intersectIdLists($restrictIds, $noteIds);
        }
        if (($semanticConstraints['has_include'] ?? false) === true) {
            $semanticIds = is_array($semanticConstraints['media_include_ids'] ?? null)
                ? array_map('intval', $semanticConstraints['media_include_ids'])
                : [];
            if ($semanticIds === []) {
                return [];
            }
            $restrictIds = self::intersectIdLists($restrictIds, $semanticIds);
            if ($restrictIds === []) {
                return [];
            }
        }

        $excludeTags = self::hiddenTagsForSearch($maria, $userId, $isAdmin);
        $excludeRelPaths = \WebAlbum\Http\Controllers\AdminTrashController::activeTrashedRelPaths($maria);
        if (!empty($semanticConstraints['media_exclude_rel_paths'])) {
            $excludeRelPaths = array_values(array_unique(array_merge(
                $excludeRelPaths,
                array_map('strval', $semanticConstraints['media_exclude_rel_paths'])
            )));
        }

        $folderRelPath = null;
        $folderId = null;
        $folderRecursive = !empty($query['folder_recursive']);
        if (!$folderRecursive && $query['folder_id'] !== null) {
            $folderId = (int)$query['folder_id'];
        } elseif ($query['folder_rel_path'] !== null) {
            $folderRelPath = trim(str_replace('\\', '/', (string)$query['folder_rel_path']), '/');
            if ($folderRelPath === '') {
                $folderRelPath = null;
            }
        }

        $countQuery = $baseQuery;
        $countQuery['offset'] = 0;
        $countQuery['limit'] = 1;
        $countResult = $runner->run($countQuery, $restrictIds, $excludeTags, $excludeRelPaths, $folderRelPath, $folderId, $folderRecursive);
        $total = (int)($countResult['total'] ?? 0);
        if ($total < 1) {
            return [];
        }

        $fullQuery = $baseQuery;
        $fullQuery['offset'] = 0;
        $fullQuery['limit'] = $total;
        $result = $runner->run($fullQuery, $restrictIds, $excludeTags, $excludeRelPaths, $folderRelPath, $folderId, $folderRecursive);
        return array_values(array_unique(array_map(static fn (array $row): int => (int)($row['id'] ?? 0), $result['rows'])));
    }

    public static function searchMediaTargets(SqliteIndex $sqlite, Maria $maria, array $query, int $userId, bool $isAdmin, bool $hasNotes, array $semanticConstraints = []): array
    {
        $runner = new Runner($sqlite);
        $restrictIds = null;
        if (!empty($query['only_favorites'])) {
            $favRows = $maria->query(
                'SELECT file_id FROM wa_favorites WHERE user_id = ?',
                [$userId]
            );
            $restrictIds = array_map(fn (array $row): int => (int)$row['file_id'], $favRows);
        }
        if ($hasNotes) {
            $noteIds = self::mediaIdsWithNotes($sqlite, $maria);
            if ($noteIds === []) {
                return [];
            }
            $restrictIds = self::intersectIdLists($restrictIds, $noteIds);
        }
        if (($semanticConstraints['has_include'] ?? false) === true) {
            $semanticIds = is_array($semanticConstraints['media_include_ids'] ?? null)
                ? array_map('intval', $semanticConstraints['media_include_ids'])
                : [];
            if ($semanticIds === []) {
                return [];
            }
            $restrictIds = self::intersectIdLists($restrictIds, $semanticIds);
            if ($restrictIds === []) {
                return [];
            }
        }
        $excludeTags = self::hiddenTagsForSearch($maria, $userId, $isAdmin);
        $excludeRelPaths = \WebAlbum\Http\Controllers\AdminTrashController::activeTrashedRelPaths($maria);
        if (!empty($semanticConstraints['media_exclude_rel_paths'])) {
            $excludeRelPaths = array_values(array_unique(array_merge(
                $excludeRelPaths,
                array_map('strval', $semanticConstraints['media_exclude_rel_paths'])
            )));
        }
        $folderRelPath = null;
        $folderId = null;
        $folderRecursive = !empty($query['folder_recursive']);
        if (!$folderRecursive && $query['folder_id'] !== null) {
            $folderId = (int)$query['folder_id'];
        } elseif ($query['folder_rel_path'] !== null) {
            $folderRelPath = trim(str_replace('\\', '/', (string)$query['folder_rel_path']), '/');
            if ($folderRelPath === '') {
                $folderRelPath = null;
            }
        }
        $countQuery = $query;
        $countQuery['offset'] = 0;
        $countQuery['limit'] = 1;
        $countResult = $runner->run($countQuery, $restrictIds, $excludeTags, $excludeRelPaths, $folderRelPath, $folderId, $folderRecursive);
        $total = (int)($countResult['total'] ?? 0);
        if ($total < 1) {
            return [];
        }
        $fullQuery = $query;
        $fullQuery['offset'] = 0;
        $fullQuery['limit'] = $total;
        $result = $runner->run($fullQuery, $restrictIds, $excludeTags, $excludeRelPaths, $folderRelPath, $folderId, $folderRecursive);
        return array_map(static function (array $row): array {
            return [
                'id' => (int)($row['id'] ?? 0),
                'asset_id' => null,
                'entity' => 'media',
                'path' => (string)($row['path'] ?? ''),
                'type' => (string)($row['type'] ?? ''),
                'taken_ts' => (int)($row['taken_ts'] ?? 0),
            ];
        }, $result['rows']);
    }

    public static function searchAssetTargets(Maria $maria, SqliteIndex $sqlite, array $query, ?string $typeFilter, array $extFilters, bool $hasNotes, array $semanticConstraints = []): array
    {
        $where = [];
        $params = [];
        $where[] = "NOT EXISTS (SELECT 1 FROM wa_media_trash mt WHERE mt.rel_path = a.rel_path AND mt.status = 'trashed')";
        if (($semanticConstraints['has_include'] ?? false) === true) {
            $includeRelPaths = is_array($semanticConstraints['asset_include_rel_paths'] ?? null)
                ? array_values(array_filter(array_map('strval', $semanticConstraints['asset_include_rel_paths']), static fn (string $v): bool => $v !== ''))
                : [];
            if ($includeRelPaths === []) {
                return [];
            }
            $where[] = self::relPathInClause('a.rel_path', $includeRelPaths);
            $params = array_merge($params, $includeRelPaths);
        }
        $excludeRelPaths = is_array($semanticConstraints['asset_exclude_rel_paths'] ?? null)
            ? array_values(array_filter(array_map('strval', $semanticConstraints['asset_exclude_rel_paths']), static fn (string $v): bool => $v !== ''))
            : [];
        if ($excludeRelPaths !== []) {
            $where[] = 'a.rel_path NOT IN (' . implode(',', array_fill(0, count($excludeRelPaths), '?')) . ')';
            $params = array_merge($params, $excludeRelPaths);
        }
        if ($typeFilter !== null) {
            $where[] = 'a.type = ?';
            $params[] = $typeFilter;
        }
        if ($hasNotes) {
            $where[] = "EXISTS (
                SELECT 1 FROM wa_objects o
                JOIN wa_object_notes n ON n.object_id = o.id
                WHERE LOWER(o.sha256) = LOWER(a.sha256)
            )";
        }
        if ($extFilters !== []) {
            $place = implode(',', array_fill(0, count($extFilters), '?'));
            $where[] = 'a.ext IN (' . $place . ')';
            foreach ($extFilters as $ext) {
                $params[] = $ext;
            }
        }
        foreach (self::extractPathRules($query['where']) as $rule) {
            $pattern = $rule['op'] === 'starts_with'
                ? self::escapeLike((string)$rule['value']) . '%'
                : '%' . self::escapeLike((string)$rule['value']) . '%';
            $where[] = 'a.rel_path LIKE ?';
            $params[] = $pattern;
        }
        foreach (self::extractTakenRules($query['where']) as $rule) {
            if (($rule['op'] ?? '') === 'between') {
                [$start, $end] = self::dateRange((string)$rule['value'][0], (string)$rule['value'][1]);
                $where[] = '(a.mtime BETWEEN ? AND ?)';
                $params[] = $start;
                $params[] = $end;
            } elseif (($rule['op'] ?? '') === 'before') {
                $where[] = '(a.mtime <= ?)';
                $params[] = self::dateEnd((string)$rule['value']);
            } else {
                $where[] = '(a.mtime >= ?)';
                $params[] = self::dateStart((string)$rule['value']);
            }
        }
        $tagFilters = self::extractTagFilters($query['where']);
        if ($tagFilters['include'] !== []) {
            $parts = [];
            foreach ($tagFilters['include'] as $tag) {
                $parts[] = "JSON_SEARCH(COALESCE(am.tags_json, JSON_ARRAY()), 'one', ?) IS NOT NULL";
                $params[] = $tag;
            }
            $where[] = '(' . implode($tagFilters['mode'] === 'ANY' ? ' OR ' : ' AND ', $parts) . ')';
        }
        foreach ($tagFilters['exclude'] as $tag) {
            $where[] = "JSON_SEARCH(COALESCE(am.tags_json, JSON_ARRAY()), 'one', ?) IS NULL";
            $params[] = $tag;
        }
        $folderClause = self::assetFolderClause($query, $sqlite);
        if ($folderClause !== null) {
            $where[] = $folderClause['sql'];
            $params = array_merge($params, $folderClause['params']);
        }
        if (!empty($query['only_favorites'])) {
            return [];
        }
        $whereSql = $where === [] ? '1=1' : implode(' AND ', $where);
        $countRows = $maria->query(
            'SELECT COUNT(*) AS c FROM wa_assets a LEFT JOIN wa_asset_meta am ON am.asset_id = a.id WHERE ' . $whereSql,
            $params
        );
        $total = (int)($countRows[0]['c'] ?? 0);
        if ($total < 1) {
            return [];
        }
        $rows = $maria->query(
            "SELECT a.id AS asset_id, a.rel_path, a.type, a.ext, a.mtime
             FROM wa_assets a LEFT JOIN wa_asset_meta am ON am.asset_id = a.id
             WHERE " . $whereSql . ' ' . self::assetOrder($query['sort'] ?? null) . ' LIMIT ' . $total . ' OFFSET 0',
            $params
        );
        return array_map(static function (array $row): array {
            $assetId = (int)($row['asset_id'] ?? 0);
            return [
                'id' => -$assetId,
                'asset_id' => $assetId,
                'entity' => 'asset',
                'path' => (string)($row['rel_path'] ?? ''),
                'type' => (string)($row['type'] ?? ''),
                'taken_ts' => (int)($row['mtime'] ?? 0),
            ];
        }, $rows);
    }

    public static function mergeTargets(array $mediaItems, array $assetItems, array $query, string $photosRoot): array
    {
        $assetPathSet = [];
        foreach ($assetItems as $row) {
            $key = self::normalizeRelPathForCompare((string)($row['path'] ?? ''), $photosRoot);
            if ($key !== '') {
                $assetPathSet[$key] = true;
            }
        }
        $assetManagedExt = [
            'pdf' => true, 'txt' => true, 'doc' => true, 'docx' => true,
            'xls' => true, 'xlsx' => true, 'ppt' => true, 'pptx' => true,
            'mp3' => true, 'm4a' => true, 'flac' => true,
        ];
        $mediaFiltered = [];
        foreach ($mediaItems as $row) {
            $key = self::normalizeRelPathForCompare((string)($row['path'] ?? ''), $photosRoot);
            $ext = strtolower((string)pathinfo((string)($row['path'] ?? ''), PATHINFO_EXTENSION));
            $isAssetManaged = isset($assetManagedExt[$ext]);
            $isOtherType = strtolower((string)($row['type'] ?? '')) === 'other';
            if (($key !== '' && isset($assetPathSet[$key])) || ($isOtherType && $isAssetManaged)) {
                continue;
            }
            $mediaFiltered[] = $row;
        }
        $combined = array_merge($mediaFiltered, $assetItems);
        $sort = $query['sort'] ?? ['field' => 'path', 'dir' => 'asc'];
        $field = $sort['field'] ?? 'path';
        $dir = strtolower((string)($sort['dir'] ?? 'asc')) === 'desc' ? -1 : 1;
        usort($combined, static function (array $a, array $b) use ($field, $dir): int {
            if ($field === 'taken') {
                $aa = (int)($a['taken_ts'] ?? 0);
                $bb = (int)($b['taken_ts'] ?? 0);
                if ($aa === $bb) {
                    return $dir * strcmp((string)($a['path'] ?? ''), (string)($b['path'] ?? ''));
                }
                return $dir * ($aa <=> $bb);
            }
            $cmp = strcasecmp((string)($a['path'] ?? ''), (string)($b['path'] ?? ''));
            if ($cmp !== 0) {
                return $dir * $cmp;
            }
            return $dir * (((int)($a['id'] ?? 0)) <=> ((int)($b['id'] ?? 0)));
        });
        return $combined;
    }

    public static function hiddenTagsForSearch(Maria $maria, int $userId, bool $isAdmin): array
    {
        $tags = [];
        $globalHidden = $maria->query(
            "SELECT tag FROM wa_tag_prefs_global WHERE is_hidden = 1"
        );
        foreach ($globalHidden as $row) {
            $tag = trim((string)($row['tag'] ?? ''));
            if ($tag !== '') {
                $tags[$tag] = true;
            }
        }
        if (!$isAdmin) {
            $userHidden = $maria->query(
                "SELECT tag FROM wa_tag_prefs_user WHERE user_id = ? AND is_hidden = 1",
                [$userId]
            );
            foreach ($userHidden as $row) {
                $tag = trim((string)($row['tag'] ?? ''));
                if ($tag !== '') {
                    $tags[$tag] = true;
                }
            }
        }
        return array_keys($tags);
    }

    public static function mediaIdsWithNotes(SqliteIndex $sqlite, Maria $maria): array
    {
        $rows = $maria->query(
            "SELECT DISTINCT o.sha256
             FROM wa_objects o
             JOIN wa_object_notes n ON n.object_id = o.id"
        );
        if ($rows === []) {
            return [];
        }
        $ids = [];
        foreach ($rows as $row) {
            $sha = strtolower(trim((string)($row['sha256'] ?? '')));
            if (!preg_match('/^[a-f0-9]{64}$/', $sha)) {
                continue;
            }
            $fileRows = $sqlite->query(
                'SELECT id FROM files WHERE LOWER(sha256) = ?',
                [$sha]
            );
            foreach ($fileRows as $fileRow) {
                $id = (int)($fileRow['id'] ?? 0);
                if ($id > 0) {
                    $ids[$id] = true;
                }
            }
        }
        return array_map('intval', array_keys($ids));
    }

    public static function intersectIdLists(?array $current, array $next): array
    {
        $nextSet = [];
        foreach ($next as $id) {
            $id = (int)$id;
            if ($id > 0) {
                $nextSet[$id] = true;
            }
        }
        if ($current === null) {
            return array_map('intval', array_keys($nextSet));
        }
        $out = [];
        foreach ($current as $id) {
            $id = (int)$id;
            if (isset($nextSet[$id])) {
                $out[$id] = true;
            }
        }
        return array_map('intval', array_keys($out));
    }

    public static function intersectStringLists(?array $current, array $next): array
    {
        $nextSet = [];
        foreach ($next as $value) {
            $value = trim((string)$value);
            if ($value !== '') {
                $nextSet[$value] = true;
            }
        }
        if ($current === null) {
            return array_keys($nextSet);
        }
        $out = [];
        foreach ($current as $value) {
            $value = trim((string)$value);
            if ($value !== '' && isset($nextSet[$value])) {
                $out[$value] = true;
            }
        }
        return array_keys($out);
    }

    private static function extractRequestedType(array $group): ?string
    {
        foreach (self::flattenRules($group) as $rule) {
            if (($rule['field'] ?? null) === 'type' && ($rule['op'] ?? null) === 'is') {
                return (string)$rule['value'];
            }
        }
        return null;
    }

    private static function extractExtFilters(array $group): array
    {
        $exts = [];
        foreach (self::flattenRules($group) as $rule) {
            if (($rule['field'] ?? null) === 'ext' && ($rule['op'] ?? null) === 'is') {
                $ext = strtolower((string)($rule['value'] ?? ''));
                if ($ext !== '') {
                    $exts[$ext] = true;
                }
            }
        }
        return array_keys($exts);
    }

    private static function extractSemanticTagFilters(array $group): array
    {
        $include = [];
        $exclude = [];
        foreach (self::flattenRules($group) as $rule) {
            if (($rule['field'] ?? null) !== 'semantic_tag') {
                continue;
            }
            $id = (int)($rule['value'] ?? 0);
            if ($id < 1) {
                continue;
            }
            if (($rule['op'] ?? null) === 'is_not') {
                $exclude[$id] = true;
            } else {
                $include[$id] = true;
            }
        }
        return [
            'include' => array_map('intval', array_keys($include)),
            'exclude' => array_map('intval', array_keys($exclude)),
        ];
    }

    private static function extractPathRules(array $group): array
    {
        $rules = [];
        foreach (self::flattenRules($group) as $rule) {
            if (($rule['field'] ?? null) === 'path' && in_array((string)($rule['op'] ?? ''), ['contains', 'starts_with'], true)) {
                $rules[] = ['op' => (string)$rule['op'], 'value' => (string)$rule['value']];
            }
        }
        return $rules;
    }

    private static function extractTakenRules(array $group): array
    {
        $rules = [];
        foreach (self::flattenRules($group) as $rule) {
            if (($rule['field'] ?? null) === 'taken') {
                $rules[] = $rule;
            }
        }
        return $rules;
    }

    private static function extractTagFilters(array $where): array
    {
        $include = [];
        $exclude = [];
        $mode = 'ALL';
        $items = is_array($where['items'] ?? null) ? $where['items'] : [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            if (isset($item['group']) && is_array($item['items'] ?? null)) {
                $allTagIs = true;
                foreach ($item['items'] as $child) {
                    if (!is_array($child) || ($child['field'] ?? null) !== 'tag' || ($child['op'] ?? null) !== 'is') {
                        $allTagIs = false;
                        break;
                    }
                }
                if ($allTagIs) {
                    $mode = (($item['group'] ?? 'ALL') === 'ANY') ? 'ANY' : 'ALL';
                    foreach ($item['items'] as $child) {
                        $tag = trim((string)($child['value'] ?? ''));
                        if ($tag !== '') {
                            $include[] = $tag;
                        }
                    }
                }
                continue;
            }
            if (($item['field'] ?? null) === 'tag' && ($item['op'] ?? null) === 'is_not') {
                $tag = trim((string)($item['value'] ?? ''));
                if ($tag !== '') {
                    $exclude[] = $tag;
                }
            }
        }
        return [
            'mode' => $mode,
            'include' => array_values(array_unique($include)),
            'exclude' => array_values(array_unique($exclude)),
        ];
    }

    private static function flattenRules(array $group): array
    {
        $out = [];
        $items = is_array($group['items'] ?? null) ? $group['items'] : [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            if (isset($item['group'])) {
                $out = array_merge($out, self::flattenRules($item));
            } else {
                $out[] = $item;
            }
        }
        return $out;
    }

    private static function assetFolderClause(array $query, SqliteIndex $sqlite): ?array
    {
        $folderRecursive = !empty($query['folder_recursive']);
        if (!empty($query['folder_id']) && !$folderRecursive) {
            $rows = $sqlite->query('SELECT rel_path FROM directories WHERE id = ? LIMIT 1', [(int)$query['folder_id']]);
            if ($rows === []) {
                return ['sql' => '1=0', 'params' => []];
            }
            $folder = trim(str_replace('\\', '/', (string)$rows[0]['rel_path']), '/');
            if ($folder === '') {
                return null;
            }
            return [
                'sql' => '(a.rel_path LIKE ? AND a.rel_path NOT LIKE ?)',
                'params' => [
                    self::escapeLike($folder) . '/%',
                    self::escapeLike($folder) . '/%/%',
                ],
            ];
        }
        if (!empty($query['folder_rel_path'])) {
            $folder = trim(str_replace('\\', '/', (string)$query['folder_rel_path']), '/');
            if ($folder === '') {
                return null;
            }
            return [
                'sql' => '(a.rel_path = ? OR a.rel_path LIKE ?)',
                'params' => [$folder, self::escapeLike($folder) . '/%'],
            ];
        }
        return null;
    }

    private static function assetOrder(?array $sort): string
    {
        $field = (string)($sort['field'] ?? 'path');
        $dir = strtolower((string)($sort['dir'] ?? 'asc')) === 'desc' ? 'DESC' : 'ASC';
        if ($field === 'taken') {
            return 'ORDER BY a.mtime ' . $dir . ', a.rel_path ' . $dir;
        }
        return 'ORDER BY a.rel_path ' . $dir;
    }

    private static function dateStart(string $date): int
    {
        $tz = new \DateTimeZone(date_default_timezone_get());
        $dt = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $date . ' 00:00:00', $tz);
        if ($dt === false) {
            throw new \RuntimeException('Invalid date: ' . $date);
        }
        return $dt->getTimestamp();
    }

    private static function dateEnd(string $date): int
    {
        $tz = new \DateTimeZone(date_default_timezone_get());
        $dt = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $date . ' 23:59:59', $tz);
        if ($dt === false) {
            throw new \RuntimeException('Invalid date: ' . $date);
        }
        return $dt->getTimestamp();
    }

    private static function dateRange(string $start, string $end): array
    {
        return [self::dateStart($start), self::dateEnd($end)];
    }

    private static function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    private static function relPathInClause(string $column, array $values): string
    {
        $parts = [];
        foreach (array_chunk($values, 250) as $chunk) {
            $parts[] = $column . ' IN (' . implode(',', array_fill(0, count($chunk), '?')) . ')';
        }
        return '(' . implode(' OR ', $parts) . ')';
    }

    private static function normalizeRelPathForCompare(string $path, string $photosRoot): string
    {
        $path = str_replace('\\', '/', trim($path));
        $root = rtrim(str_replace('\\', '/', trim($photosRoot)), '/');
        if ($path === '') {
            return '';
        }
        if ($root !== '' && str_starts_with($path, $root . '/')) {
            $path = substr($path, strlen($root) + 1);
        }
        return ltrim($path, '/');
    }

    private static function semanticTagMatchSet(SqliteIndex $sqlite, Maria $maria, int $semanticTagId, string $photosRoot): array
    {
        $rows = $maria->query(
            'SELECT id, name, normalized_name FROM wa_semantic_tags WHERE id = ? LIMIT 1',
            [$semanticTagId]
        );
        if ($rows === []) {
            return [
                'media_ids' => [],
                'media_rel_paths' => [],
                'asset_paths' => [],
            ];
        }

        $name = (string)($rows[0]['name'] ?? '');
        $normalizedName = (string)($rows[0]['normalized_name'] ?? '');
        $tagVariants = self::semanticNameVariants($name, $normalizedName);

        $mediaIds = [];
        $mediaRelPaths = [];
        if ($tagVariants !== []) {
            $parts = [];
            $params = [];
            foreach ($tagVariants as $variant) {
                $parts[] = 'LOWER(t.tag) = ?';
                $params[] = $variant;
            }
            $tagRows = $sqlite->query(
                'SELECT DISTINCT f.id, f.rel_path, f.path
                 FROM files f
                 JOIN file_tags ft ON ft.file_id = f.id
                 JOIN tags t ON t.id = ft.tag_id
                 WHERE ' . implode(' OR ', $parts),
                $params
            );
            foreach ($tagRows as $row) {
                $id = (int)($row['id'] ?? 0);
                $relPath = trim((string)($row['rel_path'] ?? ''));
                if ($id > 0) {
                    $mediaIds[$id] = true;
                }
                if ($relPath !== '') {
                    $mediaRelPaths[$relPath] = true;
                }
            }
        }

        $assetPaths = [];
        $jsonVariants = self::semanticNameJsonVariants($name);
        if ($jsonVariants !== []) {
            $parts = [];
            $params = [];
            foreach ($jsonVariants as $variant) {
                $parts[] = "JSON_SEARCH(COALESCE(am.tags_json, JSON_ARRAY()), 'one', ?) IS NOT NULL";
                $params[] = $variant;
            }
            $assetRows = $maria->query(
                'SELECT DISTINCT a.rel_path
                 FROM wa_assets a
                 LEFT JOIN wa_asset_meta am ON am.asset_id = a.id
                 WHERE ' . implode(' OR ', $parts),
                $params
            );
            foreach ($assetRows as $row) {
                $relPath = trim((string)($row['rel_path'] ?? ''));
                if ($relPath !== '') {
                    $assetPaths[$relPath] = true;
                }
            }
        }

        $manualMediaRelPaths = [];
        $linkRows = $maria->query(
            'SELECT entity_type, rel_path FROM wa_semantic_tag_links WHERE semantic_tag_id = ?',
            [$semanticTagId]
        );
        foreach ($linkRows as $row) {
            $entityType = (string)($row['entity_type'] ?? '');
            $relPath = self::normalizeManualMediaRelPath((string)($row['rel_path'] ?? ''), $photosRoot);
            if ($relPath === '') {
                continue;
            }
            if ($entityType === 'media') {
                $manualMediaRelPaths[$relPath] = true;
                $mediaRelPaths[$relPath] = true;
            } elseif ($entityType === 'asset') {
                $assetPaths[$relPath] = true;
            }
        }

        if ($manualMediaRelPaths !== []) {
            foreach (self::sqliteRelPathRows($sqlite, array_keys($manualMediaRelPaths)) as $row) {
                $id = (int)($row['id'] ?? 0);
                $relPath = trim((string)($row['rel_path'] ?? ''));
                if ($id > 0) {
                    $mediaIds[$id] = true;
                }
                if ($relPath !== '') {
                    $mediaRelPaths[$relPath] = true;
                }
            }
        }

        return [
            'media_ids' => array_map('intval', array_keys($mediaIds)),
            'media_rel_paths' => array_values(array_keys($mediaRelPaths)),
            'asset_paths' => array_values(array_keys($assetPaths)),
        ];
    }

    private static function sqliteRelPathRows(SqliteIndex $sqlite, array $relPaths): array
    {
        $relPaths = array_values(array_filter(array_map(static fn ($v): string => trim((string)$v), $relPaths), static fn (string $v): bool => $v !== ''));
        if ($relPaths === []) {
            return [];
        }
        $rows = [];
        foreach (array_chunk($relPaths, 250) as $chunk) {
            $placeholders = implode(',', array_fill(0, count($chunk), '?'));
            $found = $sqlite->query(
                'SELECT id, rel_path, path FROM files WHERE rel_path IN (' . $placeholders . ') OR path IN (' . $placeholders . ')',
                array_merge($chunk, $chunk)
            );
            foreach ($found as $row) {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    private static function semanticNameVariants(string $name, string $normalizedName): array
    {
        $values = [$name, $normalizedName];
        if (class_exists('\\Normalizer')) {
            $extra = [];
            foreach ($values as $value) {
                $value = trim((string)$value);
                if ($value === '') {
                    continue;
                }
                $nfc = \Normalizer::normalize($value, \Normalizer::FORM_C);
                $nfd = \Normalizer::normalize($value, \Normalizer::FORM_D);
                if (is_string($nfc) && $nfc !== '') {
                    $extra[] = $nfc;
                }
                if (is_string($nfd) && $nfd !== '') {
                    $extra[] = $nfd;
                }
            }
            $values = array_merge($values, $extra);
        }
        $out = [];
        foreach ($values as $value) {
            $value = trim((string)$value);
            if ($value === '') {
                continue;
            }
            $key = function_exists('mb_strtolower')
                ? mb_strtolower($value, 'UTF-8')
                : strtolower($value);
            $out[$key] = true;
        }
        return array_values(array_keys($out));
    }

    private static function semanticNameJsonVariants(string $name): array
    {
        $values = [$name];
        if (class_exists('\\Normalizer')) {
            $nfc = \Normalizer::normalize($name, \Normalizer::FORM_C);
            $nfd = \Normalizer::normalize($name, \Normalizer::FORM_D);
            if (is_string($nfc) && $nfc !== '') {
                $values[] = $nfc;
            }
            if (is_string($nfd) && $nfd !== '') {
                $values[] = $nfd;
            }
        }
        $out = [];
        foreach ($values as $value) {
            $value = trim((string)$value);
            if ($value !== '') {
                $out[$value] = true;
            }
        }
        return array_values(array_keys($out));
    }

    private static function normalizeManualMediaRelPath(string $value, string $photosRoot): string
    {
        $value = trim(str_replace('\\', '/', $value));
        if ($value === '') {
            return '';
        }
        $trimmed = ltrim($value, '/');
        $root = trim(str_replace('\\', '/', $photosRoot), '/');
        if ($root !== '') {
            if (str_starts_with($trimmed, $root . '/')) {
                return ltrim(substr($trimmed, strlen($root) + 1), '/');
            }
            $rootBase = basename($root);
            if ($rootBase !== '' && str_starts_with($trimmed, $rootBase . '/')) {
                return ltrim(substr($trimmed, strlen($rootBase) + 1), '/');
            }
        }
        return trim($trimmed, '/');
    }

    private static function expandSemanticTagIds(Maria $maria, array $seedIds): array
    {
        $pending = [];
        $seen = [];
        foreach ($seedIds as $id) {
            $id = (int)$id;
            if ($id > 0 && !isset($seen[$id])) {
                $seen[$id] = true;
                $pending[] = $id;
            }
        }
        while ($pending !== []) {
            $batch = array_splice($pending, 0, 200);
            $placeholders = implode(',', array_fill(0, count($batch), '?'));
            $rows = $maria->query(
                'SELECT id FROM wa_semantic_tags WHERE parent_tag_id IN (' . $placeholders . ')',
                $batch
            );
            foreach ($rows as $row) {
                $id = (int)($row['id'] ?? 0);
                if ($id > 0 && !isset($seen[$id])) {
                    $seen[$id] = true;
                    $pending[] = $id;
                }
            }
        }
        return array_map('intval', array_keys($seen));
    }
}

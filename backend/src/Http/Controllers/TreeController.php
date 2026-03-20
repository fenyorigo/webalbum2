<?php

declare(strict_types=1);

namespace WebAlbum\Http\Controllers;

use WebAlbum\Db\Maria;
use WebAlbum\Db\SqliteIndex;
use WebAlbum\Assets\AssetPaths;
use WebAlbum\UserContext;

final class TreeController
{
    private string $configPath;

    public function __construct(string $configPath)
    {
        $this->configPath = $configPath;
    }

    public function roots(): void
    {
        try {
            [$config, $sqlite, $maria] = $this->connections();
            if (!$this->ensureAuth($maria)) {
                return;
            }

            $photosRoot = (string)($config['photos']['root'] ?? '');
            [$folders, $idsByRel] = $this->folderUniverse($sqlite, $maria);
            $rootSet = [];
            $roots = [];
            foreach ($folders as $relRaw) {
                $rel = (string)$relRaw;
                if (strpos($rel, '/') === false) {
                    $rootSet[$rel] = true;
                }
            }
            foreach ($this->filesystemChildren($photosRoot, null) as $rel) {
                $rootSet[$rel] = true;
            }
            foreach ($rootSet as $rel => $_) {
                $roots[] = $this->mapNodeFromRel((string)$rel, null, $folders, $idsByRel, $photosRoot);
            }
            usort($roots, fn (array $a, array $b): int => strcasecmp((string)$a['rel_path'], (string)$b['rel_path']));
            $this->json($roots);
        } catch (\Throwable $e) {
            $this->json(["error" => $e->getMessage()], 400);
        }
    }

    public function children(): void
    {
        try {
            [$config, $sqlite, $maria] = $this->connections();
            if (!$this->ensureAuth($maria)) {
                return;
            }

            $photosRoot = (string)($config['photos']['root'] ?? '');
            [$folders, $idsByRel, $relById] = $this->folderUniverse($sqlite, $maria, true);
            $parentRel = trim(str_replace('\\', '/', (string)($_GET['parent_rel_path'] ?? '')), '/');
            if ($parentRel === '') {
                $parentId = isset($_GET['parent_id']) ? (int)$_GET['parent_id'] : 0;
                if ($parentId > 0 && isset($relById[$parentId])) {
                    $parentRel = $relById[$parentId];
                }
            }
            if ($parentRel === '') {
                $this->json(["error" => "parent_rel_path (or a known parent_id) is required"], 400);
                return;
            }

            $childSet = [];
            foreach ($this->childRelPaths($parentRel, $folders) as $rel) {
                $childSet[$rel] = true;
            }
            foreach ($this->filesystemChildren($photosRoot, $parentRel) as $rel) {
                $childSet[$rel] = true;
            }
            $rows = [];
            foreach ($childSet as $rel => $_) {
                $rows[] = $this->mapNodeFromRel((string)$rel, $parentRel, $folders, $idsByRel, $photosRoot);
            }
            usort($rows, fn (array $a, array $b): int => strcasecmp((string)$a['rel_path'], (string)$b['rel_path']));
            $this->json($rows);
        } catch (\Throwable $e) {
            $this->json(["error" => $e->getMessage()], 400);
        }
    }

    public function createFolder(): void
    {
        try {
            [$config, $sqlite, $maria] = $this->connections();
            unset($sqlite);
            $user = $this->ensureAdmin($maria);
            if ($user === null) {
                return;
            }

            $body = file_get_contents('php://input') ?: '{}';
            $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($data)) {
                $this->json(['error' => 'Invalid JSON'], 400);
                return;
            }

            $parentRel = AssetPaths::normalizeRelPath((string)($data['parent_rel_path'] ?? ''));
            $folderName = $this->normalizeFolderName((string)($data['folder_name'] ?? ''));
            if ($parentRel === null) {
                $this->json(['error' => 'Invalid parent folder'], 400);
                return;
            }
            if ($folderName === null) {
                $this->json(['error' => 'Invalid folder name'], 400);
                return;
            }

            $newRelPath = $parentRel . '/' . $folderName;
            $photosRoot = (string)($config['photos']['root'] ?? '');
            $targetPath = AssetPaths::joinInside($photosRoot, $newRelPath);
            if ($targetPath === null) {
                $this->json(['error' => 'Invalid folder path'], 400);
                return;
            }
            if (file_exists($targetPath)) {
                $this->json(['error' => 'Folder already exists'], 409);
                return;
            }
            if (!@mkdir($targetPath, 0775, false) && !is_dir($targetPath)) {
                $this->json(['error' => 'Failed to create folder'], 500);
                return;
            }

            $folderSet = $this->folderUniverse(new SqliteIndex($config['sqlite']['path']), $maria)[0];
            $node = $this->mapNodeFromRel($newRelPath, $parentRel, $folderSet, [], $photosRoot);
            $this->json([
                'ok' => true,
                'folder' => $node,
            ], 201);
        } catch (\JsonException $e) {
            $this->json(['error' => 'Invalid JSON'], 400);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    public function deleteFolder(): void
    {
        try {
            [$config, $sqlite, $maria] = $this->connections();
            unset($sqlite);
            $user = $this->ensureAdmin($maria);
            if ($user === null) {
                return;
            }

            $body = file_get_contents('php://input') ?: '{}';
            $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($data)) {
                $this->json(['error' => 'Invalid JSON'], 400);
                return;
            }

            $relPath = AssetPaths::normalizeRelPath((string)($data['rel_path'] ?? ''));
            if ($relPath === null) {
                $this->json(['error' => 'Invalid folder path'], 400);
                return;
            }

            $photosRoot = (string)($config['photos']['root'] ?? '');
            $targetPath = AssetPaths::joinInside($photosRoot, $relPath);
            if ($targetPath === null || !is_dir($targetPath)) {
                $this->json(['error' => 'Folder not found'], 404);
                return;
            }

            if (!$this->canDeleteFolder($targetPath)) {
                $this->json(['error' => 'Folder is not empty'], 409);
                return;
            }
            if (!$this->deleteFolderTree($targetPath)) {
                $this->json(['error' => 'Failed to delete folder'], 500);
                return;
            }

            $this->json([
                'ok' => true,
                'rel_path' => $relPath,
            ]);
        } catch (\JsonException $e) {
            $this->json(['error' => 'Invalid JSON'], 400);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    private function mapNodeFromRel(string $relPath, ?string $parentRel, array $folders, array $idsByRel, string $photosRoot): array
    {
        $id = $idsByRel[$relPath] ?? null;
        $parentId = null;
        if ($parentRel !== null && $parentRel !== '') {
            $parentId = $idsByRel[$parentRel] ?? null;
        }

        return [
            'id' => $id !== null ? (int)$id : null,
            'key' => $relPath,
            'parent_id' => $parentId !== null ? (int)$parentId : null,
            'name' => $this->nameFromRelPath($relPath),
            'rel_path' => $relPath,
            'depth' => $this->depthFromRelPath($relPath),
            'has_children' => $this->hasChildren($relPath, $folders, $photosRoot),
        ];
    }

    private function nameFromRelPath(string $relPath): string
    {
        $trimmed = trim(str_replace('\\', '/', $relPath), '/');
        if ($trimmed === '') {
            return '/';
        }
        $parts = explode('/', $trimmed);
        return (string)end($parts);
    }

    private function connections(): array
    {
        $config = require $this->configPath;
        $sqlite = new SqliteIndex($config['sqlite']['path']);
        $maria = new Maria(
            $config['mariadb']['dsn'],
            $config['mariadb']['user'],
            $config['mariadb']['pass']
        );
        return [$config, $sqlite, $maria];
    }

    private function ensureAuth(Maria $maria): bool
    {
        $user = UserContext::currentUser($maria);
        if ($user === null) {
            $this->json(['error' => 'Not authenticated'], 401);
            return false;
        }
        return true;
    }

    private function ensureAdmin(Maria $maria): ?array
    {
        $user = UserContext::currentUser($maria);
        if ($user === null) {
            $this->json(['error' => 'Not authenticated'], 401);
            return null;
        }
        if ((int)($user['is_admin'] ?? 0) !== 1) {
            $this->json(['error' => 'Forbidden'], 403);
            return null;
        }
        return $user;
    }

    private function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload);
    }

    private function folderUniverse(SqliteIndex $sqlite, Maria $maria, bool $withReverse = false): array
    {
        $folderSet = [];
        $idsByRel = [];
        $relById = [];

        $dirRows = $sqlite->query('SELECT id, rel_path FROM directories');
        foreach ($dirRows as $row) {
            $rel = trim(str_replace('\\', '/', (string)($row['rel_path'] ?? '')), '/');
            if ($rel === '') {
                continue;
            }
            $folderSet[$rel] = true;
            $id = (int)($row['id'] ?? 0);
            if ($id > 0) {
                $idsByRel[$rel] = $id;
                $relById[$id] = $rel;
            }
        }

        $assetRows = $maria->query('SELECT rel_path FROM wa_assets');
        foreach ($assetRows as $row) {
            $relPath = trim(str_replace('\\', '/', (string)($row['rel_path'] ?? '')), '/');
            if ($relPath === '') {
                continue;
            }
            $parts = explode('/', $relPath);
            array_pop($parts);
            $prefix = '';
            foreach ($parts as $part) {
                if ($part === '') {
                    continue;
                }
                $prefix = $prefix === '' ? $part : ($prefix . '/' . $part);
                $folderSet[$prefix] = true;
            }
        }

        if ($withReverse) {
            return [array_keys($folderSet), $idsByRel, $relById];
        }
        return [array_keys($folderSet), $idsByRel];
    }

    private function childRelPaths(string $parentRel, array $folders): array
    {
        $prefix = rtrim($parentRel, '/') . '/';
        $children = [];
        foreach ($folders as $relRaw) {
            $rel = (string)$relRaw;
            if (!str_starts_with($rel, $prefix)) {
                continue;
            }
            $rest = substr($rel, strlen($prefix));
            if ($rest === '' || strpos($rest, '/') !== false) {
                continue;
            }
            $children[$rel] = true;
        }
        return array_keys($children);
    }

    private function hasChildren(string $relPath, array $folders, string $photosRoot): bool
    {
        $prefix = rtrim($relPath, '/') . '/';
        foreach ($folders as $relRaw) {
            $rel = (string)$relRaw;
            if (str_starts_with($rel, $prefix)) {
                return true;
            }
        }
        return $this->filesystemChildren($photosRoot, $relPath) !== [];
    }

    /**
     * @return string[]
     */
    private function filesystemChildren(string $photosRoot, ?string $parentRel): array
    {
        $basePath = $parentRel === null || $parentRel === ''
            ? rtrim($photosRoot, DIRECTORY_SEPARATOR)
            : AssetPaths::joinInside($photosRoot, $parentRel);
        if (!is_string($basePath) || $basePath === '' || !is_dir($basePath)) {
            return [];
        }

        $children = [];
        $items = @scandir($basePath);
        if (!is_array($items)) {
            return [];
        }
        foreach ($items as $name) {
            if ($name === '.' || $name === '..') {
                continue;
            }
            $childPath = $basePath . DIRECTORY_SEPARATOR . $name;
            if (!is_dir($childPath)) {
                continue;
            }
            $rel = $parentRel === null || $parentRel === ''
                ? $name
                : ($parentRel . '/' . $name);
            $normalized = AssetPaths::normalizeRelPath($rel);
            if ($normalized !== null) {
                $children[$normalized] = true;
            }
        }
        return array_keys($children);
    }

    private function depthFromRelPath(string $relPath): int
    {
        $trimmed = trim(str_replace('\\', '/', $relPath), '/');
        if ($trimmed === '') {
            return 0;
        }
        return count(explode('/', $trimmed));
    }

    private function normalizeFolderName(string $raw): ?string
    {
        $name = trim($raw);
        if ($name === '' || $name === '.' || $name === '..') {
            return null;
        }
        if (preg_match('/[\/\\\\:*?"<>|\x00-\x1F]/u', $name)) {
            return null;
        }
        if (str_contains($name, '../') || str_contains($name, '..\\')) {
            return null;
        }
        return $name;
    }

    private function canDeleteFolder(string $path): bool
    {
        $selfName = basename($path);
        if ($selfName !== '' && str_starts_with($selfName, '.')) {
            return true;
        }

        $items = @scandir($path);
        if (!is_array($items)) {
            return false;
        }
        foreach ($items as $name) {
            if ($name === '.' || $name === '..') {
                continue;
            }
            if (str_starts_with($name, '.')) {
                continue;
            }
            return false;
        }
        return true;
    }

    private function deleteFolderTree(string $path): bool
    {
        $items = @scandir($path);
        if (!is_array($items)) {
            return false;
        }
        foreach ($items as $name) {
            if ($name === '.' || $name === '..') {
                continue;
            }
            $child = $path . DIRECTORY_SEPARATOR . $name;
            if (is_dir($child)) {
                if (!$this->deleteFolderTree($child)) {
                    return false;
                }
                continue;
            }
            if (!@unlink($child)) {
                return false;
            }
        }
        return @rmdir($path);
    }
}

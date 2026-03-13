<?php

declare(strict_types=1);

namespace WebAlbum\Media;

use WebAlbum\Db\SqliteIndex;
use WebAlbum\Security\PathGuard;

final class MediaTagSupport
{
    public static function fetchFile(SqliteIndex $sqlite, int $id): ?array
    {
        $rows = $sqlite->query(
            "SELECT id, path, rel_path, type, sha256 FROM files WHERE id = ?",
            [$id]
        );
        return $rows[0] ?? null;
    }

    public static function fetchFileByRelPath(SqliteIndex $sqlite, string $relPath): ?array
    {
        $rows = $sqlite->query(
            "SELECT id, path, rel_path, type, sha256 FROM files WHERE rel_path = ? LIMIT 1",
            [$relPath]
        );
        return $rows[0] ?? null;
    }

    public static function fetchDisplayTags(SqliteIndex $sqlite, int $id): array
    {
        $rows = $sqlite->query(
            "SELECT DISTINCT t.tag
             FROM file_tags ft
             JOIN tags t ON t.id = ft.tag_id
             WHERE ft.file_id = ?
               AND t.tag <> 'People'
               AND t.tag NOT LIKE 'People|%'
             ORDER BY LOWER(t.tag) ASC",
            [$id]
        );
        return array_map(static fn (array $r): string => (string)$r["tag"], $rows);
    }

    public static function normalizeTag(string $raw): string
    {
        $tag = preg_replace('/\s+/u', ' ', trim($raw));
        $tag = is_string($tag) ? $tag : '';
        if ($tag === '') {
            throw new \InvalidArgumentException("tag cannot be empty");
        }
        $len = function_exists('mb_strlen') ? mb_strlen($tag, 'UTF-8') : strlen($tag);
        if ($len > 128) {
            throw new \InvalidArgumentException("tag too long (max 128)");
        }
        if (str_contains($tag, '|')) {
            throw new \InvalidArgumentException("tag must not contain pipe character");
        }
        return $tag;
    }

    public static function normalizeTags(array $tags): array
    {
        $out = [];
        $seen = [];
        foreach ($tags as $raw) {
            if (!is_string($raw)) {
                throw new \InvalidArgumentException("tags must be strings");
            }
            $tag = self::normalizeTag($raw);
            if (!isset($seen[$tag])) {
                $seen[$tag] = true;
                $out[] = $tag;
            }
        }
        sort($out, SORT_NATURAL | SORT_FLAG_CASE);
        return $out;
    }

    public static function resolveOriginalPath(string $path, string $relPath, string $photosRoot): ?string
    {
        if ($path !== '' && is_file($path)) {
            return PathGuard::assertInsideRoot($path, $photosRoot);
        }
        $fallback = self::safeJoin($photosRoot, $relPath);
        if ($fallback === null) {
            return null;
        }
        return PathGuard::assertInsideRoot($fallback, $photosRoot);
    }

    public static function backupPath(string $backupRoot, string $relPath): ?string
    {
        return self::safeJoin($backupRoot, $relPath);
    }

    public static function safeJoin(string $root, string $relPath): ?string
    {
        if ($root === '' || $relPath === '') {
            return null;
        }
        $rel = str_replace('\\', '/', $relPath);
        if ($rel === '' || $rel[0] === '/' || str_contains($rel, ':')) {
            return null;
        }
        foreach (explode('/', $rel) as $part) {
            if ($part === '..') {
                return null;
            }
        }
        return rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $rel;
    }

    public static function ensureDir(string $dir): void
    {
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new \RuntimeException('Unable to create directory: ' . $dir);
        }
    }

    public static function copyFileAtomic(string $src, string $dest): void
    {
        if (!is_file($src) || !is_readable($src)) {
            throw new \RuntimeException('Backup source missing or unreadable');
        }
        self::ensureDir(dirname($dest));
        $tmp = $dest . '.tmp.' . getmypid() . '.' . bin2hex(random_bytes(4));
        if (!@copy($src, $tmp)) {
            throw new \RuntimeException('Failed to copy backup file');
        }
        $mode = @fileperms($src);
        if (is_int($mode) && $mode > 0) {
            @chmod($tmp, $mode & 0777);
        }
        if (!@rename($tmp, $dest)) {
            @unlink($tmp);
            throw new \RuntimeException('Failed to publish backup file');
        }
    }

    public static function writeTagsWithExiftool(string $exiftoolPath, string $path, array $tags): void
    {
        $binary = self::resolveExiftoolBinary($exiftoolPath);
        $cmd = [
            $binary,
            '-overwrite_original',
            '-charset',
            'filename=utf8',
            '-charset',
            'iptc=utf8',
            '-IPTC:Keywords=',
            '-XMP-dc:Subject=',
            '-XMP-lr:HierarchicalSubject=',
        ];

        foreach ($tags as $tag) {
            $cmd[] = '-IPTC:Keywords=' . $tag;
            $cmd[] = '-XMP-dc:Subject=' . $tag;
            $cmd[] = '-XMP-lr:HierarchicalSubject=People|' . $tag;
        }
        $cmd[] = $path;

        [$ok, $stdout, $stderr, $timedOut] = self::runProcess($cmd, 30);
        if (!$ok) {
            if ($timedOut) {
                throw new \RuntimeException('ExifTool timeout while writing tags');
            }
            $msg = trim($stderr !== '' ? $stderr : $stdout);
            if ($msg === '') {
                $msg = 'ExifTool failed to write tags';
            } else {
                $msg = str_replace($path, '<media>', $msg);
            }
            throw new \RuntimeException($msg);
        }
    }

    private static function resolveExiftoolBinary(string $configured): string
    {
        $configured = trim($configured);
        if ($configured !== '') {
            if ($configured === 'exiftool') {
                foreach (['/opt/homebrew/bin/exiftool', '/usr/local/bin/exiftool', '/usr/bin/exiftool', '/usr/local/sbin/exiftool'] as $candidate) {
                    if (is_file($candidate) && is_executable($candidate)) {
                        return $candidate;
                    }
                }
                return 'exiftool';
            }
            if (is_file($configured) && is_executable($configured)) {
                return $configured;
            }
            throw new \RuntimeException('Configured exiftool binary not found or not executable');
        }

        foreach (['/opt/homebrew/bin/exiftool', '/usr/local/bin/exiftool', '/usr/bin/exiftool', '/usr/local/sbin/exiftool'] as $candidate) {
            if (is_file($candidate) && is_executable($candidate)) {
                return $candidate;
            }
        }
        return 'exiftool';
    }

    private static function runProcess(array $cmd, int $timeoutSec): array
    {
        $descriptors = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $proc = @proc_open($cmd, $descriptors, $pipes, null, null, ['bypass_shell' => true]);
        if (!is_resource($proc)) {
            throw new \RuntimeException('Failed to start exiftool process. Set WA_EXIFTOOL_PATH to full exiftool path.');
        }

        $stdout = '';
        $stderr = '';
        $timedOut = false;
        foreach ([1, 2] as $idx) {
            if (isset($pipes[$idx]) && is_resource($pipes[$idx])) {
                stream_set_blocking($pipes[$idx], false);
            }
        }

        $start = microtime(true);
        while (true) {
            if (isset($pipes[1]) && is_resource($pipes[1])) {
                $chunk = stream_get_contents($pipes[1]);
                if (is_string($chunk) && $chunk !== '') {
                    $stdout .= $chunk;
                }
            }
            if (isset($pipes[2]) && is_resource($pipes[2])) {
                $chunk = stream_get_contents($pipes[2]);
                if (is_string($chunk) && $chunk !== '') {
                    $stderr .= $chunk;
                }
            }

            $status = proc_get_status($proc);
            if (!$status['running']) {
                break;
            }

            if ((microtime(true) - $start) > $timeoutSec) {
                $timedOut = true;
                proc_terminate($proc, 9);
                break;
            }
            usleep(100000);
        }

        foreach ($pipes as $pipe) {
            if (is_resource($pipe)) {
                fclose($pipe);
            }
        }
        $exit = proc_close($proc);

        return [$exit === 0 && !$timedOut, $stdout, $stderr, $timedOut];
    }
}

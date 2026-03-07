<?php

declare(strict_types=1);

namespace WebAlbum\Http\Controllers;

use WebAlbum\Db\Maria;
use WebAlbum\Db\SqliteIndex;
use WebAlbum\Security\PathGuard;
use WebAlbum\SystemTools;
use WebAlbum\Thumb\ThumbPolicy;
use WebAlbum\UserContext;

final class MediaRotateController
{
    private string $configPath;

    public function __construct(string $configPath)
    {
        $this->configPath = $configPath;
    }

    public function save(int $id): void
    {
        try {
            if ($id < 1) {
                throw new \InvalidArgumentException('Invalid id');
            }

            $config = require $this->configPath;
            $maria = new Maria(
                $config['mariadb']['dsn'],
                $config['mariadb']['user'],
                $config['mariadb']['pass']
            );
            $user = UserContext::currentUser($maria);
            if ($user === null) {
                $this->json(['error' => 'Not authenticated'], 401);
                return;
            }
            if ((int)($user['is_admin'] ?? 0) !== 1) {
                $this->json(['error' => 'Forbidden'], 403);
                return;
            }

            $raw = file_get_contents('php://input') ?: '{}';
            $data = json_decode($raw, true);
            if (!is_array($data)) {
                $data = [];
            }

            $turns = (int)($data['quarter_turns'] ?? 0);
            $turns = $this->normalizeTurns($turns);
            if ($turns === 0) {
                $this->json(['error' => 'No rotation requested'], 400);
                return;
            }

            $sqlite = new SqliteIndex($config['sqlite']['path']);
            $rows = $sqlite->query('SELECT id, path, rel_path, type, sha256 FROM files WHERE id = ?', [$id]);
            if ($rows === []) {
                $this->json(['error' => 'Not Found'], 404);
                return;
            }
            $row = $rows[0];
            $type = (string)($row['type'] ?? '');
            $oldSha = strtolower(trim((string)($row['sha256'] ?? '')));
            if ($type !== 'image' && $type !== 'video') {
                $this->json(['error' => 'Only image and video are supported'], 400);
                return;
            }

            $photosRoot = (string)($config['photos']['root'] ?? '');
            $path = $this->resolveOriginalPath(
                (string)($row['path'] ?? ''),
                (string)($row['rel_path'] ?? ''),
                $photosRoot
            );
            if ($path === null || !is_file($path)) {
                $this->json(['error' => 'File not found'], 404);
                return;
            }

            $toolStatus = SystemTools::checkExternalTools($config, true);
            $ffmpegTool = $toolStatus['tools']['ffmpeg'] ?? ['available' => false, 'path' => null];
            if (!(bool)($ffmpegTool['available'] ?? false) || empty($ffmpegTool['path'])) {
                throw new \RuntimeException('ffmpeg not available');
            }
            $ffmpeg = (string)$ffmpegTool['path'];
            $exiftoolTool = $toolStatus['tools']['exiftool'] ?? ['available' => false, 'path' => null];

            $beforeStat = @stat($path) ?: null;
            $beforeMtime = is_array($beforeStat) ? (int)($beforeStat['mtime'] ?? 0) : 0;
            $beforeSize = is_array($beforeStat) ? (int)($beforeStat['size'] ?? 0) : 0;
            $tmp = $this->tmpRotatePath($path);
            $filter = $this->rotationFilter($turns);

            try {
                $this->runRotate($ffmpeg, $path, $tmp, $filter, $type);
                if (!is_file($tmp) || (int)@filesize($tmp) <= 0) {
                    throw new \RuntimeException('Rotation output is empty');
                }
                $this->preserveOwnershipAndMode($path, $tmp);
                if (!@rename($tmp, $path)) {
                    throw new \RuntimeException('Failed to replace original file after rotation');
                }
            } finally {
                if (is_file($tmp)) {
                    @unlink($tmp);
                }
            }
            $orientationFix = [
                'attempted' => false,
                'ok' => false,
                'error' => '',
            ];
            if ($type === 'image') {
                $orientationFix = $this->normalizeImageOrientationTag(
                    $path,
                    (bool)($exiftoolTool['available'] ?? false),
                    is_string($exiftoolTool['path'] ?? null) ? (string)$exiftoolTool['path'] : 'exiftool'
                );
            }

            clearstatcache(true, $path);
            $afterStat = @stat($path) ?: null;
            $afterMtime = is_array($afterStat) ? (int)($afterStat['mtime'] ?? 0) : 0;
            $afterSize = is_array($afterStat) ? (int)($afterStat['size'] ?? 0) : 0;

            $thumbRoot = (string)($config['thumbs']['root'] ?? '');
            $relPath = (string)($row['rel_path'] ?? '');
            $thumbDeleted = false;
            if ($thumbRoot !== '' && $relPath !== '') {
                $thumbPath = ThumbPolicy::thumbPath($thumbRoot, $relPath);
                if (is_string($thumbPath) && is_file($thumbPath)) {
                    $thumbDeleted = @unlink($thumbPath);
                }
            }

            $hashSync = $this->syncHashesAfterRotate($maria, $config, $relPath, $oldSha, $path);

            $this->logRotate([
                'file_id' => $id,
                'type' => $type,
                'path' => $path,
                'before_mtime' => $beforeMtime,
                'after_mtime' => $afterMtime,
                'before_size' => $beforeSize,
                'after_size' => $afterSize,
                'thumb_deleted' => $thumbDeleted,
                'orientation_fix_attempted' => (bool)$orientationFix['attempted'],
                'orientation_fix_ok' => (bool)$orientationFix['ok'],
                'orientation_fix_error' => (string)$orientationFix['error'],
                'old_sha256' => $hashSync['old_sha256'] ?? null,
                'new_sha256' => $hashSync['new_sha256'] ?? null,
            ]);

            $this->json([
                'ok' => true,
                'id' => $id,
                'type' => $type,
                'quarter_turns' => $turns,
                'before_mtime' => $beforeMtime,
                'after_mtime' => $afterMtime,
                'orientation_fix_attempted' => (bool)$orientationFix['attempted'],
                'orientation_fix_ok' => (bool)$orientationFix['ok'],
                'old_sha256' => $hashSync['old_sha256'] ?? null,
                'new_sha256' => $hashSync['new_sha256'] ?? null,
            ]);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    private function normalizeTurns(int $turns): int
    {
        $value = $turns % 4;
        if ($value < 0) {
            $value += 4;
        }
        return $value;
    }

    private function rotationFilter(int $turns): string
    {
        return match ($turns) {
            1 => 'transpose=1',
            2 => 'hflip,vflip',
            3 => 'transpose=2',
            default => '',
        };
    }

    private function runRotate(string $ffmpeg, string $src, string $dest, string $filter, string $type): void
    {
        $args = [
            $ffmpeg,
            '-v', 'error',
            '-y',
            '-i', $src,
            '-vf', $filter,
        ];

        if ($type === 'video') {
            $args = array_merge($args, [
                '-c:v', 'libx264',
                '-preset', 'veryfast',
                '-crf', '18',
                '-c:a', 'copy',
                '-movflags', '+faststart',
                '-metadata:s:v:0', 'rotate=0',
            ]);
        } else {
            $args = array_merge($args, ['-frames:v', '1', '-q:v', '2']);
        }

        $args[] = $dest;
        $cmd = implode(' ', array_map('escapeshellarg', $args));
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $process = @proc_open($cmd, $descriptors, $pipes);
        if (!is_resource($process)) {
            throw new \RuntimeException('Failed to start ffmpeg');
        }

        fclose($pipes[0]);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $stdout = '';
        $stderr = '';
        $start = microtime(true);
        $timeout = ($type === 'video') ? 180 : 60;

        while (true) {
            $stdout .= (string)stream_get_contents($pipes[1]);
            $stderr .= (string)stream_get_contents($pipes[2]);
            $status = proc_get_status($process);
            if (!$status['running']) {
                break;
            }
            if ((microtime(true) - $start) > $timeout) {
                proc_terminate($process, 9);
                throw new \RuntimeException('ffmpeg timeout during rotate');
            }
            usleep(20000);
        }

        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($process);

        if ($exit !== 0) {
            $msg = trim($stderr !== '' ? $stderr : $stdout);
            if ($msg === '') {
                $msg = 'ffmpeg rotate failed';
            }
            throw new \RuntimeException($msg);
        }
    }

    private function normalizeImageOrientationTag(string $path, bool $available, string $binary): array
    {
        if (!$available) {
            return [
                'attempted' => false,
                'ok' => false,
                'error' => 'exiftool unavailable',
            ];
        }

        $cmd = implode(' ', array_map('escapeshellarg', [
            $binary !== '' ? $binary : 'exiftool',
            '-overwrite_original',
            '-P',
            '-n',
            '-Orientation#=1',
            $path,
        ]));

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $process = @proc_open($cmd, $descriptors, $pipes);
        if (!is_resource($process)) {
            return [
                'attempted' => true,
                'ok' => false,
                'error' => 'failed to start exiftool',
            ];
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($process);
        if ($exit !== 0) {
            $msg = trim((string)$stderr !== '' ? (string)$stderr : (string)$stdout);
            if ($msg === '') {
                $msg = 'exiftool orientation update failed';
            }
            return [
                'attempted' => true,
                'ok' => false,
                'error' => $msg,
            ];
        }

        return [
            'attempted' => true,
            'ok' => true,
            'error' => '',
        ];
    }

    private function tmpRotatePath(string $path): string
    {
        $dir = dirname($path);
        $name = pathinfo($path, PATHINFO_FILENAME);
        $ext = strtolower((string)pathinfo($path, PATHINFO_EXTENSION));
        $suffix = '.rotate.' . getmypid() . '.' . bin2hex(random_bytes(4));
        if ($ext !== '') {
            return $dir . DIRECTORY_SEPARATOR . $name . $suffix . '.' . $ext;
        }
        return $path . $suffix;
    }

    private function preserveOwnershipAndMode(string $source, string $dest): void
    {
        $sourceStat = @stat($source);
        if (!is_array($sourceStat)) {
            return;
        }
        $mode = (int)($sourceStat['mode'] ?? 0) & 0777;
        if ($mode > 0) {
            @chmod($dest, $mode);
        }
        if (function_exists('posix_geteuid') && (int)posix_geteuid() === 0) {
            if (isset($sourceStat['uid'])) {
                @chown($dest, (int)$sourceStat['uid']);
            }
            if (isset($sourceStat['gid'])) {
                @chgrp($dest, (int)$sourceStat['gid']);
            }
        }
    }

    private function resolveOriginalPath(string $path, string $relPath, string $photosRoot): ?string
    {
        if ($path !== '' && is_file($path)) {
            return PathGuard::assertInsideRoot($path, $photosRoot);
        }
        $fallback = $this->safeJoin($photosRoot, $relPath);
        if ($fallback === null) {
            return null;
        }
        return PathGuard::assertInsideRoot($fallback, $photosRoot);
    }

    private function safeJoin(string $root, string $relPath): ?string
    {
        if ($root === '' || $relPath === '') {
            return null;
        }
        $rel = str_replace('\\', '/', $relPath);
        if ($rel[0] === '/' || str_contains($rel, ':')) {
            return null;
        }
        foreach (explode('/', $rel) as $part) {
            if ($part === '..') {
                return null;
            }
        }
        return rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $rel;
    }

    private function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload);
    }

    private function syncHashesAfterRotate(Maria $maria, array $config, string $relPath, string $oldSha, string $filePath): array
    {
        $newSha = strtolower((string)@hash_file('sha256', $filePath));
        if (!preg_match('/^[a-f0-9]{64}$/', $newSha)) {
            throw new \RuntimeException('Failed to compute sha256 after rotate');
        }

        $stat = @stat($filePath);
        $size = is_array($stat) ? (int)($stat['size'] ?? 0) : 0;
        $mtime = is_array($stat) ? (int)($stat['mtime'] ?? 0) : 0;

        $sqlitePath = (string)($config['sqlite']['path'] ?? '');
        if ($sqlitePath === '' || !is_file($sqlitePath)) {
            throw new \RuntimeException('SQLite DB not available for hash sync');
        }
        $sqlite = new \PDO(
            'sqlite:' . $sqlitePath,
            null,
            null,
            [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ]
        );
        $sqlite->beginTransaction();
        try {
            $st = $sqlite->prepare(
                "UPDATE files
                 SET sha256 = ?, size = ?, mtime = ?, indexed_at = datetime('now')
                 WHERE rel_path = ?
                   AND type IN ('image','video')"
            );
            $st->execute([$newSha, max(0, $size), max(0, $mtime), $relPath]);
            $sqlite->commit();
        } catch (\Throwable $e) {
            if ($sqlite->inTransaction()) {
                $sqlite->rollBack();
            }
            throw $e;
        }

        $maria->exec("START TRANSACTION");
        try {
            $maria->exec(
                "INSERT INTO wa_objects (sha256, status, first_seen_at, last_seen_at, orphaned_at, last_synced_at)
                 VALUES (?, 'active', NOW(), NOW(), NULL, NOW())
                 ON DUPLICATE KEY UPDATE
                   status = 'active',
                   orphaned_at = NULL,
                   last_seen_at = NOW(),
                   last_synced_at = NOW()",
                [$newSha]
            );

            if (preg_match('/^[a-f0-9]{64}$/', $oldSha) && $oldSha !== $newSha) {
                $rem = $sqlite->prepare(
                    "SELECT COUNT(*) AS c
                     FROM files
                     WHERE sha256 IS NOT NULL
                       AND LOWER(sha256) = ?"
                );
                $rem->execute([$oldSha]);
                $remaining = (int)(($rem->fetch()['c'] ?? 0));
                if ($remaining === 0) {
                    $maria->exec(
                        "UPDATE wa_objects
                         SET status = 'orphaned',
                             orphaned_at = IF(orphaned_at IS NULL, NOW(), orphaned_at),
                             last_synced_at = NOW()
                         WHERE sha256 = ?
                           AND status = 'active'",
                        [$oldSha]
                    );
                } else {
                    $maria->exec(
                        "UPDATE wa_objects
                         SET status = 'active',
                             orphaned_at = NULL,
                             last_seen_at = NOW(),
                             last_synced_at = NOW()
                         WHERE sha256 = ?",
                        [$oldSha]
                    );
                }
            }

            $maria->exec(
                "UPDATE wa_assets
                 SET sha256 = ?, size = ?, mtime = ?, updated_at = NOW()
                 WHERE rel_path = ?",
                [$newSha, max(0, $size), max(0, $mtime), $relPath]
            );
            $maria->exec("COMMIT");
        } catch (\Throwable $e) {
            $maria->exec("ROLLBACK");
            throw $e;
        }

        return [
            'old_sha256' => preg_match('/^[a-f0-9]{64}$/', $oldSha) ? $oldSha : null,
            'new_sha256' => $newSha,
        ];
    }

    private function logRotate(array $details): void
    {
        @error_log('webalbum_rotate ' . json_encode($details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}

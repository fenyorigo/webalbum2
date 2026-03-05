<?php

declare(strict_types=1);

namespace WebAlbum\Http\Controllers;

use WebAlbum\Db\Maria;
use WebAlbum\Db\SqliteIndex;
use WebAlbum\UserContext;
use WebAlbum\Security\PathGuard;

final class FileController
{
    private string $configPath;

    public function __construct(string $configPath)
    {
        $this->configPath = $configPath;
    }

    public function handle(int $id): void
    {
        try {
            if ($id < 1) {
                throw new \InvalidArgumentException("Invalid id");
            }

            $config = require $this->configPath;
            $maria = new Maria(
                $config["mariadb"]["dsn"],
                $config["mariadb"]["user"],
                $config["mariadb"]["pass"]
            );
            $user = UserContext::currentUser($maria);
            if ($user === null) {
                $this->json(["error" => "Not authenticated"], 401);
                return;
            }
            $db = new SqliteIndex($config["sqlite"]["path"]);

            $rows = $db->query(
                "SELECT id, path, rel_path, mime, type FROM files WHERE id = ?",
                [$id]
            );
            if ($rows === []) {
                $this->json(["error" => "Not Found"], 404);
                return;
            }

            $row = $rows[0];
            $relPath = trim((string)($row["rel_path"] ?? ""));
            if ($relPath !== "" && $this->isRelPathTrashed($maria, $relPath)) {
                $this->json(["error" => "Trashed"], 410);
                return;
            }
            if (($row["type"] ?? "") !== "image") {
                $this->json(["error" => "Only images are supported"], 400);
                return;
            }

            $path = $this->resolveOriginalPath(
                $row["path"] ?? "",
                $row["rel_path"] ?? "",
                $config["photos"]["root"] ?? ""
            );
            if ($path === null || !is_file($path)) {
                $this->json(["error" => "File not found"], 404);
                return;
            }

            $mime = is_string($row["mime"]) && $row["mime"] !== "" ? $row["mime"] : $this->detectMime($path);
            $mtime = (int)filemtime($path);
            $size = (int)filesize($path);
            $previewRequested = isset($_GET["preview"]) && (string)$_GET["preview"] === "1";
            if ($previewRequested && $this->needsPreviewRasterFallback($mime, $path)) {
                $thumbsRoot = trim((string)($config["thumbs"]["root"] ?? ""));
                $relPathForCache = trim((string)($row["rel_path"] ?? ""));
                $cachePath = $this->previewCachePath($thumbsRoot, $relPathForCache);
                if ($cachePath !== null) {
                    $this->ensurePreviewCached($path, $cachePath);
                    clearstatcache(true, $cachePath);
                    if (is_file($cachePath)) {
                        $this->servePreviewCacheFile($cachePath, basename($path) . ".jpg");
                        return;
                    }
                }

                $jpeg = $this->renderAsJpeg($path);
                if ($jpeg !== null) {
                    $previewEtag = "\"" . md5((string)$mtime . ":" . (string)$size . ":" . $path . ":preview-jpeg-inline") . "\"";
                    header("Content-Type: image/jpeg");
                    header("Cache-Control: private, no-cache, must-revalidate, max-age=0");
                    header("Pragma: no-cache");
                    header("ETag: " . $previewEtag);
                    header("Last-Modified: " . gmdate("D, d M Y H:i:s", $mtime) . " GMT");
                    if (isset($_SERVER["HTTP_IF_NONE_MATCH"]) && trim((string)$_SERVER["HTTP_IF_NONE_MATCH"]) === $previewEtag) {
                        http_response_code(304);
                        return;
                    }
                    header("Content-Length: " . (string)strlen($jpeg));
                    header("Content-Disposition: inline; filename=\"" . basename($path) . ".jpg\"");
                    echo $jpeg;
                    return;
                }
            }
            $etag = "\"" . md5((string)$mtime . ":" . (string)$size . ":" . $path) . "\"";
            header("Content-Type: " . $mime);
            header("Cache-Control: private, no-cache, must-revalidate, max-age=0");
            header("Pragma: no-cache");
            header("ETag: " . $etag);
            header("Last-Modified: " . gmdate("D, d M Y H:i:s", $mtime) . " GMT");
            if (isset($_SERVER["HTTP_IF_NONE_MATCH"]) && trim((string)$_SERVER["HTTP_IF_NONE_MATCH"]) === $etag) {
                http_response_code(304);
                return;
            }
            header("Content-Length: " . (string)$size);
            header("Content-Disposition: inline; filename=\"" . basename($path) . "\"");
            readfile($path);
        } catch (\Throwable $e) {
            $this->json(["error" => $e->getMessage()], 400);
        }
    }

    private function resolveOriginalPath(string $path, string $relPath, string $photosRoot): ?string
    {
        if ($path !== "" && is_file($path)) {
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
        if ($root === "" || $relPath === "") {
            return null;
        }
        $rel = str_replace("\\", "/", $relPath);
        if ($rel[0] === "/" || str_contains($rel, ":")) {
            return null;
        }
        foreach (explode("/", $rel) as $part) {
            if ($part === "..") {
                return null;
            }
        }
        return rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $rel;
    }

    private function detectMime(string $path): string
    {
        if (class_exists(\finfo::class)) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($path);
            if (is_string($mime) && $mime !== "") {
                return $mime;
            }
        }
        return "application/octet-stream";
    }

    private function needsPreviewRasterFallback(string $mime, string $path): bool
    {
        $m = strtolower(trim($mime));
        if (
            $m === "image/tiff"
            || $m === "image/x-tiff"
            || $m === "image/heic"
            || $m === "image/heif"
        ) {
            return true;
        }
        $ext = strtolower((string)pathinfo($path, PATHINFO_EXTENSION));
        return $ext === "tif" || $ext === "tiff" || $ext === "heic" || $ext === "heif";
    }

    private function renderAsJpeg(string $path): ?string
    {
        if (!class_exists(\Imagick::class)) {
            return null;
        }
        try {
            $img = new \Imagick();
            $img->readImage($path . "[0]");
            $img->setImageOrientation(\Imagick::ORIENTATION_TOPLEFT);
            if (method_exists($img, "autoOrient")) {
                $img->autoOrient();
            }
            $img->setImageFormat("jpeg");
            $img->setImageCompressionQuality(90);
            $blob = $img->getImageBlob();
            $img->clear();
            $img->destroy();
            if (!is_string($blob) || $blob === "") {
                return null;
            }
            return $blob;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function previewCachePath(string $thumbsRoot, string $relPath): ?string
    {
        if ($thumbsRoot === "" || $relPath === "") {
            return null;
        }
        $rel = str_replace("\\", "/", $relPath);
        if ($rel[0] === "/" || str_contains($rel, ":")) {
            return null;
        }
        foreach (explode("/", $rel) as $part) {
            if ($part === "..") {
                return null;
            }
        }
        $full = rtrim($thumbsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR
            . str_replace("/", DIRECTORY_SEPARATOR, $rel);
        $info = pathinfo($full);
        if (!isset($info["dirname"], $info["filename"])) {
            return null;
        }
        return (string)$info["dirname"] . DIRECTORY_SEPARATOR . (string)$info["filename"] . ".preview.jpg";
    }

    private function ensurePreviewCached(string $sourcePath, string $cachePath): void
    {
        clearstatcache(true, $sourcePath);
        clearstatcache(true, $cachePath);
        $sourceMtime = (int)@filemtime($sourcePath);
        $cacheMtime = is_file($cachePath) ? (int)@filemtime($cachePath) : 0;
        if ($cacheMtime > 0 && $cacheMtime >= $sourceMtime && (int)@filesize($cachePath) > 0) {
            return;
        }

        $dir = dirname($cachePath);
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            return;
        }

        $jpeg = $this->renderAsJpeg($sourcePath);
        if ($jpeg === null || $jpeg === "") {
            return;
        }

        $tmp = $cachePath . ".tmp." . getmypid() . "." . bin2hex(random_bytes(4));
        try {
            if (@file_put_contents($tmp, $jpeg) === false || (int)@filesize($tmp) <= 0) {
                return;
            }
            @chmod($tmp, 0664);
            if (!@rename($tmp, $cachePath)) {
                return;
            }
            if ($sourceMtime > 0) {
                @touch($cachePath, $sourceMtime);
            }
        } finally {
            if (is_file($tmp)) {
                @unlink($tmp);
            }
        }
    }

    private function servePreviewCacheFile(string $cachePath, string $downloadName): void
    {
        $mtime = (int)@filemtime($cachePath);
        $size = (int)@filesize($cachePath);
        $etag = "\"" . md5((string)$mtime . ":" . (string)$size . ":" . $cachePath . ":preview-cache") . "\"";
        header("Content-Type: image/jpeg");
        header("Cache-Control: private, no-cache, must-revalidate, max-age=0");
        header("Pragma: no-cache");
        header("ETag: " . $etag);
        header("Last-Modified: " . gmdate("D, d M Y H:i:s", $mtime) . " GMT");
        if (isset($_SERVER["HTTP_IF_NONE_MATCH"]) && trim((string)$_SERVER["HTTP_IF_NONE_MATCH"]) === $etag) {
            http_response_code(304);
            return;
        }
        header("Content-Length: " . (string)$size);
        header("Content-Disposition: inline; filename=\"" . $downloadName . "\"");
        readfile($cachePath);
    }

    private function isRelPathTrashed(Maria $maria, string $relPath): bool
    {
        try {
            $rows = $maria->query(
                "SELECT id FROM wa_media_trash WHERE rel_path = ? AND status = 'trashed' LIMIT 1",
                [$relPath]
            );
            return $rows !== [];
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header("Content-Type: application/json; charset=utf-8");
        echo json_encode($payload);
    }
}

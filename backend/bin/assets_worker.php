<?php

declare(strict_types=1);

use WebAlbum\Assets\AssetPaths;
use WebAlbum\Assets\AssetSupport;
use WebAlbum\Assets\Jobs;
use WebAlbum\Assets\ObjectTransformJobs;
use WebAlbum\Db\Maria;
use WebAlbum\Db\SqliteIndex;
use WebAlbum\Media\MediaTagEdits;
use WebAlbum\Media\MediaTagSupport;
use WebAlbum\ObjectSyncService;
use WebAlbum\SystemTools;
use WebAlbum\Thumb\ThumbPolicy;

$root = dirname(__DIR__);
$autoload = $root . '/vendor/autoload.php';
if (is_file($autoload)) {
    require $autoload;
} else {
    spl_autoload_register(function (string $class) use ($root): void {
        if (!str_starts_with($class, 'WebAlbum\\')) {
            return;
        }
        $path = $root . '/src/' . str_replace('\\', '/', substr($class, 9)) . '.php';
        if (is_file($path)) {
            require $path;
        }
    });
}

$config = require $root . '/config/config.php';
$db = new Maria($config['mariadb']['dsn'], $config['mariadb']['user'], $config['mariadb']['pass']);

$once = in_array('--once', $argv, true);
$maxJobs = 0; // 0 = no limit (run until queue is empty)
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--max-jobs=')) {
        $maxJobs = max(0, (int)substr($arg, strlen('--max-jobs=')));
    }
}

$workerId = gethostname() . ':' . getmypid();
Jobs::recoverStaleLocks($db, 15);
ObjectTransformJobs::recoverStaleLocks($db, 15);

$processed = 0;
while (true) {
    $job = Jobs::claimNext($db, $workerId);
    $queue = 'assets';
    if ($job === null) {
        $job = ObjectTransformJobs::claimNext($db, $workerId);
        $queue = 'object';
    }
    if ($job === null) {
        // Batch mode: stop when queue is currently empty.
        if ($once || $maxJobs > 0) {
            break;
        }
        usleep(300000);
        continue;
    }

    $processed++;
    $jobId = (int)$job['id'];
    $attempts = (int)$job['attempts'];

    try {
        if ($queue === 'assets') {
            processJob($db, $config, $job);
            Jobs::markDone($db, $jobId);
        } else {
            processObjectTransformJob($db, $config, $job);
            ObjectTransformJobs::markDone($db, $jobId);
        }
        echo "done {$queue} job #{$jobId} ({$job['job_type']})\n";
    } catch (Throwable $e) {
        $nonRetry = str_starts_with($e->getMessage(), 'NON_RETRY:');
        if ($queue === 'assets') {
            Jobs::markError($db, $jobId, $e->getMessage(), $nonRetry ? 999 : $attempts);
            markOpenMediaTagEditErrored($db, $job, $nonRetry ? 999 : $attempts, $e->getMessage());
        } else {
            ObjectTransformJobs::markError($db, $jobId, $e->getMessage(), $attempts);
        }
        echo "error {$queue} job #{$jobId}: {$e->getMessage()}\n";
    }

    if ($once || ($maxJobs > 0 && $processed >= $maxJobs)) {
        break;
    }
}

function processJob(Maria $db, array $config, array $job): void
{
    $type = (string)$job['job_type'];
    $payload = is_array($job['payload']) ? $job['payload'] : [];

    if ($type === 'media_tag_edit') {
        processMediaTagEditJob($db, $config, $job);
        return;
    }

    $assetId = (int)($payload['asset_id'] ?? 0);
    if ($assetId < 1) {
        throw new RuntimeException('Missing asset_id in payload');
    }

    $rows = $db->query('SELECT id, rel_path, ext, type FROM wa_assets WHERE id = ?', [$assetId]);
    if ($rows === []) {
        throw new RuntimeException('Asset not found');
    }
    $asset = $rows[0];
    $relPath = (string)$asset['rel_path'];
    $photosRoot = (string)($config['photos']['root'] ?? '');
    $thumbRoot = (string)($config['thumbs']['root'] ?? '');
    $sourcePath = AssetPaths::joinInside($photosRoot, $relPath);
    if ($sourcePath === null || !is_file($sourcePath) || !is_readable($sourcePath)) {
        throw new RuntimeException('Source file is missing');
    }

    if (!is_dir($thumbRoot)) {
        @mkdir($thumbRoot, 0755, true);
    }

    if ($type === 'doc_pdf_preview') {
        buildPdfPreview($db, $config, $assetId, $asset, $sourcePath, $thumbRoot);
        return;
    }

    if ($type === 'doc_thumb') {
        buildDocThumb($db, $config, $assetId, $asset, $sourcePath, $thumbRoot);
        return;
    }

    throw new RuntimeException('Unsupported job_type: ' . $type);
}

function buildPdfPreview(Maria $db, array $config, int $assetId, array $asset, string $sourcePath, string $thumbRoot): void
{
    $ext = strtolower((string)$asset['ext']);
    if (!AssetSupport::isConvertibleToPdf($ext)) {
        throw new RuntimeException('pdf_preview job supports convertible documents only');
    }

    $tools = SystemTools::checkExternalTools($config);
    $soffice = $tools['tools']['soffice'] ?? null;
    if (!is_array($soffice) || !($soffice['available'] ?? false) || empty($soffice['path'])) {
        throw new RuntimeException('soffice not available');
    }

    $target = AssetPaths::derivativePath($thumbRoot, (string)$asset['rel_path'], '.wa-preview.pdf');
    if ($target === null) {
        throw new RuntimeException('Invalid derivative path');
    }
    ensureDir(dirname($target));

    $tmpDir = sys_get_temp_dir() . '/wa-soffice-' . bin2hex(random_bytes(6));
    ensureDir($tmpDir);

    $cmd = escapeshellarg((string)$soffice['path'])
        . ' --headless --nologo --nofirststartwizard --convert-to pdf --outdir '
        . escapeshellarg($tmpDir) . ' ' . escapeshellarg($sourcePath) . ' 2>&1';
    $output = [];
    $code = 0;
    exec($cmd, $output, $code);
    if ($code !== 0) {
        throw new RuntimeException('soffice conversion failed: ' . trim(implode("\n", $output)));
    }

    $converted = findFirstPdf($tmpDir);
    if ($converted === null || !is_file($converted)) {
        throw new RuntimeException('soffice did not produce PDF');
    }

    $tmpTarget = $target . '.tmp.' . getmypid();
    if (!@copy($converted, $tmpTarget)) {
        throw new RuntimeException('Failed to copy converted preview');
    }
    if (!is_file($tmpTarget) || (int)@filesize($tmpTarget) <= 0 || !is_readable($tmpTarget)) {
        @unlink($tmpTarget);
        throw new RuntimeException('Converted preview is invalid');
    }
    if (!@rename($tmpTarget, $target)) {
        @unlink($tmpTarget);
        throw new RuntimeException('Failed to publish preview');
    }

    $db->exec(
        "INSERT INTO wa_asset_derivatives (asset_id, kind, path, status, error_text, updated_at)\n" .
        "VALUES (?, 'pdf_preview', ?, 'ready', NULL, NOW())\n" .
        "ON DUPLICATE KEY UPDATE path = VALUES(path), status = 'ready', error_text = NULL, updated_at = NOW()",
        [$assetId, $target]
    );
}

function buildDocThumb(Maria $db, array $config, int $assetId, array $asset, string $sourcePath, string $thumbRoot): void
{
    $ext = strtolower((string)$asset['ext']);
    $sourcePdf = null;
    if ($ext === 'pdf') {
        $sourcePdf = $sourcePath;
    } elseif (AssetSupport::isConvertibleToPdf($ext)) {
        $previewRows = $db->query(
            "SELECT path, status FROM wa_asset_derivatives WHERE asset_id = ? AND kind = 'pdf_preview' LIMIT 1",
            [$assetId]
        );
        if ($previewRows === [] || (string)$previewRows[0]['status'] !== 'ready' || !is_file((string)$previewRows[0]['path'])) {
            Jobs::enqueue($db, 'doc_pdf_preview', ['asset_id' => $assetId]);
            throw new RuntimeException('PDF preview not ready yet');
        }
        $sourcePdf = (string)$previewRows[0]['path'];
    } else {
        throw new RuntimeException('thumb job supports doc assets only');
    }

    $target = AssetPaths::derivativePath($thumbRoot, (string)$asset['rel_path'], '.wa-thumb.jpg');
    if ($target === null) {
        throw new RuntimeException('Invalid thumb path');
    }
    ensureDir(dirname($target));

    $tmpTarget = $target . '.tmp.' . getmypid();
    @unlink($tmpTarget);

    $tools = SystemTools::checkExternalTools($config);
    $gs = $tools['tools']['gs'] ?? null;
    if (!is_array($gs) || !($gs['available'] ?? false) || empty($gs['path'])) {
        throw new RuntimeException('ghostscript (gs) not available for document thumbnail rendering');
    }

    if (!renderPdfThumb($config, $sourcePdf, $tmpTarget)) {
        throw new RuntimeException('Failed to render document thumbnail');
    }
    if (!is_file($tmpTarget) || (int)@filesize($tmpTarget) <= 0 || !is_readable($tmpTarget)) {
        @unlink($tmpTarget);
        throw new RuntimeException('Generated thumb is invalid');
    }
    if (!@rename($tmpTarget, $target)) {
        @unlink($tmpTarget);
        throw new RuntimeException('Failed to publish thumbnail');
    }

    $db->exec(
        "INSERT INTO wa_asset_derivatives (asset_id, kind, path, status, error_text, updated_at)\n" .
        "VALUES (?, 'thumb', ?, 'ready', NULL, NOW())\n" .
        "ON DUPLICATE KEY UPDATE path = VALUES(path), status = 'ready', error_text = NULL, updated_at = NOW()",
        [$assetId, $target]
    );
}

function processMediaTagEditJob(Maria $db, array $config, array $job): void
{
    $payload = is_array($job['payload'] ?? null) ? $job['payload'] : [];
    $editId = (int)($payload['edit_id'] ?? 0);
    if ($editId < 1) {
        throw new RuntimeException('Missing edit_id in media_tag_edit payload');
    }

    $edit = MediaTagEdits::getEditById($db, $editId);
    if ($edit === null) {
        throw new RuntimeException('Tag edit not found');
    }
    if ((string)($edit['status'] ?? '') !== 'open') {
        return;
    }

    $relPath = (string)($edit['rel_path'] ?? '');
    if ($relPath === '') {
        throw new RuntimeException('Tag edit rel_path is missing');
    }

    $photosRoot = (string)($config['photos']['root'] ?? '');
    $sourcePath = MediaTagSupport::safeJoin($photosRoot, $relPath);
    if ($sourcePath === null || !is_file($sourcePath) || !is_readable($sourcePath)) {
        throw new RuntimeException('Source file is missing for rel_path: ' . $relPath);
    }

    $sqlite = new SqliteIndex((string)($config['sqlite']['path'] ?? ''));
    $beforeFile = MediaTagSupport::fetchFileByRelPath($sqlite, $relPath);
    if ($beforeFile === null) {
        throw new RuntimeException('SQLite file row not found for rel_path: ' . $relPath);
    }
    $beforeSha = strtolower(trim((string)($beforeFile['sha256'] ?? '')));
    $beforeTags = MediaTagSupport::fetchDisplayTags($sqlite, (int)$beforeFile['id']);

    ensureObjectBackupExists($db, $config, $edit, $sourcePath);

    $action = (string)($edit['action_type'] ?? '');
    if ($action === 'restore_backup') {
        restoreObjectBackup($config, $edit, $sourcePath);
    } else {
        $newTags = decodeTagJson((string)($edit['new_tags_json'] ?? ''));
        MediaTagSupport::writeTagsWithExiftool((string)($config['tools']['exiftool'] ?? 'exiftool'), $sourcePath, $newTags);
    }

    reindexSingleMediaFile($config, $relPath);

    $sqliteAfter = new SqliteIndex((string)($config['sqlite']['path'] ?? ''));
    $sync = (new ObjectSyncService())->syncMediaRelPath($sqliteAfter, $db, $relPath, $beforeSha);
    $afterFile = MediaTagSupport::fetchFileByRelPath($sqliteAfter, $relPath);
    if ($afterFile === null) {
        throw new RuntimeException('SQLite file row missing after refresh');
    }
    $afterTags = MediaTagSupport::fetchDisplayTags($sqliteAfter, (int)$afterFile['id']);

    MediaTagEdits::markBackupReady($db, (int)($edit['backup_id'] ?? 0), (int)($sync['object_id'] ?? 0));
    MediaTagEdits::markEditApplied(
        $db,
        $editId,
        $action === 'restore_backup' ? 'restored' : 'final',
        (int)($sync['object_id'] ?? 0),
        (string)($sync['new_sha256'] ?? '')
    );

    $db->exec(
        "UPDATE wa_object_tag_edits SET old_tags_json = ?, new_tags_json = ? WHERE id = ?",
        [
            json_encode(array_values($beforeTags), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            $action === 'restore_backup'
                ? json_encode(array_values($afterTags), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                : (string)($edit['new_tags_json'] ?? '[]'),
            $editId,
        ]
    );

    @error_log('webalbum_media_tag_edit ' . json_encode([
        'edit_id' => $editId,
        'action' => $action,
        'rel_path' => $relPath,
        'old_sha256' => $sync['old_sha256'] ?? null,
        'new_sha256' => $sync['new_sha256'] ?? null,
        'old_tags' => $beforeTags,
        'new_tags' => $afterTags,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
}

function ensureObjectBackupExists(Maria $db, array $config, array $edit, string $sourcePath): void
{
    $backupId = (int)($edit['backup_id'] ?? 0);
    if ($backupId < 1) {
        throw new RuntimeException('Tag edit backup record is missing');
    }
    $backupRoot = (string)($config['backups']['root'] ?? '');
    if ($backupRoot === '') {
        throw new RuntimeException('Backup root is not configured');
    }
    $backupPath = MediaTagSupport::backupPath($backupRoot, (string)($edit['backup_rel_path'] ?? ''));
    if ($backupPath === null) {
        throw new RuntimeException('Backup path is invalid');
    }

    if (is_file($backupPath) && is_readable($backupPath) && (string)($edit['backup_status'] ?? '') === 'ready') {
        return;
    }

    try {
        MediaTagSupport::copyFileAtomic($sourcePath, $backupPath);
        MediaTagEdits::markBackupReady($db, $backupId, isset($edit['object_id']) ? (int)$edit['object_id'] : null);
    } catch (Throwable $e) {
        MediaTagEdits::markBackupError($db, $backupId, $e->getMessage());
        throw $e;
    }
}

function restoreObjectBackup(array $config, array $edit, string $sourcePath): void
{
    $backupRoot = (string)($config['backups']['root'] ?? '');
    if ($backupRoot === '') {
        throw new RuntimeException('Backup root is not configured');
    }
    $backupPath = MediaTagSupport::backupPath($backupRoot, (string)($edit['backup_rel_path'] ?? ''));
    if ($backupPath === null || !is_file($backupPath) || !is_readable($backupPath)) {
        throw new RuntimeException('Backup file is missing');
    }
    MediaTagSupport::copyFileAtomic($backupPath, $sourcePath);
}

function decodeTagJson(string $json): array
{
    $decoded = json_decode($json, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('Tag edit payload has invalid tags JSON');
    }
    return MediaTagSupport::normalizeTags($decoded);
}

function reindexSingleMediaFile(array $config, string $relPath): void
{
    $sqlitePath = (string)($config['sqlite']['path'] ?? '');
    $photosRoot = (string)($config['photos']['root'] ?? '');
    $indexerRoot = (string)($config['indexer']['root'] ?? '');
    $python = (string)($config['indexer']['python'] ?? 'python3');
    $configPath = trim((string)($config['indexer']['config_path'] ?? ''));
    if ($configPath === '') {
        $configPath = rtrim($indexerRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'config.yaml';
    }

    if ($sqlitePath === '' || !is_file($sqlitePath)) {
        throw new RuntimeException('SQLite DB not found for reindex');
    }
    if ($photosRoot === '' || !is_dir($photosRoot)) {
        throw new RuntimeException('Photos root is not available for reindex');
    }
    if ($indexerRoot === '' || !is_dir($indexerRoot)) {
        throw new RuntimeException('Indexer root is not configured or missing');
    }
    if (!is_file($configPath)) {
        throw new RuntimeException('Indexer config.yaml not found: ' . $configPath);
    }

    $cmd = [
        $python,
        '-m', 'app',
        '--cli',
        '--db', $sqlitePath,
        '--root', $photosRoot,
        '--config', $configPath,
        '--refresh-file', $relPath,
        '--json',
        '--no-progress',
    ];

    [$ok, $stdout, $stderr, $timedOut] = runProcessWithTimeout($cmd, $indexerRoot, 180);
    if (!$ok) {
        if ($timedOut) {
            throw new RuntimeException('Indexer single-file refresh timed out');
        }
        $msg = trim($stderr !== '' ? $stderr : $stdout);
        if ($msg === '') {
            $msg = 'Indexer single-file refresh failed';
        }
        throw new RuntimeException($msg);
    }
}

function runProcessWithTimeout(array $cmd, ?string $cwd, int $timeoutSec): array
{
    $descriptors = [
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];
    $proc = @proc_open($cmd, $descriptors, $pipes, $cwd, null, ['bypass_shell' => true]);
    if (!is_resource($proc)) {
        throw new RuntimeException('Failed to start child process');
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

function markOpenMediaTagEditErrored(Maria $db, array $job, int $attempts, string $error): void
{
    if ((string)($job['job_type'] ?? '') !== 'media_tag_edit' || $attempts < 8) {
        return;
    }
    $payload = is_array($job['payload'] ?? null) ? $job['payload'] : [];
    $editId = (int)($payload['edit_id'] ?? 0);
    if ($editId > 0) {
        MediaTagEdits::markEditError($db, $editId, $error);
    }
}

function processObjectTransformJob(Maria $db, array $config, array $job): void
{
    $jobType = (string)($job['job_type'] ?? '');
    if ($jobType !== 'rotate') {
        throw new RuntimeException('Unsupported object job_type: ' . $jobType);
    }

    $payload = is_array($job['payload'] ?? null) ? $job['payload'] : [];
    $relPath = (string)($payload['rel_path'] ?? '');
    $type = strtolower(trim((string)($payload['type'] ?? '')));
    $turns = normalizeQuarterTurns((int)($payload['quarter_turns'] ?? 0));
    $jobId = (int)($job['id'] ?? 0);

    if ($relPath === '') {
        throw new RuntimeException('Missing rel_path in object transform payload');
    }
    if ($turns === 0) {
        throw new RuntimeException('Invalid rotation (quarter_turns=0)');
    }
    if ($type !== 'image' && $type !== 'video') {
        throw new RuntimeException('Unsupported object transform media type');
    }

    $photosRoot = (string)($config['photos']['root'] ?? '');
    $sourcePath = AssetPaths::joinInside($photosRoot, $relPath);
    if ($sourcePath === null || !is_file($sourcePath) || !is_readable($sourcePath)) {
        throw new RuntimeException('Source file is missing for rel_path: ' . $relPath);
    }

    $tools = SystemTools::checkExternalTools($config, true);
    $ffmpegTool = $tools['tools']['ffmpeg'] ?? ['available' => false, 'path' => null];
    if (!(bool)($ffmpegTool['available'] ?? false) || empty($ffmpegTool['path'])) {
        throw new RuntimeException('ffmpeg not available');
    }
    $ffmpeg = (string)$ffmpegTool['path'];
    $exiftoolTool = $tools['tools']['exiftool'] ?? ['available' => false, 'path' => null];

    $tmp = tmpRotatePath($sourcePath);
    $filter = rotationFilter($turns);
    try {
        runRotate($ffmpeg, $sourcePath, $tmp, $filter, $type);
        if (!is_file($tmp) || (int)@filesize($tmp) <= 0) {
            throw new RuntimeException('Rotation output is empty');
        }
        preserveOwnershipAndMode($sourcePath, $tmp);
        if (!@rename($tmp, $sourcePath)) {
            throw new RuntimeException('Failed to replace original file after rotation');
        }
    } finally {
        if (is_file($tmp)) {
            @unlink($tmp);
        }
    }

    if ($type === 'image') {
        normalizeImageOrientationTag(
            $sourcePath,
            (bool)($exiftoolTool['available'] ?? false),
            is_string($exiftoolTool['path'] ?? null) ? (string)$exiftoolTool['path'] : 'exiftool'
        );
    }

    $thumbRoot = (string)($config['thumbs']['root'] ?? '');
    if ($thumbRoot !== '') {
        $thumbPath = ThumbPolicy::thumbPath($thumbRoot, $relPath);
        if (is_string($thumbPath) && is_file($thumbPath)) {
            @unlink($thumbPath);
        }
    }

    try {
        refreshObjectHashesAfterTransform($db, $config, $job, $sourcePath, $relPath, $type, $jobId);
    } catch (Throwable $e) {
        throw new RuntimeException('NON_RETRY: hash/db sync after rotate failed: ' . $e->getMessage());
    }

    @error_log('webalbum_object_transform ' . json_encode([
        'job_id' => (int)($job['id'] ?? 0),
        'object_id' => (int)($job['object_id'] ?? 0),
        'proposal_id' => isset($job['proposal_id']) ? (int)$job['proposal_id'] : null,
        'job_type' => $jobType,
        'rel_path' => $relPath,
        'type' => $type,
        'quarter_turns' => $turns,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
}

function refreshObjectHashesAfterTransform(
    Maria $db,
    array $config,
    array $job,
    string $sourcePath,
    string $relPath,
    string $type,
    int $jobId
): void {
    $sqlitePath = (string)($config['sqlite']['path'] ?? '');
    if ($sqlitePath === '') {
        throw new RuntimeException('SQLite path is not configured');
    }
    if (!is_file($sqlitePath)) {
        throw new RuntimeException('SQLite DB not found: ' . $sqlitePath);
    }

    $newSha = strtolower((string)@hash_file('sha256', $sourcePath));
    if (!preg_match('/^[a-f0-9]{64}$/', $newSha)) {
        throw new RuntimeException('Failed to compute sha256 for transformed media');
    }
    $stat = @stat($sourcePath);
    $size = is_array($stat) ? (int)($stat['size'] ?? 0) : 0;
    $mtime = is_array($stat) ? (int)($stat['mtime'] ?? 0) : 0;

    $sqlite = new PDO(
        'sqlite:' . $sqlitePath,
        null,
        null,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    $oldSha = '';
    $shaStmt = $sqlite->prepare(
        "SELECT LOWER(sha256) AS sha256
         FROM files
         WHERE rel_path = ?
           AND type IN ('image','video')
         ORDER BY id ASC
         LIMIT 1"
    );
    $shaStmt->execute([$relPath]);
    $shaRow = $shaStmt->fetch();
    if (is_array($shaRow) && is_string($shaRow['sha256'] ?? null)) {
        $oldSha = strtolower(trim((string)$shaRow['sha256']));
    }

    $sqlite->beginTransaction();
    try {
        $upd = $sqlite->prepare(
            "UPDATE files
             SET sha256 = ?, size = ?, mtime = ?, indexed_at = datetime('now')
             WHERE rel_path = ?
               AND type IN ('image','video')"
        );
        $upd->execute([$newSha, max(0, $size), max(0, $mtime), $relPath]);
        $sqlite->commit();
    } catch (Throwable $e) {
        if ($sqlite->inTransaction()) {
            $sqlite->rollBack();
        }
        throw $e;
    }

    $oldObjectSha = '';
    $oldObjectId = (int)($job['object_id'] ?? 0);
    if ($oldObjectId > 0) {
        $oldRows = $db->query("SELECT sha256 FROM wa_objects WHERE id = ? LIMIT 1", [$oldObjectId]);
        if ($oldRows !== []) {
            $oldObjectSha = strtolower(trim((string)($oldRows[0]['sha256'] ?? '')));
        }
    }
    if (!preg_match('/^[a-f0-9]{64}$/', $oldObjectSha)) {
        $oldObjectSha = preg_match('/^[a-f0-9]{64}$/', $oldSha) ? $oldSha : '';
    }

    $db->exec("START TRANSACTION");
    try {
        $db->exec(
            "INSERT INTO wa_objects (sha256, status, first_seen_at, last_seen_at, orphaned_at, last_synced_at)
             VALUES (?, 'active', NOW(), NOW(), NULL, NOW())
             ON DUPLICATE KEY UPDATE
               status = 'active',
               orphaned_at = NULL,
               last_seen_at = NOW(),
               last_synced_at = NOW()",
            [$newSha]
        );
        $newObjectRows = $db->query("SELECT id FROM wa_objects WHERE sha256 = ? LIMIT 1", [$newSha]);
        $newObjectId = (int)($newObjectRows[0]['id'] ?? 0);
        if ($newObjectId > 0 && $jobId > 0) {
            $db->exec("UPDATE wa_object_transform_jobs SET object_id = ? WHERE id = ?", [$newObjectId, $jobId]);
        }

        if ($oldObjectSha !== '' && $oldObjectSha !== $newSha) {
            $rem = $sqlite->prepare(
                "SELECT COUNT(*) AS c
                 FROM files
                 WHERE sha256 IS NOT NULL
                   AND LOWER(sha256) = ?"
            );
            $rem->execute([$oldObjectSha]);
            $remaining = (int)(($rem->fetch()['c'] ?? 0));
            if ($remaining === 0) {
                $db->exec(
                    "UPDATE wa_objects
                     SET status = 'orphaned',
                         orphaned_at = IF(orphaned_at IS NULL, NOW(), orphaned_at),
                         last_synced_at = NOW()
                     WHERE sha256 = ?
                       AND status = 'active'",
                    [$oldObjectSha]
                );
            } else {
                $db->exec(
                    "UPDATE wa_objects
                     SET status = 'active',
                         orphaned_at = NULL,
                         last_seen_at = NOW(),
                         last_synced_at = NOW()
                     WHERE sha256 = ?",
                    [$oldObjectSha]
                );
            }
        }

        $db->exec(
            "UPDATE wa_assets
             SET sha256 = ?, size = ?, mtime = ?, updated_at = NOW()
             WHERE rel_path = ?",
            [$newSha, max(0, $size), max(0, $mtime), $relPath]
        );

        $db->exec("COMMIT");
    } catch (Throwable $e) {
        $db->exec("ROLLBACK");
        throw $e;
    }

    @error_log('webalbum_object_sha_sync ' . json_encode([
        'job_id' => $jobId,
        'rel_path' => $relPath,
        'type' => $type,
        'old_sha256' => $oldObjectSha !== '' ? $oldObjectSha : null,
        'new_sha256' => $newSha,
        'size' => $size,
        'mtime' => $mtime,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
}

function normalizeQuarterTurns(int $turns): int
{
    $value = $turns % 4;
    if ($value < 0) {
        $value += 4;
    }
    return $value;
}

function rotationFilter(int $turns): string
{
    return match ($turns) {
        1 => 'transpose=1',
        2 => 'hflip,vflip',
        3 => 'transpose=2',
        default => '',
    };
}

function runRotate(string $ffmpeg, string $src, string $dest, string $filter, string $type): void
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
        throw new RuntimeException('Failed to start ffmpeg');
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
            throw new RuntimeException('ffmpeg timeout during rotate');
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
        throw new RuntimeException($msg);
    }
}

function normalizeImageOrientationTag(string $path, bool $available, string $binary): array
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

function tmpRotatePath(string $path): string
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

function preserveOwnershipAndMode(string $source, string $dest): void
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

function renderPdfThumb(array $config, string $pdfPath, string $destJpeg): bool
{
    $max = (int)($config['thumbs']['max'] ?? 256);
    $quality = (int)($config['thumbs']['quality'] ?? 75);

    if (class_exists(Imagick::class)) {
        try {
            $im = new Imagick();
            $im->setResolution(150, 150);
            $im->readImage($pdfPath . '[0]');
            $im->setImageBackgroundColor('white');
            $im = $im->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
            $im->setImageFormat('jpeg');
            $im->thumbnailImage($max, $max, true, true);
            $im->setImageCompressionQuality($quality);
            $im->stripImage();
            $ok = $im->writeImage($destJpeg);
            $im->clear();
            $im->destroy();
            if ($ok) {
                return true;
            }
        } catch (Throwable $e) {
            // fallback to ffmpeg
        }
    }

    $tools = SystemTools::checkExternalTools($config);
    $ffmpeg = $tools['tools']['ffmpeg'] ?? null;
    if (!is_array($ffmpeg) || !($ffmpeg['available'] ?? false) || empty($ffmpeg['path'])) {
        return false;
    }

    $cmd = escapeshellarg((string)$ffmpeg['path'])
        . ' -v error -y -i ' . escapeshellarg($pdfPath)
        . ' -frames:v 1 -vf ' . escapeshellarg('scale=' . $max . ':-1')
        . ' -q:v 3 ' . escapeshellarg($destJpeg) . ' 2>&1';
    $out = [];
    $code = 0;
    exec($cmd, $out, $code);
    return $code === 0 && is_file($destJpeg) && (int)@filesize($destJpeg) > 0;
}

function ensureDir(string $dir): void
{
    if (is_dir($dir)) {
        return;
    }
    if (!@mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new RuntimeException('Failed to create directory: ' . $dir);
    }
}

function findFirstPdf(string $dir): ?string
{
    $files = glob(rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.pdf');
    if (!is_array($files) || $files === []) {
        return null;
    }
    sort($files);
    return $files[0] ?? null;
}

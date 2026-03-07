<?php

declare(strict_types=1);

use WebAlbum\Db\Maria;
use WebAlbum\Db\SqliteIndex;
use WebAlbum\ObjectSyncService;

$root = dirname(__DIR__);
$autoload = $root . "/vendor/autoload.php";
if (is_file($autoload)) {
    require $autoload;
} else {
    spl_autoload_register(function (string $class): void {
        if (!str_starts_with($class, "WebAlbum\\")) {
            return;
        }
        $path = __DIR__ . "/../src/" . str_replace("\\", "/", substr($class, 9)) . ".php";
        if (is_file($path)) {
            require $path;
        }
    });
}

try {
    $config = require $root . "/config/config.php";
    $maria = new Maria(
        $config['mariadb']['dsn'],
        $config['mariadb']['user'],
        $config['mariadb']['pass']
    );
    $sqlite = new SqliteIndex((string)$config['sqlite']['path']);

    $result = (new ObjectSyncService())->sync($sqlite, $maria);
    echo json_encode(['ok' => true] + $result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit(0);
} catch (\Throwable $e) {
    fwrite(STDERR, "objects_sync failed: " . $e->getMessage() . PHP_EOL);
    exit(1);
}

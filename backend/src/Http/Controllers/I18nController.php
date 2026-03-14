<?php

declare(strict_types=1);

namespace WebAlbum\Http\Controllers;

use WebAlbum\Db\Maria;
use WebAlbum\I18nService;
use WebAlbum\UserContext;

final class I18nController
{
    private string $configPath;

    public function __construct(string $configPath)
    {
        $this->configPath = $configPath;
    }

    public function bundle(): void
    {
        try {
            $config = require $this->configPath;
            $db = new Maria(
                $config['mariadb']['dsn'],
                $config['mariadb']['user'],
                $config['mariadb']['pass']
            );
            $user = UserContext::currentUser($db);
            $service = new I18nService();
            $requested = isset($_GET['lang']) ? (string)$_GET['lang'] : null;
            $language = $service->resolveLanguage($db, $user, $requested);
            $bundle = $service->buildBundle($db, $language);
            $bundle['supported_languages'] = $service->supportedLanguages($db);
            $this->json($bundle);
        } catch (\Throwable $e) {
            $this->json([
                'language' => 'en',
                'fallback_language' => 'en',
                'supported_languages' => [['code' => 'en', 'translated_count' => 0]],
                'strings' => [],
                'error' => $e->getMessage(),
            ], 200);
        }
    }

    private function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}

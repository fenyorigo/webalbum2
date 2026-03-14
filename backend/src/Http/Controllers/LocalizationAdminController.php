<?php

declare(strict_types=1);

namespace WebAlbum\Http\Controllers;

use WebAlbum\AuditLogMetaCache;
use WebAlbum\Db\Maria;
use WebAlbum\I18nService;
use WebAlbum\UserContext;

final class LocalizationAdminController
{
    private string $configPath;

    public function __construct(string $configPath)
    {
        $this->configPath = $configPath;
    }

    public function listLanguages(): void
    {
        try {
            [$db] = $this->requireAdmin();
            $rows = $db->query(
                "SELECT code, name_en, name_native, is_active, sort_order
                 FROM wa_languages
                 ORDER BY sort_order ASC, code ASC"
            );
            $this->json(["items" => $rows]);
        } catch (\RuntimeException $e) {
            $this->json(["error" => $e->getMessage()], $this->httpStatus($e->getCode(), 400));
        } catch (\Throwable $e) {
            $this->json(["error" => $e->getMessage()], 500);
        }
    }

    public function createLanguage(): void
    {
        try {
            [$db, $admin] = $this->requireAdmin();
            $data = $this->decodeJsonBody();
            $service = new I18nService();
            $code = $service->normalizeLanguage(isset($data['code']) && is_string($data['code']) ? $data['code'] : null);
            if ($code === null) {
                $this->json(["error" => "Invalid language code"], 400);
                return;
            }
            $nameEn = trim((string)($data['name_en'] ?? ''));
            $nameNative = trim((string)($data['name_native'] ?? ''));
            $isActive = !empty($data['is_active']) ? 1 : 0;
            if ($nameEn === '' || $nameNative === '') {
                $this->json(["error" => "Language names are required"], 400);
                return;
            }
            $exists = $db->query("SELECT code FROM wa_languages WHERE code = ? LIMIT 1", [$code]);
            if ($exists !== []) {
                $this->json(["error" => "Language already exists"], 409);
                return;
            }
            $maxRow = $db->query("SELECT COALESCE(MAX(sort_order), 0) AS m FROM wa_languages");
            $sortOrder = isset($data['sort_order']) && is_numeric($data['sort_order'])
                ? (int)$data['sort_order']
                : ((int)($maxRow[0]['m'] ?? 0) + 10);
            $db->exec(
                "INSERT INTO wa_languages (code, name_en, name_native, is_active, sort_order)
                 VALUES (?, ?, ?, ?, ?)",
                [$code, $nameEn, $nameNative, $isActive, $sortOrder]
            );
            $row = [
                'code' => $code,
                'name_en' => $nameEn,
                'name_native' => $nameNative,
                'is_active' => $isActive,
                'sort_order' => $sortOrder,
            ];
            $this->logAudit($db, (int)$admin['id'], 'admin_localization_language_create', [
                'code' => $code,
                'is_active' => $isActive === 1,
            ]);
            $this->json(["ok" => true, "item" => $row], 201);
        } catch (\JsonException $e) {
            $this->json(["error" => "Invalid JSON"], 400);
        } catch (\RuntimeException $e) {
            $this->json(["error" => $e->getMessage()], $this->httpStatus($e->getCode(), 400));
        } catch (\Throwable $e) {
            $this->json(["error" => $e->getMessage()], 500);
        }
    }

    public function listStrings(): void
    {
        try {
            [$db] = $this->requireAdmin();
            $service = new I18nService();
            $language = $service->normalizeLanguage(isset($_GET['language']) ? (string)$_GET['language'] : null) ?? 'en';
            $context = trim((string)($_GET['context'] ?? ''));
            $status = strtolower(trim((string)($_GET['status'] ?? 'all')));
            $query = trim((string)($_GET['query'] ?? ''));
            $page = max(1, (int)($_GET['page'] ?? 1));
            $pageSize = max(1, min(200, (int)($_GET['page_size'] ?? 50)));
            $offset = ($page - 1) * $pageSize;

            $params = [$language];
            $where = [];
            if ($context !== '') {
                $where[] = 's.context = ?';
                $params[] = $context;
            }
            if ($query !== '') {
                $like = '%' . $query . '%';
                $where[] = '(s.string_key LIKE ? OR s.default_en LIKE ? OR COALESCE(t.translated_value, \'\') LIKE ?)';
                $params[] = $like;
                $params[] = $like;
                $params[] = $like;
            }
            if ($status === 'missing') {
                $where[] = 't.id IS NULL';
            } elseif ($status === 'draft') {
                $where[] = 't.id IS NOT NULL AND t.is_final = 0';
            } elseif ($status === 'final') {
                $where[] = 't.id IS NOT NULL AND t.is_final = 1';
            }
            $whereSql = $where ? (' WHERE ' . implode(' AND ', $where)) : '';

            $countRows = $db->query(
                "SELECT COUNT(*) AS c
                 FROM wa_ui_strings s
                 LEFT JOIN wa_ui_translations t
                   ON t.ui_string_id = s.id
                  AND t.language_code = ?" . $whereSql,
                $params
            );
            $total = (int)($countRows[0]['c'] ?? 0);

            $limitSql = sprintf(' LIMIT %d OFFSET %d', $pageSize, $offset);
            $rows = $db->query(
                "SELECT s.id,
                        s.string_key,
                        s.default_en,
                        s.context,
                        t.id AS translation_id,
                        t.language_code,
                        t.translated_value,
                        t.is_final,
                        t.updated_at,
                        t.updated_by_user_id,
                        CASE
                          WHEN t.id IS NULL THEN 'missing'
                          WHEN t.is_final = 1 THEN 'final'
                          ELSE 'draft'
                        END AS translation_status
                 FROM wa_ui_strings s
                 LEFT JOIN wa_ui_translations t
                   ON t.ui_string_id = s.id
                  AND t.language_code = ?" . $whereSql . "
                 ORDER BY s.context ASC, s.string_key ASC
                 " . $limitSql,
                $params
            );

            $contexts = $db->query(
                "SELECT DISTINCT context
                 FROM wa_ui_strings
                 WHERE context IS NOT NULL AND context <> ''
                 ORDER BY context ASC"
            );
            $this->json([
                'items' => $rows,
                'total' => $total,
                'page' => $page,
                'page_size' => $pageSize,
                'total_pages' => max(1, (int)ceil($total / $pageSize)),
                'selected_language' => $language,
                'contexts' => array_map(static fn(array $row): string => (string)$row['context'], $contexts),
            ]);
        } catch (\RuntimeException $e) {
            $this->json(["error" => $e->getMessage()], $this->httpStatus($e->getCode(), 400));
        } catch (\Throwable $e) {
            $this->json(["error" => $e->getMessage()], 500);
        }
    }

    public function createString(): void
    {
        try {
            [$db, $admin] = $this->requireAdmin();
            $data = $this->decodeJsonBody();
            $service = new I18nService();
            $stringKey = trim((string)($data['string_key'] ?? ''));
            $context = trim((string)($data['context'] ?? ''));
            $defaultEn = trim((string)($data['default_en'] ?? ''));
            $language = $service->normalizeLanguage(isset($data['language']) ? (string)$data['language'] : null);
            $initialTranslation = trim((string)($data['translated_value'] ?? ''));
            $isFinal = !empty($data['is_final']) ? 1 : 0;

            if (!preg_match('/^[a-z0-9][a-z0-9._-]{1,127}$/', $stringKey)) {
                $this->json(["error" => "Invalid string key"], 400);
                return;
            }
            if ($defaultEn === '') {
                $this->json(["error" => "Default English text is required"], 400);
                return;
            }
            $exists = $db->query("SELECT id FROM wa_ui_strings WHERE string_key = ? LIMIT 1", [$stringKey]);
            if ($exists !== []) {
                $this->json(["error" => "String key already exists"], 409);
                return;
            }

            $db->begin();
            $db->exec(
                "INSERT INTO wa_ui_strings (string_key, default_en, context, created_at, updated_at)
                 VALUES (?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)",
                [$stringKey, $defaultEn, $context !== '' ? $context : null]
            );
            $stringId = $db->lastInsertId();
            if ($language !== null && $language !== 'en' && $initialTranslation !== '') {
                $this->assertLanguageExists($db, $language);
                $db->exec(
                    "INSERT INTO wa_ui_translations (ui_string_id, language_code, translated_value, is_final, updated_by_user_id, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)",
                    [$stringId, $language, $initialTranslation, $isFinal, (int)$admin['id']]
                );
            }
            $db->commit();

            $this->logAudit($db, (int)$admin['id'], 'admin_localization_string_create', [
                'string_key' => $stringKey,
                'context' => $context,
                'language' => $language,
                'has_initial_translation' => $initialTranslation !== '',
            ]);
            $this->json([
                'ok' => true,
                'item' => [
                    'id' => $stringId,
                    'string_key' => $stringKey,
                    'default_en' => $defaultEn,
                    'context' => $context,
                ],
            ], 201);
        } catch (\JsonException $e) {
            $this->json(["error" => "Invalid JSON"], 400);
        } catch (\RuntimeException $e) {
            $this->json(["error" => $e->getMessage()], $this->httpStatus($e->getCode(), 400));
        } catch (\Throwable $e) {
            try {
                if (isset($db)) {
                    $db->rollBack();
                }
            } catch (\Throwable $_e) {
            }
            $this->json(["error" => $e->getMessage()], 500);
        }
    }

    public function saveTranslation(): void
    {
        try {
            [$db, $admin] = $this->requireAdmin();
            $data = $this->decodeJsonBody();
            $service = new I18nService();
            $stringKey = trim((string)($data['string_key'] ?? ''));
            $language = $service->normalizeLanguage(isset($data['language']) ? (string)$data['language'] : null);
            $translatedValue = trim((string)($data['translated_value'] ?? ''));
            $isFinal = !empty($data['is_final']) ? 1 : 0;
            if ($stringKey === '') {
                $this->json(["error" => "String key is required"], 400);
                return;
            }
            if ($language === null || $language === 'en') {
                $this->json(["error" => "Invalid language code"], 400);
                return;
            }
            if ($translatedValue === '') {
                $this->json(["error" => "Translated text is required"], 400);
                return;
            }
            $this->assertLanguageExists($db, $language);
            $rows = $db->query("SELECT id, context, default_en FROM wa_ui_strings WHERE string_key = ? LIMIT 1", [$stringKey]);
            if ($rows === []) {
                $this->json(["error" => "Not Found"], 404);
                return;
            }
            $stringId = (int)$rows[0]['id'];
            $existing = $db->query(
                "SELECT id FROM wa_ui_translations WHERE ui_string_id = ? AND language_code = ? LIMIT 1",
                [$stringId, $language]
            );
            if ($existing === []) {
                $db->exec(
                    "INSERT INTO wa_ui_translations (ui_string_id, language_code, translated_value, is_final, updated_by_user_id, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)",
                    [$stringId, $language, $translatedValue, $isFinal, (int)$admin['id']]
                );
            } else {
                $db->exec(
                    "UPDATE wa_ui_translations
                     SET translated_value = ?, is_final = ?, updated_by_user_id = ?, updated_at = CURRENT_TIMESTAMP
                     WHERE id = ?",
                    [$translatedValue, $isFinal, (int)$admin['id'], (int)$existing[0]['id']]
                );
            }
            $this->logAudit($db, (int)$admin['id'], 'admin_localization_translation_save', [
                'string_key' => $stringKey,
                'language' => $language,
                'is_final' => $isFinal === 1,
            ]);
            $this->json([
                'ok' => true,
                'item' => [
                    'string_key' => $stringKey,
                    'context' => (string)($rows[0]['context'] ?? ''),
                    'default_en' => (string)($rows[0]['default_en'] ?? ''),
                    'language_code' => $language,
                    'translated_value' => $translatedValue,
                    'is_final' => $isFinal,
                    'translation_status' => $isFinal === 1 ? 'final' : 'draft',
                ],
            ]);
        } catch (\JsonException $e) {
            $this->json(["error" => "Invalid JSON"], 400);
        } catch (\RuntimeException $e) {
            $this->json(["error" => $e->getMessage()], $this->httpStatus($e->getCode(), 400));
        } catch (\Throwable $e) {
            $this->json(["error" => $e->getMessage()], 500);
        }
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
        return [$db, $user];
    }

    private function decodeJsonBody(): array
    {
        $body = file_get_contents('php://input');
        $data = json_decode($body ?: '', true, 512, JSON_THROW_ON_ERROR);
        return is_array($data) ? $data : [];
    }

    private function assertLanguageExists(Maria $db, string $language): void
    {
        $rows = $db->query("SELECT code FROM wa_languages WHERE code = ? LIMIT 1", [$language]);
        if ($rows === []) {
            throw new \RuntimeException('Language not found', 404);
        }
    }

    private function httpStatus($code, int $fallback): int
    {
        $status = is_int($code) ? $code : (is_numeric($code) ? (int)$code : 0);
        if ($status >= 100 && $status <= 599) {
            return $status;
        }
        return $fallback;
    }

    private function logAudit(Maria $db, int $actorId, string $action, array $details): void
    {
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $db->exec(
                "INSERT INTO wa_audit_log (actor_user_id, target_user_id, action, source, ip_address, user_agent, details)
                 VALUES (?, NULL, ?, 'web', ?, ?, ?)",
                [
                    $actorId,
                    $action,
                    $ip,
                    $agent,
                    json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]
            );
            AuditLogMetaCache::invalidateIfMissing($action, 'web');
        } catch (\Throwable $e) {
            // audit logging must not block localization admin actions
        }
    }

    private function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        $json = json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
        );
        if ($json === false) {
            http_response_code(500);
            echo '{"error":"JSON encode failed"}';
            return;
        }
        echo $json;
    }
}

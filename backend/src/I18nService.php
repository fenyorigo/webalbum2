<?php

declare(strict_types=1);

namespace WebAlbum;

use WebAlbum\Db\Maria;

final class I18nService
{
    public function resolveLanguage(Maria $db, ?array $user, ?string $requested = null): string
    {
        $requested = $this->normalizeLanguage($requested);
        if ($requested !== null) {
            return $requested;
        }
        $userId = (int)($user['id'] ?? 0);
        if ($userId > 0) {
            $rows = $db->query(
                "SELECT ui_language FROM wa_user_prefs WHERE user_id = ? LIMIT 1",
                [$userId]
            );
            $lang = $this->normalizeLanguage((string)($rows[0]['ui_language'] ?? ''));
            if ($lang !== null) {
                return $lang;
            }
        }
        return 'en';
    }

    public function buildBundle(Maria $db, string $language): array
    {
        $lang = $this->normalizeLanguage($language) ?? 'en';
        $rows = $db->query(
            "SELECT s.string_key, s.default_en, s.context,
                    t.translated_value
             FROM wa_ui_strings s
             LEFT JOIN wa_ui_translations t
               ON t.ui_string_id = s.id
              AND t.language_code = ?
             ORDER BY s.string_key ASC",
            [$lang]
        );

        $strings = [];
        foreach ($rows as $row) {
            $key = (string)($row['string_key'] ?? '');
            if ($key === '') {
                continue;
            }
            $translated = $row['translated_value'];
            $default = (string)($row['default_en'] ?? '');
            $strings[$key] = is_string($translated) && $translated !== '' ? $translated : $default;
        }

        return [
            'language' => $lang,
            'fallback_language' => 'en',
            'strings' => $strings,
        ];
    }

    public function supportedLanguages(Maria $db): array
    {
        $translationRows = $db->query(
            "SELECT language_code, COUNT(*) AS translated_count
             FROM wa_ui_translations
             GROUP BY language_code"
        );
        $counts = [];
        foreach ($translationRows as $row) {
            $code = $this->normalizeLanguage((string)($row['language_code'] ?? ''));
            if ($code !== null) {
                $counts[$code] = (int)($row['translated_count'] ?? 0);
            }
        }

        try {
            $rows = $db->query(
                "SELECT code, name_en, name_native, is_active, sort_order
                 FROM wa_languages
                 WHERE is_active = 1
                 ORDER BY sort_order ASC, code ASC"
            );
        } catch (\Throwable $e) {
            $rows = [];
        }
        $out = [];
        foreach ($rows as $row) {
            $code = $this->normalizeLanguage((string)($row['code'] ?? ''));
            if ($code === null) {
                continue;
            }
            $out[] = [
                'code' => $code,
                'name_en' => (string)($row['name_en'] ?? $code),
                'name_native' => (string)($row['name_native'] ?? $code),
                'translated_count' => (int)($counts[$code] ?? 0),
            ];
        }
        if ($out === []) {
            return [[
                'code' => 'en',
                'name_en' => 'English',
                'name_native' => 'English',
                'translated_count' => (int)($counts['en'] ?? 0),
            ]];
        }
        return $out;
    }

    public function normalizeLanguage(?string $value): ?string
    {
        $value = strtolower(trim((string)$value));
        if ($value === '') {
            return null;
        }
        if (!preg_match('/^[a-z]{2,12}(?:-[a-z0-9]{2,12})?$/', $value)) {
            return null;
        }
        return $value;
    }
}

INSERT INTO wa_ui_strings (string_key, default_en, context)
VALUES
  ('admin.localization', 'Localization', 'admin'),
  ('localization.description', 'Manage UI string translations and supported languages.', 'localization'),
  ('localization.language', 'Language', 'localization'),
  ('localization.context', 'Context', 'localization'),
  ('localization.status', 'Status', 'localization'),
  ('localization.search', 'Search', 'localization'),
  ('localization.search_placeholder', 'Key, English, translation', 'localization'),
  ('localization.all_contexts', 'All contexts', 'localization'),
  ('localization.add_language', 'Add language', 'localization'),
  ('localization.add_key', 'Add key', 'localization'),
  ('localization.key', 'Key', 'localization'),
  ('localization.english', 'English', 'localization'),
  ('localization.translation', 'Translation', 'localization'),
  ('localization.actions', 'Actions', 'localization'),
  ('localization.table_empty', 'No localization rows match the current filters.', 'localization'),
  ('localization.edit_translation', 'Edit translation', 'localization'),
  ('localization.default_english', 'Default English', 'localization'),
  ('localization.placeholder_note', 'Placeholder tokens like {name} must be preserved where needed.', 'localization'),
  ('localization.language_code', 'Language code', 'localization'),
  ('localization.language_name_en', 'English name', 'localization'),
  ('localization.language_name_native', 'Native name', 'localization'),
  ('localization.active', 'Active', 'localization'),
  ('localization.initial_translation', 'Initial translation', 'localization'),
  ('localization.languages_load_failed', 'Failed to load languages', 'localization'),
  ('localization.strings_load_failed', 'Failed to load localization strings', 'localization'),
  ('localization.save_translation_failed', 'Failed to save translation', 'localization'),
  ('localization.translation_saved', 'Translation saved', 'localization'),
  ('localization.save_language_failed', 'Failed to add language', 'localization'),
  ('localization.language_saved', 'Language added', 'localization'),
  ('localization.save_key_failed', 'Failed to add key', 'localization'),
  ('localization.key_saved', 'Key added', 'localization'),
  ('status.missing', 'Missing', 'status'),
  ('status.draft', 'Draft', 'status'),
  ('status.final', 'Final', 'status'),
  ('api.invalid_language_code', 'Invalid language code', 'api'),
  ('api.language_names_required', 'Language names are required', 'api'),
  ('api.language_exists', 'Language already exists', 'api'),
  ('api.invalid_string_key', 'Invalid string key', 'api'),
  ('api.default_english_required', 'Default English text is required', 'api'),
  ('api.string_key_exists', 'String key already exists', 'api'),
  ('api.string_key_required', 'String key is required', 'api'),
  ('api.translated_text_required', 'Translated text is required', 'api'),
  ('api.language_not_found', 'Language not found', 'api')
ON DUPLICATE KEY UPDATE
  default_en = VALUES(default_en),
  context = VALUES(context),
  updated_at = CURRENT_TIMESTAMP;

INSERT INTO wa_ui_translations (ui_string_id, language_code, translated_value, is_final)
SELECT s.id, 'hu', v.translated_value, 1
FROM wa_ui_strings s
JOIN (
  SELECT 'admin.localization' AS string_key, 'Lokalizáció' AS translated_value UNION ALL
  SELECT 'localization.description', 'A felületi szövegek fordításainak és a támogatott nyelveknek a kezelése.' UNION ALL
  SELECT 'localization.language', 'Nyelv' UNION ALL
  SELECT 'localization.context', 'Kontextus' UNION ALL
  SELECT 'localization.status', 'Állapot' UNION ALL
  SELECT 'localization.search', 'Keresés' UNION ALL
  SELECT 'localization.search_placeholder', 'Kulcs, angol szöveg, fordítás' UNION ALL
  SELECT 'localization.all_contexts', 'Minden kontextus' UNION ALL
  SELECT 'localization.add_language', 'Nyelv hozzáadása' UNION ALL
  SELECT 'localization.add_key', 'Kulcs hozzáadása' UNION ALL
  SELECT 'localization.key', 'Kulcs' UNION ALL
  SELECT 'localization.english', 'Angol' UNION ALL
  SELECT 'localization.translation', 'Fordítás' UNION ALL
  SELECT 'localization.actions', 'Műveletek' UNION ALL
  SELECT 'localization.table_empty', 'Nincs a szűrőknek megfelelő lokalizációs sor.' UNION ALL
  SELECT 'localization.edit_translation', 'Fordítás szerkesztése' UNION ALL
  SELECT 'localization.default_english', 'Alapértelmezett angol' UNION ALL
  SELECT 'localization.placeholder_note', 'A helyettesítő tokeneket, például a {name} mintát, szükség szerint meg kell őrizni.' UNION ALL
  SELECT 'localization.language_code', 'Nyelvkód' UNION ALL
  SELECT 'localization.language_name_en', 'Angol név' UNION ALL
  SELECT 'localization.language_name_native', 'Anyanyelvi név' UNION ALL
  SELECT 'localization.active', 'Aktív' UNION ALL
  SELECT 'localization.initial_translation', 'Kezdeti fordítás' UNION ALL
  SELECT 'localization.languages_load_failed', 'A nyelvek betöltése sikertelen' UNION ALL
  SELECT 'localization.strings_load_failed', 'A lokalizációs kulcsok betöltése sikertelen' UNION ALL
  SELECT 'localization.save_translation_failed', 'A fordítás mentése sikertelen' UNION ALL
  SELECT 'localization.translation_saved', 'A fordítás mentve' UNION ALL
  SELECT 'localization.save_language_failed', 'A nyelv hozzáadása sikertelen' UNION ALL
  SELECT 'localization.language_saved', 'A nyelv hozzáadva' UNION ALL
  SELECT 'localization.save_key_failed', 'A kulcs hozzáadása sikertelen' UNION ALL
  SELECT 'localization.key_saved', 'A kulcs hozzáadva' UNION ALL
  SELECT 'status.missing', 'Hiányzik' UNION ALL
  SELECT 'status.draft', 'Piszkozat' UNION ALL
  SELECT 'status.final', 'Végleges' UNION ALL
  SELECT 'api.invalid_language_code', 'Érvénytelen nyelvkód' UNION ALL
  SELECT 'api.language_names_required', 'A nyelv nevei kötelezők' UNION ALL
  SELECT 'api.language_exists', 'A nyelv már létezik' UNION ALL
  SELECT 'api.invalid_string_key', 'Érvénytelen kulcs' UNION ALL
  SELECT 'api.default_english_required', 'Az alapértelmezett angol szöveg kötelező' UNION ALL
  SELECT 'api.string_key_exists', 'A kulcs már létezik' UNION ALL
  SELECT 'api.string_key_required', 'A kulcs megadása kötelező' UNION ALL
  SELECT 'api.translated_text_required', 'A fordított szöveg kötelező' UNION ALL
  SELECT 'api.language_not_found', 'A nyelv nem található'
) v ON v.string_key = s.string_key
ON DUPLICATE KEY UPDATE
  translated_value = VALUES(translated_value),
  is_final = VALUES(is_final),
  updated_at = CURRENT_TIMESTAMP;

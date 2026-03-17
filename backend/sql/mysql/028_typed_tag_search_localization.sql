INSERT INTO wa_ui_strings (string_key, default_en, context, created_at, updated_at)
SELECT 'search.semantic_tag', 'Typed tag', 'search', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM wa_ui_strings WHERE string_key = 'search.semantic_tag');

INSERT INTO wa_ui_strings (string_key, default_en, context, created_at, updated_at)
SELECT 'search.semantic_tag_placeholder', 'Search typed tag...', 'search', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM wa_ui_strings WHERE string_key = 'search.semantic_tag_placeholder');

INSERT INTO wa_ui_strings (string_key, default_en, context, created_at, updated_at)
SELECT 'search.semantic_tag_selected', 'Selected typed tag: {name}', 'search', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM wa_ui_strings WHERE string_key = 'search.semantic_tag_selected');

INSERT INTO wa_ui_strings (string_key, default_en, context, created_at, updated_at)
SELECT 'search.semantic_tag_select_valid', 'Select a typed tag from the list', 'search', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM wa_ui_strings WHERE string_key = 'search.semantic_tag_select_valid');

INSERT INTO wa_ui_strings (string_key, default_en, context, created_at, updated_at)
SELECT 'search.semantic_tag_descendants', 'Include descendants', 'search', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM wa_ui_strings WHERE string_key = 'search.semantic_tag_descendants');

INSERT INTO wa_ui_translations (ui_string_id, language_code, translated_value, is_final, updated_by_user_id, created_at, updated_at)
SELECT s.id, 'hu', 'Típusos címke', 1, NULL, NOW(), NOW()
FROM wa_ui_strings s
WHERE s.string_key = 'search.semantic_tag'
  AND NOT EXISTS (
    SELECT 1 FROM wa_ui_translations t
    WHERE t.ui_string_id = s.id AND t.language_code = 'hu'
  );

INSERT INTO wa_ui_translations (ui_string_id, language_code, translated_value, is_final, updated_by_user_id, created_at, updated_at)
SELECT s.id, 'hu', 'Típusos címke keresése...', 1, NULL, NOW(), NOW()
FROM wa_ui_strings s
WHERE s.string_key = 'search.semantic_tag_placeholder'
  AND NOT EXISTS (
    SELECT 1 FROM wa_ui_translations t
    WHERE t.ui_string_id = s.id AND t.language_code = 'hu'
  );

INSERT INTO wa_ui_translations (ui_string_id, language_code, translated_value, is_final, updated_by_user_id, created_at, updated_at)
SELECT s.id, 'hu', 'Kiválasztott típusos címke: {name}', 1, NULL, NOW(), NOW()
FROM wa_ui_strings s
WHERE s.string_key = 'search.semantic_tag_selected'
  AND NOT EXISTS (
    SELECT 1 FROM wa_ui_translations t
    WHERE t.ui_string_id = s.id AND t.language_code = 'hu'
  );

INSERT INTO wa_ui_translations (ui_string_id, language_code, translated_value, is_final, updated_by_user_id, created_at, updated_at)
SELECT s.id, 'hu', 'Válassz egy típusos címkét a listából', 1, NULL, NOW(), NOW()
FROM wa_ui_strings s
WHERE s.string_key = 'search.semantic_tag_select_valid'
  AND NOT EXISTS (
    SELECT 1 FROM wa_ui_translations t
    WHERE t.ui_string_id = s.id AND t.language_code = 'hu'
  );

INSERT INTO wa_ui_translations (ui_string_id, language_code, translated_value, is_final, updated_by_user_id, created_at, updated_at)
SELECT s.id, 'hu', 'Leszármazottak bevonása', 1, NULL, NOW(), NOW()
FROM wa_ui_strings s
WHERE s.string_key = 'search.semantic_tag_descendants'
  AND NOT EXISTS (
    SELECT 1 FROM wa_ui_translations t
    WHERE t.ui_string_id = s.id AND t.language_code = 'hu'
  );

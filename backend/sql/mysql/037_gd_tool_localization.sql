INSERT IGNORE INTO wa_ui_strings (string_key, default_en, context) VALUES
  ('tools.gd_missing_warning', 'Image processing fallback may be unavailable: PHP GD extension not loaded', 'tools');

INSERT INTO wa_ui_translations (ui_string_id, language_code, translated_value, is_final, updated_by_user_id)
SELECT s.id, 'hu', t.translated_value, 1, NULL
FROM wa_ui_strings s
JOIN (
  SELECT 'tools.gd_missing_warning' AS string_key, 'A képfeldolgozási tartalékútvonal nem biztos, hogy elérhető: a PHP GD bővítmény nincs betöltve' AS translated_value
) AS t ON t.string_key = s.string_key
LEFT JOIN wa_ui_translations x
  ON x.ui_string_id = s.id AND x.language_code = 'hu'
WHERE x.id IS NULL;

INSERT IGNORE INTO wa_ui_strings (string_key, default_en, context) VALUES
  ('tools.python3_full_path', 'python3 full path', 'tools'),
  ('tools.python3_missing_warning', 'Media move/indexer operations disabled: python3 not found on server', 'tools');

INSERT INTO wa_ui_translations (ui_string_id, language_code, translated_value, is_final, updated_by_user_id)
SELECT s.id, 'hu', t.translated_value, 1, NULL
FROM wa_ui_strings s
JOIN (
  SELECT 'tools.python3_full_path' AS string_key, 'python3 teljes elérési útja' AS translated_value UNION ALL
  SELECT 'tools.python3_missing_warning', 'A médiaáthelyezés/indexer műveletek le vannak tiltva: a python3 nem található a szerveren'
) AS t ON t.string_key = s.string_key
LEFT JOIN wa_ui_translations x
  ON x.ui_string_id = s.id AND x.language_code = 'hu'
WHERE x.id IS NULL;

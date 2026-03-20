INSERT IGNORE INTO wa_ui_strings (string_key, default_en, context) VALUES
  ('tools.python3_darwin_warning', 'On macOS, python3 alone is not sufficient. If indexer2 runs in a virtual environment, WA_INDEXER2_PYTHON must point to that environment''s python.', 'tools');

INSERT INTO wa_ui_translations (ui_string_id, language_code, translated_value, is_final, updated_by_user_id)
SELECT s.id, 'hu', t.translated_value, 1, NULL
FROM wa_ui_strings s
JOIN (
  SELECT 'tools.python3_darwin_warning' AS string_key, 'macOS esetén a python3 megléte önmagában nem elegendő. Ha az indexer2 virtuális környezetet használ, a WA_INDEXER2_PYTHON változónak annak a környezetnek a python-jára kell mutatnia.' AS translated_value
) AS t ON t.string_key = s.string_key
LEFT JOIN wa_ui_translations x
  ON x.ui_string_id = s.id AND x.language_code = 'hu'
WHERE x.id IS NULL;

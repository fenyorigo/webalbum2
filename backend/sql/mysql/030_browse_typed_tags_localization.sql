INSERT IGNORE INTO wa_ui_strings (string_key, default_en, context) VALUES
  ('nav.typed_tags', 'Typed Tags', 'nav'),
  ('browse_tags.title', 'Typed Tags', 'browse_tags'),
  ('browse_tags.description', 'Browse categories and events, then open search from the selected tag.', 'browse_tags'),
  ('browse_tags.search', 'Search tags', 'browse_tags'),
  ('browse_tags.search_placeholder', 'Find categories and events...', 'browse_tags'),
  ('browse_tags.empty', 'No typed tags available.', 'browse_tags'),
  ('browse_tags.load_failed', 'Failed to load typed tags', 'browse_tags'),
  ('browse_tags.nodes', 'nodes', 'browse_tags');

INSERT INTO wa_ui_translations (ui_string_id, language_code, translated_value, is_final, updated_by_user_id)
SELECT s.id, 'hu', t.translated_value, 1, NULL
FROM wa_ui_strings s
JOIN (
  SELECT 'nav.typed_tags' AS string_key, 'Típusos címkék' AS translated_value UNION ALL
  SELECT 'browse_tags.title', 'Típusos címkék' UNION ALL
  SELECT 'browse_tags.description', 'Böngészd a kategóriákat és eseményeket, majd indíts keresést a kiválasztott címkéből.' UNION ALL
  SELECT 'browse_tags.search', 'Címkék keresése' UNION ALL
  SELECT 'browse_tags.search_placeholder', 'Kategóriák és események keresése...' UNION ALL
  SELECT 'browse_tags.empty', 'Nem érhető el típusos címke.' UNION ALL
  SELECT 'browse_tags.load_failed', 'A típusos címkék betöltése sikertelen' UNION ALL
  SELECT 'browse_tags.nodes', 'csomópont'
) AS t ON t.string_key = s.string_key
LEFT JOIN wa_ui_translations x
  ON x.ui_string_id = s.id AND x.language_code = 'hu'
WHERE x.id IS NULL;

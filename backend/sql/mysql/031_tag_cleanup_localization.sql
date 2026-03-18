INSERT IGNORE INTO wa_ui_strings (string_key, default_en, context) VALUES
  ('admin.cleanup_tags', 'Cleanup Tags', 'admin'),
  ('cleanup_tags.description', 'Review generic, root, and unused typed tags that still need structural cleanup.', 'cleanup_tags'),
  ('cleanup_tags.search', 'Search tags', 'cleanup_tags'),
  ('cleanup_tags.search_placeholder', 'Find typed tags...', 'cleanup_tags'),
  ('cleanup_tags.generic_only', 'Generic tags', 'cleanup_tags'),
  ('cleanup_tags.root_only', 'Root tags', 'cleanup_tags'),
  ('cleanup_tags.unused_only', 'Unused tags', 'cleanup_tags'),
  ('cleanup_tags.matching_tags', 'matching tags', 'cleanup_tags'),
  ('cleanup_tags.children', 'Children', 'cleanup_tags'),
  ('cleanup_tags.empty', 'No matching tags.', 'cleanup_tags'),
  ('cleanup_tags.load_failed', 'Failed to load cleanup tags', 'cleanup_tags'),
  ('cleanup_tags.saved', 'Tag updated.', 'cleanup_tags'),
  ('cleanup_tags.deleted', 'Tag deleted.', 'cleanup_tags'),
  ('cleanup_tags.delete_failed', 'Failed to delete tag', 'cleanup_tags');

INSERT INTO wa_ui_translations (ui_string_id, language_code, translated_value, is_final, updated_by_user_id)
SELECT s.id, 'hu', t.translated_value, 1, NULL
FROM wa_ui_strings s
JOIN (
  SELECT 'admin.cleanup_tags' AS string_key, 'Címkék rendbetétele' AS translated_value UNION ALL
  SELECT 'cleanup_tags.description', 'Tekintsd át az általános, gyökér és nem használt típusos címkéket, amelyek még strukturális rendezést igényelnek.' UNION ALL
  SELECT 'cleanup_tags.search', 'Címkék keresése' UNION ALL
  SELECT 'cleanup_tags.search_placeholder', 'Típusos címkék keresése...' UNION ALL
  SELECT 'cleanup_tags.generic_only', 'Általános címkék' UNION ALL
  SELECT 'cleanup_tags.root_only', 'Gyökér címkék' UNION ALL
  SELECT 'cleanup_tags.unused_only', 'Nem használt címkék' UNION ALL
  SELECT 'cleanup_tags.matching_tags', 'egyező címke' UNION ALL
  SELECT 'cleanup_tags.children', 'Gyermekek' UNION ALL
  SELECT 'cleanup_tags.empty', 'Nincs egyező címke.' UNION ALL
  SELECT 'cleanup_tags.load_failed', 'A rendbetételi címkék betöltése sikertelen' UNION ALL
  SELECT 'cleanup_tags.saved', 'A címke frissült.' UNION ALL
  SELECT 'cleanup_tags.deleted', 'A címke törölve.' UNION ALL
  SELECT 'cleanup_tags.delete_failed', 'A címke törlése sikertelen'
) AS t ON t.string_key = s.string_key
LEFT JOIN wa_ui_translations x
  ON x.ui_string_id = s.id AND x.language_code = 'hu'
WHERE x.id IS NULL;

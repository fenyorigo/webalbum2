INSERT IGNORE INTO wa_ui_strings (string_key, default_en, context) VALUES
  ('search.filter.remove', 'Remove filter', 'search'),
  ('search.filter.typed_generic', 'Typed tag', 'search'),
  ('search.filter.typed_event', 'Event', 'search'),
  ('search.filter.typed_category', 'Category', 'search'),
  ('search.filter.typed_person', 'Person', 'search'),
  ('search.filter.tag', 'Tag', 'search'),
  ('search.filter.tag_exclude', 'Exclude tag', 'search'),
  ('search.filter.folder', 'Folder', 'search'),
  ('search.filter.path', 'Path', 'search'),
  ('search.filter.type', 'Type', 'search'),
  ('search.filter.extension', 'Extension', 'search'),
  ('search.filter.favorites', 'Favorites', 'search'),
  ('search.filter.has_notes', 'Has notes', 'search'),
  ('search.filter.media_ids', 'Media IDs', 'search'),
  ('search.filter.taken_after', 'Taken after', 'search'),
  ('search.filter.taken_before', 'Taken before', 'search'),
  ('search.filter.taken_between', 'Taken', 'search'),
  ('search.filter.enabled', 'On', 'search');

INSERT INTO wa_ui_translations (ui_string_id, language_code, translated_value, is_final, updated_by_user_id)
SELECT s.id, 'hu', t.translated_value, 1, NULL
FROM wa_ui_strings s
JOIN (
  SELECT 'search.filter.remove' AS string_key, 'Szűrő eltávolítása' AS translated_value UNION ALL
  SELECT 'search.filter.typed_generic', 'Típusos címke' UNION ALL
  SELECT 'search.filter.typed_event', 'Esemény' UNION ALL
  SELECT 'search.filter.typed_category', 'Kategória' UNION ALL
  SELECT 'search.filter.typed_person', 'Személy' UNION ALL
  SELECT 'search.filter.tag', 'Címke' UNION ALL
  SELECT 'search.filter.tag_exclude', 'Kizárt címke' UNION ALL
  SELECT 'search.filter.folder', 'Mappa' UNION ALL
  SELECT 'search.filter.path', 'Útvonal' UNION ALL
  SELECT 'search.filter.type', 'Típus' UNION ALL
  SELECT 'search.filter.extension', 'Kiterjesztés' UNION ALL
  SELECT 'search.filter.favorites', 'Kedvencek' UNION ALL
  SELECT 'search.filter.has_notes', 'Van megjegyzés' UNION ALL
  SELECT 'search.filter.media_ids', 'Média azonosítók' UNION ALL
  SELECT 'search.filter.taken_after', 'Készült ezután' UNION ALL
  SELECT 'search.filter.taken_before', 'Készült ezelőtt' UNION ALL
  SELECT 'search.filter.taken_between', 'Készült' UNION ALL
  SELECT 'search.filter.enabled', 'Be'
) AS t ON t.string_key = s.string_key
LEFT JOIN wa_ui_translations x
  ON x.ui_string_id = s.id AND x.language_code = 'hu'
WHERE x.id IS NULL;

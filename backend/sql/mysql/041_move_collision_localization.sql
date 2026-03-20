INSERT IGNORE INTO wa_ui_strings (string_key, default_en, context) VALUES
  ('move.completed_renamed', 'Media moved and renamed to avoid a name conflict', 'move'),
  ('asset_move.completed_renamed', 'Asset moved and renamed to avoid a name conflict', 'asset_move'),
  ('asset_move.completed_renamed_with_warnings', 'Asset moved and renamed to avoid a name conflict, but derivative cleanup had warnings', 'asset_move'),
  ('api.move_collision_resolution_failed', 'Could not resolve collision-free target filename', 'api');

INSERT INTO wa_ui_translations (ui_string_id, language_code, translated_value, is_final, updated_by_user_id)
SELECT s.id, 'hu', t.translated_value, 1, NULL
FROM wa_ui_strings s
JOIN (
  SELECT 'move.completed_renamed' AS string_key, 'A média áthelyezése sikerült, és névütközés miatt át lett nevezve' AS translated_value UNION ALL
  SELECT 'asset_move.completed_renamed', 'Az asset áthelyezése sikerült, és névütközés miatt át lett nevezve' UNION ALL
  SELECT 'asset_move.completed_renamed_with_warnings', 'Az asset áthelyezése sikerült, és névütközés miatt át lett nevezve, de a származtatott fájlok takarításánál figyelmeztetés keletkezett' UNION ALL
  SELECT 'api.move_collision_resolution_failed', 'Nem sikerült ütközésmentes célfájlnevet meghatározni'
) AS t ON t.string_key = s.string_key
LEFT JOIN wa_ui_translations x
  ON x.ui_string_id = s.id AND x.language_code = 'hu'
WHERE x.id IS NULL;

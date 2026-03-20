INSERT IGNORE INTO wa_ui_strings (string_key, default_en, context) VALUES
  ('viewer.move_media', 'Move', 'viewer'),
  ('move.title', 'Move media', 'move'),
  ('move.current_path', 'Current path', 'move'),
  ('move.selected_destination', 'Destination folder', 'move'),
  ('move.destination_hint', 'Choose a destination folder from the tree.', 'move'),
  ('move.destination_none', 'No destination selected', 'move'),
  ('move.confirm', 'Confirm move', 'move'),
  ('move.confirm_message', 'Move "{name}" to "{destination}"?', 'move'),
  ('move.success', 'Media moved successfully', 'move'),
  ('move.failed', 'Failed to move media', 'move'),
  ('move.select_destination_required', 'Select a destination folder', 'move'),
  ('move.same_folder', 'The selected folder is the current folder', 'move'),
  ('move.in_progress', 'Moving media...', 'move'),
  ('api.invalid_destination_folder', 'Invalid destination folder', 'api'),
  ('api.move_same_folder', 'Source and destination folders are the same', 'api'),
  ('api.destination_file_exists', 'Destination already contains a file with this name', 'api'),
  ('api.disk_move_failed', 'Failed to move file on disk', 'api'),
  ('api.indexer_move_failed_restored', 'Indexer move failed and original file was restored', 'api'),
  ('api.indexer_move_failed_rollback_failed', 'Indexer move failed and rollback failed', 'api'),
  ('api.only_media_files_supported', 'Only image/video media files are supported', 'api');

INSERT INTO wa_ui_translations (ui_string_id, language_code, translated_value, is_final, updated_by_user_id)
SELECT s.id, 'hu', t.translated_value, 1, NULL
FROM wa_ui_strings s
JOIN (
  SELECT 'viewer.move_media' AS string_key, 'Áthelyezés' AS translated_value UNION ALL
  SELECT 'move.title', 'Média áthelyezése' UNION ALL
  SELECT 'move.current_path', 'Jelenlegi útvonal' UNION ALL
  SELECT 'move.selected_destination', 'Célmappa' UNION ALL
  SELECT 'move.destination_hint', 'Válassz célmappát a fa nézetből.' UNION ALL
  SELECT 'move.destination_none', 'Nincs célmappa kiválasztva' UNION ALL
  SELECT 'move.confirm', 'Áthelyezés megerősítése' UNION ALL
  SELECT 'move.confirm_message', 'Áthelyezed a(z) "{name}" fájlt ide: "{destination}"?' UNION ALL
  SELECT 'move.success', 'A média áthelyezése sikerült' UNION ALL
  SELECT 'move.failed', 'A média áthelyezése sikertelen' UNION ALL
  SELECT 'move.select_destination_required', 'Válassz célmappát' UNION ALL
  SELECT 'move.same_folder', 'A kiválasztott mappa megegyezik a jelenlegi mappával' UNION ALL
  SELECT 'move.in_progress', 'Média áthelyezése...' UNION ALL
  SELECT 'api.invalid_destination_folder', 'Érvénytelen célmappa' UNION ALL
  SELECT 'api.move_same_folder', 'A forrás- és célmappa megegyezik' UNION ALL
  SELECT 'api.destination_file_exists', 'A célhelyen már létezik ilyen nevű fájl' UNION ALL
  SELECT 'api.disk_move_failed', 'A fájl lemezen történő áthelyezése sikertelen' UNION ALL
  SELECT 'api.indexer_move_failed_restored', 'Az indexer frissítése sikertelen volt, az eredeti fájl vissza lett állítva' UNION ALL
  SELECT 'api.indexer_move_failed_rollback_failed', 'Az indexer frissítése és a visszaállítás is sikertelen volt' UNION ALL
  SELECT 'api.only_media_files_supported', 'Csak kép- és videófájlok támogatottak'
) AS t ON t.string_key = s.string_key
LEFT JOIN wa_ui_translations x
  ON x.ui_string_id = s.id AND x.language_code = 'hu'
WHERE x.id IS NULL;

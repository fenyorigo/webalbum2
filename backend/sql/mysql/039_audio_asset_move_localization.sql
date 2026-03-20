INSERT IGNORE INTO wa_ui_strings (string_key, default_en, context) VALUES
  ('asset_move.action', 'Move', 'asset_move'),
  ('asset_move.title', 'Move audio asset', 'asset_move'),
  ('asset_move.current_path', 'Current path', 'asset_move'),
  ('asset_move.selected_destination', 'Destination folder', 'asset_move'),
  ('asset_move.destination_hint', 'Choose a destination folder from the tree.', 'asset_move'),
  ('asset_move.destination_none', 'No destination selected', 'asset_move'),
  ('asset_move.confirm', 'Confirm move', 'asset_move'),
  ('asset_move.confirm_message', 'Move "{name}" to "{destination}"?', 'asset_move'),
  ('asset_move.success', 'Audio asset moved successfully', 'asset_move'),
  ('asset_move.failed', 'Failed to move audio asset', 'asset_move'),
  ('asset_move.select_destination_required', 'Select a destination folder', 'asset_move'),
  ('asset_move.same_folder', 'The selected folder is the current folder', 'asset_move'),
  ('asset_move.in_progress', 'Moving audio asset...', 'asset_move'),
  ('api.only_audio_assets_supported', 'Only audio assets are supported', 'api'),
  ('api.asset_move_blocked_running_job', 'Asset move blocked: a running asset job exists', 'api'),
  ('api.asset_move_db_sync_failed_restored', 'Asset move DB sync failed and original file was restored', 'api'),
  ('api.asset_move_db_sync_failed_rollback_failed', 'Asset move DB sync failed and rollback failed', 'api');

INSERT INTO wa_ui_translations (ui_string_id, language_code, translated_value, is_final, updated_by_user_id)
SELECT s.id, 'hu', t.translated_value, 1, NULL
FROM wa_ui_strings s
JOIN (
  SELECT 'asset_move.action' AS string_key, 'Áthelyezés' AS translated_value UNION ALL
  SELECT 'asset_move.title', 'Audió asset áthelyezése' UNION ALL
  SELECT 'asset_move.current_path', 'Jelenlegi útvonal' UNION ALL
  SELECT 'asset_move.selected_destination', 'Célmappa' UNION ALL
  SELECT 'asset_move.destination_hint', 'Válassz célmappát a fa nézetből.' UNION ALL
  SELECT 'asset_move.destination_none', 'Nincs célmappa kiválasztva' UNION ALL
  SELECT 'asset_move.confirm', 'Áthelyezés megerősítése' UNION ALL
  SELECT 'asset_move.confirm_message', 'Áthelyezed a(z) "{name}" fájlt ide: "{destination}"?' UNION ALL
  SELECT 'asset_move.success', 'Az audió asset áthelyezése sikerült' UNION ALL
  SELECT 'asset_move.failed', 'Az audió asset áthelyezése sikertelen' UNION ALL
  SELECT 'asset_move.select_destination_required', 'Válassz célmappát' UNION ALL
  SELECT 'asset_move.same_folder', 'A kiválasztott mappa megegyezik a jelenlegi mappával' UNION ALL
  SELECT 'asset_move.in_progress', 'Audió asset áthelyezése...' UNION ALL
  SELECT 'api.only_audio_assets_supported', 'Csak audió assetek támogatottak' UNION ALL
  SELECT 'api.asset_move_blocked_running_job', 'Az asset áthelyezése blokkolva: futó asset feladat tartozik hozzá' UNION ALL
  SELECT 'api.asset_move_db_sync_failed_restored', 'Az asset MariaDB szinkron sikertelen volt, az eredeti fájl vissza lett állítva' UNION ALL
  SELECT 'api.asset_move_db_sync_failed_rollback_failed', 'Az asset MariaDB szinkron és a visszaállítás is sikertelen volt'
) AS t ON t.string_key = s.string_key
LEFT JOIN wa_ui_translations x
  ON x.ui_string_id = s.id AND x.language_code = 'hu'
WHERE x.id IS NULL;

INSERT IGNORE INTO wa_ui_strings (string_key, default_en, context) VALUES
  ('viewer.undo_move', 'Undo move', 'viewer'),
  ('move.undo_confirm_message', 'Move this media back from {current} toward {destination}?', 'move'),
  ('move.undo_completed', 'Media move undone successfully', 'move'),
  ('move.undo_completed_renamed', 'Media moved back, but the original filename was occupied so it was renamed', 'move'),
  ('asset_move.undo_action', 'Undo move', 'asset_move'),
  ('asset_move.undo_confirm_message', 'Move this asset back from {current} toward {destination}?', 'asset_move'),
  ('asset_move.undo_completed', 'Asset move undone successfully', 'asset_move'),
  ('asset_move.undo_completed_renamed', 'Asset moved back, but the original filename was occupied so it was renamed', 'asset_move'),
  ('undo.failed', 'Undo failed', 'undo'),
  ('api.undo_not_available_no_history', 'Undo not available: no move history', 'api'),
  ('api.undo_not_available_newer_move_exists', 'Undo not available: newer move exists', 'api'),
  ('api.undo_not_available_current_path_mismatch', 'Undo not available: current path mismatch', 'api'),
  ('api.undo_not_available_item_missing', 'Undo not available: item missing', 'api'),
  ('api.undo_not_available_original_destination_missing', 'Undo not available: original destination missing', 'api'),
  ('api.undo_not_available_blocked_active_work', 'Undo not available: blocked by active work', 'api'),
  ('api.undo_failed', 'Undo failed', 'api');

INSERT INTO wa_ui_translations (ui_string_id, language_code, translated_value, is_final, updated_by_user_id)
SELECT s.id, 'hu', t.translated_value, 1, NULL
FROM wa_ui_strings s
JOIN (
  SELECT 'viewer.undo_move' AS string_key, 'Áthelyezés visszavonása' AS translated_value UNION ALL
  SELECT 'move.undo_confirm_message', 'Visszahelyezi ezt a médiát innen: {current}, ide: {destination}?' UNION ALL
  SELECT 'move.undo_completed', 'A média visszahelyezése sikerült' UNION ALL
  SELECT 'move.undo_completed_renamed', 'A média visszahelyezése sikerült, de az eredeti fájlnév foglalt volt, ezért át lett nevezve' UNION ALL
  SELECT 'asset_move.undo_action', 'Áthelyezés visszavonása' UNION ALL
  SELECT 'asset_move.undo_confirm_message', 'Visszahelyezi ezt az assetet innen: {current}, ide: {destination}?' UNION ALL
  SELECT 'asset_move.undo_completed', 'Az asset visszahelyezése sikerült' UNION ALL
  SELECT 'asset_move.undo_completed_renamed', 'Az asset visszahelyezése sikerült, de az eredeti fájlnév foglalt volt, ezért át lett nevezve' UNION ALL
  SELECT 'undo.failed', 'Az áthelyezés visszavonása nem sikerült' UNION ALL
  SELECT 'api.undo_not_available_no_history', 'A visszavonás nem érhető el: nincs áthelyezési előzmény' UNION ALL
  SELECT 'api.undo_not_available_newer_move_exists', 'A visszavonás nem érhető el: újabb áthelyezés történt' UNION ALL
  SELECT 'api.undo_not_available_current_path_mismatch', 'A visszavonás nem érhető el: a jelenlegi útvonal nem egyezik' UNION ALL
  SELECT 'api.undo_not_available_item_missing', 'A visszavonás nem érhető el: az elem nem található' UNION ALL
  SELECT 'api.undo_not_available_original_destination_missing', 'A visszavonás nem érhető el: az eredeti célmappa nem található' UNION ALL
  SELECT 'api.undo_not_available_blocked_active_work', 'A visszavonás nem érhető el: aktív művelet blokkolja' UNION ALL
  SELECT 'api.undo_failed', 'Az áthelyezés visszavonása nem sikerült'
) AS t ON t.string_key = s.string_key
LEFT JOIN wa_ui_translations x
  ON x.ui_string_id = s.id AND x.language_code = 'hu'
WHERE x.id IS NULL;

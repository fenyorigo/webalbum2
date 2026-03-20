INSERT IGNORE INTO wa_ui_strings (string_key, default_en, context) VALUES
  ('batch_move.title', 'Batch move', 'batch_move'),
  ('batch_move.move_selected', 'Move selected', 'batch_move'),
  ('batch_move.move_all', 'Move all results', 'batch_move'),
  ('batch_move.scope_label', 'Scope: {scope}', 'batch_move'),
  ('batch_move.scope_selected', 'Selected items', 'batch_move'),
  ('batch_move.scope_all_results', 'All search results', 'batch_move'),
  ('batch_move.item_count', 'Items: {count}', 'batch_move'),
  ('batch_move.selected_destination', 'Selected destination', 'batch_move'),
  ('batch_move.destination_none', 'No folder selected', 'batch_move'),
  ('batch_move.conflict_note', 'Name conflicts are resolved automatically with numeric suffixes.', 'batch_move'),
  ('batch_move.confirm', 'Move now', 'batch_move'),
  ('batch_move.confirm_message', 'Move {count} item(s) to {destination}?', 'batch_move'),
  ('batch_move.in_progress', 'Moving...', 'batch_move'),
  ('batch_move.completed', 'Batch move completed: {moved} items moved.', 'batch_move'),
  ('batch_move.partial', 'Batch move finished with partial success: moved {moved}, blocked {blocked}, failed {failed}.', 'batch_move'),
  ('batch_move.failed', 'Batch move failed', 'batch_move'),
  ('batch_move.summary_title', 'Batch move summary', 'batch_move'),
  ('batch_move.summary.moved', 'Moved: {count}', 'batch_move'),
  ('batch_move.summary.renamed', 'Renamed: {count}', 'batch_move'),
  ('batch_move.summary.blocked', 'Blocked: {count}', 'batch_move'),
  ('batch_move.summary.failed', 'Failed: {count}', 'batch_move'),
  ('batch_move.status.moved', 'Moved', 'batch_move'),
  ('batch_move.status.renamed', 'Moved with rename', 'batch_move'),
  ('batch_move.status.blocked', 'Blocked', 'batch_move'),
  ('batch_move.status.failed', 'Failed', 'batch_move'),
  ('batch_move.select_items_first', 'Select items first', 'batch_move'),
  ('batch_move.select_or_search_first', 'Select items or run a search first', 'batch_move'),
  ('batch_move.no_items', 'No items to move', 'batch_move'),
  ('batch_move.resolve_failed', 'Failed to resolve batch move scope', 'batch_move'),
  ('batch_move.limit_exceeded', 'Batch move is limited to {count} items per run', 'batch_move'),
  ('api.ids_required', 'ids are required', 'api'),
  ('api.batch_move_too_many_items', 'Too many items. Move in batches of up to 500', 'api'),
  ('api.batch_move_unsupported_item_id', 'Unsupported move item id', 'api');

INSERT INTO wa_ui_translations (ui_string_id, language_code, translated_value, is_final, updated_by_user_id)
SELECT s.id, 'hu', t.translated_value, 1, NULL
FROM wa_ui_strings s
JOIN (
  SELECT 'batch_move.title' AS string_key, 'Csoportos áthelyezés' AS translated_value UNION ALL
  SELECT 'batch_move.move_selected', 'Kijelöltek áthelyezése' UNION ALL
  SELECT 'batch_move.move_all', 'Összes találat áthelyezése' UNION ALL
  SELECT 'batch_move.scope_label', 'Hatókör: {scope}' UNION ALL
  SELECT 'batch_move.scope_selected', 'Kijelölt elemek' UNION ALL
  SELECT 'batch_move.scope_all_results', 'Összes találat' UNION ALL
  SELECT 'batch_move.item_count', 'Elemek: {count}' UNION ALL
  SELECT 'batch_move.selected_destination', 'Kiválasztott célmappa' UNION ALL
  SELECT 'batch_move.destination_none', 'Nincs kiválasztott mappa' UNION ALL
  SELECT 'batch_move.conflict_note', 'A névütközéseket a rendszer automatikusan numerikus utótaggal oldja fel.' UNION ALL
  SELECT 'batch_move.confirm', 'Áthelyezés most' UNION ALL
  SELECT 'batch_move.confirm_message', '{count} elem áthelyezése ide: {destination}?' UNION ALL
  SELECT 'batch_move.in_progress', 'Áthelyezés...' UNION ALL
  SELECT 'batch_move.completed', 'A csoportos áthelyezés befejeződött: {moved} elem áthelyezve.' UNION ALL
  SELECT 'batch_move.partial', 'A csoportos áthelyezés részben sikerült: áthelyezve {moved}, blokkolva {blocked}, sikertelen {failed}.' UNION ALL
  SELECT 'batch_move.failed', 'A csoportos áthelyezés sikertelen' UNION ALL
  SELECT 'batch_move.summary_title', 'Csoportos áthelyezés összesítő' UNION ALL
  SELECT 'batch_move.summary.moved', 'Áthelyezve: {count}' UNION ALL
  SELECT 'batch_move.summary.renamed', 'Átnevezve: {count}' UNION ALL
  SELECT 'batch_move.summary.blocked', 'Blokkolva: {count}' UNION ALL
  SELECT 'batch_move.summary.failed', 'Sikertelen: {count}' UNION ALL
  SELECT 'batch_move.status.moved', 'Áthelyezve' UNION ALL
  SELECT 'batch_move.status.renamed', 'Áthelyezve átnevezéssel' UNION ALL
  SELECT 'batch_move.status.blocked', 'Blokkolva' UNION ALL
  SELECT 'batch_move.status.failed', 'Sikertelen' UNION ALL
  SELECT 'batch_move.select_items_first', 'Előbb válassz ki elemeket' UNION ALL
  SELECT 'batch_move.select_or_search_first', 'Előbb válassz elemeket vagy futtass keresést' UNION ALL
  SELECT 'batch_move.no_items', 'Nincs áthelyezhető elem' UNION ALL
  SELECT 'batch_move.resolve_failed', 'Nem sikerült feloldani a csoportos áthelyezés hatókörét' UNION ALL
  SELECT 'batch_move.limit_exceeded', 'Egy futásban legfeljebb {count} elem helyezhető át' UNION ALL
  SELECT 'api.ids_required', 'Az ids megadása kötelező' UNION ALL
  SELECT 'api.batch_move_too_many_items', 'Túl sok elem. Egy futásban legfeljebb 500 elem helyezhető át' UNION ALL
  SELECT 'api.batch_move_unsupported_item_id', 'Nem támogatott batch move elemazonosító'
) AS t ON t.string_key = s.string_key
LEFT JOIN wa_ui_translations x
  ON x.ui_string_id = s.id AND x.language_code = 'hu'
WHERE x.id IS NULL;

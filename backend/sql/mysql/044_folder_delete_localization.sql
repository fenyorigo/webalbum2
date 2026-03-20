INSERT IGNORE INTO wa_ui_strings (string_key, default_en, context) VALUES
  ('folders.delete', 'Delete folder', 'folders'),
  ('folders.delete.confirm', 'Delete folder "{path}"?', 'folders'),
  ('folders.delete.success', 'Folder deleted', 'folders'),
  ('folders.delete.failed', 'Failed to delete folder', 'folders'),
  ('folders.delete.in_progress', 'Deleting...', 'folders'),
  ('api.folder_not_found', 'Folder not found', 'api'),
  ('api.folder_not_empty', 'Folder is not empty', 'api'),
  ('api.folder_delete_failed', 'Failed to delete folder', 'api');

INSERT INTO wa_ui_translations (ui_string_id, language_code, translated_value, is_final, updated_by_user_id)
SELECT s.id, 'hu', t.translated_value, 1, NULL
FROM wa_ui_strings s
JOIN (
  SELECT 'folders.delete' AS string_key, 'Mappa törlése' AS translated_value UNION ALL
  SELECT 'folders.delete.confirm', 'Töröljük a(z) "{path}" mappát?' UNION ALL
  SELECT 'folders.delete.success', 'A mappa törölve' UNION ALL
  SELECT 'folders.delete.failed', 'A mappa törlése sikertelen' UNION ALL
  SELECT 'folders.delete.in_progress', 'Törlés...' UNION ALL
  SELECT 'api.folder_not_found', 'A mappa nem található' UNION ALL
  SELECT 'api.folder_not_empty', 'A mappa nem üres' UNION ALL
  SELECT 'api.folder_delete_failed', 'A mappa törlése sikertelen'
) AS t ON t.string_key = s.string_key
LEFT JOIN wa_ui_translations x
  ON x.ui_string_id = s.id AND x.language_code = 'hu'
WHERE x.id IS NULL;

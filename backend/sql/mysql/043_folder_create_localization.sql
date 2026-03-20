INSERT IGNORE INTO wa_ui_strings (string_key, default_en, context) VALUES
  ('folders.create', 'Create folder', 'folders'),
  ('folders.create.cancel', 'Cancel', 'folders'),
  ('folders.create.parent', 'Create under: {path}', 'folders'),
  ('folders.create.name', 'Folder name', 'folders'),
  ('folders.create.placeholder', 'New folder name', 'folders'),
  ('folders.create.confirm', 'Create', 'folders'),
  ('folders.create.in_progress', 'Creating...', 'folders'),
  ('folders.create.failed', 'Failed to create folder', 'folders'),
  ('folders.create.invalid_name', 'Invalid folder name', 'folders'),
  ('folders.create.select_parent_first', 'Select a parent folder first', 'folders'),
  ('folders.create.success', 'Folder created', 'folders'),
  ('api.invalid_parent_folder', 'Invalid parent folder', 'api'),
  ('api.invalid_folder_name', 'Invalid folder name', 'api'),
  ('api.invalid_folder_path', 'Invalid folder path', 'api'),
  ('api.folder_already_exists', 'Folder already exists', 'api'),
  ('api.folder_create_failed', 'Failed to create folder', 'api');

INSERT INTO wa_ui_translations (ui_string_id, language_code, translated_value, is_final, updated_by_user_id)
SELECT s.id, 'hu', t.translated_value, 1, NULL
FROM wa_ui_strings s
JOIN (
  SELECT 'folders.create' AS string_key, 'Mappa létrehozása' AS translated_value UNION ALL
  SELECT 'folders.create.cancel', 'Mégse' UNION ALL
  SELECT 'folders.create.parent', 'Létrehozás itt: {path}' UNION ALL
  SELECT 'folders.create.name', 'Mappanév' UNION ALL
  SELECT 'folders.create.placeholder', 'Új mappa neve' UNION ALL
  SELECT 'folders.create.confirm', 'Létrehozás' UNION ALL
  SELECT 'folders.create.in_progress', 'Létrehozás...' UNION ALL
  SELECT 'folders.create.failed', 'A mappa létrehozása sikertelen' UNION ALL
  SELECT 'folders.create.invalid_name', 'Érvénytelen mappanév' UNION ALL
  SELECT 'folders.create.select_parent_first', 'Előbb válassz ki egy szülőmappát' UNION ALL
  SELECT 'folders.create.success', 'A mappa létrejött' UNION ALL
  SELECT 'api.invalid_parent_folder', 'Érvénytelen szülőmappa' UNION ALL
  SELECT 'api.invalid_folder_name', 'Érvénytelen mappanév' UNION ALL
  SELECT 'api.invalid_folder_path', 'Érvénytelen mappaútvonal' UNION ALL
  SELECT 'api.folder_already_exists', 'A mappa már létezik' UNION ALL
  SELECT 'api.folder_create_failed', 'A mappa létrehozása sikertelen'
) AS t ON t.string_key = s.string_key
LEFT JOIN wa_ui_translations x
  ON x.ui_string_id = s.id AND x.language_code = 'hu'
WHERE x.id IS NULL;

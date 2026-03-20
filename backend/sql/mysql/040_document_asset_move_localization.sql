UPDATE wa_ui_strings
SET default_en = CASE string_key
  WHEN 'asset_move.title' THEN 'Move asset'
  WHEN 'asset_move.success' THEN 'Asset moved successfully'
  WHEN 'asset_move.failed' THEN 'Failed to move asset'
  WHEN 'asset_move.in_progress' THEN 'Moving asset...'
  ELSE default_en
END
WHERE string_key IN ('asset_move.title', 'asset_move.success', 'asset_move.failed', 'asset_move.in_progress');

UPDATE wa_ui_translations t
JOIN wa_ui_strings s ON s.id = t.ui_string_id
SET t.translated_value = CASE s.string_key
  WHEN 'asset_move.title' THEN 'Asset áthelyezése'
  WHEN 'asset_move.success' THEN 'Az asset áthelyezése sikerült'
  WHEN 'asset_move.failed' THEN 'Az asset áthelyezése sikertelen'
  WHEN 'asset_move.in_progress' THEN 'Asset áthelyezése...'
  ELSE t.translated_value
END
WHERE t.language_code = 'hu'
  AND s.string_key IN ('asset_move.title', 'asset_move.success', 'asset_move.failed', 'asset_move.in_progress');

INSERT IGNORE INTO wa_ui_strings (string_key, default_en, context) VALUES
  ('asset_move.success_with_warnings', 'Asset moved, but derivative cleanup had warnings', 'asset_move'),
  ('api.only_document_assets_supported', 'Only document assets are supported', 'api'),
  ('api.only_supported_assets_can_be_moved', 'Only supported assets can be moved', 'api'),
  ('api.asset_move_blocked_running_derivative_job', 'Asset move blocked: a running derivative job exists for this asset', 'api');

INSERT INTO wa_ui_translations (ui_string_id, language_code, translated_value, is_final, updated_by_user_id)
SELECT s.id, 'hu', t.translated_value, 1, NULL
FROM wa_ui_strings s
JOIN (
  SELECT 'asset_move.success_with_warnings' AS string_key, 'Az asset áthelyezése sikerült, de a származtatott fájlok takarításánál figyelmeztetés keletkezett' AS translated_value UNION ALL
  SELECT 'api.only_document_assets_supported', 'Csak dokumentum assetek támogatottak' UNION ALL
  SELECT 'api.only_supported_assets_can_be_moved', 'Csak támogatott assetek helyezhetők át' UNION ALL
  SELECT 'api.asset_move_blocked_running_derivative_job', 'Az asset áthelyezése blokkolva: futó származtatott fájl feladat tartozik hozzá'
) AS t ON t.string_key = s.string_key
LEFT JOIN wa_ui_translations x
  ON x.ui_string_id = s.id AND x.language_code = 'hu'
WHERE x.id IS NULL;

INSERT INTO wa_ui_strings (string_key, default_en, context)
VALUES
  ('semantic_tags.current', 'Typed tags', 'semantic_tags'),
  ('semantic_tags.none_current', 'No typed tags', 'semantic_tags'),
  ('semantic_tags.source_embedded', 'embedded', 'semantic_tags'),
  ('semantic_tags.source_manual', 'manual', 'semantic_tags'),
  ('semantic_tags.remove_manual', 'Remove manual relation', 'semantic_tags'),
  ('semantic_tags.assign_here', 'Assign here', 'semantic_tags'),
  ('semantic_tags.load_target_failed', 'Failed to load typed tags', 'semantic_tags'),
  ('semantic_tags.unassign_failed', 'Failed to remove typed tag relation', 'semantic_tags'),
  ('semantic_tags.unassign_done', 'Typed tag relation removed.', 'semantic_tags')
ON DUPLICATE KEY UPDATE
  default_en = VALUES(default_en),
  context = VALUES(context),
  updated_at = CURRENT_TIMESTAMP;

INSERT INTO wa_ui_translations (ui_string_id, language_code, translated_value, is_final)
SELECT s.id, 'hu', v.translated_value, 1
FROM wa_ui_strings s
JOIN (
  SELECT 'semantic_tags.current' AS string_key, 'Típusos címkék' AS translated_value UNION ALL
  SELECT 'semantic_tags.none_current', 'Nincs típusos címke' UNION ALL
  SELECT 'semantic_tags.source_embedded', 'beágyazott' UNION ALL
  SELECT 'semantic_tags.source_manual', 'manuális' UNION ALL
  SELECT 'semantic_tags.remove_manual', 'Manuális kapcsolat eltávolítása' UNION ALL
  SELECT 'semantic_tags.assign_here', 'Hozzárendelés itt' UNION ALL
  SELECT 'semantic_tags.load_target_failed', 'A típusos címkék betöltése sikertelen' UNION ALL
  SELECT 'semantic_tags.unassign_failed', 'A típusos címkekapcsolat eltávolítása sikertelen' UNION ALL
  SELECT 'semantic_tags.unassign_done', 'A típusos címkekapcsolat eltávolítva.'
) v ON v.string_key = s.string_key
ON DUPLICATE KEY UPDATE
  translated_value = VALUES(translated_value),
  is_final = VALUES(is_final),
  updated_at = CURRENT_TIMESTAMP;

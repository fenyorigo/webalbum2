INSERT INTO wa_ui_strings (string_key, default_en, context)
VALUES
  ('common.active', 'Active', 'common'),
  ('common.inactive', 'Inactive', 'common'),
  ('semantic_tags.search', 'Semantic tag search', 'semantic_tags'),
  ('semantic_tags.search_placeholder', 'Find typed tags...', 'semantic_tags'),
  ('semantic_tags.add', 'Add typed tag', 'semantic_tags'),
  ('semantic_tags.create_title', 'Create typed tag', 'semantic_tags'),
  ('semantic_tags.edit_title', 'Edit typed tag', 'semantic_tags'),
  ('semantic_tags.name', 'Tag name', 'semantic_tags'),
  ('semantic_tags.type', 'Tag type', 'semantic_tags'),
  ('semantic_tags.type_person', 'Person', 'semantic_tags'),
  ('semantic_tags.type_event', 'Event', 'semantic_tags'),
  ('semantic_tags.type_category', 'Category', 'semantic_tags'),
  ('semantic_tags.type_generic', 'Generic', 'semantic_tags'),
  ('semantic_tags.parent', 'Parent tag', 'semantic_tags'),
  ('semantic_tags.parent_placeholder', 'Optional parent tag', 'semantic_tags'),
  ('semantic_tags.parent_selected', 'Parent: {name}', 'semantic_tags'),
  ('semantic_tags.active', 'Active', 'semantic_tags'),
  ('semantic_tags.usage', 'Usage', 'semantic_tags'),
  ('semantic_tags.usage_state', 'State', 'semantic_tags'),
  ('semantic_tags.orphan', 'Orphan', 'semantic_tags'),
  ('semantic_tags.used', 'Used', 'semantic_tags'),
  ('semantic_tags.empty', 'No typed tags yet.', 'semantic_tags'),
  ('semantic_tags.load_failed', 'Failed to load typed tags', 'semantic_tags'),
  ('semantic_tags.create_failed', 'Failed to create typed tag', 'semantic_tags'),
  ('semantic_tags.save_failed', 'Failed to save typed tag', 'semantic_tags'),
  ('semantic_tags.create_and_assign', 'Create typed tag and assign', 'semantic_tags'),
  ('semantic_tags.created_and_assigned', 'Typed tag created and added', 'semantic_tags'),
  ('api.invalid_tag_name', 'Invalid tag name', 'api'),
  ('api.invalid_tag_type', 'Invalid tag type', 'api'),
  ('api.tag_exists', 'Tag already exists', 'api'),
  ('api.parent_tag_not_found', 'Parent tag not found', 'api'),
  ('api.parent_tag_self', 'Parent tag cannot equal tag', 'api'),
  ('api.semantic_tag_not_found', 'Semantic tag not found', 'api')
ON DUPLICATE KEY UPDATE
  default_en = VALUES(default_en),
  context = VALUES(context),
  updated_at = CURRENT_TIMESTAMP;

INSERT INTO wa_ui_translations (ui_string_id, language_code, translated_value, is_final)
SELECT s.id, 'hu', v.translated_value, 1
FROM wa_ui_strings s
JOIN (
  SELECT 'common.active' AS string_key, 'Aktív' AS translated_value UNION ALL
  SELECT 'common.inactive', 'Inaktív' UNION ALL
  SELECT 'semantic_tags.search', 'Szemantikus címke keresés' UNION ALL
  SELECT 'semantic_tags.search_placeholder', 'Típusos címkék keresése...' UNION ALL
  SELECT 'semantic_tags.add', 'Típusos címke hozzáadása' UNION ALL
  SELECT 'semantic_tags.create_title', 'Típusos címke létrehozása' UNION ALL
  SELECT 'semantic_tags.edit_title', 'Típusos címke szerkesztése' UNION ALL
  SELECT 'semantic_tags.name', 'Címkenév' UNION ALL
  SELECT 'semantic_tags.type', 'Címketípus' UNION ALL
  SELECT 'semantic_tags.type_person', 'Személy' UNION ALL
  SELECT 'semantic_tags.type_event', 'Esemény' UNION ALL
  SELECT 'semantic_tags.type_category', 'Kategória' UNION ALL
  SELECT 'semantic_tags.type_generic', 'Általános' UNION ALL
  SELECT 'semantic_tags.parent', 'Szülő címke' UNION ALL
  SELECT 'semantic_tags.parent_placeholder', 'Opcionális szülő címke' UNION ALL
  SELECT 'semantic_tags.parent_selected', 'Szülő: {name}' UNION ALL
  SELECT 'semantic_tags.active', 'Aktív' UNION ALL
  SELECT 'semantic_tags.usage', 'Használat' UNION ALL
  SELECT 'semantic_tags.usage_state', 'Állapot' UNION ALL
  SELECT 'semantic_tags.orphan', 'Árva' UNION ALL
  SELECT 'semantic_tags.used', 'Használt' UNION ALL
  SELECT 'semantic_tags.empty', 'Még nincs típusos címke.' UNION ALL
  SELECT 'semantic_tags.load_failed', 'A típusos címkék betöltése sikertelen' UNION ALL
  SELECT 'semantic_tags.create_failed', 'A típusos címke létrehozása sikertelen' UNION ALL
  SELECT 'semantic_tags.save_failed', 'A típusos címke mentése sikertelen' UNION ALL
  SELECT 'semantic_tags.create_and_assign', 'Típusos címke létrehozása és hozzárendelése' UNION ALL
  SELECT 'semantic_tags.created_and_assigned', 'A típusos címke létrejött és hozzá lett adva' UNION ALL
  SELECT 'api.invalid_tag_name', 'Érvénytelen címkenév' UNION ALL
  SELECT 'api.invalid_tag_type', 'Érvénytelen címketípus' UNION ALL
  SELECT 'api.tag_exists', 'A címke már létezik' UNION ALL
  SELECT 'api.parent_tag_not_found', 'A szülő címke nem található' UNION ALL
  SELECT 'api.parent_tag_self', 'A szülő címke nem lehet azonos a címkével' UNION ALL
  SELECT 'api.semantic_tag_not_found', 'A szemantikus címke nem található'
) v ON v.string_key = s.string_key
ON DUPLICATE KEY UPDATE
  translated_value = VALUES(translated_value),
  is_final = VALUES(is_final),
  updated_at = CURRENT_TIMESTAMP;

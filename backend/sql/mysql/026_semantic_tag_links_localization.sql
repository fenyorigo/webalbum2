INSERT INTO wa_ui_strings (string_key, default_en, context)
VALUES
  ('semantic_tags.assign', 'Assign typed tag', 'semantic_tags'),
  ('semantic_tags.assign_title', 'Assign typed tag', 'semantic_tags'),
  ('semantic_tags.assign_scope', 'Apply to', 'semantic_tags'),
  ('semantic_tags.assign_selected', 'Selected items', 'semantic_tags'),
  ('semantic_tags.assign_all_results', 'All search results', 'semantic_tags'),
  ('semantic_tags.assign_tag', 'Typed tag', 'semantic_tags'),
  ('semantic_tags.assign_placeholder', 'Search typed tag...', 'semantic_tags'),
  ('semantic_tags.assign_selected_tag', 'Selected typed tag: {name}', 'semantic_tags'),
  ('semantic_tags.assign_count', 'Matching items: {count}.', 'semantic_tags'),
  ('semantic_tags.assign_load_failed', 'Failed to resolve assignment scope', 'semantic_tags'),
  ('semantic_tags.assign_submit_failed', 'Failed to assign typed tag', 'semantic_tags'),
  ('semantic_tags.assign_done', 'Typed tag assigned to {assigned} items, skipped {skipped}.', 'semantic_tags'),
  ('semantic_tags.assign_confirm_all_results', 'Apply typed tag "{name}" to all {count} matching items?', 'semantic_tags'),
  ('semantic_tags.assign_select_tag_first', 'Select or create a typed tag first', 'semantic_tags'),
  ('semantic_tags.assign_select_or_search_first', 'Select items or run a search first', 'semantic_tags'),
  ('semantic_tags.manual_relations', 'Manual relations', 'semantic_tags'),
  ('api.no_matching_media_found', 'No matching media found', 'api')
ON DUPLICATE KEY UPDATE
  default_en = VALUES(default_en),
  context = VALUES(context),
  updated_at = CURRENT_TIMESTAMP;

INSERT INTO wa_ui_translations (ui_string_id, language_code, translated_value, is_final)
SELECT s.id, 'hu', v.translated_value, 1
FROM wa_ui_strings s
JOIN (
  SELECT 'semantic_tags.assign' AS string_key, 'Típusos címke hozzárendelése' AS translated_value UNION ALL
  SELECT 'semantic_tags.assign_title', 'Típusos címke hozzárendelése' UNION ALL
  SELECT 'semantic_tags.assign_scope', 'Hatókör' UNION ALL
  SELECT 'semantic_tags.assign_selected', 'Kijelölt elemek' UNION ALL
  SELECT 'semantic_tags.assign_all_results', 'Minden keresési találat' UNION ALL
  SELECT 'semantic_tags.assign_tag', 'Típusos címke' UNION ALL
  SELECT 'semantic_tags.assign_placeholder', 'Típusos címke keresése...' UNION ALL
  SELECT 'semantic_tags.assign_selected_tag', 'Kiválasztott típusos címke: {name}' UNION ALL
  SELECT 'semantic_tags.assign_count', 'Egyező elemek: {count}.' UNION ALL
  SELECT 'semantic_tags.assign_load_failed', 'A hozzárendelési hatókör feloldása sikertelen' UNION ALL
  SELECT 'semantic_tags.assign_submit_failed', 'A típusos címke hozzárendelése sikertelen' UNION ALL
  SELECT 'semantic_tags.assign_done', 'A típusos címke {assigned} elemhez hozzáadva, kihagyva: {skipped}.' UNION ALL
  SELECT 'semantic_tags.assign_confirm_all_results', 'Alkalmazzuk a(z) "{name}" típusos címkét mind a(z) {count} egyező elemre?' UNION ALL
  SELECT 'semantic_tags.assign_select_tag_first', 'Előbb válassz vagy hozz létre egy típusos címkét' UNION ALL
  SELECT 'semantic_tags.assign_select_or_search_first', 'Előbb jelölj ki elemeket vagy futtass keresést' UNION ALL
  SELECT 'semantic_tags.manual_relations', 'Manuális kapcsolatok' UNION ALL
  SELECT 'api.no_matching_media_found', 'Nem található egyező média'
) v ON v.string_key = s.string_key
ON DUPLICATE KEY UPDATE
  translated_value = VALUES(translated_value),
  is_final = VALUES(is_final),
  updated_at = CURRENT_TIMESTAMP;

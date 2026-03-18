INSERT IGNORE INTO wa_ui_strings (string_key, default_en, context) VALUES
  ('admin.tag_tree', 'Tag Tree', 'admin'),
  ('tag_tree.description', 'Manage the semantic hierarchy of typed tags.', 'tag_tree'),
  ('tag_tree.search', 'Search tags', 'tag_tree'),
  ('tag_tree.search_placeholder', 'Find typed tags...', 'tag_tree'),
  ('tag_tree.add_tag', 'Add Tag', 'tag_tree'),
  ('tag_tree.add_child', 'Add child', 'tag_tree'),
  ('tag_tree.edit', 'Edit', 'tag_tree'),
  ('tag_tree.rename', 'Rename', 'tag_tree'),
  ('tag_tree.delete', 'Delete Tag', 'tag_tree'),
  ('tag_tree.delete_confirm', 'Delete tag "{name}"?', 'tag_tree'),
  ('tag_tree.delete_usage_warning', 'This tag is currently linked to {count} items.', 'tag_tree'),
  ('tag_tree.delete_children_blocked', 'Delete child tags first.', 'tag_tree'),
  ('tag_tree.load_failed', 'Failed to load tag tree', 'tag_tree'),
  ('tag_tree.delete_failed', 'Failed to delete tag', 'tag_tree'),
  ('tag_tree.saved', 'Tag tree updated.', 'tag_tree'),
  ('tag_tree.deleted', 'Tag deleted.', 'tag_tree'),
  ('tag_tree.empty', 'No typed tags found.', 'tag_tree'),
  ('tag_tree.expand', 'Expand', 'tag_tree'),
  ('tag_tree.collapse', 'Collapse', 'tag_tree'),
  ('tag_tree.expand_matches', 'Expand matches', 'tag_tree'),
  ('tag_tree.collapse_all', 'Collapse all', 'tag_tree'),
  ('tag_tree.nodes', 'nodes', 'tag_tree'),
  ('api.cannot_delete_tag_with_children', 'Cannot delete tag with children', 'api');

INSERT INTO wa_ui_translations (ui_string_id, language_code, translated_value, is_final, updated_by_user_id)
SELECT s.id, 'hu', t.translated_value, 1, NULL
FROM wa_ui_strings s
JOIN (
  SELECT 'admin.tag_tree' AS string_key, 'Címkefa' AS translated_value UNION ALL
  SELECT 'tag_tree.description', 'A típusos címkék szemantikus hierarchiájának kezelése.' UNION ALL
  SELECT 'tag_tree.search', 'Címkék keresése' UNION ALL
  SELECT 'tag_tree.search_placeholder', 'Típusos címkék keresése...' UNION ALL
  SELECT 'tag_tree.add_tag', 'Címke hozzáadása' UNION ALL
  SELECT 'tag_tree.add_child', 'Gyermek hozzáadása' UNION ALL
  SELECT 'tag_tree.edit', 'Szerkesztés' UNION ALL
  SELECT 'tag_tree.rename', 'Átnevezés' UNION ALL
  SELECT 'tag_tree.delete', 'Címke törlése' UNION ALL
  SELECT 'tag_tree.delete_confirm', 'Töröljük a(z) "{name}" címkét?' UNION ALL
  SELECT 'tag_tree.delete_usage_warning', 'Ehhez a címkéhez jelenleg {count} elem kapcsolódik.' UNION ALL
  SELECT 'tag_tree.delete_children_blocked', 'Előbb töröld a gyermek címkéket.' UNION ALL
  SELECT 'tag_tree.load_failed', 'A címkefa betöltése sikertelen' UNION ALL
  SELECT 'tag_tree.delete_failed', 'A címke törlése sikertelen' UNION ALL
  SELECT 'tag_tree.saved', 'A címkefa frissült.' UNION ALL
  SELECT 'tag_tree.deleted', 'A címke törölve.' UNION ALL
  SELECT 'tag_tree.empty', 'Nincs található típusos címke.' UNION ALL
  SELECT 'tag_tree.expand', 'Kibontás' UNION ALL
  SELECT 'tag_tree.collapse', 'Összecsukás' UNION ALL
  SELECT 'tag_tree.expand_matches', 'Találatok kibontása' UNION ALL
  SELECT 'tag_tree.collapse_all', 'Összes összecsukása' UNION ALL
  SELECT 'tag_tree.nodes', 'csomópont' UNION ALL
  SELECT 'api.cannot_delete_tag_with_children', 'A gyermek címkével rendelkező címke nem törölhető'
) AS t ON t.string_key = s.string_key
LEFT JOIN wa_ui_translations x
  ON x.ui_string_id = s.id AND x.language_code = 'hu'
WHERE x.id IS NULL;

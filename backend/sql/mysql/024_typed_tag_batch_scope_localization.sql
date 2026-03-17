INSERT INTO wa_ui_strings (string_key, default_en, context)
VALUES
  ('search.folder_recursive', 'Recursive', 'search'),
  ('search.batch_total_count', 'Matching results: {count}.', 'search'),
  ('search.batch_scope', 'Apply to', 'search'),
  ('search.batch_scope_selected', 'Selected items', 'search'),
  ('search.batch_scope_all_results', 'All search results', 'search'),
  ('search.batch_all_results_confirm', 'Apply changes to all {count} matching items?', 'search'),
  ('search.batch_select_or_search_first', 'Select media objects or run a search first', 'search'),
  ('api.search_query_required_for_all_results', 'search_query is required for all_results', 'api'),
  ('api.no_matching_media_found', 'No matching media found', 'api')
ON DUPLICATE KEY UPDATE
  default_en = VALUES(default_en),
  context = VALUES(context),
  updated_at = CURRENT_TIMESTAMP;

INSERT INTO wa_ui_translations (ui_string_id, language_code, translated_value, is_final)
SELECT s.id, 'hu', v.translated_value, 1
FROM wa_ui_strings s
JOIN (
  SELECT 'search.folder_recursive' AS string_key, 'Rekurzív' AS translated_value UNION ALL
  SELECT 'search.batch_total_count', 'Találatok összesen: {count}.' UNION ALL
  SELECT 'search.batch_scope', 'Hatókör' UNION ALL
  SELECT 'search.batch_scope_selected', 'Kijelölt elemek' UNION ALL
  SELECT 'search.batch_scope_all_results', 'Minden keresési találat' UNION ALL
  SELECT 'search.batch_all_results_confirm', 'Alkalmazzuk a módosításokat mind a(z) {count} egyező elemre?' UNION ALL
  SELECT 'search.batch_select_or_search_first', 'Előbb jelölj ki médiaelemeket vagy futtass keresést' UNION ALL
  SELECT 'api.search_query_required_for_all_results', 'Az all_results használatához kötelező a search_query' UNION ALL
  SELECT 'api.no_matching_media_found', 'Nem található egyező média'
) v ON v.string_key = s.string_key
ON DUPLICATE KEY UPDATE
  translated_value = VALUES(translated_value),
  is_final = VALUES(is_final),
  updated_at = CURRENT_TIMESTAMP;

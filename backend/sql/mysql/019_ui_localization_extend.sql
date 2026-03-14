INSERT INTO wa_ui_strings (string_key, default_en, context) VALUES
('search.tag_match.all', 'All', 'search'),
('search.tag_match.any', 'Any', 'search'),
('search.taken.after', 'After', 'search'),
('search.taken.before', 'Before', 'search'),
('search.taken.between', 'Between', 'search'),
('search.sort.path', 'Path', 'search'),
('search.sort.taken', 'Taken', 'search'),
('search.type.photos', 'Photos', 'search'),
('search.type.videos', 'Videos', 'search'),
('search.type.audio', 'Audio', 'search'),
('search.type.documents', 'Documents', 'search'),
('ui.previous', 'Previous', 'ui'),
('ui.next', 'Next', 'ui'),
('ui.go', 'Go', 'ui'),
('ui.refresh', 'Refresh', 'ui'),
('ui.save', 'Save', 'ui'),
('ui.cancel', 'Cancel', 'ui'),
('ui.apply', 'Apply', 'ui'),
('ui.clear', 'Clear', 'ui'),
('ui.close', 'Close', 'ui'),
('ui.view', 'View', 'ui'),
('tags.reenable', 'Re-enable tags', 'tags'),
('tags.export', 'Export tags', 'tags'),
('tags.title', 'Tags', 'tags'),
('tags.single', 'Tag', 'tags'),
('favorites.title', 'My Favorites', 'favorites'),
('favorites.description', 'Your starred images.', 'favorites'),
('results.title', 'Results', 'results'),
('results.images', 'images', 'results'),
('saved_searches.title', 'Saved searches', 'saved_searches'),
('saved_searches.manage', 'Manage your saved search presets.', 'saved_searches'),
('saved_searches.empty', 'No saved searches yet.', 'saved_searches'),
('proposals.title', 'My Proposals', 'proposals'),
('proposals.description', 'Your submitted object change proposals.', 'proposals'),
('proposals.empty', 'No proposals in this filter.', 'proposals'),
('status.all', 'All', 'status'),
('status.pending', 'Pending', 'status'),
('status.approved', 'Approved', 'status'),
('status.rejected', 'Rejected', 'status'),
('status.cancelled', 'Cancelled', 'status'),
('status.found', 'Found', 'status'),
('notes.title', 'My Notes', 'notes'),
('notes.description', 'Your object notes across the gallery.', 'notes'),
('notes.single', 'Note', 'notes'),
('admin.user_management', 'User management', 'admin'),
('admin.logs.view', 'View logs', 'admin'),
('admin.trash', 'Trash', 'admin'),
('admin.assets', 'Assets', 'admin'),
('admin.object_proposals', 'Object proposals', 'admin'),
('admin.scan_documents_audio', 'Scan documents and audio', 'admin'),
('admin.job_status', 'Job status', 'admin'),
('admin.required_tools', 'Required tools', 'admin'),
('admin.clean_structure', 'Clean structure', 'admin'),
('admin.manage_thumbs', 'Manage thumbs', 'admin'),
('object.status', 'Status', 'object'),
('object.created', 'Created', 'object'),
('object.action', 'Action', 'object'),
('object.open', 'Open object', 'object'),
('tag_admin.title', 'Tag Admin', 'tag_admin'),
('tag_admin.description', 'Control tag visibility for global and personal scope.', 'tag_admin'),
('audit.title', 'Audit log details', 'audit'),
('audit.page_of', 'Page {x} of {y}', 'audit'),
('audit.source', 'Source', 'audit'),
('audit.actor', 'Actor', 'audit'),
('audit.target', 'Target', 'audit'),
('audit.export', 'Export logs (CSV)', 'audit'),
('audit.entries', 'entries', 'audit'),
('misc.limit', 'Limit', 'misc'),
('misc.search', 'Search', 'misc'),
('misc.save_paths', 'Save paths', 'misc'),
('misc.recheck', 'Recheck', 'misc'),
('misc.resolved_path', 'Resolved path', 'misc'),
('thumbs.purge_placeholder', 'Purge placeholder thumbs', 'thumbs'),
('thumbs.clear_all', 'Clear all thumbs', 'thumbs'),
('thumbs.maintenance', 'Manage thumbs', 'thumbs')
ON DUPLICATE KEY UPDATE
  default_en = VALUES(default_en),
  context = VALUES(context),
  updated_at = CURRENT_TIMESTAMP;

INSERT INTO wa_ui_translations (ui_string_id, language_code, translated_value, is_final)
SELECT s.id, 'hu', x.translated_value, 1
FROM wa_ui_strings s
JOIN (
  SELECT 'search.tag_match.all' AS string_key, 'Mind' AS translated_value UNION ALL
  SELECT 'search.tag_match.any', 'Bármely' UNION ALL
  SELECT 'search.taken.after', 'Utána' UNION ALL
  SELECT 'search.taken.before', 'Előtte' UNION ALL
  SELECT 'search.taken.between', 'Között' UNION ALL
  SELECT 'search.sort.path', 'Útvonal' UNION ALL
  SELECT 'search.sort.taken', 'Készült' UNION ALL
  SELECT 'search.type.photos', 'Képek' UNION ALL
  SELECT 'search.type.videos', 'Videók' UNION ALL
  SELECT 'search.type.audio', 'Audió' UNION ALL
  SELECT 'search.type.documents', 'Dokumentumok' UNION ALL
  SELECT 'ui.previous', 'Előző' UNION ALL
  SELECT 'ui.next', 'Következő' UNION ALL
  SELECT 'ui.go', 'Ugrás' UNION ALL
  SELECT 'ui.refresh', 'Frissítés' UNION ALL
  SELECT 'ui.save', 'Mentés' UNION ALL
  SELECT 'ui.cancel', 'Mégsem' UNION ALL
  SELECT 'ui.apply', 'Alkalmaz' UNION ALL
  SELECT 'ui.clear', 'Törlés' UNION ALL
  SELECT 'ui.close', 'Bezár' UNION ALL
  SELECT 'ui.view', 'Megnyitás' UNION ALL
  SELECT 'tags.reenable', 'Címkék újraengedélyezése' UNION ALL
  SELECT 'tags.export', 'Címkék exportálása' UNION ALL
  SELECT 'tags.title', 'Címkék' UNION ALL
  SELECT 'tags.single', 'Címke' UNION ALL
  SELECT 'favorites.title', 'Kedvenceim' UNION ALL
  SELECT 'favorites.description', 'Kijelölt képeim' UNION ALL
  SELECT 'results.title', 'Találatok' UNION ALL
  SELECT 'results.images', 'képek' UNION ALL
  SELECT 'saved_searches.title', 'Mentett keresések' UNION ALL
  SELECT 'saved_searches.manage', 'Mentett kereséseim kezelése' UNION ALL
  SELECT 'saved_searches.empty', 'Nincs még mentett keresés' UNION ALL
  SELECT 'proposals.title', 'Javaslataim' UNION ALL
  SELECT 'proposals.description', 'Az Ön által beküldött objektumváltoztatási javaslatok.' UNION ALL
  SELECT 'proposals.empty', 'Nincs javaslat ebben a szűrőben.' UNION ALL
  SELECT 'status.all', 'Minden' UNION ALL
  SELECT 'status.pending', 'Függőben' UNION ALL
  SELECT 'status.approved', 'Jóváhagyva' UNION ALL
  SELECT 'status.rejected', 'Elutasítva' UNION ALL
  SELECT 'status.cancelled', 'Törölve' UNION ALL
  SELECT 'status.found', 'Megtalálva' UNION ALL
  SELECT 'notes.title', 'Megjegyzéseim' UNION ALL
  SELECT 'notes.description', 'Az albumhoz fűzött megjegyzéseim' UNION ALL
  SELECT 'notes.single', 'Megjegyzés' UNION ALL
  SELECT 'admin.user_management', 'Felhasználók kezelése' UNION ALL
  SELECT 'admin.logs.view', 'Naplóbejegyzések' UNION ALL
  SELECT 'admin.trash', 'Kuka' UNION ALL
  SELECT 'admin.assets', 'Eszközök' UNION ALL
  SELECT 'admin.object_proposals', 'Objektumjavaslatok' UNION ALL
  SELECT 'admin.scan_documents_audio', 'Dokumentumok és audió beolvasása' UNION ALL
  SELECT 'admin.job_status', 'Feladatállapot' UNION ALL
  SELECT 'admin.required_tools', 'Szükséges eszközök' UNION ALL
  SELECT 'admin.clean_structure', 'Struktúra tisztítása' UNION ALL
  SELECT 'admin.manage_thumbs', 'Bélyegképek kezelése' UNION ALL
  SELECT 'object.status', 'Állapot' UNION ALL
  SELECT 'object.created', 'Létrehozva' UNION ALL
  SELECT 'object.action', 'Művelet' UNION ALL
  SELECT 'object.open', 'Objektum megnyitása' UNION ALL
  SELECT 'tag_admin.title', 'Címke admin' UNION ALL
  SELECT 'tag_admin.description', 'A címkék láthatóságának kezelése globális és személyes szinten.' UNION ALL
  SELECT 'audit.title', 'Naplórészletek' UNION ALL
  SELECT 'audit.page_of', '{x}. oldal / {y}' UNION ALL
  SELECT 'audit.source', 'Forrás' UNION ALL
  SELECT 'audit.actor', 'Végrehajtó' UNION ALL
  SELECT 'audit.target', 'Cél' UNION ALL
  SELECT 'audit.export', 'Naplók exportálása (CSV)' UNION ALL
  SELECT 'audit.entries', 'bejegyzés' UNION ALL
  SELECT 'misc.limit', 'Limit' UNION ALL
  SELECT 'misc.search', 'Keresés' UNION ALL
  SELECT 'misc.save_paths', 'Elérési utak mentése' UNION ALL
  SELECT 'misc.recheck', 'Újraellenőrzés' UNION ALL
  SELECT 'misc.resolved_path', 'Feloldott útvonal' UNION ALL
  SELECT 'thumbs.purge_placeholder', 'Helyfoglaló bélyegek törlése' UNION ALL
  SELECT 'thumbs.clear_all', 'Minden bélyeg törlése' UNION ALL
  SELECT 'thumbs.maintenance', 'Bélyegképek kezelése'
) x ON x.string_key = s.string_key
ON DUPLICATE KEY UPDATE
  translated_value = VALUES(translated_value),
  is_final = VALUES(is_final),
  updated_at = CURRENT_TIMESTAMP;

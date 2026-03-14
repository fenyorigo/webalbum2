INSERT INTO wa_ui_strings (string_key, default_en, context)
VALUES
  ('common.loading', 'Loading...', 'common'),
  ('common.modified', 'Modified', 'common'),
  ('ui.reset_loaded', 'Reset to loaded', 'common'),
  ('common.name', 'Name', 'common'),
  ('common.type', 'Type', 'common'),
  ('common.error', 'Error', 'common'),
  ('common.updated', 'Updated', 'common'),
  ('common.delete', 'Delete', 'common'),
  ('common.confirm', 'Confirm', 'common'),
  ('common.restore', 'Restore', 'common'),
  ('common.selected', 'Selected', 'common'),
  ('common.id', 'ID', 'common'),
  ('common.path', 'Path', 'common'),
  ('common.filename', 'Filename', 'common'),
  ('common.size', 'Size', 'common'),
  ('common.preview', 'Preview', 'common'),
  ('common.thumbnail', 'Thumbnail', 'common'),
  ('common.updated_at', 'Updated', 'common'),
  ('common.completed', 'Completed', 'common'),
  ('common.attempts', 'Attempts', 'common'),
  ('common.author', 'Author', 'common'),
  ('common.proposer', 'Proposer', 'common'),
  ('common.replace', 'Replace', 'common'),
  ('common.run', 'Run', 'common'),
  ('common.rename', 'Rename', 'common'),
  ('common.edit', 'Edit', 'common'),
  ('common.load', 'Load', 'common'),
  ('common.download', 'Download', 'common'),
  ('common.download_original', 'Download original', 'common'),
  ('common.copy', 'Copy', 'common'),
  ('common.object', 'Object', 'common'),
  ('common.view_mode.list', 'List', 'common'),
  ('common.view_mode.grid', 'Grid', 'common'),
  ('common.any', 'Any', 'common'),
  ('common.none', 'none', 'common'),
  ('common.note', 'Note', 'common'),
  ('common.note_required', 'Note is required', 'common'),
  ('common.operation_failed', 'Operation failed', 'common'),
  ('search.tag_placeholder', 'Tag', 'search'),
  ('search.logic.and', 'AND', 'search'),
  ('search.logic.and_not', 'AND NOT', 'search'),
  ('search.add_tag', 'Add tag', 'search'),
  ('search.folder', 'Folder', 'search'),
  ('search.folder_clear', 'Clear folder filter', 'search'),
  ('search.max_zip_note', 'Max 20 files per ZIP', 'search'),
  ('search.asset_seconds', 'Sec', 'search'),
  ('search.object_notes', 'Object notes', 'search'),
  ('search.save_modal_title', 'Save search', 'search'),
  ('search.replace_saved_title', 'Replace saved search?', 'search'),
  ('search.replace_saved_body', 'A saved search with this name already exists. Replace it?', 'search'),
  ('search.batch_tag_edit_title', 'Batch tag edit', 'search'),
  ('search.batch_add_tag_placeholder', 'Optional tag to add', 'search'),
  ('search.batch_common_tags', 'Common tags', 'search'),
  ('search.batch_remove_tags', 'Tags to remove', 'search'),
  ('search.batch_no_removable_tags', 'No removable tags found on the selection.', 'search'),
  ('search.batch_tag_conflict', 'The same tag cannot be removed and added.', 'search'),
  ('search.batch_loading_items', 'Loading selected items...', 'search'),
  ('search.batch_no_items', 'No selected items.', 'search'),
  ('search.batch_no_current_tags', 'No current tags', 'search'),
  ('search.history_filter_placeholder', 'Optional filter: 4490, 3579', 'search'),
  ('search.history.old', 'Old', 'search'),
  ('search.history.new', 'New', 'search'),
  ('search.saved_invalid', 'Saved search is invalid', 'search'),
  ('search.saved_load_failed', 'Failed to load saved search', 'search'),
  ('search.loaded_from_saved', 'Loaded from saved search', 'search'),
  ('search.date_invalid', 'Date must be YYYY-MM-DD', 'search'),
  ('search.save_login_required', 'Login required to save searches', 'search'),
  ('search.saved_ok', 'Saved', 'search'),
  ('search.save_failed', 'Failed to save search', 'search'),
  ('search.copy_ok', 'Copied!', 'search'),
  ('search.copy_failed', 'Copy failed', 'search'),
  ('search.select_files_first', 'Please select files first (max 20)', 'search'),
  ('search.too_many_files', 'More than 20 files selected, please unselect some', 'search'),
  ('search.download_failed', 'Download failed', 'search'),
  ('search.trash_moved', 'Moved to Trash', 'search'),
  ('search.trash_failed', 'Failed to move to trash', 'search'),
  ('search.favorites_login_required', 'Login required to use favorites', 'search'),
  ('search.favorite_toggle_failed', 'Failed to toggle favorite', 'search'),
  ('search.object_ref_missing', 'Object reference missing', 'search'),
  ('search.restore_queue_failed', 'Failed to queue restore', 'search'),
  ('search.batch_select_first', 'Select media objects first', 'search'),
  ('search.batch_preview_failed', 'Failed to load batch preview', 'search'),
  ('search.batch_submit_failed', 'Failed to queue batch tag edit', 'search'),
  ('search.preview_unsupported', 'Preview not supported for this file type', 'search'),
  ('search.restore_queued_for_file', 'Restore queued for file {id}', 'search'),
  ('search.batch_queued_summary', 'Batch queued: {queued}, skipped: {skipped}', 'search'),
  ('history.load_failed', 'Failed to load tag changes', 'search'),
  ('trash.title', 'Trash', 'trash'),
  ('trash.description', 'Review trashed media. Restore or permanently delete.', 'trash'),
  ('trash.empty', 'Trash is empty.', 'trash'),
  ('trash.restore_selected', 'Restore selected', 'trash'),
  ('trash.purge_selected', 'Purge selected', 'trash'),
  ('trash.empty_action', 'Empty trash', 'trash'),
  ('trash.sort.deleted_new', 'Trashed time (newest first)', 'trash'),
  ('trash.sort.deleted_old', 'Trashed time (oldest first)', 'trash'),
  ('trash.trashed_at', 'Trashed at', 'trash'),
  ('trash.trashed_by', 'Trashed by', 'trash'),
  ('trash.type_purge', 'Type PURGE to continue', 'trash'),
  ('trash.confirm_restore_title', 'Restore selected', 'trash'),
  ('trash.confirm_purge_title', 'Purge selected', 'trash'),
  ('trash.confirm_empty_title', 'Empty trash', 'trash'),
  ('trash.confirm_restore_body', 'Restore {count} items back to the library?', 'trash'),
  ('trash.confirm_purge_body', 'Permanently delete {count} items from Trash? This cannot be undone.', 'trash'),
  ('trash.confirm_empty_body', 'Permanently delete all current trash items? This cannot be undone.', 'trash'),
  ('trash.emptied', 'Trash emptied', 'trash'),
  ('folders.title', 'Folders', 'folders'),
  ('folders.loading', 'Loading folders...', 'folders'),
  ('folders.empty', 'No folders indexed', 'folders'),
  ('folders.expand', 'Expand folder', 'folders'),
  ('folders.collapse', 'Collapse folder', 'folders'),
  ('results.copy_link', 'Copy link', 'results'),
  ('results.unstar', 'Unstar', 'results'),
  ('results.star', 'Star', 'results'),
  ('setup.title', 'Initial setup', 'setup'),
  ('setup.description', 'Create the first admin account.', 'setup'),
  ('setup.admin_username', 'Admin username', 'setup'),
  ('setup.create_admin', 'Create admin', 'setup'),
  ('setup.username_required', 'Username is required', 'setup'),
  ('setup.password_mismatch', 'Passwords do not match', 'setup'),
  ('setup.failed', 'Setup failed', 'setup'),
  ('viewer.stop', 'Stop', 'viewer'),
  ('viewer.play', 'Play', 'viewer'),
  ('viewer.pause', 'Pause', 'viewer'),
  ('viewer.edit_tags', 'Edit Tags', 'viewer'),
  ('viewer.restore_original', 'Restore original', 'viewer'),
  ('viewer.move_to_trash', 'Move to Trash', 'viewer'),
  ('viewer.start_slideshow', 'Start slideshow', 'viewer'),
  ('viewer.end_slideshow', 'End slideshow', 'viewer'),
  ('viewer.create_proposal', 'Create proposal', 'viewer'),
  ('viewer.create_proposal_busy', 'Creating proposal...', 'viewer'),
  ('viewer.add_tag_label', 'Add tag', 'viewer'),
  ('viewer.add_tag_placeholder', 'Type a tag and press Enter', 'viewer'),
  ('viewer.video_not_available', 'Video not available', 'viewer'),
  ('viewer.image_not_available', 'Preview not supported for this file type', 'viewer'),
  ('viewer.previous_page', 'Previous page', 'viewer'),
  ('viewer.next_page', 'Next page', 'viewer'),
  ('viewer.play_pause', 'Play or pause', 'viewer'),
  ('viewer.rotate_ccw', 'Rotate counterclockwise', 'viewer'),
  ('viewer.rotate_cw', 'Rotate clockwise', 'viewer'),
  ('viewer.cancel_rotation', 'Cancel preview rotation', 'viewer'),
  ('viewer.remove_tag', 'Remove tag', 'viewer'),
  ('object.title', 'Object', 'object'),
  ('object.description', 'Collaborative notes and change proposals by SHA-256 identity.', 'object'),
  ('object.not_in_registry', 'not in registry', 'object'),
  ('object.entity', 'Entity', 'object'),
  ('object.notes', 'Notes', 'object'),
  ('object.new_note', 'New note', 'object'),
  ('object.write_note', 'Write an object note', 'object'),
  ('object.save_note', 'Save note', 'object'),
  ('notes.empty', 'No notes yet.', 'object'),
  ('object.change_proposals', 'Change Proposals', 'object'),
  ('object.proposal_type', 'Proposal type', 'object'),
  ('object.details_optional', 'Details (optional)', 'object'),
  ('object.proposal_details_placeholder', 'Describe what should change and why', 'object'),
  ('object.submit_proposal', 'Submit proposal', 'object'),
  ('object.transform_jobs', 'Transform Jobs', 'object'),
  ('object.no_transform_jobs', 'No transform jobs for this object yet.', 'object'),
  ('object.delete_note_confirm', 'Delete note #{id}?', 'object'),
  ('object.cancel_proposal_confirm', 'Cancel proposal #{id}?', 'object'),
  ('object.discard_changes_confirm', 'Discard unsaved object changes and close?', 'object'),
  ('saved_search.rename_title', 'Rename saved search', 'saved_searches'),
  ('saved_search.delete_title', 'Delete saved search', 'saved_searches'),
  ('saved_search.delete_confirm', 'Delete "{name}"?', 'saved_searches'),
  ('saved_search.name_required', 'Name is required', 'saved_searches'),
  ('saved_search.rename_failed', 'Rename failed', 'saved_searches'),
  ('saved_search.delete_failed', 'Delete failed', 'saved_searches'),
  ('saved_search.updated', 'Updated', 'saved_searches'),
  ('saved_search.load', 'Load', 'saved_searches'),
  ('saved_search.run', 'Run', 'saved_searches'),
  ('saved_search.rename', 'Rename', 'saved_searches'),
  ('saved_search.delete', 'Delete', 'saved_searches'),
  ('admin.asset_jobs', 'Asset jobs', 'admin'),
  ('admin.no_recent_errors', 'No recent errors.', 'admin'),
  ('admin.scan_clear_list', 'Clear list', 'admin'),
  ('admin.scan_clear_done_only', 'Only completed items can be cleared.', 'admin'),
  ('admin.scan_intro', 'Scan your photo library for documents and audio files, then queue any required processing (thumbnails/previews) for supported document types.', 'admin'),
  ('admin.scan_audio_note', 'Note: Audio assets do not generate thumbnails or previews.', 'admin'),
  ('admin.start_scan', 'Start scan', 'admin'),
  ('admin.no_items_section', 'No items in this section.', 'admin'),
  ('admin.tool_recheck_failed', 'Tool recheck failed', 'admin'),
  ('admin.clean_structure_confirm', 'Remove empty folders across photos/thumbs/trash roots?', 'admin'),
  ('admin.clean_structure_done', 'Clean structure done', 'admin'),
  ('admin.clean_structure_failed', 'Clean structure failed', 'admin'),
  ('admin.placeholder_purge_dry_confirm', 'Run dry-run placeholder thumb purge now?', 'admin'),
  ('admin.placeholder_purge_dry_failed', 'Placeholder purge dry-run failed', 'admin'),
  ('admin.placeholder_purge_delete_confirm', 'Delete these placeholder thumbs now?', 'admin'),
  ('admin.placeholder_purge_complete', 'Placeholder purge complete', 'admin'),
  ('admin.placeholder_purge_failed', 'Placeholder purge failed', 'admin'),
  ('admin.clear_thumbs_confirm', 'Delete all thumbnail files under WA_THUMBS_ROOT?', 'admin'),
  ('admin.clear_thumbs_confirm_final', 'This cannot be undone. Continue?', 'admin'),
  ('admin.clear_thumbs_complete', 'Clear all thumbs complete', 'admin'),
  ('admin.clear_thumbs_failed', 'Clear all thumbs failed', 'admin')
ON DUPLICATE KEY UPDATE
  default_en = VALUES(default_en),
  context = VALUES(context),
  updated_at = CURRENT_TIMESTAMP;

INSERT INTO wa_ui_translations (ui_string_id, language_code, translated_value, is_final)
SELECT s.id, 'hu', v.translated_value, 1
FROM wa_ui_strings s
JOIN (
  SELECT 'common.loading' AS string_key, 'Betöltés...' AS translated_value UNION ALL
  SELECT 'common.modified', 'Módosítva' UNION ALL
  SELECT 'ui.reset_loaded', 'Betöltött állapot visszaállítása' UNION ALL
  SELECT 'common.name', 'Név' UNION ALL
  SELECT 'common.type', 'Típus' UNION ALL
  SELECT 'common.error', 'Hiba' UNION ALL
  SELECT 'common.updated', 'Frissítve' UNION ALL
  SELECT 'common.delete', 'Törlés' UNION ALL
  SELECT 'common.confirm', 'Megerősítés' UNION ALL
  SELECT 'common.restore', 'Visszaállítás' UNION ALL
  SELECT 'common.selected', 'Kijelölve' UNION ALL
  SELECT 'common.id', 'Azonosító' UNION ALL
  SELECT 'common.path', 'Útvonal' UNION ALL
  SELECT 'common.filename', 'Fájlnév' UNION ALL
  SELECT 'common.size', 'Méret' UNION ALL
  SELECT 'common.preview', 'Előnézet' UNION ALL
  SELECT 'common.thumbnail', 'Bélyegkép' UNION ALL
  SELECT 'common.updated_at', 'Frissítve' UNION ALL
  SELECT 'common.completed', 'Befejezve' UNION ALL
  SELECT 'common.attempts', 'Próbálkozások' UNION ALL
  SELECT 'common.author', 'Szerző' UNION ALL
  SELECT 'common.proposer', 'Beküldő' UNION ALL
  SELECT 'common.replace', 'Csere' UNION ALL
  SELECT 'common.run', 'Futtatás' UNION ALL
  SELECT 'common.rename', 'Átnevezés' UNION ALL
  SELECT 'common.edit', 'Szerkesztés' UNION ALL
  SELECT 'common.load', 'Betöltés' UNION ALL
  SELECT 'common.download', 'Letöltés' UNION ALL
  SELECT 'common.download_original', 'Eredeti letöltése' UNION ALL
  SELECT 'common.copy', 'Másolás' UNION ALL
  SELECT 'common.object', 'Objektum' UNION ALL
  SELECT 'common.view_mode.list', 'Lista' UNION ALL
  SELECT 'common.view_mode.grid', 'Rács' UNION ALL
  SELECT 'common.any', 'Bármely' UNION ALL
  SELECT 'common.none', 'nincs' UNION ALL
  SELECT 'common.note', 'Megjegyzés' UNION ALL
  SELECT 'common.note_required', 'A megjegyzés kötelező' UNION ALL
  SELECT 'search.tag_placeholder', 'Címke' UNION ALL
  SELECT 'search.logic.and', 'ÉS' UNION ALL
  SELECT 'search.logic.and_not', 'ÉS NEM' UNION ALL
  SELECT 'search.add_tag', 'Címke hozzáadása' UNION ALL
  SELECT 'search.folder', 'Mappa' UNION ALL
  SELECT 'search.folder_clear', 'Mappaszűrő törlése' UNION ALL
  SELECT 'search.max_zip_note', 'Legfeljebb 20 fájl ZIP-enként' UNION ALL
  SELECT 'search.asset_seconds', 'Mp' UNION ALL
  SELECT 'search.object_notes', 'Objektum megjegyzések' UNION ALL
  SELECT 'search.save_modal_title', 'Keresés mentése' UNION ALL
  SELECT 'search.replace_saved_title', 'Mentett keresés cseréje?' UNION ALL
  SELECT 'search.replace_saved_body', 'Már létezik ilyen nevű mentett keresés. Lecseréled?' UNION ALL
  SELECT 'search.batch_tag_edit_title', 'Tömeges címkeszerkesztés' UNION ALL
  SELECT 'search.batch_add_tag_placeholder', 'Opcionális hozzáadandó címke' UNION ALL
  SELECT 'search.batch_common_tags', 'Közös címkék' UNION ALL
  SELECT 'search.batch_remove_tags', 'Eltávolítandó címkék' UNION ALL
  SELECT 'search.batch_no_removable_tags', 'A kijelölésen nincs eltávolítható címke.' UNION ALL
  SELECT 'search.batch_tag_conflict', 'Ugyanaz a címke nem lehet egyszerre törlendő és hozzáadandó.' UNION ALL
  SELECT 'search.batch_loading_items', 'A kijelölt elemek betöltése...' UNION ALL
  SELECT 'search.batch_no_items', 'Nincs kijelölt elem.' UNION ALL
  SELECT 'search.batch_no_current_tags', 'Nincsenek jelenlegi címkék' UNION ALL
  SELECT 'search.history_filter_placeholder', 'Opcionális szűrő: 4490, 3579' UNION ALL
  SELECT 'search.history.old', 'Régi' UNION ALL
  SELECT 'search.history.new', 'Új' UNION ALL
  SELECT 'search.saved_invalid', 'A mentett keresés érvénytelen' UNION ALL
  SELECT 'search.saved_load_failed', 'A mentett keresés betöltése sikertelen' UNION ALL
  SELECT 'search.loaded_from_saved', 'Betöltve mentett keresésből' UNION ALL
  SELECT 'search.date_invalid', 'A dátum formátuma YYYY-MM-DD legyen' UNION ALL
  SELECT 'search.save_login_required', 'Mentéshez bejelentkezés szükséges' UNION ALL
  SELECT 'search.saved_ok', 'Mentve' UNION ALL
  SELECT 'search.save_failed', 'A mentés sikertelen' UNION ALL
  SELECT 'search.copy_ok', 'Másolva!' UNION ALL
  SELECT 'search.copy_failed', 'A másolás sikertelen' UNION ALL
  SELECT 'search.select_files_first', 'Először válassz fájlokat (max. 20)' UNION ALL
  SELECT 'search.too_many_files', 'Több mint 20 fájl van kijelölve, vegyél ki néhányat' UNION ALL
  SELECT 'search.download_failed', 'A letöltés sikertelen' UNION ALL
  SELECT 'search.trash_moved', 'Áthelyezve a kukába' UNION ALL
  SELECT 'search.trash_failed', 'Nem sikerült a kukába helyezni' UNION ALL
  SELECT 'search.favorites_login_required', 'A kedvencekhez bejelentkezés szükséges' UNION ALL
  SELECT 'search.favorite_toggle_failed', 'A kedvenc állapot váltása sikertelen' UNION ALL
  SELECT 'search.object_ref_missing', 'Hiányzó objektumhivatkozás' UNION ALL
  SELECT 'search.restore_queue_failed', 'A visszaállítás sorba állítása sikertelen' UNION ALL
  SELECT 'search.batch_select_first', 'Először válassz médiaobjektumokat' UNION ALL
  SELECT 'search.batch_preview_failed', 'A tömeges előnézet betöltése sikertelen' UNION ALL
  SELECT 'search.batch_submit_failed', 'A tömeges címkemódosítás sorba állítása sikertelen' UNION ALL
  SELECT 'search.preview_unsupported', 'Ehhez a fájltípushoz nincs előnézet' UNION ALL
  SELECT 'search.restore_queued_for_file', 'Visszaállítás sorba állítva a(z) {id} fájlhoz' UNION ALL
  SELECT 'search.batch_queued_summary', 'Tömeges művelet sorban: {queued}, kihagyva: {skipped}' UNION ALL
  SELECT 'history.load_failed', 'A címkeváltozások betöltése sikertelen' UNION ALL
  SELECT 'trash.title', 'Kuka' UNION ALL
  SELECT 'trash.description', 'A törölt média áttekintése. Visszaállítás vagy végleges törlés.' UNION ALL
  SELECT 'trash.empty', 'A kuka üres.' UNION ALL
  SELECT 'trash.restore_selected', 'Kijelöltek visszaállítása' UNION ALL
  SELECT 'trash.purge_selected', 'Kijelöltek végleges törlése' UNION ALL
  SELECT 'trash.empty_action', 'Kuka ürítése' UNION ALL
  SELECT 'trash.sort.deleted_new', 'Kukába helyezés ideje (újabb elöl)' UNION ALL
  SELECT 'trash.sort.deleted_old', 'Kukába helyezés ideje (régebbi elöl)' UNION ALL
  SELECT 'trash.trashed_at', 'Kukába helyezve' UNION ALL
  SELECT 'trash.trashed_by', 'Kukába helyezte' UNION ALL
  SELECT 'trash.type_purge', 'Folytatáshoz írd be: PURGE' UNION ALL
  SELECT 'trash.confirm_restore_title', 'Kijelöltek visszaállítása' UNION ALL
  SELECT 'trash.confirm_purge_title', 'Kijelöltek végleges törlése' UNION ALL
  SELECT 'trash.confirm_empty_title', 'Kuka ürítése' UNION ALL
  SELECT 'trash.confirm_restore_body', '{count} elem visszaállítása a könyvtárba?' UNION ALL
  SELECT 'trash.confirm_purge_body', '{count} elem végleges törlése a kukából? Ez nem vonható vissza.' UNION ALL
  SELECT 'trash.confirm_empty_body', 'Az összes jelenlegi kukaelem végleges törlése? Ez nem vonható vissza.' UNION ALL
  SELECT 'trash.emptied', 'A kuka kiürítve' UNION ALL
  SELECT 'folders.title', 'Mappák' UNION ALL
  SELECT 'folders.loading', 'Mappák betöltése...' UNION ALL
  SELECT 'folders.empty', 'Nincs indexelt mappa' UNION ALL
  SELECT 'folders.expand', 'Mappa kibontása' UNION ALL
  SELECT 'folders.collapse', 'Mappa összecsukása' UNION ALL
  SELECT 'results.copy_link', 'Hivatkozás másolása' UNION ALL
  SELECT 'results.unstar', 'Eltávolítás a kedvencekből' UNION ALL
  SELECT 'results.star', 'Kedvenc' UNION ALL
  SELECT 'setup.title', 'Kezdeti beállítás' UNION ALL
  SELECT 'setup.description', 'Az első admin fiók létrehozása.' UNION ALL
  SELECT 'setup.admin_username', 'Admin felhasználónév' UNION ALL
  SELECT 'setup.create_admin', 'Admin létrehozása' UNION ALL
  SELECT 'setup.username_required', 'A felhasználónév kötelező' UNION ALL
  SELECT 'setup.password_mismatch', 'A jelszavak nem egyeznek' UNION ALL
  SELECT 'setup.failed', 'A beállítás sikertelen' UNION ALL
  SELECT 'common.operation_failed', 'A művelet sikertelen' UNION ALL
  SELECT 'viewer.stop', 'Leállítás' UNION ALL
  SELECT 'viewer.play', 'Lejátszás' UNION ALL
  SELECT 'viewer.pause', 'Szünet' UNION ALL
  SELECT 'viewer.edit_tags', 'Címkék szerkesztése' UNION ALL
  SELECT 'viewer.restore_original', 'Eredeti visszaállítása' UNION ALL
  SELECT 'viewer.move_to_trash', 'Kukába helyezés' UNION ALL
  SELECT 'viewer.start_slideshow', 'Diavetítés indítása' UNION ALL
  SELECT 'viewer.end_slideshow', 'Diavetítés vége' UNION ALL
  SELECT 'viewer.create_proposal', 'Javaslat létrehozása' UNION ALL
  SELECT 'viewer.create_proposal_busy', 'Javaslat létrehozása...' UNION ALL
  SELECT 'viewer.add_tag_label', 'Címke hozzáadása' UNION ALL
  SELECT 'viewer.add_tag_placeholder', 'Írj be egy címkét és nyomj Entert' UNION ALL
  SELECT 'viewer.video_not_available', 'A videó nem elérhető' UNION ALL
  SELECT 'viewer.image_not_available', 'Ehhez a fájltípushoz nincs előnézet' UNION ALL
  SELECT 'viewer.previous_page', 'Előző oldal' UNION ALL
  SELECT 'viewer.next_page', 'Következő oldal' UNION ALL
  SELECT 'viewer.play_pause', 'Lejátszás vagy szünet' UNION ALL
  SELECT 'viewer.rotate_ccw', 'Forgatás balra' UNION ALL
  SELECT 'viewer.rotate_cw', 'Forgatás jobbra' UNION ALL
  SELECT 'viewer.cancel_rotation', 'Előnézeti forgatás megszakítása' UNION ALL
  SELECT 'viewer.remove_tag', 'Címke eltávolítása' UNION ALL
  SELECT 'object.title', 'Objektum' UNION ALL
  SELECT 'object.description', 'Közös megjegyzések és változtatási javaslatok SHA-256 azonosság alapján.' UNION ALL
  SELECT 'object.not_in_registry', 'nincs a nyilvántartásban' UNION ALL
  SELECT 'object.entity', 'Entitás' UNION ALL
  SELECT 'object.notes', 'Megjegyzések' UNION ALL
  SELECT 'object.new_note', 'Új megjegyzés' UNION ALL
  SELECT 'object.write_note', 'Írj objektum megjegyzést' UNION ALL
  SELECT 'object.save_note', 'Megjegyzés mentése' UNION ALL
  SELECT 'notes.empty', 'Még nincs megjegyzés.' UNION ALL
  SELECT 'object.change_proposals', 'Változtatási javaslatok' UNION ALL
  SELECT 'object.proposal_type', 'Javaslat típusa' UNION ALL
  SELECT 'object.details_optional', 'Részletek (opcionális)' UNION ALL
  SELECT 'object.proposal_details_placeholder', 'Írd le, mi változzon és miért' UNION ALL
  SELECT 'object.submit_proposal', 'Javaslat beküldése' UNION ALL
  SELECT 'object.transform_jobs', 'Transzformációs feladatok' UNION ALL
  SELECT 'object.no_transform_jobs', 'Ehhez az objektumhoz még nincs transzformációs feladat.' UNION ALL
  SELECT 'object.delete_note_confirm', 'A(z) #{id} megjegyzés törlése?' UNION ALL
  SELECT 'object.cancel_proposal_confirm', 'A(z) #{id} javaslat törlése?' UNION ALL
  SELECT 'object.discard_changes_confirm', 'A nem mentett objektummódosítások eldobása és bezárás?' UNION ALL
  SELECT 'saved_search.rename_title', 'Mentett keresés átnevezése' UNION ALL
  SELECT 'saved_search.delete_title', 'Mentett keresés törlése' UNION ALL
  SELECT 'saved_search.delete_confirm', 'Törlöd ezt: "{name}"?' UNION ALL
  SELECT 'saved_search.name_required', 'A név kötelező' UNION ALL
  SELECT 'saved_search.rename_failed', 'Az átnevezés sikertelen' UNION ALL
  SELECT 'saved_search.delete_failed', 'A törlés sikertelen' UNION ALL
  SELECT 'saved_search.updated', 'Frissítve' UNION ALL
  SELECT 'saved_search.load', 'Betöltés' UNION ALL
  SELECT 'saved_search.run', 'Futtatás' UNION ALL
  SELECT 'saved_search.rename', 'Átnevezés' UNION ALL
  SELECT 'saved_search.delete', 'Törlés' UNION ALL
  SELECT 'admin.asset_jobs', 'Eszközfeladatok' UNION ALL
  SELECT 'admin.no_recent_errors', 'Nincsenek friss hibák.' UNION ALL
  SELECT 'admin.scan_clear_list', 'Lista törlése' UNION ALL
  SELECT 'admin.scan_clear_done_only', 'Csak a befejezett elemek törölhetők.' UNION ALL
  SELECT 'admin.scan_intro', 'A fotótár dokumentum- és hangfájljainak vizsgálata, majd a szükséges feldolgozás (bélyegképek/előnézetek) sorba állítása a támogatott dokumentumtípusokhoz.' UNION ALL
  SELECT 'admin.scan_audio_note', 'Megjegyzés: a hangfájlokhoz nem készül bélyegkép vagy előnézet.' UNION ALL
  SELECT 'admin.start_scan', 'Keresés indítása' UNION ALL
  SELECT 'admin.no_items_section', 'Nincs elem ebben a részben.' UNION ALL
  SELECT 'admin.tool_recheck_failed', 'Az eszközök újraellenőrzése sikertelen' UNION ALL
  SELECT 'admin.clean_structure_confirm', 'Az üres mappák eltávolítása a fotó/bélyegkép/kuka gyökerekből?' UNION ALL
  SELECT 'admin.clean_structure_done', 'A struktúratisztítás kész' UNION ALL
  SELECT 'admin.clean_structure_failed', 'A struktúratisztítás sikertelen' UNION ALL
  SELECT 'admin.placeholder_purge_dry_confirm', 'Futtassuk most a helyfoglaló bélyegek próba-törlését?' UNION ALL
  SELECT 'admin.placeholder_purge_dry_failed', 'A helyfoglaló bélyeg próba-törlése sikertelen' UNION ALL
  SELECT 'admin.placeholder_purge_delete_confirm', 'Töröljük most ezeket a helyfoglaló bélyegeket?' UNION ALL
  SELECT 'admin.placeholder_purge_complete', 'A helyfoglaló bélyegek törlése kész' UNION ALL
  SELECT 'admin.placeholder_purge_failed', 'A helyfoglaló bélyegek törlése sikertelen' UNION ALL
  SELECT 'admin.clear_thumbs_confirm', 'Töröljük az összes bélyegképfájlt a WA_THUMBS_ROOT alatt?' UNION ALL
  SELECT 'admin.clear_thumbs_confirm_final', 'Ez nem vonható vissza. Folytatod?' UNION ALL
  SELECT 'admin.clear_thumbs_complete', 'Az összes bélyeg törlése kész' UNION ALL
  SELECT 'admin.clear_thumbs_failed', 'Az összes bélyeg törlése sikertelen'
) v ON v.string_key = s.string_key
ON DUPLICATE KEY UPDATE
  translated_value = VALUES(translated_value),
  is_final = VALUES(is_final),
  updated_at = CURRENT_TIMESTAMP;

INSERT INTO wa_ui_strings (string_key, default_en, context)
VALUES
  ('profile_picker.title', 'Select a profile', 'profile'),
  ('profile_picker.empty', 'No users found. Seed wa_users first.', 'profile'),
  ('profile_picker.load_failed', 'Failed to load users', 'profile'),
  ('profile_picker.select_failed', 'Failed to select user', 'profile'),
  ('object.sha', 'SHA', 'object'),
  ('object.sha256', 'SHA-256', 'object'),
  ('object.resolve_failed', 'Object resolve failed', 'object'),
  ('object.invalid_sha', 'Invalid SHA-256', 'object'),
  ('object.load_failed', 'Failed to load object data', 'object'),
  ('proposal.type.retag', 'Retag', 'proposal'),
  ('proposal.type.rotate_left', 'Rotate left', 'proposal'),
  ('proposal.type.rotate_right', 'Rotate right', 'proposal'),
  ('proposal.type.annotate', 'Annotate', 'proposal'),
  ('proposal.type.transform', 'Transform', 'proposal'),
  ('proposal.type.restore_metadata', 'Restore metadata', 'proposal'),
  ('proposal.type_required', 'Proposal type is required', 'proposal'),
  ('proposals.load_failed', 'Failed to load proposals', 'proposal'),
  ('proposals.load_mine_failed', 'Failed to load my proposals', 'proposal'),
  ('proposals.submit_failed', 'Failed to submit proposal', 'proposal'),
  ('proposals.cancel_failed', 'Failed to cancel proposal', 'proposal'),
  ('proposals.cancel_confirm', 'Cancel proposal #{id}?', 'proposal'),
  ('proposals.review_failed', 'Failed to review proposal', 'proposal'),
  ('proposals.review_approve_title', 'Approve proposal', 'proposal'),
  ('proposals.review_reject_title', 'Reject proposal', 'proposal'),
  ('proposals.review_target', 'Proposal #{id} ({type})', 'proposal'),
  ('proposals.review_note', 'Review note', 'proposal'),
  ('proposals.review_note_placeholder', 'Optional rationale', 'proposal'),
  ('notes.load_failed', 'Failed to load notes', 'notes'),
  ('notes.create_failed', 'Failed to create note', 'notes'),
  ('notes.update_failed', 'Failed to update note', 'notes'),
  ('notes.delete_failed', 'Failed to delete note', 'notes'),
  ('jobs.load_failed', 'Failed to load transform jobs', 'jobs'),
  ('jobs.filter', 'Job filter', 'jobs'),
  ('jobs.filter_active', 'Active (queued/running)', 'jobs'),
  ('status.done', 'Done', 'status'),
  ('status.queued', 'Queued', 'status'),
  ('status.running', 'Running', 'status'),
  ('status.ready', 'Ready', 'status'),
  ('status.failed', 'Failed', 'status'),
  ('status.no_processing', 'No processing needed', 'status'),
  ('status.approve_action', 'Approve', 'status'),
  ('status.reject_action', 'Reject', 'status'),
  ('admin.review', 'Review', 'admin'),
  ('assets.description', 'Documents and audio indexed in the archive.', 'assets'),
  ('assets.jobs_summary', 'Jobs: queued {queued}, running {running}, done {done}, error {error}', 'assets'),
  ('assets.search_path', 'Search path', 'assets'),
  ('assets.ext', 'Ext', 'assets'),
  ('assets.derivative_status', 'Derivative status', 'assets'),
  ('assets.clear_done_only', 'Only completed items can be cleared.', 'assets'),
  ('assets.mtime', 'MTime', 'assets'),
  ('assets.requeue_thumb', 'Requeue thumb', 'assets'),
  ('assets.scan_confirm', 'Scan your photo library for documents and audio files, then queue any required processing (thumbnails/previews) for supported document types.\n\nNote: Audio items don’t generate thumbnails or previews.', 'assets'),
  ('assets.scan_failed', 'Scan failed', 'assets'),
  ('assets.scan_done', 'Scan done. Scanned {scanned} (Documents: {docs}, Audio: {audio}), enqueued {jobs} doc jobs.', 'assets'),
  ('assets.requeue_failed', 'Requeue failed', 'assets'),
  ('assets.requeue_done', 'Queued: {items}', 'assets'),
  ('tags.filter_placeholder', 'Filter tags...', 'tags'),
  ('tags.reveal_hidden', 'Reveal hidden tags', 'tags'),
  ('tags.variants', 'Variants', 'tags'),
  ('tags.enabled_global', 'Enabled (Global)', 'tags'),
  ('tags.enabled_personal', 'Enabled (Personal)', 'tags'),
  ('tags.enabled', 'Enabled', 'tags'),
  ('tags.load_failed', 'Failed to load tags', 'tags'),
  ('tags.save_failed', 'Failed to save tag settings', 'tags'),
  ('tags.reenable_confirm', 'Re-enable all tags globally and for all users?', 'tags'),
  ('tags.reenable_failed', 'Failed to re-enable tags', 'tags'),
  ('tags.reenable_done', 'All tags are re-enabled.', 'tags'),
  ('tags.export_failed', 'Failed to export tags', 'tags'),
  ('saved_search.load_failed', 'Failed to load saved searches', 'saved_search'),
  ('favorites.sort', 'Sort', 'favorites'),
  ('sort.date_new_old', 'Date New-Old', 'sort'),
  ('sort.date_old_new', 'Date Old-New', 'sort'),
  ('sort.name_az', 'Name A-Z', 'sort'),
  ('sort.name_za', 'Name Z-A', 'sort'),
  ('search.failed', 'Search failed', 'search'),
  ('assets.load_failed', 'Failed to load assets', 'assets'),
  ('tools.save_failed', 'Saving tool paths failed', 'admin'),
  ('scan.status_load_failed', 'Failed to load scan status', 'admin'),
  ('jobs.status_load_failed', 'Failed to load job status', 'jobs'),
  ('logs.load_failed', 'Failed to load logs', 'logs'),
  ('logs.export_failed', 'Failed to export logs', 'logs'),
  ('admin.save_changes_failed', 'Failed to save changes', 'admin'),
  ('admin.create_user_failed', 'Failed to create user', 'admin'),
  ('admin.update_user_failed', 'Failed to update user', 'admin'),
  ('admin.save_user_failed', 'Failed to save user', 'admin'),
  ('admin.disable_user_failed', 'Failed to disable user', 'admin'),
  ('profile.load_failed', 'Failed to load preferences', 'profile'),
  ('profile.save_failed', 'Failed to save preferences', 'profile'),
  ('profile.password_change_failed', 'Password change failed', 'profile'),
  ('folders.load_failed', 'Failed to load folders', 'folders'),
  ('folders.children_load_failed', 'Failed to load child folders', 'folders'),
  ('api.not_authenticated', 'Not authenticated', 'api'),
  ('api.forbidden', 'Forbidden', 'api'),
  ('api.not_found', 'Not Found', 'api'),
  ('api.invalid_json', 'Invalid JSON', 'api'),
  ('api.file_not_found', 'File not found', 'api'),
  ('api.asset_file_not_found', 'Asset file not found', 'api'),
  ('api.trashed', 'Trashed', 'api'),
  ('api.invalid_name', 'Invalid name', 'api'),
  ('api.invalid_query', 'Invalid query', 'api'),
  ('api.no_updates', 'No updates', 'api'),
  ('api.id_required', 'ID is required', 'api'),
  ('api.invalid_file_id', 'Invalid file reference', 'api'),
  ('api.invalid_user_id', 'Invalid user reference', 'api'),
  ('api.user_not_found', 'User not found', 'api'),
  ('api.mariadb_unavailable', 'MariaDB unavailable', 'api'),
  ('api.invalid_credentials', 'Invalid credentials', 'api'),
  ('api.too_many_login_attempts', 'Too many login attempts. Try again later.', 'api'),
  ('api.missing_fields', 'Missing fields', 'api'),
  ('api.passwords_do_not_match', 'Passwords do not match', 'api'),
  ('api.current_password_incorrect', 'Current password is incorrect', 'api'),
  ('api.invalid_username', 'Invalid username', 'api'),
  ('api.username_exists', 'Username already exists', 'api'),
  ('api.password_required', 'Password is required', 'api'),
  ('api.password_too_short', 'Password is too short', 'api'),
  ('api.cannot_delete_own_user', 'Cannot delete your own user', 'api'),
  ('api.saved_search_exists', 'Saved search already exists', 'api'),
  ('api.favorite_trashed_forbidden', 'Cannot favorite trashed media', 'api'),
  ('api.only_images_supported', 'Only images are supported', 'api'),
  ('api.only_videos_supported', 'Only videos are supported', 'api'),
  ('api.download_select_files_first', 'Please select files first (max 20)', 'api'),
  ('api.download_too_many_files', 'More than 20 files selected, please unselect some', 'api'),
  ('api.download_media_missing', 'Some selected media files were not found', 'api'),
  ('api.download_assets_missing', 'Some selected assets were not found', 'api'),
  ('api.download_trashed_forbidden', 'Trashed media cannot be downloaded', 'api'),
  ('api.download_only_media_supported', 'Only image/video media files are supported', 'api'),
  ('api.download_only_assets_supported', 'Only audio/document assets are supported', 'api'),
  ('api.file_outside_root', 'File outside configured photos root', 'api'),
  ('api.asset_outside_root', 'Asset outside configured photos root', 'api'),
  ('api.no_files_selected', 'No files selected', 'api'),
  ('api.setup_completed', 'Setup already completed', 'api'),
  ('api.query_must_be_object', 'Query must be an object', 'api'),
  ('api.query_where_required', 'query.where is required', 'api')
ON DUPLICATE KEY UPDATE
  default_en = VALUES(default_en),
  context = VALUES(context),
  updated_at = CURRENT_TIMESTAMP;

INSERT INTO wa_ui_translations (ui_string_id, language_code, translated_value, is_final)
SELECT s.id, 'hu', v.translated_value, 1
FROM wa_ui_strings s
JOIN (
  SELECT 'profile_picker.title' AS string_key, 'Profil kiválasztása' AS translated_value UNION ALL
  SELECT 'profile_picker.empty', 'Nincs felhasználó. Előbb töltsd fel a wa_users táblát.' UNION ALL
  SELECT 'profile_picker.load_failed', 'A felhasználók betöltése sikertelen' UNION ALL
  SELECT 'profile_picker.select_failed', 'A felhasználó kiválasztása sikertelen' UNION ALL
  SELECT 'object.sha', 'SHA' UNION ALL
  SELECT 'object.sha256', 'SHA-256' UNION ALL
  SELECT 'object.resolve_failed', 'Az objektum feloldása sikertelen' UNION ALL
  SELECT 'object.invalid_sha', 'Érvénytelen SHA-256' UNION ALL
  SELECT 'object.load_failed', 'Az objektum adatainak betöltése sikertelen' UNION ALL
  SELECT 'proposal.type.retag', 'Újcímkézés' UNION ALL
  SELECT 'proposal.type.rotate_left', 'Forgatás balra' UNION ALL
  SELECT 'proposal.type.rotate_right', 'Forgatás jobbra' UNION ALL
  SELECT 'proposal.type.annotate', 'Annotálás' UNION ALL
  SELECT 'proposal.type.transform', 'Transzformálás' UNION ALL
  SELECT 'proposal.type.restore_metadata', 'Metaadatok visszaállítása' UNION ALL
  SELECT 'proposal.type_required', 'A javaslat típusa kötelező' UNION ALL
  SELECT 'proposals.load_failed', 'A javaslatok betöltése sikertelen' UNION ALL
  SELECT 'proposals.load_mine_failed', 'A saját javaslatok betöltése sikertelen' UNION ALL
  SELECT 'proposals.submit_failed', 'A javaslat beküldése sikertelen' UNION ALL
  SELECT 'proposals.cancel_failed', 'A javaslat törlése sikertelen' UNION ALL
  SELECT 'proposals.cancel_confirm', 'A(z) #{id} javaslat törlése?' UNION ALL
  SELECT 'proposals.review_failed', 'A javaslat felülvizsgálata sikertelen' UNION ALL
  SELECT 'proposals.review_approve_title', 'Javaslat jóváhagyása' UNION ALL
  SELECT 'proposals.review_reject_title', 'Javaslat elutasítása' UNION ALL
  SELECT 'proposals.review_target', 'Javaslat #{id} ({type})' UNION ALL
  SELECT 'proposals.review_note', 'Felülvizsgálati megjegyzés' UNION ALL
  SELECT 'proposals.review_note_placeholder', 'Opcionális indoklás' UNION ALL
  SELECT 'notes.load_failed', 'A megjegyzések betöltése sikertelen' UNION ALL
  SELECT 'notes.create_failed', 'A megjegyzés létrehozása sikertelen' UNION ALL
  SELECT 'notes.update_failed', 'A megjegyzés frissítése sikertelen' UNION ALL
  SELECT 'notes.delete_failed', 'A megjegyzés törlése sikertelen' UNION ALL
  SELECT 'jobs.load_failed', 'A transzformációs feladatok betöltése sikertelen' UNION ALL
  SELECT 'jobs.filter', 'Feladatszűrő' UNION ALL
  SELECT 'jobs.filter_active', 'Aktív (sorban/fut)' UNION ALL
  SELECT 'status.done', 'Kész' UNION ALL
  SELECT 'status.queued', 'Sorban' UNION ALL
  SELECT 'status.running', 'Fut' UNION ALL
  SELECT 'status.ready', 'Kész' UNION ALL
  SELECT 'status.failed', 'Sikertelen' UNION ALL
  SELECT 'status.no_processing', 'Nincs szükség feldolgozásra' UNION ALL
  SELECT 'status.approve_action', 'Jóváhagyás' UNION ALL
  SELECT 'status.reject_action', 'Elutasítás' UNION ALL
  SELECT 'admin.review', 'Felülvizsgálat' UNION ALL
  SELECT 'assets.description', 'Az archívumban indexelt dokumentumok és hangfájlok.' UNION ALL
  SELECT 'assets.jobs_summary', 'Feladatok: sorban {queued}, fut {running}, kész {done}, hiba {error}' UNION ALL
  SELECT 'assets.search_path', 'Útvonal keresése' UNION ALL
  SELECT 'assets.ext', 'Kiterjesztés' UNION ALL
  SELECT 'assets.derivative_status', 'Származtatott állapot' UNION ALL
  SELECT 'assets.clear_done_only', 'Csak a befejezett elemek törölhetők.' UNION ALL
  SELECT 'assets.mtime', 'Módosítva' UNION ALL
  SELECT 'assets.requeue_thumb', 'Bélyeg újrasorba állítása' UNION ALL
  SELECT 'assets.scan_confirm', 'Vizsgálja át a fotótárat dokumentumok és hangfájlok után, majd állítsa sorba a szükséges feldolgozást (bélyegképek/előnézetek) a támogatott dokumentumtípusokhoz.\n\nMegjegyzés: a hangfájlokhoz nem készül bélyegkép vagy előnézet.' UNION ALL
  SELECT 'assets.scan_failed', 'A vizsgálat sikertelen' UNION ALL
  SELECT 'assets.scan_done', 'Vizsgálat kész. Feldolgozva: {scanned} (Dokumentum: {docs}, Audió: {audio}), sorba állítva {jobs} dokumentumfeladat.' UNION ALL
  SELECT 'assets.requeue_failed', 'Az újrasorba állítás sikertelen' UNION ALL
  SELECT 'assets.requeue_done', 'Sorba állítva: {items}' UNION ALL
  SELECT 'tags.filter_placeholder', 'Címkék szűrése...' UNION ALL
  SELECT 'tags.reveal_hidden', 'Rejtett címkék megjelenítése' UNION ALL
  SELECT 'tags.variants', 'Változatok' UNION ALL
  SELECT 'tags.enabled_global', 'Engedélyezve (globális)' UNION ALL
  SELECT 'tags.enabled_personal', 'Engedélyezve (személyes)' UNION ALL
  SELECT 'tags.enabled', 'Engedélyezve' UNION ALL
  SELECT 'tags.load_failed', 'A címkék betöltése sikertelen' UNION ALL
  SELECT 'tags.save_failed', 'A címkebeállítások mentése sikertelen' UNION ALL
  SELECT 'tags.reenable_confirm', 'Minden címke újraengedélyezése globálisan és minden felhasználónál?' UNION ALL
  SELECT 'tags.reenable_failed', 'A címkék újraengedélyezése sikertelen' UNION ALL
  SELECT 'tags.reenable_done', 'Minden címke újra engedélyezve.' UNION ALL
  SELECT 'tags.export_failed', 'A címkék exportja sikertelen' UNION ALL
  SELECT 'saved_search.load_failed', 'A mentett keresések betöltése sikertelen' UNION ALL
  SELECT 'favorites.sort', 'Rendezés' UNION ALL
  SELECT 'sort.date_new_old', 'Dátum új-régi' UNION ALL
  SELECT 'sort.date_old_new', 'Dátum régi-új' UNION ALL
  SELECT 'sort.name_az', 'Név A-Z' UNION ALL
  SELECT 'sort.name_za', 'Név Z-A' UNION ALL
  SELECT 'search.failed', 'A keresés sikertelen' UNION ALL
  SELECT 'assets.load_failed', 'Az assetek betöltése sikertelen' UNION ALL
  SELECT 'tools.save_failed', 'Az eszközútvonalak mentése sikertelen' UNION ALL
  SELECT 'scan.status_load_failed', 'A vizsgálati állapot betöltése sikertelen' UNION ALL
  SELECT 'jobs.status_load_failed', 'A feladatállapot betöltése sikertelen' UNION ALL
  SELECT 'logs.load_failed', 'A naplók betöltése sikertelen' UNION ALL
  SELECT 'logs.export_failed', 'A naplók exportja sikertelen' UNION ALL
  SELECT 'admin.save_changes_failed', 'A változtatások mentése sikertelen' UNION ALL
  SELECT 'admin.create_user_failed', 'A felhasználó létrehozása sikertelen' UNION ALL
  SELECT 'admin.update_user_failed', 'A felhasználó frissítése sikertelen' UNION ALL
  SELECT 'admin.save_user_failed', 'A felhasználó mentése sikertelen' UNION ALL
  SELECT 'admin.disable_user_failed', 'A felhasználó letiltása sikertelen' UNION ALL
  SELECT 'profile.load_failed', 'A beállítások betöltése sikertelen' UNION ALL
  SELECT 'profile.save_failed', 'A beállítások mentése sikertelen' UNION ALL
  SELECT 'profile.password_change_failed', 'A jelszócsere sikertelen' UNION ALL
  SELECT 'folders.load_failed', 'A mappák betöltése sikertelen' UNION ALL
  SELECT 'folders.children_load_failed', 'Az almappák betöltése sikertelen' UNION ALL
  SELECT 'api.not_authenticated', 'Nincs bejelentkezve' UNION ALL
  SELECT 'api.forbidden', 'Nincs jogosultság' UNION ALL
  SELECT 'api.not_found', 'Nem található' UNION ALL
  SELECT 'api.invalid_json', 'Érvénytelen JSON' UNION ALL
  SELECT 'api.file_not_found', 'A fájl nem található' UNION ALL
  SELECT 'api.asset_file_not_found', 'Az asset fájl nem található' UNION ALL
  SELECT 'api.trashed', 'Kukába helyezve' UNION ALL
  SELECT 'api.invalid_name', 'Érvénytelen név' UNION ALL
  SELECT 'api.invalid_query', 'Érvénytelen lekérdezés' UNION ALL
  SELECT 'api.no_updates', 'Nincs módosítás' UNION ALL
  SELECT 'api.id_required', 'Az azonosító kötelező' UNION ALL
  SELECT 'api.invalid_file_id', 'Érvénytelen fájlazonosító' UNION ALL
  SELECT 'api.invalid_user_id', 'Érvénytelen felhasználóazonosító' UNION ALL
  SELECT 'api.user_not_found', 'A felhasználó nem található' UNION ALL
  SELECT 'api.mariadb_unavailable', 'A MariaDB nem érhető el' UNION ALL
  SELECT 'api.invalid_credentials', 'Érvénytelen bejelentkezési adatok' UNION ALL
  SELECT 'api.too_many_login_attempts', 'Túl sok bejelentkezési próbálkozás. Próbáld később.' UNION ALL
  SELECT 'api.missing_fields', 'Hiányzó mezők' UNION ALL
  SELECT 'api.passwords_do_not_match', 'A jelszavak nem egyeznek' UNION ALL
  SELECT 'api.current_password_incorrect', 'A jelenlegi jelszó hibás' UNION ALL
  SELECT 'api.invalid_username', 'Érvénytelen felhasználónév' UNION ALL
  SELECT 'api.username_exists', 'A felhasználónév már létezik' UNION ALL
  SELECT 'api.password_required', 'A jelszó kötelező' UNION ALL
  SELECT 'api.password_too_short', 'A jelszó túl rövid' UNION ALL
  SELECT 'api.cannot_delete_own_user', 'A saját felhasználó nem törölhető' UNION ALL
  SELECT 'api.saved_search_exists', 'A mentett keresés már létezik' UNION ALL
  SELECT 'api.favorite_trashed_forbidden', 'Kukába helyezett média nem jelölhető kedvencnek' UNION ALL
  SELECT 'api.only_images_supported', 'Csak képek támogatottak' UNION ALL
  SELECT 'api.only_videos_supported', 'Csak videók támogatottak' UNION ALL
  SELECT 'api.download_select_files_first', 'Előbb válassz fájlokat (max. 20)' UNION ALL
  SELECT 'api.download_too_many_files', '20-nál több fájl van kijelölve, csökkentsd a kijelölést' UNION ALL
  SELECT 'api.download_media_missing', 'Néhány kijelölt médiafájl nem található' UNION ALL
  SELECT 'api.download_assets_missing', 'Néhány kijelölt asset nem található' UNION ALL
  SELECT 'api.download_trashed_forbidden', 'Kukába helyezett média nem tölthető le' UNION ALL
  SELECT 'api.download_only_media_supported', 'Csak kép/videó médiafájlok támogatottak' UNION ALL
  SELECT 'api.download_only_assets_supported', 'Csak audió/dokumentum assetek támogatottak' UNION ALL
  SELECT 'api.file_outside_root', 'A fájl a beállított fotógyökéren kívül van' UNION ALL
  SELECT 'api.asset_outside_root', 'Az asset a beállított fotógyökéren kívül van' UNION ALL
  SELECT 'api.no_files_selected', 'Nincs kijelölt fájl' UNION ALL
  SELECT 'api.setup_completed', 'A kezdeti beállítás már megtörtént' UNION ALL
  SELECT 'api.query_must_be_object', 'A query mezőnek objektumnak kell lennie' UNION ALL
  SELECT 'api.query_where_required', 'A query.where kötelező'
) v ON v.string_key = s.string_key
ON DUPLICATE KEY UPDATE
  translated_value = VALUES(translated_value),
  is_final = VALUES(is_final),
  updated_at = CURRENT_TIMESTAMP;

INSERT INTO wa_ui_strings (string_key, default_en, context)
VALUES
  ('search.batch_selected_count', 'Selected: {count}.', 'search'),
  ('search.batch_eligible_count', 'Eligible media: {count}.', 'search'),
  ('search.batch_summary', 'Queued {queued}, skipped {skipped}, failed {failed}.', 'search'),
  ('search.batch_queue_edit', 'Queue batch edit', 'search'),
  ('history.showing_count', 'Showing {shown} of {total} tag edits.', 'history'),
  ('history.at', 'at', 'history'),
  ('viewer.resolve_failed', 'Failed to resolve object', 'viewer'),
  ('viewer.sha_unavailable', 'Object SHA-256 not available', 'viewer'),
  ('viewer.proposal_create_failed', 'Failed to create proposal', 'viewer'),
  ('viewer.proposal_rotate_created', 'Rotate proposal created', 'viewer'),
  ('viewer.trash_reversible', 'This is reversible from Admin -> Trash.', 'viewer'),
  ('viewer.trash_failed', 'Failed to move to trash', 'viewer'),
  ('viewer.tag_empty', 'Tag cannot be empty', 'viewer'),
  ('viewer.tag_too_long', 'Tag too long (max 128)', 'viewer'),
  ('viewer.tag_pipe_forbidden', 'Tag must not contain pipe character', 'viewer'),
  ('viewer.tags_save_failed', 'Failed to save tags', 'viewer'),
  ('viewer.tag_edit_queued', 'Tag edit queued.', 'viewer'),
  ('viewer.restore_failed', 'Failed to restore original tags', 'viewer'),
  ('viewer.restore_queued', 'Original tag restore queued.', 'viewer')
ON DUPLICATE KEY UPDATE
  default_en = VALUES(default_en),
  context = VALUES(context),
  updated_at = CURRENT_TIMESTAMP;

INSERT INTO wa_ui_translations (ui_string_id, language_code, translated_value, is_final)
SELECT s.id, 'hu', v.translated_value, 1
FROM wa_ui_strings s
JOIN (
  SELECT 'search.batch_selected_count' AS string_key, 'Kijelölve: {count}.' AS translated_value UNION ALL
  SELECT 'search.batch_eligible_count', 'Érintett média: {count}.' UNION ALL
  SELECT 'search.batch_summary', 'Sorba állítva: {queued}, kihagyva: {skipped}, hibás: {failed}.' UNION ALL
  SELECT 'search.batch_queue_edit', 'Csoportos szerkesztés sorba állítása' UNION ALL
  SELECT 'history.showing_count', '{shown} / {total} címkemódosítás látható.' UNION ALL
  SELECT 'history.at', 'ekkor:' UNION ALL
  SELECT 'viewer.resolve_failed', 'Az objektum feloldása sikertelen' UNION ALL
  SELECT 'viewer.sha_unavailable', 'Az objektum SHA-256 azonosítója nem érhető el' UNION ALL
  SELECT 'viewer.proposal_create_failed', 'A javaslat létrehozása sikertelen' UNION ALL
  SELECT 'viewer.proposal_rotate_created', 'A forgatási javaslat létrejött' UNION ALL
  SELECT 'viewer.trash_reversible', 'Ez az Admin -> Kuka menüből visszaállítható.' UNION ALL
  SELECT 'viewer.trash_failed', 'A kukába helyezés sikertelen' UNION ALL
  SELECT 'viewer.tag_empty', 'A címke nem lehet üres' UNION ALL
  SELECT 'viewer.tag_too_long', 'A címke túl hosszú (max. 128)' UNION ALL
  SELECT 'viewer.tag_pipe_forbidden', 'A címke nem tartalmazhat pipe karaktert' UNION ALL
  SELECT 'viewer.tags_save_failed', 'A címkék mentése sikertelen' UNION ALL
  SELECT 'viewer.tag_edit_queued', 'A címkemódosítás sorba állítva.' UNION ALL
  SELECT 'viewer.restore_failed', 'Az eredeti címkék visszaállítása sikertelen' UNION ALL
  SELECT 'viewer.restore_queued', 'Az eredeti címkék visszaállítása sorba állítva.'
) v ON v.string_key = s.string_key
ON DUPLICATE KEY UPDATE
  translated_value = VALUES(translated_value),
  is_final = VALUES(is_final),
  updated_at = CURRENT_TIMESTAMP;

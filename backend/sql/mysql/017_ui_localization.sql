ALTER TABLE wa_user_prefs
  ADD COLUMN IF NOT EXISTS ui_language VARCHAR(16) NOT NULL DEFAULT 'en' AFTER sort_mode;

CREATE TABLE IF NOT EXISTS wa_ui_strings (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  string_key VARCHAR(191) NOT NULL,
  default_en TEXT NOT NULL,
  context VARCHAR(191) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_wa_ui_strings_key (string_key),
  INDEX idx_wa_ui_strings_context (context)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS wa_ui_translations (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  ui_string_id BIGINT NOT NULL,
  language_code VARCHAR(16) NOT NULL,
  translated_value TEXT NOT NULL,
  is_final TINYINT(1) NOT NULL DEFAULT 0,
  updated_by_user_id INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_wa_ui_translations_string FOREIGN KEY (ui_string_id) REFERENCES wa_ui_strings(id) ON DELETE CASCADE,
  CONSTRAINT fk_wa_ui_translations_updated_by FOREIGN KEY (updated_by_user_id) REFERENCES wa_users(id) ON DELETE SET NULL,
  UNIQUE KEY uniq_wa_ui_translations_lang (ui_string_id, language_code),
  INDEX idx_wa_ui_translations_lang (language_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO wa_ui_strings (string_key, default_en, context) VALUES
('app.brand', 'Family memories', 'app'),
('nav.search', 'Search', 'nav'),
('nav.tags', 'Tags', 'nav'),
('nav.favorites', 'My Favorites', 'nav'),
('nav.saved_searches', 'Saved searches', 'nav'),
('nav.profile', 'My Profile', 'nav'),
('nav.my_proposals', 'My Proposals', 'nav'),
('nav.my_notes', 'My Notes', 'nav'),
('nav.admin', 'Admin', 'nav'),
('nav.logout', 'Logout', 'nav'),
('login.title', 'Login', 'login'),
('login.setup_title', 'Create admin user', 'login'),
('login.subtitle', 'Sign in to access Family memories.', 'login'),
('login.setup_subtitle', 'Set up the initial admin account.', 'login'),
('login.username', 'Username', 'login'),
('login.password', 'Password', 'login'),
('login.button', 'Login', 'login'),
('login.setup_username', 'Admin username', 'login'),
('login.setup_button', 'Create admin', 'login'),
('profile.title', 'My Profile', 'profile'),
('profile.subtitle', 'Adjust your default preferences.', 'profile'),
('profile.default_view', 'Default view', 'profile'),
('profile.page_size', 'Page size', 'profile'),
('profile.thumb_size', 'Thumb size', 'profile'),
('profile.sort_mode', 'Sort mode', 'profile'),
('profile.ui_language', 'UI language', 'profile'),
('profile.save', 'Save', 'profile'),
('profile.saved', 'Saved', 'profile'),
('profile.change_password', 'Change password', 'profile'),
('profile.current_password', 'Current password', 'profile'),
('profile.new_password', 'New password', 'profile'),
('profile.confirm_password', 'Confirm password', 'profile'),
('profile.update_password', 'Update password', 'profile'),
('profile.password_updated', 'Password updated', 'profile'),
('search.title', 'Family memories', 'search'),
('search.subtitle', 'Query your indexer DB (read-only).', 'search'),
('search.tags', 'Tags', 'search'),
('search.tag_match', 'Tag match', 'search'),
('search.path_contains', 'Path contains', 'search'),
('search.media_ids', 'Media ID(s)', 'search'),
('search.taken', 'Taken', 'search'),
('search.date', 'Date', 'search'),
('search.start', 'Start', 'search'),
('search.end', 'End', 'search'),
('search.sort', 'Sort', 'search'),
('search.direction', 'Direction', 'search'),
('search.type', 'Type', 'search'),
('search.has_notes', 'Has notes', 'search'),
('search.extension', 'Extension', 'search'),
('search.only_favorites', 'Only favorites', 'search'),
('search.limit', 'Limit', 'search'),
('search.view', 'View', 'search'),
('search.button', 'Search', 'search'),
('search.save_search', 'Save search', 'search'),
('search.tag_changes', 'Tag changes', 'search'),
('search.clear_criteria', 'Clear search criteria', 'search'),
('search.debug_sql', 'Debug SQL', 'search'),
('search.download_selected', 'Download selected', 'search'),
('search.batch_tag_edit', 'Batch tag edit', 'search'),
('search.unselect_all', 'Unselect all', 'search'),
('search.results_empty', 'Results: —', 'search'),
('history.title', 'Tag changes', 'history'),
('history.media_ids', 'Media ID(s)', 'history'),
('history.limit', 'Limit', 'history'),
('history.load', 'Load', 'history'),
('history.preview', 'Preview', 'history'),
('history.restore', 'Restore original', 'history')
ON DUPLICATE KEY UPDATE
  default_en = VALUES(default_en),
  context = VALUES(context),
  updated_at = CURRENT_TIMESTAMP;

INSERT INTO wa_ui_translations (ui_string_id, language_code, translated_value, is_final)
SELECT s.id, 'hu', x.translated_value, 1
FROM wa_ui_strings s
JOIN (
  SELECT 'app.brand' AS string_key, 'Családi emlékek' AS translated_value UNION ALL
  SELECT 'nav.search', 'Keresés' UNION ALL
  SELECT 'nav.tags', 'Címkék' UNION ALL
  SELECT 'nav.favorites', 'Kedvenceim' UNION ALL
  SELECT 'nav.saved_searches', 'Mentett keresések' UNION ALL
  SELECT 'nav.profile', 'Profilom' UNION ALL
  SELECT 'nav.my_proposals', 'Javaslataim' UNION ALL
  SELECT 'nav.my_notes', 'Jegyzeteim' UNION ALL
  SELECT 'nav.admin', 'Admin' UNION ALL
  SELECT 'nav.logout', 'Kilépés' UNION ALL
  SELECT 'login.title', 'Bejelentkezés' UNION ALL
  SELECT 'login.setup_title', 'Admin felhasználó létrehozása' UNION ALL
  SELECT 'login.subtitle', 'Jelentkezzen be a Családi emlékek eléréséhez.' UNION ALL
  SELECT 'login.setup_subtitle', 'Állítsa be a kezdeti admin fiókot.' UNION ALL
  SELECT 'login.username', 'Felhasználónév' UNION ALL
  SELECT 'login.password', 'Jelszó' UNION ALL
  SELECT 'login.button', 'Bejelentkezés' UNION ALL
  SELECT 'login.setup_username', 'Admin felhasználónév' UNION ALL
  SELECT 'login.setup_button', 'Admin létrehozása' UNION ALL
  SELECT 'profile.title', 'Profilom' UNION ALL
  SELECT 'profile.subtitle', 'Állítsa be az alapértelmezett beállításait.' UNION ALL
  SELECT 'profile.default_view', 'Alapértelmezett nézet' UNION ALL
  SELECT 'profile.page_size', 'Oldalméret' UNION ALL
  SELECT 'profile.thumb_size', 'Bélyegkép mérete' UNION ALL
  SELECT 'profile.sort_mode', 'Rendezés' UNION ALL
  SELECT 'profile.ui_language', 'Felület nyelve' UNION ALL
  SELECT 'profile.save', 'Mentés' UNION ALL
  SELECT 'profile.saved', 'Mentve' UNION ALL
  SELECT 'profile.change_password', 'Jelszó módosítása' UNION ALL
  SELECT 'profile.current_password', 'Jelenlegi jelszó' UNION ALL
  SELECT 'profile.new_password', 'Új jelszó' UNION ALL
  SELECT 'profile.confirm_password', 'Jelszó megerősítése' UNION ALL
  SELECT 'profile.update_password', 'Jelszó frissítése' UNION ALL
  SELECT 'profile.password_updated', 'Jelszó frissítve' UNION ALL
  SELECT 'search.title', 'Családi emlékek' UNION ALL
  SELECT 'search.subtitle', 'Lekérdezés az indexer adatbázisból (csak olvasható).' UNION ALL
  SELECT 'search.tags', 'Címkék' UNION ALL
  SELECT 'search.tag_match', 'Címke egyezés' UNION ALL
  SELECT 'search.path_contains', 'Elérési út tartalmazza' UNION ALL
  SELECT 'search.media_ids', 'Média ID(k)' UNION ALL
  SELECT 'search.taken', 'Készült' UNION ALL
  SELECT 'search.date', 'Dátum' UNION ALL
  SELECT 'search.start', 'Kezdet' UNION ALL
  SELECT 'search.end', 'Vége' UNION ALL
  SELECT 'search.sort', 'Rendezés' UNION ALL
  SELECT 'search.direction', 'Irány' UNION ALL
  SELECT 'search.type', 'Típus' UNION ALL
  SELECT 'search.has_notes', 'Van jegyzet' UNION ALL
  SELECT 'search.extension', 'Kiterjesztés' UNION ALL
  SELECT 'search.only_favorites', 'Csak kedvencek' UNION ALL
  SELECT 'search.limit', 'Limit' UNION ALL
  SELECT 'search.view', 'Nézet' UNION ALL
  SELECT 'search.button', 'Keresés' UNION ALL
  SELECT 'search.save_search', 'Keresés mentése' UNION ALL
  SELECT 'search.tag_changes', 'Címkeváltozások' UNION ALL
  SELECT 'search.clear_criteria', 'Keresési feltételek törlése' UNION ALL
  SELECT 'search.debug_sql', 'SQL hibakeresés' UNION ALL
  SELECT 'search.download_selected', 'Kijelöltek letöltése' UNION ALL
  SELECT 'search.batch_tag_edit', 'Címkék tömeges szerkesztése' UNION ALL
  SELECT 'search.unselect_all', 'Kijelölés törlése' UNION ALL
  SELECT 'search.results_empty', 'Találatok: —' UNION ALL
  SELECT 'history.title', 'Címkeváltozások' UNION ALL
  SELECT 'history.media_ids', 'Média ID(k)' UNION ALL
  SELECT 'history.limit', 'Limit' UNION ALL
  SELECT 'history.load', 'Betöltés' UNION ALL
  SELECT 'history.preview', 'Előnézet' UNION ALL
  SELECT 'history.restore', 'Eredeti visszaállítása'
) x ON x.string_key = s.string_key
ON DUPLICATE KEY UPDATE
  translated_value = VALUES(translated_value),
  is_final = VALUES(is_final),
  updated_at = CURRENT_TIMESTAMP;

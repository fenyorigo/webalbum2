INSERT IGNORE INTO wa_ui_strings (string_key, default_en, context) VALUES
  ('nav.help', 'Help', 'nav'),
  ('help.title', 'WebAlbum - Quick User Guide', 'help'),
  ('help.quick_start.title', 'Quick start', 'help'),
  ('help.quick_start.intro', 'The easiest way to start using WebAlbum:', 'help'),
  ('help.quick_start.step1', 'Open the Tags menu', 'help'),
  ('help.quick_start.step2', 'Select an event', 'help'),
  ('help.quick_start.step3', 'Browse the images', 'help'),
  ('help.typed_tags_intro.title', 'What is a typed tag?', 'help'),
  ('help.typed_tags_intro.p1', 'Typed tags connect images to events or categories.', 'help'),
  ('help.typed_tags_intro.p2', 'They help group related images together logically, even if the files are stored in different folders.', 'help'),
  ('help.typed_tags_intro.p3', 'This makes it easier to browse and search family memories by event or topic, not only by folder.', 'help'),
  ('help.purpose.title', 'What is WebAlbum for?', 'help'),
  ('help.purpose.p1', 'WebAlbum is designed for browsing and searching family photos, videos, and documents.', 'help'),
  ('help.purpose.p2', 'Some of the images show identified members of the family. These images are marked with person-related tags.', 'help'),
  ('help.purpose.p3', 'These tags are stored directly inside the image files, so the images themselves carry this information independently of WebAlbum.', 'help'),
  ('help.tags.title', 'Tags and typed tags', 'help'),
  ('help.tags.p1', 'Images can also have typed tags that represent events or categories. Typed tags allow images to be logically connected even if they are located in different folders.', 'help'),
  ('help.tags.item1', 'Tags - tags related to people or general topics', 'help'),
  ('help.tags.item2', 'Typed tags - tags representing events or categories', 'help'),
  ('help.search.title', 'Search', 'help'),
  ('help.search.p1', 'The easiest way to find images is by using the Search page.', 'help'),
  ('help.search.item1', 'person', 'help'),
  ('help.search.item2', 'tag', 'help'),
  ('help.search.item3', 'typed tag (event or category)', 'help'),
  ('help.search.item4', 'folder', 'help'),
  ('help.search.item5', 'date or file type (image, video, etc.)', 'help'),
  ('help.image_open.title', 'Opening images', 'help'),
  ('help.image_open.p1', 'Clicking an image opens it in a larger view.', 'help'),
  ('help.image_open.item1', 'move to the next image', 'help'),
  ('help.image_open.item2', 'play a video', 'help'),
  ('help.image_open.item3', 'start a slideshow', 'help'),
  ('help.filters.title', 'Search filters', 'help'),
  ('help.filters.p1', 'The active search filters are always visible above the results list. To remove a filter, click the X icon.', 'help'),
  ('help.favorites.title', 'Favorites', 'help'),
  ('help.favorites.p1', 'Images can be marked as favorites to make them easier to find later.', 'help'),
  ('help.notes.title', 'Notes', 'help'),
  ('help.notes.p1', 'You can also add notes to an image, for example if:', 'help'),
  ('help.notes.item1', 'someone is incorrectly tagged', 'help'),
  ('help.notes.item2', 'some information is missing', 'help'),
  ('help.notes.item3', 'you want to add a story or context', 'help'),
  ('help.important.title', 'Important', 'help'),
  ('help.important.p1', 'WebAlbum does not modify the original image files.', 'help'),
  ('help.important.p2', 'The goal of the system is to help organize, find, and browse family memories.', 'help');

INSERT INTO wa_ui_translations (ui_string_id, language_code, translated_value, is_final, updated_by_user_id)
SELECT s.id, 'hu', t.translated_value, 1, NULL
FROM wa_ui_strings s
JOIN (
  SELECT 'nav.help' AS string_key, 'Súgó' AS translated_value UNION ALL
  SELECT 'help.title', 'WebAlbum – Rövid használati útmutató' UNION ALL
  SELECT 'help.quick_start.title', 'Gyors kezdés' UNION ALL
  SELECT 'help.quick_start.intro', 'A WebAlbum használatának legegyszerűbb módja:' UNION ALL
  SELECT 'help.quick_start.step1', 'Nyisd meg a Címkék menüt' UNION ALL
  SELECT 'help.quick_start.step2', 'Válassz egy eseményt' UNION ALL
  SELECT 'help.quick_start.step3', 'Böngészd a képeket' UNION ALL
  SELECT 'help.typed_tags_intro.title', 'Mi az a típusos címke?' UNION ALL
  SELECT 'help.typed_tags_intro.p1', 'A típusos címkék olyan címkék, amelyek a képeket eseményekhez vagy kategóriákhoz kapcsolják.' UNION ALL
  SELECT 'help.typed_tags_intro.p2', 'Segítségükkel a különböző mappákban található képek logikai kapcsolatba rendezhetők.' UNION ALL
  SELECT 'help.typed_tags_intro.p3', 'Így a családi emlékek nemcsak mappák szerint, hanem események és témák szerint is könnyebben böngészhetők és kereshetők.' UNION ALL
  SELECT 'help.purpose.title', 'Mire való a WebAlbum?' UNION ALL
  SELECT 'help.purpose.p1', 'A WebAlbum a családi képek, videók és dokumentumok böngészésére és keresésére szolgál.' UNION ALL
  SELECT 'help.purpose.p2', 'A rendszerben található képek egy részén a család felismert és azonosított tagjai láthatók. Ezeket a képeket személyhez kapcsolódó címkék jelölik.' UNION ALL
  SELECT 'help.purpose.p3', 'Ezek a címkék magukban a képállományokban vannak eltárolva, így a képek a WebAlbumtól függetlenül is hordozzák ezeket az információkat.' UNION ALL
  SELECT 'help.tags.title', 'Címkék és típusos címkék' UNION ALL
  SELECT 'help.tags.p1', 'A képekhez ezen kívül úgynevezett típusos címkék is rendelhetők, amelyek eseményekhez vagy kategóriákhoz kapcsolódnak. A típusos címkék segítségével a képek logikai kapcsolatba kerülnek egymással, akkor is, ha fizikailag különböző mappákban találhatók.' UNION ALL
  SELECT 'help.tags.item1', 'Címkék – személyekhez vagy egyéb témákhoz kapcsolódó címkék' UNION ALL
  SELECT 'help.tags.item2', 'Típusos címkék – eseményekhez vagy kategóriákhoz tartozó címkék' UNION ALL
  SELECT 'help.search.title', 'Keresés' UNION ALL
  SELECT 'help.search.p1', 'A képek megtalálásának legegyszerűbb módja a Keresés menüpont használata.' UNION ALL
  SELECT 'help.search.item1', 'személy' UNION ALL
  SELECT 'help.search.item2', 'címke' UNION ALL
  SELECT 'help.search.item3', 'típusos címke (esemény vagy kategória)' UNION ALL
  SELECT 'help.search.item4', 'mappa' UNION ALL
  SELECT 'help.search.item5', 'dátum vagy fájltípus (kép, videó stb.)' UNION ALL
  SELECT 'help.image_open.title', 'Képek megnyitása' UNION ALL
  SELECT 'help.image_open.p1', 'Ha rákattintasz egy képre, az nagy méretben megnyílik.' UNION ALL
  SELECT 'help.image_open.item1', 'a következő képre lapozni' UNION ALL
  SELECT 'help.image_open.item2', 'videót lejátszani' UNION ALL
  SELECT 'help.image_open.item3', 'diavetítést indítani' UNION ALL
  SELECT 'help.filters.title', 'Keresési feltételek' UNION ALL
  SELECT 'help.filters.p1', 'A találati lista fölött mindig láthatók az aktuális keresési feltételek. Ha egy feltételt törölni szeretnél, kattints az X jelre.' UNION ALL
  SELECT 'help.favorites.title', 'Kedvencek' UNION ALL
  SELECT 'help.favorites.p1', 'A képeket meg lehet jelölni kedvencként, így később könnyen megtalálhatók.' UNION ALL
  SELECT 'help.notes.title', 'Megjegyzések' UNION ALL
  SELECT 'help.notes.p1', 'Egy képhez megjegyzést is lehet írni, például ha:' UNION ALL
  SELECT 'help.notes.item1', 'valaki rosszul van megjelölve' UNION ALL
  SELECT 'help.notes.item2', 'hiányzik egy információ' UNION ALL
  SELECT 'help.notes.item3', 'történetet szeretnél hozzáfűzni' UNION ALL
  SELECT 'help.important.title', 'Fontos' UNION ALL
  SELECT 'help.important.p1', 'A WebAlbum nem módosítja az eredeti képfájlokat.' UNION ALL
  SELECT 'help.important.p2', 'A rendszer célja az, hogy segítse a családi emlékek megtalálását, rendszerezését és böngészését.'
) AS t ON t.string_key = s.string_key
LEFT JOIN wa_ui_translations x
  ON x.ui_string_id = s.id AND x.language_code = 'hu'
WHERE x.id IS NULL;

UPDATE wa_ui_strings
SET default_en = CASE string_key
  WHEN 'nav.help' THEN 'Help'
  WHEN 'help.title' THEN 'WebAlbum - Quick User Guide'
  WHEN 'help.quick_start.title' THEN 'Quick start'
  WHEN 'help.quick_start.intro' THEN 'The easiest way to start using WebAlbum:'
  WHEN 'help.quick_start.step1' THEN 'Open the Tags menu'
  WHEN 'help.quick_start.step2' THEN 'Select an event'
  WHEN 'help.quick_start.step3' THEN 'Browse the images'
  WHEN 'help.typed_tags_intro.title' THEN 'What is a typed tag?'
  WHEN 'help.typed_tags_intro.p1' THEN 'Typed tags connect images to events or categories.'
  WHEN 'help.typed_tags_intro.p2' THEN 'They help group related images together logically, even if the files are stored in different folders.'
  WHEN 'help.typed_tags_intro.p3' THEN 'This makes it easier to browse and search family memories by event or topic, not only by folder.'
  WHEN 'help.purpose.title' THEN 'What is WebAlbum for?'
  WHEN 'help.purpose.p1' THEN 'WebAlbum is designed for browsing and searching family photos, videos, and documents.'
  WHEN 'help.purpose.p2' THEN 'Some of the images show identified members of the family. These images are marked with person-related tags.'
  WHEN 'help.purpose.p3' THEN 'These tags are stored directly inside the image files, so the images themselves carry this information independently of WebAlbum.'
  WHEN 'help.tags.title' THEN 'Tags and typed tags'
  WHEN 'help.tags.p1' THEN 'Images can also have typed tags that represent events or categories. Typed tags allow images to be logically connected even if they are located in different folders.'
  WHEN 'help.tags.item1' THEN 'Tags - tags related to people or general topics'
  WHEN 'help.tags.item2' THEN 'Typed tags - tags representing events or categories'
  WHEN 'help.search.title' THEN 'Search'
  WHEN 'help.search.p1' THEN 'The easiest way to find images is by using the Search page.'
  WHEN 'help.search.item1' THEN 'person'
  WHEN 'help.search.item2' THEN 'tag'
  WHEN 'help.search.item3' THEN 'typed tag (event or category)'
  WHEN 'help.search.item4' THEN 'folder'
  WHEN 'help.search.item5' THEN 'date or file type (image, video, etc.)'
  WHEN 'help.image_open.title' THEN 'Opening images'
  WHEN 'help.image_open.p1' THEN 'Clicking an image opens it in a larger view.'
  WHEN 'help.image_open.item1' THEN 'move to the next image'
  WHEN 'help.image_open.item2' THEN 'play a video'
  WHEN 'help.image_open.item3' THEN 'start a slideshow'
  WHEN 'help.filters.title' THEN 'Search filters'
  WHEN 'help.filters.p1' THEN 'The active search filters are always visible above the results list. To remove a filter, click the X icon.'
  WHEN 'help.favorites.title' THEN 'Favorites'
  WHEN 'help.favorites.p1' THEN 'Images can be marked as favorites to make them easier to find later.'
  WHEN 'help.notes.title' THEN 'Notes'
  WHEN 'help.notes.p1' THEN 'You can also add notes to an image, for example if:'
  WHEN 'help.notes.item1' THEN 'someone is incorrectly tagged'
  WHEN 'help.notes.item2' THEN 'some information is missing'
  WHEN 'help.notes.item3' THEN 'you want to add a story or context'
  WHEN 'help.important.title' THEN 'Important'
  WHEN 'help.important.p1' THEN 'WebAlbum does not modify the original image files.'
  WHEN 'help.important.p2' THEN 'The goal of the system is to help organize, find, and browse family memories.'
  ELSE default_en
END
WHERE string_key IN (
  'nav.help',
  'help.title',
  'help.quick_start.title',
  'help.quick_start.intro',
  'help.quick_start.step1',
  'help.quick_start.step2',
  'help.quick_start.step3',
  'help.typed_tags_intro.title',
  'help.typed_tags_intro.p1',
  'help.typed_tags_intro.p2',
  'help.typed_tags_intro.p3',
  'help.purpose.title',
  'help.purpose.p1',
  'help.purpose.p2',
  'help.purpose.p3',
  'help.tags.title',
  'help.tags.p1',
  'help.tags.item1',
  'help.tags.item2',
  'help.search.title',
  'help.search.p1',
  'help.search.item1',
  'help.search.item2',
  'help.search.item3',
  'help.search.item4',
  'help.search.item5',
  'help.image_open.title',
  'help.image_open.p1',
  'help.image_open.item1',
  'help.image_open.item2',
  'help.image_open.item3',
  'help.filters.title',
  'help.filters.p1',
  'help.favorites.title',
  'help.favorites.p1',
  'help.notes.title',
  'help.notes.p1',
  'help.notes.item1',
  'help.notes.item2',
  'help.notes.item3',
  'help.important.title',
  'help.important.p1',
  'help.important.p2'
);

INSERT INTO wa_ui_translations (ui_string_id, language_code, translated_value, is_final, updated_by_user_id)
SELECT s.id, 'hu', t.translated_value, 1, NULL
FROM wa_ui_strings s
JOIN (
  SELECT 'help.login.step3' AS string_key, 'Kattintson a "Bejelentkezés" gombra.' AS translated_value UNION ALL
  SELECT 'help.login.p1', 'Sikeres belépés után a rendszer a Keresés oldalt nyitja meg.' UNION ALL
  SELECT 'help.nav.item1', 'Keresés - keresés indítása és szűrése' UNION ALL
  SELECT 'help.nav.item2', 'Címkék - a címkék listája' UNION ALL
  SELECT 'help.nav.item3', 'Típusos címkék - típusos címkék böngészése' UNION ALL
  SELECT 'help.nav.item4', 'Kedvenceim - saját kedvencek' UNION ALL
  SELECT 'help.nav.item5', 'Mentett keresések - mentett lekérdezések' UNION ALL
  SELECT 'help.nav.item6', 'Súgó - ez a felhasználói útmutató' UNION ALL
  SELECT 'help.nav.item7', 'Profilom - személyes beállítások és jelszócsere' UNION ALL
  SELECT 'help.nav.item8', 'Javaslataim - saját javaslatok' UNION ALL
  SELECT 'help.nav.item9', 'Jegyzeteim - saját megjegyzések' UNION ALL
  SELECT 'help.nav.p2', 'A jobb felső sarok mutatja az aktuális felhasználót és a "Kilépés" gombot.' UNION ALL
  SELECT 'help.files.p1', 'A Keresés oldalon a "Típus" szűrő ezekre a fő csoportokra tud szűkíteni:' UNION ALL
  SELECT 'help.files.type1', 'Képek' UNION ALL
  SELECT 'help.files.type2', 'Videók' UNION ALL
  SELECT 'help.files.type3', 'Audió' UNION ALL
  SELECT 'help.files.type4', 'Dokumentumok' UNION ALL
  SELECT 'help.files.p2', 'A "Kiterjesztés" szűrő jelenleg ezeket a kiterjesztéseket támogatja:' UNION ALL
  SELECT 'help.search.p1', 'A központi munkafelület a Keresés oldal. Bal oldalon mappafa, középen a keresési feltételek és a találatok láthatók.' UNION ALL
  SELECT 'help.search.item1', 'Címkék - egy vagy több címke megadása "ÉS" vagy "ÉS NEM" logikával' UNION ALL
  SELECT 'help.search.item2', 'Címkeillesztés - a pozitív címkéknél az "Összes" vagy "Bármelyik" kapcsolat használata' UNION ALL
  SELECT 'help.search.item3', 'Elérési út tartalmazza - szöveg keresése a mappaútvonalban vagy a fájlnévben' UNION ALL
  SELECT 'help.search.item4', 'Média ID(k) - keresés pontos azonosító alapján' UNION ALL
  SELECT 'help.search.item5', 'Típusos címke - keresés típusos címke alapján' UNION ALL
  SELECT 'help.search.item6', 'Készült - dátumszűrés "Utána", "Előtte" vagy "Között" módban' UNION ALL
  SELECT 'help.search.item7', 'Típus - kép, videó, hang vagy dokumentum' UNION ALL
  SELECT 'help.search.item8', 'Kiterjesztés - dokumentum- vagy hangfájl-kiterjesztés' UNION ALL
  SELECT 'help.search.item9', 'Van jegyzet - csak olyan elemek, amelyekhez már tartozik megjegyzés' UNION ALL
  SELECT 'help.search.item10', 'Csak kedvencek - keresés csak a saját kedvencek között' UNION ALL
  SELECT 'help.typed.item1', 'a Keresés oldalon a "Típusos címke" mezőben' UNION ALL
  SELECT 'help.typed.item2', 'a "Típusos címkék" oldalon a fa böngészésével' UNION ALL
  SELECT 'help.typed.p3', 'Ha a "Leszármazottak bevonása" be van kapcsolva, a keresés a kijelölt típusos címke alcímkéire is kiterjed.' UNION ALL
  SELECT 'help.results.p1', 'A találatok a "Rendezés" és "Irány" mezőkkel rendezhetők.' UNION ALL
  SELECT 'help.results.item4', 'Lista - táblázatos elrendezés' UNION ALL
  SELECT 'help.results.item5', 'Rács - bélyegképes rácsnézet' UNION ALL
  SELECT 'help.download.p1', 'A találatok jelölőnégyzetekkel kijelölhetők, majd a "Kijelöltek letöltése" gombbal ZIP fájlban letölthetők.' UNION ALL
  SELECT 'help.collab.p1', 'Minden találatból megnyitható az "Objektum" oldal. Itt lehet megjegyzéseket és változtatási javaslatokat rögzíteni.' UNION ALL
  SELECT 'help.collab.p4', 'A saját javaslatok a "Javaslataim", a saját megjegyzések a "Jegyzeteim" oldalon követhetők.' UNION ALL
  SELECT 'help.saved.p1', 'Az aktuális keresési feltételek a "Keresés mentése" gombbal elmenthetők.' UNION ALL
  SELECT 'help.saved.p2', 'A "Mentett keresések" oldalon egy mentett keresés:' UNION ALL
  SELECT 'help.profile.p1', 'A "Profilom" oldalon a felhasználó a saját beállításait módosíthatja:' UNION ALL
  SELECT 'help.important.item4', 'Kijelentkezni a jobb felső sarokban lévő "Kilépés" gombbal lehet.'
) AS t ON t.string_key = s.string_key
LEFT JOIN wa_ui_translations x
  ON x.ui_string_id = s.id AND x.language_code = 'hu'
WHERE x.id IS NULL;

UPDATE wa_ui_translations t
JOIN wa_ui_strings s ON s.id = t.ui_string_id
SET t.translated_value = CASE s.string_key
  WHEN 'help.login.step3' THEN 'Kattintson a "Bejelentkezés" gombra.'
  WHEN 'help.login.p1' THEN 'Sikeres belépés után a rendszer a Keresés oldalt nyitja meg.'
  WHEN 'help.nav.item1' THEN 'Keresés - keresés indítása és szűrése'
  WHEN 'help.nav.item2' THEN 'Címkék - a címkék listája'
  WHEN 'help.nav.item3' THEN 'Típusos címkék - típusos címkék böngészése'
  WHEN 'help.nav.item4' THEN 'Kedvenceim - saját kedvencek'
  WHEN 'help.nav.item5' THEN 'Mentett keresések - mentett lekérdezések'
  WHEN 'help.nav.item6' THEN 'Súgó - ez a felhasználói útmutató'
  WHEN 'help.nav.item7' THEN 'Profilom - személyes beállítások és jelszócsere'
  WHEN 'help.nav.item8' THEN 'Javaslataim - saját javaslatok'
  WHEN 'help.nav.item9' THEN 'Jegyzeteim - saját megjegyzések'
  WHEN 'help.nav.p2' THEN 'A jobb felső sarok mutatja az aktuális felhasználót és a "Kilépés" gombot.'
  WHEN 'help.files.p1' THEN 'A Keresés oldalon a "Típus" szűrő ezekre a fő csoportokra tud szűkíteni:'
  WHEN 'help.files.type1' THEN 'Képek'
  WHEN 'help.files.type2' THEN 'Videók'
  WHEN 'help.files.type3' THEN 'Audió'
  WHEN 'help.files.type4' THEN 'Dokumentumok'
  WHEN 'help.files.p2' THEN 'A "Kiterjesztés" szűrő jelenleg ezeket a kiterjesztéseket támogatja:'
  WHEN 'help.search.p1' THEN 'A központi munkafelület a Keresés oldal. Bal oldalon mappafa, középen a keresési feltételek és a találatok láthatók.'
  WHEN 'help.search.item1' THEN 'Címkék - egy vagy több címke megadása "ÉS" vagy "ÉS NEM" logikával'
  WHEN 'help.search.item2' THEN 'Címkeillesztés - a pozitív címkéknél az "Összes" vagy "Bármelyik" kapcsolat használata'
  WHEN 'help.search.item3' THEN 'Elérési út tartalmazza - szöveg keresése a mappaútvonalban vagy a fájlnévben'
  WHEN 'help.search.item4' THEN 'Média ID(k) - keresés pontos azonosító alapján'
  WHEN 'help.search.item5' THEN 'Típusos címke - keresés típusos címke alapján'
  WHEN 'help.search.item6' THEN 'Készült - dátumszűrés "Utána", "Előtte" vagy "Között" módban'
  WHEN 'help.search.item7' THEN 'Típus - kép, videó, hang vagy dokumentum'
  WHEN 'help.search.item8' THEN 'Kiterjesztés - dokumentum- vagy hangfájl-kiterjesztés'
  WHEN 'help.search.item9' THEN 'Van jegyzet - csak olyan elemek, amelyekhez már tartozik megjegyzés'
  WHEN 'help.search.item10' THEN 'Csak kedvencek - keresés csak a saját kedvencek között'
  WHEN 'help.typed.item1' THEN 'a Keresés oldalon a "Típusos címke" mezőben'
  WHEN 'help.typed.item2' THEN 'a "Típusos címkék" oldalon a fa böngészésével'
  WHEN 'help.typed.p3' THEN 'Ha a "Leszármazottak bevonása" be van kapcsolva, a keresés a kijelölt típusos címke alcímkéire is kiterjed.'
  WHEN 'help.results.p1' THEN 'A találatok a "Rendezés" és "Irány" mezőkkel rendezhetők.'
  WHEN 'help.results.item4' THEN 'Lista - táblázatos elrendezés'
  WHEN 'help.results.item5' THEN 'Rács - bélyegképes rácsnézet'
  WHEN 'help.download.p1' THEN 'A találatok jelölőnégyzetekkel kijelölhetők, majd a "Kijelöltek letöltése" gombbal ZIP fájlban letölthetők.'
  WHEN 'help.collab.p1' THEN 'Minden találatból megnyitható az "Objektum" oldal. Itt lehet megjegyzéseket és változtatási javaslatokat rögzíteni.'
  WHEN 'help.collab.p4' THEN 'A saját javaslatok a "Javaslataim", a saját megjegyzések a "Jegyzeteim" oldalon követhetők.'
  WHEN 'help.saved.p1' THEN 'Az aktuális keresési feltételek a "Keresés mentése" gombbal elmenthetők.'
  WHEN 'help.saved.p2' THEN 'A "Mentett keresések" oldalon egy mentett keresés:'
  WHEN 'help.profile.p1' THEN 'A "Profilom" oldalon a felhasználó a saját beállításait módosíthatja:'
  WHEN 'help.important.item4' THEN 'Kijelentkezni a jobb felső sarokban lévő "Kilépés" gombbal lehet.'
  ELSE t.translated_value
END,
t.is_final = 1
WHERE t.language_code = 'hu'
  AND s.string_key IN (
    'help.login.step3',
    'help.login.p1',
    'help.nav.item1',
    'help.nav.item2',
    'help.nav.item3',
    'help.nav.item4',
    'help.nav.item5',
    'help.nav.item6',
    'help.nav.item7',
    'help.nav.item8',
    'help.nav.item9',
    'help.nav.p2',
    'help.files.p1',
    'help.files.type1',
    'help.files.type2',
    'help.files.type3',
    'help.files.type4',
    'help.files.p2',
    'help.search.p1',
    'help.search.item1',
    'help.search.item2',
    'help.search.item3',
    'help.search.item4',
    'help.search.item5',
    'help.search.item6',
    'help.search.item7',
    'help.search.item8',
    'help.search.item9',
    'help.search.item10',
    'help.typed.item1',
    'help.typed.item2',
    'help.typed.p3',
    'help.results.p1',
    'help.results.item4',
    'help.results.item5',
    'help.download.p1',
    'help.collab.p1',
    'help.collab.p4',
    'help.saved.p1',
    'help.saved.p2',
    'help.profile.p1',
    'help.important.item4'
  );

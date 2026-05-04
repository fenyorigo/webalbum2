# WebAlbum felhasználói kézikönyv

Ez a dokumentum a beépített `Help` oldal tartalmi alapja. A cél, hogy a súgóoldal és ez a kézikönyv ugyanazokat a normál felhasználói folyamatokat és fogalmakat írja le.

## 1. Mire való a WebAlbum?

A WebAlbum családi emlékek böngészésére és visszakeresésére szolgál. A rendszerben nemcsak fényképek, hanem videók, hangállományok és dokumentumok is megjelenhetnek. A felhasználó fő feladatai általában ezek:

- belépés a rendszerbe,
- keresés különböző szempontok szerint,
- találatok böngészése lista- vagy rácsnézetben,
- előnézet megnyitása,
- kijelölés és letöltés,
- kedvencek használata,
- megjegyzések és változtatási javaslatok rögzítése,
- keresések mentése,
- saját profil és jelszó kezelése.

Ez a kézikönyv kizárólag a normál felhasználói funkciókat ismerteti. Az adminisztrátori lehetőségek nem részei ennek a leírásnak.

## 2. Belépés

A bejelentkezés a `Login` oldalon történik.

1. Adja meg a felhasználónevét.
2. Adja meg a jelszavát.
3. Kattintson a `Login` gombra.

Sikeres belépés után a rendszer a keresőoldalra visz.

### Jelszószabályok

Jelszóváltoztatáskor az új jelszónak meg kell felelnie ezeknek a szabályoknak:

- legalább 12 karakter,
- legalább 1 kisbetű,
- legalább 1 nagybetű,
- legalább 1 szám,
- legalább 1 speciális karakter.

Ha a rendszer kötelező jelszócserét kér, belépés után csak a jelszó módosítása után lehet tovább dolgozni.

## 3. Mit lát a felhasználó belépés után?

A felső menüsorban a normál felhasználó általában ezeket a pontokat látja:

- `Search` - keresés,
- `Tags` - címkék listája és személyes láthatósági beállításai,
- `Typed Tags` - típusos címkék böngészése,
- `My Favorites` - kedvencek,
- `Saved searches` - mentett keresések,
- `Help` - rövid súgó,
- `My Profile` - személyes beállítások és jelszócsere,
- `My Proposals` - saját javaslatok állapota,
- `My Notes` - saját megjegyzések listája.

A jobb felső sarokban látható az aktuális felhasználó neve és a `Logout` gomb.

## 4. Milyen állományokat kezel a WebAlbum?

A WebAlbum többféle tartalmat tud megjeleníteni:

- képek,
- videók,
- hangállományok,
- dokumentumok.

### 4.1. Típusok a keresőben

A `Type` szűrőben ezek választhatók:

- `Any` - bármilyen típus,
- `Photos`,
- `Videos`,
- `Audio`,
- `Documents`.

### 4.2. Kiterjesztések

A `Extension` szűrőben jelenleg ezek a kiterjesztések választhatók:

- `pdf`
- `txt`
- `doc`
- `docx`
- `xls`
- `xlsx`
- `ppt`
- `pptx`
- `mp3`
- `m4a`
- `flac`

Fontos:

- a kiterjesztés szerinti szűrés elsősorban a dokumentum- és hangállományoknál használható,
- a képek és videók jellemzően inkább típus szerint kereshetők.

## 5. Keresés

A központi munkafelület a `Search` oldal.

Bal oldalon egy mappafa látszik, középen pedig a keresőfeltételek és a találatok.

### 5.1. Keresés címke alapján

A `Tags` mezőben több címke is megadható.

Minden sorban két mód közül lehet választani:

- `AND` - a címke legyen rajta az állományon,
- `AND NOT` - a címke ne legyen rajta az állományon.

Az `Add tag` gombbal újabb címkesor adható hozzá. Gépelés közben a rendszer javaslatokat adhat a meglévő címkékből.

### 5.2. Több címke együtt: Tag match

A `Tag match` mező szabályozza, hogy a megadott pozitív címkék hogyan működjenek együtt:

- `All` - minden megadott címkének teljesülnie kell,
- `Any` - elég, ha bármelyik teljesül.

### 5.3. Keresés útvonal vagy fájlnév alapján

A `Path contains` mező a teljes eltárolt relatív útvonalban keres.

Ez azt jelenti, hogy használható:

- mappanévre,
- útvonalrészletre,
- fájlnévrészletre is.

Példák:

- `Balaton`
- `2020/nyaralas`
- `IMG_1045`

Mivel a fájlnév is része az útvonalnak, külön fájlnévmező nélkül is lehet névrészletre keresni.

### 5.4. Keresés időtartomány szerint

A `Taken` mezővel a készítés ideje szerint lehet szűrni.

Lehetőségek:

- `After` - adott dátum után,
- `Before` - adott dátum előtt,
- `Between` - két dátum között.

A dátumformátum: `YYYY-MM-DD`.

### 5.5. Keresés típus és kiterjesztés szerint

A `Type` és az `Extension` mező együtt is használható. Például:

- csak dokumentumok,
- csak hangfájlok,
- csak PDF-ek,
- csak MP3 állományok.

### 5.6. Keresés jegyzet alapján

A `Has notes` jelölőnégyzettel csak olyan találatok kérhetők, amelyekhez már tartozik objektum-megjegyzés.

### 5.7. Keresés kedvencek között

Az `Only favorites` jelöléssel a keresés csak a saját kedvencek között fut.

### 5.8. Keresés mappa alapján

A bal oldali mappafában egy mappa kiválasztható. Ilyenkor a keresés szűkíthető az adott helyre.

Lehetőségek:

- csak a kiválasztott mappa,
- `Recursive` bekapcsolásával az almappák is.

A mappaszűrő külön jelzésként megjelenik a keresőben, és a `Clear folder filter` gombbal törölhető.

### 5.9. Keresés médiakód alapján

A `Media ID(s)` mezőbe egy vagy több azonosító is megadható, vesszővel elválasztva. Ez akkor hasznos, ha pontosan ismert, melyik elemet kell megnyitni vagy ellenőrizni.

### 5.10. Keresés indítása és törlése

- A `Search` gomb elindítja a keresést.
- A `Clear search criteria` gomb törli a feltételeket.

Az aktív szűrők a találatok felett külön kis címkékként is megjelennek. Ezekből egy-egy szűrő az `X` gombbal közvetlenül eltávolítható.

## 6. Mi az a típusos címke?

A WebAlbum kétféle címke-logikát használ:

- hagyományos címkék,
- típusos címkék.

A hagyományos címkék általában személyekhez vagy egyszerű témákhoz kapcsolódnak.

A típusos címke egy strukturált, külön típussal rendelkező címke. Segít abban, hogy az állományok ne csak mappák szerint, hanem tartalmi kapcsolat alapján is összekapcsolhatók legyenek.

Példák típusos címkékre:

- esemény,
- kategória,
- személy,
- általános témacímke.

### 6.1. Mire jó a típusos címke?

- összefoghat egy eseményt több különböző mappából,
- könnyebbé teszi a tematikus böngészést,
- használható keresésben is,
- a típusos címkék egymás alá is rendezhetők.

### 6.2. Hol használható?

- a `Search` oldalon a `Typed tag` mezőben,
- a `Typed Tags` oldalon böngészéssel,
- az objektumhoz kapcsolódó együttműködési felületeken.

### 6.3. Include descendants

Ha egy típusos címke alatt további alcímkék vannak, az `Include descendants` opcióval a keresés ezekre is kiterjeszthető.

## 7. Típusos címkék böngészése

A `Typed Tags` oldalon fa-struktúrában lehet böngészni a típusos címkéket.

Itt lehet:

- keresni a címkék nevében,
- kinyitni a hierarchiát,
- kiválasztani egy címkét,
- az adott címkéből közvetlenül keresést indítani.

Amikor a felhasználó rákattint egy címkére, a rendszer visszalép a keresőoldalra, beállítja ezt a szűrőt, és elindítja a keresést.

## 8. Címkék oldala

A `Tags` oldalon a felhasználó a címkék listáját láthatja.

Normál felhasználóként itt elsősorban ezek hasznosak:

- címkék keresése,
- a címkelista lapozása,
- személyes engedélyezés vagy elrejtés.

Egy címke személyes letiltása azt eredményezi, hogy az adott címke a saját keresésekben elrejthető, így a találatok tisztábbá tehetők.

## 9. Találatok rendezése

A keresőoldalon a `Sort` és `Direction` mezőkkel lehet rendezni a találatokat.

Rendezési mezők:

- `Path`
- `Taken`

Irányok:

- növekvő,
- csökkenő.

Példák:

- útvonal szerint A-Z,
- útvonal szerint Z-A,
- készítés szerint régebbitől az újabb felé,
- készítés szerint újabbtól a régebbi felé.

## 10. Találatok megjelenítése

A találatok kétféleképpen jeleníthetők meg:

- `List` - táblázatos lista,
- `Grid` - rácsnézet.

Mindkét nézetben elérhető:

- az előnézet megnyitása,
- kijelölés,
- közvetlen hivatkozás másolása,
- az objektumlap megnyitása.

### 10.1. Lista nézet

A lista nézetben jól áttekinthetők:

- az útvonal,
- a típus,
- a készítési dátum,
- az azonosító.

### 10.2. Rácsnézet

A rácsnézet vizuális böngészéshez kényelmesebb. Itt a bélyegképek hangsúlyosabbak.

## 11. Kijelölés és letöltés

A találatok kijelölhetők jelölőnégyzettel.

Ezután a `Download selected` gombbal egy ZIP fájl tölthető le.

Fontos korlát:

- egyszerre legfeljebb 20 kijelölt állomány tölthető le ZIP-ben.

Ez a letöltés képekre, videókra, dokumentumokra és hangállományokra is használható, ha azok a találati listában szerepelnek.

## 12. Előnézet és navigálás

Az állományra kattintva előnézet nyílik.

### 12.1. Képek

A képelőnézetben elérhető:

- előző és következő találat,
- link másolása,
- letöltés,
- objektumlap megnyitása,
- diavetítés indítása,
- többoldalas állomány esetén oldalak közötti léptetés.

### 12.2. Videók

A videóelőnézetben elérhető:

- lejátszás és megállítás,
- előző és következő találat,
- objektumlap megnyitása,
- diavetítés.

### 12.3. Hangállományok

Hangfájl esetén beépített lejátszó jelenik meg. A találatok között innen is lehet előre és hátra lépni.

### 12.4. Dokumentumok

Dokumentumoknál az előnézet beágyazott nézetben jelenik meg. A dokumentumok között ugyanúgy lehet lépkedni, mint más találatoknál.

### 12.5. Diavetítés

A diavetítés a találati lista sorrendjében halad.

- a megjelenítési idő másodpercben állítható,
- képeknél az időzítés szerint vált,
- hang- és videóállományoknál a lejátszás végén lép tovább.

## 13. Kedvencek

Az állományok csillaggal megjelölhetők kedvencként.

Erre két fő használati mód van:

- a keresőtalálatok között,
- a `My Favorites` oldalon.

A `My Favorites` oldalon a felhasználó csak a saját kedvenceit látja, és azokat külön is rendezheti és böngészheti.

## 14. Objektumlap: megjegyzések és javaslatok

Minden találatból megnyitható az `Object` oldal. Ez az együttműködési felület, ahol egy állományhoz kapcsolódó megjegyzések és változtatási javaslatok kezelhetők.

Az objektum azonosítása SHA-256 alapon történik, vagyis a rendszer ugyanahhoz a tartalomhoz kapcsolja a jegyzeteket és javaslatokat.

### 14.1. Megjegyzések

Az objektumlapon a felhasználó:

- új megjegyzést írhat,
- saját megjegyzését szerkesztheti,
- saját megjegyzését törölheti,
- láthatja más megjegyzéseit is.

A `My Notes` oldalon a saját megjegyzések egy helyen visszakereshetők.

### 14.2. Javaslatok

Az objektumlapon új változtatási javaslat is rögzíthető.

Jelenleg választható javaslattípusok:

- `retag`
- `rotate_left`
- `rotate_right`
- `annotate`
- `transform`
- `restore_metadata`

A javaslathoz szöveges indoklás vagy részletezés is megadható.

### 14.3. A javaslat folyamata

Egy javaslat tipikus életútja:

1. a felhasználó rögzíti a javaslatot,
2. a javaslat `pending` állapotba kerül,
3. később jóváhagyható vagy elutasítható,
4. szükség esetén feldolgozási lépés is kapcsolódhat hozzá,
5. a felhasználó a saját függő javaslatát vissza is vonhatja.

Lehetséges állapotok:

- `pending`
- `approved`
- `rejected`
- `cancelled`

### 14.4. Hol követhetők a javaslatok?

- az adott objektum oldalán,
- a `My Proposals` oldalon.

## 15. Keresés mentése és későbbi elővétele

A `Search` oldalon az aktuális keresés elmenthető a `Save search` gombbal.

Mentéskor a rendszer eltárolja például:

- a címkéket,
- a típusos címkét,
- az időszűrést,
- az útvonalfeltételt,
- a típust,
- a kiterjesztést,
- a kedvenc- és jegyzetszűrőt,
- a rendezést.

### 15.1. Mentett keresések oldala

A `Saved searches` oldalon minden mentett kereséshez ezek a műveletek érhetők el:

- `Run` - azonnali futtatás,
- `Load` - betöltés a keresőbe,
- `Rename` - átnevezés,
- `Delete` - törlés.

## 16. Személyes profil

A `My Profile` oldalon a felhasználó a saját alapbeállításait módosíthatja.

### 16.1. Beállítható elemek

- alapértelmezett nézet: lista vagy rács,
- oldalméret,
- bélyegkép-méret,
- alapértelmezett rendezési mód,
- felhasználói felület nyelve.

Ezek a beállítások később a kereső és a kedvencek oldal működésére is hatnak.

### 16.2. Jelszóváltoztatás

Ugyanezen az oldalon végezhető a jelszócsere is.

Ehhez szükséges:

- a jelenlegi jelszó,
- az új jelszó,
- az új jelszó megerősítése.

## 17. Kijelentkezés

A jobb felső sarokban található `Logout` gombbal lehet kijelentkezni.

## 18. Gyakorlati használati minták

### 18.1. Keresés személy és időszak alapján

1. Adja meg a címkét a `Tags` mezőben.
2. Állítsa a `Taken` mezőt `Between` értékre.
3. Adja meg a kezdő és záró dátumot.
4. Kattintson a `Search` gombra.

### 18.2. Keresés fájlnévrészlet alapján

1. A `Path contains` mezőbe írjon be egy jellegzetes szövegrészletet.
2. Szükség esetén állítson be típust is.
3. Indítsa el a keresést.

### 18.3. Esemény szerinti keresés

1. Nyissa meg a `Typed Tags` oldalt.
2. Keresse meg az eseményt.
3. Kattintson rá.
4. A rendszer visszavisz a keresőoldalra és lefuttatja a keresést.

### 18.4. Javaslat küldése hibás tartalomhoz

1. Nyissa meg az állományt.
2. Válassza az `Object` gombot.
3. A `Change Proposals` résznél válassza ki a javaslat típusát.
4. Írja le röviden, mit kellene javítani.
5. Küldje be a javaslatot.

## 19. Fontos tudnivalók

- A találatok több oldalon jelenhetnek meg, ezért érdemes figyelni a lapozásra.
- Az aktív szűrők külön eltávolíthatók a találati lista feletti sávból.
- Egy keresés elmentése nem az állományokat, hanem a keresési feltételeket menti el.
- A kedvencek és a mentett keresések felhasználónként külön tárolódnak.
- A letöltési ZIP egyszerre legfeljebb 20 kijelölt elemet tartalmazhat.

# Oldalankénti, adminból szerkeszthető SEO

**Dátum:** 2026-09-22
**Érintett repók:** `madbox-99/filament-seo` (fő), `template-for-template`, `template-for-webshop`

## Cél

Mindkét sablonprojektben legyen minden publikus oldalnak adminból szerkeszthető
SEO title-je, meta leírása és JSON-LD structured datája — beleértve azokat a fix
oldalakat is (főoldal, kapcsolat, listaoldalak, kosár, pénztár), amelyeknek ma
nincs mögöttük szerkeszthető rekord.

## Kiindulási állapot

Ami már megvan mindkét projektben:

- A `madbox-99/filament-seo` plugin regisztrálva van az admin panelen.
- Globális SEO beállítások oldal: title-minta, alapértelmezett leírás, OG kép,
  robots.txt, sitemap-kizárások, Organization schema.
- Polimorf `seo_metas` tábla, `HasSeo` trait, `SeoFields` Filament űrlapszekció.
- Per-rekord SEO: `KnowledgeArticle` és blog `Post` a template projektben;
  `Product`, `ProductCategory`, `SeoPage` és blog `Post` a webshopban.

Három hiány:

1. **A fix route-oknak nincs SEO-rekordjuk.** Címük a kódba van drótozva
   (`PageController`, `Home::render()` és társai). Érintett: `/`, `/kapcsolat`,
   `/blog`, `/tudastar`, a webshopban plusz `/termekek`, `/kosar`, `/penztar`.
2. **A JSON-LD mező nincs benne a csomag `SeoFields`-jében.** A `schema_markup`
   oszlop létezik, de a form nem adja ki; a template projektben ezért kézzel
   bemásolva szerepel a `KnowledgeArticleForm`-ban, a webshopban sehol.
3. **A meta-kiírás minden blade-ben újra van írva**, és a két projekt eltérő
   konvenciót használ (`@section`/`@yield` vs. Livewire `$title` + `$head` slot).
   A webshop layoutja canonicalt, robotsot és keywordsöt egyáltalán nem ír ki.

## Döntések

| Kérdés | Döntés |
| --- | --- |
| Hol éljen a kód | A közös `madbox-99/filament-seo` csomagban; utána v0.2.0 release és `composer update` mindkét projektben. |
| Fix oldalak tárolása | Route-kulcsos rekordok új `seo_routes` táblában, `HasSeo`-n keresztül a meglévő `seo_metas`-hoz kötve. |
| JSON-LD szerkesztés | Automatikusan generált alap + adminból szerkeszthető nyers JSON, ami kiegészíti. |
| Címkék nyelve | Lang fájlba kerülnek, `en` és `hu` fordítással. |

## Felépítés

### Csomag: adatmodell

Új tábla, neve configból felülírható, alapértelmezés `seo_routes`:

| oszlop | típus | megjegyzés |
| --- | --- | --- |
| `id` | id | |
| `route_name` | string, egyedi | Laravel named route, pl. `home` |
| `label` | string | adminban megjelenő név, pl. „Főoldal” |
| `sort_order` | integer, default 0 | listasorrend |
| `timestamps` | | |

A név szándékosan **nem** `seo_pages`: az a tábla a webshopban már létezik a
tudástár-oldalakhoz, és ütközne.

A `SeoRoute` modell a meglévő `HasSeo` traitet használja, így a title,
description, keywords, canonical, noindex/nofollow, og_type és schema_markup
ugyanabban a `seo_metas` táblában él, mint a termékeké és cikkeké. Nincs második,
párhuzamos SEO-adatmodell.

A config bővül:

```php
'table_names' => [
    'seo_routes' => 'seo_routes',
],

'routes' => [
    // 'route_name' => 'Adminban megjelenő címke',
],
```

### Csomag: route-szinkronizálás

`php artisan seo:sync-routes` a configból tölti fel a táblát. Viselkedése:

- Hiányzó sorokat létrehoz, a `label`-t és `sort_order`-t frissíti.
- Ha egy felsorolt `route_name` nem létezik az alkalmazás route-jai között,
  figyelmeztetést ír és nem hozza létre a sort. Elgépelt route így nem tud némán
  elrejtőzni egy soha meg nem jelenő SEO-rekord mögött.
- Configból kivett `route_name`-hez tartozó sort **nem törli**, csak kihagyja az
  admin listájából. Egy route átnevezése így nem viszi el a már megírt SEO-szövegeket.
- Idempotens: kétszer futtatva ugyanazt az állapotot adja.

A parancs a projektek `composer setup` scriptjébe kerül, a `migrate` után.

### Csomag: űrlapmezők

A `SeoFields::schema()` három mezővel bővül:

- `og_type` — Select (`website`, `article`, `product`). Alapértelmezése `website`;
  a hívó a `SeoFields::make(defaultOgType: 'article')` paraméterrel írja felül,
  nem pedig kontextusból találgatva.
- `og_image` — FileUpload. Ehhez a `seo_metas` tábla új `og_image` oszlopot kap
  külön migrációban. Enélkül az OG kép csak globálisan lenne állítható, oldalanként
  nem — a főoldalnak viszont pont kell a saját megosztási képe.
- `schema_markup` — JSON textarea. Betöltéskor pretty-print, mentéskor tömbbé
  dekódolva. Szintaktikailag hibás JSON-t a validáció nem enged elmenteni.

Ezzel a `template-for-template` `KnowledgeArticleForm`-jából törölhető a kézzel
bemásolt blokk, a webshop `Product`, `ProductCategory` és `SeoPage` resource-a
pedig változtatás nélkül megkapja a JSON-LD mezőt.

A mezőcímkék `filament-seo::filament-seo.*` fordítási kulcsokra cserélődnek, `en`
és `hu` fájllal. A service provider `hasTranslations()`-szal publikálja őket, így
a projektek felül tudják írni.

### Csomag: Filament resource a fix oldalakhoz

`SeoRouteResource` a meglévő „SEO" navigációs csoportban:

- Lista: `label`, SEO title, van-e JSON-LD, utolsó módosítás.
- **Csak szerkesztés.** Nincs létrehozás és nincs törlés — a sorokat a config és
  a sync parancs határozza meg, így nem lehet nem létező route-ra SEO-t írni.
- A szerkesztő űrlap a `SeoFields::make()` szekciót használja, ugyanazt, amit a
  termékek és cikkek.

### Csomag: feloldó és kiírás

`Seo` singleton (facade-dal), három lépcsős visszaeséssel:

1. Explicit beállított modell `seoMeta`-ja (`Seo::for($product)`).
2. Az aktuális named route `SeoRoute` rekordjának `seoMeta`-ja.
3. Globális alapértékek a `SeoSetting`-ből.

Mezőnként esik vissza, nem rekordonként: ha egy termékhez van title, de nincs
description, a description a globális alapértelmezésből jön.

A 2. lépcső a lényeg: **ettől kapja meg a főoldal és minden más fix oldal a
szerkeszthető SEO-t anélkül, hogy egyesével bedrótoznánk őket.**

`Seo::resolve()` egy `SeoData` readonly objektumot ad: `title`, `description`,
`keywords`, `canonical`, `robots`, `ogType`, `ogImage`, `schemas`. A title-re a
globális `default_title_pattern` (`{title} | {site_name}`) alkalmazódik.

Az `ogImage` feloldása három forrásból, ebben a sorrendben: a `seoMeta->og_image`
adminban feltöltött értéke, majd a hívó által megadott kép
(`Seo::for($article)->ogImage(Storage::url($article->featured_image))`), végül a
globális `default_og_image`. Így a kiemelt kép automatikusan OG képpé válik, de az
admin felül tudja írni.

### Csomag: JSON-LD összeállítása

A csomag nem ismeri a projekt modelljeit, ezért a `ProvidesSchemaOrg` interfész
teremti meg a kapcsolatot. A metódus **blokkok listáját** adja vissza, nem egyetlen
blokkot — egy cikkoldalnak `Article` és `BreadcrumbList` schemára is szüksége van:

```php
/** @return list<array<string, mixed>> */
public function toSchemaOrg(): array;
```

A kiírt blokkok sorrendben:

1. A csomag által generált alap: a főoldalon `Organization` és `WebSite` a
   globális beállításokból.
2. A modell `toSchemaOrg()` listája, ha a modell implementálja az interfészt.
3. A `seoMeta->schema_markup`-ba írt kézi JSON, ha van.

Mindegyik külön `<script type="application/ld+json">` blokként jelenik meg. Aki
nem nyúl az adminban semmihez, az is kap érvényes alap-structured-datát.

### Csomag: head komponens

`<x-filament-seo::head />` írja ki a title-t, a meta tageket és az összes JSON-LD
blokkot. Opcionális `:title` propot fogad.

**A `<title>` kezelése projektenként eltér, és ez szándékos.** A webshopban a
címet ma a Livewire `->title()` adja, és a `wire:navigate` SPA-navigáció ezen
keresztül frissíti. Ezért:

- A webshop layoutja `<x-filament-seo::head :title="$title ?? null" />` alakban
  hívja, a Livewire komponensek pedig megtartják a `->title()` hívást, csak az
  értéket a feloldótól kérik: `->title(Seo::resolve()->title)`.
- A template projektben (nem Livewire) a komponens maga írja ki a title-t.

Ha a webshopban is a komponensre bíznánk a címet, két `<title>` tag keletkezne és
SPA-navigációnál beragadna a régi cím.

### Bekötés: template-for-template

- `config/filament-seo.php`: `home`, `contact`, `blog.index`, `tudastar.index`.
- `layouts/app.blade.php` fejrészének 25 soros `@hasSection` blokkja egyetlen
  `<x-filament-seo::head />` sorra cserélődik.
- Hét blade `@section('seo_*')` / `@section('og_*')` blokkjai törlődnek:
  `pages/home`, `pages/contact`, `pages/blog/index`, `pages/blog/show`,
  `pages/knowledge/index`, `pages/knowledge/category`, `pages/knowledge/show`.
- A `BlogController` és `KnowledgeArticleController` `Seo::for(...)`-t hív.
- `KnowledgeArticle` implementálja a `ProvidesSchemaOrg`-ot; a ma a kontrollerben
  és a blade-ben összerakott `$structuredData` és `$breadcrumbData` ide költözik.
- A `KnowledgeArticleForm`-ból törlődik a kézzel bemásolt `og_type` és
  `schema_markup` mező.

### Bekötés: template-for-webshop

- `config/filament-seo.php`: `home`, `contact`, `blog.index`, `shop.index`,
  `seo-page.index`, `cart`, `checkout`.
- `components/layouts/app.blade.php` fejrésze `<x-filament-seo::head :title="$title ?? null" />`-re
  cserélődik; a `$head` slot escape hatchként megmarad.
- A Livewire komponensek `render()`-jei `Seo::for(...)`-t hívnak, ahol van modell
  (`ProductShow`, `PostShow`, `PageShow`, kategóriás `ProductIndex` és `PostIndex`),
  és a `->title()` értéke `Seo::resolve()->title`-re vált.
- `Product` implementálja a `ProvidesSchemaOrg`-ot: `Product` schema árral,
  pénznemmel és készlet-állapottal. `ProductCategory`: `CollectionPage`.
  `SeoPage`: a ma `PageShow::structuredData()`-ban lévő `Article`/`WebPage` logika
  a modellre költözik.
- A `livewire/blog/post-show.blade.php` és `livewire/seo-pages/page-show.blade.php`
  kézi meta-blokkjai törlődnek.

## Tesztelés

**Csomag (Pest + orchestra/testbench):**

- A feloldó sorrendje: modell üti a route-ot, route üti a globálisat, és mezőnként
  esik vissza.
- A `default_title_pattern` behelyettesítése.
- A három JSON-LD forrás összefűzése, és hogy üres forrás nem ad üres blokkot.
- A sync parancs: idempotencia, nem létező route-ra figyelmeztet és nem hoz létre
  sort, configból kivett sort nem töröl.
- A `schema_markup` mező hibás JSON-nél validációs hibát ad.

**Mindkét projektben:**

- Az adminból a főoldalra írt SEO title és leírás megjelenik a `/` HTML-jében.
- Egy noindexre állított route `robots` metát kap.
- A `PublicPageTest` / `StorefrontTest` meglévő esetei nem törnek.
- A webshopban a termékoldal `Product` típusú JSON-LD-t tartalmaz árral.
- A meglévő `SeoPageSeoMetaTest` és `SitemapTest` változatlanul megy.

## Kiadás sorrendje

1. A csomag v0.2.0 fejlesztése. A két projekt addig lokális path-repóval húzza be,
   hogy ne kelljen minden lépéshez kiadni.
2. v0.2.0 kiadása. Visszafelé kompatibilis: a `SeoFields` bővül, semmi nem tűnik el.
3. `template-for-template`: `composer update`, config, `migrate`, `seo:sync-routes`,
   blade- és controller-módosítások.
4. `template-for-webshop`: ugyanez.
5. A path-repók visszavétele verziószámra.

A három szakasz (csomag, template bekötés, webshop bekötés) külön implementációs
tervet is kaphat, ha egyben túl nagynak bizonyul. A csomag szakasza mindenképp
elsőnek megy, a két bekötés utána egymástól függetlenül futtatható.

## Ismert korlátok

- A `wire:navigate` a `<title>`-t frissíti, a `<meta>` tageket nem cseréli. Ez ma
  is így van, nem ez a változtatás okozza, és a keresőket nem érinti, mert ők friss
  kéréssel jönnek. Ha valaha számítani fog, egy Livewire `navigated` eseményre kötött
  kis JS cserélheti a metákat.
- A kézi JSON-LD-t szintaktikailag validáljuk, schema.org-szemantika ellen nem.
  Érvényes JSON-ban is lehet értelmetlen schema.
- Az `seo_routes` csak named route-okat tud kezelni. Paraméteres route-ok
  (pl. `/blog/{slug}`) SEO-ja továbbra is a modellen keresztül állítható, nem itt.
- A noindexre állított oldalak **nem esnek ki automatikusan a sitemapből**. A két
  projekt `SitemapController`-e külön logika, amit ez a munka nem érint. Kézzel a
  globális beállítások „Sitemap kizárások" mezőjével lehet szinkronban tartani.
  Összekötésük külön feladat, tudatosan hatókörön kívül.

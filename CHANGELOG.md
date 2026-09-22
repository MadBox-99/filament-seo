# Changelog

## v0.2.0

### Added

- `seo_routes` tábla és `SeoRoute` modell: fix, named route-okhoz kötött SEO,
  a meglévő polimorf `seo_metas` rekordokon keresztül.
- `seo:sync-routes` artisan parancs, ami a configból tölti fel a route-listát.
- `SeoManager` feloldó és `Seo` facade: mezőnkénti visszaesés a modell SEO-járól
  az aktuális route rekordjára, onnan a globális beállításokra (title, description,
  keywords, canonical, og:type, og:image). Kivétel a `no_index`/`no_follow`: ezek
  nem esnek vissza mezőnként, hanem logikai VAGY-gyal kombinálódnak a modell és a
  route rekord között, globális alapértelmezés nélkül — ha bármelyik rekord
  noindexet/nofollow-t ír elő, az érvényesül.
- `ProvidesSchemaOrg` interfész: a modellek saját JSON-LD blokkokat adhatnak.
- `<x-filament-seo::head />` Blade komponens a meta tagek és a JSON-LD kiírására.
- `SeoRouteResource`: az „Oldalak SEO-ja" lista az admin panelen, csak szerkesztéssel.
- `og_image` oszlop a `seo_metas` táblában, hozzá FileUpload mező.
- `og_type` és `schema_markup` mező a `SeoFields` űrlapban.
- Magyar és angol fordítás minden címkéhez.

### Changed

- A `SeoFields::schema()` 6 helyett 9 mezőt ad vissza. Ha a projekted a
  korábbi kézi `og_type` / `schema_markup` mezőket a `SeoFields::make()->schema([...])`
  hívásban fűzte hozzá, azokat most **törölni kell**, különben duplán jelennek meg.
- A `SeoFields::make()` és `::schema()` új, opcionális `$defaultOgType` paramétert
  kapott. Meglévő hívások változtatás nélkül működnek.
- A `SeoManager` bindingja `singleton`-ról `scoped`-ra változott, hogy Octane
  (vagy más hosszú életű worker) alatt egy kérés `Seo::for()`/`Seo::ogImage()`
  állapota ne szivárogjon át a következő kérésbe.

### Fixed

- A `schema_markup` mező skalár JSON literált (pl. `123`, `"szöveg"`) is
  elfogadott, ami mentéskor `TypeError`-ral 500-as hibát okozott. A validáció
  most elutasítja, ha a beírt JSON nem objektum vagy tömb; a dehidratáció is
  védetté vált erre az esetre.
- Érintetlen (alapértelmezett) globális beállítások mellett a főoldal egy
  névtelen, üres `Organization` JSON-LD blokkot is kiírt. Ha a
  `schema_org_data` minden mezője üres, ez a blokk most kimarad, csak a
  `WebSite` blokk kerül ki.
- **Az OG kép feltöltési és felolvasási diszkje mostantól egy konfigból
  (`filament-seo.og_image_disk`, alapértelmezetten `public`) jön, mindhárom
  helyen: `SeoFields`, `SeoManager::imageUrl()` és a globális
  `default_og_image` feltöltés.** Korábban a globális OG kép a Filament
  alapértelmezett diszkjére (`FILESYSTEM_DISK`) töltődött fel, a
  `<meta property="og:image">` viszont mindig a `public` diszkről próbálta
  felolvasni — ha a kettő eltért, a kép csendben 404-et adott. **Meglévő
  telepítéseken előfordulhat, hogy a globális OG képet újra fel kell tölteni**,
  mert a felolvasás diszkje most explicit.
- Egy admin által üres tömbre (`[]`) állított modell-szintű `schema_markup` a
  `??` operátor miatt elnyomta a route-szintű kézi JSON-LD-t. A visszaesés
  most `filled()`-et használ, így az üres tömb üresként viselkedik.

### Known limitations

- A `wire:navigate` a `<title>`-t frissíti, a `<meta>` tageket nem cseréli.
- A kézi JSON-LD szintaktikailag validált, schema.org-szemantika ellen nem.
- A noindexre állított oldalak nem esnek ki automatikusan a sitemapből.

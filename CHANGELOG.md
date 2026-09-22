# Changelog

## v0.2.0

### Added

- `seo_routes` tábla és `SeoRoute` modell: fix, named route-okhoz kötött SEO,
  a meglévő polimorf `seo_metas` rekordokon keresztül.
- `seo:sync-routes` artisan parancs, ami a configból tölti fel a route-listát.
- `SeoManager` feloldó és `Seo` facade: mezőnkénti visszaesés a modell SEO-járól
  az aktuális route rekordjára, onnan a globális beállításokra.
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

### Known limitations

- A `wire:navigate` a `<title>`-t frissíti, a `<meta>` tageket nem cseréli.
- A kézi JSON-LD szintaktikailag validált, schema.org-szemantika ellen nem.
- A noindexre állított oldalak nem esnek ki automatikusan a sitemapből.

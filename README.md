# Filament SEO

SEO plugin for [Filament](https://filamentphp.com) v5 — meta tags, Open Graph,
Schema.org and robots control, managed from one admin panel. Global settings
live in a singleton page; per-page SEO for fixed named routes is a plain
resource. Both feed the same public API: a Blade component that renders the
`<head>` tags for any page, with most fields resolved model-first, then route,
then global defaults — robots directives are the exception (see below).

## Fix oldalak SEO-ja

Sorold fel a `config/filament-seo.php`-ban azokat a named route-okat, amelyeknek
adminból szerkeszthető SEO-t adsz:

```php
'routes' => [
    'home' => 'Főoldal',
    'contact' => 'Kapcsolat',
    'blog.index' => 'Blog',
],

'home_route' => 'home',
```

Majd futtasd:

```bash
php artisan seo:sync-routes
```

A parancs idempotens, figyelmeztet a nem létező route-okra, és a configból kivett
sorokat nem törli. Tedd be a `composer setup` scriptbe a `migrate` után.

## Kiírás

A layout `<head>` szekciójába:

```blade
<x-filament-seo::head />
```

Livewire alatt, ahol a címet a `->title()` adja, a prop is átadandó, hogy ne
keletkezzen két `<title>` tag:

```blade
<x-filament-seo::head :title="$title ?? null" />
```

Ahol van konkrét modell, add meg a feloldónak:

```php
use Illuminate\Support\Facades\Storage;
use Madbox99\FilamentSeo\Facades\Seo;

Seo::for($product, fallbackTitle: $product->name)
    ->ogImage(Storage::url($product->image));
```

A feloldás mezőnként esik vissza: a modell SEO-rekordja, majd az aktuális route
rekordja, végül a globális beállítások. Ez igaz a title, description, keywords,
canonical, og:type és og:image mezőkre.

Kivétel a `no_index` és a `no_follow`: ezek nem esnek vissza mezőnként, hanem
logikai VAGY-gyal kombinálódnak a modell és a route rekord között, és nincs
hozzájuk globális alapértelmezés. Ha akár a modell, akár a route rekordja
noindexet vagy nofollow-t ír elő, az oldal noindex/nofollow marad — a másik
rekord "engedékenyebb" beállítása nem írja felül.

A site nevét a projekt adja meg, jellemzően az `AppServiceProvider::boot()`-ban:

```php
use Madbox99\FilamentSeo\SeoManager;

SeoManager::siteNameUsing(fn () => resolve(GeneralSettings::class)->site_name);
```

## Saját JSON-LD modellenként

```php
use Illuminate\Database\Eloquent\Model;
use Madbox99\FilamentSeo\Contracts\ProvidesSchemaOrg;

class Product extends Model implements ProvidesSchemaOrg
{
    /** @return list<array<string, mixed>> */
    public function toSchemaOrg(): array
    {
        return [[
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $this->name,
        ]];
    }
}
```

# Oldalankénti SEO — csomag (v0.2.0) implementációs terv

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** A `madbox-99/filament-seo` csomag tudjon fix route-okhoz kötött, adminból szerkeszthető SEO-t (title, leírás, OG kép, JSON-LD), és egyetlen Blade komponensből kiírni az egészet.

**Architecture:** Új `seo_routes` tábla köti a Laravel named route-okat a meglévő polimorf `seo_metas` rekordokhoz a `HasSeo` traiten keresztül. Egy `SeoManager` singleton old fel mezőnként három lépcsőben (explicit modell → aktuális route → globális beállítások), és egy `<x-filament-seo::head />` anonim Blade komponens írja ki a meta tageket meg a JSON-LD blokkokat.

**Tech Stack:** PHP 8.3, Laravel 12/13, Filament v5, Spatie Laravel Package Tools, Pest 3 + Orchestra Testbench.

**Spec:** `docs/superpowers/specs/2026-09-22-oldalankenti-seo-design.md`

**Ez a terv a három szakasz közül az elsőt fedi le.** A `template-for-template` és a `template-for-webshop` bekötése külön tervet kap, és csak ezután indul.

## Global Constraints

- PHP `^8.3`, `filament/filament ^5.0`, `laravel/framework ^12.0 || ^13.0` — a `composer.json` require blokkja nem változik.
- A tábla neve **`seo_routes`**, semmiképp nem `seo_pages`: az a név a `template-for-webshop`-ban már foglalt egy másik táblára, és ütközne.
- Minden táblanév configból felülírható, a meglévő `filament-seo.table_names` minta szerint.
- **Visszafelé kompatibilis kiadás:** a `SeoFields` bővül, de meglévő mező, metódus vagy szignatúra nem tűnik el és nem vált típust.
- Fordítási kulcsok névtere `filament-seo::filament-seo.*`, `en` és `hu` fájllal. **Az `en` szövegek szó szerint a mai, kódba írt angol címkék** (`SEO Title`, `Meta Description`, `Keywords`, `Canonical URL`), hogy a meglévő assertionök ne törjenek.
- Kiindulási állapot: `./vendor/bin/pest` → **94 passed (233 assertions)**. Minden task végén ennyi vagy több teszt fut zölden, kevesebb soha.
- A sync parancs configból kivett `route_name`-hez tartozó sort **nem töröl**.
- Minden új osztály `declare(strict_types=1);` nélkül készül — a csomag meglévő forrásai sem használják, kivéve a `FilamentSeoServiceProvider`-t. Kövesd a szomszédos fájl stílusát.

---

### Task 1: `seo_routes` tábla és `SeoRoute` modell

**Files:**
- Create: `database/migrations/2026_09_22_000001_create_seo_routes_table.php`
- Create: `src/Models/SeoRoute.php`
- Create: `tests/Unit/SeoRouteTest.php`
- Modify: `config/filament-seo.php`
- Modify: `tests/TestCase.php:44-47` (a `filament-seo.table_names` beállítás)

**Interfaces:**
- Consumes: `Madbox99\FilamentSeo\Concerns\HasSeo` (létezik), `Madbox99\FilamentSeo\Models\SeoMeta` (létezik).
- Produces: `Madbox99\FilamentSeo\Models\SeoRoute` — kitöltendő mezők `route_name`, `label`, `sort_order`; `seoMeta(): MorphOne`; `static forRoute(string $routeName): ?static`. A `config('filament-seo.routes')` kulcs egy `array<string, string>` route_name → címke leképezés.

- [ ] **Step 1: Write the failing test**

`tests/Unit/SeoRouteTest.php`:

```php
<?php

use Illuminate\Database\QueryException;
use Madbox99\FilamentSeo\Models\SeoMeta;
use Madbox99\FilamentSeo\Models\SeoRoute;

it('stores a route with its label and sort order', function () {
    $route = SeoRoute::create([
        'route_name' => 'home',
        'label' => 'Főoldal',
        'sort_order' => 3,
    ]);

    expect($route->route_name)->toBe('home')
        ->and($route->label)->toBe('Főoldal')
        ->and($route->sort_order)->toBe(3);
});

it('defaults sort order to zero', function () {
    $route = SeoRoute::create(['route_name' => 'contact', 'label' => 'Kapcsolat']);

    expect($route->refresh()->sort_order)->toBe(0);
});

it('rejects a duplicate route name', function () {
    SeoRoute::create(['route_name' => 'home', 'label' => 'Főoldal']);
    SeoRoute::create(['route_name' => 'home', 'label' => 'Másik főoldal']);
})->throws(QueryException::class);

it('attaches a seo meta record through the HasSeo trait', function () {
    $route = SeoRoute::create(['route_name' => 'home', 'label' => 'Főoldal']);

    $route->seoMeta()->create(['title' => 'Kezdőlap', 'description' => 'Rövid leírás']);

    expect($route->refresh()->seoMeta)->toBeInstanceOf(SeoMeta::class)
        ->and($route->seoMeta->title)->toBe('Kezdőlap');
});

it('finds a route record by its route name', function () {
    SeoRoute::create(['route_name' => 'contact', 'label' => 'Kapcsolat']);

    expect(SeoRoute::forRoute('contact')?->label)->toBe('Kapcsolat')
        ->and(SeoRoute::forRoute('nem-letezik'))->toBeNull();
});

it('uses the configured table name', function () {
    config()->set('filament-seo.table_names.seo_routes', 'custom_seo_routes');

    expect((new SeoRoute)->getTable())->toBe('custom_seo_routes');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `./vendor/bin/pest tests/Unit/SeoRouteTest.php`
Expected: FAIL — `Class "Madbox99\FilamentSeo\Models\SeoRoute" not found`

- [ ] **Step 3: Write the migration**

`database/migrations/2026_09_22_000001_create_seo_routes_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('filament-seo.table_names.seo_routes', 'seo_routes'), function (Blueprint $table) {
            $table->id();
            $table->string('route_name')->unique();
            $table->string('label');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('filament-seo.table_names.seo_routes', 'seo_routes'));
    }
};
```

- [ ] **Step 4: Write the model**

`src/Models/SeoRoute.php`:

```php
<?php

namespace Madbox99\FilamentSeo\Models;

use Illuminate\Database\Eloquent\Model;
use Madbox99\FilamentSeo\Concerns\HasSeo;

class SeoRoute extends Model
{
    use HasSeo;

    protected $guarded = [];

    #[\Override]
    public function getTable(): string
    {
        return config('filament-seo.table_names.seo_routes', 'seo_routes');
    }

    #[\Override]
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public static function forRoute(string $routeName): ?static
    {
        return static::query()->where('route_name', $routeName)->first();
    }
}
```

- [ ] **Step 5: Extend the config**

`config/filament-seo.php` teljes tartalma a módosítás után:

```php
<?php

return [
    'table_names' => [
        'seo_metas' => 'seo_metas',
        'seo_settings' => 'seo_settings',
        'seo_routes' => 'seo_routes',
    ],

    /*
     * Fix oldalak, amelyeknek adminból szerkeszthető SEO-juk legyen.
     * Kulcs: Laravel named route neve. Érték: az adminban megjelenő címke.
     * A `php artisan seo:sync-routes` parancs ebből tölti fel a seo_routes táblát.
     */
    'routes' => [
        // 'home' => 'Főoldal',
        // 'contact' => 'Kapcsolat',
    ],

    /*
     * Az a named route, amit a csomag főoldalnak tekint. A főoldalon kerül ki
     * az Organization és a WebSite JSON-LD blokk a globális beállításokból.
     */
    'home_route' => 'home',
];
```

- [ ] **Step 6: Register the new table name in the test harness**

`tests/TestCase.php` — a `getEnvironmentSetUp()` metódusban a `filament-seo.table_names` beállítás egészüljön ki:

```php
        $app['config']->set('filament-seo.table_names', [
            'seo_metas' => 'seo_metas',
            'seo_settings' => 'seo_settings',
            'seo_routes' => 'seo_routes',
        ]);
```

- [ ] **Step 7: Run the new test to verify it passes**

Run: `./vendor/bin/pest tests/Unit/SeoRouteTest.php`
Expected: PASS — 6 passed

- [ ] **Step 8: Run the whole suite for regressions**

Run: `./vendor/bin/pest`
Expected: PASS — 100 passed (94 korábbi + 6 új)

- [ ] **Step 9: Commit**

```bash
git add config/filament-seo.php database/migrations/2026_09_22_000001_create_seo_routes_table.php src/Models/SeoRoute.php tests/Unit/SeoRouteTest.php tests/TestCase.php
git commit -m "Add the seo_routes table and SeoRoute model"
```

---

### Task 2: Fordítások, `og_image` oszlop és a bővített `SeoFields`

**Files:**
- Create: `database/migrations/2026_09_22_000002_add_og_image_to_seo_metas_table.php`
- Create: `resources/lang/en/filament-seo.php`
- Create: `resources/lang/hu/filament-seo.php`
- Modify: `src/FilamentSeoServiceProvider.php:17-22` (`configurePackage`)
- Modify: `src/Forms/SeoFields.php` (teljes csere)
- Modify: `tests/Unit/SeoFieldsTest.php:50-54` (a mezőszám-assertion) és a fájl vége (új esetek)

**Interfaces:**
- Consumes: semmi az előző taskból.
- Produces: `SeoFields::make(string $relationship = 'seoMeta', string $defaultOgType = 'website'): Section` és `SeoFields::schema(string $defaultOgType = 'website'): array` — a tömb 9 elemű, sorrendje: `title`, `description`, `keywords`, `canonical_url`, `no_index`, `no_follow`, `og_type`, `og_image`, `schema_markup`. A `seo_metas` tábla új `og_image` oszlopa stringet tárol (storage-path vagy abszolút URL).

- [ ] **Step 1: Write the failing test**

`tests/Unit/SeoFieldsTest.php` — a meglévő `it('returns correct schema fields', ...)` eset **6-ról 9-re** módosul:

```php
it('returns correct schema fields', function () {
    $schema = SeoFields::schema();

    expect($schema)->toBeArray();
    expect($schema)->toHaveCount(9);
});
```

Majd a fájl **végére** kerülnek az új esetek (a `use` blokk is bővül a `Filament\Forms\Components\FileUpload` és `Filament\Forms\Components\Select` importtal):

```php
it('schema contains an og_type select defaulting to website', function () {
    $ogType = SeoFields::schema()[6];

    expect($ogType)->toBeInstanceOf(Select::class);
    expect($ogType->getName())->toBe('og_type');
    expect($ogType->getDefaultState())->toBe('website');
});

it('lets the caller override the default og type', function () {
    $ogType = SeoFields::schema('article')[6];

    expect($ogType->getDefaultState())->toBe('article');
});

it('schema contains an og_image upload', function () {
    $ogImage = SeoFields::schema()[7];

    expect($ogImage)->toBeInstanceOf(FileUpload::class);
    expect($ogImage->getName())->toBe('og_image');
});

it('schema contains a schema_markup textarea', function () {
    $schemaMarkup = SeoFields::schema()[8];

    expect($schemaMarkup)->toBeInstanceOf(Textarea::class);
    expect($schemaMarkup->getName())->toBe('schema_markup');
});

// A JSON-validáció viselkedését szándékosan nem itt ellenőrizzük: a Filament a
// szabályokat closure-ökbe csomagolja, így a getRules() assertion törékeny lenne.
// A valódi ellenőrzés a Task 7 resource-tesztjében történik, űrlap-beküldéssel.

it('translates field labels to Hungarian', function () {
    app()->setLocale('hu');

    expect(SeoFields::schema()[0]->getLabel())->toBe('SEO cím');
    expect(SeoFields::schema()[1]->getLabel())->toBe('Meta leírás');
});

it('keeps the English labels unchanged', function () {
    app()->setLocale('en');

    expect(SeoFields::schema()[0]->getLabel())->toBe('SEO Title');
    expect(SeoFields::schema()[1]->getLabel())->toBe('Meta Description');
});

it('adds an og_image column to the seo metas table', function () {
    expect(Illuminate\Support\Facades\Schema::getColumnListing('seo_metas'))->toContain('og_image');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `./vendor/bin/pest tests/Unit/SeoFieldsTest.php`
Expected: FAIL — a mezőszám 6 marad 9 helyett, és `Undefined offset 6`.

- [ ] **Step 3: Write the migration**

`database/migrations/2026_09_22_000002_add_og_image_to_seo_metas_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(config('filament-seo.table_names.seo_metas', 'seo_metas'), function (Blueprint $table) {
            $table->string('og_image')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table(config('filament-seo.table_names.seo_metas', 'seo_metas'), function (Blueprint $table) {
            $table->dropColumn('og_image');
        });
    }
};
```

Megjegyzés: az `->after('og_type')` modifier szándékosan marad ki, mert SQLite-on nem értelmezett, és a tesztek SQLite-on futnak.

- [ ] **Step 4: Write the language files**

`resources/lang/en/filament-seo.php`:

```php
<?php

return [
    'section' => [
        'heading' => 'SEO',
    ],
    'fields' => [
        'title' => 'SEO Title',
        'description' => 'Meta Description',
        'keywords' => 'Keywords',
        'canonical_url' => 'Canonical URL',
        'no_index' => 'Hide from search engines (noindex)',
        'no_follow' => 'No follow links (nofollow)',
        'og_type' => 'OG type',
        'og_image' => 'Share image (OG)',
        'schema_markup' => 'Custom JSON-LD (schema.org)',
        'schema_markup_help' => 'Optional, valid JSON. Added next to the generated structured data.',
    ],
    'routes' => [
        'label' => 'Page SEO',
        'plural_label' => 'Page SEO',
        'columns' => [
            'label' => 'Page',
            'title' => 'SEO title',
            'has_schema' => 'JSON-LD',
            'updated_at' => 'Modified',
        ],
    ],
];
```

`resources/lang/hu/filament-seo.php`:

```php
<?php

return [
    'section' => [
        'heading' => 'SEO',
    ],
    'fields' => [
        'title' => 'SEO cím',
        'description' => 'Meta leírás',
        'keywords' => 'Kulcsszavak',
        'canonical_url' => 'Kanonikus URL',
        'no_index' => 'Elrejtés a keresők elől (noindex)',
        'no_follow' => 'Linkek nem követése (nofollow)',
        'og_type' => 'OG típus',
        'og_image' => 'Megosztási kép (OG)',
        'schema_markup' => 'Egyedi JSON-LD (schema.org)',
        'schema_markup_help' => 'Opcionális, érvényes JSON. Az automatikusan generált structured data mellé kerül.',
    ],
    'routes' => [
        'label' => 'Oldal SEO',
        'plural_label' => 'Oldalak SEO-ja',
        'columns' => [
            'label' => 'Oldal',
            'title' => 'SEO cím',
            'has_schema' => 'JSON-LD',
            'updated_at' => 'Módosítva',
        ],
    ],
];
```

- [ ] **Step 5: Register translations in the service provider**

`src/FilamentSeoServiceProvider.php` — a `configurePackage()` metódusban a lánc egészüljön ki a `->hasTranslations()` hívással:

```php
    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->discoversMigrations()
            ->runsMigrations();
    }
```

- [ ] **Step 6: Rewrite SeoFields**

`src/Forms/SeoFields.php` teljes tartalma:

```php
<?php

namespace Madbox99\FilamentSeo\Forms;

use Filament\Forms;
use Filament\Schemas;

class SeoFields
{
    public static function make(string $relationship = 'seoMeta', string $defaultOgType = 'website'): Schemas\Components\Section
    {
        return Schemas\Components\Section::make(__('filament-seo::filament-seo.section.heading'))
            ->relationship($relationship)
            ->schema(static::schema($defaultOgType))
            ->collapsed();
    }

    /** @return array<Forms\Components\Component> */
    public static function schema(string $defaultOgType = 'website'): array
    {
        return [
            Forms\Components\TextInput::make('title')
                ->label(__('filament-seo::filament-seo.fields.title'))
                ->maxLength(70),
            Forms\Components\Textarea::make('description')
                ->label(__('filament-seo::filament-seo.fields.description'))
                ->maxLength(160)
                ->rows(3),
            Forms\Components\TextInput::make('keywords')
                ->label(__('filament-seo::filament-seo.fields.keywords'))
                ->maxLength(255),
            Forms\Components\TextInput::make('canonical_url')
                ->label(__('filament-seo::filament-seo.fields.canonical_url'))
                ->url()
                ->maxLength(255),
            Forms\Components\Toggle::make('no_index')
                ->label(__('filament-seo::filament-seo.fields.no_index')),
            Forms\Components\Toggle::make('no_follow')
                ->label(__('filament-seo::filament-seo.fields.no_follow')),
            Forms\Components\Select::make('og_type')
                ->label(__('filament-seo::filament-seo.fields.og_type'))
                ->options([
                    'website' => 'website',
                    'article' => 'article',
                    'product' => 'product',
                ])
                ->default($defaultOgType),
            Forms\Components\FileUpload::make('og_image')
                ->label(__('filament-seo::filament-seo.fields.og_image'))
                ->image()
                ->disk('public')
                ->directory('seo')
                ->visibility('public'),
            Forms\Components\Textarea::make('schema_markup')
                ->label(__('filament-seo::filament-seo.fields.schema_markup'))
                ->helperText(__('filament-seo::filament-seo.fields.schema_markup_help'))
                ->rows(5)
                ->rules(['nullable', 'json'])
                ->formatStateUsing(fn ($state): ?string => is_array($state)
                    ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                    : $state)
                ->dehydrateStateUsing(fn (?string $state): ?array => filled($state)
                    ? json_decode($state, true)
                    : null),
        ];
    }
}
```

- [ ] **Step 7: Run the tests**

Run: `./vendor/bin/pest tests/Unit/SeoFieldsTest.php`
Expected: PASS — 19 passed

- [ ] **Step 8: Run the whole suite for regressions**

Run: `./vendor/bin/pest`
Expected: PASS — 107 passed. Ha a `SecurityTest` vagy a `FilamentSmokeTest` elhasal, **ne a tesztet írd át** — az azt jelenti, hogy a mezőbővítés visszafelé nem kompatibilis, és a `SeoFields`-et kell javítani.

- [ ] **Step 9: Commit**

```bash
git add database/migrations/2026_09_22_000002_add_og_image_to_seo_metas_table.php resources/lang src/FilamentSeoServiceProvider.php src/Forms/SeoFields.php tests/Unit/SeoFieldsTest.php
git commit -m "Translate the SEO fields and add og_image, og_type and JSON-LD inputs"
```

---

### Task 3: `seo:sync-routes` parancs

**Files:**
- Create: `src/Console/SyncSeoRoutesCommand.php`
- Create: `tests/Feature/SyncSeoRoutesCommandTest.php`
- Modify: `src/FilamentSeoServiceProvider.php` (`configurePackage`)

**Interfaces:**
- Consumes: `SeoRoute` (Task 1), `config('filament-seo.routes')` (Task 1).
- Produces: `seo:sync-routes` artisan parancs. Mindig `0` (SUCCESS) kilépési kóddal tér vissza, még akkor is, ha voltak kihagyott route-ok — a figyelmeztetés a kimeneten jelenik meg. Ez szándékos: a parancs a projektek `composer setup` scriptjébe kerül, és egy elgépelt route nem akaszthatja meg a telepítést.

- [ ] **Step 1: Write the failing test**

`tests/Feature/SyncSeoRoutesCommandTest.php`:

```php
<?php

use Illuminate\Support\Facades\Route;
use Madbox99\FilamentSeo\Models\SeoRoute;

beforeEach(function () {
    Route::get('/', fn () => 'home')->name('home');
    Route::get('/kapcsolat', fn () => 'contact')->name('contact');
});

it('creates a record for every configured route', function () {
    config()->set('filament-seo.routes', [
        'home' => 'Főoldal',
        'contact' => 'Kapcsolat',
    ]);

    $this->artisan('seo:sync-routes')->assertSuccessful();

    expect(SeoRoute::count())->toBe(2)
        ->and(SeoRoute::forRoute('home')->label)->toBe('Főoldal')
        ->and(SeoRoute::forRoute('home')->sort_order)->toBe(1)
        ->and(SeoRoute::forRoute('contact')->sort_order)->toBe(2);
});

it('is idempotent and refreshes the label', function () {
    config()->set('filament-seo.routes', ['home' => 'Főoldal']);
    $this->artisan('seo:sync-routes')->assertSuccessful();

    config()->set('filament-seo.routes', ['home' => 'Kezdőlap']);
    $this->artisan('seo:sync-routes')->assertSuccessful();

    expect(SeoRoute::count())->toBe(1)
        ->and(SeoRoute::forRoute('home')->label)->toBe('Kezdőlap');
});

it('warns about a route that does not exist and creates no record', function () {
    config()->set('filament-seo.routes', ['nincs-ilyen' => 'Szellem oldal']);

    $this->artisan('seo:sync-routes')
        ->expectsOutputToContain('nincs-ilyen')
        ->assertSuccessful();

    expect(SeoRoute::count())->toBe(0);
});

it('keeps a record whose route was removed from the config', function () {
    config()->set('filament-seo.routes', ['home' => 'Főoldal', 'contact' => 'Kapcsolat']);
    $this->artisan('seo:sync-routes');

    SeoRoute::forRoute('contact')->seoMeta()->create(['title' => 'Megírt SEO cím']);

    config()->set('filament-seo.routes', ['home' => 'Főoldal']);
    $this->artisan('seo:sync-routes')->assertSuccessful();

    expect(SeoRoute::count())->toBe(2)
        ->and(SeoRoute::forRoute('contact')->seoMeta->title)->toBe('Megírt SEO cím');
});

it('does not attach a seo meta record on its own', function () {
    config()->set('filament-seo.routes', ['home' => 'Főoldal']);

    $this->artisan('seo:sync-routes')->assertSuccessful();

    expect(SeoRoute::forRoute('home')->seoMeta)->toBeNull();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `./vendor/bin/pest tests/Feature/SyncSeoRoutesCommandTest.php`
Expected: FAIL — `The command "seo:sync-routes" does not exist.`

- [ ] **Step 3: Write the command**

`src/Console/SyncSeoRoutesCommand.php`:

```php
<?php

namespace Madbox99\FilamentSeo\Console;

use Illuminate\Console\Command;
use Illuminate\Routing\Router;
use Madbox99\FilamentSeo\Models\SeoRoute;

class SyncSeoRoutesCommand extends Command
{
    protected $signature = 'seo:sync-routes';

    protected $description = 'Sync the editable page SEO records from config/filament-seo.php';

    public function handle(Router $router): int
    {
        /** @var array<string, string> $routes */
        $routes = config('filament-seo.routes', []);

        $synced = 0;
        $sortOrder = 0;

        foreach ($routes as $routeName => $label) {
            $sortOrder++;

            if (! $router->has($routeName)) {
                $this->warn("Skipped [{$routeName}]: the application has no named route by that name.");

                continue;
            }

            SeoRoute::query()->updateOrCreate(
                ['route_name' => $routeName],
                ['label' => $label, 'sort_order' => $sortOrder],
            );

            $synced++;
        }

        $this->info("Synced {$synced} page SEO record(s).");

        $orphans = SeoRoute::query()
            ->whereNotIn('route_name', array_keys($routes))
            ->pluck('route_name');

        if ($orphans->isNotEmpty()) {
            $this->warn('Kept but no longer configured: '.$orphans->implode(', '));
        }

        return self::SUCCESS;
    }
}
```

- [ ] **Step 4: Register the command**

`src/FilamentSeoServiceProvider.php` — importáld a parancsot és fűzd a láncra:

```php
use Madbox99\FilamentSeo\Console\SyncSeoRoutesCommand;

// ...

        $package->name(static::$name)
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasCommand(SyncSeoRoutesCommand::class)
            ->discoversMigrations()
            ->runsMigrations();
```

- [ ] **Step 5: Run the tests**

Run: `./vendor/bin/pest tests/Feature/SyncSeoRoutesCommandTest.php`
Expected: PASS — 5 passed

- [ ] **Step 6: Run the whole suite**

Run: `./vendor/bin/pest`
Expected: PASS — 112 passed

- [ ] **Step 7: Commit**

```bash
git add src/Console/SyncSeoRoutesCommand.php src/FilamentSeoServiceProvider.php tests/Feature/SyncSeoRoutesCommandTest.php
git commit -m "Add the seo:sync-routes command"
```

---

### Task 4: `SeoData` és a `SeoManager` feloldó

**Files:**
- Create: `src/Data/SeoData.php`
- Create: `src/SeoManager.php`
- Create: `src/Facades/Seo.php`
- Create: `tests/Feature/SeoManagerTest.php`
- Modify: `src/FilamentSeoServiceProvider.php` (új `packageRegistered()` metódus)

**Interfaces:**
- Consumes: `SeoRoute::forRoute()` (Task 1), az `og_image` oszlop (Task 2), `SeoSetting::current()` (létezik).
- Produces:
  - `Madbox99\FilamentSeo\Data\SeoData` readonly osztály, publikus property-k: `?string $title`, `?string $description`, `?string $keywords`, `?string $canonical`, `?string $robots`, `string $ogType`, `?string $ogImage`, `array $schemas`.
  - `Madbox99\FilamentSeo\SeoManager` singleton: `for(Model $model, ?string $fallbackTitle = null): static`, `ogImage(?string $url): static`, `reset(): static`, `resolve(): SeoData`, `static siteNameUsing(Closure $callback): void`.
  - `Madbox99\FilamentSeo\Facades\Seo` facade ugyanezekkel a statikus hívásokkal.
  - A `schemas` property ebben a taskban **mindig üres tömb** — a Task 5 tölti fel.

- [ ] **Step 1: Write the failing test**

`tests/Feature/SeoManagerTest.php`:

```php
<?php

use Illuminate\Support\Facades\Route;
use Madbox99\FilamentSeo\Facades\Seo;
use Madbox99\FilamentSeo\Models\SeoRoute;
use Madbox99\FilamentSeo\Models\SeoSetting;
use Madbox99\FilamentSeo\SeoManager;
use Madbox99\FilamentSeo\Tests\Fixtures\Post;

beforeEach(function () {
    Route::get('/', fn () => 'home')->name('home');

    SeoSetting::current()->update([
        'default_title_pattern' => '{title} | {site_name}',
        'default_description' => 'Globális leírás',
        'default_og_image' => 'https://pelda.hu/global-og.png',
    ]);

    SeoManager::siteNameUsing(fn () => 'Teszt Oldal');
    Seo::reset();
});

it('falls back to the global description when nothing else is set', function () {
    $data = Seo::resolve();

    expect($data->description)->toBe('Globális leírás');
});

it('prefers the route record over the global default', function () {
    SeoRoute::create(['route_name' => 'home', 'label' => 'Főoldal'])
        ->seoMeta()->create(['title' => 'Kezdőlap', 'description' => 'Route leírás']);

    $this->get('/');

    $data = Seo::resolve();

    expect($data->title)->toBe('Kezdőlap | Teszt Oldal')
        ->and($data->description)->toBe('Route leírás');
});

it('prefers the explicit model over the route record', function () {
    SeoRoute::create(['route_name' => 'home', 'label' => 'Főoldal'])
        ->seoMeta()->create(['title' => 'Kezdőlap', 'description' => 'Route leírás']);

    $post = Post::create(['title' => 'Cikk']);
    $post->seoMeta()->create(['title' => 'Cikk SEO címe']);

    $this->get('/');

    $data = Seo::for($post)->resolve();

    expect($data->title)->toBe('Cikk SEO címe | Teszt Oldal');
});

it('falls back field by field, not record by record', function () {
    SeoRoute::create(['route_name' => 'home', 'label' => 'Főoldal'])
        ->seoMeta()->create(['title' => 'Kezdőlap', 'description' => 'Route leírás']);

    $post = Post::create(['title' => 'Cikk']);
    $post->seoMeta()->create(['title' => 'Cikk SEO címe']);

    $this->get('/');

    $data = Seo::for($post)->resolve();

    expect($data->title)->toBe('Cikk SEO címe | Teszt Oldal')
        ->and($data->description)->toBe('Route leírás');
});

it('uses the caller supplied fallback title when no seo meta exists', function () {
    $post = Post::create(['title' => 'Cikk']);

    $data = Seo::for($post, 'Cikk')->resolve();

    expect($data->title)->toBe('Cikk | Teszt Oldal');
});

it('returns the bare site name when there is no title at all', function () {
    expect(Seo::resolve()->title)->toBe('Teszt Oldal');
});

it('emits a robots value only when noindex or nofollow is set', function () {
    expect(Seo::resolve()->robots)->toBeNull();

    $post = Post::create(['title' => 'Cikk']);
    $post->seoMeta()->create(['no_index' => true]);

    expect(Seo::for($post)->resolve()->robots)->toBe('noindex, follow');
});

it('combines noindex and nofollow', function () {
    $post = Post::create(['title' => 'Cikk']);
    $post->seoMeta()->create(['no_index' => true, 'no_follow' => true]);

    expect(Seo::for($post)->resolve()->robots)->toBe('noindex, nofollow');
});

it('defaults the canonical url to the current url', function () {
    $this->get('/');

    expect(Seo::resolve()->canonical)->toBe(url('/'));
});

it('resolves the og image from the record, then the caller, then the global default', function () {
    expect(Seo::resolve()->ogImage)->toBe('https://pelda.hu/global-og.png');

    Seo::reset();
    expect(Seo::ogImage('https://pelda.hu/kiemelt.png')->resolve()->ogImage)
        ->toBe('https://pelda.hu/kiemelt.png');

    $post = Post::create(['title' => 'Cikk']);
    $post->seoMeta()->create(['og_image' => 'https://pelda.hu/sajat.png']);

    Seo::reset();
    expect(Seo::for($post)->ogImage('https://pelda.hu/kiemelt.png')->resolve()->ogImage)
        ->toBe('https://pelda.hu/sajat.png');
});

it('turns a stored disk path into a public url', function () {
    $post = Post::create(['title' => 'Cikk']);
    $post->seoMeta()->create(['og_image' => 'seo/kep.png']);

    expect(Seo::for($post)->resolve()->ogImage)
        ->toBe(Illuminate\Support\Facades\Storage::disk('public')->url('seo/kep.png'));
});

it('defaults the og type to website', function () {
    expect(Seo::resolve()->ogType)->toBe('website');
});

it('reads the og type from the seo meta record', function () {
    $post = Post::create(['title' => 'Cikk']);
    $post->seoMeta()->create(['og_type' => 'article']);

    expect(Seo::for($post)->resolve()->ogType)->toBe('article');
});

it('returns no schemas yet', function () {
    expect(Seo::resolve()->schemas)->toBe([]);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `./vendor/bin/pest tests/Feature/SeoManagerTest.php`
Expected: FAIL — `Class "Madbox99\FilamentSeo\Facades\Seo" not found`

- [ ] **Step 3: Write the SeoData object**

`src/Data/SeoData.php`:

```php
<?php

namespace Madbox99\FilamentSeo\Data;

final readonly class SeoData
{
    /**
     * @param  list<array<string, mixed>>  $schemas
     */
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public ?string $keywords = null,
        public ?string $canonical = null,
        public ?string $robots = null,
        public string $ogType = 'website',
        public ?string $ogImage = null,
        public array $schemas = [],
    ) {}
}
```

- [ ] **Step 4: Write the SeoManager**

`src/SeoManager.php`:

```php
<?php

namespace Madbox99\FilamentSeo;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Madbox99\FilamentSeo\Data\SeoData;
use Madbox99\FilamentSeo\Models\SeoMeta;
use Madbox99\FilamentSeo\Models\SeoRoute;
use Madbox99\FilamentSeo\Models\SeoSetting;

class SeoManager
{
    protected static ?Closure $siteNameResolver = null;

    protected ?Model $model = null;

    protected ?string $fallbackTitle = null;

    protected ?string $ogImage = null;

    public static function siteNameUsing(Closure $callback): void
    {
        static::$siteNameResolver = $callback;
    }

    public function for(Model $model, ?string $fallbackTitle = null): static
    {
        $this->model = $model;
        $this->fallbackTitle = $fallbackTitle;

        return $this;
    }

    public function ogImage(?string $url): static
    {
        $this->ogImage = $url;

        return $this;
    }

    public function reset(): static
    {
        $this->model = null;
        $this->fallbackTitle = null;
        $this->ogImage = null;

        return $this;
    }

    public function siteName(): string
    {
        if (static::$siteNameResolver instanceof Closure) {
            return (string) call_user_func(static::$siteNameResolver);
        }

        return (string) config('app.name');
    }

    public function resolve(): SeoData
    {
        $settings = SeoSetting::current();
        $modelMeta = $this->modelMeta();
        $routeMeta = $this->routeMeta();

        return new SeoData(
            title: $this->title($modelMeta, $routeMeta, $settings),
            description: $this->firstFilled([
                $modelMeta?->description,
                $routeMeta?->description,
                $settings->default_description,
            ]),
            keywords: $this->firstFilled([$modelMeta?->keywords, $routeMeta?->keywords]),
            canonical: $this->firstFilled([
                $modelMeta?->canonical_url,
                $routeMeta?->canonical_url,
                url()->current(),
            ]),
            robots: $this->robots($modelMeta ?? $routeMeta),
            ogType: $this->firstFilled([$modelMeta?->og_type, $routeMeta?->og_type]) ?? 'website',
            ogImage: $this->imageUrl($this->firstFilled([
                $modelMeta?->og_image,
                $routeMeta?->og_image,
                $this->ogImage,
                $settings->default_og_image,
            ])),
            schemas: [],
        );
    }

    protected function modelMeta(): ?SeoMeta
    {
        if (! $this->model instanceof Model) {
            return null;
        }

        if (! method_exists($this->model, 'seoMeta')) {
            return null;
        }

        return $this->model->seoMeta;
    }

    protected function routeMeta(): ?SeoMeta
    {
        $routeName = request()->route()?->getName();

        if (! is_string($routeName) || $routeName === '') {
            return null;
        }

        return SeoRoute::forRoute($routeName)?->seoMeta;
    }

    protected function title(?SeoMeta $modelMeta, ?SeoMeta $routeMeta, SeoSetting $settings): string
    {
        $siteName = $this->siteName();

        $title = $this->firstFilled([
            $modelMeta?->title,
            $routeMeta?->title,
            $this->fallbackTitle,
        ]);

        if ($title === null) {
            return $siteName;
        }

        $pattern = $settings->default_title_pattern ?: '{title} | {site_name}';

        return str_replace(['{title}', '{site_name}'], [$title, $siteName], $pattern);
    }

    protected function robots(?SeoMeta $meta): ?string
    {
        if (! $meta instanceof SeoMeta) {
            return null;
        }

        if (! $meta->no_index && ! $meta->no_follow) {
            return null;
        }

        return ($meta->no_index ? 'noindex' : 'index').', '.($meta->no_follow ? 'nofollow' : 'follow');
    }

    protected function imageUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '//')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }

    /**
     * @param  array<int, string|null>  $candidates
     */
    protected function firstFilled(array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if (filled($candidate)) {
                return (string) $candidate;
            }
        }

        return null;
    }
}
```

- [ ] **Step 5: Write the facade**

`src/Facades/Seo.php`:

```php
<?php

namespace Madbox99\FilamentSeo\Facades;

use Illuminate\Support\Facades\Facade;
use Madbox99\FilamentSeo\SeoManager;

/**
 * @method static SeoManager for(\Illuminate\Database\Eloquent\Model $model, ?string $fallbackTitle = null)
 * @method static SeoManager ogImage(?string $url)
 * @method static SeoManager reset()
 * @method static string siteName()
 * @method static \Madbox99\FilamentSeo\Data\SeoData resolve()
 *
 * @see SeoManager
 */
class Seo extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SeoManager::class;
    }
}
```

- [ ] **Step 6: Bind the manager as a singleton**

`src/FilamentSeoServiceProvider.php` — vedd fel ezt az új metódust a `configurePackage()` alá:

```php
    public function packageRegistered(): void
    {
        $this->app->singleton(SeoManager::class);
    }
```

A fájl tetejére kell a `use Madbox99\FilamentSeo\SeoManager;` import is.

- [ ] **Step 7: Run the tests**

Run: `./vendor/bin/pest tests/Feature/SeoManagerTest.php`
Expected: PASS — 14 passed

- [ ] **Step 8: Run the whole suite**

Run: `./vendor/bin/pest`
Expected: PASS — 126 passed

- [ ] **Step 9: Commit**

```bash
git add src/Data/SeoData.php src/SeoManager.php src/Facades/Seo.php src/FilamentSeoServiceProvider.php tests/Feature/SeoManagerTest.php
git commit -m "Add the SeoManager resolver with model, route and global fallbacks"
```

---

### Task 5: `ProvidesSchemaOrg` és a JSON-LD blokkok összeállítása

**Files:**
- Create: `src/Contracts/ProvidesSchemaOrg.php`
- Create: `tests/Fixtures/SchemaPost.php`
- Create: `tests/Feature/SeoSchemaOrgTest.php`
- Modify: `src/SeoManager.php` (a `resolve()` `schemas:` argumentuma és új protected metódusok)

**Interfaces:**
- Consumes: `SeoManager::resolve()` (Task 4), `config('filament-seo.home_route')` (Task 1), `SeoSetting::$schema_org_type` és `$schema_org_data` (létezik).
- Produces: `Madbox99\FilamentSeo\Contracts\ProvidesSchemaOrg` interfész egyetlen metódussal: `toSchemaOrg(): array`, ami **blokkok listáját** adja vissza (`list<array<string, mixed>>`), nem egyetlen blokkot. A `SeoData::$schemas` ugyanilyen lista.

- [ ] **Step 1: Write the failing test**

Először a fixture, `tests/Fixtures/SchemaPost.php`:

```php
<?php

namespace Madbox99\FilamentSeo\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Madbox99\FilamentSeo\Concerns\HasSeo;
use Madbox99\FilamentSeo\Contracts\ProvidesSchemaOrg;

class SchemaPost extends Model implements ProvidesSchemaOrg
{
    use HasSeo;

    protected $guarded = [];

    protected $table = 'posts';

    /** @return list<array<string, mixed>> */
    public function toSchemaOrg(): array
    {
        return [
            ['@context' => 'https://schema.org', '@type' => 'Article', 'headline' => $this->title],
            ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => []],
        ];
    }
}
```

Majd `tests/Feature/SeoSchemaOrgTest.php`:

```php
<?php

use Illuminate\Support\Facades\Route;
use Madbox99\FilamentSeo\Facades\Seo;
use Madbox99\FilamentSeo\Models\SeoSetting;
use Madbox99\FilamentSeo\SeoManager;
use Madbox99\FilamentSeo\Tests\Fixtures\Post;
use Madbox99\FilamentSeo\Tests\Fixtures\SchemaPost;

beforeEach(function () {
    Route::get('/', fn () => 'home')->name('home');
    Route::get('/cikk', fn () => 'article')->name('article');

    SeoSetting::current()->update([
        'schema_org_type' => 'Organization',
        'schema_org_data' => [
            'name' => 'Teszt Kft.',
            'url' => 'https://pelda.hu',
            'logo' => 'https://pelda.hu/logo.png',
            'description' => '',
        ],
    ]);

    SeoManager::siteNameUsing(fn () => 'Teszt Oldal');
    Seo::reset();
});

it('emits the organization and website blocks on the home route', function () {
    $this->get('/');

    $schemas = Seo::resolve()->schemas;

    expect($schemas)->toHaveCount(2)
        ->and($schemas[0]['@type'])->toBe('Organization')
        ->and($schemas[0]['name'])->toBe('Teszt Kft.')
        ->and($schemas[1]['@type'])->toBe('WebSite');
});

it('drops empty values from the organization block', function () {
    $this->get('/');

    expect(Seo::resolve()->schemas[0])->not->toHaveKey('description');
});

it('emits no global blocks outside the home route', function () {
    $this->get('/cikk');

    expect(Seo::resolve()->schemas)->toBe([]);
});

it('appends every block the model provides', function () {
    $this->get('/cikk');

    $post = SchemaPost::create(['title' => 'Cikk címe']);

    $schemas = Seo::for($post)->resolve()->schemas;

    expect($schemas)->toHaveCount(2)
        ->and($schemas[0]['@type'])->toBe('Article')
        ->and($schemas[0]['headline'])->toBe('Cikk címe')
        ->and($schemas[1]['@type'])->toBe('BreadcrumbList');
});

it('ignores a model that does not implement the contract', function () {
    $this->get('/cikk');

    $post = Post::create(['title' => 'Cikk']);

    expect(Seo::for($post)->resolve()->schemas)->toBe([]);
});

it('appends a single hand written json-ld block from the admin', function () {
    $this->get('/cikk');

    $post = Post::create(['title' => 'Cikk']);
    $post->seoMeta()->create([
        'schema_markup' => ['@context' => 'https://schema.org', '@type' => 'FAQPage'],
    ]);

    $schemas = Seo::for($post)->resolve()->schemas;

    expect($schemas)->toHaveCount(1)
        ->and($schemas[0]['@type'])->toBe('FAQPage');
});

it('appends a list of hand written blocks', function () {
    $this->get('/cikk');

    $post = Post::create(['title' => 'Cikk']);
    $post->seoMeta()->create([
        'schema_markup' => [
            ['@type' => 'FAQPage'],
            ['@type' => 'HowTo'],
        ],
    ]);

    expect(Seo::for($post)->resolve()->schemas)->toHaveCount(2);
});

it('puts the model blocks before the hand written ones', function () {
    $this->get('/cikk');

    $post = SchemaPost::create(['title' => 'Cikk címe']);
    $post->seoMeta()->create(['schema_markup' => ['@type' => 'FAQPage']]);

    $schemas = Seo::for($post)->resolve()->schemas;

    expect($schemas)->toHaveCount(3)
        ->and($schemas[2]['@type'])->toBe('FAQPage');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `./vendor/bin/pest tests/Feature/SeoSchemaOrgTest.php`
Expected: FAIL — `Interface "Madbox99\FilamentSeo\Contracts\ProvidesSchemaOrg" not found`

- [ ] **Step 3: Write the contract**

`src/Contracts/ProvidesSchemaOrg.php`:

```php
<?php

namespace Madbox99\FilamentSeo\Contracts;

interface ProvidesSchemaOrg
{
    /**
     * A modellhez tartozó schema.org blokkok listája.
     *
     * Listát ad vissza, nem egyetlen blokkot: egy cikkoldalnak például
     * Article és BreadcrumbList blokkra is szüksége van.
     *
     * @return list<array<string, mixed>>
     */
    public function toSchemaOrg(): array;
}
```

- [ ] **Step 4: Fill the schemas in the manager**

`src/SeoManager.php` — a `resolve()` metódusban a `schemas: []` argumentum cserélődik:

```php
            schemas: $this->schemas($modelMeta, $settings),
```

És a `firstFilled()` metódus **fölé** kerülnek ezek az új metódusok:

```php
    /**
     * @return list<array<string, mixed>>
     */
    protected function schemas(?SeoMeta $modelMeta, SeoSetting $settings): array
    {
        $schemas = [];

        if ($this->isHomeRoute()) {
            $schemas = $this->globalSchemas($settings);
        }

        if ($this->model instanceof ProvidesSchemaOrg) {
            $schemas = [...$schemas, ...$this->model->toSchemaOrg()];
        }

        foreach ($this->handWrittenSchemas($modelMeta) as $block) {
            $schemas[] = $block;
        }

        return $schemas;
    }

    protected function isHomeRoute(): bool
    {
        $homeRoute = config('filament-seo.home_route', 'home');

        return is_string($homeRoute) && request()->route()?->getName() === $homeRoute;
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function globalSchemas(SeoSetting $settings): array
    {
        $organisation = array_filter(
            (array) ($settings->schema_org_data ?? []),
            fn ($value): bool => filled($value),
        );

        return [
            [
                '@context' => 'https://schema.org',
                '@type' => $settings->schema_org_type ?: 'Organization',
                ...$organisation,
            ],
            [
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                'name' => $this->siteName(),
                'url' => url('/'),
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function handWrittenSchemas(?SeoMeta $modelMeta): array
    {
        $markup = $modelMeta?->schema_markup ?? $this->routeMeta()?->schema_markup;

        if (! is_array($markup) || $markup === []) {
            return [];
        }

        // Egyetlen blokk (asszociatív tömb) vagy blokkok listája is jöhet.
        return array_is_list($markup) ? array_values($markup) : [$markup];
    }
```

A fájl tetejére kell a `use Madbox99\FilamentSeo\Contracts\ProvidesSchemaOrg;` import.

- [ ] **Step 5: Run the tests**

Run: `./vendor/bin/pest tests/Feature/SeoSchemaOrgTest.php`
Expected: PASS — 8 passed

- [ ] **Step 6: Run the whole suite**

Run: `./vendor/bin/pest`
Expected: PASS — 134 passed

- [ ] **Step 7: Commit**

```bash
git add src/Contracts/ProvidesSchemaOrg.php src/SeoManager.php tests/Fixtures/SchemaPost.php tests/Feature/SeoSchemaOrgTest.php
git commit -m "Assemble the JSON-LD blocks from globals, models and hand written markup"
```

---

### Task 6: `<x-filament-seo::head />` Blade komponens

**Files:**
- Create: `resources/views/components/head.blade.php`
- Create: `tests/Feature/SeoHeadComponentTest.php`
- Modify: `src/FilamentSeoServiceProvider.php` (új `packageBooted()` metódus)

**Interfaces:**
- Consumes: `Seo::resolve()` (Task 4, Task 5).
- Produces: `<x-filament-seo::head />` anonim Blade komponens, egyetlen opcionális proppal: `title`. Ha a `title` prop meg van adva és nem üres, az kerül a `<title>` tagbe; egyébként a feloldó címe. Ez azért kell, mert a Livewire `->title()` a `wire:navigate` navigációnál a layout `$title` propján keresztül frissíti a címet, és két `<title>` tag nem keletkezhet.

- [ ] **Step 1: Write the failing test**

`tests/Feature/SeoHeadComponentTest.php`:

```php
<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Madbox99\FilamentSeo\Facades\Seo;
use Madbox99\FilamentSeo\Models\SeoRoute;
use Madbox99\FilamentSeo\Models\SeoSetting;
use Madbox99\FilamentSeo\SeoManager;
use Madbox99\FilamentSeo\Tests\Fixtures\Post;

beforeEach(function () {
    Route::get('/', fn () => 'home')->name('home');

    SeoSetting::current()->update([
        'default_title_pattern' => '{title} | {site_name}',
        'default_description' => 'Globális leírás',
        'default_og_image' => null,
        'schema_org_type' => 'Organization',
        'schema_org_data' => ['name' => 'Teszt Kft.'],
    ]);

    SeoManager::siteNameUsing(fn () => 'Teszt Oldal');
    Seo::reset();
});

it('renders the resolved title', function () {
    SeoRoute::create(['route_name' => 'home', 'label' => 'Főoldal'])
        ->seoMeta()->create(['title' => 'Kezdőlap']);

    $this->get('/');

    expect(Blade::render('<x-filament-seo::head />'))
        ->toContain('<title>Kezdőlap | Teszt Oldal</title>');
});

it('lets an explicit title prop win', function () {
    $this->get('/');

    expect(Blade::render('<x-filament-seo::head title="Kívülről jött cím" />'))
        ->toContain('<title>Kívülről jött cím</title>');
});

it('renders exactly one title tag', function () {
    $this->get('/');

    expect(substr_count(Blade::render('<x-filament-seo::head title="Cím" />'), '<title>'))->toBe(1);
});

it('renders the description meta tag', function () {
    $this->get('/');

    expect(Blade::render('<x-filament-seo::head />'))
        ->toContain('<meta name="description" content="Globális leírás">');
});

it('renders no robots meta tag when nothing is hidden', function () {
    $this->get('/');

    expect(Blade::render('<x-filament-seo::head />'))->not->toContain('name="robots"');
});

it('renders the robots meta tag for a noindex page', function () {
    SeoRoute::create(['route_name' => 'home', 'label' => 'Főoldal'])
        ->seoMeta()->create(['no_index' => true]);

    $this->get('/');

    expect(Blade::render('<x-filament-seo::head />'))
        ->toContain('<meta name="robots" content="noindex, follow">');
});

it('renders the canonical link', function () {
    $this->get('/');

    expect(Blade::render('<x-filament-seo::head />'))
        ->toContain('<link rel="canonical" href="'.url('/').'">');
});

it('renders one script tag per json-ld block', function () {
    $this->get('/');

    $html = Blade::render('<x-filament-seo::head />');

    expect(substr_count($html, 'application/ld+json'))->toBe(2)
        ->and($html)->toContain('"@type":"Organization"');
});

it('escapes a closing script tag inside the json-ld', function () {
    $this->get('/');

    $post = Post::create(['title' => 'Cikk']);
    $post->seoMeta()->create([
        'schema_markup' => ['@type' => 'FAQPage', 'name' => '</script><script>alert(1)</script>'],
    ]);

    Seo::for($post);

    expect(Blade::render('<x-filament-seo::head />'))->not->toContain('<script>alert(1)');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `./vendor/bin/pest tests/Feature/SeoHeadComponentTest.php`
Expected: FAIL — `Unable to locate a class or view for component [filament-seo::head]`

- [ ] **Step 3: Write the component**

`resources/views/components/head.blade.php`:

```blade
@props(['title' => null])

@php($seo = \Madbox99\FilamentSeo\Facades\Seo::resolve())

<title>{{ filled($title) ? $title : $seo->title }}</title>

@if ($seo->description)
<meta name="description" content="{{ $seo->description }}">
@endif
@if ($seo->keywords)
<meta name="keywords" content="{{ $seo->keywords }}">
@endif
@if ($seo->robots)
<meta name="robots" content="{{ $seo->robots }}">
@endif
@if ($seo->canonical)
<link rel="canonical" href="{{ $seo->canonical }}">
@endif
<meta property="og:type" content="{{ $seo->ogType }}">
<meta property="og:title" content="{{ filled($title) ? $title : $seo->title }}">
@if ($seo->description)
<meta property="og:description" content="{{ $seo->description }}">
@endif
@if ($seo->ogImage)
<meta property="og:image" content="{{ $seo->ogImage }}">
@endif
@if ($seo->canonical)
<meta property="og:url" content="{{ $seo->canonical }}">
@endif
@foreach ($seo->schemas as $schema)
<script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endforeach
```

A `JSON_HEX_TAG` flag nem elhagyható: e nélkül egy adminba beírt `</script>` kitörne a script blokkból.

**Ha a tesztek a route-feloldáson buknak el:** a `Blade::render()` a `$this->get('/')`
után is a legutóbb kezelt kérés route-ját látja, de ez a testbench verziójától függ.
Ha a `routeMeta()` nullt ad vissza a teszt alatt, ne a komponenst írd át — helyette
regisztrálj a tesztben egy route-ot, ami magát a komponenst rendereli, és a
válaszon állíts:

```php
Route::get('/fejlec', fn () => Blade::render('<x-filament-seo::head />'))->name('home');

$this->get('/fejlec')->assertSee('<title>Kezdőlap | Teszt Oldal</title>', false);
```

- [ ] **Step 4: Register the anonymous component namespace**

`src/FilamentSeoServiceProvider.php` — vedd fel ezt az új metódust:

```php
    public function packageBooted(): void
    {
        Blade::anonymousComponentNamespace('filament-seo::components', 'filament-seo');
    }
```

A fájl tetejére kell a `use Illuminate\Support\Facades\Blade;` import.

- [ ] **Step 5: Run the tests**

Run: `./vendor/bin/pest tests/Feature/SeoHeadComponentTest.php`
Expected: PASS — 9 passed

- [ ] **Step 6: Run the whole suite**

Run: `./vendor/bin/pest`
Expected: PASS — 143 passed

- [ ] **Step 7: Commit**

```bash
git add resources/views/components/head.blade.php src/FilamentSeoServiceProvider.php tests/Feature/SeoHeadComponentTest.php
git commit -m "Add the x-filament-seo::head component"
```

---

### Task 7: `SeoRouteResource` Filament resource

**Files:**
- Create: `src/Filament/Resources/SeoRoutes/SeoRouteResource.php`
- Create: `src/Filament/Resources/SeoRoutes/Pages/ListSeoRoutes.php`
- Create: `src/Filament/Resources/SeoRoutes/Pages/EditSeoRoute.php`
- Create: `tests/Feature/SeoRouteResourceTest.php`
- Modify: `src/FilamentSeoPlugin.php:80-85` (a `register()` metódus)

**Interfaces:**
- Consumes: `SeoRoute` (Task 1), `SeoFields::make()` (Task 2), a `filament-seo::filament-seo.routes.*` fordítási kulcsok (Task 2).
- Produces: `Madbox99\FilamentSeo\Filament\Resources\SeoRoutes\SeoRouteResource`, a plugin regisztrálja. `canCreate()` és `canDelete()` egyaránt `false` — a sorokat a config és a sync parancs határozza meg.

- [ ] **Step 1: Write the failing test**

`tests/Feature/SeoRouteResourceTest.php`:

```php
<?php

use Madbox99\FilamentSeo\Filament\Resources\SeoRoutes\Pages\EditSeoRoute;
use Madbox99\FilamentSeo\Filament\Resources\SeoRoutes\Pages\ListSeoRoutes;
use Madbox99\FilamentSeo\Filament\Resources\SeoRoutes\SeoRouteResource;
use Madbox99\FilamentSeo\Models\SeoRoute;
use Madbox99\FilamentSeo\Tests\Fixtures\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs(User::create([
        'name' => 'Admin',
        'email' => 'admin@pelda.hu',
        'password' => bcrypt('titok'),
    ]));
});

it('is registered on the panel', function () {
    expect(filament()->getPanel('admin')->getResources())
        ->toContain(SeoRouteResource::class);
});

it('forbids creating and deleting records', function () {
    expect(SeoRouteResource::canCreate())->toBeFalse()
        ->and(SeoRouteResource::canDelete(SeoRoute::create([
            'route_name' => 'home',
            'label' => 'Főoldal',
        ])))->toBeFalse();
});

it('lists the synced routes', function () {
    SeoRoute::create(['route_name' => 'home', 'label' => 'Főoldal', 'sort_order' => 1]);
    SeoRoute::create(['route_name' => 'contact', 'label' => 'Kapcsolat', 'sort_order' => 2]);

    livewire(ListSeoRoutes::class)
        ->assertSuccessful()
        ->assertSee('Főoldal')
        ->assertSee('Kapcsolat');
});

it('saves the seo meta from the edit form', function () {
    $route = SeoRoute::create(['route_name' => 'home', 'label' => 'Főoldal']);

    livewire(EditSeoRoute::class, ['record' => $route->getKey()])
        ->assertSuccessful()
        ->fillForm([
            'seoMeta.title' => 'Kezdőlap',
            'seoMeta.description' => 'A főoldal leírása',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($route->refresh()->seoMeta->title)->toBe('Kezdőlap')
        ->and($route->seoMeta->description)->toBe('A főoldal leírása');
});

it('rejects invalid json in the schema markup field', function () {
    $route = SeoRoute::create(['route_name' => 'home', 'label' => 'Főoldal']);

    livewire(EditSeoRoute::class, ['record' => $route->getKey()])
        ->fillForm(['seoMeta.schema_markup' => '{ ez nem json'])
        ->call('save')
        ->assertHasFormErrors(['seoMeta.schema_markup']);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `./vendor/bin/pest tests/Feature/SeoRouteResourceTest.php`
Expected: FAIL — `Class "Madbox99\FilamentSeo\Filament\Resources\SeoRoutes\SeoRouteResource" not found`

- [ ] **Step 3: Write the resource**

`src/Filament/Resources/SeoRoutes/SeoRouteResource.php`:

```php
<?php

namespace Madbox99\FilamentSeo\Filament\Resources\SeoRoutes;

use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Madbox99\FilamentSeo\Filament\Resources\SeoRoutes\Pages\EditSeoRoute;
use Madbox99\FilamentSeo\Filament\Resources\SeoRoutes\Pages\ListSeoRoutes;
use Madbox99\FilamentSeo\Forms\SeoFields;
use Madbox99\FilamentSeo\Models\SeoRoute;
use UnitEnum;

class SeoRouteResource extends Resource
{
    protected static ?string $model = SeoRoute::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-magnifying-glass';

    protected static string|UnitEnum|null $navigationGroup = 'SEO';

    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return __('filament-seo::filament-seo.routes.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament-seo::filament-seo.routes.plural_label');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make()->schema([
                TextInput::make('label')
                    ->label(__('filament-seo::filament-seo.routes.columns.label'))
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('route_name')
                    ->label('Route')
                    ->disabled()
                    ->dehydrated(false),
            ])->columns(2),
            SeoFields::make(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('label')
                    ->label(__('filament-seo::filament-seo.routes.columns.label'))
                    ->searchable(),
                TextColumn::make('seoMeta.title')
                    ->label(__('filament-seo::filament-seo.routes.columns.title'))
                    ->placeholder('—')
                    ->limit(50),
                IconColumn::make('has_schema')
                    ->label(__('filament-seo::filament-seo.routes.columns.has_schema'))
                    ->boolean()
                    ->getStateUsing(fn (SeoRoute $record): bool => filled($record->seoMeta?->schema_markup)),
                TextColumn::make('updated_at')
                    ->label(__('filament-seo::filament-seo.routes.columns.updated_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSeoRoutes::route('/'),
            'edit' => EditSeoRoute::route('/{record}/edit'),
        ];
    }
}
```

- [ ] **Step 4: Write the pages**

`src/Filament/Resources/SeoRoutes/Pages/ListSeoRoutes.php`:

```php
<?php

namespace Madbox99\FilamentSeo\Filament\Resources\SeoRoutes\Pages;

use Filament\Resources\Pages\ListRecords;
use Madbox99\FilamentSeo\Filament\Resources\SeoRoutes\SeoRouteResource;

class ListSeoRoutes extends ListRecords
{
    protected static string $resource = SeoRouteResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
```

`src/Filament/Resources/SeoRoutes/Pages/EditSeoRoute.php`:

```php
<?php

namespace Madbox99\FilamentSeo\Filament\Resources\SeoRoutes\Pages;

use Filament\Resources\Pages\EditRecord;
use Madbox99\FilamentSeo\Filament\Resources\SeoRoutes\SeoRouteResource;

class EditSeoRoute extends EditRecord
{
    protected static string $resource = SeoRouteResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
```

- [ ] **Step 5: Register the resource on the plugin**

`src/FilamentSeoPlugin.php` — a `register()` metódus:

```php
    public function register(Panel $panel): void
    {
        $panel->pages([
            ManageSeoSettings::class,
        ]);

        $panel->resources([
            SeoRouteResource::class,
        ]);
    }
```

A fájl tetejére kell a `use Madbox99\FilamentSeo\Filament\Resources\SeoRoutes\SeoRouteResource;` import.

- [ ] **Step 6: Run the tests**

Run: `./vendor/bin/pest tests/Feature/SeoRouteResourceTest.php`
Expected: PASS — 5 passed

- [ ] **Step 7: Run the whole suite**

Run: `./vendor/bin/pest`
Expected: PASS — 148 passed

- [ ] **Step 8: Commit**

```bash
git add src/Filament src/FilamentSeoPlugin.php tests/Feature/SeoRouteResourceTest.php
git commit -m "Add the page SEO resource for fixed routes"
```

---

### Task 8: Dokumentáció, lint és v0.2.0 kiadás

**Files:**
- Create: `CHANGELOG.md`
- Create vagy Modify: `README.md`
- Modify: `docs/superpowers/plans/2026-09-22-oldalankenti-seo-csomag.md` (a checkboxok kipipálása menet közben)

**Interfaces:**
- Consumes: az összes korábbi task publikus felülete.
- Produces: `v0.2.0` git tag, amire a két sablonprojekt `composer.json`-ja hivatkozhat.

- [ ] **Step 1: Write the README section**

A `README.md`-be kerüljön be ez a szakasz (ha a fájl nem létezik, hozd létre a csomag rövid bemutatásával, majd ezzel):

````markdown
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
use Madbox99\FilamentSeo\Facades\Seo;

Seo::for($product, fallbackTitle: $product->name)
    ->ogImage(Storage::url($product->image));
```

A feloldás mezőnként esik vissza: a modell SEO-rekordja, majd az aktuális route
rekordja, végül a globális beállítások.

A site nevét a projekt adja meg, jellemzően az `AppServiceProvider::boot()`-ban:

```php
use Madbox99\FilamentSeo\SeoManager;

SeoManager::siteNameUsing(fn () => resolve(GeneralSettings::class)->site_name);
```

## Saját JSON-LD modellenként

```php
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
````

- [ ] **Step 2: Write the changelog**

`CHANGELOG.md`:

```markdown
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
```

- [ ] **Step 3: Run the linter and the refactorer**

```bash
composer lint
composer refactor
```

Ha a `rector` módosított fájlokat, nézd át a diffet, és csak azt tartsd meg, ami
nem változtat viselkedést.

- [ ] **Step 4: Run the whole suite one final time**

Run: `./vendor/bin/pest`
Expected: PASS — 148 passed, 0 failed

- [ ] **Step 5: Commit and tag**

```bash
git add README.md CHANGELOG.md src resources
git commit -m "Document the page SEO feature and prepare v0.2.0"
git tag v0.2.0
```

A `git push --tags` és a Packagist-frissítés **nem része ennek a tervnek** — a két
sablonprojekt addig lokális path repóval húzza be a csomagot. A push a bekötési
tervek lezárása után, a tulajdonos döntésére történik.

---

## Ami ezután jön

1. `template-for-template` bekötése — külön terv.
2. `template-for-webshop` bekötése — külön terv.

Mindkettő ennek a tervnek a befejezését feltételezi, de egymástól függetlenül
futtatható.

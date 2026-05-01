<?php

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Madbox99\FilamentSeo\FilamentSeoPlugin;
use Madbox99\FilamentSeo\Models\SeoSetting;
use Madbox99\FilamentSeo\Pages\ManageSeoSettings;
use Madbox99\FilamentSeo\Tests\Fixtures\User;

// ──────────────────────────────────────────────────────────────
// Helper
// ──────────────────────────────────────────────────────────────

function authenticatedUser(): User
{
    return User::create([
        'name' => 'Test Admin',
        'email' => 'admin-'.uniqid().'@test.com',
        'password' => Hash::make('password'),
    ]);
}

// ──────────────────────────────────────────────────────────────
// Plugin Registration
// ──────────────────────────────────────────────────────────────

it('registers the filament-seo plugin', function () {
    expect(filament()->hasPlugin('filament-seo'))->toBeTrue();
});

it('returns correct plugin id', function () {
    $plugin = FilamentSeoPlugin::make();

    expect($plugin->getId())->toBe('filament-seo');
});

it('can create plugin instance via make', function () {
    $plugin = FilamentSeoPlugin::make();

    expect($plugin)->toBeInstanceOf(FilamentSeoPlugin::class);
});

// ──────────────────────────────────────────────────────────────
// Plugin Configuration
// ──────────────────────────────────────────────────────────────

it('has default navigation group', function () {
    $plugin = FilamentSeoPlugin::make();

    expect($plugin->getNavigationGroup())->toBe('SEO');
});

it('can customize navigation group', function () {
    $plugin = FilamentSeoPlugin::make()->navigationGroup('Settings');

    expect($plugin->getNavigationGroup())->toBe('Settings');
});

it('has default navigation icon', function () {
    $plugin = FilamentSeoPlugin::make();

    expect($plugin->getNavigationIcon())->toBe('heroicon-o-magnifying-glass');
});

it('can customize navigation icon', function () {
    $plugin = FilamentSeoPlugin::make()->navigationIcon('heroicon-o-cog');

    expect($plugin->getNavigationIcon())->toBe('heroicon-o-cog');
});

it('has default navigation sort', function () {
    $plugin = FilamentSeoPlugin::make();

    expect($plugin->getNavigationSort())->toBe(1);
});

it('can customize navigation sort', function () {
    $plugin = FilamentSeoPlugin::make()->navigationSort(99);

    expect($plugin->getNavigationSort())->toBe(99);
});

it('supports null navigation group', function () {
    $plugin = FilamentSeoPlugin::make()->navigationGroup(null);

    expect($plugin->getNavigationGroup())->toBeNull();
});

it('supports null navigation icon', function () {
    $plugin = FilamentSeoPlugin::make()->navigationIcon(null);

    expect($plugin->getNavigationIcon())->toBeNull();
});

it('supports null navigation sort', function () {
    $plugin = FilamentSeoPlugin::make()->navigationSort(null);

    expect($plugin->getNavigationSort())->toBeNull();
});

// ──────────────────────────────────────────────────────────────
// Plugin method chaining
// ──────────────────────────────────────────────────────────────

it('supports fluent method chaining', function () {
    $plugin = FilamentSeoPlugin::make()
        ->navigationGroup('Custom Group')
        ->navigationIcon('heroicon-o-cog')
        ->navigationSort(5);

    expect($plugin->getNavigationGroup())->toBe('Custom Group');
    expect($plugin->getNavigationIcon())->toBe('heroicon-o-cog');
    expect($plugin->getNavigationSort())->toBe(5);
});

// ──────────────────────────────────────────────────────────────
// Authentication
// ──────────────────────────────────────────────────────────────

it('redirects unauthenticated users to login', function () {
    $this->get('/admin')
        ->assertRedirect();
});

it('does not redirect authenticated users to login', function () {
    $user = authenticatedUser();

    $response = $this->actingAs($user)
        ->get('/admin');

    // Panel root may redirect to a default page, but should NOT redirect to login
    if ($response->status() === 302) {
        expect($response->headers->get('Location'))->not->toContain('login');
    } else {
        expect($response->isSuccessful())->toBeTrue();
    }
});

// ──────────────────────────────────────────────────────────────
// Page Static Properties
// ──────────────────────────────────────────────────────────────

it('has correct navigation label', function () {
    expect(ManageSeoSettings::getNavigationLabel())->toBe('Global SEO');
});

it('reads navigation group from plugin', function () {
    // The plugin is registered in the panel, so it should use plugin config
    $group = ManageSeoSettings::getNavigationGroup();

    expect($group)->toBe('SEO');
});

it('reads navigation icon from plugin', function () {
    $icon = ManageSeoSettings::getNavigationIcon();

    expect($icon)->toBe('heroicon-o-magnifying-glass');
});

it('reads navigation sort from plugin', function () {
    $sort = ManageSeoSettings::getNavigationSort();

    expect($sort)->toBe(1);
});

// ──────────────────────────────────────────────────────────────
// Navigation Registration
// ──────────────────────────────────────────────────────────────

it('registers ManageSeoSettings as a page in the panel', function () {
    $pages = filament()->getPages();

    expect($pages)->toContain(ManageSeoSettings::class);
});

// ──────────────────────────────────────────────────────────────
// Settings save() logic (direct model test)
// ──────────────────────────────────────────────────────────────

it('can save SEO settings via model', function () {
    $settings = SeoSetting::current();

    $settings->update([
        'default_title_pattern' => '{title} - My Site',
        'default_description' => 'A test description',
        'robots_txt' => "User-agent: *\nDisallow: /admin",
        'schema_org_type' => 'Corporation',
    ]);

    $settings->refresh();

    expect($settings->default_title_pattern)->toBe('{title} - My Site');
    expect($settings->default_description)->toBe('A test description');
    expect($settings->robots_txt)->toBe("User-agent: *\nDisallow: /admin");
    expect($settings->schema_org_type)->toBe('Corporation');
});

it('can save sitemap excluded URLs via model', function () {
    $settings = SeoSetting::current();

    $settings->update([
        'sitemap_excluded_urls' => ['/admin', '/secret', '/api/*'],
    ]);

    $settings->refresh();

    expect($settings->sitemap_excluded_urls)->toContain('/admin');
    expect($settings->sitemap_excluded_urls)->toContain('/secret');
    expect($settings->sitemap_excluded_urls)->toContain('/api/*');
});

it('can save schema.org data via model', function () {
    $settings = SeoSetting::current();

    $settings->update([
        'schema_org_type' => 'Person',
        'schema_org_data' => [
            'name' => 'John Doe',
            'url' => 'https://example.com',
        ],
    ]);

    $settings->refresh();

    expect($settings->schema_org_type)->toBe('Person');
    expect($settings->schema_org_data['name'])->toBe('John Doe');
});

it('can update existing settings', function () {
    SeoSetting::create([
        'default_title_pattern' => 'Old Pattern',
        'default_description' => 'Old description',
    ]);

    $settings = SeoSetting::current();
    $settings->update([
        'default_title_pattern' => 'New Pattern',
        'default_description' => 'New description',
    ]);

    expect(SeoSetting::count())->toBe(1);
    expect(SeoSetting::first()->default_title_pattern)->toBe('New Pattern');
});

// ──────────────────────────────────────────────────────────────
// Service Provider
// ──────────────────────────────────────────────────────────────

it('loads the config file', function () {
    expect(config('filament-seo'))->toBeArray();
    expect(config('filament-seo.table_names'))->toBeArray();
    expect(config('filament-seo.table_names.seo_metas'))->toBe('seo_metas');
    expect(config('filament-seo.table_names.seo_settings'))->toBe('seo_settings');
});

it('runs migrations automatically', function () {
    expect(Schema::hasTable('seo_metas'))->toBeTrue();
    expect(Schema::hasTable('seo_settings'))->toBeTrue();
});

it('seo_metas table has expected columns', function () {
    $columns = Schema::getColumnListing('seo_metas');

    expect($columns)->toContain('id');
    expect($columns)->toContain('seoable_type');
    expect($columns)->toContain('seoable_id');
    expect($columns)->toContain('title');
    expect($columns)->toContain('description');
    expect($columns)->toContain('keywords');
    expect($columns)->toContain('canonical_url');
    expect($columns)->toContain('no_index');
    expect($columns)->toContain('no_follow');
    expect($columns)->toContain('og_type');
    expect($columns)->toContain('schema_markup');
});

it('seo_settings table has expected columns', function () {
    $columns = Schema::getColumnListing('seo_settings');

    expect($columns)->toContain('id');
    expect($columns)->toContain('default_title_pattern');
    expect($columns)->toContain('default_description');
    expect($columns)->toContain('default_og_image');
    expect($columns)->toContain('robots_txt');
    expect($columns)->toContain('sitemap_excluded_urls');
    expect($columns)->toContain('schema_org_type');
    expect($columns)->toContain('schema_org_data');
});

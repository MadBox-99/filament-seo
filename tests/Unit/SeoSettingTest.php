<?php

use Madbox99\FilamentSeo\Models\SeoSetting;

// ──────────────────────────────────────────────────────────────
// Model Basics
// ──────────────────────────────────────────────────────────────

it('can create a SeoSetting instance', function () {
    $setting = new SeoSetting;

    expect($setting)->toBeInstanceOf(SeoSetting::class);
});

it('uses the correct table name', function () {
    $setting = new SeoSetting;

    expect($setting->getTable())->toBe('seo_settings');
});

it('uses configured table name from config', function () {
    config()->set('filament-seo.table_names.seo_settings', 'custom_seo_settings');

    $setting = new SeoSetting;

    expect($setting->getTable())->toBe('custom_seo_settings');

    // Reset
    config()->set('filament-seo.table_names.seo_settings', 'seo_settings');
});

it('has correct casts', function () {
    $setting = new SeoSetting;
    $casts = $setting->getCasts();

    expect($casts)->toHaveKey('sitemap_excluded_urls', 'array');
    expect($casts)->toHaveKey('schema_org_data', 'array');
});

// ──────────────────────────────────────────────────────────────
// current() Singleton Pattern
// ──────────────────────────────────────────────────────────────

it('creates default settings via current() when none exist', function () {
    expect(SeoSetting::count())->toBe(0);

    $settings = SeoSetting::current();

    expect($settings)->toBeInstanceOf(SeoSetting::class);
    expect($settings->exists)->toBeTrue();
    expect(SeoSetting::count())->toBe(1);
});

it('returns existing settings via current()', function () {
    SeoSetting::create([
        'default_title_pattern' => 'Existing: {title}',
    ]);

    $settings = SeoSetting::current();

    expect($settings->default_title_pattern)->toBe('Existing: {title}');
    expect(SeoSetting::count())->toBe(1);
});

it('does not create duplicates when calling current() multiple times', function () {
    SeoSetting::current();
    SeoSetting::current();
    SeoSetting::current();

    expect(SeoSetting::count())->toBe(1);
});

// ──────────────────────────────────────────────────────────────
// Default Values
// ──────────────────────────────────────────────────────────────

it('has correct default title pattern', function () {
    $settings = SeoSetting::current();

    expect($settings->default_title_pattern)->toBe('{title} | {site_name}');
});

it('has correct default robots.txt', function () {
    $settings = SeoSetting::current();

    expect($settings->robots_txt)->toContain('User-agent: *');
    expect($settings->robots_txt)->toContain('Allow: /');
    expect($settings->robots_txt)->toContain('Sitemap: /sitemap.xml');
});

it('has correct default schema_org_type', function () {
    $settings = SeoSetting::current();

    expect($settings->schema_org_type)->toBe('Organization');
});

it('has correct default schema_org_data structure', function () {
    $settings = SeoSetting::current();

    expect($settings->schema_org_data)->toBeArray();
    expect($settings->schema_org_data)->toHaveKeys(['name', 'url', 'logo', 'description']);
});

it('has empty default sitemap_excluded_urls', function () {
    $settings = SeoSetting::current();

    expect($settings->sitemap_excluded_urls)->toBe([]);
});

// ──────────────────────────────────────────────────────────────
// CRUD Operations
// ──────────────────────────────────────────────────────────────

it('can update settings', function () {
    $settings = SeoSetting::current();

    $settings->update([
        'default_title_pattern' => '{title} - Updated',
        'default_description' => 'Updated description',
        'schema_org_type' => 'LocalBusiness',
    ]);

    $settings->refresh();

    expect($settings->default_title_pattern)->toBe('{title} - Updated');
    expect($settings->default_description)->toBe('Updated description');
    expect($settings->schema_org_type)->toBe('LocalBusiness');
});

it('can store complex schema_org_data', function () {
    $settings = SeoSetting::current();

    $data = [
        'name' => 'My Company',
        'url' => 'https://mycompany.com',
        'logo' => 'https://mycompany.com/logo.png',
        'description' => 'We do things',
        'address' => [
            'streetAddress' => '123 Main St',
            'city' => 'Budapest',
        ],
    ];

    $settings->update(['schema_org_data' => $data]);
    $settings->refresh();

    expect($settings->schema_org_data)->toBe($data);
    expect($settings->schema_org_data['address']['city'])->toBe('Budapest');
});

it('can store multiple sitemap excluded URLs', function () {
    $settings = SeoSetting::current();

    $urls = ['/admin', '/login', '/api/*', '/internal/*'];

    $settings->update(['sitemap_excluded_urls' => $urls]);
    $settings->refresh();

    expect($settings->sitemap_excluded_urls)->toBe($urls);
    expect($settings->sitemap_excluded_urls)->toHaveCount(4);
});

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

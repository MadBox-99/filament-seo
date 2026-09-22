<?php

use Illuminate\Support\Facades\Route;
use Madbox99\FilamentSeo\Models\SeoRoute;

beforeEach(function () {
    Route::get('/', fn () => 'home')->name('home');
    Route::get('/kapcsolat', fn () => 'contact')->name('contact');
    app('router')->getRoutes()->refreshNameLookups();
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

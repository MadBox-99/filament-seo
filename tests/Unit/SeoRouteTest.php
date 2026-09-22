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

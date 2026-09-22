<?php

use Madbox99\FilamentSeo\Filament\Resources\SeoRoutes\Pages\EditSeoRoute;
use Madbox99\FilamentSeo\Filament\Resources\SeoRoutes\Pages\ListSeoRoutes;
use Madbox99\FilamentSeo\Filament\Resources\SeoRoutes\SeoRouteResource;
use Madbox99\FilamentSeo\Models\SeoRoute;
use Madbox99\FilamentSeo\Tests\Fixtures\User;
use Livewire\Livewire;

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

    Livewire::test(ListSeoRoutes::class)
        ->assertSuccessful()
        ->assertSee('Főoldal')
        ->assertSee('Kapcsolat');
});

it('saves the seo meta from the edit form', function () {
    $route = SeoRoute::create(['route_name' => 'home', 'label' => 'Főoldal']);

    Livewire::test(EditSeoRoute::class, ['record' => $route->getKey()])
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

    Livewire::test(EditSeoRoute::class, ['record' => $route->getKey()])
        ->fillForm(['seoMeta.schema_markup' => '{ ez nem json'])
        ->call('save')
        ->assertHasFormErrors(['seoMeta.schema_markup']);
});

it('does not let a save alter the label or route name', function () {
    $route = SeoRoute::create(['route_name' => 'home', 'label' => 'Főoldal']);

    Livewire::test(EditSeoRoute::class, ['record' => $route->getKey()])
        ->fillForm([
            'label' => 'Átírt címke',
            'route_name' => 'atirt.route',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($route->refresh()->label)->toBe('Főoldal')
        ->and($route->route_name)->toBe('home');
});

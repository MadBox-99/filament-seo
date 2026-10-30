<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Madbox99\FilamentSeo\Pages\ManageSeoSettings;
use Madbox99\FilamentSeo\Tests\Fixtures\User;

function settingsPageUser(): User
{
    return User::create([
        'name' => 'Test Admin',
        'email' => 'admin-'.uniqid().'@test.com',
        'password' => Hash::make('password'),
    ]);
}

afterEach(function () {
    app()->setLocale('en');
});

it('renders the settings page in English', function () {
    app()->setLocale('en');

    $this->actingAs(settingsPageUser())
        ->get('/admin/manage-seo-settings')
        ->assertOk()
        ->assertSee('Global SEO Settings')
        ->assertSee('Defaults')
        ->assertSee('Title Pattern')
        ->assertSee('Excluded URLs from Sitemap')
        ->assertSee('Save');
});

it('renders the settings page in Hungarian', function () {
    app()->setLocale('hu');

    $this->actingAs(settingsPageUser())
        ->get('/admin/manage-seo-settings')
        ->assertOk()
        ->assertSee('Általános SEO-beállítások')
        ->assertSee('Alapértelmezések')
        ->assertSee('Címminta')
        ->assertSee('Oldaltérkép')
        ->assertSee('Oldaltérképből kizárt címek')
        ->assertSee('Szervezet (Organization)')
        ->assertSee('Mentés')
        ->assertDontSee('Global SEO Settings')
        ->assertDontSee('Title Pattern');
});

it('translates the navigation label and title', function () {
    app()->setLocale('hu');

    expect(ManageSeoSettings::getNavigationLabel())->toBe('Általános SEO');
    expect(app(ManageSeoSettings::class)->getTitle())->toBe('Általános SEO-beállítások');

    app()->setLocale('en');

    expect(ManageSeoSettings::getNavigationLabel())->toBe('Global SEO');
    expect(app(ManageSeoSettings::class)->getTitle())->toBe('Global SEO Settings');
});

it('has the same settings keys in every locale', function () {
    $keys = fn (string $locale): array => array_keys(Arr::dot(
        require __DIR__."/../../resources/lang/{$locale}/filament-seo.php"
    ));

    expect($keys('hu'))->toEqualCanonicalizing($keys('en'));
});

it('sends the saved notification in Hungarian', function () {
    app()->setLocale('hu');

    $this->actingAs(settingsPageUser());

    Livewire::test(ManageSeoSettings::class)
        ->call('save')
        ->assertNotified('Beállítások mentve.');
});

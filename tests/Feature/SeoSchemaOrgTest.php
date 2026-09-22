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

it('keeps zero-like values while dropping empty ones from the organization block', function () {
    SeoSetting::current()->update([
        'schema_org_data' => [
            'name' => 'Teszt Kft.',
            'description' => '',
            'foundingYear' => '0',
        ],
    ]);

    $this->get('/');

    $organisation = Seo::resolve()->schemas[0];

    expect($organisation)->not->toHaveKey('description')
        ->and($organisation['foundingYear'])->toBe('0');
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

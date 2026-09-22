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

afterEach(function () {
    SeoManager::siteNameUsing(null);
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

it('keeps the route nofollow directive even when the model record sets no flags', function () {
    SeoRoute::create(['route_name' => 'home', 'label' => 'Főoldal'])
        ->seoMeta()->create(['no_follow' => true]);

    $post = Post::create(['title' => 'Cikk']);
    $post->seoMeta()->create(['title' => 'Cikk SEO címe']);

    $this->get('/');

    expect(Seo::for($post)->resolve()->robots)->toBe('index, nofollow');
});

it('keeps the route noindex directive even when the model record sets no flags', function () {
    SeoRoute::create(['route_name' => 'home', 'label' => 'Főoldal'])
        ->seoMeta()->create(['no_index' => true]);

    $post = Post::create(['title' => 'Cikk']);
    $post->seoMeta()->create(['title' => 'Cikk SEO címe']);

    $this->get('/');

    expect(Seo::for($post)->resolve()->robots)->toBe('noindex, follow');
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

it('restores the default site name resolution when siteNameUsing is cleared', function () {
    SeoManager::siteNameUsing(null);

    expect(Seo::siteName())->toBe((string) config('app.name'));
});

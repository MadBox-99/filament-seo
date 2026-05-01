<?php

use Madbox99\FilamentSeo\Models\SeoMeta;
use Madbox99\FilamentSeo\Tests\Fixtures\Post;

// ──────────────────────────────────────────────────────────────
// Model Basics
// ──────────────────────────────────────────────────────────────

it('can create a SeoMeta instance', function () {
    $meta = new SeoMeta;

    expect($meta)->toBeInstanceOf(SeoMeta::class);
});

it('uses the correct table name', function () {
    $meta = new SeoMeta;

    expect($meta->getTable())->toBe('seo_metas');
});

it('uses configured table name from config', function () {
    config()->set('filament-seo.table_names.seo_metas', 'custom_seo_metas');

    $meta = new SeoMeta;

    expect($meta->getTable())->toBe('custom_seo_metas');

    // Reset
    config()->set('filament-seo.table_names.seo_metas', 'seo_metas');
});

it('has correct casts', function () {
    $meta = new SeoMeta;
    $casts = $meta->getCasts();

    expect($casts)->toHaveKey('no_index', 'boolean');
    expect($casts)->toHaveKey('no_follow', 'boolean');
    expect($casts)->toHaveKey('schema_markup', 'array');
});

// ──────────────────────────────────────────────────────────────
// CRUD Operations
// ──────────────────────────────────────────────────────────────

it('can create seo meta for a post', function () {
    $post = Post::create(['title' => 'My Post']);

    $meta = $post->seoMeta()->create([
        'title' => 'SEO Title',
        'description' => 'SEO Description',
        'keywords' => 'php, laravel, filament',
        'canonical_url' => 'https://example.com/my-post',
        'no_index' => false,
        'no_follow' => false,
        'og_type' => 'article',
    ]);

    expect($meta)->toBeInstanceOf(SeoMeta::class);
    expect($meta->title)->toBe('SEO Title');
    expect($meta->description)->toBe('SEO Description');
    expect($meta->keywords)->toBe('php, laravel, filament');
    expect($meta->canonical_url)->toBe('https://example.com/my-post');
    expect($meta->no_index)->toBeFalse();
    expect($meta->no_follow)->toBeFalse();
    expect($meta->og_type)->toBe('article');
});

it('can update seo meta', function () {
    $post = Post::create(['title' => 'My Post']);
    $meta = $post->seoMeta()->create(['title' => 'Original']);

    $meta->update(['title' => 'Updated']);

    expect($meta->fresh()->title)->toBe('Updated');
});

it('can delete seo meta', function () {
    $post = Post::create(['title' => 'My Post']);
    $meta = $post->seoMeta()->create(['title' => 'Delete Me']);

    $meta->delete();

    expect($post->fresh()->seoMeta)->toBeNull();
    expect(SeoMeta::count())->toBe(0);
});

it('can store null values for optional fields', function () {
    $post = Post::create(['title' => 'My Post']);

    $meta = $post->seoMeta()->create([
        'title' => null,
        'description' => null,
        'keywords' => null,
        'canonical_url' => null,
        'og_type' => null,
        'schema_markup' => null,
    ]);

    expect($meta->title)->toBeNull();
    expect($meta->description)->toBeNull();
    expect($meta->keywords)->toBeNull();
    expect($meta->canonical_url)->toBeNull();
    expect($meta->og_type)->toBeNull();
    expect($meta->schema_markup)->toBeNull();
});

it('stores and retrieves schema_markup as array', function () {
    $post = Post::create(['title' => 'My Post']);

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => 'My Post',
        'author' => [
            '@type' => 'Person',
            'name' => 'John Doe',
        ],
    ];

    $meta = $post->seoMeta()->create(['schema_markup' => $schema]);

    $meta->refresh();

    expect($meta->schema_markup)->toBe($schema);
    expect($meta->schema_markup['@type'])->toBe('Article');
});

// ──────────────────────────────────────────────────────────────
// Polymorphic Relationship
// ──────────────────────────────────────────────────────────────

it('has a seoable morph-to relationship', function () {
    $post = Post::create(['title' => 'My Post']);
    $meta = $post->seoMeta()->create(['title' => 'Test']);

    expect($meta->seoable)->toBeInstanceOf(Post::class);
    expect($meta->seoable->id)->toBe($post->id);
});

it('correctly stores seoable_type', function () {
    $post = Post::create(['title' => 'My Post']);
    $meta = $post->seoMeta()->create(['title' => 'Test']);

    expect($meta->seoable_type)->toBe(Post::class);
    expect($meta->seoable_id)->toBe($post->id);
});

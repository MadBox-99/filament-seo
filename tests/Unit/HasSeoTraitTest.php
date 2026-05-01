<?php

use Illuminate\Database\Eloquent\Relations\MorphOne;
use Madbox99\FilamentSeo\Models\SeoMeta;
use Madbox99\FilamentSeo\Tests\Fixtures\Post;

it('adds seoMeta relationship to model', function () {
    $post = new Post;

    expect($post->seoMeta())->toBeInstanceOf(MorphOne::class);
});

it('seoMeta relationship returns SeoMeta model', function () {
    $post = Post::create(['title' => 'Test']);
    $post->seoMeta()->create(['title' => 'SEO Title']);

    expect($post->seoMeta)->toBeInstanceOf(SeoMeta::class);
});

it('seoMeta relationship returns null when no meta exists', function () {
    $post = Post::create(['title' => 'Test']);

    expect($post->seoMeta)->toBeNull();
});

it('can eager load seoMeta relationship', function () {
    $post = Post::create(['title' => 'Test']);
    $post->seoMeta()->create(['title' => 'Eager Load Test']);

    $loaded = Post::with('seoMeta')->find($post->id);

    expect($loaded->relationLoaded('seoMeta'))->toBeTrue();
    expect($loaded->seoMeta->title)->toBe('Eager Load Test');
});

it('deleting the parent does not cascade to seo meta by default', function () {
    $post = Post::create(['title' => 'Test']);
    $post->seoMeta()->create(['title' => 'Orphan Test']);

    $metaId = $post->seoMeta->id;

    $post->delete();

    // Without cascade delete, meta remains orphaned
    expect(SeoMeta::find($metaId))->not->toBeNull();
});

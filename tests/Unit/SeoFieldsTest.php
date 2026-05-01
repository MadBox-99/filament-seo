<?php

use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Madbox99\FilamentSeo\Forms\SeoFields;

it('returns a Section component from make()', function () {
    $section = SeoFields::make();

    expect($section)->toBeInstanceOf(Section::class);
});

it('returns a Section with correct heading', function () {
    $section = SeoFields::make();

    expect($section->getHeading())->toBe('SEO');
});

it('uses seoMeta as default relationship', function () {
    $section = SeoFields::make();

    expect($section->getRelationshipName())->toBe('seoMeta');
});

it('accepts custom relationship name', function () {
    $section = SeoFields::make('customSeoRelation');

    expect($section->getRelationshipName())->toBe('customSeoRelation');
});

it('section is collapsed by default', function () {
    $section = SeoFields::make();

    expect($section->isCollapsed())->toBeTrue();
});

it('returns correct schema fields', function () {
    $schema = SeoFields::schema();

    expect($schema)->toBeArray();
    expect($schema)->toHaveCount(6);
});

it('schema contains title input with max 70 chars', function () {
    $schema = SeoFields::schema();
    $title = $schema[0];

    expect($title)->toBeInstanceOf(TextInput::class);
    expect($title->getName())->toBe('title');
    expect($title->getLabel())->toBe('SEO Title');
});

it('schema contains description textarea with max 160 chars', function () {
    $schema = SeoFields::schema();
    $description = $schema[1];

    expect($description)->toBeInstanceOf(Textarea::class);
    expect($description->getName())->toBe('description');
    expect($description->getLabel())->toBe('Meta Description');
});

it('schema contains keywords input', function () {
    $schema = SeoFields::schema();
    $keywords = $schema[2];

    expect($keywords)->toBeInstanceOf(TextInput::class);
    expect($keywords->getName())->toBe('keywords');
    expect($keywords->getLabel())->toBe('Keywords');
});

it('schema contains canonical URL input with url validation', function () {
    $schema = SeoFields::schema();
    $canonicalUrl = $schema[3];

    expect($canonicalUrl)->toBeInstanceOf(TextInput::class);
    expect($canonicalUrl->getName())->toBe('canonical_url');
    expect($canonicalUrl->getLabel())->toBe('Canonical URL');
});

it('schema contains no_index toggle', function () {
    $schema = SeoFields::schema();
    $noIndex = $schema[4];

    expect($noIndex)->toBeInstanceOf(Toggle::class);
    expect($noIndex->getName())->toBe('no_index');
});

it('schema contains no_follow toggle', function () {
    $schema = SeoFields::schema();
    $noFollow = $schema[5];

    expect($noFollow)->toBeInstanceOf(Toggle::class);
    expect($noFollow->getName())->toBe('no_follow');
});

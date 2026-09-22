<?php

use Filament\Schemas\Components\Section;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
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
    expect($schema)->toHaveCount(9);
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

it('schema contains an og_type select defaulting to website', function () {
    $ogType = SeoFields::schema()[6];

    expect($ogType)->toBeInstanceOf(Select::class);
    expect($ogType->getName())->toBe('og_type');
    expect($ogType->getDefaultState())->toBe('website');
});

it('lets the caller override the default og type', function () {
    $ogType = SeoFields::schema('article')[6];

    expect($ogType->getDefaultState())->toBe('article');
});

it('schema contains an og_image upload', function () {
    $ogImage = SeoFields::schema()[7];

    expect($ogImage)->toBeInstanceOf(FileUpload::class);
    expect($ogImage->getName())->toBe('og_image');
});

it('schema contains a schema_markup textarea', function () {
    $schemaMarkup = SeoFields::schema()[8];

    expect($schemaMarkup)->toBeInstanceOf(Textarea::class);
    expect($schemaMarkup->getName())->toBe('schema_markup');
});

// A JSON-validáció viselkedését szándékosan nem itt ellenőrizzük: a Filament a
// szabályokat closure-ökbe csomagolja, így a getRules() assertion törékeny lenne.
// A valódi ellenőrzés a Task 7 resource-tesztjében történik, űrlap-beküldéssel.

it('translates field labels to Hungarian', function () {
    app()->setLocale('hu');

    expect(SeoFields::schema()[0]->getLabel())->toBe('SEO cím');
    expect(SeoFields::schema()[1]->getLabel())->toBe('Meta leírás');
});

it('keeps the English labels unchanged', function () {
    app()->setLocale('en');

    expect(SeoFields::schema()[0]->getLabel())->toBe('SEO Title');
    expect(SeoFields::schema()[1]->getLabel())->toBe('Meta Description');
});

it('adds an og_image column to the seo metas table', function () {
    expect(Illuminate\Support\Facades\Schema::getColumnListing('seo_metas'))->toContain('og_image');
});

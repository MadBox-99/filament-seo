<?php

namespace Madbox99\FilamentSeo\Forms;

use Filament\Forms;
use Filament\Schemas\Components\Section;

class SeoFields
{
    public static function make(string $relationship = 'seoMeta'): Section
    {
        return Section::make('SEO')
            ->relationship($relationship)
            ->schema(static::schema())
            ->collapsed();
    }

    /** @return array<Forms\Components\Component> */
    public static function schema(): array
    {
        return [
            Forms\Components\TextInput::make('title')
                ->label('SEO Title')
                ->maxLength(70),
            Forms\Components\Textarea::make('description')
                ->label('Meta Description')
                ->maxLength(160)
                ->rows(3),
            Forms\Components\TextInput::make('keywords')
                ->label('Keywords')
                ->maxLength(255),
            Forms\Components\TextInput::make('canonical_url')
                ->label('Canonical URL')
                ->url()
                ->maxLength(255),
            Forms\Components\Toggle::make('no_index')
                ->label('Hide from search engines (noindex)'),
            Forms\Components\Toggle::make('no_follow')
                ->label('No follow links (nofollow)'),
        ];
    }
}

<?php

namespace Madbox99\FilamentSeo\Forms;

use Closure;
use Filament\Forms;
use Filament\Schemas;

class SeoFields
{
    public static function make(string $relationship = 'seoMeta', string $defaultOgType = 'website'): Schemas\Components\Section
    {
        return Schemas\Components\Section::make(__('filament-seo::filament-seo.section.heading'))
            ->relationship($relationship)
            ->schema(static::schema($defaultOgType))
            ->collapsed();
    }

    /** @return array<Forms\Components\Component> */
    public static function schema(string $defaultOgType = 'website'): array
    {
        return [
            Forms\Components\TextInput::make('title')
                ->label(__('filament-seo::filament-seo.fields.title'))
                ->maxLength(70),
            Forms\Components\Textarea::make('description')
                ->label(__('filament-seo::filament-seo.fields.description'))
                ->maxLength(160)
                ->rows(3),
            Forms\Components\TextInput::make('keywords')
                ->label(__('filament-seo::filament-seo.fields.keywords'))
                ->maxLength(255),
            Forms\Components\TextInput::make('canonical_url')
                ->label(__('filament-seo::filament-seo.fields.canonical_url'))
                ->url()
                ->maxLength(255),
            Forms\Components\Toggle::make('no_index')
                ->label(__('filament-seo::filament-seo.fields.no_index')),
            Forms\Components\Toggle::make('no_follow')
                ->label(__('filament-seo::filament-seo.fields.no_follow')),
            Forms\Components\Select::make('og_type')
                ->label(__('filament-seo::filament-seo.fields.og_type'))
                ->options([
                    'website' => 'website',
                    'article' => 'article',
                    'product' => 'product',
                ])
                ->default($defaultOgType),
            Forms\Components\FileUpload::make('og_image')
                ->label(__('filament-seo::filament-seo.fields.og_image'))
                ->image()
                ->disk(config('filament-seo.og_image_disk', 'public'))
                ->directory('seo')
                ->visibility('public'),
            Forms\Components\Textarea::make('schema_markup')
                ->label(__('filament-seo::filament-seo.fields.schema_markup'))
                ->helperText(__('filament-seo::filament-seo.fields.schema_markup_help'))
                ->rows(5)
                ->rules([
                    'nullable',
                    'json',
                    fn (): Closure => static function (string $attribute, mixed $value, Closure $fail): void {
                        if (blank($value)) {
                            return;
                        }

                        $decoded = json_decode((string) $value, true);

                        if (json_last_error() !== JSON_ERROR_NONE) {
                            // Malformed JSON is already reported by the `json` rule above.
                            return;
                        }

                        if (! is_array($decoded)) {
                            $fail(__('filament-seo::filament-seo.fields.schema_markup_not_object'));
                        }
                    },
                ])
                ->formatStateUsing(fn ($state): ?string => is_array($state)
                    ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                    : $state)
                ->dehydrateStateUsing(function (?string $state): ?array {
                    if (blank($state)) {
                        return null;
                    }

                    $decoded = json_decode($state, true);

                    return is_array($decoded) ? $decoded : null;
                }),
        ];
    }
}

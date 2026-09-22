<?php

namespace Madbox99\FilamentSeo\Filament\Resources\SeoRoutes;

use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Madbox99\FilamentSeo\Filament\Resources\SeoRoutes\Pages\EditSeoRoute;
use Madbox99\FilamentSeo\Filament\Resources\SeoRoutes\Pages\ListSeoRoutes;
use Madbox99\FilamentSeo\FilamentSeoPlugin;
use Madbox99\FilamentSeo\Forms\SeoFields;
use Madbox99\FilamentSeo\Models\SeoRoute;
use UnitEnum;

class SeoRouteResource extends Resource
{
    protected static ?string $model = SeoRoute::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-magnifying-glass';

    protected static string|UnitEnum|null $navigationGroup = 'SEO';

    protected static ?int $navigationSort = 2;

    #[\Override]
    public static function getNavigationGroup(): string|UnitEnum|null
    {
        if (filament()->hasPlugin('filament-seo')) {
            /** @var FilamentSeoPlugin $plugin */
            $plugin = filament()->getPlugin('filament-seo');

            return $plugin->getNavigationGroup() ?? static::$navigationGroup;
        }

        return static::$navigationGroup;
    }

    #[\Override]
    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        if (filament()->hasPlugin('filament-seo')) {
            /** @var FilamentSeoPlugin $plugin */
            $plugin = filament()->getPlugin('filament-seo');

            return $plugin->getNavigationIcon() ?? static::$navigationIcon;
        }

        return static::$navigationIcon;
    }

    #[\Override]
    public static function getNavigationSort(): ?int
    {
        if (filament()->hasPlugin('filament-seo')) {
            /** @var FilamentSeoPlugin $plugin */
            $plugin = filament()->getPlugin('filament-seo');

            return $plugin->getNavigationSort() ?? static::$navigationSort;
        }

        return static::$navigationSort;
    }

    public static function getModelLabel(): string
    {
        return __('filament-seo::filament-seo.routes.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament-seo::filament-seo.routes.plural_label');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make()->schema([
                TextInput::make('label')
                    ->label(__('filament-seo::filament-seo.routes.columns.label'))
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('route_name')
                    ->label(__('filament-seo::filament-seo.routes.columns.route_name'))
                    ->disabled()
                    ->dehydrated(false),
            ])->columns(2),
            SeoFields::make(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->modifyQueryUsing(fn ($query) => $query->with('seoMeta'))
            ->columns([
                TextColumn::make('label')
                    ->label(__('filament-seo::filament-seo.routes.columns.label'))
                    ->searchable(),
                TextColumn::make('seoMeta.title')
                    ->label(__('filament-seo::filament-seo.routes.columns.title'))
                    ->placeholder('—')
                    ->limit(50),
                IconColumn::make('has_schema')
                    ->label(__('filament-seo::filament-seo.routes.columns.has_schema'))
                    ->boolean()
                    ->getStateUsing(fn (SeoRoute $record): bool => filled($record->seoMeta?->schema_markup)),
                TextColumn::make('updated_at')
                    ->label(__('filament-seo::filament-seo.routes.columns.updated_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSeoRoutes::route('/'),
            'edit' => EditSeoRoute::route('/{record}/edit'),
        ];
    }
}

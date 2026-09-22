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
use Illuminate\Database\Eloquent\Model;
use Madbox99\FilamentSeo\Filament\Resources\SeoRoutes\Pages\EditSeoRoute;
use Madbox99\FilamentSeo\Filament\Resources\SeoRoutes\Pages\ListSeoRoutes;
use Madbox99\FilamentSeo\Forms\SeoFields;
use Madbox99\FilamentSeo\Models\SeoRoute;
use UnitEnum;

class SeoRouteResource extends Resource
{
    protected static ?string $model = SeoRoute::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-magnifying-glass';

    protected static string|UnitEnum|null $navigationGroup = 'SEO';

    protected static ?int $navigationSort = 2;

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
                    ->label('Route')
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

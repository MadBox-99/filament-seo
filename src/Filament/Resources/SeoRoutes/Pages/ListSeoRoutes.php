<?php

namespace Madbox99\FilamentSeo\Filament\Resources\SeoRoutes\Pages;

use Filament\Resources\Pages\ListRecords;
use Madbox99\FilamentSeo\Filament\Resources\SeoRoutes\SeoRouteResource;

class ListSeoRoutes extends ListRecords
{
    protected static string $resource = SeoRouteResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}

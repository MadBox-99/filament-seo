<?php

namespace Madbox99\FilamentSeo\Filament\Resources\SeoRoutes\Pages;

use Filament\Resources\Pages\EditRecord;
use Madbox99\FilamentSeo\Filament\Resources\SeoRoutes\SeoRouteResource;

class EditSeoRoute extends EditRecord
{
    protected static string $resource = SeoRouteResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}

<?php

namespace Madbox99\FilamentSeo\Models;

use Illuminate\Database\Eloquent\Model;
use Madbox99\FilamentSeo\Concerns\HasSeo;

class SeoRoute extends Model
{
    use HasSeo;

    protected $guarded = [];

    #[\Override]
    public function getTable(): string
    {
        return config('filament-seo.table_names.seo_routes', 'seo_routes');
    }

    #[\Override]
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public static function forRoute(string $routeName): ?static
    {
        return static::query()->where('route_name', $routeName)->first();
    }
}

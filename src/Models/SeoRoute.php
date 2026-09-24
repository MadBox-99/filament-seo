<?php

namespace Madbox99\FilamentSeo\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Madbox99\FilamentSeo\Concerns\HasSeo;

/**
 * Lásd a {@see SeoMeta} megjegyzését: a táblanév `config()`-ból jön, ezért
 * kell felsorolni az oszlopokat.
 *
 * @property int $id
 * @property string $route_name
 * @property string $label
 * @property int $sort_order
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
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

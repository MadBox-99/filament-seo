<?php

namespace Madbox99\FilamentSeo\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A táblanév `config()`-ból jön, amit a statikus elemzés nem tud feloldani:
 * a migrációból így egyetlen oszlopot sem ismer fel. Ezért sorolja fel őket
 * ez a blokk — enélkül a csomagot használó alkalmazásban minden mezőre
 * „undefined property" jön.
 *
 * @property int $id
 * @property string $seoable_type
 * @property int $seoable_id
 * @property string|null $title
 * @property string|null $description
 * @property string|null $keywords
 * @property string|null $canonical_url
 * @property bool $no_index
 * @property bool $no_follow
 * @property string|null $og_type
 * @property string|null $og_image
 * @property array<string, mixed>|null $schema_markup
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
class SeoMeta extends Model
{
    protected $guarded = [];

    #[\Override]
    public function getTable(): string
    {
        return config('filament-seo.table_names.seo_metas', 'seo_metas');
    }

    #[\Override]
    protected function casts(): array
    {
        return [
            'no_index' => 'boolean',
            'no_follow' => 'boolean',
            'schema_markup' => 'array',
        ];
    }

    /** @return MorphTo<Model, $this> */
    public function seoable(): MorphTo
    {
        return $this->morphTo();
    }
}

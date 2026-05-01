<?php

namespace Madbox99\FilamentSeo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

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

    public function seoable(): MorphTo
    {
        return $this->morphTo();
    }
}

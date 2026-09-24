<?php

namespace Madbox99\FilamentSeo\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphOne;
use Madbox99\FilamentSeo\Models\SeoMeta;

trait HasSeo
{
    /** @return MorphOne<SeoMeta, $this> */
    public function seoMeta(): MorphOne
    {
        return $this->morphOne(SeoMeta::class, 'seoable');
    }
}

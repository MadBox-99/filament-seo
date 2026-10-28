<?php

declare(strict_types=1);

namespace Madbox99\FilamentSeo\Facades;

use Illuminate\Support\Facades\Facade;
use Madbox99\FilamentSeo\SeoManager;

/**
 * @method static SeoManager for(\Illuminate\Database\Eloquent\Model $model, ?string $fallbackTitle = null)
 * @method static SeoManager ogImage(?string $url)
 * @method static SeoManager ogType(?string $type)
 * @method static SeoManager reset()
 * @method static string siteName()
 * @method static void siteNameUsing(?\Closure $callback)
 * @method static \Madbox99\FilamentSeo\Data\SeoData resolve()
 *
 * @see SeoManager
 */
class Seo extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SeoManager::class;
    }
}

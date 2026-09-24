<?php

namespace Madbox99\FilamentSeo\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Lásd a {@see SeoMeta} megjegyzését: a táblanév `config()`-ból jön, ezért
 * kell felsorolni az oszlopokat.
 *
 * @property int $id
 * @property string $default_title_pattern
 * @property string|null $default_description
 * @property string|null $default_og_image
 * @property string|null $robots_txt
 * @property array<int, string>|null $sitemap_excluded_urls
 * @property string $schema_org_type
 * @property array<string, mixed>|null $schema_org_data
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
class SeoSetting extends Model
{
    protected $guarded = [];

    #[\Override]
    public function getTable(): string
    {
        return config('filament-seo.table_names.seo_settings', 'seo_settings');
    }

    #[\Override]
    protected function casts(): array
    {
        return [
            'sitemap_excluded_urls' => 'array',
            'schema_org_data' => 'array',
        ];
    }

    public static function current(): static
    {
        return static::firstOrCreate([], [
            'default_title_pattern' => '{title} | {site_name}',
            'default_description' => '',
            'default_og_image' => '',
            'robots_txt' => "User-agent: *\nAllow: /\n\nSitemap: /sitemap.xml",
            'sitemap_excluded_urls' => [],
            'schema_org_type' => 'Organization',
            'schema_org_data' => [
                'name' => '',
                'url' => '',
                'logo' => '',
                'description' => '',
            ],
        ]);
    }
}

<?php

namespace Madbox99\FilamentSeo\Models;

use Illuminate\Database\Eloquent\Model;

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

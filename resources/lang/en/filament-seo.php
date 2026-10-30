<?php

return [
    'section' => [
        'heading' => 'SEO',
    ],
    'fields' => [
        'title' => 'SEO Title',
        'description' => 'Meta Description',
        'keywords' => 'Keywords',
        'canonical_url' => 'Canonical URL',
        'no_index' => 'Hide from search engines (noindex)',
        'no_follow' => 'No follow links (nofollow)',
        'og_type' => 'OG type',
        'og_image' => 'Share image (OG)',
        'schema_markup' => 'Custom JSON-LD (schema.org)',
        'schema_markup_help' => 'Optional, valid JSON. Added next to the generated structured data.',
        'schema_markup_not_object' => 'The custom JSON-LD must be a JSON object or array, not a bare value.',
    ],
    'routes' => [
        'label' => 'Page SEO',
        'plural_label' => 'Page SEO',
        'columns' => [
            'label' => 'Page',
            'route_name' => 'Route',
            'title' => 'SEO title',
            'has_schema' => 'JSON-LD',
            'updated_at' => 'Modified',
        ],
    ],
    'settings' => [
        'navigation_label' => 'Global SEO',
        'title' => 'Global SEO Settings',
        'sections' => [
            'defaults' => 'Defaults',
            'robots_txt' => 'Robots.txt',
            'sitemap' => 'Sitemap',
            'schema_org' => 'Schema.org',
        ],
        'fields' => [
            'default_title_pattern' => 'Title Pattern',
            'default_title_pattern_help' => 'Available placeholders: {title}, {site_name}',
            'default_description' => 'Default Meta Description',
            'default_og_image' => 'Default OG Image',
            'robots_txt' => 'robots.txt Content',
            'sitemap_excluded_urls' => 'Excluded URLs from Sitemap',
            'schema_org_type' => 'Schema Type',
            'schema_org_data' => 'Schema.org Data',
        ],
        'schema_org_types' => [
            'Organization' => 'Organization',
            'LocalBusiness' => 'LocalBusiness',
            'Corporation' => 'Corporation',
            'Person' => 'Person',
        ],
        'actions' => [
            'save' => 'Save',
        ],
        'notifications' => [
            'saved' => 'Settings saved.',
        ],
    ],
];

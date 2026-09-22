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
    ],
    'routes' => [
        'label' => 'Page SEO',
        'plural_label' => 'Page SEO',
        'columns' => [
            'label' => 'Page',
            'title' => 'SEO title',
            'has_schema' => 'JSON-LD',
            'updated_at' => 'Modified',
        ],
    ],
];

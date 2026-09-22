<?php

return [
    'section' => [
        'heading' => 'SEO',
    ],
    'fields' => [
        'title' => 'SEO cím',
        'description' => 'Meta leírás',
        'keywords' => 'Kulcsszavak',
        'canonical_url' => 'Kanonikus URL',
        'no_index' => 'Elrejtés a keresők elől (noindex)',
        'no_follow' => 'Linkek nem követése (nofollow)',
        'og_type' => 'OG típus',
        'og_image' => 'Megosztási kép (OG)',
        'schema_markup' => 'Egyedi JSON-LD (schema.org)',
        'schema_markup_help' => 'Opcionális, érvényes JSON. Az automatikusan generált structured data mellé kerül.',
    ],
    'routes' => [
        'label' => 'Oldal SEO',
        'plural_label' => 'Oldalak SEO-ja',
        'columns' => [
            'label' => 'Oldal',
            'route_name' => 'Route (rendszernév)',
            'title' => 'SEO cím',
            'has_schema' => 'JSON-LD',
            'updated_at' => 'Módosítva',
        ],
    ],
];

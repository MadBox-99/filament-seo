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
        'schema_markup_not_object' => 'Az egyedi JSON-LD csak JSON objektum vagy tömb lehet, önálló érték nem.',
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
    'settings' => [
        'navigation_label' => 'Általános SEO',
        'title' => 'Általános SEO-beállítások',
        'sections' => [
            'defaults' => 'Alapértelmezések',
            'robots_txt' => 'robots.txt',
            'sitemap' => 'Oldaltérkép',
            'schema_org' => 'Schema.org',
        ],
        'fields' => [
            'default_title_pattern' => 'Címminta',
            'default_title_pattern_help' => 'Használható helyőrzők: {title} (az oldal címe), {site_name} (a webhely neve).',
            'default_description' => 'Alapértelmezett meta-leírás',
            'default_og_image' => 'Alapértelmezett megosztási kép (OG)',
            'robots_txt' => 'A robots.txt tartalma',
            'sitemap_excluded_urls' => 'Oldaltérképből kizárt címek',
            'schema_org_type' => 'Típus',
            'schema_org_data' => 'Schema.org adatok',
        ],
        'schema_org_types' => [
            'Organization' => 'Szervezet (Organization)',
            'LocalBusiness' => 'Helyi vállalkozás (LocalBusiness)',
            'Corporation' => 'Vállalat (Corporation)',
            'Person' => 'Személy (Person)',
        ],
        'actions' => [
            'save' => 'Mentés',
        ],
        'notifications' => [
            'saved' => 'Beállítások mentve.',
        ],
    ],
];

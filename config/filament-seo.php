<?php

return [
    'table_names' => [
        'seo_metas' => 'seo_metas',
        'seo_settings' => 'seo_settings',
        'seo_routes' => 'seo_routes',
    ],

    /*
     * Fix oldalak, amelyeknek adminból szerkeszthető SEO-juk legyen.
     * Kulcs: Laravel named route neve. Érték: az adminban megjelenő címke.
     * A `php artisan seo:sync-routes` parancs ebből tölti fel a seo_routes táblát.
     */
    'routes' => [
        // 'home' => 'Főoldal',
        // 'contact' => 'Kapcsolat',
    ],

    /*
     * Az a named route, amit a csomag főoldalnak tekint. A főoldalon kerül ki
     * az Organization és a WebSite JSON-LD blokk a globális beállításokból.
     */
    'home_route' => 'home',

    /*
     * Az OG kép feltöltéséhez és felolvasásához használt Filesystem disk.
     * Ugyanezt olvassa a SeoManager::imageUrl() és a globális beállítások
     * default_og_image mezője is, hogy a két feltöltési útvonal ne térjen el.
     */
    'og_image_disk' => 'public',
];

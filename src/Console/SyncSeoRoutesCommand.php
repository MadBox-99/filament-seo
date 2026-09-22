<?php

namespace Madbox99\FilamentSeo\Console;

use Illuminate\Console\Command;
use Illuminate\Routing\Router;
use Madbox99\FilamentSeo\Models\SeoRoute;

class SyncSeoRoutesCommand extends Command
{
    protected $signature = 'seo:sync-routes';

    protected $description = 'Sync the editable page SEO records from config/filament-seo.php';

    public function handle(Router $router): int
    {
        /** @var array<string, string> $routes */
        $routes = config('filament-seo.routes', []);

        $synced = 0;
        $sortOrder = 0;

        foreach ($routes as $routeName => $label) {
            $sortOrder++;

            if (! $router->has($routeName)) {
                $this->warn("Skipped [{$routeName}]: the application has no named route by that name.");

                continue;
            }

            SeoRoute::query()->updateOrCreate(
                ['route_name' => $routeName],
                ['label' => $label, 'sort_order' => $sortOrder],
            );

            $synced++;
        }

        $this->info("Synced {$synced} page SEO record(s).");

        $orphans = SeoRoute::query()
            ->whereNotIn('route_name', array_keys($routes))
            ->pluck('route_name');

        if ($orphans->isNotEmpty()) {
            $this->warn('Kept but no longer configured: '.$orphans->implode(', '));
        }

        return self::SUCCESS;
    }
}

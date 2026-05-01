<?php

declare(strict_types=1);

namespace Madbox99\FilamentSeo;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentSeoServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-seo';

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasConfigFile()
            ->hasViews()
            ->discoversMigrations()
            ->runsMigrations();
    }
}

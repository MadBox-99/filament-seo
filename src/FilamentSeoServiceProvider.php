<?php

declare(strict_types=1);

namespace Madbox99\FilamentSeo;

use Madbox99\FilamentSeo\Console\SyncSeoRoutesCommand;
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
            ->hasTranslations()
            ->hasCommand(SyncSeoRoutesCommand::class)
            ->discoversMigrations()
            ->runsMigrations();
    }
}

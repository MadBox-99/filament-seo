<?php

namespace Madbox99\FilamentSeo\Tests;

use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Support\SupportServiceProvider;
use Illuminate\Support\ViewErrorBag;
use Livewire\LivewireServiceProvider;
use Madbox99\FilamentSeo\FilamentSeoServiceProvider;
use Madbox99\FilamentSeo\Tests\Fixtures\AdminPanelProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        // Share an empty error bag with views to prevent Livewire rendering issues
        app('view')->share('errors', new ViewErrorBag);
    }

    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            SupportServiceProvider::class,
            FormsServiceProvider::class,
            NotificationsServiceProvider::class,
            FilamentServiceProvider::class,
            FilamentSeoServiceProvider::class,
            AdminPanelProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('filament-seo.table_names', [
            'seo_metas' => 'seo_metas',
            'seo_settings' => 'seo_settings',
        ]);

        $app['config']->set('app.key', 'base64:9InmilS+Be4PN9ArF+ZvlOszL6gc1pLRf1hhZcgJgxI=');
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}

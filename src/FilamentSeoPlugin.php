<?php

namespace Madbox99\FilamentSeo;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Madbox99\FilamentSeo\Pages\ManageSeoSettings;

class FilamentSeoPlugin implements Plugin
{
    protected ?string $navigationGroup = 'SEO';

    protected ?string $navigationIcon = 'heroicon-o-magnifying-glass';

    protected ?int $navigationSort = 1;

    public function getId(): string
    {
        return 'filament-seo';
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public function navigationGroup(?string $group): static
    {
        $this->navigationGroup = $group;

        return $this;
    }

    public function navigationIcon(?string $icon): static
    {
        $this->navigationIcon = $icon;

        return $this;
    }

    public function navigationSort(?int $sort): static
    {
        $this->navigationSort = $sort;

        return $this;
    }

    public function getNavigationGroup(): ?string
    {
        return $this->navigationGroup;
    }

    public function getNavigationIcon(): ?string
    {
        return $this->navigationIcon;
    }

    public function getNavigationSort(): ?int
    {
        return $this->navigationSort;
    }

    public function register(Panel $panel): void
    {
        $panel->pages([
            ManageSeoSettings::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }
}

<?php

namespace Madbox99\FilamentSeo;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Madbox99\FilamentSeo\Data\SeoData;
use Madbox99\FilamentSeo\Models\SeoMeta;
use Madbox99\FilamentSeo\Models\SeoRoute;
use Madbox99\FilamentSeo\Models\SeoSetting;

class SeoManager
{
    protected static ?Closure $siteNameResolver = null;

    protected ?Model $model = null;

    protected ?string $fallbackTitle = null;

    protected ?string $ogImage = null;

    public static function siteNameUsing(Closure $callback): void
    {
        static::$siteNameResolver = $callback;
    }

    public function for(Model $model, ?string $fallbackTitle = null): static
    {
        $this->model = $model;
        $this->fallbackTitle = $fallbackTitle;

        return $this;
    }

    public function ogImage(?string $url): static
    {
        $this->ogImage = $url;

        return $this;
    }

    public function reset(): static
    {
        $this->model = null;
        $this->fallbackTitle = null;
        $this->ogImage = null;

        return $this;
    }

    public function siteName(): string
    {
        if (static::$siteNameResolver instanceof Closure) {
            return (string) call_user_func(static::$siteNameResolver);
        }

        return (string) config('app.name');
    }

    public function resolve(): SeoData
    {
        $settings = SeoSetting::current();
        $modelMeta = $this->modelMeta();
        $routeMeta = $this->routeMeta();

        return new SeoData(
            title: $this->title($modelMeta, $routeMeta, $settings),
            description: $this->firstFilled([
                $modelMeta?->description,
                $routeMeta?->description,
                $settings->default_description,
            ]),
            keywords: $this->firstFilled([$modelMeta?->keywords, $routeMeta?->keywords]),
            canonical: $this->firstFilled([
                $modelMeta?->canonical_url,
                $routeMeta?->canonical_url,
                url()->current(),
            ]),
            robots: $this->robots($modelMeta ?? $routeMeta),
            ogType: $this->firstFilled([$modelMeta?->og_type, $routeMeta?->og_type]) ?? 'website',
            ogImage: $this->imageUrl($this->firstFilled([
                $modelMeta?->og_image,
                $routeMeta?->og_image,
                $this->ogImage,
                $settings->default_og_image,
            ])),
            schemas: [],
        );
    }

    protected function modelMeta(): ?SeoMeta
    {
        if (! $this->model instanceof Model) {
            return null;
        }

        if (! method_exists($this->model, 'seoMeta')) {
            return null;
        }

        return $this->model->seoMeta;
    }

    protected function routeMeta(): ?SeoMeta
    {
        $routeName = request()->route()?->getName();

        if (! is_string($routeName) || $routeName === '') {
            return null;
        }

        return SeoRoute::forRoute($routeName)?->seoMeta;
    }

    protected function title(?SeoMeta $modelMeta, ?SeoMeta $routeMeta, SeoSetting $settings): string
    {
        $siteName = $this->siteName();

        $title = $this->firstFilled([
            $modelMeta?->title,
            $routeMeta?->title,
            $this->fallbackTitle,
        ]);

        if ($title === null) {
            return $siteName;
        }

        $pattern = $settings->default_title_pattern ?: '{title} | {site_name}';

        return str_replace(['{title}', '{site_name}'], [$title, $siteName], $pattern);
    }

    protected function robots(?SeoMeta $meta): ?string
    {
        if (! $meta instanceof SeoMeta) {
            return null;
        }

        if (! $meta->no_index && ! $meta->no_follow) {
            return null;
        }

        return ($meta->no_index ? 'noindex' : 'index').', '.($meta->no_follow ? 'nofollow' : 'follow');
    }

    protected function imageUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '//')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }

    /**
     * @param  array<int, string|null>  $candidates
     */
    protected function firstFilled(array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if (filled($candidate)) {
                return (string) $candidate;
            }
        }

        return null;
    }
}

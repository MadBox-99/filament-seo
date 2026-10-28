<?php

declare(strict_types=1);

namespace Madbox99\FilamentSeo\Data;

final readonly class SeoData
{
    /**
     * @param  list<array<string, mixed>>  $schemas
     */
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public ?string $keywords = null,
        public ?string $canonical = null,
        public ?string $robots = null,
        public string $ogType = 'website',
        public ?string $ogImage = null,
        public array $schemas = [],
    ) {}
}

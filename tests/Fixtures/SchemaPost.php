<?php

namespace Madbox99\FilamentSeo\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Madbox99\FilamentSeo\Concerns\HasSeo;
use Madbox99\FilamentSeo\Contracts\ProvidesSchemaOrg;

class SchemaPost extends Model implements ProvidesSchemaOrg
{
    use HasSeo;

    protected $guarded = [];

    protected $table = 'posts';

    /** @return list<array<string, mixed>> */
    public function toSchemaOrg(): array
    {
        return [
            ['@context' => 'https://schema.org', '@type' => 'Article', 'headline' => $this->title],
            ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => []],
        ];
    }
}

<?php

namespace Madbox99\FilamentSeo\Contracts;

interface ProvidesSchemaOrg
{
    /**
     * A modellhez tartozó schema.org blokkok listája.
     *
     * Listát ad vissza, nem egyetlen blokkot: egy cikkoldalnak például
     * Article és BreadcrumbList blokkra is szüksége van.
     *
     * @return list<array<string, mixed>>
     */
    public function toSchemaOrg(): array;
}

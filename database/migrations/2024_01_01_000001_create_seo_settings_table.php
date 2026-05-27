<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('filament-seo.table_names.seo_settings', 'seo_settings'), function (Blueprint $table) {
            $table->id();
            $table->string('default_title_pattern')->default('{title} | {site_name}');
            $table->text('default_description')->nullable();
            $table->string('default_og_image')->nullable();
            $table->text('robots_txt')->nullable();
            $table->json('sitemap_excluded_urls')->nullable();
            $table->string('schema_org_type')->default('Organization');
            $table->json('schema_org_data')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('filament-seo.table_names.seo_settings', 'seo_settings'));
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $table = config('filament-seo.table_names.seo_metas', 'seo_metas');

        if (Schema::hasColumn($table, 'og_image')) {
            return;
        }

        Schema::table($table, function (Blueprint $table) {
            $table->string('og_image')->nullable();
        });
    }

    public function down(): void
    {
        $table = config('filament-seo.table_names.seo_metas', 'seo_metas');

        if (! Schema::hasColumn($table, 'og_image')) {
            return;
        }

        Schema::table($table, function (Blueprint $table) {
            $table->dropColumn('og_image');
        });
    }
};

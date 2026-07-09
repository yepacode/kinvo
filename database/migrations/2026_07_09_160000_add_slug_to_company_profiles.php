<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Slug público para el perfil del estudio (página pública /estudio/{slug}).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_profiles', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('company_name');
        });

        // Backfill de slugs únicos para estudios existentes.
        foreach (DB::table('company_profiles')->whereNull('slug')->get() as $p) {
            $base = Str::slug($p->company_name ?: 'estudio') ?: 'estudio';
            $slug = $base;
            $i = 1;
            while (DB::table('company_profiles')->where('slug', $slug)->exists()) {
                $slug = $base.'-'.(++$i);
            }
            DB::table('company_profiles')->where('id', $p->id)->update(['slug' => $slug]);
        }
    }

    public function down(): void
    {
        Schema::table('company_profiles', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};

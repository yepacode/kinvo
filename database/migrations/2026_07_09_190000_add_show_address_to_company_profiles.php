<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Opt-in para mostrar la dirección exacta del estudio en su perfil público.
 * Por defecto NO se muestra (solo el estado); el estudio decide exponerla.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_profiles', function (Blueprint $table) {
            $table->boolean('show_address')->default(false)->after('postal_code');
        });
    }

    public function down(): void
    {
        Schema::table('company_profiles', function (Blueprint $table) {
            $table->dropColumn('show_address');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Idioma preferido del usuario. Se guarda al cambiar el selector de idioma
 * (cookie + user) y lo usa `HasLocalePreference` para que los mailables en
 * cola respeten el idioma del receptor aunque se procesen más tarde.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('locale', 5)->default('es')->after('estado');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('locale');
        });
    }
};

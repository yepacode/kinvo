<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Campo Colonia (texto libre) para ubicar mejor a talento y estudios,
 * además de Ciudad/Estado. Solicitado por el cliente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('professional_profiles', function (Blueprint $table) {
            $table->string('colonia')->nullable()->after('location_id');
        });
        Schema::table('company_profiles', function (Blueprint $table) {
            $table->string('colonia')->nullable()->after('postal_code');
        });
    }

    public function down(): void
    {
        Schema::table('professional_profiles', function (Blueprint $table) {
            $table->dropColumn('colonia');
        });
        Schema::table('company_profiles', function (Blueprint $table) {
            $table->dropColumn('colonia');
        });
    }
};

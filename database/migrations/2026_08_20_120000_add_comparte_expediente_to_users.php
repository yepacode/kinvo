<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Punto 12 (ago-2026): el coach elige si su EXPEDIENTE DE CUIDADO se ve o no.
 * Cuando está en false, sus registros de bienestar NO se reflejan en el panel
 * de bienestar del estudio. Default true = comportamiento actual (visible).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('comparte_expediente')->default(true)->after('membership_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('comparte_expediente');
        });
    }
};

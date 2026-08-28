<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Feedback Karla 27-ago: expediente admin necesita
 *  1) un switch encendido/apagado (para dar de baja entradas sin borrar).
 *  2) poder atarlas al catálogo de Services (ej. "Fisioterapia" del plan Pro).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wellness_entries', function (Blueprint $t) {
            $t->boolean('is_active')->default(true)->after('valid_until');
            $t->foreignId('service_id')->nullable()->after('is_active')
                ->constrained('services')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('wellness_entries', function (Blueprint $t) {
            $t->dropForeign(['service_id']);
            $t->dropColumn(['is_active', 'service_id']);
        });
    }
};

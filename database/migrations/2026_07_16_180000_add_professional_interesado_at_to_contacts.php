<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marca de tiempo de "profesional interesado" en un contacto.
 * Cuando el profesional pulsa "Me interesa, conéctame con el estudio" en su
 * bandeja, se guarda la fecha aquí y Kinvoo (el owner) recibe una notificación
 * para hacer el puente con el estudio.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->timestamp('professional_interesado_at')->nullable()->after('estado');
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn('professional_interesado_at');
        });
    }
};

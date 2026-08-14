<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * H3 · petición cliente (chat, ago-2026):
 * "hay que tener en cuenta disponibilidad de horas también, porque a veces
 * el estudio puede necesitar horas exactas".
 *
 * Se añaden a offers:
 *  - schedule_ranges: rangos estructurados por día
 *      [{day: 'lun', from: '07:00', to: '09:00'}, ...]
 *  - schedule_notes:  texto libre ("prefiero puntualidad", "sábados alternos")
 *
 * Se conserva availability (AM/PM) porque el perfil profesional lo usa
 * y permite matching rápido — los rangos son la especificidad.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->json('schedule_ranges')->nullable()->after('availability');
            $table->text('schedule_notes')->nullable()->after('schedule_ranges');
        });
    }

    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->dropColumn(['schedule_ranges', 'schedule_notes']);
        });
    }
};

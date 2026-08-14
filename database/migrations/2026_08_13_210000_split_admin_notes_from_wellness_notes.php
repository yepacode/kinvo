<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * CRITICAL-4: `wellness_notes` era leída y editada por AMBOS:
 *  - el admin, en Filament CompanyProfileResource (nota interna sobre el estudio)
 *  - el estudio, en TeamController::guardarNotaBienestar (nota sobre su propio equipo)
 * Resultado: data leak — el estudio veía y podía sobrescribir la nota interna
 * del admin. Aquí separamos en dos columnas con dueño claro:
 *   - admin_notes    → solo admin lee/escribe (Filament)
 *   - wellness_notes → solo estudio lee/escribe (vista /equipo)
 * Backfill: copiamos wellness_notes → admin_notes para no perder contexto
 * ya capturado por el admin (si algún valor viniera del estudio queda en ambos,
 * el admin decide si lo borra al revisarlo).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_profiles', function (Blueprint $table) {
            $table->text('admin_notes')->nullable()->after('wellness_notes');
        });

        // Copia defensiva del valor histórico (perfil por perfil).
        DB::table('company_profiles')
            ->whereNotNull('wellness_notes')
            ->update(['admin_notes' => DB::raw('wellness_notes')]);
    }

    public function down(): void
    {
        Schema::table('company_profiles', function (Blueprint $table) {
            $table->dropColumn('admin_notes');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Relaciones N:M del perfil profesional con disciplinas y certificaciones.
 */
return new class extends Migration
{
    public function up(): void
    {
        // FKs con nombres cortos explícitos: los autogenerados exceden el límite de
        // 64/63 caracteres de MySQL/PostgreSQL (SQLite no tiene ese límite).
        Schema::create('discipline_professional_profile', function (Blueprint $table) {
            $table->foreignId('professional_profile_id');
            $table->foreignId('discipline_id');
            $table->primary(['professional_profile_id', 'discipline_id'], 'disc_prof_pk');
            $table->foreign('professional_profile_id', 'disc_pp_fk')->references('id')->on('professional_profiles')->cascadeOnDelete();
            $table->foreign('discipline_id', 'disc_d_fk')->references('id')->on('disciplines')->cascadeOnDelete();
        });

        Schema::create('certification_professional_profile', function (Blueprint $table) {
            $table->foreignId('professional_profile_id');
            $table->foreignId('certification_id');
            $table->primary(['professional_profile_id', 'certification_id'], 'cert_prof_pk');
            $table->foreign('professional_profile_id', 'cert_pp_fk')->references('id')->on('professional_profiles')->cascadeOnDelete();
            $table->foreign('certification_id', 'cert_c_fk')->references('id')->on('certifications')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certification_professional_profile');
        Schema::dropIfExists('discipline_professional_profile');
    }
};

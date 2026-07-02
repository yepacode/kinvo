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
        Schema::create('discipline_professional_profile', function (Blueprint $table) {
            $table->foreignId('professional_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('discipline_id')->constrained()->cascadeOnDelete();
            $table->primary(['professional_profile_id', 'discipline_id'], 'disc_prof_pk');
        });

        Schema::create('certification_professional_profile', function (Blueprint $table) {
            $table->foreignId('professional_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('certification_id')->constrained()->cascadeOnDelete();
            $table->primary(['professional_profile_id', 'certification_id'], 'cert_prof_pk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certification_professional_profile');
        Schema::dropIfExists('discipline_professional_profile');
    }
};

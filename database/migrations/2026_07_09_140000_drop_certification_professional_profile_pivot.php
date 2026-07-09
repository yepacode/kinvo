<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El pivote profesional↔certificación quedó huérfano: los profesionales ahora
 * capturan sus certificaciones en un campo de texto libre (certifications_text),
 * no como taxonomía. Se elimina la tabla muerta. (El pivote de disciplinas se
 * conserva; sigue en uso.)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('certification_professional_profile');
    }

    public function down(): void
    {
        Schema::create('certification_professional_profile', function (Blueprint $table) {
            $table->foreignId('professional_profile_id');
            $table->foreignId('certification_id');
            $table->primary(['professional_profile_id', 'certification_id'], 'cert_prof_pk');
            $table->foreign('professional_profile_id', 'cert_pp_fk')->references('id')->on('professional_profiles')->cascadeOnDelete();
            $table->foreign('certification_id', 'cert_c_fk')->references('id')->on('certifications')->cascadeOnDelete();
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Campos solicitados por el cliente (doc WEB KINVOO) para el perfil profesional:
 * disponibilidad AM/PM, idiomas, certificaciones en texto + adjunto privado,
 * multimedia y fecha de nacimiento (validación 18+).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('professional_profiles', function (Blueprint $table) {
            $table->date('birthdate')->nullable()->after('headline');
            $table->json('availability')->nullable()->after('modalidad');   // ["lun_am", "sab_pm", ...]
            $table->json('languages')->nullable()->after('availability');   // ["es", "en"]
            $table->text('certifications_text')->nullable()->after('languages');
            $table->string('certification_file_path')->nullable()->after('certifications_text'); // privado (solo admin)
            $table->string('media_url')->nullable()->after('certification_file_path');           // opcional
        });
    }

    public function down(): void
    {
        Schema::table('professional_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'birthdate', 'availability', 'languages',
                'certifications_text', 'certification_file_path', 'media_url',
            ]);
        });
    }
};

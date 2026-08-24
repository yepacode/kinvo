<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Contenido del admin (Punto 4 · ago-2026): permitir subir video/imagen/audio/
 * documento y publicar blogs (artículos), dirigidos a una membresía.
 *
 *  - `body`      → cuerpo del artículo/blog (texto enriquecido). Los demás tipos
 *                  no lo usan.
 *  - `file_disk` → en qué disco vive `file_path`. Los archivos del admin dirigidos
 *                  a una membresía se guardan en el disco PRIVADO ('local') y se
 *                  sirven por una ruta que valida el plan; el link directo no sirve.
 *                  Los contenidos previos (subidos por estudios) quedan en 'public'.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_items', function (Blueprint $table) {
            $table->longText('body')->nullable()->after('description');
            $table->string('file_disk', 20)->default('public')->after('file_path');
        });
    }

    public function down(): void
    {
        Schema::table('content_items', function (Blueprint $table) {
            $table->dropColumn(['body', 'file_disk']);
        });
    }
};

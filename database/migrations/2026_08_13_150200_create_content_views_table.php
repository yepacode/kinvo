<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * H5 · Reportes por coach (petición cliente, docx PRUEBA KINVOO):
 * "Historial de asistencia a Zooms/contenido de desarrollo, con fecha —
 * no solo 'cuántos vio', sino 'cuáles y cuándo' para saber si alguien
 * avanzó del Nivel 1 al Nivel 2 y que se vea en su expediente".
 *
 * Un registro por vista del coach al contenido. Índice compuesto para
 * consultas "última vista", "conteo por coach", "por content_item".
 * Sin dedupe: cada apertura suma (útil para engagement real).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('content_item_id')->constrained('content_items')->cascadeOnDelete();
            $table->timestamp('viewed_at');
            $table->timestamps();

            $table->index(['user_id', 'viewed_at']);
            $table->index(['content_item_id', 'viewed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_views');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 2 · Bolsa de trabajo: ofertas publicadas por estudios.
 *
 * Estados:
 *   - 'draft'     → borrador, no visible al público
 *   - 'published' → visible en /ofertas
 *   - 'closed'    → cerrada, ya no acepta postulaciones
 *   - 'expired'   → pasó la fecha límite automáticamente
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contractor_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('discipline_id')->nullable()->constrained('disciplines')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();

            $table->string('slug')->unique();
            $table->string('title', 200);
            $table->text('description');
            $table->text('requirements')->nullable();

            $table->unsignedBigInteger('salary_min_cents')->nullable();
            $table->unsignedBigInteger('salary_max_cents')->nullable();
            $table->string('salary_currency', 3)->default('MXN');
            $table->string('salary_period', 20)->default('month'); // hour|month|project

            $table->string('modality', 20)->default('presencial'); // presencial|online|hibrido
            $table->string('contract_type', 20)->nullable(); // full_time|part_time|freelance

            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->date('expires_on')->nullable();
            $table->unsignedInteger('applications_count')->default(0);

            $table->timestamps();

            $table->index(['status', 'published_at']);
            $table->index(['contractor_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};

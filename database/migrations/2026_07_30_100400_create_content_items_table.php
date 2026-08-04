<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 2 · Página de contenido: pláticas grabadas, capacitaciones, recursos.
 *
 * `type`: video|document|audio|link
 * `gate_role`: null (todos los autenticados) | professional | contractor
 * `gate_plan_id`: si no es null, requiere ese plan de membresía
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_items', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('category', 60)->nullable();

            $table->string('type', 20)->default('video');
            $table->string('url')->nullable(); // video embed / link externo
            $table->string('file_path')->nullable(); // documentos subidos

            $table->string('gate_role', 20)->nullable();
            $table->foreignId('gate_plan_id')->nullable()->constrained('plans')->nullOnDelete();

            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('views_count')->default(0);

            $table->timestamps();

            $table->index(['is_published', 'published_at']);
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_items');
    }
};

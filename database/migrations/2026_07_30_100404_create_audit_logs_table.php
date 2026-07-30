<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 2 · Bitácora polimórfica de altas, bajas y cambios de estatus.
 *
 * Cada acción crítica queda registrada: quién (actor), sobre qué (subject
 * polimórfico), qué acción (action), estado antes/después (old/new).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->morphs('subject'); // subject_type, subject_id

            $table->string('action', 40); // created|updated|approved|rejected|suspended|...
            $table->json('old')->nullable();
            $table->json('new')->nullable();
            $table->string('ip', 45)->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['subject_type', 'subject_id', 'created_at']);
            $table->index(['actor_user_id', 'created_at']);
            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};

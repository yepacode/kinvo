<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * H4 · Wall "Comparte un momento" (petición cliente, docx PRUEBA KINVOO).
 *
 * El estudio publica foto o video corto + una frase; el admin modera
 * y aprueba antes de que aparezca en /comunidad para todos los coaches.
 *
 * Estados:
 *  - 'pending'  → recién subido, esperando revisión del admin
 *  - 'approved' → visible en el wall público
 *  - 'rejected' → admin lo bloqueó (queda registro con moderator_id)
 *  - 'archived' → el estudio lo quitó del wall
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wall_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('caption', 280);
            $table->string('media_path'); // storage/app/public/...
            $table->string('media_type', 10); // image|video
            $table->string('status', 20)->default('pending');
            $table->foreignId('moderator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable();
            $table->string('moderation_reason', 500)->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wall_posts');
    }
};

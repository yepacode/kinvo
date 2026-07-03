<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bitácora de contactos: un contratante contacta a un profesional.
 * Guarda quién contactó a quién, cuándo y con qué datos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contractor_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('professional_profile_id')->constrained()->cascadeOnDelete();
            $table->string('contact_name');
            $table->string('contact_email');
            $table->string('contact_phone')->nullable();
            $table->text('message');
            $table->string('estado')->default('unread'); // enum EstadoContacto
            $table->timestamps();

            $table->index('estado');
            $table->index(['professional_profile_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};

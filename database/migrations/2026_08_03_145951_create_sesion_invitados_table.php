<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 2 · Invitados por sesión. Cada fila = un user invitado a una sesión.
 * Guarda RSVP (pending/accepted/declined) y un token firmado para el link
 * "Voy / No puedo" del correo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sesion_invitados', function (Blueprint $t) {
            $t->id();
            $t->foreignId('sesion_id')->constrained('sesiones')->cascadeOnDelete();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->dateTime('invited_at');
            $t->dateTime('notified_at')->nullable();       // cuando le llegó el correo
            $t->string('rsvp', 20)->default('pending');    // pending|accepted|declined
            $t->dateTime('rsvp_at')->nullable();
            $t->string('rsvp_token', 64)->unique();        // para el link firmado
            $t->timestamps();
            // Un user no puede duplicarse en la misma sesión.
            $t->unique(['sesion_id', 'user_id']);
            $t->index(['sesion_id', 'rsvp']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sesion_invitados');
    }
};

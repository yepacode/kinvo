<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 2 · Sesiones en vivo (webinars, talleres, consultas) que Kinvoo
 * agenda y a las que invita a coaches y estudios por correo desde el admin.
 * Distinto de `content_items` que son grabaciones on-demand.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sesiones', function (Blueprint $t) {
            $t->id();
            $t->string('title', 200);
            $t->text('description')->nullable();
            $t->string('tipo', 30)->default('webinar');          // webinar|taller|consulta|otro
            $t->dateTime('scheduled_at');
            $t->unsignedSmallInteger('duration_min')->default(60);
            $t->string('link', 500)->nullable();                 // Zoom/Meet
            $t->string('audience', 20)->default('all');          // all|professional|contractor
            // Overrides opcionales sobre la plantilla por default.
            $t->string('subject_override', 255)->nullable();
            $t->text('body_override')->nullable();
            $t->dateTime('notified_at')->nullable();             // último envío masivo
            $t->foreignId('created_by_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->index(['scheduled_at']);
            $t->index(['audience', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sesiones');
    }
};

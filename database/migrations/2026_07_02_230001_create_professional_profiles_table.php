<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Perfil autoeditable del profesional (1:1 con users donde nivel = Professional).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('professional_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->string('photo_path')->nullable();
            $table->string('headline')->nullable();          // titular corto ("Coach de fuerza")
            $table->text('bio')->nullable();
            $table->unsignedTinyInteger('years_experience')->nullable();
            $table->string('modalidad')->nullable();          // enum ModalidadTrabajo
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('phone')->nullable();
            $table->json('socials')->nullable();              // {instagram, tiktok, web...}
            $table->boolean('is_published')->default(false);  // el owner/usuario publica al aprobarse
            $table->timestamps();

            $table->index('is_published');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('professional_profiles');
    }
};

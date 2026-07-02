<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Taxonomías administrables por el owner: disciplinas, certificaciones y ubicaciones.
 * Nombres bilingües (nombre = ES, nombre_en = EN).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disciplines', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('nombre_en')->nullable();
            $table->string('slug')->unique();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('certifications', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('nombre_en')->nullable();
            $table->string('slug')->unique();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('ciudad');
            $table->string('region')->nullable();
            $table->string('pais')->default('México');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['ciudad', 'region']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
        Schema::dropIfExists('certifications');
        Schema::dropIfExists('disciplines');
    }
};

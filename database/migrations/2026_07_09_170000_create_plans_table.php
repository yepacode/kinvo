<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Planes de membresía, administrables por el owner desde el panel.
 * (Sin gate ni cobro por ahora: solo el catálogo editable.)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('slug')->unique();
            $table->string('audiencia')->default('individual'); // individual (persona física) | estudio (persona moral)
            $table->decimal('precio', 10, 2)->nullable();
            $table->string('moneda', 3)->default('MXN');
            $table->string('periodo')->default('mensual');       // mensual | anual
            $table->text('descripcion')->nullable();
            $table->json('beneficios')->nullable();              // lista de beneficios/incluye
            $table->text('cobertura')->nullable();               // "COBERTURA: ..."
            $table->boolean('destacado')->default(false);
            $table->boolean('activo')->default(true);
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();

            $table->index(['audiencia', 'activo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Punto 5-A (ago-2026): catálogo de SERVICIOS que el admin administra, se
 * incluyen por plan/membresía, y el usuario solicita (con aprobación del admin).
 *
 *  - services        → catálogo editable desde el panel (Salud, Fisio, Nutrición…).
 *  - plan_service    → qué servicios incluye cada plan (lo elige el admin).
 *  - benefit_requests.service_id → generaliza la solicitud existente: además de
 *    los `type` legacy (telemedicine/physio), una solicitud puede apuntar a un
 *    servicio del catálogo. Nullable para no romper las filas históricas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 120);
            $table->string('slug')->unique();
            $table->text('descripcion')->nullable();
            $table->string('icono', 40)->nullable(); // emoji o nombre de heroicon
            $table->boolean('activo')->default(true);
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();
        });

        Schema::create('plan_service', function (Blueprint $table) {
            $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->primary(['plan_id', 'service_id']);
        });

        Schema::table('benefit_requests', function (Blueprint $table) {
            $table->foreignId('service_id')->nullable()->after('type')
                ->constrained('services')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('benefit_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('service_id');
        });
        Schema::dropIfExists('plan_service');
        Schema::dropIfExists('services');
    }
};

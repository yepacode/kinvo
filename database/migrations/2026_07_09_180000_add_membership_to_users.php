<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Membresía por usuario (gestión manual del owner, sin pasarela por ahora):
 * plan asignado + fecha de vencimiento. Activa = vence hoy o en el futuro.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('membership_plan_id')->nullable()->after('estado')
                ->constrained('plans')->nullOnDelete();
            $table->date('membership_expires_at')->nullable()->after('membership_plan_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('membership_plan_id');
            $table->dropColumn('membership_expires_at');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Rol: 0=Admin, 1=Professional, 2=Contractor (enum RolUsuario)
            $table->unsignedTinyInteger('nivel')->default(1)->after('password');
            // Estado de aprobación: pending|active|suspended (enum EstadoUsuario)
            $table->string('estado')->default('pending')->after('nivel');

            $table->index('nivel');
            $table->index('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['nivel']);
            $table->dropIndex(['estado']);
            $table->dropColumn(['nivel', 'estado']);
        });
    }
};

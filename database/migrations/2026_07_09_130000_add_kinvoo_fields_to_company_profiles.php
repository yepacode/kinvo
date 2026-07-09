<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Campos solicitados por el cliente (doc WEB KINVOO) para el perfil del estudio:
 * disciplina en texto, estado de México, dirección con CP, datos de contacto
 * y contenido multimedia.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_profiles', function (Blueprint $table) {
            $table->string('disciplines_text')->nullable()->after('sector');
            $table->string('estado')->nullable()->after('location_id');       // estado de México
            $table->string('address')->nullable()->after('estado');
            $table->string('postal_code', 10)->nullable()->after('address');  // CP
            $table->string('contact_name')->nullable()->after('postal_code');
            $table->string('contact_phone')->nullable()->after('contact_name');
            $table->string('contact_email')->nullable()->after('contact_phone');
            $table->string('media_url')->nullable()->after('contact_email');  // opcional
        });
    }

    public function down(): void
    {
        Schema::table('company_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'disciplines_text', 'estado', 'address', 'postal_code',
                'contact_name', 'contact_phone', 'contact_email', 'media_url',
            ]);
        });
    }
};

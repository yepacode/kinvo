<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nombre completo/legal del profesional (además del nombre visible de la cuenta),
 * solicitado por el cliente para identificación.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('professional_profiles', function (Blueprint $table) {
            $table->string('full_name')->nullable()->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('professional_profiles', function (Blueprint $table) {
            $table->dropColumn('full_name');
        });
    }
};

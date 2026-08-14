<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * M8 · Bitácora legal completa: agregar `user_agent` para el detalle forense.
 * Ya guardamos IP y timestamp; user-agent completa el trío estándar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('user_agent', 500)->nullable()->after('ip');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn('user_agent');
        });
    }
};

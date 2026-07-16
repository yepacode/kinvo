<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Multimedia como SUBIDA de archivo (video o imagen) en profesional y estudio.
 * Convive con media_url (link externo) que ya existía.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('professional_profiles', function (Blueprint $table) {
            $table->string('media_path')->nullable()->after('media_url');
            $table->string('media_type', 10)->nullable()->after('media_path');
        });
        Schema::table('company_profiles', function (Blueprint $table) {
            $table->string('media_path')->nullable()->after('media_url');
            $table->string('media_type', 10)->nullable()->after('media_path');
        });
    }

    public function down(): void
    {
        Schema::table('professional_profiles', function (Blueprint $table) {
            $table->dropColumn(['media_path', 'media_type']);
        });
        Schema::table('company_profiles', function (Blueprint $table) {
            $table->dropColumn(['media_path', 'media_type']);
        });
    }
};

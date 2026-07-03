<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('professional_profiles', function (Blueprint $table) {
            $table->boolean('is_verified')->default(false)->after('is_published');
            $table->timestamp('verified_at')->nullable()->after('is_verified');
            $table->index('is_verified');
        });
    }

    public function down(): void
    {
        Schema::table('professional_profiles', function (Blueprint $table) {
            $table->dropIndex(['is_verified']);
            $table->dropColumn(['is_verified', 'verified_at']);
        });
    }
};

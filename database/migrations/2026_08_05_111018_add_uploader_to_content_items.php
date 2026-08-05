<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 2 · Permite que estudios suban su propio contenido, no solo Marian.
 * Añade `uploader_user_id` para trazar quién lo subió y saber si es
 * contenido oficial de Kinvoo (uploader_user_id NULL → admin/Kinvoo) o
 * de un estudio.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_items', function (Blueprint $t) {
            $t->foreignId('uploader_user_id')
                ->nullable()
                ->after('gate_plan_id')
                ->constrained('users')
                ->nullOnDelete()
                ->comment('NULL = subido por Kinvoo (admin). Otro = user que lo subió.');
            $t->index('uploader_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('content_items', function (Blueprint $t) {
            $t->dropForeign(['uploader_user_id']);
            $t->dropIndex(['uploader_user_id']);
            $t->dropColumn('uploader_user_id');
        });
    }
};

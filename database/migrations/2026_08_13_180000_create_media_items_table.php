<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Feedback cliente (docx PRUEBA KINVOO): "El contenido multimedia en
 * miniatura o carrusel". El modelo actual guardaba UN solo media_path
 * por perfil / contenido. Esta tabla polimórfica permite N media por
 * owner (perfil de estudio, perfil de coach, content item) y ordenarlos.
 *
 * Los campos legacy `media_path`, `media_type`, `media_url` en los owners
 * SIGUEN existiendo (backward compat) y se rellenan desde este table
 * como fallback — el show mira primero media_items y solo cae al singular
 * si media_items está vacío para ese owner.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_items', function (Blueprint $table) {
            $table->id();
            $table->morphs('mediable'); // mediable_type + mediable_id + índice
            $table->string('path');
            $table->string('type', 10)->default('image'); // image | video
            $table->string('caption', 200)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['mediable_type', 'mediable_id', 'sort_order']);
        });

        // Backfill: mover el media_path/media_type singular de los owners
        // a media_items como sort_order=0. Idempotente por INSERT ... SELECT
        // con WHERE NOT EXISTS.
        $this->backfill('company_profiles', \App\Models\CompanyProfile::class);
        $this->backfill('professional_profiles', \App\Models\ProfessionalProfile::class);
        $this->backfill('content_items', \App\Models\ContentItem::class, hasFilePath: true);
    }

    public function down(): void
    {
        Schema::dropIfExists('media_items');
    }

    private function backfill(string $table, string $mediableClass, bool $hasFilePath = false): void
    {
        $now = now()->toDateTimeString();
        // ContentItem usa `file_path`/`type` en vez de `media_path`/`media_type`.
        $col = $hasFilePath ? 'file_path' : 'media_path';

        \Illuminate\Support\Facades\DB::table($table)
            ->select('id', $col, $hasFilePath ? 'type' : 'media_type')
            ->whereNotNull($col)
            ->orderBy('id')
            ->chunk(200, function ($rows) use ($table, $mediableClass, $col, $hasFilePath, $now) {
                foreach ($rows as $row) {
                    $tipo = $hasFilePath
                        ? (in_array($row->type, ['video','audio']) ? 'video' : 'image')
                        : ($row->media_type ?: 'image');

                    // Solo insertar si no existe ya un media_items para este owner.
                    $exists = \Illuminate\Support\Facades\DB::table('media_items')
                        ->where('mediable_type', $mediableClass)
                        ->where('mediable_id', $row->id)
                        ->exists();
                    if ($exists) continue;

                    \Illuminate\Support\Facades\DB::table('media_items')->insert([
                        'mediable_type' => $mediableClass,
                        'mediable_id'   => $row->id,
                        'path'          => $row->{$col},
                        'type'          => $tipo,
                        'sort_order'    => 0,
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ]);
                }
            });
    }
};

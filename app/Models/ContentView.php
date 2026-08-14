<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * H5 · Historial de vistas de contenido de desarrollo por coach.
 * Uno por apertura (sin dedupe) para reflejar engagement real.
 */
class ContentView extends Model
{
    protected $fillable = ['user_id', 'content_item_id', 'viewed_at'];

    protected $casts = ['viewed_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contentItem(): BelongsTo
    {
        return $this->belongsTo(ContentItem::class);
    }
}

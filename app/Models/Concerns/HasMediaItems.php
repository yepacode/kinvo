<?php

namespace App\Models\Concerns;

use App\Models\MediaItem;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Añade galería polimórfica de MediaItem al modelo host.
 * Uso: `class CompanyProfile { use HasMediaItems; }`.
 */
trait HasMediaItems
{
    public function mediaItems(): MorphMany
    {
        return $this->morphMany(MediaItem::class, 'mediable')
            ->orderBy('sort_order')
            ->orderBy('id');
    }
}

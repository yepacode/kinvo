<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

/**
 * Ítem multimedia polimórfico. Puede pertenecer a un CompanyProfile,
 * un ProfessionalProfile o un ContentItem (via `mediable()`).
 * Habilita el carrusel (petición cliente, docx PRUEBA KINVOO).
 */
class MediaItem extends Model
{
    public const TYPE_IMAGE = 'image';
    public const TYPE_VIDEO = 'video';

    protected $fillable = ['mediable_type', 'mediable_id', 'path', 'type', 'caption', 'sort_order'];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function mediable(): MorphTo
    {
        return $this->morphTo();
    }

    /** URL del archivo (respeta host del request via asset()). */
    public function getUrlAttribute(): string
    {
        return asset('storage/'.$this->path);
    }

    /** Borra el archivo físico al eliminar el registro. */
    protected static function booted(): void
    {
        static::deleting(function (MediaItem $m) {
            if ($m->path) {
                Storage::disk('public')->delete($m->path);
            }
        });
    }
}

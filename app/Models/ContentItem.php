<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ContentItem extends Model
{
    public const TYPE_VIDEO    = 'video';
    public const TYPE_DOCUMENT = 'document';
    public const TYPE_AUDIO    = 'audio';
    public const TYPE_LINK     = 'link';

    protected $fillable = [
        'slug', 'title', 'description', 'category', 'type', 'url', 'file_path',
        'gate_role', 'gate_plan_id', 'is_published', 'published_at',
        'uploader_user_id',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'views_count' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (ContentItem $c) {
            if (blank($c->slug)) {
                $base = Str::slug($c->title) ?: 'contenido';
                $slug = $base; $i = 2;
                while (self::where('slug', $slug)->where('id', '!=', $c->id)->exists()) {
                    $slug = $base.'-'.$i; $i++;
                }
                $c->slug = $slug;
            }
        });
    }

    public function gatePlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'gate_plan_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploader_user_id');
    }

    /** ¿Este ítem lo subió Kinvoo (admin)? */
    public function esOficial(): bool
    {
        return $this->uploader_user_id === null;
    }

    /**
     * ¿Este user puede ver el ítem? Admin siempre; el rol y el plan de
     * membresía deben coincidir con las restricciones del ítem.
     */
    public function esAccesiblePor(?User $user): bool
    {
        if (! $this->is_published || ! $user) {
            return false;
        }
        if ($user->esAdmin()) {
            return true;
        }
        if ($this->gate_role === 'professional' && ! $user->esProfesional()) {
            return false;
        }
        if ($this->gate_role === 'contractor' && ! $user->esContratante()) {
            return false;
        }
        if ($this->gate_plan_id !== null) {
            return $user->tieneMembresiaActiva() && $user->membership_plan_id === $this->gate_plan_id;
        }
        return true;
    }
}

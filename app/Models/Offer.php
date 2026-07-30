<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Offer extends Model
{
    public const STATUS_DRAFT     = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_CLOSED    = 'closed';
    public const STATUS_EXPIRED   = 'expired';

    protected $fillable = [
        'contractor_user_id', 'discipline_id', 'location_id',
        'slug', 'title', 'description', 'requirements',
        'salary_min_cents', 'salary_max_cents', 'salary_currency', 'salary_period',
        'modality', 'contract_type',
        'status', 'published_at', 'expires_on',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'expires_on' => 'date',
        'salary_min_cents' => 'integer',
        'salary_max_cents' => 'integer',
        'applications_count' => 'integer',
    ];

    protected static function booted(): void
    {
        // Slug automático a partir del título si viene vacío.
        static::saving(function (Offer $o) {
            if (blank($o->slug)) {
                $o->slug = self::generarSlugUnico($o->title);
            }
        });
    }

    private static function generarSlugUnico(string $titulo): string
    {
        $base = Str::slug($titulo) ?: 'oferta';
        $slug = $base;
        $i = 2;
        while (self::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }
        return $slug;
    }

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'contractor_user_id');
    }

    public function discipline(): BelongsTo
    {
        return $this->belongsTo(Discipline::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function estaPublicada(): bool
    {
        return $this->status === self::STATUS_PUBLISHED
            && ($this->expires_on === null || $this->expires_on->isFuture());
    }
}

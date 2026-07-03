<?php

namespace App\Models;

use App\Enums\ModalidadTrabajo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class ProfessionalProfile extends Model
{
    protected $fillable = [
        'user_id', 'slug', 'photo_path', 'headline', 'bio',
        'years_experience', 'modalidad', 'location_id', 'phone',
        'socials', 'is_published',
    ];

    protected $casts = [
        'socials' => 'array',
        'is_published' => 'boolean',
        'modalidad' => ModalidadTrabajo::class,
    ];

    protected static function booted(): void
    {
        static::creating(function (ProfessionalProfile $p) {
            if (blank($p->slug)) {
                $base = Str::slug($p->headline ?: ($p->user?->name ?? 'perfil'));
                $slug = $base ?: 'perfil';
                $i = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $base.'-'.(++$i);
                }
                $p->slug = $slug;
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function disciplines(): BelongsToMany
    {
        return $this->belongsToMany(Discipline::class);
    }

    public function certifications(): BelongsToMany
    {
        return $this->belongsToMany(Certification::class);
    }

    public function contacts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function views(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProfileView::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}

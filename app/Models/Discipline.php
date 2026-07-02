<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Discipline extends Model
{
    protected $fillable = ['nombre', 'nombre_en', 'slug', 'activo'];

    protected $casts = ['activo' => 'boolean'];

    protected static function booted(): void
    {
        static::saving(function (Discipline $d) {
            if (blank($d->slug)) {
                $d->slug = Str::slug($d->nombre);
            }
        });
    }

    public function professionals(): BelongsToMany
    {
        return $this->belongsToMany(ProfessionalProfile::class);
    }

    /** Nombre según el locale activo. */
    public function nombreLocalizado(): string
    {
        return app()->getLocale() === 'en' && filled($this->nombre_en)
            ? $this->nombre_en
            : $this->nombre;
    }
}

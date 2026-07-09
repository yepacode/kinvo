<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Certification extends Model
{
    protected $fillable = ['nombre', 'nombre_en', 'slug', 'activo'];

    protected $casts = ['activo' => 'boolean'];

    // Catálogo de referencia. Desde el rediseño, los profesionales capturan sus
    // certificaciones como texto libre (no se enlazan a esta taxonomía).
    protected static function booted(): void
    {
        static::saving(function (Certification $c) {
            if (blank($c->slug)) {
                $c->slug = Str::slug($c->nombre);
            }
        });
    }

    public function nombreLocalizado(): string
    {
        return app()->getLocale() === 'en' && filled($this->nombre_en)
            ? $this->nombre_en
            : $this->nombre;
    }
}

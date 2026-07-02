<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    protected $fillable = ['ciudad', 'region', 'pais', 'activo'];

    protected $casts = ['activo' => 'boolean'];

    public function professionalProfiles(): HasMany
    {
        return $this->hasMany(ProfessionalProfile::class);
    }

    public function companyProfiles(): HasMany
    {
        return $this->hasMany(CompanyProfile::class);
    }

    /** Etiqueta legible: "Ciudad, Región". */
    public function etiqueta(): string
    {
        return collect([$this->ciudad, $this->region])->filter()->implode(', ');
    }
}

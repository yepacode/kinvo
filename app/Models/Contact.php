<?php

namespace App\Models;

use App\Enums\EstadoContacto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contact extends Model
{
    protected $fillable = [
        'contractor_user_id', 'professional_profile_id',
        'contact_name', 'contact_email', 'contact_phone',
        'message', 'estado', 'professional_interesado_at',
    ];

    protected $casts = [
        'estado' => EstadoContacto::class,
        'professional_interesado_at' => 'datetime',
    ];

    /** ¿El profesional ya marcó que quiere que Kinvoo lo conecte? */
    public function esInteresado(): bool
    {
        return $this->professional_interesado_at !== null;
    }

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'contractor_user_id');
    }

    public function professionalProfile(): BelongsTo
    {
        return $this->belongsTo(ProfessionalProfile::class);
    }
}

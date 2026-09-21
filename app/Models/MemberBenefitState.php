<?php

namespace App\Models;

use App\Enums\BenefitKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Feedback Karla 21-sep · "Requerimiento del apagador".
 *
 * Estado on/off por miembro × subservicio de la membresía Esencial. El admin
 * lo enciende cuando el trámite con el proveedor externo (1DOC3, Thona) o
 * interno (Bolsa, Desarrollo) está listo. Sin agenda, sin lógica automática.
 */
class MemberBenefitState extends Model
{
    protected $fillable = [
        'user_id', 'benefit_key', 'activo',
        'activated_at', 'deactivated_at', 'admin_notes',
        'changed_by_admin_id',
    ];

    protected $casts = [
        'benefit_key'   => BenefitKey::class,
        'activo'        => 'boolean',
        'activated_at'  => 'datetime',
        'deactivated_at'=> 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_admin_id');
    }
}

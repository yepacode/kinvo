<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * H6 · Solicitud de Respaldo del coach (telemedicina o fisioterapia).
 * El coach envía; el admin agenda desde Filament.
 */
class BenefitRequest extends Model
{
    public const TYPE_TELEMEDICINE = 'telemedicine';
    public const TYPE_PHYSIO       = 'physio';

    public const STATUS_PENDING   = 'pending';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_DONE      = 'done';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id', 'type', 'note', 'preferred_slot', 'status',
        'scheduled_for', 'admin_note', 'handled_by',
    ];

    protected $casts = [
        'scheduled_for' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}

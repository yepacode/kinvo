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
    // Solicitud de un servicio del catálogo (Punto 5-A): el servicio real va en
    // service_id; `type` queda como 'service' para satisfacer la columna NOT NULL.
    public const TYPE_SERVICE      = 'service';

    public const STATUS_PENDING   = 'pending';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_DONE      = 'done';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id', 'type', 'service_id', 'note', 'preferred_slot', 'status',
        'scheduled_for', 'admin_note', 'handled_by',
    ];

    protected $casts = [
        'scheduled_for' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /** Etiqueta legible del servicio solicitado (catálogo o legacy). */
    public function etiquetaServicio(): string
    {
        if ($this->service) {
            return $this->service->nombre;
        }

        return match ($this->type) {
            self::TYPE_TELEMEDICINE => 'Salud / Telemedicina',
            self::TYPE_PHYSIO => 'Fisioterapia',
            default => ucfirst($this->type),
        };
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WellnessEntry extends Model
{
    public const TYPE_TELEMEDICINE = 'telemedicine';
    public const TYPE_PHYSIO       = 'physio';
    public const TYPE_TALK         = 'talk';
    public const TYPE_INSURANCE    = 'insurance';
    public const TYPE_OTHER        = 'other';

    public const TYPES = [
        self::TYPE_TELEMEDICINE => 'Telemedicina',
        self::TYPE_PHYSIO       => 'Fisioterapia',
        self::TYPE_TALK         => 'Charla',
        self::TYPE_INSURANCE    => 'Seguro',
        self::TYPE_OTHER        => 'Otro',
    ];

    protected $fillable = [
        'professional_user_id', 'created_by_admin_id', 'type', 'occurred_on',
        'provider', 'notes', 'valid_until', 'is_active', 'service_id',
    ];

    protected $casts = [
        'occurred_on' => 'date',
        'valid_until' => 'date',
        'is_active'   => 'boolean',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function professional(): BelongsTo
    {
        return $this->belongsTo(User::class, 'professional_user_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_admin_id');
    }

    public function label(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}

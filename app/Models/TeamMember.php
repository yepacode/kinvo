<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamMember extends Model
{
    public const STATUS_INVITED  = 'invited';
    public const STATUS_ACTIVE   = 'active';
    public const STATUS_DECLINED = 'declined';
    public const STATUS_REMOVED  = 'removed';

    protected $fillable = [
        'contractor_user_id', 'professional_user_id', 'status',
        'invited_at', 'joined_at', 'left_at',
    ];

    protected $casts = [
        'invited_at' => 'datetime',
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
    ];

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'contractor_user_id');
    }

    public function professional(): BelongsTo
    {
        return $this->belongsTo(User::class, 'professional_user_id');
    }

    public function esActivo(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}

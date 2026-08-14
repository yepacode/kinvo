<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    /** No hay updated_at: la bitácora es append-only, solo created_at. */
    public $timestamps = false;

    protected $fillable = [
        'actor_user_id', 'subject_type', 'subject_id', 'action', 'old', 'new', 'ip', 'user_agent', 'created_at',
    ];

    protected $casts = [
        'old' => 'array',
        'new' => 'array',
        'created_at' => 'datetime',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Helper para registrar una acción. Uso:
     *   AuditLog::record($actor, $subscription, 'canceled', old: [...], new: [...]);
     */
    public static function record(?User $actor, Model $subject, string $action, array $old = [], array $new = []): self
    {
        return self::create([
            'actor_user_id' => $actor?->id,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'action' => $action,
            'old' => $old,
            'new' => $new,
            'ip' => request()?->ip(),
            'user_agent' => substr((string) request()?->userAgent(), 0, 500),
            'created_at' => now(),
        ]);
    }
}

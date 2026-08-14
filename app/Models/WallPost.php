<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * H4 · Post del wall "Comparte un momento".
 * El estudio sube foto/video + frase; admin modera.
 */
class WallPost extends Model
{
    public const STATUS_PENDING  = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_ARCHIVED = 'archived';

    public const TYPE_IMAGE = 'image';
    public const TYPE_VIDEO = 'video';

    protected $fillable = [
        'user_id', 'caption', 'media_path', 'media_type',
        'status', 'moderator_id', 'moderated_at', 'moderation_reason',
    ];

    protected $casts = [
        'moderated_at' => 'datetime',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderator_id');
    }

    public function estaAprobado(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Application extends Model
{
    public const STATUS_SUBMITTED  = 'submitted';
    public const STATUS_SEEN       = 'seen';
    public const STATUS_IN_CONTACT = 'in_contact';
    public const STATUS_ACCEPTED   = 'accepted';
    public const STATUS_REJECTED   = 'rejected';
    public const STATUS_WITHDRAWN  = 'withdrawn';

    protected $fillable = [
        'offer_id', 'professional_user_id', 'cover_letter', 'status', 'status_changed_at', 'notes',
    ];

    protected $casts = [
        'status_changed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // Contador denormalizado applications_count del Offer para no
        // hacer query costoso en listados.
        static::created(fn (Application $a) => $a->offer()->increment('applications_count'));
        static::deleted(fn (Application $a) => $a->offer()->decrement('applications_count'));
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function professional(): BelongsTo
    {
        return $this->belongsTo(User::class, 'professional_user_id');
    }
}

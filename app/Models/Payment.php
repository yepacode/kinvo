<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    public const STATUS_PENDING        = 'pending';
    public const STATUS_SUCCEEDED      = 'succeeded';
    public const STATUS_FAILED         = 'failed';
    public const STATUS_REFUNDED       = 'refunded';
    public const STATUS_PARTIAL_REFUND = 'partial_refund';

    protected $fillable = [
        'user_id', 'subscription_id', 'provider', 'provider_payment_id', 'provider_invoice_id',
        'amount_cents', 'currency', 'status', 'failure_code', 'failure_message',
        'paid_at', 'refunded_at',
    ];

    protected $casts = [
        'amount_cents' => 'integer',
        'paid_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /** Monto formateado en la moneda del pago (ej. "199.00 MXN"). */
    public function montoFormateado(): string
    {
        return number_format($this->amount_cents / 100, 2, '.', ',').' '.$this->currency;
    }
}

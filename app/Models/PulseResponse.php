<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * H6 · Encuesta de Pulso Kinvoo.
 * El coach contesta; el estudio ve resultados agregados de su equipo.
 */
class PulseResponse extends Model
{
    protected $fillable = [
        'user_id', 'contractor_user_id',
        'rating', 'answer_energy', 'answer_growth', 'answer_support',
        'period_start',
    ];

    protected $casts = [
        'rating' => 'integer',
        'period_start' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'contractor_user_id');
    }
}

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
            // BUG CRÍTICO (auditoría ago-2026): si algún valor tenía UTF-8
            // malformado (input con encoding raro tipo Windows-1252), el
            // JSON encode del cast 'array' fallaba con Illuminate\Database\
            // Eloquent\JsonEncodingException y la request completa cascaba a 500.
            // Sanitizamos recursivamente antes de guardar.
            'old' => self::sanitizarUtf8($old),
            'new' => self::sanitizarUtf8($new),
            'ip' => request()?->ip(),
            'user_agent' => substr((string) request()?->userAgent(), 0, 500),
            'created_at' => now(),
        ]);
    }

    /** Convierte strings mal encodeados a UTF-8 válido, intentando preservar acentos. */
    private static function sanitizarUtf8(mixed $val): mixed
    {
        if (is_string($val)) {
            if (! mb_check_encoding($val, 'UTF-8')) {
                // Detectar el encoding original y convertir; si no se detecta,
                // reemplazar bytes inválidos (perdemos algún acento pero no rompemos).
                $src = mb_detect_encoding($val, ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'CP1252'], true);
                return mb_convert_encoding($val, 'UTF-8', $src ?: 'ISO-8859-1');
            }
            return $val;
        }
        if (is_array($val)) {
            return array_map(fn ($v) => self::sanitizarUtf8($v), $val);
        }
        return $val;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

/**
 * Credenciales de la pasarela de pago, editables desde
 * /admin/configuracion-pagos (feedback Karla 06-10-2026).
 *
 * Los tres secretos usan el cast `encrypted`: Laravel los cifra con APP_KEY
 * al guardar y los descifra al leer. Nunca quedan en claro en la BD.
 *
 * Si APP_KEY cambia, los valores guardados dejan de poder descifrarse — por
 * eso `secreto()` atrapa la excepción y devuelve null en vez de reventar la
 * app: el sistema cae al .env y el admin puede volver a capturarlas.
 */
class PaymentCredential extends Model
{
    public const CACHE_KEY = 'payment_credential.activa';

    protected $fillable = [
        'gateway', 'activo', 'access_token', 'webhook_secret',
        'public_key', 'modo', 'verificado_at', 'verificado_resultado',
        'updated_by_admin_id',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'access_token' => 'encrypted',
            'webhook_secret' => 'encrypted',
            'public_key' => 'encrypted',
            'verificado_at' => 'datetime',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_admin_id');
    }

    /**
     * Lee un secreto tolerando un ciphertext inválido (p. ej. si rotaron
     * APP_KEY). Devolver null hace que el sistema caiga al .env en lugar
     * de tirar la pasarela entera.
     */
    public function secreto(string $campo): ?string
    {
        try {
            $valor = $this->getAttribute($campo);
        } catch (\Throwable $e) {
            report($e);
            return null;
        }

        return filled($valor) ? $valor : null;
    }

    /** La pasarela activa, o null si no hay ninguna configurada en BD. */
    public static function activa(): ?self
    {
        try {
            return Cache::remember(self::CACHE_KEY, 300, function () {
                return static::query()->where('activo', true)->first();
            });
        } catch (\Throwable $e) {
            // Tabla aún no migrada (deploy en curso) o BD caída: el sistema
            // sigue funcionando con las variables del .env.
            report($e);
            return null;
        }
    }

    public static function olvidarCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    protected static function booted(): void
    {
        static::saved(fn () => static::olvidarCache());
        static::deleted(fn () => static::olvidarCache());
    }

    /**
     * Sólo una pasarela activa a la vez: al activar una, apaga las demás.
     * Se llama desde la página de admin dentro de una transacción.
     */
    public function activarExclusivo(): void
    {
        static::query()->where('id', '!=', $this->id)->update(['activo' => false]);
        $this->activo = true;
        $this->save();
    }
}

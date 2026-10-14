<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Deja constancia de cada llamada que entra por /webhooks/billing.
 *
 * Sin esto no había forma de responder a la pregunta más básica de toda la
 * integración: "¿MercadoPago nos ha llamado alguna vez?". Si la dirección
 * estaba mal configurada en su panel —que es justo lo que pasó— el silencio
 * era idéntico al de una integración sana que todavía no ha vendido nada.
 *
 * Guarda dos cosas por separado, porque distinguen dos problemas muy
 * distintos:
 *   - la última llamada ACEPTADA  → la firma es correcta, todo bien;
 *   - la última llamada RECHAZADA → MercadoPago sí llama, pero el Secret de
 *     firma guardado en Kinvoo no es el suyo.
 *
 * Usa caché a propósito: ningún despliegue necesita migraciones por esto.
 */
class PulsoWebhook
{
    private const OK = 'billing:webhook:ultimo-ok';

    private const RECHAZADO = 'billing:webhook:ultimo-rechazado';

    private const DIAS = 60;

    public static function aceptado(string $tipo): void
    {
        self::guardar(self::OK, ['tipo' => $tipo ?: 'desconocido']);
    }

    public static function rechazado(string $motivo): void
    {
        self::guardar(self::RECHAZADO, ['motivo' => $motivo]);
    }

    /** @return array{cuando:string,tipo?:string}|null */
    public static function ultimoAceptado(): ?array
    {
        return self::leer(self::OK);
    }

    /** @return array{cuando:string,motivo?:string}|null */
    public static function ultimoRechazado(): ?array
    {
        return self::leer(self::RECHAZADO);
    }

    /** ¿Ha llamado la pasarela alguna vez, aunque fuera para ser rechazada? */
    public static function huboAlgunaLlamada(): bool
    {
        return self::ultimoAceptado() !== null || self::ultimoRechazado() !== null;
    }

    private static function guardar(string $clave, array $dato): void
    {
        try {
            Cache::put($clave, $dato + ['cuando' => now()->toDateTimeString()], now()->addDays(self::DIAS));
        } catch (\Throwable $e) {
            // El pulso es informativo: si la caché falla, el webhook sigue.
            report($e);
        }
    }

    private static function leer(string $clave): ?array
    {
        try {
            return Cache::get($clave);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }
}

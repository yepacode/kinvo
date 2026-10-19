<?php

namespace App\Support;

use App\Models\SiteSetting;

/**
 * Deja constancia de cada llamada que entra por /webhooks/billing.
 *
 * Sin esto no había forma de responder a la pregunta más básica de toda la
 * integración: "¿MercadoPago nos ha llamado alguna vez?". Si la dirección
 * estaba mal configurada en su panel, el silencio era idéntico al de una
 * integración sana que todavía no ha vendido nada.
 *
 * Guarda dos cosas por separado, porque distinguen dos problemas distintos:
 *   - la última llamada ACEPTADA  → la firma es correcta, todo bien;
 *   - la última llamada RECHAZADA → MercadoPago sí llama, pero el Secret de
 *     firma guardado en Kinvoo no es el suyo.
 *
 * Vive en la tabla de ajustes, NO en caché. La primera versión usaba caché
 * para no añadir migraciones al despliegue, y salió mal: `optimize:clear`
 * —que es justo lo que se corre en cada despliegue— la vacía. El 16-10-2026
 * MercadoPago llamó de verdad, el aviso se registró, y el despliegue del día
 * siguiente borró la prueba; el panel pasó a decir "no ha llamado nunca" y
 * mandó a buscar el problema donde no estaba.
 */
class PulsoWebhook
{
    private const OK = 'webhook_ultimo_aceptado';

    private const RECHAZADO = 'webhook_ultimo_rechazado';

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
            SiteSetting::set($clave, json_encode(
                $dato + ['cuando' => now()->toDateTimeString()],
                JSON_UNESCAPED_UNICODE
            ));
        } catch (\Throwable $e) {
            // El pulso es informativo: si falla, el webhook sigue su curso.
            report($e);
        }
    }

    private static function leer(string $clave): ?array
    {
        try {
            $crudo = SiteSetting::get($clave);
            if (! filled($crudo)) {
                return null;
            }

            $dato = json_decode((string) $crudo, true);

            return is_array($dato) ? $dato : null;
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }
}

<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Guarda el último rechazo de la pasarela para poder verlo desde el panel.
 *
 * Hasta ahora, cuando MercadoPago rechazaba una suscripción, la persona veía
 * una pantalla blanca de error 500 y el motivo quedaba sólo en
 * storage/logs/laravel.log — un archivo al que quien administra Kinvoo no
 * tiene acceso. Resultado: "no deja pagar" sin ninguna pista de por qué.
 *
 * Deliberadamente usa la caché y no una tabla nueva: así el despliegue sigue
 * siendo `git pull` + `optimize:clear`, sin migraciones. Si la caché se
 * limpia se pierde el dato, pero el log de Laravel conserva el registro.
 */
class FalloDePasarela
{
    private const CLAVE = 'billing:ultimo-fallo';

    private const DIAS = 14;

    /**
     * Traduce una excepción de la pasarela al motivo legible.
     *
     * MercadoPago no pone la causa real en el mensaje de la excepción: la
     * manda en el cuerpo de la respuesta (`message` + `cause[]`). Sin esto,
     * el log sólo decía "api error" y no había forma de saber qué rechazó.
     *
     * Vive aquí, y no en el checkout, para que la prueba del panel y el cobro
     * real cuenten lo mismo palabra por palabra.
     */
    public static function motivo(\Throwable $e): string
    {
        if ($e instanceof \MercadoPago\Exceptions\MPApiException) {
            $cuerpo = $e->getApiResponse()->getContent() ?? [];

            $partes = array_filter([
                $cuerpo['message'] ?? $cuerpo['error'] ?? null,
                ...array_map(
                    fn ($c) => is_array($c) ? ($c['description'] ?? $c['message'] ?? null) : (string) $c,
                    $cuerpo['cause'] ?? []
                ),
            ]);

            $texto = $partes ? implode(' · ', array_unique($partes)) : $e->getMessage();

            return 'MercadoPago ('.$e->getStatusCode().'): '.$texto;
        }

        return $e->getMessage() ?: $e::class;
    }

    public static function registrar(string $motivo, array $contexto = []): void
    {
        $dato = [
            'motivo'   => $motivo,
            'contexto' => $contexto,
            'cuando'   => now()->toDateTimeString(),
        ];

        try {
            Cache::put(self::CLAVE, $dato, now()->addDays(self::DIAS));
        } catch (\Throwable $e) {
            // Si la caché falla no pasa nada: el log de abajo es el respaldo.
            report($e);
        }

        Log::warning('Pasarela de pagos rechazó la operación', $dato);
    }

    /** @return array{motivo:string,contexto:array,cuando:string}|null */
    public static function ultimo(): ?array
    {
        try {
            return Cache::get(self::CLAVE);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    public static function limpiar(): void
    {
        try {
            Cache::forget(self::CLAVE);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}

<?php

namespace App\Support;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Guarda el último rechazo de la pasarela para poder verlo desde el panel.
 *
 * Hasta ahora, cuando MercadoPago rechazaba una suscripción, la persona veía
 * una pantalla blanca de error 500 y el motivo quedaba sólo en
 * storage/logs/laravel.log — un archivo al que quien administra Kinvoo no
 * tiene acceso. Resultado: "no deja pagar" sin ninguna pista de por qué.
 *
 * Vive en la tabla de ajustes, NO en caché: `optimize:clear` —que se corre en
 * cada despliegue— vaciaba la caché y con ella la prueba de lo ocurrido,
 * justo cuando más falta hacía.
 */
class FalloDePasarela
{
    private const CLAVE = 'billing_ultimo_fallo';

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

    /**
     * Traduce el rechazo a algo que la persona que intenta pagar pueda
     * entender y resolver por su cuenta. Devuelve null cuando el problema es
     * nuestro (configuración, plan mal capturado) y no hay nada que ella
     * pueda hacer: en ese caso se le da el mensaje genérico y el detalle va
     * al panel.
     *
     * MercadoPago contesta en inglés y en su propia jerga. «Payer is
     * associated with a different site» es, en cristiano, que la cuenta de
     * MercadoPago de ese correo está registrada en otro país — pasa de
     * verdad, y sin traducirlo la persona no tiene forma de saber qué hacer.
     */
    public static function mensajeParaLaPersona(\Throwable $e): ?string
    {
        $texto = Str::lower(self::motivo($e));

        return match (true) {
            str_contains($texto, 'different site'),
            str_contains($texto, 'invalid users involved') => __('La cuenta de MercadoPago de tu correo está registrada en otro país, y por eso no puede suscribirse aquí. Prueba con un correo distinto, o paga con tarjeta eligiendo "Sin cuenta de Mercado Pago" en la pantalla de pago.'),

            str_contains($texto, 'cannot pay an amount lower') => __('El importe de este plan está por debajo del mínimo que acepta MercadoPago. Ya avisamos al equipo de Kinvoo; elige otro plan mientras tanto.'),

            str_contains($texto, 'payer_email') => __('No pudimos usar tu correo para el cobro. Revisa que el correo de tu cuenta esté bien escrito en "Mi cuenta".'),

            str_contains($texto, 'collector'),
            str_contains($texto, 'cannot be the same') => __('No puedes suscribirte con la misma cuenta de MercadoPago que recibe los cobros. Usa otro correo.'),

            // Cualquier otra cosa es asunto nuestro, no suyo.
            default => null,
        };
    }

    public static function registrar(string $motivo, array $contexto = []): void
    {
        $dato = [
            'motivo'   => $motivo,
            'contexto' => $contexto,
            'cuando'   => now()->toDateTimeString(),
        ];

        try {
            SiteSetting::set(self::CLAVE, json_encode($dato, JSON_UNESCAPED_UNICODE));
        } catch (\Throwable $e) {
            // Si falla no pasa nada: el log de abajo es el respaldo.
            report($e);
        }

        Log::warning('Pasarela de pagos rechazó la operación', $dato);
    }

    /** @return array{motivo:string,contexto:array,cuando:string}|null */
    public static function ultimo(): ?array
    {
        try {
            $crudo = SiteSetting::get(self::CLAVE);
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

    public static function limpiar(): void
    {
        try {
            SiteSetting::set(self::CLAVE, null);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}

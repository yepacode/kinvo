<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Rastro paso a paso de un intento de cobro, en storage/logs/pagos.log.
 *
 * Cada intento recibe una referencia corta (p. ej. "pg_7f3a2b") que se repite
 * en todas sus líneas, así que se puede seguir un cobro concreto aunque haya
 * varios a la vez. Esa misma referencia se le enseña a la persona cuando algo
 * falla, de modo que basta con que la diga para encontrar su caso exacto.
 *
 * Existe porque el motivo de que alguien no pudiera pagar quedaba mezclado
 * entre miles de líneas de laravel.log, un archivo de cientos de megas al que
 * además quien administra Kinvoo no tiene acceso.
 */
class RastroDePago
{
    private string $ref;

    private float $inicio;

    public function __construct(?string $ref = null)
    {
        $this->ref = $ref ?: 'pg_'.Str::lower(Str::random(6));
        $this->inicio = microtime(true);
    }

    public function referencia(): string
    {
        return $this->ref;
    }

    public function paso(string $que, array $datos = []): void
    {
        $this->escribir('info', $que, $datos);
    }

    public function aviso(string $que, array $datos = []): void
    {
        $this->escribir('warning', $que, $datos);
    }

    public function fallo(string $que, array $datos = []): void
    {
        $this->escribir('error', $que, $datos);
    }

    private function escribir(string $nivel, string $que, array $datos): void
    {
        try {
            Log::channel('pagos')->{$nivel}('['.$this->ref.'] '.$que, $datos + [
                'ms' => (int) round((microtime(true) - $this->inicio) * 1000),
            ]);
        } catch (\Throwable $e) {
            // El rastro nunca puede tumbar un cobro.
            report($e);
        }
    }
}

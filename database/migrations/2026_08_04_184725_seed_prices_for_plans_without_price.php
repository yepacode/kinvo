<?php

use App\Models\Plan;
use Illuminate\Database\Migrations\Migration;

/**
 * Fase 2 · Sembrar precios placeholder para planes que quedaron con precio=null.
 *
 * IMPORTANTE: estos son PRECIOS DE ARRANQUE para que la clienta pueda probar
 * el flujo de suscripción. La clienta (Marian) debe ajustarlos en el panel
 * admin (Filament) según los precios reales de su negocio.
 *
 * Idempotente: solo actualiza planes que tengan precio NULL o 0 — respeta
 * cualquier precio ya cargado por Marian.
 */
return new class extends Migration
{
    /** Precios placeholder por audiencia (MXN mensual). */
    private const PRECIOS_DEFAULT = [
        'individual' => 199,
        'estudio'    => 599,
    ];

    public function up(): void
    {
        Plan::query()
            ->where(function ($q) {
                $q->whereNull('precio')->orWhere('precio', 0);
            })
            ->get()
            ->each(function (Plan $p) {
                $precio = self::PRECIOS_DEFAULT[$p->audiencia] ?? 199;
                // Los planes marcados como "destacados" cobran ~2x (ej. Pro).
                if ($p->destacado) {
                    $precio *= 2;
                }
                $p->update(['precio' => $precio]);
            });
    }

    public function down(): void
    {
        // No revertimos — si Marian ya editó los precios, un rollback los perdería.
    }
};

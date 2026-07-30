<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 2 · Añade columnas al modelo Plan para hablar con la pasarela de pagos.
 *  - provider_price_id: SKU del producto/precio en Stripe / MercadoPago.
 *  - is_recurring: si el plan es suscripción mensual/anual (true) o cobro único (false).
 *  - interval: 'month' | 'year' — periodicidad de la suscripción.
 *
 * Agnóstico de pasarela: el `provider_price_id` guarda el ID del proveedor
 * elegido en el kick-off. El adaptador de pasarela lo lee sin importar cuál sea.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->string('provider_price_id')->nullable()->after('audiencia');
            $table->boolean('is_recurring')->default(true)->after('provider_price_id');
            $table->string('interval', 10)->default('month')->after('is_recurring');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['provider_price_id', 'is_recurring', 'interval']);
        });
    }
};

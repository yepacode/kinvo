<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Feedback Karla 06-10-2026: las credenciales de la pasarela de pago vivían
 * SOLO en el .env del servidor, así que cada cambio de llaves requería SSH.
 * Esta tabla permite configurarlas desde /admin/configuracion-pagos.
 *
 * Los tres campos sensibles se guardan CIFRADOS (cast `encrypted` en el
 * modelo, que usa APP_KEY). Si alguien lee la BD directamente ve texto
 * cifrado, no las llaves.
 *
 * Precedencia de lectura (ver BillingConfigServiceProvider):
 *   1. Fila activa de esta tabla
 *   2. Variables del .env (fallback — mantiene compatibilidad)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_credentials', function (Blueprint $table) {
            $table->id();

            // 'mercadopago' | 'stripe' | 'fake'. Única fila por pasarela.
            $table->string('gateway', 30)->unique();

            // Sólo una pasarela puede estar activa a la vez. El provider
            // lee la que tenga activo=true.
            $table->boolean('activo')->default(false);

            // Cifrados en reposo. TEXT porque el ciphertext de Laravel es
            // bastante más largo que el valor original.
            $table->text('access_token')->nullable();
            $table->text('webhook_secret')->nullable();
            $table->text('public_key')->nullable();

            // Modo declarado por el admin, sólo informativo en la UI
            // ('produccion' | 'pruebas'). No cambia el comportamiento: eso
            // lo determina el propio token de MercadoPago (APP_USR vs TEST).
            $table->string('modo', 20)->default('produccion');

            // Resultado del último "Probar conexión" para que el admin vea
            // si las llaves responden sin tener que hacer un cobro real.
            $table->timestamp('verificado_at')->nullable();
            $table->string('verificado_resultado', 500)->nullable();

            $table->foreignId('updated_by_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_credentials');
    }
};

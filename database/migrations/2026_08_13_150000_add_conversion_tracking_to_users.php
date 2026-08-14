<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * H5 · Reportes admin extendidos (petición cliente, docx PRUEBA KINVOO):
 * "Fecha de registro vs. fecha de conversión a pago"
 * "Origen del registro (vino del evento / de un Zoom / de bolsa de trabajo / referido)"
 *
 * users.created_at ya nos da la fecha de registro. Se añaden:
 *  - converted_to_paid_at: timestamp del primer pago exitoso (se rellena
 *    desde el webhook y también en un one-off desde payments existentes).
 *  - registration_source: origen que el usuario declara al registrarse
 *    (o que el admin fija después). Vacío = "directo" desconocido.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('converted_to_paid_at')->nullable()->after('estado');
            $table->string('registration_source', 40)->nullable()->after('converted_to_paid_at');
        });

        // Backfill: rellenar converted_to_paid_at desde el primer pago exitoso
        // de cada usuario (si la tabla existe y tiene pagos). Idempotente.
        if (Schema::hasTable('payments')) {
            \Illuminate\Support\Facades\DB::statement(<<<'SQL'
                UPDATE users
                SET converted_to_paid_at = (
                    SELECT MIN(payments.created_at)
                    FROM payments
                    WHERE payments.user_id = users.id
                      AND payments.status = 'succeeded'
                )
                WHERE converted_to_paid_at IS NULL
            SQL);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['converted_to_paid_at', 'registration_source']);
        });
    }
};

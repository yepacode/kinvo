<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Pone al día el correo de quien ya estaba registrado.
 *
 * La verificación de correo nunca estuvo activa, así que todas las cuentas
 * tienen `email_verified_at` vacío. En el momento en que se encienda el
 * interruptor, esas personas —que llevan meses usando la plataforma— se
 * quedarían fuera esperando un enlace que nadie les mandó.
 *
 * Se les da por verificadas con la fecha de su registro. La verificación
 * aplicará sólo a quien se registre a partir de que se encienda.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->whereNull('email_verified_at')
            ->update(['email_verified_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        // No se revierte: devolverlo a nulo dejaría fuera a gente que hoy
        // entra sin problema, y no hay forma de saber a quién le tocaba.
    }
};

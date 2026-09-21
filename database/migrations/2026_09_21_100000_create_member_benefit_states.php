<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Feedback Karla 21-sep · "Requerimiento del apagador":
 *
 * Kinvoo no ejecuta ningún beneficio — la telemedicina la opera 1DOC3, el
 * seguro de vida Thona, etc. Esta tabla guarda un estado manual on/off por
 * cada miembro × cada subservicio de la membresía Esencial:
 *   - telemedicina         (proveedor 1DOC3)
 *   - seguro_vida          (proveedor Thona Seguros)
 *   - bolsa_trabajo        (interno Kinvoo)
 *   - desarrollo           (interno Kinvoo)
 *
 * El admin lo enciende cuando el trámite con el proveedor está listo, el
 * miembro lo ve "Activo"; en apagado el miembro lo ve "Pendiente". No hay
 * agenda, citas ni integración: sólo refleja un estado que se resolvió por
 * fuera de la plataforma.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_benefit_states', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Guardamos la key como string para no bloquear el schema si el
            // día de mañana se agrega un subservicio nuevo desde el enum.
            $t->string('benefit_key', 40);
            $t->boolean('activo')->default(false);
            $t->timestamp('activated_at')->nullable();
            $t->timestamp('deactivated_at')->nullable();
            $t->text('admin_notes')->nullable();
            // Traza legal: qué admin hizo el último cambio (útil también para
            // que la Bitácora enlace al actor sin depender del AuditLog).
            $t->foreignId('changed_by_admin_id')->nullable()
                ->constrained('users')->nullOnDelete();
            $t->timestamps();

            // Un solo estado por miembro × beneficio.
            $t->unique(['user_id', 'benefit_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_benefit_states');
    }
};

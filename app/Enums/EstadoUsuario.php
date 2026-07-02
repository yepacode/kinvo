<?php

namespace App\Enums;

/**
 * Estado de aprobación de un usuario. La aprobación de registros está ACTIVADA:
 * todo perfil nace en Pendiente y el owner lo aprueba antes de publicarlo.
 */
enum EstadoUsuario: string
{
    case Pendiente = 'pending';
    case Activo = 'active';
    case Suspendido = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::Activo => 'Activo',
            self::Suspendido => 'Suspendido',
        };
    }
}

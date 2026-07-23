<?php

namespace App\Enums;

/**
 * Estado de aprobación de un usuario. La aprobación de registros está ACTIVADA.
 *
 * Flujo del PROFESIONAL (1 sola aprobación):
 *   Pendiente → (admin aprueba) → Activo + perfil publicado
 *
 * Flujo del CONTRATISTA (2 aprobaciones, requerido por cliente 22-jul):
 *   Pendiente → (admin aprueba cuenta, revisa membresía) → PerfilPendiente
 *             → contratista llena perfil de empresa y hace submit
 *             → (admin aprueba perfil) → Activo + perfil visible en /estudio
 *
 * PerfilPendiente sólo aplica a contratistas: cuenta OK para llenar su
 * perfil, pero AÚN no puede usar el buscador de talento ni contactar.
 */
enum EstadoUsuario: string
{
    case Pendiente = 'pending';
    case PerfilPendiente = 'profile_pending';
    case Activo = 'active';
    case Suspendido = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::PerfilPendiente => 'Perfil en revisión',
            self::Activo => 'Activo',
            self::Suspendido => 'Suspendido',
        };
    }
}

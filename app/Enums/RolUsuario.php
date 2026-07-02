<?php

namespace App\Enums;

/**
 * Roles de la plataforma Kinvoo. Patrón heredado de New Talent Map:
 * enum respaldado por una columna entera `nivel` en la tabla users
 * (sin spatie/laravel-permission).
 */
enum RolUsuario: int
{
    case Admin = 0;        // owner que administra el panel Filament
    case Professional = 1; // profesional del fitness (perfil autoeditable)
    case Contractor = 2;   // contratante (estudio/gimnasio/marca)

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Professional => 'Profesional',
            self::Contractor => 'Contratante',
        };
    }

    /** Solo el owner accede al panel administrativo. */
    public function accesoPanel(): bool
    {
        return $this === self::Admin;
    }
}

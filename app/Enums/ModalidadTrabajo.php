<?php

namespace App\Enums;

/**
 * Modalidad de trabajo del profesional del fitness.
 */
enum ModalidadTrabajo: string
{
    case Presencial = 'presencial';
    case Online = 'online';
    case Hibrido = 'hibrido';

    public function label(): string
    {
        return match ($this) {
            self::Presencial => 'Presencial',
            self::Online => 'Online',
            self::Hibrido => 'Híbrido',
        };
    }

    /** Opciones para selects de Filament / formularios (value => label). */
    public static function opciones(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $m) => [$m->value => $m->label()])
            ->all();
    }
}

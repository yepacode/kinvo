<?php

namespace App\Enums;

enum EstadoContacto: string
{
    case NoLeido = 'unread';
    case Leido = 'read';

    public function label(): string
    {
        return match ($this) {
            self::NoLeido => 'No leído',
            self::Leido => 'Leído',
        };
    }
}

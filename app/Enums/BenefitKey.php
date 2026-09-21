<?php

namespace App\Enums;

/**
 * Subservicios de la membresía Esencial (feedback Karla 21-sep, PDF
 * "Requerimiento del apagador"). Los 4 beneficios que llevan interruptor
 * on/off manual del admin, agrupados por pilar:
 *
 *   Pilar Cuidado       → Telemedicina + Seguro de vida colectivo
 *   Pilar Crecimiento   → Desarrollo (contenido propio en video)
 *   Pilar Oportunidades → Bolsa de trabajo
 *
 * Comunidad (4º pilar) no lleva apagador: es red entre coaches.
 */
enum BenefitKey: string
{
    case Telemedicina = 'telemedicina';
    case SeguroVida   = 'seguro_vida';
    case BolsaTrabajo = 'bolsa_trabajo';
    case Desarrollo   = 'desarrollo';

    public function label(): string
    {
        return match ($this) {
            self::Telemedicina => 'Telemedicina',
            self::SeguroVida   => 'Seguro de vida colectivo',
            self::BolsaTrabajo => 'Bolsa de trabajo',
            self::Desarrollo   => 'Desarrollo',
        };
    }

    public function pilar(): string
    {
        return match ($this) {
            self::Telemedicina, self::SeguroVida => 'Cuidado',
            self::Desarrollo   => 'Crecimiento',
            self::BolsaTrabajo => 'Oportunidades',
        };
    }

    /** Proveedor externo que opera el beneficio (null si es interno Kinvoo). */
    public function proveedor(): ?string
    {
        return match ($this) {
            self::Telemedicina => '1DOC3',
            self::SeguroVida   => 'Thona Seguros',
            default            => null,
        };
    }

    /** Copy que el miembro ve al lado del beneficio cuando aún NO está activo. */
    public function copyPendiente(): string
    {
        return match ($this) {
            self::Telemedicina => 'Se activa cuando Kinvoo confirma tu alta en la app de 1DOC3.',
            self::SeguroVida   => 'Se activa cuando llegue tu póliza firmada de Thona Seguros ($50,000 de suma asegurada).',
            self::BolsaTrabajo => 'Se activa cuando Kinvoo habilite tu acceso al directorio de vacantes.',
            self::Desarrollo   => 'Se activa cuando Kinvoo habilite tu acceso al material de desarrollo.',
        };
    }

    public function icono(): string
    {
        return match ($this) {
            self::Telemedicina => '🩺',
            self::SeguroVida   => '🛡️',
            self::BolsaTrabajo => '💼',
            self::Desarrollo   => '📚',
        };
    }

    /** Todos los subservicios en orden de presentación (por pilar). */
    public static function todos(): array
    {
        return [
            self::Telemedicina,
            self::SeguroVida,
            self::Desarrollo,
            self::BolsaTrabajo,
        ];
    }
}

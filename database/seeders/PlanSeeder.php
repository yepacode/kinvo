<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

/**
 * Planes de arranque según el documento del cliente (Individual y Estudios).
 * Son un punto de partida editable: la dueña ajusta precios, beneficios y cobertura.
 */
class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $planes = [
            ['nombre' => 'Esencial', 'audiencia' => 'individual', 'orden' => 1,
                'descripcion' => 'Plan individual básico.'],
            ['nombre' => 'Pro', 'audiencia' => 'individual', 'orden' => 2, 'destacado' => true,
                'descripcion' => 'Plan individual con más beneficios.'],
            ['nombre' => 'Esencial', 'audiencia' => 'estudio', 'orden' => 3,
                'descripcion' => 'Membresía básica para estudios y marcas.'],
            ['nombre' => 'Plus', 'audiencia' => 'estudio', 'orden' => 4,
                'descripcion' => 'Membresía intermedia para estudios y marcas.'],
            ['nombre' => 'Pro', 'audiencia' => 'estudio', 'orden' => 5, 'destacado' => true,
                'descripcion' => 'Membresía completa para estudios y marcas.'],
        ];

        foreach ($planes as $p) {
            Plan::updateOrCreate(
                ['nombre' => $p['nombre'], 'audiencia' => $p['audiencia']],
                array_merge(['activo' => true, 'periodo' => 'mensual', 'moneda' => 'MXN'], $p)
            );
        }
    }
}

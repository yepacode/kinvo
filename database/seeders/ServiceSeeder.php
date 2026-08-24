<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Service;
use Illuminate\Database\Seeder;

/**
 * Catálogo inicial de servicios (Punto 5-A). Idempotente (firstOrCreate por
 * slug), así que se puede correr sin duplicar. Adjunta el catálogo a los planes
 * existentes como punto de partida — el admin ajusta luego qué incluye cada plan.
 */
class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $servicios = [
            ['nombre' => 'Salud / Telemedicina', 'icono' => '🩺', 'orden' => 1,
             'descripcion' => 'Consulta médica en línea con un profesional de la salud.'],
            ['nombre' => 'Fisioterapia', 'icono' => '💪', 'orden' => 2,
             'descripcion' => 'Sesión de fisioterapia para prevención y recuperación.'],
            ['nombre' => 'Nutrición', 'icono' => '🥗', 'orden' => 3,
             'descripcion' => 'Asesoría nutricional personalizada.'],
            ['nombre' => 'Psicología', 'icono' => '🧠', 'orden' => 4,
             'descripcion' => 'Acompañamiento psicológico y bienestar emocional.'],
        ];

        $ids = [];
        foreach ($servicios as $s) {
            $service = Service::firstOrCreate(
                ['slug' => \Illuminate\Support\Str::slug($s['nombre'])],
                $s + ['activo' => true],
            );
            $ids[] = $service->id;
        }

        // Punto de partida: incluir el catálogo en los planes existentes.
        // El admin puede quitar/ajustar por plan desde el panel.
        Plan::all()->each(fn (Plan $plan) => $plan->services()->syncWithoutDetaching($ids));
    }
}

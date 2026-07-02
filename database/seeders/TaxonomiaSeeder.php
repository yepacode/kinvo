<?php

namespace Database\Seeders;

use App\Models\Certification;
use App\Models\Discipline;
use App\Models\Location;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TaxonomiaSeeder extends Seeder
{
    public function run(): void
    {
        // Disciplinas del fitness (ES / EN)
        $disciplinas = [
            ['Entrenamiento funcional', 'Functional training'],
            ['Entrenamiento personal', 'Personal training'],
            ['CrossFit', 'CrossFit'],
            ['Musculación', 'Bodybuilding'],
            ['HIIT', 'HIIT'],
            ['Calistenia', 'Calisthenics'],
            ['Yoga', 'Yoga'],
            ['Pilates', 'Pilates'],
            ['Spinning', 'Indoor cycling'],
            ['Zumba', 'Zumba'],
            ['Boxeo', 'Boxing'],
            ['Natación', 'Swimming'],
            ['Nutrición deportiva', 'Sports nutrition'],
            ['Fisioterapia deportiva', 'Sports physiotherapy'],
        ];
        foreach ($disciplinas as [$es, $en]) {
            Discipline::firstOrCreate(
                ['slug' => Str::slug($es)],
                ['nombre' => $es, 'nombre_en' => $en, 'activo' => true],
            );
        }

        // Certificaciones comunes
        $certificaciones = [
            ['NASM-CPT', 'NASM-CPT'],
            ['ACE Personal Trainer', 'ACE Personal Trainer'],
            ['NSCA-CPT', 'NSCA-CPT'],
            ['ISSA', 'ISSA'],
            ['CrossFit Level 1', 'CrossFit Level 1'],
            ['Instructor de Yoga (RYT-200)', 'Yoga Instructor (RYT-200)'],
            ['Nutrición certificada', 'Certified nutrition'],
            ['Primeros auxilios / RCP', 'First aid / CPR'],
        ];
        foreach ($certificaciones as [$es, $en]) {
            Certification::firstOrCreate(
                ['slug' => Str::slug($es)],
                ['nombre' => $es, 'nombre_en' => $en, 'activo' => true],
            );
        }

        // Ubicaciones (México)
        $ubicaciones = [
            ['Ciudad de México', 'CDMX'],
            ['Guadalajara', 'Jalisco'],
            ['Monterrey', 'Nuevo León'],
            ['Puebla', 'Puebla'],
            ['Querétaro', 'Querétaro'],
            ['Mérida', 'Yucatán'],
            ['Tijuana', 'Baja California'],
            ['Cancún', 'Quintana Roo'],
        ];
        foreach ($ubicaciones as [$ciudad, $region]) {
            Location::firstOrCreate(
                ['ciudad' => $ciudad, 'region' => $region],
                ['pais' => 'México', 'activo' => true],
            );
        }
    }
}

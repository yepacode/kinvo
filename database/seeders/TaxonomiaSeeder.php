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

        // Ubicaciones (México) — 32 capitales estatales + ciudades secundarias de
        // mercado laboral relevante para fitness/bienestar. Petición Karla 27-ago:
        // "todas las de México, borra huellas de Colombia".
        $ubicaciones = [
            // Capitales de los 32 estados
            ['Aguascalientes', 'Aguascalientes'],
            ['Mexicali', 'Baja California'],
            ['La Paz', 'Baja California Sur'],
            ['San Francisco de Campeche', 'Campeche'],
            ['Tuxtla Gutiérrez', 'Chiapas'],
            ['Chihuahua', 'Chihuahua'],
            ['Saltillo', 'Coahuila'],
            ['Colima', 'Colima'],
            ['Ciudad de México', 'CDMX'],
            ['Victoria de Durango', 'Durango'],
            ['Toluca de Lerdo', 'Estado de México'],
            ['Guanajuato', 'Guanajuato'],
            ['Chilpancingo', 'Guerrero'],
            ['Pachuca de Soto', 'Hidalgo'],
            ['Guadalajara', 'Jalisco'],
            ['Morelia', 'Michoacán'],
            ['Cuernavaca', 'Morelos'],
            ['Tepic', 'Nayarit'],
            ['Monterrey', 'Nuevo León'],
            ['Oaxaca de Juárez', 'Oaxaca'],
            ['Puebla de Zaragoza', 'Puebla'],
            ['Santiago de Querétaro', 'Querétaro'],
            ['Chetumal', 'Quintana Roo'],
            ['San Luis Potosí', 'San Luis Potosí'],
            ['Culiacán', 'Sinaloa'],
            ['Hermosillo', 'Sonora'],
            ['Villahermosa', 'Tabasco'],
            ['Ciudad Victoria', 'Tamaulipas'],
            ['Tlaxcala', 'Tlaxcala'],
            ['Xalapa-Enríquez', 'Veracruz'],
            ['Mérida', 'Yucatán'],
            ['Zacatecas', 'Zacatecas'],
            // Ciudades secundarias de alto tráfico (mercado laboral relevante)
            ['Tijuana', 'Baja California'],
            ['Cancún', 'Quintana Roo'],
            ['Playa del Carmen', 'Quintana Roo'],
            ['León', 'Guanajuato'],
            ['San Miguel de Allende', 'Guanajuato'],
            ['Puerto Vallarta', 'Jalisco'],
            ['Zapopan', 'Jalisco'],
            ['Ciudad Juárez', 'Chihuahua'],
            ['San Pedro Garza García', 'Nuevo León'],
            ['Ensenada', 'Baja California'],
            ['Los Cabos', 'Baja California Sur'],
            ['Acapulco', 'Guerrero'],
            ['Ixtapa-Zihuatanejo', 'Guerrero'],
            ['Cozumel', 'Quintana Roo'],
            ['Tulum', 'Quintana Roo'],
            ['Naucalpan de Juárez', 'Estado de México'],
        ];
        foreach ($ubicaciones as [$ciudad, $region]) {
            Location::firstOrCreate(
                ['ciudad' => $ciudad, 'region' => $region],
                ['pais' => 'México', 'activo' => true],
            );
        }
    }
}

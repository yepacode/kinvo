<?php

namespace Database\Seeders;

use App\Enums\EstadoUsuario;
use App\Enums\RolUsuario;
use App\Models\Discipline;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Datos de demostración (no se llama desde DatabaseSeeder).
 * Correr con: php artisan db:seed --class=DemoSeeder
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(TaxonomiaSeeder::class);

        $u = User::updateOrCreate(
            ['email' => 'demo.coach@gokinvoo.com'],
            [
                'name' => 'Ana Torres',
                'password' => Hash::make('password'),
                'nivel' => RolUsuario::Professional,
                'estado' => EstadoUsuario::Activo,
                'email_verified_at' => now(),
            ]
        );

        $p = $u->professionalProfile()->firstOrCreate([]);
        $p->update([
            'headline' => 'Coach de fuerza y acondicionamiento',
            'bio' => "Entrenadora con enfoque en powerlifting y movilidad.\nAyudo a construir fuerza real y sostenible.",
            'years_experience' => 8,
            'modalidad' => 'hibrido',
            'location_id' => Location::where('ciudad', 'Guadalajara')->value('id'),
            'is_published' => true,
        ]);
        $p->disciplines()->sync(
            Discipline::whereIn('slug', ['entrenamiento-funcional', 'crossfit', 'musculacion'])->pluck('id')
        );

        // Más perfiles publicados para poblar el buscador.
        $demo = [
            ['Luis Márquez', 'Instructor de CrossFit', 'presencial', 'Monterrey', ['crossfit', 'hiit']],
            ['Sofía Ramírez', 'Instructora de yoga y pilates', 'hibrido', 'Ciudad de México', ['yoga', 'pilates']],
            ['Diego Herrera', 'Entrenador personal online', 'online', 'Guadalajara', ['entrenamiento-personal', 'musculacion']],
            ['Valeria Cruz', 'Coach de spinning y HIIT', 'presencial', 'Puebla', ['spinning', 'hiit']],
            ['Andrés Gómez', 'Nutriólogo deportivo', 'online', 'Mérida', ['nutricion-deportiva']],
            ['Camila Ortiz', 'Instructora de boxeo', 'presencial', 'Cancún', ['boxeo', 'entrenamiento-funcional']],
            ['Jorge Pineda', 'Especialista en calistenia', 'hibrido', 'Tijuana', ['calistenia', 'entrenamiento-funcional']],
        ];
        foreach ($demo as [$nombre, $headline, $mod, $ciudad, $discs]) {
            $du = User::updateOrCreate(
                ['email' => Str::slug($nombre).'@demo.gokinvoo.com'],
                [
                    'name' => $nombre,
                    'password' => Hash::make('password'),
                    'nivel' => RolUsuario::Professional,
                    'estado' => EstadoUsuario::Activo,
                    'email_verified_at' => now(),
                ]
            );
            $dp = $du->professionalProfile()->firstOrCreate([]);
            $dp->update([
                'headline' => $headline,
                'modalidad' => $mod,
                'years_experience' => rand(2, 12),
                'location_id' => Location::where('ciudad', $ciudad)->value('id'),
                'is_published' => true,
            ]);
            $dp->disciplines()->sync(Discipline::whereIn('slug', $discs)->pluck('id'));
        }

        // Contratante demo (activo) para probar el flujo de contacto.
        $contratante = User::updateOrCreate(
            ['email' => 'demo.gym@gokinvoo.com'],
            [
                'name' => 'Estudio Zen',
                'password' => Hash::make('password'),
                'nivel' => RolUsuario::Contractor,
                'estado' => EstadoUsuario::Activo,
                'email_verified_at' => now(),
            ]
        );
        $contratante->companyProfile()->firstOrCreate([], [
            'company_name' => 'Estudio Zen',
            'sector' => 'Estudio de yoga',
            'location_id' => Location::where('ciudad', 'Ciudad de México')->value('id'),
        ]);

        // Un contacto de ejemplo para la bitácora del owner.
        $p->contacts()->firstOrCreate(
            ['contact_email' => 'demo.gym@gokinvoo.com'],
            [
                'contractor_user_id' => $contratante->id,
                'contact_name' => 'Estudio Zen',
                'contact_phone' => '+52 55 1234 5678',
                'message' => 'Hola Ana, buscamos una coach de fuerza para clases grupales. ¿Te interesa?',
                'estado' => \App\Enums\EstadoContacto::NoLeido,
            ]
        );

        $this->command->info('Demo listo → /talento (buscador) · perfil /talento/'.$p->fresh()->slug);
        $this->command->info('Contratante demo: demo.gym@gokinvoo.com / password');
    }
}

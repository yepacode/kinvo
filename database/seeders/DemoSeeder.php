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
    /** Crea/actualiza un usuario demo activo (nivel/estado no son mass-assignable → forceFill). */
    private function usuario(string $email, string $name, RolUsuario $rol): User
    {
        $u = User::updateOrCreate(['email' => $email], ['name' => $name, 'password' => Hash::make('password')]);
        $u->forceFill(['nivel' => $rol, 'estado' => EstadoUsuario::Activo, 'email_verified_at' => now()])->save();

        return $u;
    }

    public function run(): void
    {
        $this->call(TaxonomiaSeeder::class);

        $u = $this->usuario('demo.coach@gokinvoo.com', 'Ana Torres', RolUsuario::Professional);

        $p = $u->professionalProfile()->firstOrCreate([]);
        $p->update([
            'headline' => 'Coach de fuerza y acondicionamiento',
            'bio' => "Entrenadora con enfoque en powerlifting y movilidad.\nAyudo a construir fuerza real y sostenible.",
            'years_experience' => 8,
            'modalidad' => 'hibrido',
            'location_id' => Location::where('ciudad', 'Guadalajara')->value('id'),
            'is_published' => true,
            'is_verified' => true,
            'verified_at' => now(),
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
            $du = $this->usuario(Str::slug($nombre).'@demo.gokinvoo.com', $nombre, RolUsuario::Professional);
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
        $contratante = $this->usuario('demo.gym@gokinvoo.com', 'Estudio Zen', RolUsuario::Contractor);
        $contratante->companyProfile()->firstOrCreate([], [
            'company_name' => 'Estudio Zen',
            'sector' => 'Estudio de yoga',
            'location_id' => Location::where('ciudad', 'Ciudad de México')->value('id'),
        ]);

        // Un contacto de ejemplo para la bitácora del owner.
        $contacto = $p->contacts()->firstOrCreate(
            ['contact_email' => 'demo.gym@gokinvoo.com'],
            [
                'contractor_user_id' => $contratante->id,
                'contact_name' => 'Estudio Zen',
                'contact_phone' => '+52 55 1234 5678',
                'message' => 'Hola Ana, buscamos una coach de fuerza para clases grupales. ¿Te interesa?',
                'estado' => \App\Enums\EstadoContacto::NoLeido,
            ]
        );

        // Notificación in-app de ejemplo para el profesional demo.
        if ($u->notifications()->count() === 0) {
            $u->notify(new \App\Notifications\NuevoContactoNotification($contacto));
        }

        $this->command->info('Demo listo → /talento (buscador) · perfil /talento/'.$p->fresh()->slug);
        $this->command->info('Contratante demo: demo.gym@gokinvoo.com / password');
    }
}

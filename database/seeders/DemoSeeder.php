<?php

namespace Database\Seeders;

use App\Enums\EstadoUsuario;
use App\Enums\RolUsuario;
use App\Models\Discipline;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

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

        $this->command->info('Demo listo → /talento/'.$p->fresh()->slug);
    }
}

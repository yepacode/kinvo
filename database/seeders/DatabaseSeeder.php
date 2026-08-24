<?php

namespace Database\Seeders;

use App\Enums\EstadoUsuario;
use App\Enums\RolUsuario;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(TaxonomiaSeeder::class);
        $this->call(PlanSeeder::class);
        $this->call(ServiceSeeder::class); // catálogo de servicios (Punto 5-A), tras los planes
        $this->call(EmailTemplateSeeder::class); // plantillas de correo editables (Punto 13)

        // Owner de Kinvoo (acceso al panel Filament).
        $owner = User::updateOrCreate(
            ['email' => 'hola@gokinvoo.com'],
            [
                'name' => 'Kinvoo Admin',
                'password' => Hash::make('password'), // cambiar en producción
            ]
        );
        // nivel/estado no son mass-assignable → forceFill.
        $owner->forceFill([
            'nivel' => RolUsuario::Admin,
            'estado' => EstadoUsuario::Activo,
            'email_verified_at' => now(),
        ])->save();
    }
}

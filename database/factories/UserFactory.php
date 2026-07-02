<?php

namespace Database\Factories;

use App\Enums\EstadoUsuario;
use App\Enums\RolUsuario;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'nivel' => RolUsuario::Professional,
            'estado' => EstadoUsuario::Activo,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /** Usuario con rol Admin (owner). */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'nivel' => RolUsuario::Admin,
            'estado' => EstadoUsuario::Activo,
        ]);
    }

    /** Usuario contratante. */
    public function contratante(): static
    {
        return $this->state(fn (array $attributes) => [
            'nivel' => RolUsuario::Contractor,
        ]);
    }

    /** Cuenta pendiente de aprobación. */
    public function pendiente(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => EstadoUsuario::Pendiente,
        ]);
    }
}

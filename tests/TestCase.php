<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Autentica a un contratista con membresía vigente (quien SÍ puede ver el
     * directorio y los perfiles de talento) y lo devuelve.
     */
    protected function actingAsSocio(): User
    {
        $socio = User::factory()->contratante()->create(); // membresía activa por defecto
        $this->actingAs($socio);

        return $socio;
    }
}

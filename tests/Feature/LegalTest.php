<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalTest extends TestCase
{
    use RefreshDatabase;

    public function test_aviso_de_privacidad_carga(): void
    {
        $this->get(route('legal.privacidad'))
            ->assertStatus(200)
            ->assertSee('Aviso de Privacidad')
            ->assertSee('Ley Federal de Protección de Datos Personales');
    }

    public function test_terminos_y_condiciones_carga(): void
    {
        $this->get(route('legal.terminos'))
            ->assertStatus(200)
            ->assertSee('Términos y Condiciones')
            ->assertSee('Mediador-Operador');
    }

    public function test_paginas_legales_son_editables_desde_settings(): void
    {
        SiteSetting::set('legal_privacy_body', 'Texto de prueba editado desde el panel.');

        $this->get(route('legal.privacidad'))
            ->assertStatus(200)
            ->assertSee('Texto de prueba editado desde el panel.');
    }

    public function test_footer_muestra_leyenda_de_derechos(): void
    {
        $this->get(route('legal.privacidad'))
            ->assertSee('Todos los derechos reservados');
    }
}

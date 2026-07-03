<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SesionTest extends TestCase
{
    use RefreshDatabase;

    public function test_paginas_autenticadas_no_se_cachean(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_logout_redirige_al_home(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect('/');

        $this->assertGuest();
    }

    public function test_tras_logout_no_se_accede_al_perfil(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout');

        // Sin sesión, la ruta protegida manda al login (no muestra el perfil).
        $this->get('/mi-perfil')->assertRedirect(route('login'));
    }
}

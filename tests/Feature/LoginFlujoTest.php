<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginFlujoTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_entra_al_panel(): void
    {
        $owner = User::factory()->admin()->create();
        $this->actingAs($owner)->get('/admin')->assertStatus(200);
    }

    public function test_owner_login_redirige_al_panel(): void
    {
        $owner = User::factory()->admin()->create(['email' => 'owner@test.com']);

        $this->post('/login', ['email' => 'owner@test.com', 'password' => 'password'])
            ->assertRedirect(route('filament.admin.pages.dashboard'));
    }

    public function test_profesional_en_panel_es_redirigido_a_su_area(): void
    {
        $prof = User::factory()->create(); // Professional activo
        $this->actingAs($prof)->get('/admin')->assertRedirect(route('dashboard'));
    }

    public function test_profesional_login_va_al_dashboard_no_al_panel(): void
    {
        $prof = User::factory()->create(['email' => 'prof@test.com']);

        $this->post('/login', ['email' => 'prof@test.com', 'password' => 'password'])
            ->assertRedirect(route('dashboard'));
    }
}

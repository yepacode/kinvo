<?php

namespace Tests\Feature;

use App\Filament\Resources\ProfessionalProfileResource\Pages\ListProfessionalProfiles;
use App\Models\ProfessionalProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VerificacionTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_verifica_un_perfil(): void
    {
        $owner = User::factory()->admin()->create();
        $profile = User::factory()->create()->professionalProfile()->create(['is_published' => true]);

        $this->actingAs($owner);
        Livewire::test(ListProfessionalProfiles::class)->callTableAction('verificar', $profile);

        $profile->refresh();
        $this->assertTrue($profile->is_verified);
        $this->assertNotNull($profile->verified_at);
    }

    public function test_badge_verificado_aparece_en_el_perfil_publico(): void
    {
        $profile = User::factory()->create()
            ->professionalProfile()->create(['is_published' => true, 'is_verified' => true, 'verified_at' => now()]);

        $this->actingAsSocio();
        $this->get(route('talento.show', $profile->slug))
            ->assertSee('Perfil verificado');
    }

    public function test_perfil_no_verificado_no_muestra_badge(): void
    {
        $profile = User::factory()->create()
            ->professionalProfile()->create(['is_published' => true, 'is_verified' => false]);

        $this->actingAsSocio();
        $this->get(route('talento.show', $profile->slug))
            ->assertDontSee('Perfil verificado');
    }
}

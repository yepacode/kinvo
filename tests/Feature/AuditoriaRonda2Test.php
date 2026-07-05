<?php

namespace Tests\Feature;

use App\Enums\EstadoUsuario;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\Discipline;
use App\Models\ProfessionalProfile;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

class AuditoriaRonda2Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_sitemap_excluye_perfiles_de_dueno_no_activo(): void
    {
        $u = User::factory()->create();
        $p = $u->professionalProfile()->create(['is_published' => true]);

        $this->get('/sitemap.xml')->assertSee($p->slug, false);

        $u->forceFill(['estado' => EstadoUsuario::Suspendido])->save();
        $this->get('/sitemap.xml')->assertDontSee($p->slug, false);
    }

    public function test_suspender_despublica_y_quita_verificacion(): void
    {
        $owner = User::factory()->admin()->create();
        $activo = User::factory()->create();
        $profile = $activo->professionalProfile()->create(['is_published' => true, 'is_verified' => true, 'verified_at' => now()]);

        $this->actingAs($owner);
        Livewire::test(ListUsers::class)->callTableAction('suspender', $activo);

        $profile->refresh();
        $this->assertFalse($profile->is_published);
        $this->assertFalse($profile->is_verified);
    }

    public function test_cabeceras_de_seguridad_presentes(): void
    {
        $this->get('/')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_no_se_borra_una_disciplina_en_uso(): void
    {
        $disc = Discipline::create(['nombre' => 'Yoga', 'slug' => 'yoga']);
        $profile = User::factory()->create()->professionalProfile()->create(['is_published' => true]);
        $profile->disciplines()->attach($disc->id);

        $this->assertFalse($disc->fresh()->delete());
        $this->assertDatabaseHas('disciplines', ['id' => $disc->id]);

        // Sin uso sí se puede borrar
        $libre = Discipline::create(['nombre' => 'Boxeo libre', 'slug' => 'boxeo-libre']);
        $this->assertTrue((bool) $libre->delete());
    }

    public function test_landing_rich_no_genera_em_mal_formado(): void
    {
        SiteSetting::set('hero_title', 'Hola *mundo suelto y **dobles**');
        $html = (string) landing_rich('hero_title');

        // No debe haber <em> anidados ni vacíos
        $this->assertStringNotContainsString('<em></em>', $html);
        $this->assertStringNotContainsString('<em><em>', $html);
    }

    public function test_no_se_puede_guardar_perfil_de_suspendido(): void
    {
        $u = User::factory()->create();
        $p = $u->professionalProfile()->create(['is_published' => true]);
        $u->forceFill(['estado' => EstadoUsuario::Suspendido])->save();

        $contratante = User::factory()->contratante()->create();
        $this->actingAs($contratante)->post(route('saves.toggleProfile', $p->slug))->assertStatus(404);
    }
}

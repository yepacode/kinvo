<?php

namespace Tests\Feature;

use App\Enums\EstadoUsuario;
use App\Models\ProfessionalProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeguridadTest extends TestCase
{
    use RefreshDatabase;

    public function test_perfil_de_usuario_suspendido_no_es_visible_ni_contactable(): void
    {
        $user = User::factory()->create();
        $profile = $user->professionalProfile()->create(['is_published' => true, 'headline' => 'Coach Suspendida']);

        // Visible mientras está activo
        $this->get(route('talento.show', $profile->slug))->assertStatus(200);
        $this->get(route('talento.index'))->assertSee('Coach Suspendida');

        // Al suspender al dueño, su perfil desaparece del público
        $user->forceFill(['estado' => EstadoUsuario::Suspendido])->save();

        $this->get(route('talento.show', $profile->slug))->assertStatus(404);
        $this->get(route('talento.index'))->assertDontSee('Coach Suspendida');

        $contratante = User::factory()->contratante()->create();
        $this->actingAs($contratante)->get(route('contacto.create', $profile->slug))->assertStatus(404);
    }

    public function test_el_nombre_no_permite_xss_en_el_json_ld(): void
    {
        $user = User::factory()->create(['name' => '</script><script>alert(1)</script>']);
        $profile = $user->professionalProfile()->create(['is_published' => true]);

        $html = $this->get(route('talento.show', $profile->slug))->assertStatus(200)->getContent();

        // El </script> del nombre no debe aparecer literal dentro del JSON-LD.
        $this->assertStringNotContainsString('</script><script>alert(1)', $html);
        $this->assertStringContainsString('<', $html); // < escapado como <
    }

    public function test_nivel_y_estado_no_son_asignables_en_masa(): void
    {
        $user = User::factory()->create();

        $user->fill(['nivel' => \App\Enums\RolUsuario::Admin, 'estado' => EstadoUsuario::Suspendido]);
        $user->save();

        // fill() debe ignorar nivel/estado (no están en $fillable)
        $this->assertNotSame(\App\Enums\RolUsuario::Admin, $user->fresh()->nivel);
    }

    public function test_sitio_web_rechaza_esquema_javascript(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('professional.profile.update'), [
            'web' => 'javascript:alert(1)',
        ])->assertSessionHasErrors('web');
    }
}

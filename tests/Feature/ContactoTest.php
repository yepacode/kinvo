<?php

namespace Tests\Feature;

use App\Enums\EstadoContacto;
use App\Mail\NuevoContacto;
use App\Models\Contact;
use App\Models\ProfessionalProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactoTest extends TestCase
{
    use RefreshDatabase;

    private function perfilPublicado(): ProfessionalProfile
    {
        $user = User::factory()->create(['name' => 'Ana Coach']);
        return $user->professionalProfile()->create([
            'headline' => 'Coach',
            'is_published' => true,
        ]);
    }

    public function test_contratante_activo_ve_boton_contactar(): void
    {
        $profile = $this->perfilPublicado();
        $contratante = User::factory()->contratante()->create();

        $this->actingAs($contratante)
            ->get(route('talento.show', $profile->slug))
            ->assertSee('Contactar a');
    }

    public function test_invitado_no_ve_boton_contactar(): void
    {
        $profile = $this->perfilPublicado();

        $this->get(route('talento.show', $profile->slug))
            ->assertDontSee('Contactar a')
            ->assertSee('Inicia sesión');
    }

    public function test_profesional_no_ve_boton_contactar(): void
    {
        $profile = $this->perfilPublicado();
        $otroProfesional = User::factory()->create();

        $this->actingAs($otroProfesional)
            ->get(route('talento.show', $profile->slug))
            ->assertDontSee('Contactar a');
    }

    public function test_contratante_envia_contacto_y_se_notifica(): void
    {
        Mail::fake();
        $profile = $this->perfilPublicado();
        $contratante = User::factory()->contratante()->create();

        $this->actingAs($contratante)
            ->post(route('contacto.store', $profile->slug), [
                'contact_name' => 'Estudio Zen',
                'contact_email' => 'zen@example.com',
                'contact_phone' => '+52 33 1234 5678',
                'message' => 'Nos encantaría trabajar contigo en nuestro estudio.',
            ])
            ->assertRedirect(route('talento.show', $profile->slug))
            ->assertSessionHas('status', 'contacto-enviado');

        $this->assertDatabaseHas('contacts', [
            'professional_profile_id' => $profile->id,
            'contractor_user_id' => $contratante->id,
            'contact_email' => 'zen@example.com',
            'estado' => EstadoContacto::NoLeido->value,
        ]);

        Mail::assertQueued(NuevoContacto::class); // el correo va en cola, no síncrono
    }

    public function test_contacto_se_guarda_aunque_el_correo_falle(): void
    {
        // Simula el bug de producción: SMTP caído/lento. El contacto debe guardarse
        // y el usuario ver el "enviado", nunca un 500.
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP caído'));

        $profile = $this->perfilPublicado();
        $contratante = User::factory()->contratante()->create();

        $this->actingAs($contratante)
            ->post(route('contacto.store', $profile->slug), [
                'contact_name' => 'Estudio X',
                'contact_email' => 'x@example.com',
                'message' => 'Queremos trabajar contigo pronto.',
            ])
            ->assertRedirect(route('talento.show', $profile->slug))
            ->assertSessionHas('status', 'contacto-enviado');

        $this->assertDatabaseHas('contacts', [
            'professional_profile_id' => $profile->id,
            'contact_email' => 'x@example.com',
        ]);
    }

    public function test_profesional_no_puede_contactar(): void
    {
        $profile = $this->perfilPublicado();
        $otro = User::factory()->create();

        $this->actingAs($otro)
            ->post(route('contacto.store', $profile->slug), [
                'contact_name' => 'X', 'contact_email' => 'x@example.com',
                'message' => 'Mensaje suficientemente largo.',
            ])
            ->assertStatus(403);

        $this->assertSame(0, Contact::count());
    }

    public function test_invitado_es_redirigido_a_login(): void
    {
        $profile = $this->perfilPublicado();

        $this->post(route('contacto.store', $profile->slug), [
            'contact_name' => 'X', 'contact_email' => 'x@example.com',
            'message' => 'Mensaje suficientemente largo.',
        ])->assertRedirect(route('login'));
    }

    public function test_no_se_puede_contactar_perfil_no_publicado(): void
    {
        $user = User::factory()->create();
        $profile = $user->professionalProfile()->create(['is_published' => false]);
        $contratante = User::factory()->contratante()->create();

        $this->actingAs($contratante)
            ->get(route('contacto.create', $profile->slug))
            ->assertStatus(404);
    }

    public function test_el_email_de_contacto_se_renderiza(): void
    {
        $profile = $this->perfilPublicado();
        $contratante = User::factory()->contratante()->create();
        $contact = $profile->contacts()->create([
            'contractor_user_id' => $contratante->id,
            'contact_name' => 'Estudio Zen',
            'contact_email' => 'zen@example.com',
            'message' => 'Nos encantaría trabajar contigo.',
            'estado' => EstadoContacto::NoLeido,
        ]);

        $html = (new NuevoContacto($contact, $profile->load('user')))->render();

        $this->assertStringContainsString('Estudio Zen', $html);
        $this->assertStringContainsString('nuevo contacto', mb_strtolower($html));
    }

    public function test_owner_ve_los_contactos_en_el_panel(): void
    {
        $profile = $this->perfilPublicado();
        $contratante = User::factory()->contratante()->create();
        $profile->contacts()->create([
            'contractor_user_id' => $contratante->id,
            'contact_name' => 'Estudio Zen',
            'contact_email' => 'zen@example.com',
            'message' => 'Hola, queremos contactarte.',
            'estado' => EstadoContacto::NoLeido,
        ]);

        $owner = User::factory()->admin()->create();

        $this->actingAs($owner)
            ->get('/admin/contacts')
            ->assertStatus(200)
            ->assertSee('Estudio Zen')
            ->assertSee('Ana Coach');
    }
}

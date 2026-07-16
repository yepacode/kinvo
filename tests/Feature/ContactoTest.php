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

    public function test_invitado_es_redirigido_al_login(): void
    {
        // El directorio es privado: un anónimo va al login, no ve el perfil.
        $profile = $this->perfilPublicado();

        $this->get(route('talento.show', $profile->slug))
            ->assertRedirect(route('login'));
    }

    public function test_profesional_no_puede_ver_perfiles_de_otros(): void
    {
        // El talento no navega el directorio: se le redirige fuera.
        $profile = $this->perfilPublicado();
        $otroProfesional = User::factory()->create();

        $this->actingAs($otroProfesional)
            ->get(route('talento.show', $profile->slug))
            ->assertRedirect($otroProfesional->homeRoute());
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

    public function test_triple_submit_solo_crea_un_contacto(): void
    {
        // Regresión del bug reportado por el cliente: al enviar el form 3 veces
        // (doble-click, F5, retry de red) aparecían 3 contactos idénticos en la
        // bandeja. El controller ahora deduplica dentro de una ventana corta.
        Mail::fake();
        $profile = $this->perfilPublicado();
        $contratante = User::factory()->contratante()->create();

        $payload = [
            'contact_name' => 'Estudio Test',
            'contact_email' => 'test@estudio.mx',
            'message' => 'Hola tengo una vacante los viernes.',
        ];

        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($contratante)
                ->post(route('contacto.store', $profile->slug), $payload)
                ->assertRedirect(route('talento.show', $profile->slug));
        }

        $this->assertSame(1, $profile->contacts()->count());
        Mail::assertQueued(NuevoContacto::class, 1);
    }

    public function test_bandeja_del_profesional_no_expone_email_ni_telefono(): void
    {
        // El cliente pidió que el contacto pase EXCLUSIVAMENTE por Kinvoo:
        // el profesional no debe ver el correo ni el teléfono del contratante.
        $profile = $this->perfilPublicado();
        $contratante = User::factory()->contratante()->create();
        $profile->contacts()->create([
            'contractor_user_id' => $contratante->id,
            'contact_name' => 'Estudio Zen',
            'contact_email' => 'dlopezstreeter@gmail.com',
            'contact_phone' => '5650690049',
            'message' => 'Hola tengo una vacante los viernes.',
            'estado' => EstadoContacto::NoLeido,
        ]);

        $this->actingAs($profile->user)
            ->get('/mis-contactos')
            ->assertOk()
            ->assertSee('Estudio Zen')
            ->assertSee('vacante los viernes')
            ->assertDontSee('dlopezstreeter@gmail.com')
            ->assertDontSee('5650690049');
    }

    public function test_correo_al_profesional_no_expone_email_ni_telefono(): void
    {
        // Regresión: el mailable NuevoContacto revelaba "Correo: ..." y
        // "Puedes responder directamente a X". Ahora hace de puente.
        $profile = $this->perfilPublicado();
        $contratante = User::factory()->contratante()->create();
        $contact = $profile->contacts()->create([
            'contractor_user_id' => $contratante->id,
            'contact_name' => 'Estudio Zen',
            'contact_email' => 'dlopezstreeter@gmail.com',
            'contact_phone' => '5650690049',
            'message' => 'Hola, tenemos vacante.',
            'estado' => EstadoContacto::NoLeido,
        ]);

        $html = (new NuevoContacto($contact, $profile->load('user')))->render();

        $this->assertStringNotContainsString('dlopezstreeter@gmail.com', $html);
        $this->assertStringNotContainsString('5650690049', $html);
    }

    public function test_profesional_marca_me_interesa_y_avisa_a_kinvoo(): void
    {
        \Illuminate\Support\Facades\Notification::fake();

        $profile = $this->perfilPublicado();
        $contratante = User::factory()->contratante()->create();
        $contact = $profile->contacts()->create([
            'contractor_user_id' => $contratante->id,
            'contact_name' => 'Estudio Zen',
            'contact_email' => 'zen@example.com',
            'message' => 'Hola tenemos una vacante.',
            'estado' => EstadoContacto::NoLeido,
        ]);
        $owner = User::factory()->admin()->create();

        $this->actingAs($profile->user)
            ->post(route('professional.contactos.interesado', $contact))
            ->assertRedirect()
            ->assertSessionHas('status', 'interesado-registrado');

        $this->assertNotNull($contact->fresh()->professional_interesado_at);
        \Illuminate\Support\Facades\Notification::assertSentTo(
            $owner,
            \App\Notifications\ProfesionalInteresadoNotification::class
        );
    }

    public function test_marcar_me_interesa_es_idempotente(): void
    {
        \Illuminate\Support\Facades\Notification::fake();

        $profile = $this->perfilPublicado();
        $contratante = User::factory()->contratante()->create();
        $contact = $profile->contacts()->create([
            'contractor_user_id' => $contratante->id,
            'contact_name' => 'Estudio Zen',
            'contact_email' => 'zen@example.com',
            'message' => 'Tenemos una vacante.',
            'estado' => EstadoContacto::NoLeido,
            'professional_interesado_at' => now()->subHour(),
        ]);
        User::factory()->admin()->create();

        $this->actingAs($profile->user)
            ->post(route('professional.contactos.interesado', $contact))
            ->assertRedirect()
            ->assertSessionHas('status', 'ya-interesado');

        \Illuminate\Support\Facades\Notification::assertNothingSent();
    }

    public function test_profesional_no_puede_marcar_contactos_de_otros(): void
    {
        $profileA = $this->perfilPublicado();
        $otroPro = User::factory()->create();
        $contact = $profileA->contacts()->create([
            'contractor_user_id' => User::factory()->contratante()->create()->id,
            'contact_name' => 'Estudio X',
            'contact_email' => 'x@example.com',
            'message' => 'Un mensaje aquí.',
            'estado' => EstadoContacto::NoLeido,
        ]);

        $this->actingAs($otroPro)
            ->post(route('professional.contactos.interesado', $contact))
            ->assertForbidden();

        $this->assertNull($contact->fresh()->professional_interesado_at);
    }

    public function test_bandeja_muestra_el_boton_y_luego_el_chip_interesado(): void
    {
        $profile = $this->perfilPublicado();
        $contratante = User::factory()->contratante()->create();
        $profile->contacts()->create([
            'contractor_user_id' => $contratante->id,
            'contact_name' => 'Estudio Zen',
            'contact_email' => 'zen@example.com',
            'message' => 'Tenemos una vacante para ti.',
            'estado' => EstadoContacto::NoLeido,
        ]);

        // Antes de marcar: se ve el botón.
        $this->actingAs($profile->user)
            ->get('/mis-contactos')
            ->assertOk()
            ->assertSee('Me interesa, conéctame con el estudio');

        // Después de marcar: se ve el chip "Kinvoo está gestionando el puente".
        $profile->contacts()->first()->update(['professional_interesado_at' => now()]);

        $this->actingAs($profile->user)
            ->get('/mis-contactos')
            ->assertOk()
            ->assertDontSee('Me interesa, conéctame con el estudio')
            ->assertSee('Kinvoo está gestionando el puente');
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

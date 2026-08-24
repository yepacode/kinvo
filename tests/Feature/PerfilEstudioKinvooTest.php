<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PerfilEstudioKinvooTest extends TestCase
{
    use RefreshDatabase;

    public function test_formulario_de_estudio_carga_con_campos_nuevos(): void
    {
        $user = User::factory()->contratante()->create();

        $this->actingAs($user)->get('/mi-empresa')
            ->assertOk()
            ->assertSee('Nombre del estudio')
            ->assertSee('Estado (México)')
            ->assertSee('Código Postal')
            ->assertSee('Datos de contacto');
    }

    public function test_estudio_guarda_disciplina_estado_direccion_y_contacto(): void
    {
        Storage::fake('public');
        $user = User::factory()->contratante()->create();

        // 2026-08-06 · petición Marian: TODOS los campos obligatorios excepto
        // multimedia/redes. Este test se escribió antes de ese cambio; se
        // actualiza aquí para incluir description, colonia y logo (required
        // ahora en CompanyProfileController::update).
        $this->actingAs($user)->put('/mi-empresa', [
            'company_name' => 'Gym Norte',
            'disciplines_text' => 'Crossfit, Boxeo',
            'description' => 'Gimnasio de fuerza y acondicionamiento en Monterrey desde 2018.',
            'estado' => 'Nuevo León',
            'address' => 'Av. Constitución 100',
            'colonia' => 'Centro',
            'postal_code' => '64000',
            'contact_name' => 'Luis',
            'contact_phone' => '+52 81 1234 5678',
            'contact_email' => 'luis@gymnorte.mx',
            'media_url' => 'https://youtube.com/watch?v=gym',
            'logo' => UploadedFile::fake()->image('logo.png', 400, 400),
        ])->assertRedirectContains('/estudio/'); // estudio activo → su perfil público (petición Karla)

        $p = $user->companyProfile()->first();
        $this->assertSame('Crossfit, Boxeo', $p->disciplines_text);
        $this->assertSame('Nuevo León', $p->estado);
        $this->assertSame('64000', $p->postal_code);
        $this->assertSame('luis@gymnorte.mx', $p->contact_email);
    }

    public function test_estudio_sube_video_como_multimedia(): void
    {
        Storage::fake('public');
        $user = User::factory()->contratante()->create();

        // Nuevo carrusel: los videos ya no se guardan en `media_path` del
        // perfil, sino como MediaItem polimórfico en `mediaItems()`.
        $this->actingAs($user)->put('/mi-empresa', $this->datosValidosEstudio([
            'company_name' => 'Gym Vídeo',
            'media_files' => [UploadedFile::fake()->create('tour.mp4', 500, 'video/mp4')],
        ]))->assertRedirectContains('/estudio/'); // estudio activo → su perfil público (petición Karla)

        $item = $user->companyProfile()->first()->mediaItems()->first();
        $this->assertNotNull($item);
        $this->assertSame('video', $item->type);
        Storage::disk('public')->assertExists($item->path);
    }

    public function test_estudio_suspendido_no_puede_editar_perfil(): void
    {
        $user = User::factory()->contratante()->create([
            'estado' => \App\Enums\EstadoUsuario::Suspendido,
        ]);

        $this->actingAs($user)->get('/mi-empresa')->assertForbidden();
        $this->actingAs($user)->put('/mi-empresa', ['company_name' => 'Hackeo'])->assertForbidden();
    }

    public function test_estudio_puede_quitar_multimedia_subida(): void
    {
        Storage::fake('public');
        $user = User::factory()->contratante()->create();
        $this->actingAs($user)->put('/mi-empresa', [
            'company_name' => 'Gym Quitar',
            'media_file' => UploadedFile::fake()->image('cover.jpg'),
        ]);
        $viejo = $user->companyProfile()->first()->media_path;

        $this->actingAs($user)->put('/mi-empresa', [
            'company_name' => 'Gym Quitar',
            'remove_media_file' => '1',
        ]);

        $p = $user->companyProfile()->first();
        $this->assertNull($p->media_path);
        $this->assertNull($p->media_type);
        Storage::disk('public')->assertMissing($viejo);
    }

    public function test_editar_empresa_no_rompe_tras_renombrar_el_estudio(): void
    {
        // Regresión del bug crítico: firstOrCreate buscaba por company_name y tras un
        // rename intentaba INSERT → violación de UNIQUE(user_id) → 500 permanente.
        Storage::fake('public');
        $user = User::factory()->contratante()->create();

        $this->actingAs($user)->put('/mi-empresa', $this->datosValidosEstudio([
            'company_name' => 'PowerGym',
        ]))->assertRedirectContains('/estudio/'); // estudio activo → su perfil público (petición Karla)

        // Segunda visita y segundo guardado NO deben dar 500.
        $this->actingAs($user)->get('/mi-empresa')->assertOk();
        $this->actingAs($user)->put('/mi-empresa', $this->datosValidosEstudio([
            'company_name' => 'PowerGym 2',
        ]))->assertRedirectContains('/estudio/'); // estudio activo → su perfil público (petición Karla)

        $this->assertSame(1, $user->companyProfile()->count());
        $this->assertSame('PowerGym 2', $user->companyProfile()->first()->company_name);
    }

    public function test_estado_invalido_es_rechazado(): void
    {
        $user = User::factory()->contratante()->create();

        $this->actingAs($user)->put('/mi-empresa', [
            'company_name' => 'Gym X',
            'estado' => 'California',   // no es estado de México
        ])->assertSessionHasErrors('estado');
    }

    public function test_email_de_contacto_invalido_es_rechazado(): void
    {
        $user = User::factory()->contratante()->create();

        $this->actingAs($user)->put('/mi-empresa', [
            'company_name' => 'Gym X',
            'contact_email' => 'no-es-email',
        ])->assertSessionHasErrors('contact_email');
    }

    public function test_pagina_publica_del_estudio_no_filtra_datos_de_contacto(): void
    {
        $user = User::factory()->contratante()->create(); // activo
        $profile = $user->companyProfile()->create([
            'company_name' => 'Gym Público',
            'disciplines_text' => 'Crossfit, Boxeo',
            'estado' => 'Jalisco',
            'contact_name' => 'Contacto Secreto',
            'contact_phone' => '+52 33 0000 0000',
            'contact_email' => 'privado@gym.mx',
        ]);

        $this->actingAsSocio(); // el perfil del estudio requiere estar logueado
        $this->get(route('estudio.show', $profile->slug))
            ->assertOk()
            ->assertSee('Gym Público')
            ->assertSee('Crossfit')
            ->assertSee('Jalisco')
            ->assertSee('"@context":"https:\/\/schema.org"', false)
            ->assertDontSee('__contextArgs', false)
            ->assertDontSee('Contacto Secreto')
            ->assertDontSee('+52 33 0000 0000')
            ->assertDontSee('privado@gym.mx');
    }

    public function test_direccion_del_estudio_es_opt_in_en_el_perfil_publico(): void
    {
        $user = User::factory()->contratante()->create();
        $profile = $user->companyProfile()->create([
            'company_name' => 'Gym Dirección',
            'estado' => 'Jalisco',
            'address' => 'Calle Secreta 99',
            'postal_code' => '44100',
            'show_address' => false,
        ]);

        $this->actingAsSocio();
        // Por defecto NO se muestra la dirección exacta, solo el estado.
        $this->get(route('estudio.show', $profile->slug))
            ->assertOk()
            ->assertSee('Jalisco')
            ->assertDontSee('Calle Secreta 99');

        // Con el opt-in activado, sí se muestra.
        $profile->update(['show_address' => true]);
        $this->get(route('estudio.show', $profile->slug))
            ->assertSee('Calle Secreta 99');
    }

    public function test_pagina_del_estudio_requiere_login(): void
    {
        // El perfil del estudio ya no es público: un anónimo va al login.
        $user = User::factory()->contratante()->create();
        $profile = $user->companyProfile()->create(['company_name' => 'Gym Privado']);

        $this->get(route('estudio.show', $profile->slug))->assertRedirect(route('login'));
    }

    public function test_estudio_de_usuario_no_activo_da_404(): void
    {
        $user = User::factory()->contratante()->create();
        $user->forceFill(['estado' => \App\Enums\EstadoUsuario::Pendiente])->save();
        $profile = $user->companyProfile()->create(['company_name' => 'Oculto']);

        $this->actingAsSocio();
        $this->get(route('estudio.show', $profile->slug))->assertNotFound();
    }

    public function test_buscador_ya_no_muestra_filtro_de_certificacion(): void
    {
        $this->actingAsSocio();
        $this->get(route('talento.index'))
            ->assertOk()
            ->assertDontSee('name="certification_id"', false);
    }
}

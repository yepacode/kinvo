<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $user = User::factory()->contratante()->create();

        $this->actingAs($user)->put('/mi-empresa', [
            'company_name' => 'Gym Norte',
            'disciplines_text' => 'Crossfit, Boxeo',
            'estado' => 'Nuevo León',
            'address' => 'Av. Constitución 100',
            'postal_code' => '64000',
            'contact_name' => 'Luis',
            'contact_phone' => '+52 81 1234 5678',
            'contact_email' => 'luis@gymnorte.mx',
            'media_url' => 'https://youtube.com/watch?v=gym',
        ])->assertRedirect(route('company.profile.edit'));

        $p = $user->companyProfile()->first();
        $this->assertSame('Crossfit, Boxeo', $p->disciplines_text);
        $this->assertSame('Nuevo León', $p->estado);
        $this->assertSame('64000', $p->postal_code);
        $this->assertSame('luis@gymnorte.mx', $p->contact_email);
    }

    public function test_editar_empresa_no_rompe_tras_renombrar_el_estudio(): void
    {
        // Regresión del bug crítico: firstOrCreate buscaba por company_name y tras un
        // rename intentaba INSERT → violación de UNIQUE(user_id) → 500 permanente.
        $user = User::factory()->contratante()->create();

        $this->actingAs($user)->put('/mi-empresa', ['company_name' => 'PowerGym'])
            ->assertRedirect(route('company.profile.edit'));

        // Segunda visita y segundo guardado NO deben dar 500.
        $this->actingAs($user)->get('/mi-empresa')->assertOk();
        $this->actingAs($user)->put('/mi-empresa', ['company_name' => 'PowerGym 2'])
            ->assertRedirect(route('company.profile.edit'));

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

    public function test_estudio_de_usuario_no_activo_da_404(): void
    {
        $user = User::factory()->contratante()->create();
        $user->forceFill(['estado' => \App\Enums\EstadoUsuario::Pendiente])->save();
        $profile = $user->companyProfile()->create(['company_name' => 'Oculto']);

        $this->get(route('estudio.show', $profile->slug))->assertNotFound();
    }

    public function test_buscador_ya_no_muestra_filtro_de_certificacion(): void
    {
        $this->get(route('talento.index'))
            ->assertOk()
            ->assertDontSee('name="certification_id"', false);
    }
}

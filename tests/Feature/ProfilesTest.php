<?php

namespace Tests\Feature;

use App\Models\Discipline;
use App\Models\ProfessionalProfile;
use App\Models\User;
use Database\Seeders\TaxonomiaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfilesTest extends TestCase
{
    use RefreshDatabase;

    public function test_al_registrar_profesional_se_crea_su_perfil(): void
    {
        $this->post('/register', [
            'name' => 'Coach Ana',
            'tipo' => 'professional',
            'email' => 'ana@example.com',
            'password' => 'Str0ng!Pass',
            'password_confirmation' => 'Str0ng!Pass',
            'acepta_legales' => '1',
        ]);

        $user = User::where('email', 'ana@example.com')->first();
        $this->assertNotNull($user->professionalProfile);
        $this->assertNull($user->companyProfile);
    }

    public function test_al_registrar_contratante_se_crea_su_empresa(): void
    {
        $this->post('/register', [
            'name' => 'Gimnasio X',
            'tipo' => 'contractor',
            'email' => 'gym@example.com',
            'password' => 'Str0ng!Pass',
            'password_confirmation' => 'Str0ng!Pass',
            'acepta_legales' => '1',
        ]);

        $user = User::where('email', 'gym@example.com')->first();
        $this->assertNotNull($user->companyProfile);
        $this->assertNull($user->professionalProfile);
    }

    public function test_profesional_ve_su_formulario_de_perfil(): void
    {
        $user = User::factory()->create(); // Professional activo

        $this->actingAs($user)->get('/mi-perfil')->assertStatus(200);
    }

    public function test_contratante_no_accede_al_perfil_profesional(): void
    {
        $user = User::factory()->contratante()->create();

        $this->actingAs($user)->get('/mi-perfil')->assertStatus(403);
    }

    public function test_profesional_actualiza_su_perfil(): void
    {
        Storage::fake('public');
        $this->seed(TaxonomiaSeeder::class);
        $disc = Discipline::query()->take(2)->pluck('id')->all();

        $user = User::factory()->create();

        $this->actingAs($user)->put('/mi-perfil', $this->datosValidosProfesional([
            'headline' => 'Coach de fuerza',
            'bio' => 'Especialista en powerlifting olímpico con 10 años de carrera.',
            'years_experience' => 7,
            'modalidad' => 'presencial',
            'disciplines' => $disc,
            'photo' => UploadedFile::fake()->image('foto.jpg'),
        ]))->assertRedirect(route('professional.enviado'));

        $profile = $user->professionalProfile()->first();
        $this->assertSame('Coach de fuerza', $profile->headline);
        // El usuario NO auto-publica: is_published sigue false hasta que el admin apruebe.
        $this->assertFalse($profile->is_published);
        $this->assertCount(2, $profile->disciplines);
        $this->assertNotNull($profile->photo_path);
        Storage::disk('public')->assertExists($profile->photo_path);
    }

    public function test_perfil_publicado_es_visible_para_socio(): void
    {
        $user = User::factory()->create(['name' => 'Ana Torres']);
        $profile = $user->professionalProfile()->create([
            'headline' => 'Coach de yoga',
            'is_published' => true,
        ]);

        $this->actingAsSocio(); // directorio privado: estudio con membresía
        $this->get(route('talento.show', $profile->slug))
            ->assertStatus(200)
            ->assertSee('Ana Torres')
            ->assertSee('Coach de yoga');
    }

    public function test_perfil_no_publicado_da_404(): void
    {
        $user = User::factory()->create();
        $profile = $user->professionalProfile()->create([
            'headline' => 'Oculto',
            'is_published' => false,
        ]);

        $this->actingAsSocio();
        $this->get(route('talento.show', $profile->slug))->assertStatus(404);
    }

    public function test_contratante_actualiza_su_empresa(): void
    {
        Storage::fake('public');
        $user = User::factory()->contratante()->create();

        $response = $this->actingAs($user)->put('/mi-empresa', $this->datosValidosEstudio([
            'company_name' => 'Estudio Zen',
            'disciplines_text' => 'Yoga, Pilates',
            'estado' => 'Jalisco',
            'postal_code' => '44100',
            'contact_name' => 'Ana',
            'contact_email' => 'ana@zen.example.com',
            'website' => 'https://zen.example.com',
        ]));

        $empresa = $user->companyProfile()->first();
        // Estudio activo → al guardar va a SU perfil público, con flash de éxito (petición Karla).
        $response->assertRedirect(route('estudio.show', $empresa))
            ->assertSessionHas('success');
        $this->assertSame('Estudio Zen', $empresa->company_name);
        $this->assertSame('Jalisco', $empresa->estado);
        $this->assertSame('Yoga, Pilates', $empresa->disciplines_text);
    }

    /**
     * SEGURIDAD (auditoría ago-2026): el endpoint público del profesional NO
     * permite auto-publicarse ni auto-verificarse aunque los campos estén en
     * $fillable — el controller usa $request->validate() con allow-list y
     * los ignora del body. Test blindado contra regresión (si mañana alguien
     * hace ->update($request->all()) esto rompe).
     */
    public function test_profesional_no_puede_autopublicarse_ni_autoverificarse(): void
    {
        $user = User::factory()->create();
        $user->forceFill([
            'nivel' => \App\Enums\RolUsuario::Professional,
            'estado' => \App\Enums\EstadoUsuario::Activo,
        ])->save();
        $user->professionalProfile()->firstOrCreate([], ['headline' => 'x']);

        $this->actingAs($user)->put('/mi-perfil', [
            'headline' => 'Nuevo headline',
            'is_published' => true,   // intento de bypass
            'is_verified' => true,    // intento de bypass
            'verified_at' => now()->toDateTimeString(),
        ]);

        $p = $user->professionalProfile()->first();
        $this->assertFalse((bool) $p->is_published, 'is_published NO debe cambiarse desde el endpoint público');
        $this->assertFalse((bool) $p->is_verified, 'is_verified NO debe cambiarse desde el endpoint público');
        $this->assertNull($p->verified_at, 'verified_at NO debe cambiarse desde el endpoint público');
    }
}

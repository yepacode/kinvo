<?php

namespace Tests\Feature;

use App\Enums\EstadoUsuario;
use App\Enums\RolUsuario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PerfilProfesionalKinvooTest extends TestCase
{
    use RefreshDatabase;

    public function test_profesional_guarda_disponibilidad_idiomas_certificaciones_y_multimedia(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put('/mi-perfil', [
            'headline' => 'Coach de yoga',
            'birthdate' => '1995-05-20',
            'availability' => ['lun_am', 'lun_pm', 'fds_am'],
            'languages' => ['es', 'en'],
            'certifications_text' => 'RYT-200, Instructora de Pilates',
            'media_url' => 'https://youtube.com/watch?v=abc',
        ])->assertRedirect(route('professional.enviado'));

        $profile = $user->professionalProfile()->first();
        $this->assertSame(['lun_am', 'lun_pm', 'fds_am'], $profile->availability);
        $this->assertSame(['es', 'en'], $profile->languages);
        $this->assertSame('RYT-200, Instructora de Pilates', $profile->certifications_text);
        $this->assertSame('https://youtube.com/watch?v=abc', $profile->media_url);
        $this->assertSame('1995-05-20', $profile->birthdate->format('Y-m-d'));
    }

    public function test_profesional_guarda_su_nombre_completo(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->put('/mi-perfil', ['full_name' => 'María Fernanda López García'])
            ->assertRedirect(route('professional.enviado'));

        $this->assertSame('María Fernanda López García', $user->professionalProfile()->first()->full_name);
    }

    public function test_menor_de_edad_es_rechazado(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put('/mi-perfil', [
            'headline' => 'Menor',
            'birthdate' => now()->subYears(15)->format('Y-m-d'),
        ])->assertSessionHasErrors('birthdate');
    }

    public function test_disponibilidad_invalida_es_rechazada(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put('/mi-perfil', [
            'availability' => ['dia_invalido'],
        ])->assertSessionHasErrors('availability.0');
    }

    public function test_adjunto_de_certificacion_se_guarda_en_disco_privado(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $this->actingAs($user)->put('/mi-perfil', [
            'certification_file' => UploadedFile::fake()->create('cert.pdf', 200, 'application/pdf'),
        ]);

        $profile = $user->professionalProfile()->first();
        $this->assertNotNull($profile->certification_file_path);
        Storage::disk('local')->assertExists($profile->certification_file_path);
    }

    public function test_profesional_puede_eliminar_foto_y_adjunto(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $user = User::factory()->create();

        $this->actingAs($user)->put('/mi-perfil', [
            'photo' => UploadedFile::fake()->image('f.jpg'),
            'certification_file' => UploadedFile::fake()->create('c.pdf', 100, 'application/pdf'),
        ]);
        $profile = $user->professionalProfile()->first();
        $oldPhoto = $profile->photo_path;
        $oldCert = $profile->certification_file_path;
        $this->assertNotNull($oldPhoto);
        $this->assertNotNull($oldCert);

        $this->actingAs($user)->put('/mi-perfil', [
            'remove_photo' => '1',
            'remove_certification_file' => '1',
        ]);

        $profile->refresh();
        $this->assertNull($profile->photo_path);
        $this->assertNull($profile->certification_file_path);
        Storage::disk('public')->assertMissing($oldPhoto);
        Storage::disk('local')->assertMissing($oldCert);
    }

    public function test_subir_foto_nueva_y_marcar_eliminar_conserva_la_nueva(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $this->actingAs($user)->put('/mi-perfil', ['photo' => UploadedFile::fake()->image('a.jpg')]);

        // Misma petición: sube una foto nueva Y marca eliminar → debe ganar la nueva.
        $this->actingAs($user)->put('/mi-perfil', [
            'photo' => UploadedFile::fake()->image('b.jpg'),
            'remove_photo' => '1',
        ]);

        $profile = $user->professionalProfile()->first();
        $this->assertNotNull($profile->photo_path);
        Storage::disk('public')->assertExists($profile->photo_path);
    }

    public function test_profesional_sube_imagen_como_multimedia(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)->put('/mi-perfil', [
            'media_file' => UploadedFile::fake()->image('reel.jpg'),
        ]);

        $profile = $user->professionalProfile()->first();
        $this->assertNotNull($profile->media_path);
        $this->assertSame('image', $profile->media_type);
        Storage::disk('public')->assertExists($profile->media_path);
    }

    public function test_profesional_sube_video_como_multimedia(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)->put('/mi-perfil', [
            'media_file' => UploadedFile::fake()->create('reel.mp4', 500, 'video/mp4'),
        ]);

        $profile = $user->professionalProfile()->first();
        $this->assertNotNull($profile->media_path);
        $this->assertSame('video', $profile->media_type);
        Storage::disk('public')->assertExists($profile->media_path);
    }

    public function test_profesional_puede_quitar_multimedia_subida(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $this->actingAs($user)->put('/mi-perfil', [
            'media_file' => UploadedFile::fake()->image('reel.jpg'),
        ]);
        $viejo = $user->professionalProfile()->first()->media_path;

        $this->actingAs($user)->put('/mi-perfil', [
            'remove_media_file' => '1',
        ]);

        $profile = $user->professionalProfile()->first();
        $this->assertNull($profile->media_path);
        $this->assertNull($profile->media_type);
        Storage::disk('public')->assertMissing($viejo);
    }

    public function test_profesional_suspendido_no_puede_editar_perfil(): void
    {
        $user = User::factory()->create(['estado' => EstadoUsuario::Suspendido]);

        $this->actingAs($user)->get('/mi-perfil')->assertForbidden();
        $this->actingAs($user)->put('/mi-perfil', ['headline' => 'Hackeo'])->assertForbidden();
    }

    public function test_profesional_pendiente_si_puede_editar_perfil(): void
    {
        $user = User::factory()->create(['estado' => EstadoUsuario::Pendiente]);

        $this->actingAs($user)->get('/mi-perfil')->assertOk();
        $this->actingAs($user)->put('/mi-perfil', ['headline' => 'Coach'])
            ->assertRedirect(route('professional.enviado'));
    }

    public function test_multimedia_pesada_es_rechazada(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put('/mi-perfil', [
            'media_file' => UploadedFile::fake()->create('big.mp4', 30000, 'video/mp4'),
        ])->assertSessionHasErrors('media_file');
    }

    public function test_solo_admin_descarga_el_adjunto(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $this->actingAs($user)->put('/mi-perfil', [
            'certification_file' => UploadedFile::fake()->create('cert.pdf', 100, 'application/pdf'),
        ]);
        $profile = $user->professionalProfile()->first();

        // Un no-admin no puede descargar.
        $this->actingAs($user)
            ->get(route('admin.certificacion', $profile))
            ->assertStatus(403);

        // El admin sí.
        $admin = User::factory()->create([
            'nivel' => RolUsuario::Admin,
            'estado' => EstadoUsuario::Activo,
        ]);
        $this->actingAs($admin)
            ->get(route('admin.certificacion', $profile))
            ->assertOk();
    }

    public function test_profesional_ve_su_bandeja_de_contactos_y_se_marcan_leidos(): void
    {
        $pro = $this->actingAsProfesional();
        $contratante = User::factory()->contratante()->create();
        $profile = $pro->professionalProfile()->firstOrCreate(['is_published' => true]);
        $profile->contacts()->create([
            'contractor_user_id' => $contratante->id,
            'contact_name' => 'Estudio Zen',
            'contact_email' => 'zen@example.com',
            'message' => 'Nos encantaría trabajar contigo pronto.',
            'estado' => \App\Enums\EstadoContacto::NoLeido,
        ]);

        $this->actingAs($pro)->get(route('professional.contactos'))
            ->assertOk()
            ->assertSee('Estudio Zen')
            ->assertSee('Nos encantaría trabajar contigo');

        // Al visitarla, los no leídos quedan marcados como leídos.
        $this->assertSame(0, $profile->contacts()->where('estado', \App\Enums\EstadoContacto::NoLeido->value)->count());
    }

    private function actingAsProfesional(): User
    {
        return User::factory()->create(); // el factory por defecto crea Profesional activo
    }

    public function test_telefono_con_letras_es_rechazado(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->put('/mi-perfil', ['phone' => 'llámame ya'])
            ->assertSessionHasErrors('phone');
    }

    public function test_telefono_valido_es_aceptado(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->put('/mi-perfil', ['phone' => '+52 55 1234 5678'])
            ->assertSessionHasNoErrors();
    }

    public function test_datos_de_contacto_no_aparecen_en_el_perfil_publico(): void
    {
        $user = User::factory()->create(['name' => 'Ana Coach']);
        $profile = $user->professionalProfile()->create([
            'headline' => 'Coach',
            'full_name' => 'Ana Patricia Legal Apellido',
            'phone' => '+52 55 1234 5678',
            'socials' => ['instagram' => '@ana', 'tiktok' => '@anatok'],
            'languages' => ['es'],
            'availability' => ['lun_am'],
            'is_published' => true,
        ]);

        $this->actingAsSocio();
        $this->get(route('talento.show', $profile->slug))
            ->assertOk()
            ->assertSee('Ana Coach')
            ->assertDontSee('Ana Patricia Legal Apellido') // el nombre legal es privado
            ->assertDontSee('+52 55 1234 5678')
            ->assertDontSee('@anatok')
            ->assertSee('Disponibilidad');
    }
}

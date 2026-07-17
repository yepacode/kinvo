<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', 'cuenta-eliminada');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_login_muestra_confirmacion_de_cuenta_eliminada(): void
    {
        $this->get(route('login'))->assertOk();

        $this->withSession(['status' => 'cuenta-eliminada'])
            ->get(route('login'))
            ->assertOk()
            ->assertSee('Tu cuenta fue eliminada');
    }

    public function test_admin_no_puede_autoeliminarse(): void
    {
        $admin = User::factory()->admin()->create();

        $this
            ->actingAs($admin)
            ->delete('/profile', ['password' => 'password'])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('status', 'admin-no-se-elimina');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_admin_no_ve_la_seccion_de_eliminar_cuenta(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/profile')
            ->assertOk()
            ->assertDontSee('Eliminar cuenta');
    }

    public function test_eliminar_cuenta_limpia_archivos_y_datos_relacionados(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        \Illuminate\Support\Facades\Storage::fake('local');

        $user = User::factory()->create();
        // Sube foto y adjunto para tener rastro en disco.
        $this->actingAs($user)->put('/mi-perfil', [
            'photo' => \Illuminate\Http\UploadedFile::fake()->image('f.jpg'),
            'certification_file' => \Illuminate\Http\UploadedFile::fake()->create('c.pdf', 100, 'application/pdf'),
        ]);
        $profile = $user->professionalProfile()->first();
        $photo = $profile->photo_path;
        $cert = $profile->certification_file_path;
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($photo);
        \Illuminate\Support\Facades\Storage::disk('local')->assertExists($cert);

        // Crea una notificación in-app para probar limpieza polimórfica.
        $user->notify(new \App\Notifications\CuentaAprobadaNotification());
        $this->assertSame(1, \Illuminate\Support\Facades\DB::table('notifications')
            ->where('notifiable_id', $user->id)->count());

        $this->actingAs($user)
            ->delete('/profile', ['password' => 'password'])
            ->assertRedirect(route('login'));

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        \Illuminate\Support\Facades\Storage::disk('public')->assertMissing($photo);
        \Illuminate\Support\Facades\Storage::disk('local')->assertMissing($cert);
        $this->assertSame(0, \Illuminate\Support\Facades\DB::table('notifications')
            ->where('notifiable_id', $user->id)->count());
    }

    public function test_usuario_con_sesion_viva_cuya_cuenta_fue_borrada_ve_aviso_claro(): void
    {
        // Escenario: el admin (u otro proceso) elimina al user mientras su sesión
        // sigue viva. En el siguiente click, el middleware DetectDeletedUser lo
        // debe llevar a /login con un status legible en vez de un logout mudo.
        $user = User::factory()->create();

        $this->actingAs($user);
        $user->deleteConLimpieza();

        $this->get('/dashboard')
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', 'admin-elimino-cuenta');

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Tu cuenta ya no está activa.');
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }
}

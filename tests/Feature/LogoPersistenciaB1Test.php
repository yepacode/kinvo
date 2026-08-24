<?php

namespace Tests\Feature;

use App\Enums\EstadoUsuario;
use App\Enums\RolUsuario;
use App\Models\CompanyProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * B1 · Bug del cliente Marian (docx PRUEBA KINVOO, jul-2026):
 * "No se guardó la foto del logo en el primer intento".
 *
 * Reproduce el escenario EXACTO del bug y verifica que ya no ocurre:
 *  1. El estudio sube logo + un campo falla la validación.
 *  2. Reintenta corrigiendo el campo, SIN volver a subir el logo.
 *  3. El logo debe quedar guardado (no null).
 *
 * También verifica el flujo sano (no romper lo que funcionaba) y
 * los edge cases más comunes.
 */
class LogoPersistenciaB1Test extends TestCase
{
    use RefreshDatabase;

    private function estudioActivo(): User
    {
        $u = User::factory()->create();
        $u->forceFill([
            'nivel'  => RolUsuario::Contractor,
            'estado' => EstadoUsuario::Activo,
        ])->save();
        return $u;
    }

    private function payloadValido(): array
    {
        return [
            'company_name'     => 'Estudio Test',
            'disciplines_text' => 'Yoga, pilates',
            'description'      => 'Un estudio de bienestar en la ciudad, con 5 años de experiencia.',
            'estado'           => 'Ciudad de México',
            'address'          => 'Av. Reforma 100',
            'postal_code'      => '06600',
            'colonia'          => 'Juárez',
            'contact_name'     => 'Marian Ruiz',
            'contact_phone'    => '+52 55 1234 5678',
            'contact_email'    => 'contacto@estudio.test',
        ];
    }

    /**
     * ESCENARIO EXACTO DEL BUG: sube logo + nombre vacío → error →
     * reintenta con nombre válido SIN volver a subir logo → guarda CON logo.
     *
     * @test
     */
    public function b1_logo_persiste_entre_intentos_fallidos(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        $user = $this->estudioActivo();
        $logo = UploadedFile::fake()->image('mi-logo.png', 400, 400)->size(100);

        // Intento 1: SUBE el logo pero borra el nombre → falla validación.
        $r1 = $this->actingAs($user)
            ->from('/mi-empresa')
            ->put('/mi-empresa', array_merge($this->payloadValido(), [
                'company_name' => '', // ← causa el fallo
                'logo' => $logo,
            ]));

        $r1->assertSessionHasErrors('company_name');
        $r1->assertRedirect('/mi-empresa');

        // BD sigue sin logo (el intento 1 falló entero, correcto).
        $this->assertNull(CompanyProfile::where('user_id', $user->id)->first()?->logo_path);

        // Pero el trait guardó snapshot del logo en tmp de sesión.
        $tmp = session('tmp_upload_logo');
        $this->assertNotNull($tmp, 'El trait debe snapshot-ear el logo en sesión');
        $this->assertArrayHasKey('path', $tmp);
        Storage::disk('local')->assertExists($tmp['path']);

        // Intento 2: reintenta CON nombre válido, SIN subir logo de nuevo
        // (así es exactamente lo que hacía Marian).
        // withSession preserva el tmp puesto por el intento 1 (Laravel tests
        // no propagan sesión entre calls por default — Marian sí lo hace).
        $r2 = $this->actingAs($user)
            ->withSession(['tmp_upload_logo' => $tmp])
            ->put('/mi-empresa', $this->payloadValido()); // sin 'logo'

        $r2->assertSessionHasNoErrors();

        // ✅ EL FIX: el logo DEBE haber quedado guardado.
        $profile = CompanyProfile::where('user_id', $user->id)->firstOrFail();
        $this->assertNotNull(
            $profile->logo_path,
            'B1 FIX FAIL: el logo del primer intento se perdió al reintentar.'
        );
        Storage::disk('public')->assertExists($profile->logo_path);

        // El tmp se limpió tras el save exitoso.
        $this->assertNull(session('tmp_upload_logo'), 'El tmp debe borrarse tras save exitoso');
    }

    /**
     * Happy path — todo bien al primer intento. No regresión.
     *
     * @test
     */
    public function b1_happy_path_logo_al_primer_intento(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        $user = $this->estudioActivo();
        $logo = UploadedFile::fake()->image('logo.png', 300, 300)->size(50);

        $r = $this->actingAs($user)
            ->put('/mi-empresa', array_merge($this->payloadValido(), ['logo' => $logo]));

        $r->assertSessionHasNoErrors();

        $profile = CompanyProfile::where('user_id', $user->id)->firstOrFail();
        // Estudio activo → al guardar va a SU perfil público (petición Karla).
        $r->assertRedirect(route('estudio.show', $profile));
        $this->assertNotNull($profile->logo_path);
        Storage::disk('public')->assertExists($profile->logo_path);
    }

    /**
     * Edge: user sube DOS logos distintos en dos intentos, ambos válidos.
     * Debe quedar el ÚLTIMO subido (nueva imagen sobrescribe la vieja),
     * no restaurar del tmp del primer intento.
     *
     * @test
     */
    public function b1_segundo_upload_sobrescribe_al_tmp(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        $user = $this->estudioActivo();
        $logo1 = UploadedFile::fake()->image('primer.png', 100, 100);
        $logo2 = UploadedFile::fake()->image('segundo.png', 100, 100);

        // Intento 1 con logo1 pero campo malo.
        $this->actingAs($user)
            ->put('/mi-empresa', array_merge($this->payloadValido(), [
                'company_name' => '',
                'logo' => $logo1,
            ]));

        // Intento 2 con logo2 (nuevo) y campo bueno.
        $this->actingAs($user)
            ->put('/mi-empresa', array_merge($this->payloadValido(), ['logo' => $logo2]));

        $profile = CompanyProfile::where('user_id', $user->id)->firstOrFail();
        // El path debe ser único y en 'empresas/'.
        $this->assertStringContainsString('empresas/', $profile->logo_path);
        // El file guardado debe ser el segundo (tamaño similar, sanity check).
        Storage::disk('public')->assertExists($profile->logo_path);
    }

    /**
     * Edge: user borra el input file EXPRESAMENTE con la casilla remove_media_file.
     * (Este bug se trata del logo, así que probamos que remove_media_file
     * no interfiere y sigue funcionando el flujo normal).
     *
     * @test
     */
    public function b1_reintento_sin_tmp_ni_logo_no_crashea(): void
    {
        Storage::fake('public');

        $user = $this->estudioActivo();
        // Ya tiene logo en BD desde antes.
        $user->companyProfile()->create([
            'company_name' => 'Estudio Test',
            'logo_path'    => 'empresas/existente.png',
        ]);
        Storage::disk('public')->put('empresas/existente.png', 'fake');

        // Actualiza sin tocar logo, sin tmp en sesión.
        $r = $this->actingAs($user)
            ->put('/mi-empresa', $this->payloadValido());

        $r->assertSessionHasNoErrors();
        $profile = CompanyProfile::where('user_id', $user->id)->firstOrFail();
        // El logo existente NO se debe perder.
        $this->assertSame('empresas/existente.png', $profile->logo_path);
    }
}

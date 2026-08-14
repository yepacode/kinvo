<?php

namespace Tests;

use App\Models\Discipline;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\UploadedFile;

abstract class TestCase extends BaseTestCase
{
    /**
     * Autentica a un contratista con membresía vigente (quien SÍ puede ver el
     * directorio y los perfiles de talento) y lo devuelve.
     */
    protected function actingAsSocio(): User
    {
        $socio = User::factory()->contratante()->create(); // membresía activa por defecto
        $this->actingAs($socio);

        return $socio;
    }

    /**
     * Payload válido para PUT /mi-empresa.
     *
     * 2026-08-06 · Petición clienta (Marian): todos los campos obligatorios
     * excepto multimedia y redes/web. Este helper concentra los valores mínimos
     * para que la validación pase; cada test pasa como override sólo lo que le
     * interesa comprobar.
     */
    protected function datosValidosEstudio(array $overrides = []): array
    {
        return array_merge([
            'company_name' => 'Estudio Test',
            'disciplines_text' => 'Yoga, Pilates',
            'description' => 'Un estudio de bienestar en el centro de la ciudad, con clases todos los días.',
            'estado' => 'Nuevo León',
            'address' => 'Av. Test 100',
            'colonia' => 'Centro',
            'postal_code' => '64000',
            'contact_name' => 'Ana Ruiz',
            'contact_phone' => '+52 81 1234 5678',
            'contact_email' => 'contacto@test.mx',
            'logo' => UploadedFile::fake()->image('logo.png', 400, 400),
        ], $overrides);
    }

    /**
     * Payload válido para PUT /mi-perfil.
     *
     * Mismo motivo que datosValidosEstudio(): tras el cambio 2026-08-06 casi
     * todos los campos son required; el helper garantiza que la validación
     * pase para tests que quieren probar OTRO comportamiento (subida de foto,
     * eliminado, redirect a "enviado", notificación al admin, etc.).
     *
     * Crea (idempotente) una Location y una Discipline activas para que las
     * reglas `Rule::exists(...)` pasen sin depender del TaxonomiaSeeder.
     */
    protected function datosValidosProfesional(array $overrides = []): array
    {
        $location = Location::firstOrCreate(
            ['ciudad' => 'Ciudad de Prueba', 'region' => 'Jalisco'],
            ['pais' => 'México', 'activo' => true],
        );

        $discipline = Discipline::firstOrCreate(
            ['slug' => 'yoga-prueba'],
            ['nombre' => 'Yoga (prueba)', 'nombre_en' => 'Yoga', 'activo' => true],
        );

        return array_merge([
            'full_name' => 'Coach de Prueba',
            'headline' => 'Coach de Yoga',
            'birthdate' => '1995-05-20',
            'bio' => 'Coach certificada con años de experiencia acompañando personas.',
            'years_experience' => 5,
            'modalidad' => 'presencial',
            'availability' => ['lun_am', 'mie_pm'],
            'languages' => ['es'],
            'location_id' => $location->id,
            'colonia' => 'Centro',
            'phone' => '+52 55 1234 5678',
            'certifications_text' => 'RYT-200',
            'disciplines' => [$discipline->id],
            'photo' => UploadedFile::fake()->image('photo.jpg', 400, 400),
        ], $overrides);
    }
}

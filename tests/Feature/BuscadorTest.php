<?php

namespace Tests\Feature;

use App\Models\Discipline;
use App\Models\Location;
use App\Models\ProfessionalProfile;
use App\Models\User;
use Database\Seeders\TaxonomiaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuscadorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TaxonomiaSeeder::class);
        // El directorio es privado: se navega como estudio con membresía vigente.
        $this->actingAsSocio();
    }

    private function perfil(string $nombre, array $attrs = [], array $disciplinas = []): ProfessionalProfile
    {
        $user = User::factory()->create(['name' => $nombre]);
        $profile = $user->professionalProfile()->create(array_merge([
            'is_published' => true,
        ], $attrs));
        if ($disciplinas) {
            $profile->disciplines()->sync(Discipline::whereIn('slug', $disciplinas)->pluck('id'));
        }
        return $profile;
    }

    public function test_el_buscador_carga(): void
    {
        $this->get(route('talento.index'))->assertStatus(200);
    }

    public function test_solo_muestra_perfiles_publicados(): void
    {
        $this->perfil('Publicada', ['is_published' => true, 'headline' => 'Coach visible']);
        $this->perfil('Oculta', ['is_published' => false, 'headline' => 'Coach oculta']);

        $this->get(route('talento.index'))
            ->assertSee('Publicada')
            ->assertDontSee('Oculta');
    }

    public function test_filtra_por_disciplina(): void
    {
        $this->perfil('Yogui', ['headline' => 'Instructor'], ['yoga']);
        $this->perfil('Crossfitero', ['headline' => 'Coach'], ['crossfit']);

        $yoga = Discipline::where('slug', 'yoga')->first();

        $this->get(route('talento.index', ['discipline_id' => $yoga->id]))
            ->assertSee('Yogui')
            ->assertDontSee('Crossfitero');
    }

    public function test_filtra_por_ubicacion(): void
    {
        $gdl = Location::where('ciudad', 'Guadalajara')->first();
        $cdmx = Location::where('ciudad', 'Ciudad de México')->first();

        $this->perfil('Tapatia', ['location_id' => $gdl->id]);
        $this->perfil('Chilanga', ['location_id' => $cdmx->id]);

        $this->get(route('talento.index', ['location_id' => $gdl->id]))
            ->assertSee('Tapatia')
            ->assertDontSee('Chilanga');
    }

    public function test_filtra_por_modalidad(): void
    {
        // Nombres que no colisionan con las etiquetas del dropdown de modalidad.
        $this->perfil('Mariana Vega', ['modalidad' => 'online']);
        $this->perfil('Rodrigo Salas', ['modalidad' => 'presencial']);

        $this->get(route('talento.index', ['modalidad' => 'online']))
            ->assertSee('Mariana Vega')
            ->assertDontSee('Rodrigo Salas');
    }

    public function test_busca_por_nombre(): void
    {
        $this->perfil('Fernanda Ruiz', ['headline' => 'Coach']);
        $this->perfil('Otro Nombre', ['headline' => 'Coach']);

        $this->get(route('talento.index', ['q' => 'Fernanda']))
            ->assertSee('Fernanda Ruiz')
            ->assertDontSee('Otro Nombre');
    }
}

<?php

namespace Tests\Feature;

use App\Filament\Resources\PlanResource\Pages\CreatePlan;
use App\Filament\Resources\PlanResource\Pages\ListPlans;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PlanPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_ve_la_lista_de_planes(): void
    {
        $owner = User::factory()->admin()->create();
        Plan::create(['nombre' => 'Esencial', 'audiencia' => 'individual']);

        $this->actingAs($owner);
        Livewire::test(ListPlans::class)
            ->assertOk()
            ->assertSee('Esencial');
    }

    public function test_owner_crea_un_plan_y_se_genera_el_slug(): void
    {
        $owner = User::factory()->admin()->create();

        $this->actingAs($owner);
        Livewire::test(CreatePlan::class)
            ->fillForm([
                'nombre' => 'Elite',
                'audiencia' => 'estudio',
                'periodo' => 'mensual',
                'moneda' => 'MXN',
                'precio' => 999,
                'activo' => true,
                'orden' => 9,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('plans', ['nombre' => 'Elite', 'audiencia' => 'estudio']);
        $this->assertSame('estudio-elite', Plan::where('nombre', 'Elite')->first()->slug);
    }

    public function test_beneficios_se_guardan_como_lista(): void
    {
        $plan = Plan::create([
            'nombre' => 'Pro',
            'audiencia' => 'individual',
            'beneficios' => ['Acceso al directorio', 'Soporte prioritario'],
        ]);

        $this->assertSame(['Acceso al directorio', 'Soporte prioritario'], $plan->fresh()->beneficios);
    }
}

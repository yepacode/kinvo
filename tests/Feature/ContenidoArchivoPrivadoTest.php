<?php

namespace Tests\Feature;

use App\Enums\EstadoUsuario;
use App\Enums\RolUsuario;
use App\Models\ContentItem;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContenidoArchivoPrivadoTest extends TestCase
{
    use RefreshDatabase;

    private function plan(): Plan
    {
        return Plan::create([
            'nombre' => 'Premium', 'audiencia' => 'individual',
            'precio' => 199, 'moneda' => 'MXN', 'periodo' => 'mensual',
        ]);
    }

    private function coach(?Plan $plan): User
    {
        $u = User::factory()->create();
        $u->forceFill([
            'nivel' => RolUsuario::Professional,
            'estado' => EstadoUsuario::Activo,
            'membership_plan_id' => $plan?->id,
            'membership_expires_at' => $plan ? now()->addMonth() : null,
        ])->save();

        return $u;
    }

    private function videoPrivado(Plan $plan): ContentItem
    {
        Storage::fake('local');
        $path = UploadedFile::fake()->create('clase.mp4', 200, 'video/mp4')->store('contenido', 'local');

        return ContentItem::create([
            'title' => 'Clase premium', 'type' => ContentItem::TYPE_VIDEO,
            'file_path' => $path, 'file_disk' => 'local',
            'gate_plan_id' => $plan->id, 'access_level' => 2,
            'is_published' => true, 'published_at' => now(),
        ]);
    }

    public function test_miembro_del_plan_accede_al_archivo_privado(): void
    {
        $plan = $this->plan();
        $item = $this->videoPrivado($plan);

        $this->actingAs($this->coach($plan))
            ->get(route('contenido.archivo', $item))
            ->assertOk();
    }

    public function test_usuario_sin_el_plan_recibe_403_en_el_archivo(): void
    {
        $plan = $this->plan();
        $item = $this->videoPrivado($plan);

        // Coach activo pero SIN ese plan → el link directo no le sirve.
        $this->actingAs($this->coach(null))
            ->get(route('contenido.archivo', $item))
            ->assertForbidden();
    }

    public function test_admin_abre_el_form_de_contenido_con_los_campos_nuevos(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['nivel' => RolUsuario::Admin, 'estado' => EstadoUsuario::Activo])->save();

        $this->actingAs($admin)
            ->get('/admin/content-items/create')
            ->assertOk()
            ->assertSee('Blog / artículo')   // tipo nuevo
            ->assertSee('Archivo (subir)')   // subida real
            ->assertSee('Nivel');            // access_level en el form
    }

    public function test_blog_renderiza_su_cuerpo_en_la_vista(): void
    {
        $item = ContentItem::create([
            'title' => 'Guía de estiramientos',
            'type' => ContentItem::TYPE_BLOG,
            'body' => '<p>Respira <strong>profundo</strong> y estira.</p>',
            'access_level' => 1,
            'is_published' => true, 'published_at' => now(),
        ]);

        $this->actingAs($this->coach(null))
            ->get(route('contenido.show', $item))
            ->assertOk()
            ->assertSee('<strong>profundo</strong>', false);
    }
}

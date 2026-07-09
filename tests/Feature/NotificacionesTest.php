<?php

namespace Tests\Feature;

use App\Models\ProfessionalProfile;
use App\Models\User;
use App\Notifications\CuentaAprobadaNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NotificacionesTest extends TestCase
{
    use RefreshDatabase;

    public function test_contactar_genera_notificacion_al_profesional(): void
    {
        Mail::fake();
        $profesional = User::factory()->create();
        $profile = $profesional->professionalProfile()->create(['is_published' => true]);
        $contratante = User::factory()->contratante()->create();

        $this->actingAs($contratante)->post(route('contacto.store', $profile->slug), [
            'contact_name' => 'Estudio Zen',
            'contact_email' => 'zen@example.com',
            'message' => 'Nos encantaría trabajar contigo pronto.',
        ]);

        $this->assertCount(1, $profesional->fresh()->notifications);
        $this->assertSame('contacto', $profesional->fresh()->notifications->first()->data['tipo']);
    }

    public function test_aprobar_usuario_le_notifica(): void
    {
        $pendiente = User::factory()->pendiente()->create();
        $pendiente->notify(new CuentaAprobadaNotification());

        $this->assertCount(1, $pendiente->notifications);
        $this->assertSame('cuenta', $pendiente->notifications->first()->data['tipo']);
    }

    public function test_pagina_de_notificaciones_carga(): void
    {
        $user = User::factory()->create();
        $user->notify(new CuentaAprobadaNotification());

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertStatus(200)
            ->assertSee('aprobada');
    }

    public function test_abrir_notificacion_la_marca_leida_y_redirige(): void
    {
        $user = User::factory()->create();
        $user->notify(new CuentaAprobadaNotification());
        $n = $user->notifications()->first();

        $this->actingAs($user)
            ->get(route('notifications.open', $n->id))
            ->assertRedirect();

        $this->assertNotNull($n->fresh()->read_at);
    }

    public function test_abrir_notificacion_con_url_de_otro_host_redirige_al_host_actual(): void
    {
        // Notificaciones antiguas guardaban URL absolutas; si el APP_URL cambió,
        // el destino apuntaría a un host inalcanzable. open() debe quedarse con path+query.
        $user = User::factory()->create();
        $user->notify(new CuentaAprobadaNotification());
        $n = $user->notifications()->first();
        $data = $n->data;
        $data['url'] = 'http://host-viejo-inalcanzable.test/dashboard?ref=mail';
        $n->forceFill(['data' => $data])->save();

        $this->actingAs($user)
            ->get(route('notifications.open', $n->id))
            ->assertRedirect('/dashboard?ref=mail');
    }

    public function test_marcar_todo_leido(): void
    {
        $user = User::factory()->create();
        $user->notify(new CuentaAprobadaNotification());
        $user->notify(new CuentaAprobadaNotification());

        $this->assertSame(2, $user->unreadNotifications()->count());

        $this->actingAs($user)->post(route('notifications.readAll'))->assertRedirect();

        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());
    }
}

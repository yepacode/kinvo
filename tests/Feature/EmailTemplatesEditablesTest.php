<?php
namespace Tests\Feature;
use App\Models\BenefitRequest;
use App\Models\EmailTemplate;
use App\Models\User;
use App\Notifications\RespaldoAgendadoNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class EmailTemplatesEditablesTest extends TestCase
{
    use RefreshDatabase;

    public function test_render_usa_la_plantilla_editada_con_placeholders(): void
    {
        EmailTemplate::create([
            'key' => 'test_key', 'description' => 'x', 'is_active' => true,
            'subject' => 'Hola {{nombre}}', 'greeting' => 'Saludo', 'body' => 'Cuerpo {{nombre}}',
        ]);
        $t = EmailTemplate::render('test_key', ['nombre' => 'Ana'], ['subject' => 'fb']);
        $this->assertSame('Hola Ana', $t['subject']);
        $this->assertSame('Cuerpo Ana', $t['body']);
    }

    public function test_render_usa_fallback_si_no_hay_plantilla(): void
    {
        $t = EmailTemplate::render('inexistente', [], ['subject' => 'FB', 'body' => 'B']);
        $this->assertSame('FB', $t['subject']);
    }

    public function test_el_seeder_crea_las_plantillas_clave(): void
    {
        $this->seed(\Database\Seeders\EmailTemplateSeeder::class);
        foreach (['cuenta_aprobada','postulacion_accepted','profesional_interesado','respaldo_nuevo_admin','removido_equipo'] as $k) {
            $this->assertDatabaseHas('email_templates', ['key' => $k]);
        }
        $this->assertGreaterThanOrEqual(21, EmailTemplate::count());
    }

    public function test_una_notificacion_wired_usa_la_plantilla_editada(): void
    {
        EmailTemplate::create([
            'key' => 'respaldo_agendado_coach', 'description' => 'x', 'is_active' => true,
            'subject' => 'EDIT {{tipo}}', 'greeting' => 'Hola', 'body' => 'Cuerpo editado',
        ]);
        $coach = User::factory()->create(['name' => 'Ana']);
        $req = BenefitRequest::create([
            'user_id' => $coach->id, 'type' => 'telemedicine',
            'status' => 'scheduled', 'scheduled_for' => now()->addDay(),
        ]);
        $mail = (new RespaldoAgendadoNotification($req))->toMail($coach);
        $this->assertStringContainsString('EDIT Telemedicina', $mail->subject);
        $this->assertContains('Cuerpo editado', $mail->introLines);
    }
}

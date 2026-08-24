<?php
namespace Tests\Feature;
use App\Mail\AvisoCobroExitoso;
use App\Mail\AvisoCobroFallido;
use App\Mail\AvisoVencimientoMembresia;
use App\Mail\BienvenidaEstudio;
use App\Mail\BienvenidaTalento;
use App\Mail\NuevoContacto;
use App\Models\Contact;
use App\Models\EmailTemplate;
use App\Models\ProfessionalProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class MailablesEditablesTest extends TestCase
{
    use RefreshDatabase;

    public function test_bienvenida_talento_renderiza_con_plantilla(): void
    {
        EmailTemplate::create([
            'key' => 'bienvenida_talento', 'description' => 'x', 'is_active' => true,
            'subject' => 'EDIT bienvenida {{name}}', 'greeting' => 'Hola {{name}}', 'body' => 'Cuerpo editado',
        ]);
        $u = User::factory()->create(['name' => 'Ana']);
        $m = (new BienvenidaTalento($u))->render();
        $env = (new BienvenidaTalento($u))->envelope();
        $this->assertSame('EDIT bienvenida Ana', $env->subject);
        $this->assertStringContainsString('Hola Ana', $m);
        $this->assertStringContainsString('Cuerpo editado', $m);
    }

    public function test_bienvenida_estudio_usa_fallback_si_no_hay_plantilla(): void
    {
        $u = User::factory()->create(['name' => 'Estudio X']);
        $env = (new BienvenidaEstudio($u))->envelope();
        $this->assertStringContainsString('Bienvenido', $env->subject);
        $this->assertNotEmpty((new BienvenidaEstudio($u))->render());
    }

    public function test_avisos_cobro_renderizan(): void
    {
        $u = User::factory()->create();
        foreach ([
            new AvisoCobroExitoso($u, null),
            new AvisoCobroFallido($u, null),
            new AvisoVencimientoMembresia($u),
        ] as $mail) {
            $this->assertNotEmpty($mail->render(), get_class($mail).' no renderiza');
        }
    }

    public function test_nuevo_contacto_renderiza(): void
    {
        $u = User::factory()->create();
        $u->professionalProfile()->create(['headline' => 'x']);
        $contratante = User::factory()->create();
        $c = Contact::create([
            'professional_profile_id' => $u->professionalProfile->id,
            'contractor_user_id' => $contratante->id,
            'contact_name' => 'Estudio Que Contacta', 'contact_email' => 'e@t.com',
            'message' => 'Mensaje de prueba',
        ]);
        $m = new NuevoContacto($c, $u->professionalProfile);
        $out = $m->render();
        $this->assertStringContainsString('Estudio Que Contacta', $out);
        $this->assertStringContainsString('Mensaje de prueba', $out);
    }
}

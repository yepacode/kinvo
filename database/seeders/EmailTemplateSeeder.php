<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

/**
 * M7 · Seed idempotente de plantillas de correo.
 * Rellena la tabla con el contenido actual hard-coded de cada notif/mail.
 * Marian luego puede editar cada una desde el admin.
 */
class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $tpls = [
            [
                'key' => 'bienvenida_talento',
                'description' => 'Bienvenida al coach al registrarse.',
                'subject' => 'Bienvenido a Kinvoo, {{name}}',
                'greeting' => '¡Hola {{name}}!',
                'body' => "Nos alegra tenerte en Kinvoo. En cuanto completes tu perfil los estudios podrán encontrarte.\n\nComparte tu experiencia, disciplinas y disponibilidad — mientras más completo, más oportunidades verás.",
                'action_label' => 'Completar mi perfil',
                'action_url_hint' => 'route:professional.profile.edit',
                'outro' => 'Cualquier duda, escríbenos a hola@gokinvoo.com.',
                'placeholders_hint' => ['name'],
            ],
            [
                'key' => 'bienvenida_estudio',
                'description' => 'Bienvenida al estudio al registrarse.',
                'subject' => 'Bienvenido a Kinvoo, {{name}}',
                'greeting' => '¡Hola {{name}}!',
                'body' => "Gracias por elegir Kinvoo para tu estudio. Completa el perfil de tu empresa para poder publicar oportunidades y encontrar talento.",
                'action_label' => 'Completar mi empresa',
                'action_url_hint' => 'route:company.profile.edit',
                'outro' => 'Cualquier duda, escríbenos a hola@gokinvoo.com.',
                'placeholders_hint' => ['name'],
            ],
            [
                'key' => 'invitacion_equipo',
                'description' => 'Al coach cuando un estudio lo invita a su equipo.',
                'subject' => 'Kinvoo · {{estudio}} te invita a su equipo',
                'greeting' => 'Hola {{coach}},',
                'body' => "**{{estudio}}** quiere sumarte a su equipo en Kinvoo.\n\nAl aceptar, su cuidado empieza a sumar en tu perfil (telemedicina, fisio, contenido, etc. según el plan del estudio).",
                'action_label' => 'Ver invitación',
                'action_url_hint' => 'route:notifications.index',
                'outro' => 'Si no fue una invitación esperada, simplemente recházala desde la campanita.',
                'placeholders_hint' => ['coach', 'estudio'],
            ],
            [
                'key' => 'respuesta_equipo_aceptada',
                'description' => 'Al estudio cuando el coach ACEPTA la invitación al equipo.',
                'subject' => 'Kinvoo · {{coach}} aceptó tu invitación',
                'greeting' => 'Hola {{estudio}},',
                'body' => "**{{coach}}** aceptó formar parte de tu equipo en Kinvoo.\n\nA partir de ahora, su cuidado (consultas, fisio, charlas, etc.) suma en tu Panel de bienestar.",
                'action_label' => 'Ver mi equipo',
                'action_url_hint' => 'route:equipo.index',
                'outro' => 'Gracias por seguir cuidando a tu equipo con Kinvoo.',
                'placeholders_hint' => ['coach', 'estudio'],
            ],
            [
                'key' => 'respuesta_equipo_rechazada',
                'description' => 'Al estudio cuando el coach RECHAZA la invitación al equipo.',
                'subject' => 'Kinvoo · {{coach}} declinó tu invitación',
                'greeting' => 'Hola {{estudio}},',
                'body' => "**{{coach}}** declinó por ahora tu invitación.\n\nEs normal — puedes invitar a otros profesionales cuando quieras.",
                'action_label' => 'Ver mi equipo',
                'action_url_hint' => 'route:equipo.index',
                'outro' => 'Gracias por seguir cuidando a tu equipo con Kinvoo.',
                'placeholders_hint' => ['coach', 'estudio'],
            ],
            [
                'key' => 'postulacion_seen',
                'description' => 'Al coach cuando el estudio VE su postulación.',
                'subject' => 'Kinvoo · Tu postulación fue vista — {{oferta}}',
                'greeting' => 'Hola {{coach}},',
                'body' => "{{estudio}} revisó tu postulación. Si le interesa avanzar, te contactará pronto.\n\n**Oferta:** {{oferta}}",
                'action_label' => 'Ver mis postulaciones',
                'action_url_hint' => 'route:ofertas.mis-postulaciones',
                'outro' => 'Recibes este aviso porque postulaste a una oferta en Kinvoo.',
                'placeholders_hint' => ['coach', 'estudio', 'oferta'],
            ],
            [
                'key' => 'postulacion_in_contact',
                'description' => 'Al coach cuando el estudio quiere contactarlo.',
                'subject' => 'Kinvoo · El estudio quiere contactarte — {{oferta}}',
                'greeting' => 'Hola {{coach}},',
                'body' => "{{estudio}} quiere entrar en contacto contigo respecto a esta postulación.\n\n**Oferta:** {{oferta}}",
                'action_label' => 'Ver mis postulaciones',
                'action_url_hint' => 'route:ofertas.mis-postulaciones',
                'outro' => 'Recibes este aviso porque postulaste a una oferta en Kinvoo.',
                'placeholders_hint' => ['coach', 'estudio', 'oferta'],
            ],
            [
                'key' => 'postulacion_accepted',
                'description' => 'Al coach cuando el estudio ACEPTA su postulación.',
                'subject' => 'Kinvoo · ¡Postulación aceptada! — {{oferta}}',
                'greeting' => 'Hola {{coach}},',
                'body' => "¡Excelente noticia! {{estudio}} aceptó tu postulación y se pondrá en contacto contigo.\n\n**Oferta:** {{oferta}}",
                'action_label' => 'Ver mis postulaciones',
                'action_url_hint' => 'route:ofertas.mis-postulaciones',
                'outro' => 'Recibes este aviso porque postulaste a una oferta en Kinvoo.',
                'placeholders_hint' => ['coach', 'estudio', 'oferta'],
            ],
            [
                'key' => 'postulacion_rejected',
                'description' => 'Al coach cuando el estudio RECHAZA su postulación.',
                'subject' => 'Kinvoo · Actualización de tu postulación — {{oferta}}',
                'greeting' => 'Hola {{coach}},',
                'body' => "{{estudio}} decidió no avanzar con tu postulación en esta ocasión. Sigue postulando — cada intento cuenta.\n\n**Oferta:** {{oferta}}",
                'action_label' => 'Ver mis postulaciones',
                'action_url_hint' => 'route:ofertas.mis-postulaciones',
                'outro' => 'Recibes este aviso porque postulaste a una oferta en Kinvoo.',
                'placeholders_hint' => ['coach', 'estudio', 'oferta'],
            ],
            [
                'key' => 'respaldo_nuevo_admin',
                'description' => 'Al admin (Kinvoo) cuando un coach solicita telemedicina o fisio.',
                'subject' => 'Kinvoo · Nuevo Respaldo pendiente ({{tipo}}) — {{coach}}',
                'greeting' => 'Hola equipo Kinvoo,',
                'body' => "{{coach}} pidió una sesión de **{{tipo}}**.\n\n**Prefiere:** {{preferred_slot}}\n**Nota:** {{note}}",
                'action_label' => 'Agendar en el panel',
                'action_url_hint' => '/admin/benefit-requests',
                'outro' => 'Al confirmar la fecha, el coach recibirá el aviso automáticamente.',
                'placeholders_hint' => ['coach', 'tipo', 'preferred_slot', 'note'],
            ],
            [
                'key' => 'respaldo_agendado_coach',
                'description' => 'Al coach cuando el admin agenda su sesión.',
                'subject' => 'Kinvoo · Tu sesión de {{tipo}} está agendada',
                'greeting' => 'Hola {{coach}},',
                'body' => "Tu solicitud de **{{tipo}}** ya fue agendada por Kinvoo.\n\n**Cuándo:** {{cuando}}\n**Nota Kinvoo:** {{admin_note}}",
                'action_label' => 'Ver detalle',
                'action_url_hint' => 'route:respaldo.index',
                'outro' => 'Si necesitas reprogramar, respóndenos a este correo.',
                'placeholders_hint' => ['coach', 'tipo', 'cuando', 'admin_note'],
            ],
        ];

        foreach ($tpls as $t) {
            EmailTemplate::updateOrCreate(['key' => $t['key']], $t);
        }
    }
}

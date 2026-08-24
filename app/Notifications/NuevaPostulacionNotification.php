<?php

namespace App\Notifications;

use App\Models\Application;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * HIGH-8/17 · Al estudio cuando un profesional postula: canal database + mail.
 * Antes solo campana → el estudio se enteraba solo al entrar al panel; la
 * matriz del cliente lista "correo al estudio con la postulación" como
 * requisito explícito.
 */
class NuevaPostulacionNotification extends Notification
{
    public function __construct(public Application $application) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toArray(object $notifiable): array
    {
        $this->application->loadMissing(['professional', 'offer']);
        $nombre = $this->application->professional?->name ?: 'Un profesional';
        $oferta = $this->application->offer?->title ?: 'tu oferta';

        return [
            'tipo' => 'nueva_postulacion',
            'icono' => '📥',
            'titulo_key' => 'Nueva postulación recibida',
            'titulo_params' => [],
            'mensaje_key' => ':nombre postuló a ":oferta"',
            'mensaje_params' => ['nombre' => $nombre, 'oferta' => Str::limit($oferta, 40)],
            'titulo' => 'Nueva postulación recibida',
            'mensaje' => "$nombre postuló a \"".Str::limit($oferta, 40).'"',
            'url' => route('ofertas.mis-ofertas', absolute: false),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->application->loadMissing(['professional', 'offer.contractor.companyProfile']);
        $nombre = $this->application->professional?->name ?: 'Un profesional';
        $oferta = $this->application->offer?->title ?: 'tu oferta';
        $estudio = $this->application->offer?->contractor?->companyProfile?->company_name
            ?: $this->application->offer?->contractor?->name
            ?: 'Estudio';
        $url = url(route('ofertas.mis-ofertas', absolute: false));

        $t = \App\Models\EmailTemplate::render('nueva_postulacion',
            ['nombre' => $nombre, 'oferta' => Str::limit($oferta, 80), 'estudio' => $estudio],
            [
                'subject'      => 'Kinvoo · Nueva postulación a "'.Str::limit($oferta, 60).'"',
                'greeting'     => 'Hola '.$estudio.',',
                'body'         => '**'.$nombre.'** acaba de postular a tu vacante "'.Str::limit($oferta, 80).'" en Kinvoo. Revisa su perfil, carta de presentación y ponte en contacto directamente.',
                'action_label' => 'Ver postulaciones',
                'action_url'   => $url,
                'outro'        => 'Consejo: los coaches valoran una respuesta rápida — les da confianza sobre tu estudio.',
            ]
        );
        $t['action_url'] = $url;

        $mail = (new MailMessage)->subject($t['subject']);
        if ($t['greeting']) $mail->greeting($t['greeting']);
        foreach (explode("\n\n", $t['body']) as $par) {
            $par = trim($par);
            if ($par !== '') $mail->line($par);
        }
        if ($t['action_label']) $mail->action($t['action_label'], $t['action_url']);
        if ($t['outro']) $mail->line($t['outro']);
        return $mail;
    }
}

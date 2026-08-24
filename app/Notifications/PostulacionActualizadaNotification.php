<?php

namespace App\Notifications;

use App\Models\Application;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Fase 2 · Al profesional cuando el estudio cambia el estado de su
 * postulación (seen/in_contact/accepted/rejected).
 *
 * H3 · petición cliente (docx PRUEBA KINVOO, jul-2026):
 * "cada cambio de status se notificará" al postulante. Antes solo iba
 * a la campanita (database); ahora también correo — síncrono, para
 * que un fallo SMTP no rompa el flujo del estudio.
 */
class PostulacionActualizadaNotification extends Notification
{
    public function __construct(public Application $application) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toArray(object $notifiable): array
    {
        $labels = [
            Application::STATUS_SEEN       => 'El estudio vio tu postulación',
            Application::STATUS_IN_CONTACT => 'El estudio quiere contactarte',
            Application::STATUS_ACCEPTED   => 'Postulación aceptada',
            Application::STATUS_REJECTED   => 'Postulación rechazada',
        ];
        $titulo = $labels[$this->application->status] ?? 'Postulación actualizada';

        $this->application->loadMissing('offer');
        $oferta = $this->application->offer?->title ?: 'tu postulación';

        return [
            'tipo' => 'postulacion_actualizada',
            'icono' => match ($this->application->status) {
                Application::STATUS_ACCEPTED => '🎉',
                Application::STATUS_REJECTED => '📭',
                default => '👀',
            },
            'titulo_key' => $titulo,
            'titulo_params' => [],
            'mensaje_key' => 'Oferta: :oferta',
            'mensaje_params' => ['oferta' => $oferta],
            'titulo' => $titulo,
            'mensaje' => "Oferta: $oferta",
            'url' => route('ofertas.mis-postulaciones', absolute: false),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->application->loadMissing(['offer.contractor.companyProfile']);
        $oferta = $this->application->offer;
        $estudio = $oferta?->contractor?->companyProfile?->company_name
            ?? $oferta?->contractor?->name
            ?? 'El estudio';

        $asuntos = [
            Application::STATUS_SEEN       => 'Tu postulación fue vista',
            Application::STATUS_IN_CONTACT => 'El estudio quiere contactarte',
            Application::STATUS_ACCEPTED   => '¡Postulación aceptada!',
            Application::STATUS_REJECTED   => 'Actualización sobre tu postulación',
        ];
        $lineas = [
            Application::STATUS_SEEN       => $estudio.' revisó tu postulación. Si le interesa avanzar, te contactará pronto.',
            Application::STATUS_IN_CONTACT => $estudio.' quiere entrar en contacto contigo respecto a esta postulación.',
            Application::STATUS_ACCEPTED   => '¡Excelente noticia! '.$estudio.' aceptó tu postulación y se pondrá en contacto contigo.',
            Application::STATUS_REJECTED   => $estudio.' decidió no avanzar con tu postulación en esta ocasión. Sigue postulando — cada intento cuenta.',
        ];
        $asunto = $asuntos[$this->application->status] ?? 'Actualización de tu postulación';
        $mensaje = $lineas[$this->application->status] ?? 'El estado de tu postulación cambió.';
        $coach = $notifiable->name ?: 'Coach';
        $ofertaTitulo = $oferta?->title ?? '';
        $url = url(route('ofertas.mis-postulaciones', absolute: false));

        // Editable desde el panel: una plantilla por estado (postulacion_seen/
        // in_contact/accepted/rejected).
        $t = \App\Models\EmailTemplate::render('postulacion_'.$this->application->status,
            ['coach' => $coach, 'estudio' => $estudio, 'oferta' => $ofertaTitulo],
            [
                'subject' => 'Kinvoo · '.$asunto.($ofertaTitulo ? ' — '.$ofertaTitulo : ''),
                'greeting' => 'Hola '.$coach.',',
                'body' => $mensaje.($ofertaTitulo ? "\n\n".'**Oferta:** '.$ofertaTitulo : ''),
                'action_label' => 'Ver mis postulaciones',
                'action_url' => $url,
                'outro' => 'Recibes este aviso porque postulaste a una oferta en Kinvoo.',
            ]
        );
        $t['action_url'] = $url;

        return \App\Models\EmailTemplate::toMailMessage($t);
    }
}

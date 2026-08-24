<?php

namespace App\Notifications;

use App\Models\BenefitRequest;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * M6 · Aviso al admin de Kinvoo cuando un coach solicita Respaldo
 * (telemedicina o fisio). Va por correo + campana.
 * Envío síncrono — el mismo patrón que ProfesionalInteresadoNotification.
 */
class NuevoRespaldoNotification extends Notification
{
    public function __construct(public BenefitRequest $request) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * LOW-11 · Tipos con __() para respetar el locale del admin que reciba.
     * Los admins pueden tener locale='en' en su cuenta y las notifs deben
     * llegarles en su idioma.
     */
    private function tipoLabel(): string
    {
        // Servicio del catálogo (Punto 5-A): usar su nombre tal cual.
        if ($this->request->service) {
            return $this->request->service->nombre;
        }

        return $this->request->type === BenefitRequest::TYPE_PHYSIO
            ? __('Fisioterapia')
            : __('Telemedicina');
    }

    public function toArray(object $notifiable): array
    {
        $this->request->loadMissing('user');
        // Guardamos keys neutras + params, para que la campanita traduzca al
        // idioma del receptor al renderizar (patrón que ya usan otras notifs).
        return [
            'tipo'        => 'benefit_request',
            'icono'       => $this->request->service?->icono ?: ($this->request->type === BenefitRequest::TYPE_PHYSIO ? '💪' : '🩺'),
            'titulo_key'  => 'Nuevo Respaldo: :tipo — :coach',
            'titulo_params' => [
                'tipo'  => $this->tipoLabel(),
                'coach' => $this->request->user?->name ?? __('Coach'),
            ],
            'titulo'      => 'Nuevo Respaldo: '.$this->tipoLabel().' — '.($this->request->user?->name ?? __('Coach')),
            'mensaje'     => $this->request->preferred_slot ?: __('(sin preferencia horaria)'),
            'url'         => route('filament.admin.resources.benefit-requests.index', absolute: false),
            'request_id'  => $this->request->id,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        // Cambiar el locale global al del receptor para que __() dentro del
        // MailMessage devuelva EN si el admin tiene locale=en.
        $localeOriginal = app()->getLocale();
        $preferido = method_exists($notifiable, 'preferredLocale')
            ? ($notifiable->preferredLocale() ?: $localeOriginal)
            : $localeOriginal;
        app()->setLocale($preferido);
        try {
            $this->request->loadMissing('user');
            $tipo = $this->tipoLabel();
            $coach = $this->request->user?->name ?? __('Un coach');
            $prefiere = $this->request->preferred_slot ?: '';
            $nota = $this->request->note ?: '';
            $url = url(route('filament.admin.resources.benefit-requests.index', absolute: false));

            // Editable desde el panel (plantilla respaldo_nuevo_admin).
            $t = \App\Models\EmailTemplate::render('respaldo_nuevo_admin',
                ['coach' => $coach, 'tipo' => $tipo, 'preferred_slot' => $prefiere, 'note' => $nota],
                [
                    'subject' => 'Kinvoo · '.__('Nuevo Respaldo pendiente').' ('.$tipo.') — '.$coach,
                    'greeting' => __('Hola equipo Kinvoo,'),
                    'body' => $coach.' '.__('pidió una sesión de').' **'.$tipo.'**.'
                        .($prefiere ? "\n\n".'**'.__('Prefiere:').'** '.$prefiere : '')
                        .($nota ? "\n\n".'**'.__('Nota:').'** '.$nota : ''),
                    'action_label' => __('Agendar en el panel'),
                    'action_url' => $url,
                    'outro' => __('Al confirmar la fecha, el coach recibirá el aviso automáticamente.'),
                ]
            );
            $t['action_url'] = $url;

            return \App\Models\EmailTemplate::toMailMessage($t);
        } finally {
            app()->setLocale($localeOriginal);
        }
    }
}

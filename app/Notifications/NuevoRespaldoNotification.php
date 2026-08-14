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
            'icono'       => $this->request->type === BenefitRequest::TYPE_PHYSIO ? '💪' : '🩺',
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

            return (new MailMessage)
                ->subject('Kinvoo · '.__('Nuevo Respaldo pendiente').' ('.$tipo.') — '.$coach)
                ->greeting(__('Hola equipo Kinvoo,'))
                ->line($coach.' '.__('pidió una sesión de').' **'.$tipo.'**.')
                ->when($this->request->preferred_slot,
                    fn ($m) => $m->line('**'.__('Prefiere:').'** '.$this->request->preferred_slot))
                ->when($this->request->note,
                    fn ($m) => $m->line('**'.__('Nota:').'** '.$this->request->note))
                ->action(__('Agendar en el panel'), url(route('filament.admin.resources.benefit-requests.index', absolute: false)))
                ->line(__('Al confirmar la fecha, el coach recibirá el aviso automáticamente.'));
        } finally {
            app()->setLocale($localeOriginal);
        }
    }
}

<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Correo de restablecer contraseña. Pasa por EmailTemplate (`reset_password`)
 * para que el admin lo edite desde `/admin/email-templates`; si la plantilla
 * está inactiva o falta, cae al fallback en español + link firmado válido.
 * Feedback Karla 27-ago: "correos aún llegan los del servidor" → este era el
 * único que quedaba hardcoded, corregido.
 */
class ResetPasswordEnEspanol extends BaseResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        $minutos = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        $vars = [
            'nombre'  => $notifiable->name ?? $notifiable->getEmailForPasswordReset(),
            'minutos' => (string) $minutos,
        ];

        $t = EmailTemplate::render('reset_password', $vars, [
            'subject'      => __('Restablece tu contraseña de Kinvoo'),
            'greeting'     => __('Hola'),
            'body'         => __('Recibiste este correo porque solicitaste restablecer la contraseña de tu cuenta.'),
            'action_label' => __('Restablecer contraseña'),
            'action_url'   => $url,
            'outro'        => __('Este enlace expirará en :count minutos.', ['count' => $minutos])
                .' '.__('Si no solicitaste este cambio, puedes ignorar este correo — no se hará ningún cambio en tu cuenta.'),
        ]);

        // La URL SIEMPRE viene del código (nunca de la plantilla), por
        // seguridad. Si el admin borró el action_label, restauramos default.
        $t['action_url']   = $url;
        $t['action_label'] = $t['action_label'] ?: __('Restablecer contraseña');

        return EmailTemplate::toMailMessage($t)
            ->salutation(__('Gracias,').' '.__('El equipo de Kinvoo'));
    }
}

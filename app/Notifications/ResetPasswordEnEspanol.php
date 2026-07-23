<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Sobreescribe el correo de reset de contraseña por defecto de Laravel para
 * que respete el locale del usuario. La versión base usa strings inglés
 * pasados por `Lang::get()`, pero no están en nuestros JSON de idioma,
 * así que caían al inglés incluso para usuarios ES.
 *
 * El locale NO se aplica en el MailMessage (que en Laravel 12 no expone
 * `->locale()`), sino que la Notification honra automáticamente el
 * `preferredLocale()` del notifiable — User implementa HasLocalePreference
 * y devuelve 'es' o 'en' según la preferencia persistida.
 */
class ResetPasswordEnEspanol extends BaseResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject(__('Restablece tu contraseña de Kinvoo'))
            ->greeting(__('Hola'))
            ->line(__('Recibiste este correo porque solicitaste restablecer la contraseña de tu cuenta.'))
            ->action(__('Restablecer contraseña'), url(route('password.reset', [
                'token' => $this->token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false)))
            ->line(__('Este enlace expirará en :count minutos.', [
                'count' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire'),
            ]))
            ->line(__('Si no solicitaste este cambio, puedes ignorar este correo — no se hará ningún cambio en tu cuenta.'))
            ->salutation(__('Gracias,').' '.__('El equipo de Kinvoo'));
    }
}

<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Sobreescribe el correo de reset de contraseña por defecto de Laravel para
 * que respete el locale del usuario. La versión base usa strings inglés
 * pasados por `Lang::get()`, pero no están en nuestros JSON de idioma,
 * así que caían al inglés incluso para usuarios ES.
 */
class ResetPasswordEnEspanol extends BaseResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        // Locale del usuario si implementa HasLocalePreference, si no, cae al app locale.
        $locale = method_exists($notifiable, 'preferredLocale')
            ? $notifiable->preferredLocale()
            : app()->getLocale();

        return (new MailMessage())
            ->locale($locale)
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

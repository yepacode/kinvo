<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * M7 · Plantilla de correo editable por el admin (petición cliente).
 * Cada Notification/Mailable pide la plantilla por `key`. Si está activa,
 * usa esos textos con `{{placeholders}}` reemplazados; si no, el llamador
 * usa su fallback hard-coded.
 *
 * Uso desde una Notification:
 *   $t = EmailTemplate::render('invitacion_equipo', [
 *       'coach' => $coach, 'estudio' => $estudio,
 *   ], $fallbackArray);
 */
class EmailTemplate extends Model
{
    protected $fillable = [
        'key', 'description', 'subject', 'greeting', 'body',
        'action_label', 'action_url_hint', 'outro',
        'placeholders_hint', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'placeholders_hint' => 'array',
    ];

    /**
     * Retorna los textos de la plantilla con `{{placeholders}}` reemplazados.
     * Si la plantilla no existe o está inactiva, retorna $fallback tal cual.
     *
     * $fallback tiene que tener las mismas keys: subject, greeting, body,
     * action_label, action_url, outro.
     */
    public static function render(string $key, array $vars = [], array $fallback = []): array
    {
        $tpl = static::where('key', $key)->where('is_active', true)->first();
        if (! $tpl) {
            return $fallback;
        }

        return [
            'subject'       => static::replace($tpl->subject, $vars),
            'greeting'      => static::replace($tpl->greeting ?? '', $vars),
            'body'          => static::replace($tpl->body, $vars),
            'action_label'  => static::replace($tpl->action_label ?? '', $vars),
            'action_url'    => $fallback['action_url'] ?? null, // URL siempre viene del código
            'outro'         => static::replace($tpl->outro ?? '', $vars),
        ];
    }

    /**
     * Reemplaza `{{placeholders}}` con los valores dados, SIN escape HTML.
     * El escape lo hace la capa de renderizado (Blade `{{ }}` en tpl-generic
     * o Markdown de MailMessage en las Notifications). Antes hacía doble
     * escape → un estudio "Café D'Amico & Co" salía como "Café D&#039;Amico &amp; Co"
     * en los correos.
     *
     * Los placeholders traen datos de usuario (nombres, mensajes libres). Como
     * la capa de renderizado los escapa (o los pasa por Markdown que no ejecuta
     * HTML crudo), el riesgo XSS de un `<script>` en un nombre queda contenido.
     */
    private static function replace(string $s, array $vars): string
    {
        foreach ($vars as $k => $v) {
            $s = str_replace('{{'.$k.'}}', (string) $v, $s);
        }
        return $s;
    }

    /**
     * Construye un MailMessage a partir del array de render() (subject/greeting/
     * body/action_label/action_url/outro). Centraliza el armado para que cada
     * notificación solo llame a render() + este helper.
     */
    public static function toMailMessage(array $t): \Illuminate\Notifications\Messages\MailMessage
    {
        $mail = (new \Illuminate\Notifications\Messages\MailMessage)->subject($t['subject'] ?? '');
        if (! empty($t['greeting'])) {
            $mail->greeting($t['greeting']);
        }
        foreach (explode("\n\n", (string) ($t['body'] ?? '')) as $parrafo) {
            $parrafo = trim($parrafo);
            if ($parrafo !== '') {
                $mail->line($parrafo);
            }
        }
        if (! empty($t['action_label']) && ! empty($t['action_url'])) {
            $mail->action($t['action_label'], $t['action_url']);
        }
        if (! empty($t['outro'])) {
            $mail->line($t['outro']);
        }

        return $mail;
    }
}

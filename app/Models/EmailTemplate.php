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
     * MED-G5 · Los placeholders vienen de datos de usuario (nombres, títulos,
     * mensajes libres). Los emails que renderiza MailMessage aceptan Markdown,
     * y ese renderizador no re-escapa lo interpolado con str_replace. Un
     * usuario malicioso que ponga `<script>...</script>` en su nombre podría
     * inyectarlo en el HTML del correo. Escapamos con htmlspecialchars antes
     * de interpolar. Los `**negritas**` y `[link](url)` del template original
     * siguen funcionando porque son parte del `body` estático (no del placeholder).
     */
    private static function replace(string $s, array $vars): string
    {
        foreach ($vars as $k => $v) {
            $safe = htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $s = str_replace('{{'.$k.'}}', $safe, $s);
        }
        return $s;
    }
}

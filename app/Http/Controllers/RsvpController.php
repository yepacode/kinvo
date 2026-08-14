<?php

namespace App\Http\Controllers;

use App\Models\SesionInvitado;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Fase 2 · Recibe la respuesta RSVP desde el correo (link firmado con
 * rsvp_token único por invitado). El token ES la autenticación.
 *
 * MED-G10 · Diseño anti-prefetch: los clientes de correo (Gmail preview,
 * Outlook Safe Links, antivirus corporativos) hacen GET a las URLs de los
 * correos para chequearlas — si el GET mutaba estado, cualquiera de esos
 * previews cambiaba el RSVP del invitado sin que el humano hiciera click.
 * Ahora GET muestra una página de confirmación con dos botones; sólo el
 * POST cambia estado. Los links viejos siguen funcionando (mismo path).
 */
class RsvpController extends Controller
{
    /** GET /rsvp/{token}[?r=accepted|declined] — SOLO muestra confirmación. */
    public function responder(Request $request, string $token): View
    {
        $invitado = SesionInvitado::where('rsvp_token', $token)->with('sesion')->firstOrFail();

        return view('rsvp.confirmar', [
            'invitado'  => $invitado,
            'sesion'    => $invitado->sesion,
            'sugerido'  => $request->query('r'), // preselección desde el correo (visual, no acción).
        ]);
    }

    /** POST /rsvp/{token} — muta el estado del invitado. */
    public function confirmar(Request $request, string $token): View|RedirectResponse
    {
        $invitado = SesionInvitado::where('rsvp_token', $token)->with('sesion')->firstOrFail();

        $r = $request->input('r');
        if (! in_array($r, [SesionInvitado::RSVP_ACCEPTED, SesionInvitado::RSVP_DECLINED], true)) {
            return back()->withErrors(['r' => 'Elige aceptar o rechazar.']);
        }

        $invitado->update([
            'rsvp'    => $r,
            'rsvp_at' => now(),
        ]);

        return view('rsvp.gracias', [
            'invitado' => $invitado,
            'sesion'   => $invitado->sesion,
        ]);
    }
}

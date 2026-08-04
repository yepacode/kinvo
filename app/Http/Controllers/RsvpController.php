<?php

namespace App\Http\Controllers;

use App\Models\SesionInvitado;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Fase 2 · Recibe la respuesta RSVP desde el correo (link firmado con
 * rsvp_token único por invitado). Vía GET porque va en un correo.
 * No requiere sesión — el token ES la autenticación.
 */
class RsvpController extends Controller
{
    /** GET /rsvp/{token}?r=accepted|declined */
    public function responder(Request $request, string $token): View
    {
        $invitado = SesionInvitado::where('rsvp_token', $token)->with('sesion')->firstOrFail();

        $r = $request->query('r');
        if (in_array($r, [SesionInvitado::RSVP_ACCEPTED, SesionInvitado::RSVP_DECLINED], true)) {
            $invitado->update([
                'rsvp'    => $r,
                'rsvp_at' => now(),
            ]);
        }

        return view('rsvp.gracias', [
            'invitado' => $invitado,
            'sesion'   => $invitado->sesion,
        ]);
    }
}

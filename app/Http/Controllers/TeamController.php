<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\TeamMember;
use App\Models\User;
use App\Models\WellnessEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Fase 2 · Gestión de equipo (2.12) + Panel de bienestar (2.13, renombrado por
 * petición del cliente docx PRUEBA KINVOO — antes "Panel de impacto").
 * El estudio arma su equipo agregando profesionales por email.
 * El panel de bienestar agrega el expediente del equipo.
 */
class TeamController extends Controller
{
    /** GET /mi-equipo — estudio ve/gestiona su equipo.
     *  Matriz: sólo estudio CON membresía. Free se va a planes. */
    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user->esContratante(), 403);
        if (! $user->hasBenefit('gestion_equipo')) {
            return redirect()->route('membresias.index')
                ->with('status', 'plan-necesario-equipo');
        }

        $miembros = TeamMember::where('contractor_user_id', $user->id)
            ->with('professional.professionalProfile')
            ->latest()
            ->get();

        // Panel de bienestar: agregar expediente de los miembros activos.
        // Punto 12 · SOLO cuentan los coaches que eligieron compartir su
        // expediente de cuidado; quien apagó el switch no se refleja aquí.
        $activosIds = $miembros->where('status', TeamMember::STATUS_ACTIVE)
            ->filter(fn ($tm) => $tm->professional?->comparte_expediente)
            ->pluck('professional_user_id');
        // Perf (auditoría ago-2026): antes eran 4 queries; ahora 1 con GROUP BY.
        $conteos = WellnessEntry::whereIn('professional_user_id', $activosIds)
            ->selectRaw('type, count(*) as n')->groupBy('type')->pluck('n', 'type');
        $impacto = [
            'telemedicine' => (int) ($conteos[WellnessEntry::TYPE_TELEMEDICINE] ?? 0),
            'physio' => (int) ($conteos[WellnessEntry::TYPE_PHYSIO] ?? 0),
            'talk' => (int) ($conteos[WellnessEntry::TYPE_TALK] ?? 0),
            'insurance' => (int) ($conteos[WellnessEntry::TYPE_INSURANCE] ?? 0),
        ];

        // Pulso Kinvoo (petición cliente): score 8.2/10 con delta vs mes pasado
        // + comentario anónimo destacado (uno del equipo, del último período).
        // Score = promedio rating (1-5) × 2  → escala 2-10.
        $pulseQuery = \App\Models\PulseResponse::where('contractor_user_id', $user->id);
        $pulseActual   = (float) (clone $pulseQuery)->where('created_at', '>=', now()->subDays(30))->avg('rating');
        $pulseAnterior = (float) (clone $pulseQuery)->whereBetween('created_at', [now()->subDays(60), now()->subDays(30)])->avg('rating');
        $pulso = [
            'score'    => $pulseActual > 0 ? round($pulseActual * 2, 1) : null,
            'delta'    => ($pulseActual > 0 && $pulseAnterior > 0) ? round(($pulseActual - $pulseAnterior) * 2, 1) : null,
            'comentario' => (clone $pulseQuery)
                ->where('created_at', '>=', now()->subDays(30))
                ->whereNotNull('answer_energy')
                ->latest()->value('answer_energy'),
        ];

        return view('equipo.index', compact('miembros', 'impacto', 'pulso'));
    }

    /** POST /mi-equipo/invitar — agrega un profesional por email.
     *  Matriz: sólo estudio con membresía puede invitar. Defensa en profundidad
     *  (el middleware ya bloquea la ruta, pero re-checamos por si alguien
     *  llegara por otra vía). */
    public function invitar(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->esContratante(), 403);
        if (! $user->hasBenefit('gestion_equipo')) {
            return redirect()->route('membresias.index')
                ->with('status', 'plan-necesario-equipo');
        }

        $data = $request->validate(['email' => ['required', 'email']]);

        // H5 · Cupos por estudio (petición cliente): si el admin asignó un
        // max_coach_slots y ya está cubierto, bloquear la invitación con un
        // mensaje claro. NULL = sin límite (comportamiento previo).
        $cupos = $user->companyProfile?->max_coach_slots;
        if ($cupos !== null) {
            $activos = TeamMember::where('contractor_user_id', $user->id)
                ->whereIn('status', [TeamMember::STATUS_ACTIVE, TeamMember::STATUS_INVITED])
                ->count();
            if ($activos >= $cupos) {
                return back()->with('status', 'cupos-alcanzados');
            }
        }

        // Seguridad LOW-1: no distinguimos "no existe" vs "sí existe pero no
        // es profesional" para evitar enumeración de emails. Ambos caen a
        // 'profesional-no-invitable'. También filtramos por rol profesional
        // (no basta con tener professionalProfile: podría ser un contractor
        // con un registro histórico).
        $profesional = User::where('email', $data['email'])
            ->whereHas('professionalProfile')
            ->first();

        if (! $profesional || ! $profesional->esProfesional()) {
            return back()->with('status', 'profesional-no-invitable');
        }

        // MED-H5 · Auto-invitación bloqueada: un usuario con ambos perfiles
        // (raro, pero posible por historial) NO puede invitarse a sí mismo
        // a su propio equipo. Sin este guard el usuario aparecería como
        // "miembro" de su propio estudio, distorsionando cupos y bienestar.
        if ($profesional->id === $user->id) {
            return back()->with('status', 'no-puedes-invitarte');
        }

        // HIGH-21/22 · Manejo idempotente por estado actual del TeamMember.
        // Antes updateOrCreate pisaba el status Y borraba joined_at/left_at
        // sin importar el estado previo:
        //  - coach ACTIVE → volvía a INVITED perdiendo joined_at (bug)
        //  - coach REMOVED → borraba left_at, perdiendo la bitácora de salida
        // Ahora decidimos según el estado:
        $tmExistente = TeamMember::where('contractor_user_id', $user->id)
            ->where('professional_user_id', $profesional->id)
            ->first();

        if ($tmExistente) {
            if ($tmExistente->status === TeamMember::STATUS_ACTIVE) {
                return back()->with('status', 'miembro-ya-activo');
            }
            if ($tmExistente->status === TeamMember::STATUS_INVITED) {
                return back()->with('status', 'miembro-ya-invitado');
            }
            // REMOVED / DECLINED → nueva invitación. Conservamos left_at
            // (fecha de salida anterior) para preservar la bitácora del
            // "estuvo antes y salió el X" en el historial del admin.
            $tmExistente->update([
                'status' => TeamMember::STATUS_INVITED,
                'invited_at' => now(),
                'joined_at' => null,
                // NO tocar left_at — es la fecha de la salida anterior.
            ]);
            $tm = $tmExistente;
        } else {
            $tm = TeamMember::create([
                'contractor_user_id' => $user->id,
                'professional_user_id' => $profesional->id,
                'status' => TeamMember::STATUS_INVITED,
                'invited_at' => now(),
                'joined_at' => null,
                'left_at' => null,
            ]);
        }

        AuditLog::record($user, $tm, 'invited');

        // Notif al profesional invitado (campana).
        try {
            $profesional->notify(new \App\Notifications\InvitacionEquipoNotification($tm));
        } catch (\Throwable $e) { report($e); }

        return back()->with('status', 'invitacion-enviada');
    }

    /** POST /mi-equipo/{miembro}/remover — el estudio saca a un profesional.
     *  Matriz: sólo estudio con membresía gestiona equipo. */
    public function remover(Request $request, TeamMember $miembro): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->esContratante() && $miembro->contractor_user_id === $user->id, 403);
        if (! $user->hasBenefit('gestion_equipo')) {
            return redirect()->route('membresias.index')
                ->with('status', 'plan-necesario-equipo');
        }

        $miembro->update([
            'status' => TeamMember::STATUS_REMOVED,
            'left_at' => now(),
        ]);
        AuditLog::record($user, $miembro, 'removed');

        // CRITICAL-7: notificar al coach removido (campanita + correo).
        // Sin esto el coach nunca sabía que salió del equipo.
        try {
            $miembro->professional?->notify(new \App\Notifications\RemovidoDelEquipoNotification($miembro));
        } catch (\Throwable $e) { report($e); }

        return back()->with('status', 'miembro-removido');
    }

    /** POST /invitaciones/{miembro}/aceptar — profesional acepta la invitación.
     *  CRITICAL-6: sin este guard, un coach que fue removido conservaba la
     *  URL del correo original y podía hacer POST a /aceptar para revivir
     *  su TeamMember a ACTIVE saltando la decisión del estudio. Sólo se
     *  admite la transición INVITED → ACTIVE. */
    public function aceptar(Request $request, TeamMember $miembro): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->esProfesional() && $miembro->professional_user_id === $user->id, 403);

        if ($miembro->status !== TeamMember::STATUS_INVITED) {
            return back()->with('status', 'invitacion-no-vigente');
        }

        $miembro->update([
            'status' => TeamMember::STATUS_ACTIVE,
            'joined_at' => now(),
        ]);
        AuditLog::record($user, $miembro, 'accepted');

        // Aviso al estudio (petición cliente: "correo de aceptación/rechazo").
        try {
            $miembro->contractor?->notify(new \App\Notifications\RespuestaInvitacionEquipoNotification(
                $miembro, \App\Notifications\RespuestaInvitacionEquipoNotification::ACEPTADA
            ));
        } catch (\Throwable $e) { report($e); }

        return back()->with('status', 'invitacion-aceptada');
    }

    /** POST /invitaciones/{miembro}/rechazar — profesional rechaza.
     *  CRITICAL-6: sólo se admite la transición INVITED → DECLINED, para no
     *  revertir estados finales (ACTIVE/REMOVED/DECLINED) desde un link viejo. */
    public function rechazar(Request $request, TeamMember $miembro): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->esProfesional() && $miembro->professional_user_id === $user->id, 403);

        if ($miembro->status !== TeamMember::STATUS_INVITED) {
            return back()->with('status', 'invitacion-no-vigente');
        }

        $miembro->update([
            'status' => TeamMember::STATUS_DECLINED,
            'left_at' => now(),
        ]);
        AuditLog::record($user, $miembro, 'declined');

        try {
            $miembro->contractor?->notify(new \App\Notifications\RespuestaInvitacionEquipoNotification(
                $miembro, \App\Notifications\RespuestaInvitacionEquipoNotification::RECHAZADA
            ));
        } catch (\Throwable $e) { report($e); }

        return back()->with('status', 'invitacion-rechazada');
    }

    /**
     * POST /mi-equipo/bienestar/nota — guarda calificación 1-5 + nota libre
     * del estudio sobre el bienestar de su equipo (H3 · petición cliente).
     */
    public function guardarNotaBienestar(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->esContratante(), 403);

        // H6 · gate free/paid: la evaluación de bienestar sólo se desbloquea
        // cuando el estudio paga por los servicios de cuidado de su equipo.
        // Usamos el helper centralizado para mantener consistencia con la matriz.
        if (! $user->hasBenefit('panel_bienestar')) {
            return redirect()->route('membresias.index')
                ->with('status', 'plan-necesario-bienestar');
        }

        $data = $request->validate([
            'wellness_rating' => ['nullable', 'integer', 'between:1,5'],
            'wellness_notes'  => ['nullable', 'string', 'max:2000'],
        ]);

        $profile = $user->companyProfile()->firstOrCreate([], [
            'company_name' => $user->name,
        ]);

        // HIGH-20 · Solo actualizar los campos que EFECTIVAMENTE vinieron en
        // la request. Antes hacer $data[key] ?? null convertía el campo no
        // enviado en null — si el estudio guardaba solo la nota, la
        // calificación desaparecía y viceversa. Ahora es un update parcial.
        $updates = [];
        foreach (['wellness_rating', 'wellness_notes'] as $k) {
            if ($request->has($k)) {
                $updates[$k] = $data[$k] ?? null;
            }
        }
        if ($updates) {
            $profile->update($updates);
        }
        AuditLog::record($user, $profile, 'wellness_note_updated', new: $updates);

        return back()->with('status', 'bienestar-guardado');
    }
}

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
 * Fase 2 · Gestión de equipo (2.12) + Panel de impacto (2.13).
 * El estudio arma su equipo agregando profesionales por email.
 * El panel de impacto agrega el expediente del equipo.
 */
class TeamController extends Controller
{
    /** GET /mi-equipo — estudio ve/gestiona su equipo. */
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->esContratante(), 403);

        $miembros = TeamMember::where('contractor_user_id', $user->id)
            ->with('professional.professionalProfile')
            ->latest()
            ->get();

        // Panel de impacto: agregar expediente de los miembros activos.
        $activosIds = $miembros->where('status', TeamMember::STATUS_ACTIVE)
            ->pluck('professional_user_id');
        $impacto = [
            'telemedicine' => WellnessEntry::whereIn('professional_user_id', $activosIds)
                ->where('type', WellnessEntry::TYPE_TELEMEDICINE)->count(),
            'physio' => WellnessEntry::whereIn('professional_user_id', $activosIds)
                ->where('type', WellnessEntry::TYPE_PHYSIO)->count(),
            'talk' => WellnessEntry::whereIn('professional_user_id', $activosIds)
                ->where('type', WellnessEntry::TYPE_TALK)->count(),
            'insurance' => WellnessEntry::whereIn('professional_user_id', $activosIds)
                ->where('type', WellnessEntry::TYPE_INSURANCE)->count(),
        ];

        return view('equipo.index', compact('miembros', 'impacto'));
    }

    /** POST /mi-equipo/invitar — agrega un profesional por email. */
    public function invitar(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->esContratante(), 403);

        $data = $request->validate(['email' => ['required', 'email']]);

        $profesional = User::where('email', $data['email'])
            ->whereHas('professionalProfile')
            ->first();

        if (! $profesional || ! $profesional->esProfesional()) {
            return back()->with('status', 'profesional-no-existe');
        }

        $tm = TeamMember::updateOrCreate(
            [
                'contractor_user_id' => $user->id,
                'professional_user_id' => $profesional->id,
            ],
            [
                'status' => TeamMember::STATUS_INVITED,
                'invited_at' => now(),
                'joined_at' => null,
                'left_at' => null,
            ]
        );

        AuditLog::record($user, $tm, 'invited');
        return back()->with('status', 'invitacion-enviada');
    }

    /** POST /mi-equipo/{miembro}/remover — el estudio saca a un profesional. */
    public function remover(Request $request, TeamMember $miembro): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->esContratante() && $miembro->contractor_user_id === $user->id, 403);

        $miembro->update([
            'status' => TeamMember::STATUS_REMOVED,
            'left_at' => now(),
        ]);
        AuditLog::record($user, $miembro, 'removed');
        return back()->with('status', 'miembro-removido');
    }

    /** POST /invitaciones/{miembro}/aceptar — profesional acepta la invitación. */
    public function aceptar(Request $request, TeamMember $miembro): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->esProfesional() && $miembro->professional_user_id === $user->id, 403);

        $miembro->update([
            'status' => TeamMember::STATUS_ACTIVE,
            'joined_at' => now(),
        ]);
        AuditLog::record($user, $miembro, 'accepted');
        return back()->with('status', 'invitacion-aceptada');
    }

    /** POST /invitaciones/{miembro}/rechazar — profesional rechaza. */
    public function rechazar(Request $request, TeamMember $miembro): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->esProfesional() && $miembro->professional_user_id === $user->id, 403);

        $miembro->update([
            'status' => TeamMember::STATUS_DECLINED,
            'left_at' => now(),
        ]);
        AuditLog::record($user, $miembro, 'declined');
        return back()->with('status', 'invitacion-rechazada');
    }
}

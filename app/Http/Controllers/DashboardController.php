<?php

namespace App\Http\Controllers;

use App\Models\TeamMember;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Dashboard: reemplaza el closure de routes/web.php para sacar las queries
 * de la vista Blade (auditoría ago-2026). Precarga membershipPlan para que
 * hasBenefit() no dispare N+1 en el nav.
 */
class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        // Eager-load el plan para que hasBenefit() en nav+dashboard no haga N+1.
        $user->loadMissing('membershipPlan');

        // Punto 7 · Invitaciones a equipo pendientes (aviso destacado al entrar).
        $invitacionesPendientes = $user->esProfesional()
            ? TeamMember::with('contractor')
                ->where('professional_user_id', $user->id)
                ->where('status', TeamMember::STATUS_INVITED)
                ->latest('invited_at')->get()
            : collect();

        // Vistas del perfil profesional (para el bloque "Vistas de tu perfil").
        $totalVistas = 0;
        $vistasRecientes = collect();
        if ($user->esProfesional() && $user->professionalProfile) {
            $totalVistas = $user->professionalProfile->views()->count();
            $vistasRecientes = $user->professionalProfile->views()
                ->with('viewer')->latest()->take(6)->get();
        }

        return view('dashboard', compact(
            'invitacionesPendientes',
            'totalVistas',
            'vistasRecientes',
        ));
    }
}

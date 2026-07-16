<?php

namespace App\Http\Middleware;

use App\Models\ProfessionalProfile;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * El directorio y los perfiles de talento NO son públicos. Solo pueden verlos:
 *  - Contratistas (estudios) con membresía vigente.
 *  - El admin.
 *  - El propio profesional viendo SU perfil (vista previa).
 * Los demás (anónimos ya filtrados por 'auth') se redirigen:
 *  - Contratista sin membresía → a /membresias.
 *  - Profesional (u otro) → a su área.
 */
class AccesoDirectorioTalento
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Admin y contratista con membresía: acceso completo.
        if ($user->esAdmin() || ($user->esContratante() && $user->tieneMembresiaActiva())) {
            return $next($request);
        }

        // El profesional puede ver su propio perfil (vista previa), no el resto.
        $profile = $request->route('professionalProfile');
        if ($profile instanceof ProfessionalProfile && $profile->user_id === $user->id) {
            return $next($request);
        }

        // Contratista sin membresía → a los planes.
        if ($user->esContratante()) {
            return redirect()->route('membresias.index')->with('status', 'membresia-requerida');
        }

        // Profesional (no dueño) u otro rol → a su área.
        return redirect($user->homeRoute())->with('status', 'directorio-restringido');
    }
}

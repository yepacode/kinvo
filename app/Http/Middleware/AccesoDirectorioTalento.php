<?php

namespace App\Http\Middleware;

use App\Models\ProfessionalProfile;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * El directorio y los perfiles de talento NO son públicos. Pueden verlos:
 *  - Contratistas (estudios) — free y paid, ambos. El estudio free PUEDE VER
 *    al talento (petición cliente H6, docx PRUEBA KINVOO); el contactar sí
 *    está gated a plan (gate específico en la ruta contactar).
 *  - El admin.
 *  - El propio profesional viendo SU perfil (vista previa).
 * Los demás (anónimos ya filtrados por 'auth') se redirigen:
 *  - Profesional (no dueño) → a su área.
 */
class AccesoDirectorioTalento
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Admin y contratista (free o paid): acceso al directorio.
        // Nota: el bloqueo por membresía para CONTACTAR está en la ruta
        // /talento/{slug}/contactar con middleware `membresia:plan-necesario-contacto`.
        if ($user->esAdmin() || $user->esContratante()) {
            return $next($request);
        }

        // El profesional puede ver su propio perfil (vista previa), no el resto.
        $profile = $request->route('professionalProfile');
        if ($profile instanceof ProfessionalProfile && $profile->user_id === $user->id) {
            return $next($request);
        }

        // Profesional (no dueño) u otro rol → a su área.
        return redirect($user->homeRoute())->with('status', 'directorio-restringido');
    }
}

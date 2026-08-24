<?php

namespace App\Http\Controllers;

use App\Enums\EstadoUsuario;
use App\Enums\RolUsuario;
use App\Models\AuditLog;
use App\Models\BenefitRequest;
use App\Models\Service;
use App\Models\User;
use App\Notifications\NuevoRespaldoNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * "Mis servicios" (Punto 5-A): el usuario ve los servicios que su membresía
 * incluye (catálogo × plan) y los solicita; el admin aprueba/agenda en el panel.
 */
class ServiciosController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        // Servicios que incluye su membresía: la propia (si está vigente) Y/O la
        // del/los estudio(s) donde es colaborador activo (el estudio paga y
        // cubre a su equipo — Punto 5-A extendido). Ver User::serviciosIncluidos().
        $servicios = $user->serviciosIncluidos();

        // Última solicitud del usuario por servicio (para mostrar su estado).
        $solicitudes = BenefitRequest::where('user_id', $user->id)
            ->whereNotNull('service_id')
            ->latest()
            ->get()
            ->groupBy('service_id')
            ->map(fn ($grupo) => $grupo->first());

        return view('servicios.index', compact('servicios', 'solicitudes'));
    }

    public function solicitar(Request $request, Service $service): RedirectResponse
    {
        $user = $request->user();

        // Gate: el servicio debe estar incluido en alguno de sus planes (propio
        // o el de un estudio donde es colaborador activo con membresía vigente).
        abort_unless($user->puedeUsarServicio($service), 403);

        $data = $request->validate([
            'note'           => ['nullable', 'string', 'max:1000'],
            'preferred_slot' => ['nullable', 'string', 'max:200'],
        ]);

        // No duplicar una solicitud abierta (pendiente/agendada) del mismo servicio.
        $yaAbierta = BenefitRequest::where('user_id', $user->id)
            ->where('service_id', $service->id)
            ->whereIn('status', [BenefitRequest::STATUS_PENDING, BenefitRequest::STATUS_SCHEDULED])
            ->exists();
        if ($yaAbierta) {
            return back()->with('status', 'servicio-ya-solicitado');
        }

        $solicitud = BenefitRequest::create([
            'user_id'        => $user->id,
            'type'           => BenefitRequest::TYPE_SERVICE,
            'service_id'     => $service->id,
            'note'           => $data['note'] ?? null,
            'preferred_slot' => $data['preferred_slot'] ?? null,
            'status'         => BenefitRequest::STATUS_PENDING,
        ]);
        AuditLog::record($user, $solicitud, 'benefit_requested', new: ['service' => $service->nombre]);

        // Avisar a los admins activos (campana + correo), como en Respaldo.
        $admins = User::query()
            ->where('nivel', RolUsuario::Admin)
            ->where('estado', EstadoUsuario::Activo)
            ->get();
        foreach ($admins as $admin) {
            try {
                $admin->notify(new NuevoRespaldoNotification($solicitud));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return back()->with('status', 'servicio-solicitado');
    }
}

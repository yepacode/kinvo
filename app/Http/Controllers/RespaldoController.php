<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\BenefitRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * H6 · Respaldo del coach: telemedicina y (Plus) fisioterapia.
 * Coach paid solicita; admin gestiona desde Filament.
 */
class RespaldoController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user->esProfesional(), 403);

        // HIGH-15 · El coach cuya membresía expiró CONSERVA acceso a SU
        // historial de respaldos (que es un registro legal de servicios
        // recibidos). Sólo bloqueamos SOLICITAR nuevas cuando no tiene el
        // beneficio activo — eso se hace en `solicitar()`. Aquí el gate
        // solo aplica si NUNCA ha tenido respaldos (para no dejar entrar
        // a un coach que apenas se registró sin membresía).
        $tieneHistorial = BenefitRequest::where('user_id', $user->id)->exists();
        if (! $user->hasBenefit('respaldo_telemed') && ! $tieneHistorial) {
            return redirect()->route('membresias.index')
                ->with('status', 'plan-necesario-respaldo');
        }

        $solicitudes = BenefitRequest::where('user_id', $user->id)
            ->latest()->paginate(15);

        return view('respaldo.index', [
            'solicitudes' => $solicitudes,
            'puedeFisio'  => $user->hasBenefit('respaldo_fisio'),
            // Bandera para que la vista sepa si el botón "Solicitar" está
            // activo o si sólo se muestra el historial en modo lectura.
            'puedeSolicitar' => $user->hasBenefit('respaldo_telemed'),
        ]);
    }

    public function solicitar(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->esProfesional(), 403);

        $tiposDisponibles = [BenefitRequest::TYPE_TELEMEDICINE];
        if ($user->hasBenefit('respaldo_fisio')) {
            $tiposDisponibles[] = BenefitRequest::TYPE_PHYSIO;
        }

        $data = $request->validate([
            'type'           => ['required', Rule::in($tiposDisponibles)],
            'note'           => ['nullable', 'string', 'max:1000'],
            'preferred_slot' => ['nullable', 'string', 'max:200'],
        ]);

        // Doble check por tipo (defensa vs bypass del select del form).
        // LOW-9 · Antes: abort(403) seco perdía el detalle capturado. Ahora
        // redirect claro con status para que el UI muestre por qué no se pudo
        // crear (típicamente porque el plan cambió durante el flujo del form).
        if ($data['type'] === BenefitRequest::TYPE_TELEMEDICINE
            && ! $user->hasBenefit('respaldo_telemed')) {
            return back()->withInput()
                ->with('status', 'plan-cambio-durante-flujo');
        }
        if ($data['type'] === BenefitRequest::TYPE_PHYSIO
            && ! $user->hasBenefit('respaldo_fisio')) {
            return back()->withInput()
                ->with('status', 'plan-cambio-durante-flujo');
        }

        // HIGH-14 · Sin este límite un coach podía crear cientos de
        // solicitudes pendientes y disparar spam a todos los admins. Tope de
        // 3 solicitudes activas del mismo tipo — cuando el admin agenda,
        // cancela o marca completada, el conteo baja y el coach puede pedir
        // otra. Nota: rate limit por request lo hace el throttle:6,1 en ruta.
        $activas = BenefitRequest::where('user_id', $user->id)
            ->where('type', $data['type'])
            ->whereIn('status', [BenefitRequest::STATUS_PENDING, BenefitRequest::STATUS_SCHEDULED])
            ->count();
        if ($activas >= 3) {
            return back()->with('status', 'respaldo-tope-alcanzado');
        }

        $solicitud = BenefitRequest::create([
            'user_id'        => $user->id,
            'type'           => $data['type'],
            'note'           => $data['note'] ?? null,
            'preferred_slot' => $data['preferred_slot'] ?? null,
            'status'         => BenefitRequest::STATUS_PENDING,
        ]);
        AuditLog::record($user, $solicitud, 'benefit_requested', new: ['type' => $data['type']]);

        // M6 · avisar a los admins activos por campana + correo.
        // MED-I3 · try/catch DENTRO del loop: si el 1er admin explota (SMTP
        // rechaza, dirección inválida), los demás igual reciben. Antes el
        // try envolvía TODO el foreach y un fallo cortaba el resto.
        $admins = \App\Models\User::query()
            ->where('nivel', \App\Enums\RolUsuario::Admin)
            ->where('estado', \App\Enums\EstadoUsuario::Activo)
            ->get();
        foreach ($admins as $admin) {
            try {
                $admin->notify(new \App\Notifications\NuevoRespaldoNotification($solicitud));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return redirect()->route('respaldo.index')->with('status', 'respaldo-enviado');
    }
}

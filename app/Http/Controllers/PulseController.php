<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\PulseResponse;
use App\Models\TeamMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * H6 · Encuesta de Pulso Kinvoo.
 * - Coach paid contesta y ve su historial.
 * - Estudio paid ve resultados agregados de los coaches de su equipo.
 */
class PulseController extends Controller
{
    /** Vista para el coach: form + historial. */
    public function coach(Request $request)
    {
        $user = $request->user();
        abort_unless($user->esProfesional(), 403);
        if (! $user->hasBenefit('pulso_contestar')) {
            return redirect()->route('membresias.index')
                ->with('status', 'plan-necesario-pulso');
        }

        $historial = PulseResponse::where('user_id', $user->id)
            ->latest()->paginate(10);

        return view('pulso.coach', ['historial' => $historial]);
    }

    /** Coach guarda su respuesta. */
    public function guardar(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->esProfesional(), 403);
        if (! $user->hasBenefit('pulso_contestar')) {
            abort(403);
        }

        $data = $request->validate([
            'rating'          => ['required', 'integer', 'between:1,5'],
            'answer_energy'   => ['nullable', 'string', 'max:500'],
            'answer_growth'   => ['nullable', 'string', 'max:500'],
            'answer_support'  => ['nullable', 'string', 'max:500'],
        ]);

        // HIGH-11/42 · Anti-spam semanal: 1 respuesta por semana ISO.
        // Sin este guard el coach podía enviar N respuestas por semana y
        // distorsionar el score agregado del estudio.
        $inicioSemana = now()->startOfWeek()->toDateString();
        $yaRespondio = PulseResponse::where('user_id', $user->id)
            ->whereDate('period_start', $inicioSemana)
            ->exists();
        if ($yaRespondio) {
            return redirect()->route('pulso.coach')
                ->with('status', 'pulso-ya-respondido');
        }

        // HIGH-13 · Coach en varios estudios: si tiene N equipos activos
        // (raro pero posible), tomar el MÁS RECIENTE (último aceptado)
        // en vez de un aleatorio del value() sin orderBy.
        // HIGH-31 · Si el coach no está en ningún equipo activo, no le
        // dejamos guardar una respuesta huérfana (contractor_user_id=null
        // rompía los agregados del panel de Pulso).
        $contractorId = TeamMember::where('professional_user_id', $user->id)
            ->where('status', TeamMember::STATUS_ACTIVE)
            ->latest('joined_at')
            ->value('contractor_user_id');
        if (! $contractorId) {
            return redirect()->route('pulso.coach')
                ->with('status', 'pulso-sin-equipo');
        }

        $r = PulseResponse::create([
            'user_id'            => $user->id,
            'contractor_user_id' => $contractorId,
            'rating'             => $data['rating'],
            'answer_energy'      => $data['answer_energy'] ?? null,
            'answer_growth'      => $data['answer_growth'] ?? null,
            'answer_support'     => $data['answer_support'] ?? null,
            'period_start'       => now()->startOfWeek()->toDateString(),
        ]);
        AuditLog::record($user, $r, 'pulse_answered', new: ['rating' => $data['rating']]);

        // HIGH-12 · Notificar al estudio (campana + correo). Sin detalle
        // del coach que respondió — la encuesta es anónima. Solo si hay
        // contractorId (coach realmente en un equipo).
        if ($contractorId) {
            try {
                $estudio = \App\Models\User::find($contractorId);
                $estudio?->notify(new \App\Notifications\PulseRespondidoNotification($r));
            } catch (\Throwable $e) { report($e); }
        }

        return redirect()->route('pulso.coach')->with('status', 'pulso-guardado');
    }

    /** Vista para el estudio: agregados de su equipo. */
    public function estudio(Request $request)
    {
        $user = $request->user();
        abort_unless($user->esContratante(), 403);
        if (! $user->hasBenefit('pulso_ver')) {
            return redirect()->route('membresias.index')
                ->with('status', 'plan-necesario-pulso');
        }

        $q = PulseResponse::where('contractor_user_id', $user->id);

        $promedio = round((float) (clone $q)->avg('rating'), 2);
        $total    = (clone $q)->count();
        $porRating = (clone $q)
            ->selectRaw('rating, COUNT(*) as n')
            ->groupBy('rating')->pluck('n', 'rating')->toArray();

        $ultimas = (clone $q)->with('user')->latest()->take(30)->get();

        return view('pulso.estudio', compact('promedio', 'total', 'porRating', 'ultimas'));
    }
}

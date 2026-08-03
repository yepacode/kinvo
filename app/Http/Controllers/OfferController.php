<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\AuditLog;
use App\Models\Offer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Fase 2 · Bolsa de trabajo (2.10).
 *
 * Rutas:
 *  - GET  /ofertas                       — listado público (privado a directorio)
 *  - GET  /ofertas/{offer:slug}          — detalle
 *  - POST /ofertas/{offer:slug}/postular — profesional envía postulación
 *  - GET  /mis-ofertas                   — panel del estudio con sus ofertas
 *  - GET  /mis-postulaciones             — panel del profesional con estados
 */
class OfferController extends Controller
{
    /** Listado con filtros. Solo contratistas con membresía + admin. */
    public function index(Request $request): View
    {
        $q = Offer::query()
            ->where('status', Offer::STATUS_PUBLISHED)
            ->where(function ($q) {
                $q->whereNull('expires_on')->orWhere('expires_on', '>=', today());
            })
            ->with(['contractor', 'discipline', 'location'])
            ->latest('published_at');

        if ($termino = $request->string('q')->toString()) {
            $q->where(function ($qq) use ($termino) {
                $qq->where('title', 'like', "%$termino%")
                   ->orWhere('description', 'like', "%$termino%");
            });
        }
        if ($modalidad = $request->string('modalidad')->toString()) {
            $q->where('modality', $modalidad);
        }

        return view('ofertas.index', ['ofertas' => $q->paginate(12)]);
    }

    /** Detalle de una oferta + form de postulación si aplica. */
    public function show(Request $request, Offer $offer): View
    {
        abort_unless($offer->estaPublicada(), 404);
        $offer->load(['contractor', 'discipline', 'location']);

        $miPostulacion = null;
        if ($user = $request->user()) {
            $miPostulacion = Application::where('offer_id', $offer->id)
                ->where('professional_user_id', $user->id)
                ->first();
        }

        return view('ofertas.show', compact('offer', 'miPostulacion'));
    }

    /** Profesional envía postulación a una oferta. */
    public function postular(Request $request, Offer $offer): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && $user->esProfesional() && $user->estaActivo(), 403);
        abort_unless($offer->estaPublicada(), 404);

        $data = $request->validate([
            'cover_letter' => ['nullable', 'string', 'max:2000'],
        ]);

        $app = Application::firstOrCreate(
            [
                'offer_id' => $offer->id,
                'professional_user_id' => $user->id,
            ],
            [
                'cover_letter' => $data['cover_letter'] ?? null,
                'status' => Application::STATUS_SUBMITTED,
                'status_changed_at' => now(),
            ]
        );

        if ($app->wasRecentlyCreated) {
            AuditLog::record($user, $app, 'submitted');
        }

        return back()->with('status', $app->wasRecentlyCreated ? 'postulacion-enviada' : 'ya-postulaste');
    }

    /** Postulaciones que ENVIÓ el profesional. */
    public function misPostulaciones(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->esProfesional(), 403);

        $postulaciones = Application::where('professional_user_id', $user->id)
            ->with(['offer.contractor', 'offer.location'])
            ->latest()
            ->paginate(15);

        return view('ofertas.mis-postulaciones', compact('postulaciones'));
    }

    /** Ofertas que publicó el estudio + sus postulaciones recibidas. */
    public function misOfertas(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->esContratante(), 403);

        $ofertas = Offer::where('contractor_user_id', $user->id)
            ->withCount('applications')
            ->latest()
            ->paginate(15);

        return view('ofertas.mis-ofertas', compact('ofertas'));
    }

    /** Estudio cambia el estado de una postulación (seen, in_contact, accepted, rejected). */
    public function cambiarEstado(Request $request, Application $application): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->esContratante(), 403);
        abort_unless($application->offer->contractor_user_id === $user->id, 403);

        $data = $request->validate([
            'status' => ['required', Rule::in([
                Application::STATUS_SEEN,
                Application::STATUS_IN_CONTACT,
                Application::STATUS_ACCEPTED,
                Application::STATUS_REJECTED,
            ])],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $application->update([
            'status' => $data['status'],
            'status_changed_at' => now(),
            'notes' => $data['notes'] ?? $application->notes,
        ]);
        AuditLog::record($user, $application, 'status_changed', new: ['status' => $data['status']]);

        return back()->with('status', 'estado-actualizado');
    }
}

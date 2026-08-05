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
            // Notif in-app al estudio (campana). El correo lo maneja el flujo aparte.
            try {
                $offer->contractor?->notify(new \App\Notifications\NuevaPostulacionNotification($app));
            } catch (\Throwable $e) { report($e); }
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

    /** Formulario para crear una nueva oferta (estudio autónomo). */
    public function crear(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->esContratante(), 403);
        $this->autorizarSuscripcionActiva($user);

        return view('ofertas.form', ['oferta' => new Offer()]);
    }

    /** Guarda la nueva oferta del estudio. Queda publicada al instante. */
    public function guardar(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->esContratante(), 403);
        $this->autorizarSuscripcionActiva($user);

        $data = $this->validarOferta($request);
        $data['contractor_user_id'] = $user->id;
        $data['status'] = Offer::STATUS_PUBLISHED;
        $data['published_at'] = now();

        $oferta = Offer::create($data);
        AuditLog::record($user, $oferta, 'oferta_publicada', new: ['title' => $oferta->title]);

        return redirect()->route('ofertas.mis-ofertas')->with('status', 'oferta-creada');
    }

    /** Formulario para editar una oferta propia. */
    public function editar(Request $request, Offer $oferta): View
    {
        $user = $request->user();
        abort_unless($user->esContratante() && $oferta->contractor_user_id === $user->id, 403);
        $this->autorizarSuscripcionActiva($user);

        return view('ofertas.form', compact('oferta'));
    }

    /** Actualiza una oferta propia. */
    public function actualizar(Request $request, Offer $oferta): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->esContratante() && $oferta->contractor_user_id === $user->id, 403);
        $this->autorizarSuscripcionActiva($user);

        $data = $this->validarOferta($request);
        // Permitimos editar todo excepto el dueño; el estatus se maneja aparte.
        $oferta->update($data);
        AuditLog::record($user, $oferta, 'oferta_editada');

        return redirect()->route('ofertas.mis-ofertas')->with('status', 'oferta-actualizada');
    }

    /** Cambia el estatus de una oferta propia (cerrar / reabrir). */
    public function cambiarEstadoOferta(Request $request, Offer $oferta): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->esContratante() && $oferta->contractor_user_id === $user->id, 403);

        $data = $request->validate([
            'status' => ['required', Rule::in([
                Offer::STATUS_DRAFT,
                Offer::STATUS_PUBLISHED,
                Offer::STATUS_CLOSED,
            ])],
        ]);

        $oferta->update([
            'status' => $data['status'],
            'published_at' => $data['status'] === Offer::STATUS_PUBLISHED && ! $oferta->published_at ? now() : $oferta->published_at,
        ]);
        AuditLog::record($user, $oferta, 'oferta_estado_'.$data['status']);

        return back()->with('status', 'oferta-estado-actualizado');
    }

    /** Elimina una oferta propia (soft — la marca como closed). */
    public function eliminar(Request $request, Offer $oferta): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->esContratante() && $oferta->contractor_user_id === $user->id, 403);

        $oferta->update(['status' => Offer::STATUS_CLOSED]);
        AuditLog::record($user, $oferta, 'oferta_cerrada');

        return redirect()->route('ofertas.mis-ofertas')->with('status', 'oferta-cerrada');
    }

    /** Reglas de validación compartidas entre crear/actualizar. */
    private function validarOferta(Request $request): array
    {
        return $request->validate([
            'title'            => ['required', 'string', 'max:180'],
            'description'      => ['required', 'string', 'max:5000'],
            'requirements'     => ['nullable', 'string', 'max:3000'],
            'discipline_id'    => ['nullable', 'integer', 'exists:disciplines,id'],
            'location_id'      => ['nullable', 'integer', 'exists:locations,id'],
            'modality'         => ['required', Rule::in(['presencial', 'online', 'hibrido'])],
            'contract_type'    => ['nullable', Rule::in(['full_time', 'part_time', 'freelance'])],
            'salary_min_cents' => ['nullable', 'integer', 'min:0'],
            'salary_max_cents' => ['nullable', 'integer', 'min:0', 'gte:salary_min_cents'],
            'salary_currency'  => ['required', 'string', 'size:3'],
            'salary_period'    => ['required', Rule::in(['hour', 'month', 'year', 'project'])],
            'expires_on'       => ['nullable', 'date', 'after_or_equal:today'],
        ]);
    }

    /** Bloqueo: solo estudios con suscripción activa pueden publicar/editar ofertas. */
    private function autorizarSuscripcionActiva(\App\Models\User $user): void
    {
        // El middleware `membresia.activa` en la ruta ya lo bloquea, pero el
        // controller lo reafirma por si alguien llama al método vía API.
        $vigente = \App\Models\Subscription::where('user_id', $user->id)
            ->whereIn('status', [\App\Models\Subscription::STATUS_ACTIVE, \App\Models\Subscription::STATUS_TRIALING])
            ->where(function ($q) {
                $q->whereNull('current_period_end')
                  ->orWhere('current_period_end', '>=', now());
            })
            ->exists();

        if (! $vigente && ! $user->tieneMembresiaActiva()) {
            abort(redirect()->route('membresias.index')->with('status', 'membresia-requerida'));
        }
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

        // Notif al profesional sobre el cambio de estado.
        try {
            $application->professional?->notify(new \App\Notifications\PostulacionActualizadaNotification($application));
        } catch (\Throwable $e) { report($e); }

        return back()->with('status', 'estado-actualizado');
    }
}

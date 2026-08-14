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

    /** Detalle de una oferta + form de postulación si aplica.
     *  HIGH-29 · El dueño de la oferta y el admin pueden verla siempre,
     *  incluso en DRAFT o CLOSED. Antes 404 rompía el flujo del estudio
     *  cuando revisaba una vacante propia sin publicar. */
    public function show(Request $request, Offer $offer): View
    {
        $user = $request->user();
        $esDueno = $user && $user->id === $offer->contractor_user_id;
        $esAdmin = $user?->esAdmin() === true;
        abort_unless($offer->estaPublicada() || $esDueno || $esAdmin, 404);
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
            // Notif al estudio (campana + correo por HIGH-8/17).
            // MED-I6 · Solo si el estudio está activo — un estudio SUSPENDIDO
            // no debe recibir notificaciones ni correos (spam a alguien que
            // ya no puede actuar). Postulación queda registrada igual.
            $estudio = $offer->contractor;
            if ($estudio && $estudio->estaActivo()) {
                try {
                    $estudio->notify(new \App\Notifications\NuevaPostulacionNotification($app));
                } catch (\Throwable $e) { report($e); }
            }
            return back()->with('status', 'postulacion-enviada');
        }

        // HIGH-45 · Postulación existente: permitir actualizar el cover_letter
        // sin perderlo en silencio. Antes, un segundo POST con cover_letter
        // distinto no cambiaba nada y respondía "ya-postulaste" — el usuario
        // creía haber corregido el texto pero el estudio nunca lo veía. Ahora
        // actualizamos y dejamos rastro en AuditLog para la bitácora legal.
        $nuevaCover = $data['cover_letter'] ?? null;
        if ($nuevaCover !== null && $nuevaCover !== $app->cover_letter) {
            $anterior = $app->cover_letter;
            $app->update(['cover_letter' => $nuevaCover]);
            AuditLog::record($user, $app, 'cover_letter_updated',
                old: ['cover_letter_len' => mb_strlen((string) $anterior)],
                new: ['cover_letter_len' => mb_strlen($nuevaCover)]);
            return back()->with('status', 'postulacion-actualizada');
        }

        return back()->with('status', 'ya-postulaste');
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

        // MED-K4 · Eager-load `applications.professional` para evitar N+1:
        // la vista mis-ofertas.blade.php itera $o->applications()->with('professional')
        // dentro del loop, disparando 1 query por oferta × 1 query por postulación
        // (N×M en total). Cargándolo aquí, todo cae en 2 queries fijas.
        $ofertas = Offer::where('contractor_user_id', $user->id)
            ->withCount('applications')
            ->with(['applications' => fn ($q) => $q->latest()->with('professional')])
            ->latest()
            ->paginate(15);

        return view('ofertas.mis-ofertas', compact('ofertas'));
    }

    /** Formulario para crear una nueva oferta (estudio autónomo).
     *  Matriz: estudio free puede tener 1 activa (60 días). Aquí sólo abrimos
     *  el form si aún no llegó al cupo, para que el free no llene un
     *  formulario que después va a rebotar. */
    public function crear(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->esContratante(), 403);

        if ($upsell = $this->bloqueoPorLimiteFree($user)) {
            return $upsell;
        }

        return view('ofertas.form', ['oferta' => new Offer()]);
    }

    /** Guarda la nueva oferta del estudio. Queda publicada al instante. */
    public function guardar(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->esContratante(), 403);

        // HIGH-30/40 · Race condition: dos tabs abiertos en /nueva podían
        // pasar ambos el `bloqueoPorLimiteFree` (conteo=0 en ambos) y
        // crear 2 ofertas activas violando el cupo del plan free. Solución:
        // bloqueo por-usuario con Cache::lock durante todo el chequeo+create,
        // atómico gracias al SETNX del cache driver.
        $lock = \Illuminate\Support\Facades\Cache::lock('ofertas:guardar:user:'.$user->id, 10);
        if (! $lock->get()) {
            return back()->with('status', 'reintentalo-en-unos-segundos');
        }
        try {
            // H6 · Gate free/paid (petición cliente).
            // Free: 1 vacante activa + expira automáticamente a 60 días.
            // Paid: sin límite y con expiración manual.
            if ($upsell = $this->bloqueoPorLimiteFree($user)) {
                return $upsell;
            }

            $data = $this->validarOferta($request);
            $data['contractor_user_id'] = $user->id;
            $data['status'] = Offer::STATUS_PUBLISHED;
            $data['published_at'] = now();

            // H6 · plan free: forzar expiración a 60 días desde hoy (no editable).
            if (! $user->tieneMembresiaActiva()) {
                $data['expires_on'] = now()->addDays(60)->toDateString();
            }

            $oferta = Offer::create($data);
            AuditLog::record($user, $oferta, 'oferta_publicada', new: ['title' => $oferta->title]);

            return redirect()->route('ofertas.mis-ofertas')->with('status', 'oferta-creada');
        } finally {
            $lock->release();
        }
    }

    /** Formulario para editar una oferta propia.
     *  LOW-13 · defensa en profundidad: además del middleware `cuenta.activa`,
     *  el controller re-checa `estaActivo()` para que un cambio de estado
     *  entre el pipeline y la ejecución (poco probable, pero posible con
     *  cache-lag) no permita al suspendido tocar sus ofertas. */
    public function editar(Request $request, Offer $oferta): View
    {
        $user = $request->user();
        abort_unless($user->esContratante() && $user->estaActivo() && $oferta->contractor_user_id === $user->id, 403);
        // H6 · editar SÍ es libre incluso en free (para que el estudio ajuste
        // su única vacante activa). El límite es sobre el número de activas.

        return view('ofertas.form', compact('oferta'));
    }

    /** Actualiza una oferta propia. */
    public function actualizar(Request $request, Offer $oferta): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->esContratante() && $oferta->contractor_user_id === $user->id, 403);

        $data = $this->validarOferta($request);
        // HIGH-16 · plan free: `expires_on` NO es editable. Preservamos el
        // valor original de la oferta — antes la lógica capeaba con hoy+60
        // pero eso acortaba silenciosamente la vida de la vacante en cada
        // edición (día 30 editas → expires=hoy+60=día 90, no día 60 original).
        // Fix estructural: el free simplemente no puede tocar ese campo.
        if (! $user->tieneMembresiaActiva()) {
            $data['expires_on'] = $oferta->expires_on?->toDateString();
        }
        $oferta->update($data);
        AuditLog::record($user, $oferta, 'oferta_editada');

        return redirect()->route('ofertas.mis-ofertas')->with('status', 'oferta-actualizada');
    }

    /** Cambia el estatus de una oferta propia (cerrar / reabrir).
     *  Refactor CRITICAL-1: reactivar una oferta cerrada saltaba el límite
     *  del plan free porque el conteo de `activas` sólo se aplicaba en crear/
     *  guardar. Ahora si la transición es hacia PUBLISHED, revalidamos el
     *  cupo excluyendo la propia oferta del conteo. */
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

        // Reactivar (→ published): mismo gate que crear/guardar en free.
        if ($data['status'] === Offer::STATUS_PUBLISHED
            && ($upsell = $this->bloqueoPorLimiteFree($user, excluir: $oferta))) {
            return $upsell;
        }

        $update = [
            'status' => $data['status'],
            'published_at' => $data['status'] === Offer::STATUS_PUBLISHED && ! $oferta->published_at ? now() : $oferta->published_at,
        ];
        // Al reactivar un free, refrescar expires_on a hoy+60d si venció.
        if ($data['status'] === Offer::STATUS_PUBLISHED && ! $user->tieneMembresiaActiva()) {
            $tope = now()->addDays(60)->toDateString();
            if (empty($oferta->expires_on) || $oferta->expires_on->toDateString() < today()->toDateString()) {
                $update['expires_on'] = $tope;
            }
        }
        $oferta->update($update);
        AuditLog::record($user, $oferta, 'oferta_estado_'.$data['status']);

        return back()->with('status', 'oferta-estado-actualizado');
    }

    /** Elimina una oferta propia (soft — la marca como closed).
     *  MED-H4 · Al cerrar una oferta también:
     *   - las postulaciones abiertas (submitted/seen/in_contact) pasan a
     *     "rejected" con nota "vacante cerrada por el estudio" — así el
     *     coach ve un estado claro en Mis postulaciones.
     *   - se notifica a cada postulante del cierre para que no siga esperando.
     */
    public function eliminar(Request $request, Offer $oferta): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->esContratante() && $oferta->contractor_user_id === $user->id, 403);

        $oferta->update(['status' => Offer::STATUS_CLOSED]);
        AuditLog::record($user, $oferta, 'oferta_cerrada');

        // Cerrar postulaciones abiertas + avisar a los postulantes.
        $abiertas = Application::where('offer_id', $oferta->id)
            ->whereIn('status', [
                Application::STATUS_SUBMITTED,
                Application::STATUS_SEEN,
                Application::STATUS_IN_CONTACT,
            ])
            ->with('professional')
            ->get();
        foreach ($abiertas as $app) {
            $app->update([
                'status' => Application::STATUS_REJECTED,
                'status_changed_at' => now(),
                'notes' => trim(($app->notes ?? '')."\n[".now()->toDateString().'] Vacante cerrada por el estudio.'),
            ]);
            try {
                $app->professional?->notify(new \App\Notifications\PostulacionActualizadaNotification($app));
            } catch (\Throwable $e) { report($e); }
        }

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
            // H3 · petición cliente: campos de ubicación específica + días/horarios
            // igual que el perfil profesional (para poder emparejar).
            'colonia'          => ['nullable', 'string', 'max:120'],
            'availability'     => ['nullable', 'array'],
            'availability.*'   => [Rule::in(Offer::slotsDisponibilidad())],
            // H3 · rangos horarios exactos + notas libres.
            // Cada rango: {day: lun|mar|..., from: HH:MM, to: HH:MM}.
            'schedule_ranges'          => ['nullable', 'array', 'max:20'],
            'schedule_ranges.*.day'    => ['required_with:schedule_ranges', Rule::in(array_keys(\App\Models\ProfessionalProfile::DIAS))],
            'schedule_ranges.*.from'   => ['required_with:schedule_ranges', 'date_format:H:i'],
            'schedule_ranges.*.to'     => ['required_with:schedule_ranges', 'date_format:H:i', 'after:schedule_ranges.*.from'],
            'schedule_notes'           => ['nullable', 'string', 'max:1000'],
            'modality'         => ['required', Rule::in(['presencial', 'online', 'hibrido'])],
            'contract_type'    => ['nullable', Rule::in(['full_time', 'part_time', 'freelance'])],
            'salary_min_cents' => ['nullable', 'integer', 'min:0'],
            // MED-J6 · `gte:salary_min_cents` en Laravel se salta la regla
            // cuando el `min` no viene en la request (regla condicional).
            // Con `required_with` obligamos que si viene el max, el min
            // también, y así la comparación `gte` sí protege.
            'salary_max_cents' => ['nullable', 'integer', 'min:0', 'required_with:salary_min_cents', 'gte:salary_min_cents'],
            'salary_currency'  => ['required', 'string', 'size:3'],
            'salary_period'    => ['required', Rule::in(['hour', 'month', 'year', 'project'])],
            'expires_on'       => ['nullable', 'date', 'after_or_equal:today'],
        ]);
    }

    /** Enforce del gate free "1 vacante activa" reusable desde crear, guardar
     *  y cambiarEstadoOferta. Cuenta activas del usuario (opcionalmente
     *  excluyendo una oferta específica, útil para reactivación). Devuelve
     *  el RedirectResponse de upsell o null si puede continuar.
     *  CRITICAL-1: sin este helper unificado, cambiarEstadoOferta permitía
     *  a un estudio free reactivar N ofertas cerradas rompiendo la matriz. */
    private function bloqueoPorLimiteFree(\App\Models\User $user, ?Offer $excluir = null): ?RedirectResponse
    {
        if ($user->tieneMembresiaActiva()) {
            return null;
        }
        $query = Offer::where('contractor_user_id', $user->id)
            ->where('status', Offer::STATUS_PUBLISHED)
            ->where(function ($q) {
                $q->whereNull('expires_on')->orWhere('expires_on', '>=', today());
            });
        if ($excluir) {
            $query->where('id', '!=', $excluir->id);
        }
        if ($query->count() >= 1) {
            return redirect()->route('membresias.index')
                ->with('status', 'plan-necesario-mas-vacantes');
        }
        return null;
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
            // Fix B2 (petición cliente): flash específico para que sepa
            // que no es un "error", es que necesita plan activo.
            abort(redirect()->route('membresias.index')->with('status', 'plan-necesario-ofertas'));
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

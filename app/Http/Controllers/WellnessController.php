<?php

namespace App\Http\Controllers;

use App\Models\WellnessEntry;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Fase 2 · Expediente de cuidado (2.11).
 * El coach ve su timeline; el admin lo alimenta desde Filament.
 */
class WellnessController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user->esProfesional(), 403);
        // Matriz: expediente propio SOLO coach con plan (solo lectura).
        if (! $user->hasBenefit('expediente_propio')) {
            return redirect()->route('membresias.index')
                ->with('status', 'plan-necesario-expediente');
        }

        // Rediseño (petición cliente ago-2026): 4 tarjetas con estado + lista
        // de "Charlas a las que has asistido".
        $estudio = \App\Models\TeamMember::where('professional_user_id', $user->id)
            ->where('status', \App\Models\TeamMember::STATUS_ACTIVE)
            ->with('contractor.companyProfile')
            ->latest('joined_at')->first()?->contractor;
        $nombreEstudio = $estudio?->companyProfile?->company_name ?? $estudio?->name;

        // LOW-8 · Filtrar registros SIN occurred_on (fecha desconocida) para
        // que el "vigente" muestre la póliza más reciente CON fecha, evitando
        // que un registro histórico sin fecha se cuele arriba en la lista.
        $polizaVigente = WellnessEntry::where('professional_user_id', $user->id)
            ->where('type', WellnessEntry::TYPE_INSURANCE)
            ->whereNotNull('occurred_on')
            ->where(function ($q) {
                $q->whereNull('valid_until')->orWhere('valid_until', '>=', today());
            })
            ->latest('occurred_on')->first();

        $desarrolloEsteMes = \App\Models\ContentView::where('user_id', $user->id)
            ->where('viewed_at', '>=', now()->startOfMonth())->count();

        // Feedback Karla 21-sep · "Requerimiento del apagador":
        // los 4 beneficios de la membresía Esencial (Telemedicina, Seguro de
        // vida, Bolsa de trabajo, Desarrollo) se muestran con el estado que el
        // admin prendió manualmente. Sin agenda, sin lógica automática:
        // Activo (prendido) o Pendiente (apagado / nunca tocado).
        $estadosBeneficios = $user->benefitStates()->get()
            ->keyBy(fn ($e) => $e->benefit_key->value);
        $beneficios = [];
        foreach (\App\Enums\BenefitKey::todos() as $k) {
            $activo = (bool) ($estadosBeneficios->get($k->value)?->activo);
            $beneficios[$k->value] = [
                'titulo'      => $k->icono().' '.__($k->label()),
                'subtitulo'   => $activo
                    ? ($k->proveedor()
                        ? __('Vía :proveedor', ['proveedor' => $k->proveedor()])
                        : __('Kinvoo'))
                    : __($k->copyPendiente()),
                'pilar'       => $k->pilar(),
                'activo'      => $activo,
                'badge'       => $activo ? __('Activo') : __('Pendiente'),
                'badgeColor'  => $activo ? 'success' : 'gray',
            ];
        }

        // Charlas y capacitaciones a las que ha asistido:
        //   1) WellnessEntry type=talk (cargadas por admin)
        //   2) ContentView cuyo ContentItem sea video/audio/documento (contenido de desarrollo).
        // Se unifican y ordenan por fecha desc.
        $charlasWellness = WellnessEntry::where('professional_user_id', $user->id)
            ->where('type', WellnessEntry::TYPE_TALK)
            ->latest('occurred_on')->take(20)->get()
            ->map(fn ($e) => (object) [
                'titulo' => $e->notes ?: __('Charla'),
                'fecha'  => $e->occurred_on,
            ]);
        $charlasContenido = \App\Models\ContentView::where('user_id', $user->id)
            ->with('contentItem')
            ->latest('viewed_at')->take(20)->get()
            ->map(fn ($v) => (object) [
                'titulo' => $v->contentItem?->title ?? __('Contenido'),
                'fecha'  => $v->viewed_at,
            ]);
        // Bug: `merge()` de una Eloquent Collection sobre objetos stdClass llama
        // getKey() en cada item (no son modelos) → 500 en cuanto el coach tiene
        // vistas de contenido. Fusionamos como colección BASE (sobre arrays).
        $charlas = collect($charlasWellness->all())
            ->merge($charlasContenido->all())
            ->sortByDesc('fecha')->take(15)->values();

        // Feedback Karla 27-ago: el "Expediente" absorbe "Mis servicios" —
        // el catálogo del plan del coach se muestra aquí abajo. Vive como
        // sección dentro de la misma vista (no se creó ruta nueva).
        $misServicios = $user->serviciosIncluidos();

        return view('expediente.index', [
            'beneficios' => $beneficios,
            'charlas' => $charlas,
            'misServicios' => $misServicios,
            'comparteExpediente' => (bool) $user->comparte_expediente,
            'nombreEstudio' => $nombreEstudio,
        ]);
    }

    /**
     * Punto 12 · el coach elige si su expediente de cuidado se comparte con su
     * estudio. Si lo apaga, sus registros de bienestar NO se reflejan en el
     * panel de bienestar del estudio (ver TeamController::index).
     */
    public function visibilidad(Request $request): \Illuminate\Http\RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->esProfesional(), 403);

        $data = $request->validate(['comparte_expediente' => ['required', 'boolean']]);
        $user->forceFill(['comparte_expediente' => $data['comparte_expediente']])->save();

        return back()->with('status', $data['comparte_expediente']
            ? 'expediente-compartido'
            : 'expediente-privado');
    }
}

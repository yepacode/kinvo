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

        $beneficios = [
            'telemedicina' => [
                'titulo'    => __('Telemedicina'),
                'subtitulo' => $nombreEstudio ? __('Vía :estudio', ['estudio' => $nombreEstudio]) : __('Consultas médicas a distancia'),
                'activo'    => $user->hasBenefit('respaldo_telemed'),
                'badge'     => $user->hasBenefit('respaldo_telemed') ? __('Activo') : __('No incluido'),
                'badgeColor'=> $user->hasBenefit('respaldo_telemed') ? 'success' : 'gray',
            ],
            'fisioterapia' => [
                'titulo'    => __('Fisioterapia'),
                'subtitulo' => __('Disponible en Plus'),
                'activo'    => $user->hasBenefit('respaldo_fisio'),
                'badge'     => $user->hasBenefit('respaldo_fisio') ? __('Activo') : __('No incluido'),
                'badgeColor'=> $user->hasBenefit('respaldo_fisio') ? 'success' : 'gray',
            ],
            'seguro' => [
                'titulo'    => __('Seguro'),
                'subtitulo' => $nombreEstudio ? __('Vía :estudio', ['estudio' => $nombreEstudio]) : __('Póliza personal'),
                'activo'    => (bool) $polizaVigente,
                'badge'     => $polizaVigente ? __('Vigente') : __('Sin póliza'),
                'badgeColor'=> $polizaVigente ? 'success' : 'gray',
            ],
            'desarrollo' => [
                'titulo'    => __('Desarrollo'),
                'subtitulo' => __('Charlas y capacitaciones'),
                'activo'    => $desarrolloEsteMes > 0,
                'badge'     => $desarrolloEsteMes > 0
                    ? __(':n este mes', ['n' => $desarrolloEsteMes])
                    : __('0 este mes'),
                'badgeColor'=> $desarrolloEsteMes > 0 ? 'info' : 'gray',
            ],
        ];

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

        return view('expediente.index', [
            'beneficios' => $beneficios,
            'charlas' => $charlas,
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

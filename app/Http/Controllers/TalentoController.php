<?php

namespace App\Http\Controllers;

use App\Enums\ModalidadTrabajo;
use App\Models\Certification;
use App\Models\Discipline;
use App\Models\Location;
use App\Models\ProfessionalProfile;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TalentoController extends Controller
{
    /** Buscador público de talento con filtros neutros. */
    public function index(Request $request): View
    {
        $filtros = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'discipline_id' => ['nullable', 'integer', 'exists:disciplines,id'],
            'certification_id' => ['nullable', 'integer', 'exists:certifications,id'],
            'modalidad' => ['nullable', 'string'],
        ]);

        $profiles = ProfessionalProfile::query()
            ->where('is_published', true)
            ->with(['user:id,name', 'location', 'disciplines'])
            ->when($filtros['q'] ?? null, function ($query, $q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('headline', 'like', "%{$q}%")
                        ->orWhere('bio', 'like', "%{$q}%")
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$q}%"));
                });
            })
            ->when($filtros['location_id'] ?? null, fn ($query, $id) => $query->where('location_id', $id))
            ->when($filtros['modalidad'] ?? null, fn ($query, $m) => $query->where('modalidad', $m))
            ->when($filtros['discipline_id'] ?? null, fn ($query, $id) => $query->whereHas('disciplines', fn ($d) => $d->where('disciplines.id', $id)))
            ->when($filtros['certification_id'] ?? null, fn ($query, $id) => $query->whereHas('certifications', fn ($c) => $c->where('certifications.id', $id)))
            ->latest('updated_at')
            ->paginate(12)
            ->withQueryString();

        return view('talento.index', [
            'profiles' => $profiles,
            'filtros' => $filtros,
            'locations' => Location::where('activo', true)->orderBy('ciudad')->get(),
            'disciplines' => Discipline::where('activo', true)->orderBy('nombre')->get(),
            'certifications' => Certification::where('activo', true)->orderBy('nombre')->get(),
            'modalidades' => ModalidadTrabajo::opciones(),
        ]);
    }

    /** Vista pública del perfil de un profesional (solo si está publicado). */
    public function show(Request $request, ProfessionalProfile $professionalProfile): View
    {
        abort_unless($professionalProfile->is_published, 404);

        $this->registrarVista($request, $professionalProfile);

        $professionalProfile->load(['disciplines', 'certifications', 'location', 'user']);

        return view('talento.show', ['profile' => $professionalProfile]);
    }

    /** Registra una vista del perfil, sin contar al dueño ni recargas repetidas. */
    private function registrarVista(Request $request, ProfessionalProfile $profile): void
    {
        $viewer = $request->user();

        // El dueño viendo su propio perfil no cuenta.
        if ($viewer && $viewer->id === $profile->user_id) {
            return;
        }

        if ($viewer) {
            $yaHoy = $profile->views()
                ->where('viewer_user_id', $viewer->id)
                ->whereDate('created_at', today())
                ->exists();
            if (! $yaHoy) {
                $profile->views()->create(['viewer_user_id' => $viewer->id]);
            }
        } else {
            // Anónimo: una vez por sesión para no inflar con recargas.
            $key = 'pv_'.$profile->id;
            if (! $request->session()->has($key)) {
                $profile->views()->create(['viewer_user_id' => null]);
                $request->session()->put($key, true);
            }
        }
    }
}

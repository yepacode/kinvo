<?php

namespace App\Http\Controllers;

use App\Enums\ModalidadTrabajo;
use App\Models\Certification;
use App\Models\Discipline;
use App\Models\Location;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfessionalProfileController extends Controller
{
    /** Muestra el formulario de edición del propio perfil profesional. */
    public function edit(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->esProfesional(), 403);

        $profile = $user->professionalProfile()->firstOrCreate([]);
        $profile->load(['disciplines', 'certifications']);

        return view('professional.edit', [
            'profile' => $profile,
            'disciplines' => Discipline::where('activo', true)->orderBy('nombre')->get(),
            'certifications' => Certification::where('activo', true)->orderBy('nombre')->get(),
            'locations' => Location::where('activo', true)->orderBy('ciudad')->get(),
            'modalidades' => ModalidadTrabajo::opciones(),
        ]);
    }

    /** Guarda los cambios del propio perfil profesional. */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->esProfesional(), 403);

        $profile = $user->professionalProfile()->firstOrCreate([]);

        $data = $request->validate([
            'headline' => ['nullable', 'string', 'max:120'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'years_experience' => ['nullable', 'integer', 'min:0', 'max:70'],
            'modalidad' => ['nullable', Rule::in(array_column(ModalidadTrabajo::cases(), 'value'))],
            'location_id' => ['nullable', 'exists:locations,id'],
            'phone' => ['nullable', 'string', 'max:40'],
            'instagram' => ['nullable', 'string', 'max:120'],
            'tiktok' => ['nullable', 'string', 'max:120'],
            'web' => ['nullable', 'url', 'max:200'],
            'disciplines' => ['array'],
            'disciplines.*' => ['exists:disciplines,id'],
            'certifications' => ['array'],
            'certifications.*' => ['exists:certifications,id'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        if ($request->hasFile('photo')) {
            if ($profile->photo_path) {
                Storage::disk('public')->delete($profile->photo_path);
            }
            $data['photo_path'] = $request->file('photo')->store('perfiles', 'public');
        }

        $profile->fill([
            'headline' => $data['headline'] ?? null,
            'bio' => $data['bio'] ?? null,
            'years_experience' => $data['years_experience'] ?? null,
            'modalidad' => $data['modalidad'] ?? null,
            'location_id' => $data['location_id'] ?? null,
            'phone' => $data['phone'] ?? null,
            'socials' => array_filter([
                'instagram' => $data['instagram'] ?? null,
                'tiktok' => $data['tiktok'] ?? null,
                'web' => $data['web'] ?? null,
            ]),
            'is_published' => (bool) ($data['is_published'] ?? false),
        ]);

        if (isset($data['photo_path'])) {
            $profile->photo_path = $data['photo_path'];
        }

        $profile->save();

        $profile->disciplines()->sync($data['disciplines'] ?? []);
        $profile->certifications()->sync($data['certifications'] ?? []);

        return redirect()
            ->route('professional.profile.edit')
            ->with('status', 'perfil-actualizado');
    }
}

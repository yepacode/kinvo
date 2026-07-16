<?php

namespace App\Http\Controllers;

use App\Enums\ModalidadTrabajo;
use App\Models\Discipline;
use App\Models\Location;
use App\Models\ProfessionalProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfessionalProfileController extends Controller
{
    /** Paso 1 del wizard: pantalla de bienvenida. */
    public function bienvenida(Request $request): View
    {
        abort_unless($request->user()->esProfesional(), 403);

        return view('professional.bienvenida');
    }

    /** Paso 3 del wizard: confirmación tras guardar el perfil. */
    public function enviado(Request $request): View
    {
        abort_unless($request->user()->esProfesional(), 403);

        $profile = $request->user()->professionalProfile()->firstOrCreate([]);

        return view('professional.enviado', ['profile' => $profile]);
    }

    /** Muestra el formulario de edición del propio perfil profesional. */
    public function edit(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->esProfesional(), 403);

        $profile = $user->professionalProfile()->firstOrCreate([]);
        $profile->load(['disciplines']);

        return view('professional.edit', [
            'profile' => $profile,
            'disciplines' => Discipline::where('activo', true)->orderBy('nombre')->get(),
            'locations' => Location::where('activo', true)->orderBy('ciudad')->get(),
            'modalidades' => ModalidadTrabajo::opciones(),
        ]);
    }

    /** Descarga del adjunto privado de certificaciones (solo admin, desde el panel). */
    public function certificacion(Request $request, ProfessionalProfile $professionalProfile)
    {
        abort_unless($request->user()?->esAdmin() && $request->user()->estaActivo(), 403);
        abort_unless(
            $professionalProfile->certification_file_path
            && Storage::disk('local')->exists($professionalProfile->certification_file_path),
            404
        );

        return Storage::disk('local')->download($professionalProfile->certification_file_path);
    }

    /** Guarda los cambios del propio perfil profesional. */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->esProfesional(), 403);

        $profile = $user->professionalProfile()->firstOrCreate([]);

        $mayoriaEdad = now()->subYears(18)->toDateString();

        $data = $request->validate([
            'full_name' => ['nullable', 'string', 'max:150'],
            'headline' => ['nullable', 'string', 'max:120'],
            'birthdate' => ['nullable', 'date', 'after:1920-01-01', 'before_or_equal:'.$mayoriaEdad],
            'bio' => ['nullable', 'string', 'max:2000'],
            'years_experience' => ['nullable', 'integer', 'min:0', 'max:70'],
            'modalidad' => ['nullable', Rule::in(array_column(ModalidadTrabajo::cases(), 'value'))],
            'availability' => ['array'],
            'availability.*' => [Rule::in(ProfessionalProfile::slotsDisponibilidad())],
            'languages' => ['array'],
            'languages.*' => [Rule::in(array_keys(ProfessionalProfile::IDIOMAS))],
            'location_id' => ['nullable', Rule::exists('locations', 'id')->where('activo', true)],
            'colonia' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:40', 'regex:/^[\d\s()+.\-]{6,40}$/'],
            'certifications_text' => ['nullable', 'string', 'max:2000'],
            'certification_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
            'media_url' => ['nullable', 'url:http,https', 'max:300'],
            'instagram' => ['nullable', 'string', 'max:120', 'regex:/^[@\w.\-\/:?=&%~#]+$/u'],
            'tiktok' => ['nullable', 'string', 'max:120', 'regex:/^[@\w.\-\/:?=&%~#]+$/u'],
            'web' => ['nullable', 'url:http,https', 'max:200'],
            'disciplines' => ['array'],
            'disciplines.*' => [Rule::exists('disciplines', 'id')->where('activo', true)],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=4000,max_height=4000'],
            'remove_photo' => ['nullable', 'boolean'],
            'remove_certification_file' => ['nullable', 'boolean'],
        ], [
            'birthdate.before_or_equal' => 'Debes ser mayor de 18 años para registrarte.',
            'phone.regex' => 'El teléfono solo puede tener números, espacios y los signos + ( ) - .',
            'instagram.regex' => 'Usuario o enlace de Instagram no válido.',
            'tiktok.regex' => 'Usuario o enlace de TikTok no válido.',
        ]);

        if ($request->hasFile('photo')) {
            if ($profile->photo_path) {
                Storage::disk('public')->delete($profile->photo_path);
            }
            $data['photo_path'] = $request->file('photo')->store('perfiles', 'public');
        }

        // El adjunto de certificaciones es PRIVADO: disco local (no accesible por web),
        // solo el admin lo descarga desde el panel.
        if ($request->hasFile('certification_file')) {
            if ($profile->certification_file_path) {
                Storage::disk('local')->delete($profile->certification_file_path);
            }
            $data['certification_file_path'] = $request->file('certification_file')
                ->store('certificaciones', 'local');
        }

        $profile->fill([
            'full_name' => $data['full_name'] ?? null,
            'headline' => $data['headline'] ?? null,
            'birthdate' => $data['birthdate'] ?? null,
            'bio' => $data['bio'] ?? null,
            'years_experience' => $data['years_experience'] ?? null,
            'modalidad' => $data['modalidad'] ?? null,
            'availability' => array_values($data['availability'] ?? []),
            'languages' => array_values($data['languages'] ?? []),
            'certifications_text' => $data['certifications_text'] ?? null,
            'media_url' => $data['media_url'] ?? null,
            'location_id' => $data['location_id'] ?? null,
            'colonia' => $data['colonia'] ?? null,
            'phone' => $data['phone'] ?? null,
            'socials' => array_filter([
                'instagram' => $data['instagram'] ?? null,
                'tiktok' => $data['tiktok'] ?? null,
                'web' => $data['web'] ?? null,
            ]),
            // is_published NO lo controla el usuario: lo publica el admin al aprobar.
        ]);

        if (isset($data['photo_path'])) {
            $profile->photo_path = $data['photo_path'];
        }
        if (isset($data['certification_file_path'])) {
            $profile->certification_file_path = $data['certification_file_path'];
        }

        // Eliminar foto/adjunto si se marcó la casilla (solo si no se subió uno nuevo).
        if (! $request->hasFile('photo') && $request->boolean('remove_photo') && $profile->photo_path) {
            Storage::disk('public')->delete($profile->photo_path);
            $profile->photo_path = null;
        }
        if (! $request->hasFile('certification_file') && $request->boolean('remove_certification_file') && $profile->certification_file_path) {
            Storage::disk('local')->delete($profile->certification_file_path);
            $profile->certification_file_path = null;
        }

        $profile->save();

        $profile->disciplines()->sync($data['disciplines'] ?? []);

        // Paso 3: avanza a la confirmación (arregla la "página estática" al guardar).
        return redirect()->route('professional.enviado');
    }
}

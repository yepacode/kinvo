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
    use \App\Http\Controllers\Concerns\PersistsUploadedFile;

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
        abort_if($user->estaSuspendido(), 403);

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
        abort_if($user->estaSuspendido(), 403);

        $profile = $user->professionalProfile()->firstOrCreate([]);

        $mayoriaEdad = now()->subYears(18)->toDateString();

        // Fix B1 (petición cliente): al fallar validación, la foto y el
        // archivo de certificación se perdían del input al reintentar.
        // Los re-inyectamos desde tmp de sesión si existen.
        $this->restaurarArchivoTemporal($request, 'photo');
        $this->restaurarArchivoTemporal($request, 'certification_file');

        // 2026-08-06 · Petición de la clienta (Marian):
        // "Todos los campos obligatorios EXCEPTO contenido multimedia y
        // redes sociales/web". Motivo: se estaban creando cuentas sin
        // llenar el perfil (solo correo).
        // Excepciones opcionales: media_url, media_file, instagram, tiktok, web,
        // + los toggles `remove_*` que son helpers de UI.
        // Foto: opcional si ya existe en BD O si viene un file nuevo/tmp.
        $fotoRequerida = ($profile->photo_path || $request->hasFile('photo')) ? 'nullable' : 'required';
        $certRequerida = ($profile->certification_file_path || filled($profile->certifications_text))
            ? 'nullable' : 'required_without:certifications_text';

        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'headline' => ['required', 'string', 'max:120'],
            'birthdate' => ['required', 'date', 'after:1920-01-01', 'before_or_equal:'.$mayoriaEdad],
            'bio' => ['required', 'string', 'min:20', 'max:2000'],
            'years_experience' => ['required', 'integer', 'min:0', 'max:70'],
            'modalidad' => ['required', Rule::in(array_column(ModalidadTrabajo::cases(), 'value'))],
            'availability' => ['required', 'array', 'min:1'],
            'availability.*' => [Rule::in(ProfessionalProfile::slotsDisponibilidad())],
            'languages' => ['required', 'array', 'min:1'],
            'languages.*' => [Rule::in(array_keys(ProfessionalProfile::IDIOMAS))],
            'location_id' => ['required', Rule::exists('locations', 'id')->where('activo', true)],
            'colonia' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:40', 'regex:/^[\d\s()+.\-]{6,40}$/'],
            'certifications_text' => ['nullable', 'string', 'max:2000'],
            'certification_file' => [$certRequerida, 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
            // OPCIONALES por decisión de negocio:
            'media_url' => ['nullable', 'url:http,https', 'max:300'],
            // Multi-upload para carrusel (petición cliente):
            'media_files'   => ['nullable', 'array', 'max:20'],
            'media_files.*' => ['file', 'mimes:mp4,webm,mov,m4v,jpg,jpeg,png,webp,gif', 'max:25600'],
            'media_remove'   => ['nullable', 'array'],
            'media_remove.*' => ['integer'],
            'instagram' => ['nullable', 'string', 'max:120', 'regex:/^[@\w.\-\/:?=&%~#]+$/u'],
            'tiktok' => ['nullable', 'string', 'max:120', 'regex:/^[@\w.\-\/:?=&%~#]+$/u'],
            'web' => ['nullable', 'url:http,https', 'max:200'],
            'disciplines' => ['required', 'array', 'min:1'],
            'disciplines.*' => [Rule::exists('disciplines', 'id')->where('activo', true)],
            // Las fotos de móvil pueden superar 2 MB. 5 MB es holgado para HEIC/JPG
            // recientes y sigue siendo razonable de subir en LTE. Las dimensiones
            // (10000×10000 ~ 100 MP) están para bloquear archivos absurdos, no las
            // fotos reales de iPhone/Samsung actuales.
            'photo' => [$fotoRequerida, 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:max_width=10000,max_height=10000'],
            'remove_photo' => ['nullable', 'boolean'],
            'remove_certification_file' => ['nullable', 'boolean'],
        ], [
            'birthdate.before_or_equal' => 'Debes ser mayor de 18 años para registrarte.',
            'phone.regex' => 'El teléfono solo puede tener números, espacios y los signos + ( ) - .',
            'instagram.regex' => 'Usuario o enlace de Instagram no válido.',
            'tiktok.regex' => 'Usuario o enlace de TikTok no válido.',
            'photo.required' => 'La foto de perfil es obligatoria — súbela para completar tu perfil.',
            'certification_file.required_without' => 'Sube al menos una certificación (archivo o descripción).',
            'availability.required' => 'Selecciona al menos un horario de disponibilidad.',
            'languages.required' => 'Selecciona al menos un idioma que hables.',
            'disciplines.required' => 'Selecciona al menos una disciplina que enseñes.',
            'bio.min' => 'Cuéntanos un poco más sobre ti (mínimo 20 caracteres).',
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

        // Multi-upload de carrusel se procesa DESPUÉS del profile->save().

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

        $wasDirtyKeys = array_keys($profile->getDirty());
        $profile->save();

        // M8 · bitácora legal de cambios en el perfil profesional.
        if (! empty($wasDirtyKeys)) {
            \App\Models\AuditLog::record($user, $profile, 'professional_profile_updated', new: [
                'campos' => $wasDirtyKeys,
            ]);
        }

        // Carrusel multimedia: quitar items marcados + agregar nuevos.
        if ($idsRemover = $request->input('media_remove', [])) {
            $profile->mediaItems()->whereIn('id', $idsRemover)->get()->each->delete();
        }
        foreach ($request->file('media_files', []) as $file) {
            if (! $file) continue;
            $tipo = str_starts_with($file->getMimeType() ?? '', 'video/') ? 'video' : 'image';
            $profile->mediaItems()->create([
                'path'       => $file->store('multimedia/profesional', 'public'),
                'type'       => $tipo,
                'sort_order' => ($profile->mediaItems()->max('sort_order') ?? 0) + 1,
            ]);
        }

        $profile->disciplines()->sync($data['disciplines'] ?? []);

        // Fix B1: limpia los tmp files tras guardar exitoso.
        $this->limpiarArchivoTemporal($request, 'photo');
        $this->limpiarArchivoTemporal($request, 'certification_file');

        // Paso 3: avanza a la confirmación (arregla la "página estática" al guardar).
        return redirect()->route('professional.enviado');
    }
}

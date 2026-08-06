<?php

namespace App\Http\Controllers;

use App\Enums\EstadoUsuario;
use App\Enums\RolUsuario;
use App\Models\CompanyProfile;
use App\Models\User;
use App\Notifications\PerfilEmpresaEnviadoNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CompanyProfileController extends Controller
{
    /** Página pública del estudio (solo si el dueño está activo/aprobado). */
    public function show(CompanyProfile $companyProfile): View
    {
        abort_unless($companyProfile->esVisiblePublicamente(), 404);

        $companyProfile->load(['user', 'location']);

        return view('estudio.show', ['profile' => $companyProfile]);
    }

    /** Paso 1 del wizard: pantalla de bienvenida del estudio. */
    public function bienvenida(Request $request): View
    {
        abort_unless($request->user()->esContratante(), 403);

        return view('company.bienvenida');
    }

    /** Paso 3 del wizard: confirmación tras guardar el perfil del estudio. */
    public function enviado(Request $request): View
    {
        abort_unless($request->user()->esContratante(), 403);

        return view('company.enviado');
    }

    /** Formulario de edición del perfil de empresa del contratante. */
    public function edit(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->esContratante(), 403);
        abort_if($user->estaSuspendido(), 403);

        // Buscar SOLO por user_id (la relación ya lo restringe); company_name solo
        // como valor por defecto al crear. Si se buscara por company_name, tras un
        // rename el firstOrCreate intentaría INSERT y violaría el UNIQUE de user_id.
        $profile = $user->companyProfile()->firstOrCreate([], [
            'company_name' => $user->name,
        ]);

        return view('company.edit', [
            'profile' => $profile,
            'estados' => CompanyProfile::ESTADOS_MX,
        ]);
    }

    /** Guarda los cambios del perfil de empresa. */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->esContratante(), 403);
        abort_if($user->estaSuspendido(), 403);

        $profile = $user->companyProfile()->firstOrCreate([], [
            'company_name' => $user->name,
        ]);

        // 2026-08-06 · Petición de la clienta (Marian):
        // "Todos los campos obligatorios EXCEPTO contenido multimedia y web".
        // Los estudios que solo dejan su correo bloquean el flujo.
        // Excepciones opcionales: media_url, media_file, website (redes/web).
        // El logo es opcional solo si ya existe en el perfil.
        $logoRequerido = $profile->logo_path ? 'nullable' : 'required';

        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:150'],
            'disciplines_text' => ['required', 'string', 'max:300'],
            'description' => ['required', 'string', 'min:20', 'max:2000'],
            // OPCIONAL por decisión de negocio (redes/web):
            'website' => ['nullable', 'url:http,https', 'max:200'],
            'estado' => ['required', Rule::in(CompanyProfile::ESTADOS_MX)],
            'address' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'digits_between:4,5'],
            'colonia' => ['required', 'string', 'max:120'],
            'show_address' => ['nullable', 'boolean'],
            'contact_name' => ['required', 'string', 'max:150'],
            'contact_phone' => ['required', 'string', 'max:40', 'regex:/^[\d\s()+.\-]{6,40}$/'],
            'contact_email' => ['required', 'email', 'max:150'],
            // OPCIONAL por decisión de negocio (multimedia):
            'media_url' => ['nullable', 'url:http,https', 'max:300'],
            'media_file' => ['nullable', 'file', 'mimes:mp4,webm,mov,m4v,jpg,jpeg,png,webp,gif', 'max:25600'],
            'remove_media_file' => ['nullable', 'boolean'],
            // Mismo criterio que la foto de perfil: hasta 5 MB y bloqueo de imágenes absurdas (100 MP).
            'logo' => [$logoRequerido, 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:max_width=10000,max_height=10000'],
        ], [
            'contact_phone.regex' => 'El teléfono solo puede tener números, espacios y los signos + ( ) - .',
            'postal_code.digits_between' => 'El código postal debe tener 4 o 5 dígitos.',
            'logo.required' => 'El logo del estudio es obligatorio — súbelo para completar tu perfil.',
            'description.min' => 'Cuéntanos un poco más sobre tu estudio (mínimo 20 caracteres).',
            'disciplines_text.required' => 'Indica al menos una disciplina que se practica en tu estudio.',
        ]);

        if ($request->hasFile('logo')) {
            if ($profile->logo_path) {
                Storage::disk('public')->delete($profile->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store('empresas', 'public');
        }

        if ($request->hasFile('media_file')) {
            if ($profile->media_path) {
                Storage::disk('public')->delete($profile->media_path);
            }
            $file = $request->file('media_file');
            $data['media_path'] = $file->store('multimedia/estudio', 'public');
            $data['media_type'] = str_starts_with($file->getMimeType() ?? '', 'video/') ? 'video' : 'image';
        }

        $profile->fill([
            'company_name' => $data['company_name'],
            'disciplines_text' => $data['disciplines_text'] ?? null,
            'description' => $data['description'] ?? null,
            'website' => $data['website'] ?? null,
            'estado' => $data['estado'] ?? null,
            'address' => $data['address'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
            'colonia' => $data['colonia'] ?? null,
            'show_address' => (bool) ($data['show_address'] ?? false),
            'contact_name' => $data['contact_name'] ?? null,
            'contact_phone' => $data['contact_phone'] ?? null,
            'contact_email' => $data['contact_email'] ?? null,
            'media_url' => $data['media_url'] ?? null,
        ]);

        if (isset($data['logo_path'])) {
            $profile->logo_path = $data['logo_path'];
        }
        if (isset($data['media_path'])) {
            $profile->media_path = $data['media_path'];
            $profile->media_type = $data['media_type'];
        }

        if (! $request->hasFile('media_file') && $request->boolean('remove_media_file') && $profile->media_path) {
            Storage::disk('public')->delete($profile->media_path);
            $profile->media_path = null;
            $profile->media_type = null;
        }

        $profile->save();

        // Doble aprobación del contratista (Flujo 22-jul del cliente): si el
        // usuario aún está en PerfilPendiente, avisamos a los admins de la 2ª
        // revisión. El correo es opcional (queued dentro de la Notification);
        // envolvemos en try/catch para que un fallo NO rompa el flujo del usuario.
        if ($user->tienePerfilPendiente()) {
            try {
                $admins = User::query()
                    ->where('nivel', RolUsuario::Admin)
                    ->where('estado', EstadoUsuario::Activo)
                    ->get();
                foreach ($admins as $admin) {
                    $admin->notify(new PerfilEmpresaEnviadoNotification($user));
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        // Paso 3: avanza a la confirmación (arregla la "página estática" al guardar).
        return redirect()->route('company.enviado');
    }
}

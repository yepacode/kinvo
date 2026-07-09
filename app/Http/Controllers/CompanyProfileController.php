<?php

namespace App\Http\Controllers;

use App\Models\CompanyProfile;
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

    /** Formulario de edición del perfil de empresa del contratante. */
    public function edit(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->esContratante(), 403);

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

        $profile = $user->companyProfile()->firstOrCreate([], [
            'company_name' => $user->name,
        ]);

        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:150'],
            'disciplines_text' => ['nullable', 'string', 'max:300'],
            'description' => ['nullable', 'string', 'max:2000'],
            'website' => ['nullable', 'url:http,https', 'max:200'],
            'estado' => ['nullable', Rule::in(CompanyProfile::ESTADOS_MX)],
            'address' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'digits_between:4,5'],
            'contact_name' => ['nullable', 'string', 'max:150'],
            'contact_phone' => ['nullable', 'string', 'max:40', 'regex:/^[\d\s()+.\-]{6,40}$/'],
            'contact_email' => ['nullable', 'email', 'max:150'],
            'media_url' => ['nullable', 'url:http,https', 'max:300'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=4000,max_height=4000'],
        ], [
            'contact_phone.regex' => 'El teléfono solo puede tener números, espacios y los signos + ( ) - .',
            'postal_code.digits_between' => 'El código postal debe tener 4 o 5 dígitos.',
        ]);

        if ($request->hasFile('logo')) {
            if ($profile->logo_path) {
                Storage::disk('public')->delete($profile->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store('empresas', 'public');
        }

        $profile->fill([
            'company_name' => $data['company_name'],
            'disciplines_text' => $data['disciplines_text'] ?? null,
            'description' => $data['description'] ?? null,
            'website' => $data['website'] ?? null,
            'estado' => $data['estado'] ?? null,
            'address' => $data['address'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
            'contact_name' => $data['contact_name'] ?? null,
            'contact_phone' => $data['contact_phone'] ?? null,
            'contact_email' => $data['contact_email'] ?? null,
            'media_url' => $data['media_url'] ?? null,
        ]);

        if (isset($data['logo_path'])) {
            $profile->logo_path = $data['logo_path'];
        }

        $profile->save();

        return redirect()
            ->route('company.profile.edit')
            ->with('status', 'empresa-actualizada');
    }
}

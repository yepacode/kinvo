<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CompanyProfileController extends Controller
{
    /** Formulario de edición del perfil de empresa del contratante. */
    public function edit(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->esContratante(), 403);

        $profile = $user->companyProfile()->firstOrCreate([
            'company_name' => $user->name,
        ]);

        return view('company.edit', [
            'profile' => $profile,
            'locations' => Location::where('activo', true)->orderBy('ciudad')->get(),
        ]);
    }

    /** Guarda los cambios del perfil de empresa. */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->esContratante(), 403);

        $profile = $user->companyProfile()->firstOrCreate([
            'company_name' => $user->name,
        ]);

        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:150'],
            'sector' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'website' => ['nullable', 'url', 'max:200'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('logo')) {
            if ($profile->logo_path) {
                Storage::disk('public')->delete($profile->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store('empresas', 'public');
        }

        $profile->fill([
            'company_name' => $data['company_name'],
            'sector' => $data['sector'] ?? null,
            'description' => $data['description'] ?? null,
            'website' => $data['website'] ?? null,
            'location_id' => $data['location_id'] ?? null,
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

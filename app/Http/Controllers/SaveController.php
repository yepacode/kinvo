<?php

namespace App\Http\Controllers;

use App\Models\ProfessionalProfile;
use App\Models\Save;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SaveController extends Controller
{
    /** Guarda o quita de guardados un perfil profesional. */
    public function toggleProfile(Request $request, ProfessionalProfile $professionalProfile): RedirectResponse
    {
        $existing = Save::where('user_id', $request->user()->id)
            ->where('saveable_type', $professionalProfile->getMorphClass())
            ->where('saveable_id', $professionalProfile->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $msg = 'quitado';
        } else {
            Save::create([
                'user_id' => $request->user()->id,
                'saveable_type' => $professionalProfile->getMorphClass(),
                'saveable_id' => $professionalProfile->id,
            ]);
            $msg = 'guardado';
        }

        return back()->with('status', "perfil-$msg");
    }

    /** Lista los perfiles guardados por el usuario. */
    public function index(Request $request): View
    {
        $ids = $request->user()->saves()
            ->where('saveable_type', (new ProfessionalProfile)->getMorphClass())
            ->pluck('saveable_id');

        $profiles = ProfessionalProfile::whereIn('id', $ids)
            ->where('is_published', true)
            ->with(['user:id,name', 'location', 'disciplines'])
            ->latest('updated_at')
            ->paginate(12);

        return view('guardados.index', ['profiles' => $profiles]);
    }
}

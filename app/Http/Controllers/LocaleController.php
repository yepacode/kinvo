<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LocaleController extends Controller
{
    /**
     * Cambia el idioma del sitio (guarda una cookie de 1 año) y vuelve a la vista anterior.
     * Si hay usuario autenticado, persiste su preferencia para que los correos en cola
     * (BienvenidaTalento/Estudio, NuevoContacto) usen el idioma correcto vía HasLocalePreference.
     */
    public function switch(Request $request, string $locale): RedirectResponse
    {
        if (! in_array($locale, SetLocale::SUPPORTED, true)) {
            abort(404);
        }

        if ($user = Auth::user()) {
            $user->forceFill(['locale' => $locale])->save();
        }

        return back()->cookie('locale', $locale, 60 * 24 * 365);
    }
}

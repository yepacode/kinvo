<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    /** Cambia el idioma del sitio (guarda una cookie de 1 año) y vuelve a la vista anterior. */
    public function switch(Request $request, string $locale): RedirectResponse
    {
        if (! in_array($locale, SetLocale::SUPPORTED, true)) {
            abort(404);
        }

        return back()->cookie('locale', $locale, 60 * 24 * 365);
    }
}

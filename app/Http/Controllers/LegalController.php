<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class LegalController extends Controller
{
    /** Aviso de Privacidad (editable desde el panel). */
    public function privacidad(): View
    {
        return view('legal.show', [
            'title' => landing('legal_privacy_title'),
            'updated' => landing('legal_privacy_updated'),
            'body' => landing('legal_privacy_body'),
        ]);
    }

    /** Términos y Condiciones (editable desde el panel). */
    public function terminos(): View
    {
        return view('legal.show', [
            'title' => landing('legal_terms_title'),
            'updated' => landing('legal_terms_updated'),
            'body' => landing('legal_terms_body'),
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use Illuminate\View\View;

class LegalController extends Controller
{
    /** Aviso de Privacidad (editable desde el panel). */
    public function privacidad(): View
    {
        return view('legal.show', [
            'title' => SiteSetting::get('legal_privacy_title'),
            'updated' => SiteSetting::get('legal_privacy_updated'),
            'body' => SiteSetting::get('legal_privacy_body'),
        ]);
    }

    /** Términos y Condiciones (editable desde el panel). */
    public function terminos(): View
    {
        return view('legal.show', [
            'title' => SiteSetting::get('legal_terms_title'),
            'updated' => SiteSetting::get('legal_terms_updated'),
            'body' => SiteSetting::get('legal_terms_body'),
        ]);
    }
}

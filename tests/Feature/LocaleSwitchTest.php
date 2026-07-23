<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleSwitchTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_es_muestra_textos_en_espanol(): void
    {
        $this->get('/aviso-de-privacidad')
            ->assertOk()
            ->assertSee('Aviso de Privacidad')
            ->assertDontSee('Privacy Policy');
    }

    public function test_switch_a_ingles_guarda_cookie_y_traduce(): void
    {
        $response = $this->from('/aviso-de-privacidad')
            ->post(route('locale.switch', 'en'));

        $response->assertRedirect('/aviso-de-privacidad');

        $cookie = collect($response->headers->getCookies())
            ->first(fn ($c) => $c->getName() === 'locale');

        $this->assertNotNull($cookie);
        // El middleware EncryptCookies cifra el valor; lo desciframos para comparar.
        // Cookie viene con hash|valor (formato interno de EncryptCookies).
        $descifrado = decrypt($cookie->getValue(), unserialize: false);
        $valor = str_contains($descifrado, '|') ? explode('|', $descifrado, 2)[1] : $descifrado;
        $this->assertSame('en', $valor);
    }

    public function test_pagina_con_cookie_en_muestra_ingles(): void
    {
        // El middleware SetLocale lee la cookie y aplica app()->setLocale('en').
        $this->withCookie('locale', 'en')
            ->get('/aviso-de-privacidad')
            ->assertOk()
            ->assertSee('Privacy Policy')
            ->assertSee('Terms and Conditions');
    }

    public function test_locale_invalido_es_rechazado(): void
    {
        $this->post(route('locale.switch', 'fr'))->assertNotFound();
    }

    public function test_paginacion_en_espanol_no_muestra_claves_literales(): void
    {
        // Regresión reportada por el cliente: los botones del paginador salían
        // como "pagination.previous / pagination.next" porque faltaba
        // lang/es/pagination.php.
        \App\Models\User::factory()->count(20)->create();
        // Necesitamos que /talento resuelva con el paginador; usamos el
        // rendered string directamente.
        $html = trans('pagination.previous');
        $this->assertSame('&laquo; Anterior', $html);
        $this->assertSame('Siguiente &raquo;', trans('pagination.next'));
    }

    public function test_password_uncompromised_usa_genero_femenino_correcto(): void
    {
        // Regresión reportada por el cliente: salía "El contraseña ingresado
        // apareció... Elige un contraseña distinto." Además pidió mensaje
        // amable (14-jul): el texto ahora explica que la contraseña salió en
        // filtraciones públicas ("no es tu culpa") y sugiere un ejemplo.
        $mensaje = trans('validation.password.uncompromised');

        $this->assertStringContainsString('contraseña', $mensaje);
        $this->assertStringContainsString('distinta', $mensaje);
        $this->assertStringNotContainsString('El :attribute', $mensaje);
        $this->assertStringNotContainsString('un :attribute', $mensaje);
    }
}

<?php

namespace App\Listeners;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;

/**
 * M8 · Auditoría legal: registra CADA evento de autenticación.
 * Se enlaza en EventServiceProvider — un solo listener para todos los eventos
 * de Auth (login, logout, registered, verified, password reset, failed).
 */
class AuditAuthEvents
{
    public function handleLogin(Login $event): void
    {
        if ($event->user instanceof User) {
            AuditLog::record($event->user, $event->user, 'auth_login', new: ['guard' => $event->guard]);
        }
    }

    public function handleLogout(Logout $event): void
    {
        if ($event->user instanceof User) {
            AuditLog::record($event->user, $event->user, 'auth_logout', new: ['guard' => $event->guard]);
        }
    }

    public function handleRegistered(Registered $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }
        AuditLog::record($event->user, $event->user, 'auth_registered', new: [
            'email' => $event->user->email,
            'nivel' => $event->user->nivel?->value ?? null,
        ]);

        // HIGH-2 · Evidencia legal (LFPDPPP / INAI): guardar QUÉ versión del
        // Aviso de Privacidad y Términos aceptó el usuario, y CUÁNDO. Sin este
        // registro, ante una queja no podemos demostrar el consentimiento.
        // Las "versiones" son los strings de fecha/nota que la cliente maneja
        // desde el panel admin (SiteSetting.legal_privacy_updated / _terms_updated).
        AuditLog::record($event->user, $event->user, 'legal_accepted', new: [
            'terminos_version'    => \App\Models\SiteSetting::get('legal_terms_updated', ''),
            'privacidad_version'  => \App\Models\SiteSetting::get('legal_privacy_updated', ''),
            'accepted_at'         => now()->toIso8601String(),
            'locale'              => app()->getLocale(),
        ]);
    }

    public function handleVerified(Verified $event): void
    {
        if ($event->user instanceof User) {
            AuditLog::record($event->user, $event->user, 'auth_email_verified');
        }
    }

    public function handlePasswordReset(PasswordReset $event): void
    {
        if ($event->user instanceof User) {
            AuditLog::record($event->user, $event->user, 'auth_password_reset');
        }
    }

    public function handleFailed(Failed $event): void
    {
        // Login fallido — actor null (no sabemos quién es), subject el user
        // real si el email existe. Guardamos solo el email (no la password!).
        $subject = $event->user instanceof User
            ? $event->user
            : User::whereRaw('LOWER(email) = ?', [strtolower($event->credentials['email'] ?? '')])->first();
        if ($subject) {
            AuditLog::record(null, $subject, 'auth_failed', new: [
                'email_intento' => $event->credentials['email'] ?? null,
            ]);
        }
    }

    public function subscribe($events): array
    {
        return [
            Login::class          => 'handleLogin',
            Logout::class         => 'handleLogout',
            Registered::class     => 'handleRegistered',
            Verified::class       => 'handleVerified',
            PasswordReset::class  => 'handlePasswordReset',
            Failed::class         => 'handleFailed',
        ];
    }
}

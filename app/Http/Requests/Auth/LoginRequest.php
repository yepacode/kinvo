<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());
            // LOW-2 · Contador secundario por email (independiente de IP).
            // Decae solo tras 10 min; hard-limit=20 intentos globales por email.
            RateLimiter::hit($this->throttleKeyEmail(), 600);

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        RateLimiter::clear($this->throttleKeyEmail());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        $tooManyByIp    = RateLimiter::tooManyAttempts($this->throttleKey(), 5);
        // LOW-2 · Aunque el atacante rote IP, este contador global por email
        // acumula igual y termina bloqueando después de 20 intentos.
        $tooManyByEmail = RateLimiter::tooManyAttempts($this->throttleKeyEmail(), 20);

        if (! $tooManyByIp && ! $tooManyByEmail) {
            return;
        }

        event(new Lockout($this));

        $seconds = max(
            RateLimiter::availableIn($this->throttleKey()),
            RateLimiter::availableIn($this->throttleKeyEmail()),
        );

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     * LOW-2 · La key `email+ip` permite eludir el bloqueo rotando IP (VPN,
     * proxies). Complementamos con un check adicional por sólo email en el
     * método ensureIsNotRateLimited (más abajo) — ver commit adjunto.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }

    /**
     * LOW-2 · Rate limit secundario por email únicamente: 20 intentos / 10 min
     * globales para ese email, independiente de la IP. Complementa al
     * throttleKey de arriba (email+ip) que sigue bloqueando el burst
     * clásico. Rotación de IP ya no evade este segundo límite.
     */
    public function throttleKeyEmail(): string
    {
        return 'login-by-email|'.Str::transliterate(Str::lower($this->string('email')));
    }
}

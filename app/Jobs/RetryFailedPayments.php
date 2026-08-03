<?php

namespace App\Jobs;

use App\Mail\AvisoVencimientoMembresia;
use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Fase 2 · Job diario que revisa suscripciones en 'past_due' y las expira si
 * llevan más de N días sin recuperarse. La pasarela sigue reintentando por
 * su cuenta; este job es el guardián que corta acceso si no lo logra.
 *
 * En cron: `Schedule::job(new RetryFailedPayments)->dailyAt('03:00')`
 */
class RetryFailedPayments implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Días de gracia en past_due antes de expirar la membresía. */
    public const GRACIA_DIAS = 7;

    public function handle(): void
    {
        $limite = now()->subDays(self::GRACIA_DIAS);
        $expiradas = 0;

        Subscription::where('status', Subscription::STATUS_PAST_DUE)
            ->where('updated_at', '<=', $limite)
            ->with('user')
            ->each(function (Subscription $sub) use (&$expiradas) {
                $sub->update([
                    'status' => Subscription::STATUS_UNPAID,
                    'ends_at' => now(),
                ]);
                if ($sub->user) {
                    $sub->user->forceFill(['membership_expires_at' => now()])->save();
                    try {
                        Mail::to($sub->user->email)->send(new AvisoVencimientoMembresia($sub->user));
                    } catch (\Throwable $e) { report($e); }
                }
                $expiradas++;
            });

        Log::info('RetryFailedPayments completado', ['expiradas' => $expiradas]);
    }
}

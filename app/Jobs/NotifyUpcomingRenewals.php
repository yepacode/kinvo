<?php

namespace App\Jobs;

use App\Mail\AvisoCobroProximo;
use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Fase 2 · Envía AvisoCobroProximo a suscripciones activas cuyo
 * current_period_end cae en los próximos 7 días. Idempotencia manual:
 * marca AuditLog con action='renewal_reminder_sent' para no duplicar.
 *
 * Cron: `Schedule::job(new NotifyUpcomingRenewals)->dailyAt('09:00')`
 */
class NotifyUpcomingRenewals implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const DIAS_ANTICIPACION = 7;

    public function handle(): void
    {
        $desde = now()->startOfDay();
        $hasta = now()->addDays(self::DIAS_ANTICIPACION)->endOfDay();
        $enviados = 0;

        Subscription::where('status', Subscription::STATUS_ACTIVE)
            ->whereBetween('current_period_end', [$desde, $hasta])
            ->with('user')
            ->each(function (Subscription $sub) use (&$enviados) {
                if (! $sub->user) return;

                $yaAvisado = \App\Models\AuditLog::where('subject_type', Subscription::class)
                    ->where('subject_id', $sub->id)
                    ->where('action', 'renewal_reminder_sent')
                    ->where('created_at', '>=', now()->subDays(self::DIAS_ANTICIPACION))
                    ->exists();
                if ($yaAvisado) return;

                try {
                    Mail::to($sub->user)->send(new AvisoCobroProximo($sub->user, $sub));
                    \App\Models\AuditLog::record(null, $sub, 'renewal_reminder_sent');
                    $enviados++;
                } catch (\Throwable $e) { report($e); }
            });

        Log::info('NotifyUpcomingRenewals completado', ['enviados' => $enviados]);
    }
}

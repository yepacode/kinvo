<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Hostinger compartido (y cualquier hosting sin proceso persistente) no puede
// mantener un `queue:work` como daemon. En su lugar, disparamos el worker cada
// minuto vía Schedule + un único cron:  `* * * * * php artisan schedule:run`
// El `--stop-when-empty` hace que el proceso termine cuando la cola esté vacía,
// evitando dejar workers colgados. Overlaps bloqueados por si un lote tarda más
// de 60s. En VPS con Forge/Supervisor esto es inofensivo (se puede seguir
// corriendo `queue:work` como daemon en paralelo).
Schedule::command('queue:work --stop-when-empty --tries=3 --timeout=55')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

// Fase 2 · Reintentos de cobros fallidos: revisa suscripciones en past_due
// y las expira si llevan más de N días sin recuperarse.
Schedule::job(new \App\Jobs\RetryFailedPayments)->dailyAt('03:00');

// Fase 2 · Aviso 7 días antes del vencimiento (idempotente por AuditLog).
Schedule::job(new \App\Jobs\NotifyUpcomingRenewals)->dailyAt('09:00');

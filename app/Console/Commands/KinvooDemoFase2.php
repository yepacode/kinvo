<?php

namespace App\Console\Commands;

use App\Models\Application;
use App\Models\ContentItem;
use App\Models\Offer;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\TeamMember;
use App\Models\User;
use App\Models\WellnessEntry;
use Database\Seeders\DemoFase2Seeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;

/**
 * Genera o refresca los datos DEMO de la Fase 2 en el ambiente.
 *
 * Reglas:
 *  - Idempotente: correr N veces = mismo resultado (updateOrCreate por email).
 *  - Prefijo demo.f2.* para distinguir de los datos reales.
 *  - En producción requiere --force (guarda análogo a Laravel migrate).
 *  - Con --refresh borra SOLO los demos (nunca datos reales) y los recrea.
 */
class KinvooDemoFase2 extends Command
{
    protected $signature = 'kinvoo:demo-fase2
                            {--refresh : Borra los demos existentes y los recrea desde cero}
                            {--force : Permite correr en producción}';

    protected $description = 'Crea/actualiza los datos demo de la Fase 2 (idempotente, prefijo demo.f2.*)';

    public function handle(): int
    {
        if (App::environment('production') && ! $this->option('force')) {
            $this->error('Estás en producción. Usa --force para confirmar.');
            return self::FAILURE;
        }

        if ($this->option('refresh')) {
            $this->info('Borrando demos existentes (solo los del prefijo demo.f2.*)...');
            $this->borrarDemos();
        }

        $this->info('Cargando DemoFase2Seeder...');
        (new DemoFase2Seeder())->setCommand($this)->run();

        // Resumen rápido
        $prefix = DemoFase2Seeder::EMAIL_PREFIX;
        $this->newLine();
        $this->info('=== Resumen datos demo Fase 2 ===');
        $this->line(sprintf('  Usuarios demo:     %d', User::where('email', 'like', $prefix.'%')->count()));
        $this->line(sprintf('  Suscripciones:     %d', Subscription::where('provider', 'demo')->count()));
        $this->line(sprintf('  Payments:          %d', Payment::where('provider', 'demo')->count()));
        $this->line(sprintf('  Ofertas:           %d', Offer::whereHas('contractor', fn ($q) => $q->where('email', 'like', $prefix.'%'))->count()));
        $this->line(sprintf('  Postulaciones:     %d', Application::whereHas('professional', fn ($q) => $q->where('email', 'like', $prefix.'%'))->count()));
        $this->line(sprintf('  Contenidos:        %d', ContentItem::where('slug', 'like', '%-demo')->count()));
        $this->line(sprintf('  Expediente:        %d', WellnessEntry::whereHas('professional', fn ($q) => $q->where('email', 'like', $prefix.'%'))->count()));
        $this->line(sprintf('  Team members:      %d', TeamMember::whereHas('contractor', fn ($q) => $q->where('email', 'like', $prefix.'%'))->count()));
        $this->newLine();
        $this->info('OK. Contraseña por default para todos los demos: password');

        return self::SUCCESS;
    }

    /**
     * Borra solo lo del prefijo demo.f2.* — nunca datos reales.
     * Las FK con cascadeOnDelete se encargan de subscriptions, payments,
     * offers, applications, wellness_entries y team_members al borrar users.
     */
    private function borrarDemos(): void
    {
        $prefix = DemoFase2Seeder::EMAIL_PREFIX;

        // Contenidos y ofertas independientes (por slug demo)
        ContentItem::where('slug', 'like', '%-demo')->delete();
        Offer::where('slug', 'like', '%-demo')->delete();

        // Payments/subscriptions marcados con provider='demo' que no viven en users demo
        Subscription::where('provider', 'demo')->delete();
        Payment::where('provider', 'demo')->delete();

        // Users con prefijo — al borrar caen sus relaciones por FK cascade.
        User::where('email', 'like', $prefix.'%')->get()->each(function (User $u) {
            $u->delete();
        });
    }
}

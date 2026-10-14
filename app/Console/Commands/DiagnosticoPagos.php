<?php

namespace App\Console\Commands;

use App\Models\Plan;
use App\Models\PaymentCredential;
use App\Services\Billing\MercadoPagoGateway;
use App\Services\Billing\SubscriptionGateway;
use App\Support\FalloDePasarela;
use App\Support\PulsoWebhook;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Revisa de arriba abajo el circuito de cobro y lo imprime.
 *
 * Nació de un problema muy concreto: "no deja pagar" y nadie podía ver por
 * qué. El motivo lo tenía MercadoPago, la plataforma lo recibía y lo tiraba,
 * y el único rastro quedaba dentro de un laravel.log de cientos de megas.
 * Este comando pregunta todo lo que hay que preguntar y lo enseña en pantalla,
 * para poder ejecutarlo por SSH y leer la respuesta de una vez.
 *
 *   php artisan pagos:diagnostico
 *
 * No cobra nada: las suscripciones de prueba se crean en estado pendiente,
 * sin tarjeta, y se cancelan en el acto.
 */
class DiagnosticoPagos extends Command
{
    protected $signature = 'pagos:diagnostico {--sin-planes : No prueba los planes contra la pasarela}';

    protected $description = 'Revisa la configuración de cobros y dice exactamente qué falla';

    public function handle(): int
    {
        $this->newLine();
        $this->line('<fg=cyan>══ DIAGNÓSTICO DE LA PASARELA DE PAGOS ══</>');
        $this->line('  '.now()->toDayDateTimeString().'  ·  entorno: '.app()->environment());
        $this->newLine();

        $this->configuracion();
        $this->cuentaDeMercadoPago();
        $this->avisosRecibidos();
        $this->ultimoFallo();

        if (! $this->option('sin-planes')) {
            $this->planes();
        }

        $this->newLine();
        $this->line('<fg=cyan>══ FIN ══</>');
        $this->newLine();

        return self::SUCCESS;
    }

    private function configuracion(): void
    {
        $this->line('<fg=yellow>1 · Configuración</>');

        $cred = PaymentCredential::activa();
        $gateway = config('billing.gateway');
        $token = (string) config('billing.mercadopago.access_token');
        $secret = (string) config('billing.mercadopago.webhook_secret');

        $this->fila('Pasarela activa', $gateway, $gateway === 'mercadopago');
        $this->fila('Origen de las llaves', $cred ? 'panel de administración' : 'archivo .env');

        $this->fila(
            'Access Token',
            $token ? substr($token, 0, 11).'…'.substr($token, -4).($this->esProduccion($token) ? '  (producción)' : '  (PRUEBAS)') : 'NO HAY',
            $token !== '' && $this->esProduccion($token)
        );

        $this->fila(
            'Secret de firma del webhook',
            $secret ? 'capturado ('.strlen($secret).' caracteres)' : 'NO HAY — los pagos entrarán pero no se confirmarán solos',
            $secret !== ''
        );

        $this->fila('URL que debe tener MercadoPago', route('billing.webhook'));
        $this->newLine();
    }

    private function cuentaDeMercadoPago(): void
    {
        $this->line('<fg=yellow>2 · ¿Responde MercadoPago?</>');

        $token = (string) config('billing.mercadopago.access_token');
        if (! $token) {
            $this->fila('Conexión', 'no se puede probar: falta el Access Token', false);
            $this->newLine();

            return;
        }

        try {
            $res = Http::withToken($token)->timeout(20)->get('https://api.mercadopago.com/users/me');
        } catch (\Throwable $e) {
            // Importantísimo distinguir esto: si el servidor no puede salir a
            // internet, no hay credencial que valga y el síntoma es idéntico.
            $this->fila('Conexión', 'EL SERVIDOR NO PUDO CONECTAR: '.$e->getMessage(), false);
            $this->newLine();

            return;
        }

        if (! $res->successful()) {
            $this->fila('Conexión', 'MercadoPago respondió '.$res->status().' · '.$res->body(), false);
            $this->newLine();

            return;
        }

        $c = $res->json();
        $this->fila('Cuenta', ($c['nickname'] ?? '—').'  ('.($c['email'] ?? '—').')', true);
        $this->fila('País / moneda del sitio', ($c['site_id'] ?? '—'));
        $this->newLine();
    }

    private function avisosRecibidos(): void
    {
        $this->line('<fg=yellow>3 · Avisos recibidos de MercadoPago (webhook)</>');

        $ok = PulsoWebhook::ultimoAceptado();
        $ko = PulsoWebhook::ultimoRechazado();

        if (! $ok && ! $ko) {
            $this->fila('Estado', 'MercadoPago NO ha llamado nunca. Revisa que la URL esté dada de alta en Notificaciones › Webhooks (no en el campo de OAuth).', false);
        } else {
            if ($ok) {
                $this->fila('Último aviso aceptado', $ok['cuando'].'  ·  '.($ok['tipo'] ?? '—'), true);
            }
            if ($ko) {
                $this->fila('Último aviso rechazado', $ko['cuando'].'  ·  '.($ko['motivo'] ?? '—'), false);
            }
        }
        $this->newLine();
    }

    private function ultimoFallo(): void
    {
        $f = FalloDePasarela::ultimo();
        if (! $f) {
            return;
        }

        $this->line('<fg=yellow>4 · Último rechazo registrado</>');
        $this->line('  <fg=red>'.$f['motivo'].'</>');
        $this->line('  <fg=gray>'.$f['cuando'].'  ·  '.json_encode($f['contexto'] ?? [], JSON_UNESCAPED_UNICODE).'</>');
        $this->newLine();
    }

    private function planes(): void
    {
        $this->line('<fg=yellow>5 · ¿MercadoPago acepta cobrar cada plan?</>');

        $gateway = app(SubscriptionGateway::class);
        if (! $gateway instanceof MercadoPagoGateway) {
            $this->fila('Prueba de planes', 'la pasarela activa no es MercadoPago, no se prueba');
            $this->newLine();

            return;
        }

        $planes = Plan::where('activo', true)->orderBy('orden')->orderBy('id')->get();
        if ($planes->isEmpty()) {
            $this->fila('Planes', 'no hay planes activos');
            $this->newLine();

            return;
        }

        foreach ($planes as $plan) {
            $etiqueta = '#'.$plan->id.' '.$plan->nombre.'  ('.($plan->precio === null ? 'a consultar' : '$'.number_format((float) $plan->precio, 2).' '.$plan->moneda).', '.$plan->periodo.')';

            if ($plan->sinPrecio()) {
                $this->fila($etiqueta, 'sin precio: no pasa por la pasarela');

                continue;
            }
            if ($plan->esGratuito()) {
                $this->fila($etiqueta, 'sin costo: se activa sin pasarela', true);

                continue;
            }
            if ($plan->porDebajoDelMinimo()) {
                $this->fila($etiqueta, 'POR DEBAJO DEL MÍNIMO de $'.number_format($plan->minimoDeLaPasarela(), 2).' que cobra MercadoPago. Nadie puede contratarlo.', false);

                continue;
            }

            try {
                $gateway->probarPlan($plan);
                $this->fila($etiqueta, 'MercadoPago lo acepta', true);
            } catch (\Throwable $e) {
                $this->fila($etiqueta, FalloDePasarela::motivo($e), false);
            }
        }
        $this->newLine();
    }

    private function esProduccion(string $token): bool
    {
        return str_starts_with($token, 'APP_USR');
    }

    private function fila(string $clave, string $valor, ?bool $ok = null): void
    {
        $marca = $ok === null ? ' ·' : ($ok ? '<fg=green>OK</>' : '<fg=red>!!</>');
        $this->line('  '.$marca.'  '.str_pad($clave, 34).'  '.$valor);
    }
}

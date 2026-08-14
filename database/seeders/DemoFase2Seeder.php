<?php

namespace Database\Seeders;

use App\Enums\EstadoUsuario;
use App\Enums\RolUsuario;
use App\Models\Application;
use App\Models\ContentItem;
use App\Models\Discipline;
use App\Models\Location;
use App\Models\Offer;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\TeamMember;
use App\Models\User;
use App\Models\WellnessEntry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Fase 2 · Seeder de datos DEMO idempotente.
 *
 * Convención: TODO email/slug de este seeder lleva el prefijo `demo.f2.*` para
 * poder distinguirlos siempre de los datos reales. Se puede correr N veces sin
 * duplicar (usa updateOrCreate por email/composite key), y NO borra nada
 * — sobreescribe los atributos y respeta las FKs existentes.
 *
 * Uso:
 *   php artisan kinvoo:demo-fase2           # crea/actualiza demos
 *   php artisan kinvoo:demo-fase2 --refresh # borra SOLO los demos y los recrea
 *   php artisan kinvoo:demo-fase2 --force   # permite ejecución en producción
 */
class DemoFase2Seeder extends Seeder
{
    public const EMAIL_PREFIX = 'demo.f2.';

    /** Crea/actualiza un usuario demo. Idempotente por email. */
    private function usuario(string $email, string $name, RolUsuario $rol, EstadoUsuario $estado = EstadoUsuario::Activo): User
    {
        $u = User::updateOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => Hash::make('password')]
        );

        // nivel/estado/locale NO son mass-assignable (por seguridad); forceFill.
        $atributos = [
            'nivel' => $rol,
            'estado' => $estado,
            'email_verified_at' => now(),
        ];
        if (\Schema::hasColumn('users', 'locale')) {
            $atributos['locale'] = 'es';
        }
        $u->forceFill($atributos)->save();

        return $u;
    }

    public function run(): void
    {
        // Los usuarios y planes reales deben existir antes; si no, este seeder
        // depende de PlanSeeder para que haya al menos un plan configurable.
        if (Plan::count() === 0) {
            $this->call(PlanSeeder::class);
        }

        // ==================================================
        // 1. Admin demo
        // ==================================================
        $admin = $this->usuario(
            self::EMAIL_PREFIX.'admin@kinvoo.test',
            'Admin Demo Kinvoo',
            RolUsuario::Admin
        );

        // ==================================================
        // 2. Coaches demo (3)
        // ==================================================
        $coaches = [
            ['ana', 'Ana Torres Demo', 'Coach de fuerza y acondicionamiento',
             'hibrido', 'Ciudad de México', ['crossfit', 'entrenamiento-funcional']],
            ['pedro', 'Pedro Vega Demo', 'Instructor de yoga y meditación',
             'presencial', 'Guadalajara', ['yoga', 'pilates']],
            ['sofia', 'Sofía Ramírez Demo', 'Nutrióloga deportiva online',
             'online', 'Monterrey', ['nutricion-deportiva']],
        ];
        $coachModels = [];
        foreach ($coaches as [$slug, $name, $headline, $mod, $ciudad, $discs]) {
            $coach = $this->usuario(
                self::EMAIL_PREFIX."coach.$slug@kinvoo.test",
                $name,
                RolUsuario::Professional
            );
            $profile = $coach->professionalProfile()->firstOrCreate([]);
            $profile->update([
                'headline' => $headline,
                'bio' => 'Perfil de demostración para pruebas — '.strtolower($headline).'.',
                'years_experience' => rand(3, 12),
                'modalidad' => $mod,
                'location_id' => Location::where('ciudad', $ciudad)->value('id'),
                'is_published' => true,
                'is_verified' => true,
                'verified_at' => now(),
            ]);
            $profile->disciplines()->sync(
                Discipline::whereIn('slug', $discs)->pluck('id')
            );
            $coachModels[$slug] = $coach;
        }

        // ==================================================
        // 3. Estudios demo (3)
        // ==================================================
        $estudios = [
            ['aurea', 'Áurea Studio Demo', 'Estudio boutique de yoga y pilates', 'Ciudad de México'],
            ['norte', 'Gimnasio Norte Demo', 'Gimnasio de fuerza y CrossFit', 'Monterrey'],
            ['pacifico', 'Pacífico Wellness Demo', 'Centro de bienestar integral', 'Guadalajara'],
        ];
        $estudioModels = [];
        foreach ($estudios as [$slug, $name, $descripcion, $ciudad]) {
            $estudio = $this->usuario(
                self::EMAIL_PREFIX."estudio.$slug@kinvoo.test",
                $name,
                RolUsuario::Contractor
            );
            $cp = $estudio->companyProfile()->firstOrCreate([], [
                'company_name' => $name,
            ]);
            $cp->update([
                'company_name' => $name,
                'sector' => $descripcion,
                'estado' => 'Jalisco',
                'location_id' => Location::where('ciudad', $ciudad)->value('id'),
            ]);

            // Membresía activa: agarra el 1er plan de contratante disponible.
            $planEstudio = Plan::where('audiencia', 'estudios')->orderBy('orden')->first()
                ?? Plan::orderBy('orden')->first();
            $estudio->forceFill([
                'membership_plan_id' => $planEstudio?->id,
                'membership_expires_at' => now()->addMonth(),
            ])->save();

            $estudioModels[$slug] = $estudio;
        }

        // ==================================================
        // 3b. Cuentas PENDIENTES demo — para probar el flujo de aprobación
        //    - Un estudio recién registrado (ve solo "Inicio" + "Mi empresa").
        //    - Un coach recién registrado (ve solo "Inicio" + "Mi perfil").
        // Marian los aprueba/rechaza desde /admin/users para probar el flujo real.
        // ==================================================
        $estudioPendiente = $this->usuario(
            self::EMAIL_PREFIX.'estudio.pendiente@kinvoo.test',
            'Estudio Pendiente Demo',
            RolUsuario::Contractor,
            EstadoUsuario::PerfilPendiente,
        );
        // Perfil vacío para que Marian pueda llenar y ver el flujo.
        $estudioPendiente->companyProfile()->firstOrCreate([], [
            'company_name' => $estudioPendiente->name,
        ]);

        $coachPendiente = $this->usuario(
            self::EMAIL_PREFIX.'coach.pendiente@kinvoo.test',
            'Coach Pendiente Demo',
            RolUsuario::Professional,
            EstadoUsuario::Pendiente,
        );
        $coachPendiente->professionalProfile()->firstOrCreate([], [
            'headline' => null,
        ]);

        // ==================================================
        // 4. Suscripciones demo
        //    3 activas (una por cada estudio) + 1 canceled + 1 past_due
        // ==================================================
        $planEstudio = Plan::where('audiencia', 'estudios')->orderBy('orden')->first()
            ?? Plan::orderBy('orden')->first();

        foreach (['aurea', 'norte', 'pacifico'] as $slug) {
            Subscription::updateOrCreate(
                [
                    'user_id' => $estudioModels[$slug]->id,
                    'provider_subscription_id' => 'demo_sub_'.$slug,
                ],
                [
                    'plan_id' => $planEstudio?->id,
                    'provider' => 'demo',
                    'provider_customer_id' => 'demo_cus_'.$slug,
                    'status' => Subscription::STATUS_ACTIVE,
                    'current_period_start' => now()->subDays(15),
                    'current_period_end' => now()->addDays(15),
                ]
            );
        }

        // Un canceled (mostrar en morosidad)
        Subscription::updateOrCreate(
            ['provider_subscription_id' => 'demo_sub_cancelado'],
            [
                'user_id' => $estudioModels['norte']->id,
                'plan_id' => $planEstudio?->id,
                'provider' => 'demo',
                'status' => Subscription::STATUS_CANCELED,
                'current_period_start' => now()->subDays(45),
                'current_period_end' => now()->subDays(15),
                'canceled_at' => now()->subDays(20),
                'ends_at' => now()->subDays(15),
            ]
        );

        // ==================================================
        // 5. Payments demo (5 exitosos + 1 fallido)
        // ==================================================
        foreach (['aurea', 'norte', 'pacifico'] as $i => $slug) {
            $sub = Subscription::where('provider_subscription_id', 'demo_sub_'.$slug)->first();
            Payment::updateOrCreate(
                ['provider_payment_id' => 'demo_pay_'.$slug.'_1'],
                [
                    'user_id' => $estudioModels[$slug]->id,
                    'subscription_id' => $sub?->id,
                    'provider' => 'demo',
                    'amount_cents' => 19900, // MX$199
                    'currency' => 'MXN',
                    'status' => Payment::STATUS_SUCCEEDED,
                    'paid_at' => now()->subDays(15),
                ]
            );
        }
        Payment::updateOrCreate(
            ['provider_payment_id' => 'demo_pay_fallido_1'],
            [
                'user_id' => $estudioModels['norte']->id,
                'provider' => 'demo',
                'amount_cents' => 19900,
                'currency' => 'MXN',
                'status' => Payment::STATUS_FAILED,
                'failure_code' => 'card_declined',
                'failure_message' => 'La tarjeta fue rechazada por el banco.',
            ]
        );

        // ==================================================
        // 6. Ofertas demo (4) + Postulaciones (6)
        // ==================================================
        $ofertas = [
            ['coach-yoga-fds-demo', 'aurea', 'Coach de yoga fin de semana',
             'Buscamos coach de yoga para sábados y domingos.',
             'Certificación en Hatha o Vinyasa.', 8000, 15000, 'presencial', 'part_time'],
            ['crossfit-tiempo-completo-demo', 'norte', 'Coach de CrossFit tiempo completo',
             'Coach titular para nuestro box en Monterrey.',
             'CF-L1 mínimo, 2 años experiencia.', 25000, 40000, 'presencial', 'full_time'],
            ['nutriologo-online-demo', 'pacifico', 'Nutriólogo online',
             'Consultas nutricionales para nuestros miembros.',
             'Cédula profesional vigente.', 15000, 25000, 'online', 'freelance'],
            ['instructor-pilates-demo', 'aurea', 'Instructor de Pilates',
             'Clases matutinas de Pilates reformer.',
             'Certificación en Pilates.', 12000, 20000, 'presencial', 'part_time'],
        ];
        $ofertaModels = [];
        foreach ($ofertas as [$slug, $estudioSlug, $title, $desc, $req, $min, $max, $mod, $ct]) {
            $o = Offer::updateOrCreate(
                ['slug' => $slug],
                [
                    'contractor_user_id' => $estudioModels[$estudioSlug]->id,
                    'title' => $title,
                    'description' => $desc,
                    'requirements' => $req,
                    'salary_min_cents' => $min * 100,
                    'salary_max_cents' => $max * 100,
                    'salary_currency' => 'MXN',
                    'salary_period' => 'month',
                    'modality' => $mod,
                    'contract_type' => $ct,
                    'status' => Offer::STATUS_PUBLISHED,
                    'published_at' => now()->subDays(rand(1, 10)),
                    'expires_on' => now()->addDays(30),
                ]
            );
            $ofertaModels[$slug] = $o;
        }

        // 6 postulaciones (2 por cada uno de los 3 coaches, evitando duplicados)
        $postulaciones = [
            ['ana', 'coach-yoga-fds-demo', 'submitted'],
            ['ana', 'crossfit-tiempo-completo-demo', 'seen'],
            ['pedro', 'coach-yoga-fds-demo', 'in_contact'],
            ['pedro', 'instructor-pilates-demo', 'submitted'],
            ['sofia', 'nutriologo-online-demo', 'accepted'],
            ['sofia', 'instructor-pilates-demo', 'rejected'],
        ];
        foreach ($postulaciones as [$coachSlug, $ofertaSlug, $status]) {
            Application::updateOrCreate(
                [
                    'offer_id' => $ofertaModels[$ofertaSlug]->id,
                    'professional_user_id' => $coachModels[$coachSlug]->id,
                ],
                [
                    'cover_letter' => 'Postulación demo — '.$coachModels[$coachSlug]->name.'.',
                    'status' => $status,
                    'status_changed_at' => now()->subDays(rand(0, 5)),
                ]
            );
        }

        // ==================================================
        // 7. Content items demo (5) — mezcla de gates
        // ==================================================
        $contenidos = [
            ['intro-kinvoo-demo', 'Bienvenida a Kinvoo', 'Video introducción a la plataforma.',
             'Onboarding', 'video', 'https://example.com/intro.mp4', null, null],
            ['seguros-medicos-demo', 'Cómo funcionan los seguros médicos', 'Guía en video.',
             'Beneficios', 'video', 'https://example.com/seguros.mp4', 'professional', null],
            ['contratar-talento-demo', 'Guía para contratar talento fitness', 'PDF con checklist.',
             'Guías', 'document', null, 'contractor', null],
            ['capacitacion-avanzada-demo', 'Capacitación avanzada (solo Pro)', 'Video premium.',
             'Capacitaciones', 'video', 'https://example.com/pro.mp4', null, $planEstudio?->id],
            ['podcast-inspiracion-demo', 'Podcast: historias de coaches', 'Audio semanal.',
             'Comunidad', 'audio', 'https://example.com/podcast.mp3', null, null],
        ];
        foreach ($contenidos as [$slug, $title, $desc, $cat, $type, $url, $gateRole, $gatePlanId]) {
            ContentItem::updateOrCreate(
                ['slug' => $slug],
                [
                    'title' => $title,
                    'description' => $desc,
                    'category' => $cat,
                    'type' => $type,
                    'url' => $url,
                    'gate_role' => $gateRole,
                    'gate_plan_id' => $gatePlanId,
                    'is_published' => true,
                    'published_at' => now()->subDays(rand(1, 30)),
                ]
            );
        }

        // ==================================================
        // 8. Expediente de cuidado demo (8 entradas para Ana)
        // ==================================================
        // Fechas FIJAS (no relativas a now()): la llave `occurred_on` es parte
        // del updateOrCreate; si usara now()->subDays(N) las fechas cambiarían
        // cada día y el seeder duplicaría entradas al re-correr.
        $expediente = [
            [WellnessEntry::TYPE_TELEMEDICINE, '2026-05-30', 'Dr. Salinas',    'Consulta de rutina, todo bien.'],
            [WellnessEntry::TYPE_PHYSIO,       '2026-06-15', 'Fisio Roldán',   'Sesión de recuperación de hombro.'],
            [WellnessEntry::TYPE_TALK,         '2026-06-30', 'Kinvoo Sessions','Charla: manejo del estrés en coaches.'],
            [WellnessEntry::TYPE_INSURANCE,    '2026-01-30', 'GNP Salud',      'Renovación de póliza anual.'],
            [WellnessEntry::TYPE_TELEMEDICINE, '2026-07-10', 'Dra. Ortiz',     'Consulta nutricional.'],
            [WellnessEntry::TYPE_PHYSIO,       '2026-07-15', 'Fisio Roldán',   'Segunda sesión de hombro.'],
            [WellnessEntry::TYPE_TALK,         '2026-07-20', 'Kinvoo Sessions','Charla: comunidad y pertenencia.'],
            [WellnessEntry::TYPE_TELEMEDICINE, '2026-07-27', 'Dr. Salinas',    'Control mensual.'],
        ];
        foreach ($expediente as [$type, $fecha, $provider, $notes]) {
            $fechaCarbon = \Illuminate\Support\Carbon::parse($fecha)->startOfDay();
            $extra = ($type === WellnessEntry::TYPE_INSURANCE)
                ? ['valid_until' => $fechaCarbon->copy()->addYear()]
                : [];
            WellnessEntry::updateOrCreate(
                [
                    'professional_user_id' => $coachModels['ana']->id,
                    'type' => $type,
                    'occurred_on' => $fechaCarbon,
                ],
                array_merge([
                    'created_by_admin_id' => $admin->id,
                    'provider' => $provider,
                    'notes' => $notes,
                ], $extra)
            );
        }

        // ==================================================
        // 9. Equipos demo (2)
        //    aurea → ana (active) + pedro (invited)
        //    norte → sofia (active)
        // ==================================================
        TeamMember::updateOrCreate(
            [
                'contractor_user_id' => $estudioModels['aurea']->id,
                'professional_user_id' => $coachModels['ana']->id,
            ],
            [
                'status' => TeamMember::STATUS_ACTIVE,
                'invited_at' => now()->subMonths(2),
                'joined_at' => now()->subMonths(2)->addDays(1),
            ]
        );
        $invitacionPedro = TeamMember::updateOrCreate(
            [
                'contractor_user_id' => $estudioModels['aurea']->id,
                'professional_user_id' => $coachModels['pedro']->id,
            ],
            [
                'status' => TeamMember::STATUS_INVITED,
                'invited_at' => now()->subDays(3),
            ]
        );
        // Notif in-app para el demo — Pedro tiene la campanita con la invitación.
        // Idempotente: si ya tiene una notif del mismo tipo para este mismo team
        // member no la duplicamos.
        $yaNotificado = $coachModels['pedro']->notifications()
            ->where('type', \App\Notifications\InvitacionEquipoNotification::class)
            ->get()
            ->contains(fn ($n) => ($n->data['team_member_id'] ?? null) === $invitacionPedro->id);
        if (! $yaNotificado) {
            try {
                $coachModels['pedro']->notify(new \App\Notifications\InvitacionEquipoNotification($invitacionPedro));
            } catch (\Throwable $e) { report($e); }
        }
        TeamMember::updateOrCreate(
            [
                'contractor_user_id' => $estudioModels['norte']->id,
                'professional_user_id' => $coachModels['sofia']->id,
            ],
            [
                'status' => TeamMember::STATUS_ACTIVE,
                'invited_at' => now()->subMonth(),
                'joined_at' => now()->subMonth()->addDays(2),
            ]
        );

        // ==================================================
        // 10. Sesiones en vivo demo (2) — con invitados y RSVP
        // ==================================================
        $sesiones = [
            [
                'title'        => 'Webinar demo: Onboarding Kinvoo',
                'tipo'         => \App\Models\Sesion::TIPO_WEBINAR,
                'scheduled_at' => \Illuminate\Support\Carbon::parse('2026-09-15 18:00:00'),
                'duration_min' => 60,
                'link'         => 'https://zoom.us/j/demo-kinvoo-onboarding',
                'audience'     => \App\Models\Sesion::AUDIENCE_ALL,
                'description'  => 'Sesión demo para mostrar cómo funciona el módulo de sesiones en vivo.',
            ],
            [
                'title'        => 'Taller demo: Marketing para coaches',
                'tipo'         => \App\Models\Sesion::TIPO_TALLER,
                'scheduled_at' => \Illuminate\Support\Carbon::parse('2026-09-22 17:00:00'),
                'duration_min' => 90,
                'link'         => 'https://zoom.us/j/demo-kinvoo-marketing',
                'audience'     => \App\Models\Sesion::AUDIENCE_PROFESSIONAL,
                'description'  => 'Sesión demo dirigida solo a coaches — cómo posicionar tu perfil.',
            ],
        ];
        $sesionModels = [];
        foreach ($sesiones as $data) {
            $sesion = \App\Models\Sesion::updateOrCreate(
                ['title' => $data['title']],
                array_merge($data, ['created_by_admin_id' => $admin->id])
            );
            $sesionModels[] = $sesion;

            // Invita a todos los users demo que apliquen a la audiencia.
            $usersInvitables = match ($sesion->audience) {
                \App\Models\Sesion::AUDIENCE_PROFESSIONAL => collect($coachModels),
                \App\Models\Sesion::AUDIENCE_CONTRACTOR   => collect($estudioModels),
                default => collect($coachModels)->merge(collect($estudioModels)),
            };
            foreach ($usersInvitables as $u) {
                $inv = \App\Models\SesionInvitado::firstOrCreate(
                    ['sesion_id' => $sesion->id, 'user_id' => $u->id],
                    ['rsvp' => \App\Models\SesionInvitado::RSVP_PENDING, 'invited_at' => now()->subDays(3)]
                );
                // Ana acepta la del onboarding; Pedro rechaza la de marketing.
                if ($sesion->title === $sesiones[0]['title'] && $u->id === $coachModels['ana']->id) {
                    $inv->update(['rsvp' => \App\Models\SesionInvitado::RSVP_ACCEPTED, 'rsvp_at' => now()->subDay(), 'notified_at' => now()->subDays(2)]);
                }
                if ($sesion->title === $sesiones[1]['title'] && $u->id === $coachModels['pedro']->id) {
                    $inv->update(['rsvp' => \App\Models\SesionInvitado::RSVP_DECLINED, 'rsvp_at' => now()->subDay(), 'notified_at' => now()->subDays(2)]);
                }
            }
        }

        $this->command?->info('DemoFase2Seeder: OK (idempotente). Prefijo: '.self::EMAIL_PREFIX);
        $this->command?->info('Contraseña por default: password');
        $this->command?->info('Sesiones demo agendadas: '.count($sesiones));
    }
}

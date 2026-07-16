<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-medium text-ink">Contactos recibidos</h2>
    </x-slot>

    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6">
        <p class="mb-6 text-sm text-warmgray">
            Aquí ves a los estudios y marcas que te contactaron. El puente lo hace Kinvoo:
            nosotros nos comunicamos con ellos por ti, no publicamos tus datos ni los suyos.
        </p>

        @if ($contactos->isEmpty())
            <div class="rounded-2xl border border-line bg-white px-6 py-12 text-center">
                <div class="text-3xl" aria-hidden="true">✉️</div>
                <p class="mt-3 font-medium text-ink">Aún no tienes contactos</p>
                <p class="mt-1 text-sm text-warmgray">Cuando un estudio te contacte, aparecerá aquí con su mensaje.</p>
            </div>
        @else
            <div class="space-y-4">
                @foreach ($contactos as $c)
                    <div class="rounded-2xl border border-line bg-white p-5 sm:p-6">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div>
                                <p class="font-serif text-lg font-medium text-ink">{{ $c->contact_name }}</p>
                                <p class="text-sm text-warmgray">{{ $c->created_at->format('d/m/Y H:i') }}</p>
                            </div>
                            @if ($c->estado === \App\Enums\EstadoContacto::NoLeido)
                                <span class="rounded-full bg-lime/20 px-3 py-1 text-xs font-medium text-ink">Nuevo</span>
                            @endif
                        </div>

                        <p class="mt-3 whitespace-pre-line text-sm leading-relaxed text-ink/90">{{ $c->message }}</p>

                        <p class="mt-4 border-t border-line pt-4 text-xs text-warmgray">
                            Los datos del estudio son privados. Si quieres avanzar con esta oportunidad,
                            respóndenos y nosotros hacemos el puente.
                        </p>
                    </div>
                @endforeach
            </div>

            <div class="mt-6">
                {{ $contactos->links() }}
            </div>
        @endif
    </div>
</x-app-layout>

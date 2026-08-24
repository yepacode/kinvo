<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-medium text-ink">
            {{ __('Mi cuenta') }}
        </h2>
    </x-slot>

    <div class="mx-auto max-w-4xl px-4 py-10 sm:px-6">
        <div class="space-y-6">
            @if (session('status') === 'admin-no-se-elimina')
                <div class="rounded-2xl border border-lime/40 bg-lime/10 px-5 py-4 text-sm text-ink">
                    {{ __('Por seguridad, la cuenta del administrador no puede eliminarse desde este panel.') }}
                </div>
            @endif

            <div class="rounded-2xl border border-line bg-white p-5 sm:p-8">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="rounded-2xl border border-line bg-white p-5 sm:p-8">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            {{-- El owner (Admin) no ve la sección de eliminar cuenta: su baja no es autoservicio. --}}
            @unless (auth()->user()->esAdmin())
                <div class="rounded-2xl border border-line bg-white p-5 sm:p-8">
                    <div class="max-w-xl">
                        @include('profile.partials.delete-user-form')
                    </div>
                </div>
            @endunless
        </div>
    </div>
</x-app-layout>

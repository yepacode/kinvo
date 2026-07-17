{{--
  Selector de idioma (ES/EN). Se muestra como dos botones pill que hacen POST a
  /idioma/{locale}. Guarda cookie `locale` y recarga la vista. Se usa en el
  header público y en la nav autenticada.
--}}
@php
    $actual = app()->getLocale();
@endphp

<div class="flex items-center gap-1 text-xs">
    @foreach (['es' => 'ES', 'en' => 'EN'] as $codigo => $etiqueta)
        <form method="POST" action="{{ route('locale.switch', $codigo) }}" class="inline">
            @csrf
            <button type="submit"
                    class="rounded-full px-2.5 py-1 font-semibold uppercase transition
                           {{ $actual === $codigo
                              ? 'bg-sage text-cream'
                              : 'text-warmgray hover:text-sage' }}"
                    aria-label="{{ $codigo === 'es' ? __('Cambiar a español') : __('Switch to English') }}"
                    @if ($actual === $codigo) aria-current="true" @endif>
                {{ $etiqueta }}
            </button>
        </form>
    @endforeach
</div>

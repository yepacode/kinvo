{{--
    Ítem de menú bloqueado por falta de membresía.

    Feedback Karla 08-10-2026, textual: "mi lógica es que les tiene que
    aparecer su membresía, o como un candadito; no le pueden picar, no pueden
    ver más porque no están suscritos, y que los mande a la página de
    membresías".

    Antes estos ítems simplemente se ocultaban con un @if, así que quien no
    había pagado no sabía que la sección existía. Ahora se ve, en gris, con
    candado, y lleva a /membresias.
--}}
@props(['titulo' => null])

<a href="{{ route('membresias.index') }}"
   title="{{ $titulo ?? __('Disponible con tu membresía') }}"
   class="inline-flex items-center gap-1 px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-warmgray/60 hover:text-warmgray focus:outline-none transition duration-150 ease-in-out">
    <span aria-hidden="true" class="text-xs">🔒</span>
    {{ $slot }}
    <span class="sr-only">({{ __('requiere membresía') }})</span>
</a>

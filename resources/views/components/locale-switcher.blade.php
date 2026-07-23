{{--
  Selector de idioma minimalista: botón con ícono de globo que despliega
  un menú con las opciones ES/EN. El activo lleva un check y color sage.
  POST a /idioma/{locale} guarda cookie y persiste user.locale si hay sesión.
--}}
@php
    $actual = app()->getLocale();
    $idiomas = [
        'es' => ['label' => 'Español', 'short' => 'ES'],
        'en' => ['label' => 'English', 'short' => 'EN'],
    ];
@endphp

<div x-data="{ open: false }" @keydown.escape="open = false" class="relative inline-block">
    {{-- Botón: ícono de globo + código actual + chevron.
         Estilos inline críticos (display, gap, alineación) porque el CSS
         de welcome.blade define `nav button {…}` con más especificidad
         que las clases utility. --}}
    <button type="button"
            @click="open = ! open"
            :aria-expanded="open ? 'true' : 'false'"
            aria-haspopup="menu"
            aria-label="{{ __('Cambiar idioma') }}"
            style="display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.375rem 0.75rem; border-radius: 9999px; border: 1px solid #E0DDD5; background-color: rgba(255,255,255,0.7); font-size: 0.75rem; font-weight: 500; line-height: 1; color: #1C1C1A; cursor: pointer; transition: border-color 150ms, color 150ms;"
            onmouseover="this.style.borderColor='#5C7A5F'; this.style.color='#5C7A5F';"
            onmouseout="this.style.borderColor='#E0DDD5'; this.style.color='#1C1C1A';">
        {{-- Globo (heroicons outline: globo mundial) --}}
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="1.6"
             style="width: 1rem; height: 1rem; flex-shrink: 0;" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm0 0c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3s-4.5 4.03-4.5 9 2.015 9 4.5 9Zm-9-9h18"/>
        </svg>
        <span style="text-transform: uppercase; letter-spacing: 0.05em;">{{ $idiomas[$actual]['short'] }}</span>
        {{-- Chevron --}}
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
             style="width: 0.75rem; height: 0.75rem; flex-shrink: 0; opacity: 0.7; transition: transform 200ms;"
             :style="open ? 'width: 0.75rem; height: 0.75rem; flex-shrink: 0; opacity: 0.7; transform: rotate(180deg);' : ''"
             aria-hidden="true">
            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.06l3.71-3.83a.75.75 0 1 1 1.08 1.04l-4.25 4.39a.75.75 0 0 1-1.08 0L5.21 8.27a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd"/>
        </svg>
    </button>

    {{-- Dropdown --}}
    <div x-show="open" x-cloak
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="opacity-0 -translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click.outside="open = false"
         role="menu"
         style="position: absolute; right: 0; top: 100%; margin-top: 0.5rem; z-index: 50; width: 10rem; overflow: hidden; border-radius: 0.75rem; border: 1px solid #E0DDD5; background-color: #FFFFFF; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -4px rgba(0,0,0,0.1);">
        @foreach ($idiomas as $codigo => $info)
            @php $activo = $actual === $codigo; @endphp
            <form method="POST" action="{{ route('locale.switch', $codigo) }}" role="none" style="margin: 0;">
                @csrf
                <button type="submit"
                        role="menuitemradio"
                        aria-checked="{{ $activo ? 'true' : 'false' }}"
                        style="display: flex; align-items: center; justify-content: space-between; width: 100%; padding: 0.625rem 1rem; text-align: left; font-size: 0.875rem; line-height: 1.25; cursor: pointer; border: 0; background-color: {{ $activo ? 'rgba(92,122,95,0.1)' : 'transparent' }}; color: {{ $activo ? '#5C7A5F' : '#1C1C1A' }}; font-weight: {{ $activo ? 600 : 400 }};"
                        onmouseover="if(!this.getAttribute('aria-checked') || this.getAttribute('aria-checked')==='false') this.style.backgroundColor='#F7F4EE';"
                        onmouseout="if(!this.getAttribute('aria-checked') || this.getAttribute('aria-checked')==='false') this.style.backgroundColor='transparent';">
                    <span style="display: inline-flex; align-items: baseline; gap: 0.5rem;">
                        <span style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: {{ $activo ? '#5C7A5F' : '#6E6A63' }};">{{ $info['short'] }}</span>
                        <span>{{ $info['label'] }}</span>
                    </span>
                    @if ($activo)
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="#5C7A5F"
                             style="width: 1rem; height: 1rem; flex-shrink: 0;" aria-hidden="true">
                            <path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-8 8a1 1 0 0 1-1.4 0l-4-4a1 1 0 1 1 1.4-1.4L8 12.58l7.3-7.3a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/>
                        </svg>
                    @endif
                </button>
            </form>
        @endforeach
    </div>
</div>

{{-- Botón/link "Volver" compacto para las vistas Fase 2.
     Uso: <x-back-link :href="route('ofertas.mis-ofertas')" />
          <x-back-link href="javascript:history.back()" :value="__('← Regresar')" /> --}}
@props([
    'href' => 'javascript:history.back()',
    'value' => '← ' . __('Volver'),
])

<a href="{{ $href }}"
   {{ $attributes->merge(['class' => 'inline-flex items-center text-sm text-warmgray hover:text-sage transition mb-4']) }}>
    {{ $value }}
</a>

@props(['value', 'required' => false])

<label {{ $attributes->merge(['class' => 'block font-medium text-sm text-ink']) }}>
    {{ $value ?? $slot }}
    @if ($required)
        <span class="text-red-500" aria-hidden="true">*</span>
        <span class="sr-only">({{ __('obligatorio') }})</span>
    @endif
</label>

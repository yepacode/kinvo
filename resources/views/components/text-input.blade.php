@props(['disabled' => false])

{{-- min-h-11 y py-2 garantizan tap target ≥44px en iOS/Android (WCAG 2.5.5). --}}
<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-line text-ink focus:border-sage focus:ring-sage rounded-md shadow-sm min-h-11 py-2']) }}>

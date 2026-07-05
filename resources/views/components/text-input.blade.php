@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-line text-ink focus:border-sage focus:ring-sage rounded-md shadow-sm']) }}>

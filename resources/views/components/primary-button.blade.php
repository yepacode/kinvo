<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center rounded-full bg-sage px-6 py-2.5 text-sm font-semibold text-cream transition hover:bg-ink focus:outline-none focus:ring-2 focus:ring-sage focus:ring-offset-2']) }}>
    {{ $slot }}
</button>

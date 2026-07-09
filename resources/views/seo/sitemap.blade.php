<?php echo '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>{{ url('/') }}</loc>
        <changefreq>weekly</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc>{{ route('talento.index') }}</loc>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>
    <url>
        <loc>{{ route('membresias.index') }}</loc>
        <changefreq>monthly</changefreq>
        <priority>0.7</priority>
    </url>
    <url>
        <loc>{{ route('legal.privacidad') }}</loc>
        <changefreq>yearly</changefreq>
        <priority>0.3</priority>
    </url>
    <url>
        <loc>{{ route('legal.terminos') }}</loc>
        <changefreq>yearly</changefreq>
        <priority>0.3</priority>
    </url>
    @foreach ($perfiles as $p)
    <url>
        <loc>{{ route('talento.show', $p->slug) }}</loc>
        <lastmod>{{ $p->updated_at->toAtomString() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>
    @endforeach
    @foreach ($estudios as $e)
    <url>
        <loc>{{ route('estudio.show', $e->slug) }}</loc>
        <lastmod>{{ $e->updated_at->toAtomString() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.6</priority>
    </url>
    @endforeach
</urlset>

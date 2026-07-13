<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ landing('seo_title') }}</title>
<meta name="description" content="{{ landing('seo_description') }}">
<link rel="canonical" href="{{ url('/') }}">
<meta property="og:title" content="{{ landing('seo_title') }}">
<meta property="og:description" content="{{ landing('seo_description') }}">
<meta property="og:type" content="website">
<meta property="og:url" content="{{ url('/') }}">
<meta property="og:image" content="{{ landing_image('seo_og_image', 'img/landing/hero.jpg') }}">
<link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preconnect" href="https://api.fontshare.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;1,300;1,400&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">
<link href="https://api.fontshare.com/v2/css?f[]=satoshi@500,700,900&display=swap" rel="stylesheet">
<script type="application/ld+json">
{!! json_encode([
    '@'.'context' => 'https://schema.org',
    '@type' => 'Organization',
    'name' => landing('brand_name'),
    'description' => landing('seo_description'),
    'url' => url('/'),
    'logo' => asset('favicon.svg'),
    'email' => 'hola@gokinvoo.com',
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
</script>
<style>
@verbatim
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  --deep:   #1C1C1A;
  --bone:   #F7F4EE;
  --warm:   #EFECE4;
  --sage:   #5C7A5F;
  --sage-l: #A8BBA8;
  --stone:  #8A8A78;
  --citron: #C8C040;
  --border: #E0DDD5;
}

html { scroll-behavior: smooth; }
body {
  background: var(--bone);
  color: var(--deep);
  font-family: 'Inter', sans-serif;
  font-weight: 300;
  overflow-x: hidden;
  -webkit-font-smoothing: antialiased;
}

/* NAV */
nav {
  position: fixed; top: 0; left: 0; right: 0; z-index: 100;
  padding: 1.6rem 4rem;
  display: flex; justify-content: space-between; align-items: center;
  background: rgba(247,244,238,0.92); backdrop-filter: blur(20px);
}
.logo-block { display: flex; flex-direction: column; gap: 0.1rem; }
.logo { font-family: 'Inter', sans-serif; font-weight: 500; font-size: 1.7rem; letter-spacing: -0.07em; color: var(--deep); text-decoration: none; }
.nav-sub { font-size: 0.5rem; letter-spacing: 0.18em; text-transform: uppercase; color: var(--stone); font-style: italic; }
.nav-links { display: flex; gap: 3rem; list-style: none; align-items: center; }
.nav-links a { color: var(--stone); text-decoration: none; font-size: 0.72rem; letter-spacing: 0.08em; transition: color 0.3s; }
.nav-links a:hover { color: var(--deep); }
.nav-cta { color: var(--deep) !important; font-weight: 400 !important; border-bottom: 1px solid var(--deep); padding-bottom: 0.1rem; }

/* HERO — full bleed, minimal */
.hero {
  min-height: 100vh;
  display: grid; grid-template-columns: 1fr 1fr;
  padding-top: 72px;
}
.hero-right { align-self: stretch; }

.hero-left {
  padding: 0 4rem 4rem 4rem;
  display: flex; flex-direction: column; justify-content: flex-end;
  background: var(--bone);
}

.hero-eyebrow {
  font-size: 0.58rem; letter-spacing: 0.28em; text-transform: uppercase;
  color: var(--sage); margin-bottom: 2rem;
}

.hero-h1 {
  font-family: 'Cormorant Garamond', serif; font-weight: 300;
  font-size: clamp(3.2rem, 5.5vw, 5.5rem);
  line-height: 1.02; letter-spacing: -0.01em;
  color: var(--deep); margin-bottom: 2.5rem;
}
.hero-h1 em { font-style: italic; color: var(--sage); }

.hero-body {
  font-size: 0.84rem; line-height: 1.9; color: var(--stone);
  max-width: 300px; margin-bottom: 3rem;
}

.hero-actions { display: flex; gap: 2rem; align-items: center; }
.btn-primary {
  font-size: 0.65rem; letter-spacing: 0.14em; text-transform: uppercase;
  color: var(--bone); background: var(--deep); text-decoration: none;
  padding: 0.9rem 2rem; border: none; cursor: pointer; transition: background 0.3s; display: inline-block;
}
.btn-primary:hover { background: var(--sage); }
.btn-text {
  font-size: 0.65rem; letter-spacing: 0.1em; text-transform: uppercase;
  color: var(--stone); text-decoration: none; transition: color 0.3s;
  border-bottom: 1px solid var(--border); padding-bottom: 0.15rem;
}
.btn-text:hover { color: var(--deep); border-color: var(--deep); }

.hero-right {
  position: relative; overflow: hidden;
}
.hero-img { width: 100%; height: 100%; object-fit: cover; object-position: center; display: block; filter: brightness(0.96); }
.hero-overlay { position: absolute; inset: 0; background: linear-gradient(to right, rgba(247,244,238,0.08) 0%, transparent 30%); }
.hero-pill {
  position: absolute; bottom: 2rem; right: 2rem;
  font-size: 0.46rem; letter-spacing: 0.22em; text-transform: uppercase;
  color: rgba(247,244,238,0.92); border: 1px solid rgba(247,244,238,0.35);
  padding: 0.4rem 0.8rem; z-index: 2;
  background: rgba(28,28,26,0.28); backdrop-filter: blur(2px);
  text-shadow: 0 1px 3px rgba(0,0,0,0.45);
}

/* STATEMENT — lots of air */
.statement {
  padding: 10rem 4rem;
  display: grid; grid-template-columns: 1fr 2fr; gap: 8rem; align-items: start;
  background: var(--bone);
}
.st-label { font-size: 0.56rem; letter-spacing: 0.24em; text-transform: uppercase; color: var(--sage); }
.st-text {
  font-family: 'Cormorant Garamond', serif; font-weight: 300;
  font-size: clamp(1.8rem, 3.2vw, 2.8rem);
  line-height: 1.4; color: var(--deep);
}
.st-text em { font-style: italic; color: var(--sage); }

/* PILLARS — ultra minimal */
.pillars-wrap {
  padding: 0 4rem 8rem 4rem;
  background: var(--bone);
}
.pillars-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 4rem; border-top: 1px solid var(--border); padding-top: 2rem; }
.section-label { font-size: 0.56rem; letter-spacing: 0.24em; text-transform: uppercase; color: var(--sage); }
.section-h {
  font-family: 'Cormorant Garamond', serif; font-weight: 300; font-style: italic;
  font-size: clamp(1.4rem, 2.5vw, 2rem); color: var(--stone);
}

.pillars { display: grid; grid-template-columns: repeat(4,1fr); gap: 3rem; }
.pillar { }
.pillar-num { font-family: 'Cormorant Garamond', serif; font-size: 0.9rem; color: var(--sage-l); margin-bottom: 1.5rem; }
.pillar-title { font-size: 0.82rem; font-weight: 500; color: var(--deep); margin-bottom: 0.8rem; letter-spacing: 0.02em; }
.pillar-body { font-size: 0.74rem; line-height: 1.75; color: var(--stone); }

/* PHOTO DIVIDER — full bleed portrait */
.photo-divider { height: 70vh; position: relative; overflow: hidden; }
.photo-divider img { width: 100%; height: 100%; object-fit: cover; object-position: center 30%; display: block; }
.photo-divider-overlay { position: absolute; inset: 0; background: rgba(28,28,26,0.15); }

/* SESSIONS */
.sessions-wrap {
  padding: 8rem 4rem;
  display: grid; grid-template-columns: 1fr 1fr; gap: 8rem; align-items: start;
  background: var(--warm);
}
.sessions-left { }
.sessions-left .section-label { margin-bottom: 1rem; }
.sessions-h {
  font-family: 'Cormorant Garamond', serif; font-weight: 300;
  font-size: clamp(2rem, 3.5vw, 3rem); line-height: 1.15;
  color: var(--deep); margin-bottom: 1.5rem;
}
.sessions-h em { font-style: italic; color: var(--sage); }
.sessions-body { font-size: 0.82rem; line-height: 1.9; color: var(--stone); max-width: 320px; margin-bottom: 2.5rem; }

.sessions-right { padding-top: 2rem; }
.topics { display: flex; flex-direction: column; }
.topic {
  display: flex; align-items: baseline; gap: 1.5rem;
  padding: 1.2rem 0; border-bottom: 1px solid var(--border);
  transition: all 0.25s; cursor: default;
}
.topic:first-child { border-top: 1px solid var(--border); }
.topic:hover { padding-left: 0.4rem; }
.tn { font-family: 'Cormorant Garamond', serif; font-size: 0.7rem; color: var(--sage-l); min-width: 20px; }
.tt { font-size: 0.78rem; color: var(--stone); transition: color 0.25s; line-height: 1.5; }
.topic:hover .tt { color: var(--deep); }

/* FOR WHO */
.for-who {
  padding: 8rem 4rem;
  background: var(--bone);
}
.for-who-header { display: grid; grid-template-columns: 1fr 1fr; gap: 4rem; margin-bottom: 5rem; border-top: 1px solid var(--border); padding-top: 2rem; align-items: end; }
.fw-h {
  font-family: 'Cormorant Garamond', serif; font-weight: 300;
  font-size: clamp(2rem, 3.5vw, 3rem); line-height: 1.1; color: var(--deep);
}
.fw-h em { font-style: italic; color: var(--sage); }
.fw-body { font-size: 0.8rem; line-height: 1.85; color: var(--stone); max-width: 320px; }

.cards { display: grid; grid-template-columns: repeat(3,1fr); gap: 2rem; }
.card { padding: 2.5rem 0; border-top: 1px solid var(--border); }
.card-label { font-size: 0.56rem; letter-spacing: 0.2em; text-transform: uppercase; color: var(--sage); margin-bottom: 1rem; }
.card-title { font-family: 'Cormorant Garamond', serif; font-weight: 300; font-size: 1.4rem; color: var(--deep); margin-bottom: 0.8rem; line-height: 1.2; }
.card-body { font-size: 0.74rem; line-height: 1.75; color: var(--stone); }

/* QUOTE FULL */
.quote-full {
  padding: 8rem 4rem;
  background: var(--deep);
  text-align: center;
}
.quote-text {
  font-family: 'Cormorant Garamond', serif; font-weight: 300; font-style: italic;
  font-size: clamp(1.8rem, 4vw, 3.5rem);
  line-height: 1.35; color: rgba(247,244,238,0.82);
  max-width: 800px; margin: 0 auto 2rem;
}
.quote-text em { color: var(--citron); font-style: normal; }
.quote-attr { font-size: 0.56rem; letter-spacing: 0.22em; text-transform: uppercase; color: rgba(247,244,238,0.25); }

/* JOIN */
.join {
  padding: 10rem 4rem;
  background: var(--bone);
  display: grid; grid-template-columns: 1fr 1fr; gap: 8rem; align-items: center;
}
.join-left { }
.join-label { font-size: 0.56rem; letter-spacing: 0.24em; text-transform: uppercase; color: var(--sage); margin-bottom: 1.5rem; }
.join-h {
  font-family: 'Cormorant Garamond', serif; font-weight: 300;
  font-size: clamp(2.5rem, 4.5vw, 4rem); line-height: 1.05; color: var(--deep);
}
.join-h em { font-style: italic; color: var(--sage); }

.join-right { }
.join-body { font-size: 0.82rem; line-height: 1.9; color: var(--stone); margin-bottom: 2.5rem; }

.join-actions { display: flex; margin-bottom: 1.2rem; }
.form-note { font-size: 0.58rem; color: var(--stone); opacity: 0.6; margin-bottom: 1.5rem; letter-spacing: 0.04em; }
.toggles { display: flex; gap: 0.5rem; flex-wrap: wrap; }
.tog {
  padding: 0.5rem 1rem; border: 1px solid var(--border);
  background: transparent; color: var(--stone); text-decoration: none;
  font-family: 'Inter', sans-serif; font-size: 0.62rem;
  letter-spacing: 0.08em; text-transform: uppercase; cursor: pointer; transition: all 0.3s;
}
.tog:hover { border-color: var(--sage); color: var(--sage); }

/* FOOTER */
footer {
  padding: 3rem 4rem;
  display: grid; grid-template-columns: 1fr auto 1fr; align-items: center;
  border-top: 1px solid var(--border); background: var(--bone);
}
.footer-logo { font-family: 'Inter', sans-serif; font-weight: 500; font-size: 0.95rem; letter-spacing: -0.05em; color: var(--deep); display: block; margin-bottom: 0.2rem; }
.footer-tag { font-size: 0.46rem; letter-spacing: 0.18em; text-transform: uppercase; color: var(--stone); font-style: italic; }
.footer-links { display: flex; gap: 2rem; list-style: none; justify-content: center; }
.footer-links a { font-size: 0.68rem; color: var(--stone); text-decoration: none; letter-spacing: 0.06em; transition: color 0.3s; }
.footer-links a:hover { color: var(--deep); }
.footer-copy { font-size: 0.54rem; color: var(--stone); opacity: 0.35; text-align: right; letter-spacing: 0.06em; }

/* ANIM */
@keyframes up { from { opacity:0; transform:translateY(18px); } to { opacity:1; transform:translateY(0); } }
.hero-left > * { animation: up 0.8s ease forwards; opacity: 0; }
.hero-left > *:nth-child(1) { animation-delay: 0.1s; }
.hero-left > *:nth-child(2) { animation-delay: 0.22s; }
.hero-left > *:nth-child(3) { animation-delay: 0.34s; }
.hero-left > *:nth-child(4) { animation-delay: 0.46s; }

/* MOBILE */
@media (max-width: 768px) {
  nav { padding: 1.2rem 1.5rem; }
  .nav-links { display: none; }
  .hero { grid-template-columns: 1fr; height: auto; min-height: 0; }
  .hero-left { padding: 2.5rem 1.5rem 2.75rem; justify-content: flex-start; min-height: 0; }
  .hero-right { height: 58vh; min-height: 360px; order: -1; }
  .hero-img { object-position: 60% 32%; }
  .hero-pill { bottom: 1rem; right: 1rem; }
  .statement { grid-template-columns: 1fr; gap: 2rem; padding: 5rem 1.5rem; }
  .pillars-wrap { padding: 0 1.5rem 5rem; }
  .pillars-header { flex-direction: column; align-items: flex-start; gap: 1rem; }
  .pillars { grid-template-columns: 1fr 1fr; gap: 2rem; }
  .sessions-wrap { grid-template-columns: 1fr; gap: 3rem; padding: 5rem 1.5rem; }
  .for-who { padding: 5rem 1.5rem; }
  .for-who-header { grid-template-columns: 1fr; gap: 1.5rem; }
  .cards { grid-template-columns: 1fr; }
  .quote-full { padding: 5rem 1.5rem; }
  .join { grid-template-columns: 1fr; gap: 3rem; padding: 5rem 1.5rem; }
  .join-actions { flex-direction: column; }
  footer { grid-template-columns: 1fr; gap: 1.5rem; text-align: center; padding: 2rem 1.5rem; }
  .footer-copy { text-align: center; }
  .footer-links { flex-wrap: wrap; justify-content: center; }
}

/* ============================================================
   DIRECCIÓN EDITORIAL (doc WEB KINVOO)
   Satoshi Bold en titulares · cuerpo Inter light en gris cálido
   #4A4843 · contraste de escala dramático.
   ============================================================ */
:root { --body: #4A4843; }

.hero-h1, .st-text, .sessions-h, .fw-h, .join-h, .quote-text,
.card-title, .section-h {
  font-family: 'Satoshi', 'Inter', sans-serif;
  font-style: normal;
}
/* Titulares: bold de verdad, grandes, tracking apretado */
.hero-h1, .st-text, .sessions-h, .fw-h, .join-h { font-weight: 700; letter-spacing: -0.03em; }
.quote-text { font-weight: 700; }
.card-title { font-weight: 700; letter-spacing: -0.02em; }
.section-h  { font-weight: 500; font-style: normal; }

/* Énfasis *palabra* en salvia (sin itálica serif) */
.hero-h1 em, .st-text em, .sessions-h em, .fw-h em, .join-h em { font-style: normal; color: var(--sage); }
.quote-text em { font-style: normal; color: var(--citron); }

/* Escala dramática: titular enorme que ocupa el ancho */
.hero-h1    { font-size: clamp(3.4rem, 8vw, 8rem); line-height: 0.95; letter-spacing: -0.04em; }
.join-h     { font-size: clamp(2.8rem, 6vw, 5.5rem); line-height: 0.98; }
.st-text    { font-size: clamp(2.2rem, 4.5vw, 3.8rem); line-height: 1.16; }
.sessions-h { font-size: clamp(2.4rem, 5vw, 4.2rem); line-height: 1.04; }
.fw-h       { font-size: clamp(2.4rem, 5vw, 4.2rem); line-height: 1.04; }
.quote-text { font-size: clamp(2rem, 4.2vw, 3.6rem); }

/* Cuerpo: gris cálido, nunca negro puro */
.hero-body, .sessions-body, .fw-body, .card-body, .pillar-body, .join-body { color: var(--body); }

/* Números editoriales en Satoshi */
.pillar-num, .tn { font-family: 'Satoshi', 'Inter', sans-serif; font-weight: 700; }

/* Índice editorial: 01 · 02 · 03 antes de cada label (como revista) */
.sec-no {
  font-family: 'Satoshi', 'Inter', sans-serif; font-weight: 700;
  color: var(--sage-l); margin-right: .6rem; letter-spacing: .02em;
}

/* "Dato flotante": frase mínima desalineada sobre la foto (firma visual) */
.photo-divider-overlay { background: linear-gradient(to top right, rgba(20,18,14,.6) 0%, rgba(20,18,14,.22) 42%, transparent 72%); }
.float-data { position: absolute; left: clamp(1.5rem, 5vw, 4.5rem); bottom: clamp(1.5rem, 5vw, 3.5rem); z-index: 2; max-width: 85%; }
.float-data .fd-k { display: block; font-size: .58rem; letter-spacing: .26em; text-transform: uppercase; color: var(--citron); margin-bottom: .9rem; }
.float-data .fd-t {
  display: block; font-family: 'Satoshi', 'Inter', sans-serif; font-weight: 700;
  font-size: clamp(1.9rem, 4.2vw, 3.4rem); line-height: 1.0; letter-spacing: -.03em; color: #F7F4EE;
}

/* Acceso compacto en móvil (Entrar/Únete) — oculto en escritorio */
.nav-mobile { display: none; }
.nav-mobile a { color: var(--body); text-decoration: none; font-size: .74rem; letter-spacing: .05em; }
.nav-mobile a.nav-cta { color: var(--deep); border-bottom: 1px solid var(--deep); padding-bottom: .1rem; }

@media (max-width: 768px) {
  .hero-h1 { font-size: clamp(2.8rem, 13vw, 4.5rem); }
  .photo-divider { height: 62vw; }
  .float-data { left: 1.5rem; bottom: 1.5rem; }
  .nav-mobile { display: flex; gap: 1.3rem; align-items: center; }
}
@endverbatim
</style>
</head>
<body>

<!-- NAV -->
<nav>
  <div class="logo-block">
    <a href="{{ url('/') }}" class="logo">{{ landing('brand_name') }}</a>
    <span class="nav-sub">{{ landing('brand_tagline') }}</span>
  </div>
  <ul class="nav-links">
    <li><a href="#nosotros">Nosotros</a></li>
    <li><a href="#comunidad">Comunidad</a></li>
    <li><a href="#sessions">Sessions</a></li>
    <li><a href="{{ route('membresias.index') }}">Membresías</a></li>
    @auth
      <li><a href="{{ auth()->user()->homeRoute() }}">Mi cuenta</a></li>
    @else
      <li><a href="{{ route('login') }}">Entrar</a></li>
      <li><a href="{{ route('register') }}" class="nav-cta">Únete</a></li>
    @endauth
  </ul>
  {{-- Acceso compacto en móvil (el menú completo se oculta) --}}
  <div class="nav-mobile">
    @auth
      <a href="{{ auth()->user()->homeRoute() }}" class="nav-cta">Mi cuenta</a>
    @else
      <a href="{{ route('login') }}">Entrar</a>
      <a href="{{ route('register') }}" class="nav-cta">Únete</a>
    @endauth
  </div>
</nav>

<!-- HERO -->
<section class="hero">
  <div class="hero-left">
    <p class="hero-eyebrow">{{ landing('hero_eyebrow') }}</p>
    <h1 class="hero-h1">{!! landing_rich('hero_title') !!}</h1>
    <p class="hero-body">{{ landing('hero_body') }}</p>
    <div class="hero-actions">
      <a href="{{ route('register') }}" class="btn-primary">{{ landing('hero_cta1') }}</a>
      <a href="{{ route('talento.index') }}" class="btn-text">{{ landing('hero_cta2') }}</a>
    </div>
  </div>
  <div class="hero-right">
    <img class="hero-img" src="{{ landing_image('hero_image', 'img/landing/hero.jpg') }}" alt="Comunidad fitness" />
    <div class="hero-overlay"></div>
    <span class="hero-pill">{{ landing('hero_pill') }}</span>
  </div>
</section>

<!-- STATEMENT -->
<section class="statement" id="nosotros">
  <p class="st-label"><span class="sec-no">01</span>{{ landing('mission_label') }}</p>
  <p class="st-text">{!! landing_rich('mission_text') !!}</p>
</section>

<!-- PILLARS -->
<div class="pillars-wrap">
  <div class="pillars-header">
    <p class="section-label">{{ landing('pillars_label') }}</p>
    <h2 class="section-h">{{ landing('pillars_heading') }}</h2>
  </div>
  <div class="pillars">
    @foreach ([1,2,3,4] as $i)
    <div class="pillar">
      <p class="pillar-num">0{{ $i }}</p>
      <h3 class="pillar-title">{{ landing("pillar{$i}_title") }}</h3>
      <p class="pillar-body">{{ landing("pillar{$i}_body") }}</p>
    </div>
    @endforeach
  </div>
</div>

<!-- PHOTO DIVIDER -->
<div class="photo-divider">
  <img src="{{ landing_image('divider_image', 'img/landing/divider.jpg') }}" alt="Movimiento fitness" />
  <div class="photo-divider-overlay"></div>
  <div class="float-data">
    <span class="fd-k">Kinvoo · Comunidad</span>
    <span class="fd-t" lang="en">Where talent<br>meets fitness.</span>
  </div>
</div>

<!-- SESSIONS -->
<section class="sessions-wrap" id="sessions">
  <div class="sessions-left">
    <p class="section-label"><span class="sec-no">02</span>{{ landing('sessions_label') }}</p>
    <h2 class="sessions-h">{!! landing_rich('sessions_heading') !!}</h2>
    <p class="sessions-body">{{ landing('sessions_body') }}</p>
    <a href="#unete" class="btn-primary">{{ landing('sessions_cta') }}</a>
  </div>
  <div class="sessions-right">
    <div class="topics">
      @foreach ([1,2,3,4,5] as $i)
      <div class="topic"><span class="tn">0{{ $i }}</span><span class="tt">{{ landing("session_topic_{$i}") }}</span></div>
      @endforeach
    </div>
  </div>
</section>

<!-- FOR WHO -->
<section class="for-who" id="comunidad">
  <div class="for-who-header">
    <div>
      <p class="section-label"><span class="sec-no">03</span>{{ landing('forwho_label') }}</p>
      <h2 class="fw-h">{!! landing_rich('forwho_heading') !!}</h2>
    </div>
    <p class="fw-body">{{ landing('forwho_body') }}</p>
  </div>
  <div class="cards">
    @foreach ([1,2,3] as $i)
    <div class="card">
      <p class="card-label">{{ landing("card{$i}_label") }}</p>
      <h3 class="card-title">{{ landing("card{$i}_title") }}</h3>
      <p class="card-body">{{ landing("card{$i}_body") }}</p>
    </div>
    @endforeach
  </div>
</section>

<!-- QUOTE -->
<section class="quote-full">
  <p class="quote-text">{!! landing_rich('quote_text') !!}</p>
  <p class="quote-attr">{{ landing('quote_attr') }}</p>
</section>

<!-- JOIN -->
<section class="join" id="unete">
  <div class="join-left">
    <p class="join-label"><span class="sec-no">04</span>{{ landing('join_label') }}</p>
    <h2 class="join-h">{!! landing_rich('join_heading') !!}</h2>
  </div>
  <div class="join-right">
    <p class="join-body">{{ landing('join_body') }}</p>
    <div class="join-actions">
      <a href="{{ route('register') }}" class="btn-primary">{{ landing('join_cta') }}</a>
    </div>
    <p class="form-note">{{ landing('join_note') }}</p>
    <div class="toggles">
      <a href="{{ route('register') }}" class="tog">{{ landing('join_tog1') }}</a>
      <a href="{{ route('register') }}" class="tog">{{ landing('join_tog2') }}</a>
      <a href="mailto:hola@gokinvoo.com?subject=Quiero asistir a una sesión" class="tog">{{ landing('join_tog3') }}</a>
    </div>
  </div>
</section>

<!-- FOOTER -->
<footer>
  <div>
    <span class="footer-logo">{{ landing('brand_name') }}</span>
    <span class="footer-tag">{{ landing('footer_tag') }}</span>
  </div>
  <ul class="footer-links">
    <li><a href="#nosotros">Nosotros</a></li>
    <li><a href="#sessions">Sessions</a></li>
    <li><a href="{{ route('membresias.index') }}">Membresías</a></li>
    <li><a href="{{ route('legal.privacidad') }}">Aviso de Privacidad</a></li>
    <li><a href="{{ route('legal.terminos') }}">Términos y Condiciones</a></li>
    <li><a href="mailto:hola@gokinvoo.com">hola@gokinvoo.com</a></li>
  </ul>
  <p class="footer-copy">{{ landing('footer_copy') }}</p>
</footer>

</body>
</html>

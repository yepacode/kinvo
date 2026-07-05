# Auditoría multi-agente — Kinvoo (2026-07-03)

Revisión por 4 agentes independientes (Seguridad · Calidad de código · UI/UX+A11y · QA de flujos).
Veredicto general: **base muy sólida y bien construida** (autorización consistente, sin IDOR, eager
loading correcto, índices sensatos, ~95 tests reales, helper anti-XSS bien pensado). Los arreglos de
abajo son mayormente puntuales; hay **1 crítico** y un puñado de altos.

## 🔴 CRÍTICO

| # | Archivo | Problema | Fix |
|---|---------|----------|-----|
| C1 | `resources/views/talento/show.blade.php:11-27` | **XSS almacenado**: el `json_encode` del JSON-LD no usa `JSON_HEX_TAG`; un profesional puede meter `</script><script>…` en su **nombre**/headline y ejecutarlo en quien visite su perfil (incluido el owner). | Añadir `JSON_HEX_TAG\|JSON_HEX_AMP\|JSON_HEX_APOS\|JSON_HEX_QUOT` a los 2 `json_encode` (show + welcome) y quitar `JSON_UNESCAPED_SLASHES`. |

## 🟠 ALTO

| # | Archivo | Problema | Fix |
|---|---------|----------|-----|
| A1 | `TalentoController.php:27,58` · `ContactController.php:70-73` | **Perfil de usuario suspendido sigue público y contactable** (suspender solo cambia `users.estado`, no despublica). | Filtrar por estado del dueño: `whereHas('user', activo)` en buscador; `abort_unless($profile->user->estaActivo(),404)` en show/contacto. Crear scope `visiblePublicamente()`. |
| A2 | `routes/web.php:34-47` | **Gate incompleto**: `/profile`, `/guardados`, `/notificaciones` y `saves.toggle` solo tienen `auth` (falta `cuenta.activa`) → un pendiente/suspendido navega y guarda favoritos. | Mover ese grupo bajo `cuenta.activa` (decidir qué se permite a pendientes). |
| A3 | `routes/web.php` (register, contacto.store) | **Sin rate limit** en registro y contacto → spam de cuentas/correos; un contratante inunda a un profesional y al owner. *(lo marcaron Seguridad y QA)* | `throttle:5,1` en registro; `throttle` + regla "1 contacto por par cada X h" en contacto. |
| A4 | `app/Models/User.php:29-30` | **`nivel` y `estado` en `$fillable`** — mass assignment latente (hoy no explotable, pero un futuro `update($request->all())` permitiría auto-aprobarse/escalar). | Quitarlos de `$fillable` y setearlos siempre explícito. |
| A5 | `TalentoController.php:31-33` | **`like` es case-sensitive en PostgreSQL** → en prod buscar "yoga" NO encuentra "Yoga". Funciona en SQLite (dev) y engaña. | Usar `ilike` (Postgres) o `LOWER(col) LIKE LOWER(?)`. |
| A6 | `resources/css/app.css` (52 usos) | **`font-500/600/700` no son clases de Tailwind v4** → títulos/botones de la parte "app" se ven sin peso (aplanados). La landing no sufre (CSS propio). | Definir utilidades `@utility font-500{font-weight:500}`… o reemplazar por `font-medium/semibold/bold`. |
| A7 | `components/{text-input,input-label,primary-button,nav-link,auth-session-status}.blade.php` | **Componentes Breeze sin re-tematizar** → foco morado indigo, grises, botón gris en TODOS los formularios. Rompe la marca. | Re-tematizar a tokens Kinvoo (`focus:border-sage focus:ring-sage`, `text-ink/warmgray`). |

## 🟡 MEDIO

| # | Archivo | Problema | Fix |
|---|---------|----------|-----|
| M1 | `SiteSetting.php` | Caché `rememberForever` + `catch` silencioso → settings rancios o errores de BD ocultos. | `Log::warning($e)` antes de `return []`; considerar TTL en vez de forever. |
| M2 | `TalentoController.php:80` | `whereDate('created_at', today())` desalineado por zona horaria en Postgres → conteo de vistas duplica/pierde cerca de medianoche. | Comparar contra rango explícito / últimas 24h; fijar TZ de negocio. |
| M3 | `talento/show.blade.php:122` | `href` de "Sitio web" acepta `javascript:`/`data:` (la regla `url` no restringe esquema). | Validar `['url:http,https']`. |
| M4 | `auth/pending.blade.php` vs `CuentaAprobadaNotification.php` | La UI promete "te avisaremos por correo" pero la aprobación solo notifica in-app (`via` = database). | Añadir `'mail'` al `via()` o ajustar el texto. |
| M5 | `NuevoContactoNotification.php:25` | La notificación de contacto apunta al dashboard; **el profesional no tiene vista "Mis contactos"** para leer quién lo contactó (solo el owner los ve). | Crear vista "Mis contactos" del profesional y apuntar ahí; o meter datos en el email. |
| M6 | `TalentoController.php:23` | Filtro `modalidad` sin `Rule::in` (acepta cualquier string → vacío silencioso). | `Rule::in(ModalidadTrabajo::values)`. |
| M7 | `TalentoController.php:31-33` | `q` no escapa comodines `%` `_` (no es inyección, da resultados raros). | Escapar `\ % _` antes del patrón LIKE. |
| M8 | `layouts/navigation.blade.php` + `app.blade.php` | Nav autenticada blanca (estilo Breeze) rompe con el fondo crema+patrón de la landing → se sienten dos apps. | Unificar la nav al lenguaje de `public-layout`. |
| M9 | `auth/{forgot-password,verify-email,reset-password}.blade.php` | En **inglés** y sin marca (gris/indigo). | Traducir al español + botón salvia. |
| M10 | `dashboard.blade.php:53-55` | Queries y lógica en la vista (no testeable). | Mover a controlador/View Composer o métodos de modelo. |
| M11 | a11y | Grupos de radios/checkbox sin `<fieldset>/<legend>` (registro tipo, disciplinas); campana sin `aria-label` con conteo; contraste bajo en chips `text-sage` sobre `bg-sage/10`; `alt` genéricos; emojis como iconos funcionales. | Envolver en fieldset; aria-label en campana; oscurecer texto de chips; alt descriptivos; migrar emojis a SVG (Heroicons ya en uso). |

## 🟢 BAJO
- N+1 latentes si el botón guardar / `porcentajeCompleto` se llevan a listados (`save-button.blade.php`, `ProfessionalProfile::checklistPerfil` ignora relación cargada).
- Duplicación del patrón subir-imagen (Professional/Company controllers) → extraer trait/Action + Form Requests.
- `SaveController` reimplementa la query de `User::haGuardado` → método `toggleSave()` en User.
- Strings mágicos de flash (`'perfil-guardado'`…) → constantes/enum.
- Convención `*asterisco*`: `**x**` deja asteriscos dentro del `<em>` (cosmético; sin XSS).
- `ExampleTest` (Feature/Unit) stubs → eliminar.
- `target="_blank"` sin `rel="noopener"` en dashboard/edit.
- Avatares placeholder con el mismo emoji 🏋️ → usar iniciales.

## 🧪 Huecos de tests (la suite es buena, faltan)
- `ProfessionalProfileController::update` y `CompanyProfileController::update` (los de más lógica) — **sin test**.
- Colisión de slug; filtro por **certificación** en buscador; **paginación** (>12); orden **verificados primero**; aprobación/suspensión de usuarios + `CuentaAprobadaNotification`; fallback de `NotificationController::open` sin `url`.

## ✅ Confirmado que está BIEN
Autorización por rol+estado consistente (`abort_unless`), sin IDOR (todo scoped al usuario autenticado),
panel Filament cerrado al owner, registro nunca crea Admin, 404 en perfil/contacto no publicado,
`landing_rich` escapa antes de formatear (sin XSS del CMS), login con rate-limit + regenerate,
logout invalida sesión + no-cache, subida de archivos validada con nombre hash, sitemap sin datos
sensibles, sin inyección SQL.

---

## Orden de arreglo sugerido (antes del responsive)
1. **C1** (XSS JSON-LD) — trivial y explotable hoy.
2. **A4** (fillable) + **A5** (ilike) — rápidos y críticos para prod.
3. **A1** (suspendidos ocultos) + **A2** (gate) + **A3** (rate limit) — seguridad/lógica.
4. **A6** (font weights) + **A7** (componentes Breeze) — el 80% de la disonancia visual, y ayuda al responsive.
5. Medios seleccionados (M3, M6, M7, M9, M11) + tests faltantes.

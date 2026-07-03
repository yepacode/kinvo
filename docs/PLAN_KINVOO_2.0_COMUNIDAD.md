# Plan de Trabajo — Kinvoo 2.0 · Comunidad

**De directorio de talento → red social profesional del fitness.**
Cliente: Kinvoo (gokinvoo.com) · Desarrollo: MY Tech Solutions · Julio 2026

---

## 0. Contexto y decisión estratégica

Kinvoo 1.0 (fases F0–F8, ya construidas) es un **directorio/marketplace de talento**: perfil →
buscador → contacto → panel. Funciona y está probado (70 tests verdes), pero es **transaccional y
de una sola dirección**: no hay razón para volver a diario.

Kinvoo 2.0 convierte ese directorio en una **red social** que cumple la promesa de marca
("Comunidad · conexión real, pertenencia real · Sessions"). La pregunta que guía cada feature:

> **¿Por qué un coach abriría Kinvoo un martes cualquiera sin estar buscando trabajo?**

**Importante:** esto excede el brief original (directorio autogestionable). Es una **nueva fase con su
propia cotización**. Todo se construye ENCIMA de lo existente — no se reescribe nada. El sistema de
contacto evoluciona a mensajería; las taxonomías alimentan grupos y feed; los roles habilitan moderación.

---

## 1. Objetivo

- Crear un **loop de retención**: contenido + conexiones + notificaciones que traen al usuario de vuelta.
- Cerrar el marketplace en **dos direcciones** (contratantes publican ofertas; profesionales postulan).
- Cumplir los 4 pilares de marca: **Comunidad, Oportunidades, Crecimiento, Beneficios**.
- Habilitar **monetización real** (imposible en un simple directorio).

---

## 2. Nuevas decisiones técnicas

| Tema | Decisión | Motivo |
|------|----------|--------|
| Tiempo real | **Laravel Reverb** (websockets nativos) | Chat y notificaciones en vivo sin servicios externos |
| Notificaciones | Canal `database` de Laravel + Reverb para live + Filament | Base para todo el resto |
| Contenido multimedia | Spatie Media Library + almacenamiento (local→S3 en prod) | Fotos/videos de posts y perfiles |
| Relaciones polimórficas | saves, reacciones, comentarios, reportes, reseñas | Reutilizables en perfiles, posts, ofertas |
| Moderación | Resources Filament + tabla `reports` | UGC (contenido de usuarios) exige moderar |
| Búsqueda a escala | (futuro) Meilisearch/Scout si el volumen crece | Feed y buscador rápidos |

**Se mantiene:** Laravel 12 + Filament 3 + Blade + Tailwind v4 + PostgreSQL. Nada se reescribe.

---

## 3. Modelo de datos nuevo (resumen)

```
follows                 follower_user_id, followed_user_id            (grafo social, one-way)
posts                   user_id, tipo(texto|imagen|video|logro), body, media(json), group_id?, visibility
comments                user_id, post_id, parent_id?, body
reactions               user_id, reactable(morph: post|comment), tipo
conversations           + conversation_user(pivot: user_id, last_read_at)
messages                conversation_id, user_id, body, read_at
notifications           (tabla estándar Laravel: type, notifiable, data, read_at)
saves                   user_id, saveable(morph: profile|offer|post)
profile_views           professional_profile_id, viewer_user_id?, created_at
job_offers              company_profile_id, title, description, discipline_id, location_id, modalidad, tipo, rango_sueldo, estado
applications            job_offer_id, professional_profile_id, mensaje, estado(enviada|revisada|aceptada|rechazada)
reviews                 reviewer_user_id, reviewable(morph), rating, body, estado(pendiente|aprobada)
groups                  nombre, slug, descripcion, discipline_id?, location_id?, cover
group_user              group_id, user_id, rol(miembro|admin)
events                  organizer_user_id, titulo, descripcion, starts_at, modalidad, location_id?, cupo, cover
event_attendee          event_id, user_id, estado(voy|me_interesa)
benefits                partner, categoria(salud|legal|wellness), descripcion, logo, url, descuento
badges + badge_user     insignias y su asignación
reports                 reporter_user_id, reportable(morph), motivo, estado
```
Además: `professional_profiles`/`company_profiles` ganan `is_verified` + `verified_at`.

---

## 4. Fases por ola

> Estimación para **1 desarrollador**. Cada fase entrega algo usable y con tests.

### 🌊 OLA 1 — "Darle vida" (quick wins)
*Meta: que el producto se sienta vivo y con razones de volver, con bajo esfuerzo.*

| Fase | Alcance | Modelo | Est. |
|------|---------|--------|------|
| **C1.1 · Notificaciones (base)** | Tabla de notificaciones, campana en el header con contador, marcar leídas. Eventos: nuevo contacto, perfil aprobado. Base para todo lo demás. | notifications | 3–4 d |
| **C1.2 · Guardar / Favoritos** | Guardar perfiles (luego ofertas/posts). "Mis guardados". | saves (morph) | 2 d |
| **C1.3 · "Quién vio tu perfil"** | Registro de vistas, contador y lista en el dashboard del profesional. | profile_views | 2 d |
| **C1.4 · Perfil verificado** ✔️ | Badge de verificación que otorga el owner desde el panel; se muestra en perfil, cards y buscador. | is_verified | 1–2 d |
| **C1.5 · % de perfil completo** | Barra de progreso que empuja a completar el perfil; afecta orden en buscador. | (calculado) | 1 d |

**Subtotal Ola 1: ~9–11 días (~2 semanas).**

---

### 🌊 OLA 2 — "Volverla social" (el salto real)
*Meta: dejar de ser directorio; conexiones, contenido y mensajería.*

| Fase | Alcance | Modelo | Est. |
|------|---------|--------|------|
| **C2.0 · Infra tiempo real (Reverb)** | Montar Laravel Reverb + broadcasting; notificaciones en vivo (la campana se actualiza sola). | — | 2–3 d |
| **C2.1 · Seguir / Conexiones** | Botón seguir, contadores, listas de seguidores/seguidos, sugerencias a quién seguir. | follows | 3–4 d |
| **C2.2 · Mensajería directa (chat)** | Bandeja de conversaciones + chat 1:1 en tiempo real. El "Contactar" actual crea/abre una conversación. | conversations, messages | 6–8 d |
| **C2.3 · Feed de comunidad** | Publicar (texto/imagen/logro), feed de a quién sigues + "descubrir", reacciones y comentarios. | posts, comments, reactions | 8–10 d |
| **C2.4 · Bolsa de ofertas + postulaciones** | El contratante publica ofertas; el profesional postula; gestión de postulaciones. Cierra el marketplace en 2 direcciones. | job_offers, applications | 6–8 d |

**Subtotal Ola 2: ~25–33 días (~5–6.5 semanas).**

---

### 🌊 OLA 3 — "Comunidad y escala"
*Meta: lo que la hace "super genial" y defendible frente a un LinkedIn genérico.*

| Fase | Alcance | Modelo | Est. |
|------|---------|--------|------|
| **C3.1 · Grupos / Comunidades** | Grupos por disciplina o ciudad, membresías, feed de grupo. | groups, group_user | 6–8 d |
| **C3.2 · Sessions / Eventos** | Clases/eventos con RSVP y calendario (ya está en tu menú "Sessions"). | events, event_attendee | 5–6 d |
| **C3.3 · Reseñas / reputación** | Estudios reseñan coaches y viceversa (con moderación); rating en el perfil. | reviews | 3–4 d |
| **C3.4 · Beneficios / partners** | Catálogo de beneficios (salud, legal, wellness, descuentos) administrable por el owner. | benefits | 2–3 d |
| **C3.5 · Gamificación** | Insignias, "Top coach del mes", retos/rachas. | badges | 4–5 d |
| **C3.6 · Moderación & reportes** | Reportar contenido; herramientas de moderación en el panel del owner. | reports | 3–4 d |

**Subtotal Ola 3: ~23–30 días (~4.5–6 semanas).**

---

## 5. Estimación total

| Ola | Enfoque | Estimación |
|-----|---------|-----------|
| Ola 1 | Darle vida (quick wins) | ~2 semanas |
| Ola 2 | Volverla social | ~5–6.5 semanas |
| Ola 3 | Comunidad y escala | ~4.5–6 semanas |
| **Total Kinvoo 2.0** | | **~55–71 días · ~3–3.5 meses** (1 dev) |

Cada ola es **entregable y facturable por separado**. Se puede parar tras la Ola 1 o la Ola 2 y ya
tener un producto muy superior al actual.

---

## 6. Monetización (lo que esto habilita)

Un directorio casi no se monetiza; una red social **sí**:

- **Perfiles destacados** — el profesional paga por aparecer arriba en el buscador/feed.
- **Ofertas patrocinadas** — el contratante paga por destacar su vacante.
- **Suscripción para contratantes** — contactos/mensajes ilimitados, ver quién guardó su oferta.
- **Verificación premium** — badge + prioridad.
- **Comisión por colocación** — si Kinvoo cierra la contratación.
- **Beneficios/partners** — ingresos por afiliación o membresía premium de beneficios.

Recomendación: activar 1–2 vías tras la Ola 2 (cuando ya hay tráfico recurrente).

---

## 7. Moderación y seguridad (obligatorio con contenido de usuarios)

- **Reportes** (C3.6) desde cualquier post/comentario/perfil.
- **Cola de moderación** en el panel del owner (aprobar/ocultar/suspender).
- **Rate limiting** en posts, mensajes y postulaciones (anti-spam).
- **Bloqueo entre usuarios** (no recibir mensajes de X).
- Reutiliza los **roles y el estado** (activo/suspendido) que ya existen.

---

## 8. Reúso de lo ya construido (Kinvoo 1.0)

| Ya existe | Se convierte en / alimenta |
|-----------|----------------------------|
| Sistema de contacto (`contacts`) | Punto de entrada de la **mensajería** (C2.2) |
| Taxonomías (disciplinas, ubicaciones) | Filtros del **feed**, **grupos** y **ofertas** |
| Roles + estado (activo/suspendido) | **Moderación** y permisos de todo lo nuevo |
| Panel del owner (Filament) | Se le agregan Resources de posts, ofertas, reportes, beneficios |
| Perfiles + buscador + SEO | Base sobre la que se monta todo lo social |
| Bitácora + reportes | Se amplían con métricas de comunidad |

---

## 9. Pendientes de Kinvoo 1.0 que conviene cerrar antes o durante

- **Deploy (F9)** — subdominio, DNS, SSL, botón en la landing WordPress *(depende del hosting del cliente)*.
- **SMTP real** — hoy los correos van al log; hace falta conectar el correo de Kinvoo.
- **Verificación de email** — activable cuando haya SMTP.
- **Versión en inglés** — la interfaz (los catálogos ya tienen nombre ES/EN).

---

## 10. Decisiones pendientes (a definir con el cliente)

1. **Alcance:** ¿Ola 1, Olas 1–2, o el paquete completo?
2. **Modelo de conexión:** ¿"seguir" (una vía, estilo Instagram) o "conectar" (mutuo, estilo LinkedIn)? *(recomiendo seguir)*
3. **Monetización:** ¿cuándo y con qué vía(s) arrancar?
4. **Hosting** para tiempo real (Reverb necesita un proceso persistente; VPS ideal, no shared hosting).
5. **Contenido inicial:** ¿Kinvoo sembrará posts/eventos para que el feed no nazca vacío? (clave para el arranque).

---

## 11. Recomendación final

**Arrancar por la Ola 1** (2 semanas, bajo riesgo): con notificaciones + favoritos + "quién te vio" +
verificación, el producto pasa de "directorio" a "algo vivo" y valida el apetito del cliente por el
resto. Luego la **Ola 2** es donde nace la red social de verdad.

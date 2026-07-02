# Plan de Trabajo — Plataforma Bolsa de Talento (Kinvoo)

**Cliente:** Kinvoo (gokinvoo.com) · **Desarrollo:** MY Tech Solutions
**Fecha:** Julio 2026 · **Documento:** interno de desarrollo

---

## 0. Contexto y decisiones marco

Kinvoo es **la red profesional para la industria fitness** ("Where talent meets fitness"). La
plataforma Bolsa de Talento conecta **profesionales del fitness** (coaches, instructores, staff de
operaciones) con **contratantes** (estudios, gimnasios y marcas) mediante perfiles y búsqueda filtrada.

Decisiones marco ya acordadas:

| Punto | Decisión |
|-------|----------|
| Landing de marketing | **Se queda en WordPress** (gokinvoo.com), no se toca |
| Plataforma nueva | App **Laravel a la medida** en un **subdominio** (ej. `app.gokinvoo.com` / `talento.gokinvoo.com`) |
| Enlace entre ambas | Botón en la landing WordPress → redirige al subdominio de la app |
| Dominio y correo | Se conservan en Namecheap (`gokinvoo.com`, `hola@gokinvoo.com`); solo se agrega un registro DNS para el subdominio |
| Propiedad | Entrega total al cliente: código, accesos, hosting, dominio |

> **Ventaja de la arquitectura por subdominio:** no hay migración ni riesgo sobre el sitio actual.
> La landing sigue viva en WordPress y la app se despliega de forma independiente.

---

## 1. Identidad de marca (capturada de la landing)

**Paleta de color**

| Rol | HEX | Uso |
|-----|-----|-----|
| Primario (marca) | `#5C7A5F` | Verde salvia — botones, links, acentos |
| Primario claro | `#A8BBA8` | Estados hover, fondos suaves |
| Acento | `#C8C040` | Lima/oliva — CTAs, badges, highlights |
| Tinta / oscuro | `#1C1C1A` | Texto principal, header/footer oscuro |
| Fondo claro | `#F7F4EE` | Fondo base (crema) |
| Fondo secundario | `#EFECE4` | Cards, secciones alternas (beige) |
| Neutro cálido | `#8A8A78` | Texto secundario, placeholders |
| Bordes | `#E0DDD5` | Divisores, contornos de card |

**Tipografía**
- **Títulos:** Cormorant Garamond (serif, pesos 300/400, incluye itálicas)
- **Cuerpo:** sans-serif neutra a definir (Inter / Manrope encajan con el tono limpio) — confirmar contra la landing

**Tono:** inspirador pero profesional, comunidad y pertenencia, bilingüe ES/EN.

Estos valores se cargan en `tailwind.config.js` como tema para que toda la app respete la marca.

---

## 2. Stack técnico

| Capa | Tecnología |
|------|-----------|
| Backend | Laravel 12 (PHP 8.3) |
| Base de datos | PostgreSQL 16 |
| Panel admin (owner) | Filament 3 |
| Frontend público | Blade + Tailwind CSS (default). *Vue 3 + Inertia solo si el buscador exige mucha interactividad* |
| Autenticación | Laravel Breeze/Fortify + roles (profesional · contratante · admin) |
| Notificaciones | Mail vía SMTP / Resend |
| Almacenamiento de imágenes | Disco local + optimización (Spatie Media Library) |
| Deploy | Subdominio con PHP + PostgreSQL (Forge/Ploi sobre VPS, o cPanel PHP 8.3) |

---

## 3. Modelo de datos (propuesto)

```
users
  id, name, email, password, role(enum: professional|contractor|admin),
  status(enum: pending|active|suspended), email_verified_at, timestamps

professional_profiles            (1:1 con user role=professional)
  user_id, slug, photo_path, headline, bio, years_experience,
  modality(enum: presencial|online|hibrido), location_id,
  phone, socials(json), is_published

company_profiles                 (1:1 con user role=contractor)
  user_id, company_name, sector, logo_path, description, website, location_id

disciplines        (taxonomía)   id, name, slug
certifications     (taxonomía)   id, name, slug
locations          (taxonomía)   id, city, region, country

profile_discipline      (pivot professional_profiles ↔ disciplines)
certification_profile   (pivot professional_profiles ↔ certifications)

contacts                         (bitácora de contactos)
  id, contractor_user_id, professional_profile_id,
  message, contact_name, contact_email, contact_phone,
  status(enum: unread|read), created_at
```

Notas:
- Un profesional puede tener **varias disciplinas y varias certificaciones** (relación N:M).
- `modality` como enum simple; si el cliente quiere administrarla, se sube a taxonomía.
- `slug` en perfil profesional para URLs limpias y SEO (`/talento/juan-perez`).
- Cada **contacto** queda registrado (quién contactó a quién y cuándo) → alimenta reportes.

---

## 4. Roles y permisos

| Rol | Puede |
|-----|-------|
| **Profesional** | Registrarse, autoeditar su perfil, subir foto/certificaciones, publicar/ocultar perfil, recibir contactos |
| **Contratante** | Registrarse, buscar con filtros, ver perfiles, **contactar** profesionales, editar datos de empresa |
| **Admin (owner)** | Panel Filament: ver todo, aprobar registros, gestionar taxonomías, ver reportes y bitácora de contactos |

El botón **"Contactar"** es visible **solo para contratantes registrados** (no para visitantes ni otros profesionales).

---

## 5. Módulos funcionales (mapeados al brief)

- **5.1 Gestión de perfiles** (brief 4.1) — registro/login diferenciado, perfil profesional autoeditable, perfil contratante, panel de usuario.
- **5.2 Buscador y filtros** (brief 4.2) — filtros neutros (ubicación, disciplina, modalidad, certificaciones), resultados paginados con preview, vista detalle.
- **5.3 Sistema de contacto** (brief 4.3) — botón contactar (solo contratantes), formulario, email a profesional + owner, registro en bitácora.
- **5.4 Panel admin** (brief 4.4) — listados, aprobación (activable/desactivable), reportes simples, gestión de taxonomías.

---

## 6. Fases de desarrollo, entregables y estimación

> Estimación para **1 desarrollador**. Con reúso desde *New Talent Map* varias fases se comprimen (ver §9).

| Fase | Alcance | Entregable | Estimación |
|------|---------|-----------|-----------|
| **F0 · Setup** | Repo, Laravel 12, PostgreSQL, Filament 3, Tailwind con tema de marca, entorno staging | Proyecto base corriendo en staging | 1–2 d |
| **F1 · Auth & roles** | Registro/login diferenciado, verificación email, roles, redirección por rol | Alta de los 3 roles funcionando | 2–3 d |
| **F2 · Datos & taxonomías** | Migraciones, modelos, seeders (disciplinas/certificaciones/ubicaciones), CRUD Filament de taxonomías | Taxonomías administrables por el owner | 2–3 d |
| **F3 · Perfiles autoeditables** | Panel de usuario (profesional y contratante), upload de foto, vista pública de perfil con slug | Perfiles creables/editables + vista pública | 3–4 d |
| **F4 · Buscador & filtros** | Listado con filtros, paginación, cards preview, vista detalle | Buscador público funcional | 3–4 d |
| **F5 · Sistema de contacto** | Botón contactar (solo contratantes), formulario, emails, bitácora | Contacto end-to-end + registro | 2–3 d |
| **F6 · Panel admin owner** | Listados profesionales/contratantes, aprobación de registros, reportes del mes | Panel de administración completo | 2–3 d |
| **F7 · Branding & responsive** | Aplicar paleta + Cormorant Garamond, home de la app, responsive desktop/tablet/móvil, coherencia con landing | Frontend con identidad Kinvoo | 3–4 d |
| **F8 · SEO / seguridad / performance** | Meta tags dinámicos, sitemap.xml, JSON-LD, SSL, Core Web Vitals, backups automáticos | Checklist técnico en verde | 2 d |
| **F9 · Deploy, DNS & entrega** | Deploy en subdominio, DNS Namecheap, botón en landing WordPress, capacitación en vivo, videotutoriales, docs, 15 d soporte | Plataforma en producción + entrega | 2–3 d |

**Total estimado:** ~24–33 días de desarrollo (≈ **5–7 semanas** a tiempo completo de una persona).

---

## 7. Requerimientos técnicos (brief 5) — cómo se cubren

- **Responsive** desktop/tablet/móvil → Tailwind + pruebas por breakpoint (F7).
- **SEO nativo** (sin Rank Math): meta tags dinámicos por perfil, `sitemap.xml` generado, JSON-LD (`Person`/`Organization`), URLs con slug (F8).
- **Seguridad:** roles, policies, validación, rate limiting en contacto/registro, sin plugins de terceros.
- **Performance:** eager loading en buscador, índices en columnas de filtro, caché, Core Web Vitals en verde.
- **SSL + backups automáticos** en el deploy (F9).

---

## 7bis. Notas de entorno local (F0)

- **DB local:** SQLite (default de Laravel 12, cero config). **Producción sigue en PostgreSQL 16.**
  El código es agnóstico vía Eloquent; validar en Postgres antes de desplegar (F8/F9).
- **Build de assets bloqueado localmente:** Windows **Application Control** bloquea binarios nativos
  `.node` (Rollup y el motor `oxide` de Tailwind v4) → `npm run build` / Vite fallan localmente
  (`esbuild.exe` sí corre). Implicaciones:
  - **Producción NO se ve afectada:** el build corre en el servidor Linux al desplegar (F9).
  - **Dev local:** las vistas usan un **fallback de Tailwind v4 por CDN** (compila en el navegador)
    mientras no haya build de Vite. Para restaurar el pipeline nativo: allowlistar la ruta
    `node_modules` del proyecto en la política de App Control, o usar el binario standalone de Tailwind.

---

## 8. Hosting, infraestructura y DNS

1. **Hosting con PHP 8.3 + PostgreSQL** a nombre del cliente (recomendado: VPS gestionado con Forge/Ploi; alternativa cPanel).
2. Crear subdominio `app.gokinvoo.com` → registro **A/CNAME** en Namecheap apuntando al hosting.
3. Certificado **SSL** (Let's Encrypt) en el subdominio.
4. En la landing WordPress: agregar botón CTA ("Únete" / "Explora el talento") enlazando a `https://app.gokinvoo.com`.
5. Correo transaccional: SMTP de Namecheap o Resend para notificaciones.

---

## 9. Reúso desde *New Talent Map* (`C:/Users/DELL/claudia/newtalentmap`)

Revisado. Es un app grande de RRHH (Laravel 11 + Filament 3.2: competencias, 360, NOM-035, jornada).
El reúso es **por patrones/convenciones, no por fork del repo** (arrastra multi-tenancy y dominio HR
que aquí no aplican). Se cherry-pickean estas piezas:

| Se reutiliza | De dónde | Adaptación para Kinvoo |
|--------------|----------|------------------------|
| **Roles por enum** (`RolUsuario` + columna `nivel`, sin spatie) | `app/Enums/RolUsuario.php`, `User.php` | Nuevo enum `RolUsuario { Admin, Professional, Contractor }` |
| **Gate de panel Filament** (`FilamentUser` + `canAccessPanel`) | `User.php` | Panel admin solo para `Admin` |
| **Login Filament personalizado** | `app/Filament/Auth/AdminLogin.php` | Login del owner |
| **Convención de Resources CRUD** | `EmpleadosResource`, `UserResource`, taxonomías (`AreasResource`, `PuestosResource`) | Base para `ProfessionalResource`, `CompanyResource`, taxonomías |
| **Patrón de reportes** | `app/Filament/Reports` | Reportes simples del owner (F6) |

**Se descarta:** multi-tenancy (`HasTenants`/`empresas`) — Kinvoo es de **owner único**; y todo el
dominio HR. Por eso conviene **proyecto Laravel 12 limpio importando estos patrones**, no forkear.

**Impacto:** aun cherry-pickeando, F1/F2/F6 bajan ~30–40% vs. partir de cero (roles, auth, estilo de
Resources y reportes ya resueltos como plantilla mental/código base).

---

## 10. Decisiones

**Tomadas** (confirmadas con el cliente/equipo):

1. ✅ **Frontend:** Blade + Tailwind.
2. ✅ **Aprobación de registros:** **activada por defecto** — todo perfil nace en `status = pending` y el owner lo aprueba antes de que sea público/contactable.
3. ✅ **Idioma:** **Bilingüe ES/EN** — la interfaz, los correos y las taxonomías deben ser traducibles.
4. ✅ **Base de código:** **reusar *New Talent Map*** — adaptar roles, Filament, perfiles, aprobación y reportes.

**Pendientes** (no bloquean F0/F1, se resuelven en el camino):

5. **Subdominio:** ¿`app.`, `talento.` u otro?
6. **Hosting:** ¿el cliente ya tiene VPS/cPanel o hay que provisionarlo?
7. **Ubicación:** país/ciudades objetivo para el filtro de ubicación.
8. **Monetización:** ¿el contacto es libre o hay planes/límites a futuro? (afecta el modelo de datos).

### Impacto de "Bilingüe ES/EN" en el alcance

Añade i18n transversal: archivos de idioma (`lang/es`, `lang/en`), selector de idioma, taxonomías
con nombre traducible (`name_es` / `name_en` o tabla de traducciones), y plantillas de correo en ambos
idiomas. **Suma ~2–3 días** al total (nuevo rango: **~26–36 días**). Se distribuye en F2 (taxonomías),
F3/F4/F7 (interfaz) y F5 (correos).

---

## 11. Capacitación y entrega (brief 7)

- Sesión en vivo de administración del panel.
- Videotutoriales: aprobar usuarios, agregar disciplinas/certificaciones, ver reportes.
- Documentación básica de operación.
- **15 días de soporte sin costo** post-entrega.

# Guía de despliegue — Kinvoo

Pasos para publicar la plataforma en producción (cPanel / hosting compartido o VPS).

## Requisitos
- PHP **8.2+** con extensiones: `pdo`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `gd` (o `imagick`).
- **Composer 2**.
- **Node 18+** y npm (solo para compilar assets; no hace falta en el servidor si subes la carpeta `public/build` ya compilada).
- Base de datos **MySQL 8 / MariaDB 10.4+** o **PostgreSQL** (el código es portable).

## 1. Código y dependencias
```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build     # genera public/build (necesario para no depender de CDNs)
```
> Si el hosting no permite Node, ejecuta `npm run build` en local y **sube `public/build/`** junto con el código.

## 2. Variables de entorno (`.env` de producción)
Copia `.env.example` a `.env` y ajusta **como mínimo**:
```
APP_ENV=production
APP_DEBUG=false                 # ¡IMPORTANTE! nunca true en producción
APP_URL=https://gokinvoo.com    # o el subdominio real (con https)
APP_KEY=                        # generar en el paso 3

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...

# Correo real (no 'log'): SMTP del hosting o un proveedor (Mailgun, SES, etc.)
MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS="hola@gokinvoo.com"
MAIL_FROM_NAME="Kinvoo"
MAIL_OWNER_ADDRESS="hola@gokinvoo.com"

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

## 3. Preparar la app
```bash
php artisan key:generate --force
php artisan migrate --force
php artisan db:seed --force        # crea el owner, taxonomías y planes base
php artisan storage:link           # symlink public/storage -> storage/app/public
```
- Tras el seed, **cambia la contraseña del owner** (`hola@gokinvoo.com`, contraseña inicial `password`) desde el panel o la BD.
- Si `storage:link` falla en cPanel (sin symlinks), crea el enlace a mano o copia `storage/app/public` a `public/storage`.

## 4. Optimización (cachés)
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
> Si cambias el `.env` después, corre `php artisan config:clear` y vuelve a cachear.

## 5. Permisos
`storage/` y `bootstrap/cache/` deben ser escribibles por el usuario del servidor web.

## 6. Colas (emails y notificaciones)
Los correos/notificaciones usan la cola (`QUEUE_CONNECTION=database`). En el servidor:
```bash
php artisan queue:work --tries=3
```
Configúralo como proceso persistente (Supervisor) o un **cron** cada minuto:
```
* * * * * cd /ruta/al/proyecto && php artisan schedule:run >> /dev/null 2>&1
* * * * * cd /ruta/al/proyecto && php artisan queue:work --stop-when-empty >> /dev/null 2>&1
```

## Checklist final de seguridad
- [ ] `APP_DEBUG=false` y `APP_ENV=production`.
- [ ] `APP_KEY` generada.
- [ ] HTTPS activo (la app fuerza HSTS solo bajo https).
- [ ] **NO** existe un `public/robots.txt` estático (la app lo genera dinámicamente; si el deploy lo recrea, bórralo).
- [ ] `public/build/` presente (assets compilados; sin depender de CDNs).
- [ ] `php artisan storage:link` hecho (imágenes de usuarios se ven).
- [ ] Correo real configurado y probado (registro, aprobación, contacto).
- [ ] Contraseña del owner cambiada.
- [ ] Adjuntos de certificación en `storage/app` (privado), fuera de `public/`.

## Notas
- El **cobro en línea** de membresías (pasarela) **no** está integrado aún: la dueña activa membresías manualmente desde el panel (acción "Membresía"). Se conectará una pasarela (Stripe / MercadoPago / Conekta) en una fase posterior.
- El landing, SEO y textos legales/membresías son **editables desde el panel** (Configuración del sitio).

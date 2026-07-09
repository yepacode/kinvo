# Guía de servidor (cPanel) — arreglar imágenes y puesta en producción

Esta guía resuelve **por qué no se veían las imágenes** en el deploy anterior y deja el sitio funcionando.
Complementa a `DESPLIEGUE.md` con lo específico de **cPanel / hosting compartido**.

---

## 🔴 Problema #1: las imágenes que suben los usuarios no se ven

**Síntoma típico:** la landing y sus fotos SÍ cargan (están en `public/img/...`), pero las fotos de
perfil, logos de estudios y demás subidas por usuarios **NO** (salen rotas o dan 404).

**Causa:** Laravel guarda esos archivos en `storage/app/public/` y los sirve a través de un **enlace
simbólico** `public/storage → storage/app/public`. Si ese symlink **no existe o quedó roto** en el
servidor, las imágenes de usuarios no se ven. (Las de la landing sí, porque están directo en `public/`.)

### Solución A — con acceso a Terminal/SSH (lo ideal)
En cPanel: **Terminal** (o SSH), ubícate en la carpeta del proyecto y corre:
```bash
php artisan storage:link
```
Debe decir *"The [public/storage] link has been connected"*. Verifica que exista:
```bash
ls -la public/storage      # debe apuntar a ../storage/app/public
```

### Solución B — si NO hay symlinks o `storage:link` falla
Algunos hostings compartidos bloquean `symlink()`. Crea el enlace con este mini-script:

1. Crea un archivo `public/enlazar-storage.php` con:
```php
<?php
// Borra este archivo después de usarlo.
$target = __DIR__ . '/../storage/app/public';
$link   = __DIR__ . '/storage';
if (is_link($link) || file_exists($link)) { echo "Ya existe public/storage\n"; exit; }
echo symlink($target, $link) ? "Enlace creado ✅\n" : "No se pudo (symlink bloqueado)\n";
```
2. Ábrelo una vez en el navegador: `https://tudominio.com/enlazar-storage.php`
3. **Bórralo** después (`public/enlazar-storage.php`).

Si aún así no funciona (symlink prohibido), como **último recurso** copia la carpeta:
`storage/app/public/` → `public/storage/` (funciona, pero tendrás que re-copiar cuando suban archivos nuevos; mejor pedir symlink al soporte del hosting).

### Solución C (¡también necesaria!) — `APP_URL` correcto
Aunque el symlink exista, si `APP_URL` está mal, Laravel genera las URLs de las imágenes apuntando al
host equivocado (ej. `http://localhost/...`) y salen rotas. En el `.env` del servidor:
```
APP_URL=https://gokinvoo.com        # o el subdominio real, CON https y SIN slash final
```
Después de cambiarlo:
```bash
php artisan config:clear
php artisan config:cache
```

### Solución D — permisos
`storage/` y `bootstrap/cache/` deben ser escribibles (normalmente **755** o **775**). Si las subidas
fallan o no se guardan, revisa permisos y que el disco no esté lleno.

> **Checklist imágenes:** ① `php artisan storage:link` hecho · ② `APP_URL=https://...` correcto + `config:cache` · ③ permisos de `storage/` · ④ `FILESYSTEM_DISK=public` en `.env`.

---

## 🟠 Problema #2: el diseño/CSS o el JS no cargan (o se ve "sin estilos")

**Causa:** faltan los **assets compilados** (`public/build/`). En producción NO se usa el CDN.

**Solución:** compila y sube la carpeta `public/build/`:
```bash
npm ci && npm run build     # genera public/build/
```
Si el hosting no tiene Node, corre `npm run build` en tu PC y **sube `public/build/`** por FTP/File Manager.

---

## 🟡 Problema #3: estructura de carpetas en cPanel (dónde va `/public`)

En cPanel el dominio suele apuntar a `public_html/`, pero Laravel sirve desde su carpeta `public/`.
Dos formas de resolverlo (elige una):

**Opción 1 (recomendada) — apuntar el dominio a `/public`:**
Sube TODO el proyecto a una carpeta fuera de la web (ej. `/home/usuario/kinvoo/`) y en cPanel
(*Dominios → Document Root*) apunta el dominio/subdominio a `/home/usuario/kinvoo/public`.
Así `storage:link` y las rutas funcionan sin tocar nada.

**Opción 2 — Laravel en subcarpeta + contenido de `public` en `public_html`:**
Sube el proyecto a `public_html/kinvoo/` y **mueve el contenido de `kinvoo/public/`** a `public_html/`.
Luego edita `public_html/index.php` para que las dos rutas `require` apunten a `../kinvoo/...`:
```php
require __DIR__.'/../kinvoo/vendor/autoload.php';
$app = require_once __DIR__.'/../kinvoo/bootstrap/app.php';
```
(La Opción 1 es más limpia; usa la 2 solo si no puedes cambiar el Document Root.)

---

## 🔵 Problema #4: robots.txt / SEO

El sitio genera `robots.txt` y `sitemap.xml` **dinámicamente**. Si el deploy dejó un
`public/robots.txt` **estático**, bórralo (tapa al dinámico y desindexa mal). Verifica:
`https://tudominio.com/robots.txt` debe listar `Sitemap:` y los `Disallow:` de áreas privadas.

---

## ✅ Pasos completos de puesta en marcha (resumen)

```bash
# 1. Subir código + dependencias
composer install --no-dev --optimize-autoloader
npm ci && npm run build            # o subir public/build/ compilado

# 2. .env de producción (ver DESPLIEGUE.md): APP_ENV=production, APP_DEBUG=false,
#    APP_URL=https://..., DB_*, MAIL_* real (no 'log'), MAIL_OWNER_ADDRESS

# 3. Preparar
php artisan key:generate --force
php artisan migrate --force
php artisan db:seed --force        # crea owner (hola@gokinvoo.com / password → CAMBIAR), taxonomías, planes
php artisan storage:link           # ← el arreglo de las imágenes

# 4. Cachés
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Correos/notificaciones (cola). Cron cada minuto en cPanel:
#    * * * * * cd /ruta/al/proyecto && php artisan queue:work --stop-when-empty >> /dev/null 2>&1
```

## Verificación final
- [ ] Sube una foto de perfil de prueba y **confírmala visible** (esto valida symlink + APP_URL).
- [ ] `https://tudominio.com/admin` carga (login del owner).
- [ ] `robots.txt` y `sitemap.xml` responden bien.
- [ ] El CSS/JS carga (hay `public/build/`).
- [ ] `APP_DEBUG=false` (no debe verse ningún stack trace).
- [ ] Correo de prueba llega (registro / contacto).

> Si algo falla, activa temporalmente `APP_DEBUG=true` para ver el error exacto, corrígelo, y
> **vuelve a poner `APP_DEBUG=false`**. Los logs están en `storage/logs/laravel.log`.

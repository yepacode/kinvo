# Guía de despliegue — Hostinger compartido

Esta guía deja la app corriendo en un hosting compartido de Hostinger. Cubre el
proyecto Laravel 12 con el patrón "público en `public_html`, resto arriba".

Si estás en un VPS con Forge/Ploi/Cleavr, esta guía no aplica — usa el flujo
estándar de esas plataformas.

---

## 1. Estructura de carpetas en el servidor

Hostinger sirve `public_html/` como raíz web. Nuestro Laravel espera que
`public/` sea la raíz, así que colocamos el proyecto **fuera** de `public_html`
y sincronizamos el contenido:

```
/home/USUARIO/
├── domains/
│   └── gokinvoo.com/
│       └── public_html/          <- raíz web (contenido de public/ de Laravel)
├── kinvoo/                       <- proyecto Laravel completo (todo menos public/)
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── resources/
│   ├── routes/
│   ├── storage/
│   ├── vendor/
│   ├── .env
│   ├── artisan
│   └── composer.json
```

Editar `kinvoo/public/index.php` (que no está en el árbol) NO se usa: el que se
usa es el que va en `public_html/index.php`.

En `public_html/` copiar SOLO el contenido de `kinvoo/public/`:

```bash
cp -R kinvoo/public/* domains/gokinvoo.com/public_html/
cp    kinvoo/public/.htaccess domains/gokinvoo.com/public_html/
```

Y editar el `public_html/index.php` para que apunte al proyecto arriba:

```php
require __DIR__.'/../../kinvoo/vendor/autoload.php';
$app = require_once __DIR__.'/../../kinvoo/bootstrap/app.php';
```

(los `../../` dependen de la profundidad; ajusta según el árbol real).

---

## 2. Subir el código

Con **Git** por SSH (recomendado):
```bash
cd ~/kinvoo
git clone https://github.com/yepacode/kinvo.git .
composer install --no-dev --optimize-autoloader
```

Sin SSH: subir por FTP los archivos del proyecto a `~/kinvoo/` y las carpetas
de assets a `~/domains/gokinvoo.com/public_html/`.

---

## 3. `.env` de producción

Copiar `.env.example` a `.env` y editar. Mínimo indispensable:

```env
APP_NAME=Kinvoo
APP_ENV=production
APP_KEY=                                # generar con `php artisan key:generate`
APP_DEBUG=false                         # <-- CRÍTICO en prod
APP_URL=https://app.gokinvoo.com

APP_LOCALE=es
APP_FALLBACK_LOCALE=en
APP_TIMEZONE=America/Mexico_City

LOG_CHANNEL=stack
LOG_LEVEL=error                         # solo errores en prod

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=u284700479_kinvoo
DB_USERNAME=u284700479_kinvoo
DB_PASSWORD=**********

SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=database

# --- Correo (rellenar cuando el cliente confirme la cuenta) ---
MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com            # o smtp.namecheap.com / smtp.resend.com
MAIL_PORT=587
MAIL_USERNAME=hola@gokinvoo.com
MAIL_PASSWORD=**********
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=hola@gokinvoo.com
MAIL_FROM_NAME="Kinvoo"
MAIL_OWNER_ADDRESS=hola@gokinvoo.com
```

**Nunca subir `.env` a git**. `.gitignore` ya lo excluye.

---

## 4. Base de datos

Desde el panel de Hostinger → MySQL Databases:
1. Crear base `u284700479_kinvoo`, usuario y contraseña.
2. Copiar credenciales al `.env`.
3. Correr las migraciones:
   ```bash
   php artisan migrate --force
   php artisan db:seed --class=TaxonomiaSeeder --force
   php artisan db:seed --class=PlanSeeder --force
   ```
4. Crear el owner:
   ```bash
   php artisan tinker
   >>> $u = App\Models\User::create(['name'=>'Kinvoo Admin','email'=>'hola@gokinvoo.com','password'=>bcrypt('CAMBIAR-ESTA')]);
   >>> $u->forceFill(['nivel'=>App\Enums\RolUsuario::Admin, 'estado'=>App\Enums\EstadoUsuario::Activo])->save();
   ```

---

## 5. Storage / imágenes

Hostinger NO permite symlinks estándar en algunos planes. Alternativas:

**Opción A — Si `storage:link` funciona:**
```bash
php artisan storage:link
```

**Opción B — Regla en `.htaccess` del `public_html/`** (si el symlink no
funciona):
```apache
# Sirve /storage/... desde ../storage/app/public/... sin symlink
RewriteRule ^storage/(.*)$ ../../../kinvoo/storage/app/public/$1 [L]
```

Verificar permisos:
```bash
chmod -R 775 ~/kinvoo/storage ~/kinvoo/bootstrap/cache
```

---

## 6. Cola de correos (queue) — el punto donde se atoraba

En un compartido no se puede correr `queue:work` como daemon. Ya está resuelto
en el código: `routes/console.php` registra un Schedule que corre
`queue:work --stop-when-empty` cada minuto.

Solo hace falta agregar UN cron en Hostinger:

Panel Hostinger → Cron Jobs → Add new →
- Cada minuto
- Comando:
  ```bash
  cd ~/kinvoo && /opt/alt/php83/usr/bin/php artisan schedule:run >> /dev/null 2>&1
  ```
  (el path del `php` depende del panel; suele estar en `/opt/alt/php83/...`).

Con eso, cualquier correo encolado (bienvenida, contacto, etc.) se manda dentro
del minuto siguiente.

---

## 7. PHP: tamaños de subida

Panel Hostinger → PHP Configuration:
- `upload_max_filesize = 30M`
- `post_max_size = 30M`
- `memory_limit = 256M`
- `max_execution_time = 60`

Sin esto, la subida de multimedia de 25 MB falla.

---

## 8. Cachear config, rutas y vistas

Después de cada deploy:
```bash
php artisan optimize
# o desglosado:
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

Si tocas `.env` o `config/*.php`, correr `php artisan optimize:clear` primero.

---

## 9. HTTPS

Hostinger da SSL gratis (Let's Encrypt) desde el panel. Activarlo tras apuntar
el subdominio. En el `.env`, `APP_URL=https://...`. Laravel forzará HTTPS en
producción cuando detecte `APP_ENV=production`.

---

## 10. Verificaciones finales

- [ ] `https://app.gokinvoo.com/` responde 200.
- [ ] `/admin/login` responde 200. El owner puede entrar.
- [ ] Crear un usuario de prueba desde `/register`. Verificar que el correo de
      bienvenida llega dentro del minuto (revisar `storage/logs/laravel.log`
      si no llega para ver el error del SMTP).
- [ ] `php artisan queue:failed` está vacío.
- [ ] `storage/app/public/` es escribible y `/storage/...` sirve las imágenes.
- [ ] `APP_DEBUG=false` (verificar generando un 500 controlado — no debe
      exponer stack trace).
- [ ] `robots.txt` responde y `sitemap.xml` está actualizado.

---

## 11. Actualizaciones (deploys posteriores)

```bash
cd ~/kinvoo
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan optimize
# copiar cualquier archivo nuevo de public/ a public_html/
```

Para builds de Vite, correr `npm ci && npm run build` en un entorno con Node y
subir la carpeta `public/build/` al servidor. El sitio ya tiene fallback CDN
para dev; en prod es preferible usar el build.

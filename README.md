# laravel12-api-saas
API restfull Laravel v12.21.0 (PHO v8.2)

Instructions

1. # cd folder_name
  
2. # composer install

3. # cp .env.example .env

4. # php artisan key:generate

5. # php artisan jwt:secret

6. Crear la carpeta /storage/app/public/repo
# php artisan storage:link
# chmod -R 775 storage/app/public/repo
# chown -R www-data:www-data storage/app/public/repo 

7. # php artisan migrate:fresh --seed

8. Configuracion spatie/multitenant

Publicar config:

# php artisan vendor:publish --provider="Spatie\Multitenancy\MultitenancyServiceProvider" --tag="multitenancy-config"

9. Crear carpeta landlord dentro de database/migrations/:

# php artisan vendor:publish --provider="Spatie\Multitenancy\MultitenancyServiceProvider" --tag="multitenancy-migrations"

10. Ejecutar la migracion de tenant:

# php artisan migrate --path=database/migrations/landlord --database=landlord

11. ## Configuración de cookies JWT para login y logout (desarrollo y producción)

El trait `JWTResponseTrait` y el método `logout` usan cookies httpOnly para el token JWT. Para que funcionen correctamente en desarrollo y producción, debes configurar las siguientes variables en tu archivo `.env`:

```
# Dominio donde se enviará la cookie (ejemplo: 127.0.0.1, saas.local, tu-dominio.com)
FRONTEND_COOKIE_DOMAIN=127.0.0.1
# Usar true solo en producción con HTTPS
FRONTEND_COOKIE_SECURE=false
```

**En producción:**
- Cambia `FRONTEND_COOKIE_DOMAIN` al dominio real de tu frontend (ejemplo: saas.tuempresa.com).
- Cambia `FRONTEND_COOKIE_SECURE=true` si usas HTTPS.

**Importante:**
- El dominio y secure deben coincidir en login y logout para que la cookie se cree y elimine correctamente.
- Si usas un subdominio, pon el dominio raíz (ejemplo: `.tuempresa.com`).

**Ejemplo de .env para producción:**
```
FRONTEND_COOKIE_DOMAIN=saas.tuempresa.com
FRONTEND_COOKIE_SECURE=true
```

## Comandos para gestionar migraciones de Tenants

### Migrar un tenant específico (Desarrollo Local)

Ejecutar migraciones en un tenant específico por nombre de base de datos:

```bash
php artisan tenant:migrate {nombre_base_datos}
```

**Opciones:**
- `--fresh` : Elimina todas las tablas y ejecuta todas las migraciones desde cero
- `--seed` : Ejecuta los seeders después de migrar

**Ejemplos:**
```bash
# Migrar tenant específico
php artisan tenant:migrate cliente1

# Migrar desde cero con seeders
php artisan tenant:migrate cliente1 --fresh --seed

# Solo migrate fresh
php artisan tenant:migrate cliente1 --fresh
```

### Actualizar todos los tenants o uno específico

Ejecuta las migraciones pendientes en todos los tenants activos o en uno específico:

```bash
php artisan tenants:update
```

**Opciones:**
- `--tenant={id_o_dominio}` : Actualizar solo un tenant específico
- `--force` : Ejecutar sin confirmación

**Ejemplos:**
```bash
# Actualizar todos los tenants (pedirá confirmación)
php artisan tenants:update

# Actualizar un tenant específico por ID
php artisan tenants:update --tenant=1

# Actualizar un tenant específico por dominio
php artisan tenants:update --tenant=cliente1.agendas.local

# Actualizar sin confirmación
php artisan tenants:update --force
```

### Migrar todos los tenants

Ejecuta migraciones en todos los tenants registrados:

```bash
php artisan tenants:migrate
```

**Opciones:**
- `--path={ruta}` : Especificar ruta de las migraciones (opcional)

**Ejemplo:**
```bash
# Migrar todos los tenants
php artisan tenants:migrate

# Migrar con ruta específica
php artisan tenants:migrate --path=database/migrations
```

Listo!

# 🚀 Configuración de Deployment Multi-Environment

Este documento explica cómo configurar GitHub Actions para hacer deploy a múltiples VPS (agendas y parking) usando GitHub Environments.

## 📋 Estructura de Directorios en VPS

### Path estandarizado para TODOS los VPS:
```
/var/www/saas/
├── backend/          # Laravel backend
├── frontend/         # Quasar frontend
│   └── dist/        # Build de Quasar
└── docker/          # Configuraciones Docker
    └── default.conf # Nginx config
```

### Nombres de contenedores Docker:
- `saas-backend` (antes: agendas-backend)
- `saas-webserver` (antes: agendas-webserver)
- `saas-frontend` (antes: agendas-frontend)

## ⚙️ Configuración en GitHub

### Paso 1: Crear Environments en GitHub

1. Ve a tu repositorio en GitHub
2. Settings → Environments → New environment
3. Crea dos environments:
   - **agendas**
   - **parking**

### Paso 2: Configurar Secrets por Environment

#### Environment: **agendas**

| Secret Name | Valor | Descripción |
|-------------|-------|-------------|
| `VPS_HOST` | `IP o dominio del VPS de agendas` | Ej: `159.65.XXX.XXX` o `agendas.grupoados.com` |
| `VPS_USER` | `root` o tu usuario SSH | Usuario para conectarse por SSH |
| `VPS_SSH_KEY` | `-----BEGIN OPENSSH PRIVATE KEY-----...` | Llave privada SSH completa |

#### Environment: **parking**

| Secret Name | Valor | Descripción |
|-------------|-------|-------------|
| `VPS_HOST` | `IP o dominio del VPS de parking` | Ej: `159.65.YYY.YYY` o `parking.grupoados.com` |
| `VPS_USER` | `root` o tu usuario SSH | Usuario para conectarse por SSH |
| `VPS_SSH_KEY` | `-----BEGIN OPENSSH PRIVATE KEY-----...` | Llave privada SSH completa |

### Paso 3: Preparar los VPS

En **cada VPS**, ejecuta estos comandos:

```bash
# Crear estructura de directorios
mkdir -p /var/www/saas/{backend,frontend/dist,docker}

# Dar permisos
chown -R www-data:www-data /var/www/saas
chmod -R 755 /var/www/saas

# Verificar estructura
tree -L 2 /var/www/saas
```

### Paso 4: Actualizar docker-compose.yml en cada VPS

Renombra los servicios en tu `docker-compose.yml`:

```yaml
services:
  saas-backend:        # antes: agendas-backend
    # ... configuración
    
  saas-webserver:      # antes: agendas-webserver
    # ... configuración
    
  saas-frontend:       # antes: agendas-frontend
    # ... configuración
```

## 🎯 Cómo Hacer Deploy

### Deploy Automático (Push a main)

Por defecto, cada push a `main` hace deploy al environment **agendas**:

```bash
git add .
git commit -m "Update feature"
git push origin main
```

### Deploy Manual a Environment Específico

1. Ve a GitHub → Actions → Select workflow
2. Click en "Run workflow"
3. Selecciona el environment:
   - `agendas` → Deploy a VPS de agendas
   - `parking` → Deploy a VPS de parking
4. Click "Run workflow"

## 🔄 Migración desde Sistema Actual

### Si ya tienes `/var/www/agendas/`:

```bash
# En el VPS de agendas
cd /var/www
mv agendas saas

# Actualizar docker-compose.yml para usar nuevos nombres de servicios
cd /var/www/saas
nano docker-compose.yml  # Renombrar servicios

# Reiniciar contenedores
docker compose down
docker compose up -d
```

### Si prefieres mantener `/var/www/agendas/` y `/var/www/parking/`:

Modifica los workflows para usar:
```yaml
/var/www/${{ secrets.VPS_MODULE }}/backend
/var/www/${{ secrets.VPS_MODULE }}/frontend
```

Y agrega el secret `VPS_MODULE` a cada environment:
- agendas: `VPS_MODULE = "agendas"`
- parking: `VPS_MODULE = "parking"`

## ✅ Verificación

Después del primer deploy, verifica:

```bash
# En el VPS
cd /var/www/saas

# Ver contenedores corriendo
docker compose ps

# Ver logs
docker compose logs -f saas-backend
docker compose logs -f saas-frontend

# Verificar archivos
ls -la backend/
ls -la frontend/dist/
```

## 🆘 Troubleshooting

### Error: "Directory not found"
```bash
# Crear directorios manualmente en el VPS
mkdir -p /var/www/saas/{backend,frontend/dist,docker}
```

### Error: "Permission denied"
```bash
# Dar permisos correctos
chown -R www-data:www-data /var/www/saas
chmod -R 775 /var/www/saas
```

### Error: "Container not found"
```bash
# Verificar nombres de contenedores en docker-compose.yml
docker ps -a
# Deben ser: saas-backend, saas-webserver, saas-frontend
```

## 📌 Notas Importantes

1. **Un solo código, múltiples destinos**: El mismo código base se despliega a diferentes VPS según el environment seleccionado
2. **Secrets por environment**: Cada environment tiene sus propios secrets, evitando conflictos
3. **Path estandarizado**: `/var/www/saas/` facilita scripts y mantenimiento
4. **Escalabilidad**: Puedes agregar más environments (ej: `staging`, `demo`) sin modificar workflows

## 🔐 Seguridad

- **Nunca** comitees secrets al repositorio
- Usa SSH keys separadas por VPS si es posible
- Configura protección de branches en GitHub para `main`
- Considera agregar "Required reviewers" en environments de producción

# Deploy: Correcciones de sesión y asignación de estudios

## Cambios incluidos

| Fix | Descripción |
|-----|-------------|
| 1 | Migración de `SESSION_DRIVER=file` a `database` |
| 2 | Interceptor axios global 401 → toast + redirect a `/login` |
| 3 | Heartbeat: ping `/api/user` cada 25 min |
| 4 | Refresco automático de token CSRF desde cookie `XSRF-TOKEN` |
| 5 | `SESSION_LIFETIME` aumentado de 120 a 480 minutos (8h) |
| 6 | Corregido falso `agendado=1` en estudios sin paciente real |

---

## Paso a paso en producción

### 1. Subir cambios desde desarrollo

```bash
cd C:\laragon\www\lidiag
git add .
git commit -m "Session persistence: database driver, axios 401 interceptor, heartbeat, CSRF refresh"
git push origin main
```

### 2. Actualizar código en producción

```bash
cd /ruta/del/proyecto   # ej: /var/www/lidiag
git pull origin main
```

### 3. Instalar dependencias PHP

```bash
composer install --no-dev --optimize-autoloader
```

### 4. Correr migraciones

```bash
php artisan migrate
```

Esto crea la tabla `sessions` en la base de datos de producción.

### 5. Configurar `.env` de producción

Editar el `.env` del servidor de producción:

```ini
SESSION_DRIVER=database
SESSION_LIFETIME=480
```

> **Importante**: Verificar que `SANCTUM_STATEFUL_DOMAINS` incluya el dominio real de producción (ej: `midominio.com,midominio.com:443`). Si no está, las rutas API no se autenticarán vía cookie de sesión.

### 6. Build del frontend

```bash
npm ci --production
npx vite build
```

> Si el servidor de producción **no tiene Node.js**, ejecutar `npm ci && npx vite build` en desarrollo y copiar la carpeta `public/build/` a producción.

### 7. Limpiar caches de Laravel

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 8. Limpiar sesiones viejas (archivos)

```bash
# Ya no se usan con SESSION_DRIVER=database
rm -rf storage/framework/sessions/*
```

---

## Verificación post-deploy

1. **Login**: Iniciar sesión normalmente
2. **Heartbeat**: Abrir DevTools → Red → verificar que cada ~25 min se llama `GET /api/user`
3. **Refresco CSRF**: Hacer logout → login → verificar que la cookie `XSRF-TOKEN` se actualiza
4. **Sesión expirada**: Esperar 8h de inactividad o borrar la cookie de sesión → debe mostrar toast "Tu sesión expiró" y redirigir a `/login`
5. **Tabla sessions**: Verificar en BD que `SELECT * FROM sessions` muestra registros activos

---

## Rollback (si algo falla)

```bash
# Volver a sesión por archivos
git revert HEAD --no-edit
# O manualmente:
# 1. .env: SESSION_DRIVER=file, SESSION_LIFETIME=120
# 2. npx vite build
# 3. php artisan optimize:clear
```

---
name: spatie-permissions-workflow
description: Mantiene y depura roles/permisos con spatie/laravel-permission en LIDiag. Usar al agregar permisos nuevos, resolver 403 por middleware permission, o alinear seeders y cache.
---

# Spatie permisos/roles (LIDiag)

## Contexto del repo

- Guard esperado: `web` (ver `config/sanctum.php` y middlewares en rutas `permission:...,web`)
- Hay comandos de auditoría/documentados en `README.md`:
  - `php artisan permissions:audit`
  - `php artisan permissions:cache-reset`
  - `php artisan permissions:user-check ...`

## Cuando agregues un permiso nuevo

1. **Definir el string del permiso**
   - Convención: `modulo.accion[.subaccion]` (ej. `ecografias.consentimientos.pdf.generar`)
2. **Usarlo en rutas/controladores**
   - Middleware: `permission:permiso1|permiso2,web`
3. **Persistirlo**
   - Actualizar seeders del módulo (permisos + asignación a roles).
   - Si el seeding depende de orden (roles antes que permisos), respetarlo (ver notas en `README.md`).

## Auditoría y cache (comandos del proyecto)

- Comparar permisos usados vs BD:
  - `php artisan permissions:audit`
  - `php artisan permissions:audit --show-unused`
  - `php artisan permissions:audit --fail`
- Reset del cache (evita falsos negativos con `CACHE_DRIVER=file`):
  - `php artisan permissions:cache-reset`

## Debug de 403

- Verificar:
  - Usuario autenticado (Sanctum)
  - Rol asignado
  - Permiso existe en BD con guard `web`
  - Middleware incluye `,web` cuando aplica (patrón del repo)

## Debug rápido por CLI (cuando tengas el id/usuario)

- `php artisan permissions:user-check --search=nombre`
- `php artisan permissions:user-check 5 --perm=estudio.leerestudio`

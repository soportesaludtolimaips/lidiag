---
name: debug-sanctum-401-spa
description: Diagnostica y corrige 401/Unauthenticated en requests del SPA autenticados con cookies (Laravel Sanctum) en LIDiag. Usar cuando /api devuelve 401 en local (Laragon/lidiag.test) o cuando se sospecha stateful domains/CSRF/cookies.
---

# Debug: 401 en `/api/*` (Sanctum SPA)

## Contexto del repo

- Rutas SPA: `routes/api_*.php` (registradas bajo `/api` en `app/Providers/RouteServiceProvider.php`)
- Axios: `resources/js/bootstrap.js` (usa cookies `withCredentials=true` y CSRF desde `<meta name="csrf-token">`)
- Sanctum: `config/sanctum.php` agrega el host actual “best-effort” si `SANCTUM_STATEFUL_DOMAINS` no está definido.

## Diagnóstico rápido (orden recomendado)

1. **URL real**
   - `APP_URL` debe coincidir con el host por el que navegas (ej. `https://lidiag.test`).
   - Evitar mezclar `http://localhost` con `https://lidiag.test`.

2. **Stateful domains**
   - Revisar `SANCTUM_STATEFUL_DOMAINS` (si está vacío, `config/sanctum.php` agrega el host actual best-effort).
   - Confirmar que el host (ej. `lidiag.test`) quede incluido.

3. **Grupo `api`**
   - En LIDiag, `routes/api_*.php` se registran con middleware `api` + prefijo `/api` desde `app/Providers/RouteServiceProvider.php`.
   - Evitar endpoints de SPA fuera de `/api` (frontend debe llamar `/api/...`).

4. **Cookies / sesión**
   - Borrar cookies del dominio y re-login.
   - Verificar que el navegador esté en el mismo dominio (no mezclar `localhost` y `lidiag.test`).
   - En DevTools, confirmar que viajen cookies tipo `XSRF-TOKEN` y `laravel_session` en el request a `/api/...`.

5. **CSRF**
   - Si es un POST/PUT/DELETE:
     - Confirmar meta tag `csrf-token` en el layout (`resources/views/layouts/*.blade.php`).
     - Confirmar header `X-CSRF-TOKEN` (axios lo setea desde el meta si existe).

## Señales típicas y qué significan

- 401 solo en un host (ej. `localhost`) pero no en otro (ej. `lidiag.test`) → mismatch de dominios/cookies stateful.
- 419 (Page Expired) → CSRF/cookies no están alineados con el dominio o falta meta tag.

## Qué cambiar si el fix es de configuración

- Ajustar `APP_URL` y `SANCTUM_STATEFUL_DOMAINS` en `.env` (no en código).
- Si Vite/HMR está en HTTPS, mantener el sitio en HTTPS para evitar problemas de cookies.

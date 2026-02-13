---
name: laravel-add-api-endpoint
description: Crea o modifica endpoints API del SPA de LIDiag (Laravel) con Sanctum (cookies) y Spatie permisos. Usar cuando el usuario pida agregar una ruta /api, un controlador, o consumirlo desde Vue con axios.
---

# Laravel: agregar endpoint API (LIDiag)

## Objetivo

- Endpoint bajo `/api/...` (ver `app/Providers/RouteServiceProvider.php`)
- Protegido con `auth:sanctum` y, si aplica, `permission:...,web`
- Consumible desde Vue (`axios`) con cookies stateful

## Flujo recomendado

1. **Definir el endpoint**
   - ¿Módulo? (ej. `ecografias`, `estudios`, `helpdesk`)
   - ¿Tipo? (GET/POST/PUT/DELETE, JSON/archivo)
   - ¿Permiso? (string Spatie, guard `web`)

2. **Crear/editar ruta**
   - Archivo: `routes/api_<modulo>.php`
   - Debe quedar bajo `Route::middleware(['auth:sanctum'])->group(function () { ... })`
   - Agregar `->name('api.<...>')` consistente.
   - Si requiere permisos: `->middleware(['permission:permiso1|permiso2,web'])`

   **Plantilla (ejemplo):**

   ```php
   Route::post('/ecografias-consentimientos/token', [EcografiaConsentimientosController::class, 'crearToken'])
       ->name('api.ecografias.consentimientos.token')
       ->middleware(['permission:ecografias.consentimientos.capturar_firma,web']);
   ```

3. **Implementar controlador**
   - Ubicación típica: `app/Http/Controllers/<Modulo>/...Controller.php`
   - Añadir PHPDoc con **Flujo del proceso** (ver `docs/CODING_STANDARDS.md`).
   - Si el request es complejo, preferir `FormRequest` para validación.
   - Mantener respuestas consistentes (`JsonResponse`) y códigos HTTP.

   **Si agregas/actualizas controladores relevantes**: actualizar índice en `docs/CONTROLLERS.md`.

4. **Frontend (Vue)**
   - Consumir por `/api/...` (ver regla `api-routes-frontend.mdc`).
   - Si devuelve archivo: usar `responseType: 'blob'`.
   - `resources/js/bootstrap.js` ya tiene `withCredentials=true` y CSRF desde meta tag.

   **Plantilla axios (JSON):**

   ```js
   const { data } = await axios.post("/api/mi-endpoint", payload);
   ```

   **Plantilla axios (archivo):**

   ```js
   const res = await axios.get("/api/reporte", { responseType: "blob" });
   ```

5. **Permisos**
   - Si agregas un permiso nuevo, reflejarlo en seeders de permisos del módulo y validar con:
     - `php artisan permissions:audit`
     - `php artisan permissions:cache-reset`

## Checklist final

- Ruta en `routes/api_*.php` y no duplicada en `routes/web.php`
- Middleware `auth:sanctum` + `permission:...,web` cuando aplique
- PHPDoc completo en el método del controlador
- Frontend usa `/api/...`
- Si hay permiso nuevo: seeder + `permissions:audit` sin faltantes

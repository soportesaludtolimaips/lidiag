# AUDITORÍA DE SEGURIDAD — Login y Persistencia de Sesiones

| Campo | Valor |
|---|---|
| **Proyecto** | Lidiag (`C:\laragon\www\lidiag`) — Laravel 12.67 + Sanctum (SPA cookie) + httpOnly session |
| **Fecha** | 2026-08-24 |
| **Auditor** | Jhon Javer Lozano Arce |
| **Alcance** | Flujo login/logout, sesiones database-driver, endpoints debug, visor DICOM/OHIF, storage del navegador |
| **Commit de fixes** | Ver `git log` — "security(auth): auditoría completa" |

---

## 1. Arquitectura actual (verificada)

- **Guard default**: `web` (sesión en BD, driver `database`, tabla `sessions`).
- **Cookie**: `httpOnly=true`, `same_site=lax`, lifetime 8h.
- **CSRF**: `VerifyCsrfToken` en grupo web; axios envía `X-XSRF-TOKEN` (`bootstrap.js`).
- **Rate limiting login**: doble capa — throttle `login` (10/min por email+IP) + lockout manual 5 intentos/15 min con log `LOGIN_LOCKOUT`.
- **Auditoría de eventos**: `Login`/`Logout`/`Failed` → tabla `audit_log`; detección de sesión duplicada (`SESION_ABRUPTA`).
- **Headers de seguridad**: middleware `SecurityHeaders` (nosniff, X-Frame-Options SAMEORIGIN, Referrer-Policy, Permissions-Policy, HSTS sobre HTTPS, CSP Report-Only).
- **Sanctum**: tokens personales existen en BD (4) pero la app no los crea; guard `auth:sanctum` usado puntualmente (burn/DICOM).

## 2. Vulnerabilidades encontradas y CORREGIDAS

| # | Severidad | Vulnerabilidad | Corrección aplicada | Verificación |
|---|---|---|---|---|
| V-01 | 🔴 CRÍTICA | `GET /api/debug-session` exponía: ID de sesión, cookies crudas del header, sesiones de otros usuarios (hardcodeaba user_id=1) con IP y user-agent, config interna | Reescrito como diagnóstico mínimo `{auth_check, user_id_in_session}`; mantiene el flujo del interceptor 401 de `bootstrap.js` | curl sin auth → **401**; respuesta reducida |
| V-02 | 🔴 CRÍTICA | `GET /api/debug` volcaba el registro COMPLETO de rutas nombradas a cualquier usuario autenticado (mapa de attack surface) | **Endpoint eliminado** | curl → **404** |
| V-03 | 🔴 CRÍTICA | `GET /api/debug-session-auth` exponía email/nombre vía sanctum | **Endpoint eliminado** | route:list sin resultados |
| V-04 | 🔴 CRÍTICA | Visor DICOM/OHIF: 12 rutas QIDO/WADO servían imágenes y metadatos médicos (PHI) **sin autenticación** | Grupo completo bajo `Route::middleware(['auth'])` en `routes/api_OHIF_visor.php` | curl `/api/qido/studies` sin auth → **401** |
| V-05 | 🔴 CRÍTICA | `GET /api/test-ohif` pública listaba estudios DICOM | **Ruta eliminada** (queda documentada en comentario) | curl → **404** |
| V-06 | 🟠 ALTA | Fijación de sesión: no había `$request->session()->regenerate()` tras login exitoso | Regeneración inmediata en `LoginController::login()` en la rama de éxito | Código + patrón estándar Laravel |
| V-07 | 🟠 ALTA | Logout frontend NO purgaba storage: el siguiente usuario en la misma PC heredaba sede, médico y URL del visor (`ST-*`) | `Logout()` ahora elimina TODAS las claves `ST-*` de sessionStorage y localStorage antes de redirigir | `userProfileAndSearchComponent.vue` |
| V-08 | 🟡 MEDIA | Modelo User sin cast `'password' => 'hashed'`: asignación masiva futura guardaría texto plano | Cast agregado a `$casts` | `app/Models/User.php` |
| V-09 | 🟡 MEDIA | Logout no revocaba tokens Sanctum (hoy inocuo, pero quedarían vivos si se adoptan tokens) | `optional(Auth::user())->tokens()->delete()` en logout | `AuthService::logout()` |
| V-10 | 🟡 MEDIA | Cambio de contraseña no invalidaba otras sesiones ni tokens | `changePassword()` borra las demás filas de `sessions` del usuario y revoca sus tokens | `AuthService::changePassword()` |

## 3. Hallazgos que requieren decisión de .env / infraestructura (NO tocados por código)

Estos dependen del entorno y deben ajustarse al pasar a producción:

| # | Hallazgo | Recomendación para producción |
|---|---|---|
| ENV-01 | `APP_DEBUG=true` (verificado en runtime) | `APP_DEBUG=false` en producción: stack traces revelan rutas/config |
| ENV-02 | `session.secure=false` | Con HTTPS (lidiag.test ya sirve https): `SESSION_SECURE_COOKIE=true`. Nota: hoy está así para pruebas curl locales |
| ENV-03 | CORS incluye orígenes `localhost`/`127.0.0.1` | Eliminarlos de `CORS_ALLOWED_ORIGINS` en producción |
| ENV-04 | `.htaccess` sin redirect HTTPS | Laragon fuerza https en vhost; en Apache real agregar redirect 301 a https |
| ENV-05 | `TrustProxies::$proxies = null` | Si habrá reverse proxy/LB: configurar IPs confiables o `*` interno |
| ENV-06 | Sin flujo "olvidé mi contraseña" (solo consumo de token) | Decisión de negocio; habilitar `password.request/email` con throttle |

## 4. Fortalezas confirmadas (sin cambios)

- Lockout progresivo + throttle dedicado de login, auditado en `audit_log`.
- `invalidate()` + `regenerateToken()` en logout (CSRF fresco).
- `dontFlash` de contraseñas en excepciones.
- `$hidden` correcto en User (`password`, `remember_token`).
- `ForcePasswordChange` operativo para usuarios sin `password_changed_at`.
- HMAC firmado en webhook de tele-radiología.
- `files.php` protegido con `auth` + `ProtectFiles` (validación de disco contra allowlist, sin path traversal).

## 5. Riesgos residuales documentados

| # | Riesgo | Mitigación sugerida futura |
|---|---|---|
| R-01 | Files: permiso grueso por disco (un usuario con permiso lee cualquier archivo del disco — IDOR potencial si archivos son por-paciente) | Ownership check por registro |
| R-02 | Mezcla guards `auth` vs `auth:sanctum` entre módulos | Estandarizar: SPA cookie → `auth` web |
| R-03 | Sin lock/concurrencia al abrir lectura (2 médicos, mismo estudio) | Columna `leyendo_hasta` o token de reserva |
| R-04 | `Gate::before` da bypass total al rol Super Admin | Aceptar conscientemente; auditar acciones del rol |
| R-05 | OHIF externo: si el visor se sirve desde otro host, las cookies no viajarán | Migrar visor a tokens Sanctum |

## 6. Pruebas ejecutadas post-fix

| Prueba | Comando/Método | Resultado |
|---|---|---|
| Endpoints eliminados | `curl /api/test-ohif`, `/api/debug` | ✅ 404 |
| Debug-session sin auth | `curl /api/debug-session` | ✅ 401 |
| DICOM sin auth (PHI) | `curl /api/qido/studies` | ✅ 401 |
| Sintaxis PHP | `php -l` ×5 archivos | ✅ OK |
| Rutas compilan | `php artisan route:list` | ✅ OK |
| Frontend compila | `npm run build` | ✅ OK |

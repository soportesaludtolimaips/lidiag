# Plan de Reestructuración Arquitectónica — Lidiag

> **Contexto:** Monolito Laravel 10 + Vue 3 (Options API) + Bootstrap. Refactorización mayor aplicando SOLID, Service Layer, Composition API, y controles ISO 27001.
>
> **Estrategia:** Backend completo primero, Frontend después. Fases secuenciales con entregables por módulo.

---

## ✅ Fase 0: Infraestructura Backend — COMPLETADA

### ✅ 0.1 BaseService

**Archivo:** `app/Services/BaseService.php` ✅

- [x] Crear `BaseService` con response helpers: `success`, `created`, `updated`, `deleted`, `error`, `notFound`, `unauthorized`, `validationError`, `extractCheckedIds`
- [x] `transaction()` wrapper: `DB::transaction` + catch + Log + error response
- [ ] Pendiente: Manejo de excepciones centralizado

### ✅ 0.2 FileService con integridad SHA-256

**Archivo:** `app/Services/FileService.php` ✅

- [x] Crear `FileServiceInterface` con `store`, `replace`, `delete`, `verifyIntegrity`, `hash`
- [x] `store()`: SHA-256 hash, `Str::random(50)`, registra en activity_log con evento `archivo_subido`
- [x] `replace()`: elimina archivo anterior + store
- [x] `verifyIntegrity()`: compara SHA-256 con `hash_equals()`
- [x] `delete()`: elimina + registra evento `archivo_eliminado`

### ✅ 0.3 ActivityLog unificado

- [x] Agregado `LogsActivity` trait al modelo `User`
- [x] Global observer en `AppServiceProvider::registerGlobalActivityLog()` cubre todos los modelos (~80)
- [x] Deprecado `AuditLogger` helper (usa legacy `audit_log`)
- [x] Migrado `AuditLogService` para leer de `activity_log`

### ✅ 0.4 Form Requests (parcial)

- [x] Crear `StoreUserRequest`, `UpdateUserRequest` con helper `decoded()`
- [ ] Pendiente: BaseRequest con authorize() automático
- [ ] Pendiente: ~50 Form Requests restantes

### ✅ 0.5 API Resources (parcial)

- [x] Crear `UserResource`, `RoleResource`, `ModuleResource`, `SedeResource`, `ServicioCitaResource`
- [ ] Pendiente: ~30 Resources restantes

### ✅ 0.6 Seguridad ISO 27001

- [x] **A.9.4.2:** `throttle:login` (10/min por email+IP) en ruta `POST /login`
- [x] **A.12.6.1:** CSP via `SecurityHeaders` middleware ya implementado
- [x] **A.13.2.3:** Limpiado `.env.example` — secretos HMAC hardcodeados removidos
- [x] **A.16.1.5:** Sin paquetes debug instalados (Telescope, Debugbar ausentes)
- [x] **A.9.2.1:** `Gate::before()` para Super Admin en `AppServiceProvider`
- [x] **A.9.4.2 (lockout):** Lockout por 5 intentos fallidos (15 min) via Cache en AuthService::login()

---

## ✅ Fase 1: Refactorización Módulo Seguridad — COMPLETADA

**Controllers:** UserController ✅

- [x] **1.1** Crear `app/Services/Seguridad/UserService.php`
- [x] **1.3** Refactorizar `UserController` — 645→57 líneas, Service + FormRequests + Resources
- [x] **1.6** Crear `StoreUserRequest`, `UpdateUserRequest` con `decoded()` helper
- [x] **1.7** Crear `UserResource`, `RoleResource`, `ModuleResource`, `SedeResource`, `ServicioCitaResource`
- [x] **1.8** Agregado `LogsActivity` trait al modelo User
- [x] RolController, AuditLogController refactorizados (inyectan servicios)
- [x] AuditLogService migrado a `ActivityLog` (activity_log)
- [ ] Pendiente: Migrar Vue components a Composition API

---

## 🔄 Fase 2: Refactorización Módulo Configuración — EN PROGRESO

**Controllers (15):** 1,886 líneas totales. 3 arquetipos identificados:
- **Arquetipo A** — Simple CRUD (5): sin auth, sin audit
- **Arquetipo B** — Auth+Audit (7): middleware + AuditLogger, 4 ya con FormRequest
- **Arquetipo C** — Complex (3): file uploads, JSON decode, Validator::make()

### Estado general

- [x] **2.0** Exploración completa: bugs documentados, arquetipos clasificados
- [ ] **2.1** Crear `app/Services/Configuracion/` — servicios por controller
- [ ] **2.2** Refactorizar controllers: Service + FormRequest + Resource
- [ ] **2.3** Migrar uploads (logos sedes, consentimientos) a `FileService`
- [ ] **2.4** Migrar ~15 Vue components a Composition API
- [ ] **2.5** Estandarizar imports a `@/helpers/...`
- [ ] **2.6** Corregir bugs encontrados: modelos duplicados, AuditLogger vacío, validaciones con typo

### ✅ Batch 2a — YA TIENEN FORM REQUEST (4 controllers) — COMPLETADO

| # | Controller | Service | Antes | Después | Status |
|---|-----------|:-------:|:-----:|:-------:|:------:|
| 1 | `ConfigProductoController` | `ConfigProductoService` | 92 | 44 | ✅ |
| 2 | `ConfigPrioridadController` | `ConfigPrioridadService` | 82 | 49 | ✅ |
| 3 | `ConfigDiagnosticoController` | `ConfigDiagnosticoService` | 81 | 42 | ✅ |
| 4 | `ConfigAdminSaludController` | `ConfigAdminSaludService` | 80 | 42 | ✅ |
| | | | **335** | **177** | ✅ |

✅ Bug corregido B4 (AuditLogger::crear con modelo vacío en los 4 controllers)

### ✅ Batch 2b — Usan $request->validate() (8 controllers) — COMPLETADO

| # | Controller | Service | Antes | Después | Status |
|---|-----------|:-------:|:-----:|:-------:|:------:|
| 5 | `ConfigServicioCitasController` | `ConfigServicioCitaService` | 91 | 37 | ✅ |
| 6 | `ServicioProductoController` | `ServicioProductoService` | 85 | 31 | ✅ |
| 7 | `ConfigWhatsappController` | `ConfigWhatsappService` | 101 | 46 | ✅ |
| 8 | `ConfigTipoDocumentoController` | `ConfigTipoDocumentoService` | 136 | 50 | ✅ |
| 9 | `ConfigSedeWhatsappNumberController` | `ConfigSedeWhatsappNumberService` | 69 | 30 | ✅ |
| 10 | `ConfigPlantillaTipoController` | `ConfigPlantillaTipoService` | 138 | 50 | ✅ |
| 11 | `ConfigPlantillaController` | `ConfigPlantillaService` | 157 | 50 | ✅ |
| 12 | `ConfigModulosController` | `ConfigModuloService` | 34 | 14 | ✅ |
| | | | **811** | **308** | |

✅ Bug corregido B1 (misServicios duplicado) y B2 (listarModulos duplicado)
✅ Bug corregido B5 (valida nom_plantilla, actualiza nom_tipo)
✅ Bug corregido B6 (typo planitalla, tabla errónea)
✅ Bug corregido B8 (store usa FormRequest, update usa plain Request)

### ✅ Batch 2c — Complex o Validator::make() (3 controllers) — COMPLETADO

| # | Controller | Service | Antes | Después | Status |
|---|-----------|:-------:|:-----:|:-------:|:------:|
| 13 | `ConfigSedeController` | `ConfigSedeService` | 319 | 61 | ✅ |
| 14 | `ConfigRecomendacionProductoController` | `ConfigRecomendacionProductoService` | 139 | 49 | ✅ |
| 15 | `ConsentimientoInformadoController` | `ConsentimientoInformadoService` | 182 | 53 | ✅ |
| | | | **640** | **163** | |

✅ Bug corregido B7 (store/update duplicación file handling) — ahora usa FileService
✅ Bug corregido B9 (estado = 1 int vs bool)
✅ Bug corregido B10 (typo "crrectamente")
✅ Bug corregido B11 ($registro['estado'] sin null check)
✅ Migrado uploads a FileService con SHA-256

**Resumen Fase 2: 1,886 → 648 líneas totales (-66%)**

### Bugs a corregir durante la refactorización

| # | Severidad | Location | Issue |
|---|-----------|----------|-------|
| B1 | 🔴 ALTA | ConfigServicioCitasController | `misServicios()` duplicado de `index()` |
| B2 | 🔴 ALTA | ConfigModulosController | `listarModulos()` duplicado de `index()` |
| B3 | 🔴 ALTA | ConfigServicioCita + ConfigServicioCitas | DOS modelos para la MISMA tabla `config_servicio_citas` |
| B4 | 🔴 ALTA | 4 controllers con FormRequest | `AuditLogger::crear()` llamado con modelo vacío |
| B5 | 🟡 MEDIA | ConfigPlantillaTipoController | Valida `nom_plantilla` pero actualiza `nom_tipo` |
| B6 | 🟡 MEDIA | ConfigPlantillaController | Typo `planitalla`, tabla errónea `plantillas_tipos` |
| B7 | 🟡 MEDIA | ConfigSedeController | store() y update() con código duplicado (file handling) |
| B8 | 🟡 MEDIA | ConfigPrioridadController | store() usa FormRequest, update() usa plain Request |
| B9 | 🟢 BAJA | ConfigTipoDocumentoController | `estado = 1` (int) vs `true` (bool) |
| B10 | 🟢 BAJA | ConfigDiagnostico, ConfigPrioridad | Typo "crrectamente" |
| B11 | 🟢 BAJA | ConfigSedeController | `$registro['estado']` sin null check

---

## ✅ Fase 3: Refactorización Módulo Estudios — COMPLETADA

**Controllers (7):** EstudioController ✅, EstudioProductoController ✅, EstudioDiagnosticoController ✅, EstudioNotificacionController ✅, EstudioAudioController ✅, PlantillaEstudioController ✅, EstudioSoporteHCController ✅

**Vue components (6):** AsignarEstudioComponent, estudioAsignadosComponent, leerEstudioComponent, transcribirEstudioComponent, notificarEstudioComponent, audio-texto

- [x] **3.1** Crear `app/Services/Estudios/` — 7 services por dominio
- [x] **3.2** Eliminar controller-to-controller calls — ahora vía servicios inyectados (EstudioService, PlantillaEstudioService, EstudioNotificacionService)
- [x] **3.3** Migrar uploads de audios/soportes a servicios dedicados
- [x] **3.4** Refactorizar los 7 controllers a Services + FormRequests + Resources
- [ ] **3.5** Migrar los 6 Vue components a Composition API (pendiente)
- [ ] **3.6** Estandarizar errores con `handleAxiosErrorToast` (pendiente)

---

## Fase 4: Refactorización Módulo Ecografías — Prioridad ALTA

**Controllers (5):** EcografiaController, EcografiaProductoController, ecografiaLecturaController, EcografiaConsentimientosController, PlantillaEcografiaController

**Vue components (6):** admisionarComponent, lecturaComponent, ecografiasAdmisionadasComponent, ecografiasConsentimientosComponent, ecografiasConsentimientosPlantillasComponent, ecografiaFirmaConsentimientoComponent

- [ ] **4.1** Crear `app/Services/Ecografias/`
- [ ] **4.2** Refactorizar los 5 controllers a Services + FormRequests + Resources
- [ ] **4.3** Migrar uploads de firmas/consentimientos a `FileService` + SHA-256
- [ ] **4.4** Migrar los 6 Vue components a Composition API
- [ ] **4.5** Estandarizar manejo de errores

---

## Fase 5: Refactorización Módulo Help Desk — Prioridad MEDIA

**Controllers (8):** HelpDeskTicketsController, HelpDeskAdjuntosController, HelpDeskCategoriasController, HelpDeskEstadosController, HelpDeskNotificacionesController, HelpDeskPrioridadesController, HelpDeskReportesController, HelpDeskUsuariosController

**Vue components (5):** helpdeskTicketsComponent, helpdeskCrearTicketComponent, helpdeskDetalleTicketComponent, helpdeskNotificacionesComponent, helpdeskReportesComponent

- [ ] **5.1** Crear `app/Services/HelpDesk/`
- [ ] **5.2** Refactorizar los 8 controllers a Services + FormRequests + Resources
- [ ] **5.3** Migrar uploads de adjuntos a `FileService` + SHA-256
- [ ] **5.4** Migrar los 5 Vue components a Composition API
- [ ] **5.5** Reemplazar `hasRole()` inline por Spatie `permission:` middleware

---

## Fase 6: Refactorización Citas / Agenda — Prioridad MEDIA

**Controllers (5):** AgendaCitaController, AgendaDisponibilidaController, AgendaDisponibilidaDetalleController, ConfigCitaRechazoMotivoController, DashBoardController

**Vue components (5):** agendaDisponibilidadComponent, citasComponent, citasRevisionComponent, gestionCitasComponent, citasNotificationBadgeComponent, recomendacionProductoComponent

- [ ] **6.1** Crear `app/Services/Citas/` (AgendaCitaService, AgendaDisponibilidadService)
- [ ] **6.2** Refactorizar controllers a Services + FormRequests + Resources
- [ ] **6.3** Migrar Vue components a Composition API
- [ ] **6.4** Centralizar API calls en helper (patrón `@/helpers/api.js`)

---

## Fase 7: Refactorización Interfaces (SAHI, TeleRadiología) — Prioridad BAJA

**Controllers (4):** SAHIController, SAHIEcografiasController, SAHIEcografiasEnviarController, SAHITranscibirController, TeleRadiologiaController

- [ ] **7.1** Crear `app/Services/Interfaces/` — SAHIService, TeleRadiologiaService
- [ ] **7.2** Refactorizar controllers a Services
- [ ] **7.3** Mantener HMAC middleware (ya cumple ISO 27001)

---

## Fase 8: Refactorización Reportes — Prioridad BAJA

**Controllers (2):** ReporteProduccion, ResporteLecturaController
**Vue components (1):** reporteProduccionComponent

- [ ] **8.1** Crear Services de reportes
- [ ] **8.2** Refactorizar controllers
- [ ] **8.3** Migrar Vue component a Composition API

---

## Resumen de Esfuerzo

| Fase | Módulo | Controllers | Services | Form Requests | Resources | Vue Components | Prioridad |
|------|--------|:-----------:|:--------:|:-------------:|:---------:|:--------------:|:---------:|
| 0 | Infraestructura | — | 2 | 1 base | 1 base | — | ALTA |
| 1 | Seguridad | 3 | 2 | 4 | 3 | 4 | ALTA |
| 2 | Configuración | 15 | ~8 | ~20 | ~10 | 15 | ALTA |
| 3 | Estudios | 7 | 7 | 5 | 6 | 6 | ALTA ✅ |
| 4 | Ecografías | 5 | ~3 | ~6 | ~4 | 6 | ALTA |
| 5 | Help Desk | 8 | ~4 | ~6 | ~6 | 5 | MEDIA |
| 6 | Citas/Agenda | 5 | ~3 | ~4 | ~4 | 6 | MEDIA |
| 7 | Interfaces | 5 | ~2 | ~2 | ~2 | — | BAJA |
| 8 | Reportes | 2 | ~1 | ~1 | ~1 | 1 | BAJA |
| | **Total** | **50** | **~29** | **~52** | **~37** | **43** | |

---

## Notas Técnicas

- **Controllers → Services**: Los controllers existentes DEBEN mantenerse (misma URL, misma respuesta) — solo cambia la implementación interna
- **Transacciones**: `DB::transaction` en Services, nunca en Controllers
- **ActivityLog**: El trait se dispara automáticamente en eventos del modelo. Para eventos de archivo, llamar manualmente `activity()->event('archivo_subido')->log()`
- **FileService**: Reemplazar progresivamente cada `Storage::put()` / `$request->file()->store()` por `FileService::store()`
- **Composition API**: Migrar manteniendo la misma funcionalidad. No refactorizar lógica de negocio — solo sintaxis y organización
- **Tests**: Cada Service nuevo debe tener tests unitarios. Cada Controller refactorizado debe mantener los tests de integración existentes

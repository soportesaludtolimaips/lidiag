# Plan: Refactor Remaining Controllers to Service Layer Pattern

## Context
All remaining controllers across small modules need to follow the established Service Layer pattern (as seen in `Configuracion/ConfigProductoController.php`). This is the final batch - 18 controllers total, but many are trivial or scaffold-only.

## Key Findings from Analysis

### Pattern Reference
- **Controller**: Constructor-injects service, delegates all logic, returns `response()->json($result, $result['status'])`
- **Service**: Extends `BaseService`, contains all business logic, returns arrays via `$this->success()`, `$this->created()`, etc.
- **FormRequest**: Validation extracted from controller `store`/`update` methods
- **AuditLogger**: Used in services for create/update/delete operations

### Files to DELETE (1)
1. `app/Http/Controllers/ConfigRecomendacionProductoController.php` - Empty scaffold (all methods return nothing), `Configuracion/ConfigRecomendacionProductoController.php` already refactored

### Files to SKIP (no refactoring needed) (6)
1. **HomeController.php** (34 lines) - Just returns `view('home')`, no logic to extract
2. **Auth/VerificationController.php** (42 lines) - Breeze scaffold using `VerifiesEmails` trait, no custom logic
3. **Auth/ResetPasswordController.php** (30 lines) - Breeze scaffold using `ResetsPasswords` trait, no custom logic
4. **Auth/PasswordChangeController.php** (41 lines) - Simple validation + `$user->update()`, no complex logic
5. **Reportes/ResporteLecturaController.php** (34 lines) - Utility class (not a route controller), takes constructor args, generates PDF
6. **ConfigRecomendacionProductoController root** (65 lines) - All empty methods, duplicate of already-refactored version

### Controllers to REFACTOR (12)

---

## Wave 1: Simple CRUD Controllers (parallel - low risk)

### 1. Agenda/ConfigCitaRechazoMotivoController (52 lines)
**Create:**
- `app/Services/Agenda/ConfigCitaRechazoMotivoService.php` - CRUD for motivo model
- `app/Http/Requests/Agenda/ConfigCitaRechazoMotivoRequest.php` - Validation for store/update

**Modify:** Controller to inject service, delegate calls

### 2. Agenda/AgendaDisponibilidaDetalleController (94 lines)
**Create:**
- `app/Services/Agenda/AgendaDisponibilidaDetalleService.php` - CRUD + slot generation logic
- `app/Http/Requests/Agenda/AgendaDisponibilidaDetalleRequest.php` - Validation

**Modify:** Controller to inject service, delegate calls

### 3. Agenda/AgendaDisponibilidaController (238 lines)
**Create:**
- `app/Services/Agenda/AgendaDisponibilidaService.php` - Complex CRUD with slot generation, user-sede validation, DB transactions
- `app/Http/Requests/Agenda/AgendaDisponibilidaRequest.php` - Validation for store/update (includes custom messages)

**Modify:** Controller to inject service, delegate calls

---

## Wave 2: Medium Complexity Controllers (parallel - moderate risk)

### 4. Consentimientos/ConsentimientoPlantillasController (215 lines)
**Create:**
- `app/Services/Consentimientos/ConsentimientoPlantillaService.php` - Versioning logic, vigente management, PDF preview
- `app/Http/Requests/Consentimientos/ConsentimientoPlantillaRequest.php` - Validation for store/update

**Modify:** Controller to inject service, delegate calls

### 5. Reportes/ReporteProduccion.php (121 lines)
**Create:**
- `app/Services/Reportes/ReporteProduccionService.php` - Excel export logic with normalizeNullable helper

**Modify:** Controller to inject service, delegate calls

### 6. Dcm4chee/StudyController (151 lines)
**Create:**
- `app/Services/Dcm4chee/Dcm4cheeStudyService.php` - Dynamic DB connection, PACS query, cross-reference with Lidiag
- `app/Http/Requests/Dcm4chee/ListarEstudiosRequest.php` - Validation

**Modify:** Controller to inject service, delegate calls

### 7. FileAccessController (43 lines)
**Create:**
- `app/Services/FileAccessService.php` - File serving with disk validation, encryption, MIME detection

**Modify:** Controller to inject service, delegate calls

---

## Wave 3: Complex Controllers (parallel - higher risk)

### 8. OHIF_visor/OhifVisorController (396 lines)
**Create:**
- `app/Services/OHIF/OhifVisorService.php` - All DICOM metadata, QIDO-RS, WADO-RS, dynamic DB connections, HTTP proxy

**Modify:** Controller to inject service, delegate calls (8 methods to move)

### 9. Importar/ImportarDicom (331 lines)
**Create:**
- `app/Services/Importar/ImportarDicomService.php` - DICOM import logic, multi-DB switching, data migration

**Modify:** Controller to inject service, delegate calls

### 10. Auth/LoginController (130 lines)
**Create:**
- `app/Services/Auth/AuthService.php` - Login with session detection, logging, logout

**Modify:** Controller to inject service, delegate calls

### 11. Dashboard/DashBoardController (711 lines)
**Create:**
- `app/Services/Dashboard/DashboardService.php` - All 10 query methods (indicadores, operacion, series, calidad, etc.), constants, cache logic, scope resolution

**Modify:** Controller to inject service, delegate calls

---

## Files Summary

### New Services (11 files):
1. `app/Services/Agenda/ConfigCitaRechazoMotivoService.php`
2. `app/Services/Agenda/AgendaDisponibilidaDetalleService.php`
3. `app/Services/Agenda/AgendaDisponibilidaService.php`
4. `app/Services/Consentimientos/ConsentimientoPlantillaService.php`
5. `app/Services/Reportes/ReporteProduccionService.php`
6. `app/Services/Dcm4chee/Dcm4cheeStudyService.php`
7. `app/Services/FileAccessService.php`
8. `app/Services/OHIF/OhifVisorService.php`
9. `app/Services/Importar/ImportarDicomService.php`
10. `app/Services/Auth/AuthService.php`
11. `app/Services/Dashboard/DashboardService.php`

### New FormRequests (4 files):
1. `app/Http/Requests/Agenda/ConfigCitaRechazoMotivoRequest.php`
2. `app/Http/Requests/Agenda/AgendaDisponibilidaDetalleRequest.php`
3. `app/Http/Requests/Agenda/AgendaDisponibilidaRequest.php`
4. `app/Http/Requests/Dcm4chee/ListarEstudiosRequest.php`

### Modified Controllers (12 files):
1. `app/Http/Controllers/Agenda/ConfigCitaRechazoMotivoController.php`
2. `app/Http/Controllers/Agenda/AgendaDisponibilidaDetalleController.php`
3. `app/Http/Controllers/Agenda/AgendaDisponibilidaController.php`
4. `app/Http/Controllers/Consentimientos/ConsentimientoPlantillasController.php`
5. `app/Http/Controllers/Reportes/ReporteProduccion.php`
6. `app/Http/Controllers/Dcm4chee/StudyController.php`
7. `app/Http/Controllers/FileAccessController.php`
8. `app/Http/Controllers/OHIF_visor/OhifVisorController.php`
9. `app/Http/Controllers/Importar/ImportarDicom.php`
10. `app/Http/Controllers/Auth/LoginController.php`
11. `app/Http/Controllers/Dashboard/DashBoardController.php`

### Deleted Controllers (1 file):
1. `app/Http/Controllers/ConfigRecomendacionProductoController.php`

### Directories to Create:
1. `app/Services/Agenda/`
2. `app/Services/Consentimientos/`
3. `app/Services/Reportes/`
4. `app/Services/Dcm4chee/`
5. `app/Services/OHIF/`
6. `app/Services/Importar/`
7. `app/Services/Auth/`
8. `app/Services/Dashboard/`
9. `app/Http/Requests/Agenda/`
10. `app/Http/Requests/Dcm4chee/`
11. `app/Http/Requests/Consentimientos/`

## Execution Order
1. Create all directories
2. Wave 1 (3 simple Agenda services + requests) in parallel
3. Wave 2 (4 medium controllers) in parallel
4. Wave 3 (4 complex controllers) in parallel
5. Delete root ConfigRecomendacionProductoController
6. Run `php -l` on all created/modified files
7. Report final line counts

## Verification
- `php -l` on every new/modified PHP file
- Ensure all middleware preserved in controllers
- Ensure same URLs, methods, response formats

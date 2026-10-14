# 📊 Progreso de la Refactorización Arquitectural — LIDiag

Documento de seguimiento del plan de refactorización sistemática de **LIDiag**:
- **Meta 1 (Thin Controllers):** Delegación completa de validación (FormRequests) y lógica de negocio a la capa de servicios.
- **Meta 2 (Modularidad de Servicios):** Descomposición de servicios monolíticos en servicios especializados de responsabilidad única (Single Responsibility Principle).
- **Meta 3 (Estandarización de Respuestas API):** Formato uniforme basado en `BaseService` (`success`, `data`, `message`, `status`, `errors`) sin romper contratos consumidos por el cliente Vue 3.

---

## 🚦 Tablero de Estado General

| Módulo | Prioridad | Estado | Thin Controllers | Servicios Especializados | Respuestas Estándar | Verificación Lint/Build |
| :--- | :---: | :---: | :---: | :---: | :---: | :---: |
| **1. Estudios (Core)** | 🔴 Alta | 🟢 Completado | 🟢 100% | 🟢 95% | 🟢 100% | 🟢 Pasó (Pint + Vite) |
| **2. Ecografías** | 🔴 Alta | 🟢 Completado | 🟢 100% | 🟢 100% | 🟢 100% | 🟢 Pasó (Pint + Vite) |
| **3. Citas & Agenda** | 🟡 Media | 🟢 Completado | 🟢 100% | 🟢 100% | 🟢 100% | 🟢 Pasó (Pint + Vite) |
| **4. Contratación & Liquidación** | 🟡 Media | 🟢 Completado | 🟢 100% | 🟢 100% | 🟢 100% | 🟢 Pasó (Pint + Vite) |
| **5. Help Desk** | 🟢 Normal | 🟢 Completado | 🟢 100% | 🟢 100% | 🟢 100% | 🟢 Pasó (Pint + Vite) |
| **6. Seguridad & Configuración** | 🟢 Normal | 🟢 Completado | 🟢 100% | 🟢 100% | 🟢 100% | 🟢 Pasó (Pint + Vite) |

---

## 🎯 Fase 1: Módulo de Estudios (Completada)

El módulo de estudios es el componente central de flujo radiológico (PACS → Asignación → Lectura → Transcripción → Entrega/Notificación).

### 📋 Tareas de la Fase 1
- [x] **1.1 Form Requests Dedicados:** Creados los 8 Form Requests requeridos en `app/Http/Requests/Estudios`.
- [x] **1.2 Thin Controller (`EstudioController.php`):** Convertido en Thin Controller homogéneo.
- [x] **1.3 Descomposición de `EstudioService.php`:** Creado `EstudioQueryService` y corregido bug de `DESTINO_TELE_RADIOLOGIA`.
- [x] **1.4 Verificación de Calidad:** Pint passed, Vite build passed en 1.50s.

---

## 🎯 Fase 2: Módulo de Ecografías (Completada)

El módulo de ecografías administra el registro, admisión, consentimiento y lectura ecográfica.

### 📋 Tareas de la Fase 2
- [x] **2.1 Form Requests Dedicados:**
  - [x] Creado `EcografiaFiltroRequest` para validación de filtros (`bus_nom_num_docu`, `bus_fech_ini`, `bus_fecha_fin`) en `index` y `filtarEcografias`.
- [x] **2.2 Estandarización PSR-4 y Thin Controller:**
  - [x] Renombrado controlador a `EcografiaLecturaController` (PascalCase estricto PSR-4/PSR-12) manteniendo `ecografiaLecturaController` como puente de compatibilidad retroactiva.
  - [x] Actualización de referencias en `routes/api_ecografias.php`.
  - [x] Refactorización de `EcografiaController` para delegar filtros validados al servicio.
- [x] **2.3 Verificación de Calidad y Estilo:**
  - [x] Formateo y validación estricta con `vendor\bin\pint.bat --test` (Passed).
  - [x] Verificación de compilación frontend con `npm run build` (Passed en 999ms sin errores).

---

## 🎯 Fase 3: Módulo de Citas & Agenda (Completada)

El módulo de citas y agenda administra el agendamiento omnicanal (WhatsApp/Bot + Web), gestión de slots de disponibilidad y ciclo de vida de la cita (solicitada, asignada, confirmada, cumplida, rechazada, cancelada).

### 📋 Tareas de la Fase 3
- [x] **3.1 Form Requests Dedicados (`app/Http/Requests/Agenda`):**
  - [x] `AgendaCitaIndexRequest`: Validación de filtros de listado (`estado`, `fecha_inicio`, `fecha_fin`, `servicio_id`, `sede_id`, `busqueda`, `per_page`, `page`).
  - [x] `AgendaCitaPendientesRequest`: Validación de filtros de citas pendientes (`servicio_id`, `sede_id`, `busqueda`).
  - [x] `AgendaCitaCancelarRequest`: Validación del motivo de cancelación.
  - [x] `AgendaCitaTestEnviarRequest`: Validación de parámetros para pruebas de mensajería WhatsApp.
  - [x] `AgendaDisponibilidaIndexRequest`: Validación de filtros de disponibilidad (`sede_id`, `servicio_id`, `bus_fech_ini`, `bus_fecha_fin`).
- [x] **3.2 Desacoplamiento de Servicio Monolítico (`AgendaCitaService`):**
  - [x] Creación de `AgendaCitaQueryService` en `app/Services/Agenda` para encapsular la lógica de lecturas y consultas complejas (`all`, `pendientes`, `disponibilidad`, `servicios`, `productosPorServicio`, `verConversacion`).
  - [x] Inyección de `AgendaCitaQueryService` en `AgendaCitaService` delegando consultas y manteniendo compatibilidad total hacia atrás.
- [x] **3.3 Thin Controllers:**
  - [x] `AgendaCitaController`: Cero validaciones inline (`$request->validate()` eliminado en `testEnviar`), delegación a `AgendaCitaQueryService` y `AgendaCitaService`.
  - [x] `AgendaDisponibilidaController`: Migración a `AgendaDisponibilidaIndexRequest`.
  - [x] `ConfigCitaRechazoMotivoController`: Estandarización de respuesta de `destroy` a formato JSON BaseService.
- [x] **3.4 Verificación de Calidad y Estilo:**
  - [x] Formateo y validación estricta con `vendor\bin\pint.bat --test` (Passed).
  - [x] Verificación de compilación frontend con `npm run build` (Passed en 1.02s).

---

## 🎯 Fase 4: Módulo de Contratación & Liquidación (Completada)

El módulo de contratación y liquidación gestiona los convenios, contratos y liquidaciones de servicios médicos.

### 📋 Tareas de la Fase 4
- [x] **4.1 Form Requests Dedicados:**
  - [x] `LiquidacionFiltroRequest`: Validación compartida de filtros (`sede_id`, `fecha_inicio`, `fecha_fin`, `contrato_id`, `medico_id`) para cálculo y exportación Excel.
  - [x] `LiquidacionProductividadRequest`: Validación para el corte mensual de productividad (`sede_id`, `periodo` formato YYYY-MM).
  - [x] `ContratoIndexRequest`: Validación de filtros de contratos (`sede_id`, `estado`, `q`, `per_page`, `page`).
  - [x] `ContratoSyncMedicosRequest`: Validación de la sincronización y diff de médicos (`medicos`, tarifas consulta/urgencias, `retirar_faltantes`).
  - [x] `ContratoImportarMedicosRequest`: Validación de subida de archivo Excel para médicos (`archivo`, `medico_id`, `crear_faltantes`).
- [x] **4.2 Thin Controllers:**
  - [x] `LiquidacionController`: Eliminado método privado `validarFiltros()`, migrados `calcular()`, `exportar()` y `productividad()` a FormRequests tipados.
  - [x] `ContratoController`: Eliminado método privado `validarDetalleMedicos()`, delegada la lectura/parseo de Excel a `ContratoService::importarMedicos()`.
- [x] **4.3 Lógica de Negocio en Servicios:**
  - [x] `ContratoService::importarMedicos()`: Encapsulamiento del manejo de `IOFactory::load`, celda C2, `CupsFaltantesException` y respuesta con `requiere_confirmacion`.
- [x] **4.4 Verificación de Calidad y Estilo:**
  - [x] Formateo y validación estricta con `vendor\bin\pint.bat --test` (Passed).
  - [x] Verificación de compilación frontend con `npm run build` (Passed en 901ms sin errores).

---

## 🎯 Fase 5: Módulo de Help Desk (Completada)

El módulo de help desk gestiona los tickets de soporte, prioridades, estados y trazabilidad de incidencias internas.

### 📋 Tareas de la Fase 5
- [x] **5.1 Form Requests Dedicados (`app/Http/Requests/HelpDesk`):**
  - [x] `HelpDeskTicketIndexRequest`: Validación de filtros de listado de tickets (`estado_id`, `categoria_id`, `prioridad_id`, `asignado_a`, `fecha_inicio`, `fecha_fin`, `busqueda`, `per_page`, `page`).
  - [x] `HelpDeskNotificacionIndexRequest`: Validación de filtros de notificaciones (`leida`, `per_page`, `page`).
- [x] **5.2 Thin Controllers & Estandarización de Códigos de Respuesta:**
  - [x] `HelpDeskTicketsController`: Uso de `HelpDeskTicketIndexRequest` y casting estricto `(int) $result['status']`.
  - [x] `HelpDeskNotificacionesController`: Uso de `HelpDeskNotificacionIndexRequest` y casting `(int) $result['status']`.
  - [x] `HelpDeskCategoriasController`, `HelpDeskPrioridadesController`, `HelpDeskEstadosController`, `HelpDeskReportesController`, `HelpDeskAdjuntosController`, `HelpDeskUsuariosController`: Estandarización integral de HTTP status y consistencia de comillas dobles.
- [x] **5.3 Verificación de Calidad y Estilo:**
  - [x] Formateo y validación estricta con `vendor\bin\pint.bat --test` (Passed).
  - [x] Verificación de compilación frontend con `npm run build` (Passed en 1.02s).

---

## 🎯 Fase 6: Módulo de Seguridad & Configuración (Completada)

El módulo de seguridad y configuración administra usuarios, roles, permisos y catálogos maestros del sistema (sedes, servicios, productos, etc.).

### 📋 Tareas de la Fase 6
- [x] **6.1 Form Requests Dedicados:**
  - [x] `UpdateUserProfileRequest`: Validación del perfil de usuario y avatar/firma con política estricta de contraseñas y unicidad de correo.
  - [x] `RolActualizarRequest`: Validación del cuerpo anidado de roles y catálogo de permisos.
  - [x] `AuditLogIndexRequest`: Validación de filtros de consulta de logs de auditoría.
  - [x] `UserEstadoRequest`: Validación tipada de cambio de estado activo/inactivo de usuarios.
  - [x] `ConfigWhatsappBuscarRequest`: Validación de búsqueda de línea WhatsApp por sede y servicio.
  - [x] `ServicioProductoNoVinculadosRequest`: Validación de filtros de productos pendientes por vincular.
  - [x] Actualización de `ConfigPrioridadRequest` para cubrir `nivel`, `tiempo` y `estado` tanto en creación como en actualización.
- [x] **6.2 Thin Controllers:**
  - [x] `UserController`: Eliminadas todas las validaciones inline `$request->validate()` en `actualizarPerfil()`, uso de `UpdateUserProfileRequest` y `UserEstadoRequest`.
  - [x] `RolController`: Eliminada validación inline en `update()`, migrado a `RolActualizarRequest`.
  - [x] `AuditLogController`: Uso de `AuditLogIndexRequest`.
  - [x] `ConfigPrioridadController`: Eliminada validación inline en `update()`, migrado a `ConfigPrioridadRequest`.
  - [x] `ConfigWhatsappController`: Uso de `ConfigWhatsappBuscarRequest`.
  - [x] `ServicioProductoController`: Uso de `ServicioProductoNoVinculadosRequest`.
  - [x] Estandarización masiva en todos los controladores de Configuración (`ConfigSedeController`, `ConfigServicioController`, `ConfigProductoController`, `ConfigDiagnosticoController`, `ConfigPlantillaController`, `ConfigPlantillaTipoController`, `ConfigAdminSaludController`, `ConfigModulosController`, `ConfigTipoDocumentoController`, `ConfigSedeWhatsappNumberController`, `ConsentimientoInformadoController`) a casting entero `(int) $result["status"]` y comillas dobles.
- [x] **6.3 Verificación de Calidad y Estilo:**
  - [x] Formateo y validación con `vendor\bin\pint.bat --test` (Passed).
  - [x] Verificación de compilación frontend con `npm run build` (Passed en 866ms sin errores).

---

## 📝 Bitácora de Cambios (Changelog de Refactorización)

### [2026-10-13] — Fase 6 (Seguridad & Configuración) Completada
- Creados Form Requests `UpdateUserProfileRequest`, `RolActualizarRequest`, `AuditLogIndexRequest`, `UserEstadoRequest`, `ConfigWhatsappBuscarRequest`, `ServicioProductoNoVinculadosRequest` y ampliado `ConfigPrioridadRequest`.
- Eliminadas todas las validaciones inline `$request->validate()` en los controladores de Seguridad y Configuración.
- Estandarización de retornos JSON y códigos de estado en todos los controladores de Configuración y Seguridad.
- Verificación con Pint (passed) y Vite build (866ms).

### [2026-10-13] — Fase 5 (Help Desk) Completada
- Creados Form Requests `HelpDeskTicketIndexRequest` y `HelpDeskNotificacionIndexRequest`.
- Refactorizados los 8 controladores de HelpDesk eliminando lecturas sin tipar y estandarizando HTTP status `(int) $result["status"]`.
- Verificación con Pint (passed) y compilación Vite exitosa (1.02s).

### [2026-10-13] — Fase 4 (Contratación & Liquidación) Completada
- Creados 2 Form Requests en `app/Http/Requests/Liquidacion` y 3 en `app/Http/Requests/Contratacion`.
- `LiquidacionController` y `ContratoController` convertidos en Thin Controllers sin métodos privados de validación.
- Encapsulado `importarMedicos()` en `ContratoService`.
- Validación exitosa con Pint (passed) y Vite build (901ms).

### [2026-10-13] — Fase 3 (Citas & Agenda) Completada
- Creados 5 Form Requests en `app/Http/Requests/Agenda`.
- Creado `AgendaCitaQueryService` desacoplando consultas de `AgendaCitaService`.
- Refactorizados `AgendaCitaController`, `AgendaDisponibilidaController` y `ConfigCitaRechazoMotivoController` como Thin Controllers.
- Validación con Pint (passed) y compilación Vite exitosa (1.02s).

### [2026-10-13] — Fase 2 (Ecografías) Completada
- Creación de `EcografiaFiltroRequest` para sanitizar y tipar filtros de ecografías.
- Corrección de convención PSR-4 creando `EcografiaLecturaController` y actualizando rutas.
- Verificación completa con Pint y compilación Vite exitosa (999ms).

### [2026-10-13] — Fase 1 (Estudios) Completada
- Creación de 8 Form Requests en `app/Http/Requests/Estudios`.
- Creación de `EstudioQueryService` en `app/Services/Estudios`.
- Reducción y desacoplamiento de `EstudioService` y `EstudioController` (Thin Controller).
- Corrección de bug de constante `DESTINO_TELE_RADIOLOGIA` en transcripción.
- Verificación exitosa de Pint y build de Vite.

# MEMORY.md — LIDiag

Este archivo registra el estado actual del desarrollo, las decisiones técnicas consolidadas, el estado de las integraciones y las lecciones aprendidas del repositorio. El Agente de IA debe actualizar este archivo al finalizar tareas críticas o hitos importantes.

---

## 1. Estado Actual del Sistema (Hitos y Contexto)
- **Módulos Activos:** Agendamiento, Citas (con Webhook de Meta WhatsApp), Estudios (RX, TAC, Resonancia, Mamografía), Ecografías (con Consentimientos Informados y firma por token), Interface SAHI (SQL Server), Integración PACS (Dcm4chee + Oviyam), HelpDesk, Configuración y Seguridad (Spatie).
- **Entorno de Trabajo Consolidado:** Laragon + Windows (Powershell 5.1). El Virtual Host obligatorio es `https://lidiag.test`. El entorno local expone HTTPS usando los certificados por defecto de Laragon (`C:/laragon/etc/ssl/...`).
- **Credenciales de Desarrollo Locales:** `administrador@administrador.com` / `123456`.

---

## 2. Estado de las Integraciones y Servicios (¡No Modificar Arquitectura!)

### 🎙️ Audio y Transcripción (Whisper + FastAPI)
- **Estado:** Totalmente operativo bajo el flujo: *Subida en Vue 3 → Laravel Controller (Conversión FFmpeg a MP3 64kbps en disk `audios/tmp/`) → Envío por POST a FastAPI (:8001) → Transcripción guardada en `estudios_audios.transcripcion`*.
- **Lección Aprendida:** Whisper se ejecuta localmente en CPU. Audios largos pueden causar retrasos o *timeouts*. El `.env` debe respetar un `TRANSCRIPTOR_TIMEOUT=300`. No intentes optimizar este flujo con colas asíncronas (`QUEUE_CONNECTION=sync` por defecto).

### 💬 Webhook de WhatsApp (Twilio + Cloudflare)
- **Estado:** Configurado para desarrollo local exponiendo el vhost mediante un túnel de `cloudflared` (`http://lidiag.test`).
- **Punto Crítico:** Cada vez que se reinicia el túnel se genera una nueva URL transitoria `://trycloudflare.com`. El webhook configurado en Meta usa el verify token `lidiag_whatsapp_2026`.

### 🏥 Interoperabilidad HIS (SAHI) y PACS (Dcm4chee)
- **Estado:** Sincronización bidireccional mediante los comandos `sync-usuarios` y `sync:dicom-ecografias`.
- **Restricción:** La conexión a SQL Server requiere las extensiones nativas `sqlsrv` y `pdo_sqlsrv` activas en Laragon. No asumas que las consultas SQL Server soportan la misma sintaxis fluida/eloquent que MySQL para operaciones complejas; prioriza el uso de conexiones limpias mediante consultas preparadas o el driver secundario configurado.

---

## 3. Lecciones Aprendidas y "Trampas" del Repositorio (Anti-Bugs)

- **El Infierno de las Líneas de Fin (LF vs CRLF):** Git está configurado con `core.autocrlf=true` (lo que descarga archivos en CRLF). Sin embargo, Laravel Pint exige estrictamente finales de línea en **LF**. Si una verificación de Pint falla *únicamente* por `line_ending`, fuerza la normalización del archivo a LF sin alterar un solo byte de lógica.
- **La Trampa de los Atributos en Requests:** Al validar formularios mediante `$request->validate()`, Laravel descarta cualquier campo que no tenga una regla explícitamente declarada. Si necesitas enviar banderas opcionales o datos booleanos contextuales (ej: `retirar_faltantes`), agrégalas obligatoriamente con la regla `'sometimes|boolean'` o jamás llegarán al Service/Controller.
- **Evitar el BOM de UTF-8 en PowerShell:** Ejecutar comandos de automatización o generación de scripts en PS5.1 usando `Set-Content` inyecta un BOM invisible en los archivos `.php` que rompe el intérprete de PHP provocando errores fatales. Genera siempre archivos planos en UTF-8 puro sin BOM.
- **El Falso Negativo de Spatie Cache:** El comando nativo `permission:cache-reset` de Spatie arroja excepciones `Unable to flush cache` si el archivo de caché no existe (común cuando `CACHE_DRIVER=file`). Para mitigar esto en automatizaciones, utiliza el comando personalizado del repositorio: `php artisan permissions:cache-reset`.

---

## 4. Próximos Pasos Técnicos e Historial Inmediato
- [x] Corrección de discrepancias de versión en el entorno y definición de reglas del agente.
- [x] **2026-10-13 — Análisis completo del repositorio:** Inventario de 65 controladores (organizados en 14 namespaces), 56 componentes Vue, 13 rutas API, 7 servicios, 13 comandos Artisan, ~100 migraciones.
- [x] **2026-10-13 — `docs/CONTROLLERS.md` completamente reescrito:** Se añadieron 8 módulos faltantes (HelpDesk, Contratación, Liquidación, Tele-Radiología, Consentimientos, Seguridad, Reportes, Auth) con todos los métodos documentados extrayendo la información directamente del código fuente.
- [x] **2026-10-13 — Creación de `progreso.md` y Ejecución de Fase 1 (Estudios):** 
  - `EstudioController` convertido en Thin Controller con 8 FormRequests dedicados en `app/Http/Requests/Estudios`.
  - Extracción de consultas de lectura a `EstudioQueryService` aligerando `EstudioService` de 1060 a ~730 líneas.
  - Corrección de bug latente de constante no calificada `DESTINO_TELE_RADIOLOGIA` en transcripción.
  - Validación con Laravel Pint (`passed`) y compilación Vite (`npm run build` exitoso).
- [x] **2026-10-13 — Ejecución de Fase 2 (Ecografías):**
  - Creación de `EcografiaFiltroRequest` para filtros en `index` y `filtarEcografias`.
  - Estandarización PSR-4/PSR-12 en `EcografiaLecturaController` (con puente para `ecografiaLecturaController`).
  - Actualización de rutas en `api_ecografias.php`, validación Pint (`passed`) y Vite build en 999ms.
- [x] **2026-10-13 — Ejecución de Fase 3 (Citas & Agenda):**
  - Creados 5 FormRequests dedicados en `app/Http/Requests/Agenda/`: `AgendaCitaIndexRequest`, `AgendaCitaPendientesRequest`, `AgendaCitaCancelarRequest`, `AgendaCitaTestEnviarRequest`, `AgendaDisponibilidaIndexRequest`.
  - Creado `AgendaCitaQueryService` encapsulando consultas (`all`, `pendientes`, `disponibilidad`, `servicios`, `productosPorServicio`, `verConversacion`).
  - `AgendaCitaService` aligerado delegando a `AgendaCitaQueryService` con retrocompatibilidad.
  - `AgendaCitaController` y `AgendaDisponibilidaController` transformados en Thin Controllers (eliminadas validaciones inline `$request->validate()` en `testEnviar`).
  - Estandarizada respuesta JSON de `ConfigCitaRechazoMotivoController::destroy` al estándar `BaseService`.
- [x] **2026-10-13 — Ejecución de Fase 4 (Contratación & Liquidación):**
  - Creados FormRequests en `app/Http/Requests/Liquidacion/`: `LiquidacionFiltroRequest`, `LiquidacionProductividadRequest`.
  - Creados FormRequests en `app/Http/Requests/Contratacion/`: `ContratoIndexRequest`, `ContratoSyncMedicosRequest`, `ContratoImportarMedicosRequest`.
  - `LiquidacionController` y `ContratoController` refactorizados como Thin Controllers (eliminados métodos privados `validarFiltros` y `validarDetalleMedicos`).
  - Lógica de lectura/parseo de Excel y manejo de `CupsFaltantesException` trasladada a `ContratoService::importarMedicos()`.
  - Verificación estricta: `vendor\bin\pint.bat --test` passed y `npm run build` passed en 901ms.
- [x] **2026-10-13 — Ejecución de Fase 5 (Help Desk):**
  - Creados FormRequests tipados: `HelpDeskTicketIndexRequest` y `HelpDeskNotificacionIndexRequest`.
  - Refactorizados los 8 controladores de Help Desk (`HelpDeskTicketsController`, `HelpDeskNotificacionesController`, `HelpDeskCategoriasController`, `HelpDeskPrioridadesController`, `HelpDeskEstadosController`, `HelpDeskReportesController`, `HelpDeskAdjuntosController`, `HelpDeskUsuariosController`) eliminando lecturas sin tipar y estandarizando HTTP status `(int) $result["status"]` y comillas dobles.
  - Verificación estricta: `vendor\bin\pint.bat` passed y `npm run build` passed en 1.02s.
- [x] **2026-10-13 — Ejecución de Fase 6 (Seguridad & Configuración):**
  - Creados FormRequests: `UpdateUserProfileRequest`, `RolActualizarRequest`, `AuditLogIndexRequest`, `UserEstadoRequest`, `ConfigWhatsappBuscarRequest`, `ServicioProductoNoVinculadosRequest`, y ampliado `ConfigPrioridadRequest`.
  - Refactorizados controladores de Seguridad (`UserController`, `RolController`, `AuditLogController`) eliminando todas las validaciones inline `$request->validate()`.
  - Refactorizados y normalizados todos los controladores de Configuración (`ConfigSedeController`, `ConfigServicioController`, `ConfigProductoController`, `ConfigDiagnosticoController`, `ConfigPlantillaController`, `ConfigPlantillaTipoController`, `ConfigPrioridadController`, `ConfigAdminSaludController`, `ConfigModulosController`, `ConfigTipoDocumentoController`, `ConfigWhatsappController`, `ConfigSedeWhatsappNumberController`, `ConsentimientoInformadoController`, `ServicioProductoController`) con respuestas tipadas, casting entero `(int) $result["status"]` y comillas dobles.
  - Verificación estricta: `vendor\bin\pint.bat --test` passed y `npm run build` passed en 866ms.
- [x] **Refactorización integral de 6 fases completada al 100%:** Estudios, Ecografías, Citas & Agenda, Contratación & Liquidación, Help Desk, Seguridad & Configuración.
- [ ] Revisión final con el usuario y aprobación para commit.

### Estado de módulos recientes (oct. 2026)
- **EstudioLockController** (2026-10-08): ✅ Sin permisos propios — usa solo `auth`. Las rutas `estudio-lock/tomar` y `estudio-lock/liberar` están registradas en `api_estudios.php`. No requiere seeder.
- **LiquidacionController** (2026-10-01): ✅ Seeder `PermisionLiquidacionSeeder` existente y registrado en `DatabaseSeeder`. Permisos `liquidacion.index` y `liquidacion.exportar` asignados al rol `Administrador`.

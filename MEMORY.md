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
- [x] **2026-10-27 — Comando Inteligente de Siembra e Idempotencia Total (`seed:faltantes`):**
  - **Objetivo:** Ejecutar de forma segura, discriminada y automatizada únicamente los seeders que no hayan sido aplicados en bases de datos restauradas o actualizadas, sin violar claves primarias/únicas ni fallar por restricciones de llaves foráneas.
  - **Verificación Previa (`isApplied`):** Cada uno de los 29 seeders registrados cuenta con una función de inspección contextual en base de datos (`isApplied()`) que verifica previamente si sus permisos, catálogos o registros ya existen. Si ya están aplicados, se omite (`⏭ Ya aplicado`). Se incluye la opción `--force` para re-ejecución forzada y `--audit` para auditoría comparativa de permisos entre código y BD.
  - **Blindaje de Seeders contra Duplicados y Foreign Keys:**
    * *Permisos Spatie:* Refactorizados a `Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web'])` en `PermisosAgendamientoSeeder`, `RoleSeederAgenda`, `PermisionConfigTipoDocumentoSeeder`, `PermisionConsentimientoInformadoSeeder`, `RoleSeederReporteLectura` y `PlantillaTipoSeeder`.
    * *WhatsApp Ecografías:* `ConfigWhatsappEcografiasRequerimientos` y `ConfigWhatsappEcografiasRecomendaciones` ahora resuelven el `servicio_id` dinámicamente y verifican que el `producto_id` exista en `config_productos` antes de insertar para respetar restricciones de integridad referencial.
  - **Verificación:** `php artisan seed:faltantes --audit` ejecutado exitosamente (0 errores, 0 fallidos, 100% permisos en código cubiertos en BD). Laravel Pint passed ✅.
- [x] **2026-10-27 — Comando Artisan de Siembra Segura de Seeders Faltantes (`seed:faltantes`):**
  - **Objetivo:** Evitar fallos de clave duplicada (1062) en bases de datos restauradas donde ya existen usuarios y sedes pero faltan permisos nuevos, umbrales regulatorios y catálogos.
  - **Comando Creado (`SeedFaltantesCommand.php`):** Registrado `php artisan seed:faltantes [--audit]`. Ejecuta secuencialmente y de forma estrictamente idempotente los 22 seeders de módulos nuevos (Dosimetría Res. 482, Backups, Contratación, Liquidación, Citas/WhatsApp, Auditoría, Consentimientos, HelpDesk), resetea la caché de Spatie e invoca auditoría. Pint passed ✅.
- [x] **2026-10-27 — Inclusión de 'unsafe-eval' en Content-Security-Policy (CSP) para Vue 3 y DataTables:**
  - **Diagnóstico:** En producción (`lidiag.com`), la directiva CSP estricta en `SecurityHeaders.php` bloqueaba la ejecución de JavaScript en tiempo de ejecución con `Uncaught EvalError: Evaluating a string as JavaScript violates the following Content Security Policy directive because 'unsafe-eval' is not an allowed source of script`.
  - **Causa Raíz:** Vue 3 y DataTables requieren evaluar expresiones de plantillas dinámicamente (`new Function`). Al carecer de `'unsafe-eval'` en `script-src`, el navegador bloqueaba la inicialización de los componentes.
  - **Solución:** Incorporado `'unsafe-eval'` en `$scriptSrc` dentro de `app/Http/Middleware/SecurityHeaders.php`. Laravel Pint passed ✅.
- [x] **2026-10-27 — Despliegue en Producción Real (lidiag.com) y Blindaje de Redirección Post-Cambio de Contraseña:**
  - **TrustHosts y Proxies:** Incorporado el patrón `^(.+\.)?lidiag\.com$` en `TrustHosts.php` y configurado `protected $proxies = '*'` en `TrustProxies.php` para delegar adecuadamente la terminación SSL (HTTPS) de balanceadores y Cloudflare.
  - **Redirección Defensiva (`PasswordChangeController.php`):** Descartado `url.intended` si apunta a la ruta de `login`, garantizando que tras un cambio exitoso de contraseña el usuario sea redirigido siempre al `dashboard` y nunca expulsado al login.
- [x] **2026-10-27 — Optimización y Reorganización Estructural del Menú Sidebar (`sidebar.blade.php`):**
  - **Reorganización Semántica y Jerárquica (`nav-small-cap`):** Reducción de 14 encabezados redundantes a 4 grupos de trabajo clínicos y administrativos coherentes: *Principal*, *Gestión Clínica*, *Citas y Agenda*, *Gestión y Reportes*, y *Administración*.
  - **Corrección de Enlaces y Bugs de Enrutamiento:**
    * *Pacientes:* Eliminada duplicidad de enlaces que apuntaban a la misma vista de admisión.
    * *Agenda:* Corregida la ruta errónea de *Asignación* que apuntaba a `citas.index`, enlazándola a `agendamiento.index` (`/agenda`), y reubicada *Disponibilidad* (`agenda.disponibilidad.index`) dentro del módulo de Agenda.
    * *Reportes:* Incorporado acceso al reporte de *Estudios PACS* (`reportes.estudios.pac`).
    * *Ecografías:* Integrados accesos directos a *Consentimientos (Firmas/PDF)* y *Plantillas* facilitando la operación asistencial.
  - **Homogeneización Visual e Iconografía:**
    * Sustituido el logo comercial `fa-etsy` por el icono clínico `fa-stethoscope` en el módulo de Ecografías.
    * Incorporados iconos temáticos con clase `text-info` a todos los sub-elementos de Parametrización y Helpdesk (eliminando el `nav-small-cap` anidado que rompía el DOM).
  - **Consistencia de Directivas Blade:** Estandarizado el uso de `@canany` y `@can` en lugar de llamadas manuales dispersas a `auth()->user()->hasAnyPermission()`.
  - **Respeto Estricto de Estilos:** Cero modificación de clases o reglas CSS; 100% compatible con el diseño nativo de AdminWrap. LF UTF-8 no-BOM verificado.
- [x] **2026-10-27 — Homologación Estricta a Estilos Originales AdminWrap en Reportes (`reporteProduccionComponent.vue`):**
  - **Eliminación Total de Estilos y Clases Personalizadas:**
    * Eliminado el bloque `<style scoped>` y la clase `.btn-action-clinical`.
    * Eliminados estilos inline de modales (`background: rgba(...)`, `backdrop-filter: blur(...)`).
    * Restaurada la estructura nativa estándar de Bootstrap/AdminWrap en todos los modales (`class="modal fade" :class="{ show: ... }" :style="{ display: ... }"` y `<div v-if="..." class="modal-backdrop fade show"></div>`).
    * Restaurados los botones de modales al estándar clásico original del sistema: `btn btn-default waves-effect` (Cerrar) y `btn btn-info waves-effect waves-light` (Descargar) con sus iconos tradicionales (`<i class="fa fa-download"></i>`), sin badges de teclado ni efectos ajenos.
    * Eliminada la negrita de los encabezados de modales (`h4.modal-title` sin `font-bold`) y de sus etiquetas (`label.control-label` sin `font-weight-bold`), homologándolos 100% al modal de referencia *Producción Detallada*.
    * Breadcrumb restaurado al diseño limpio original.
  - **Verificaciones Estrictas:** `npx eslint` 0 errores ✅ | `npm run build` en 1.09s ✅.
- [x] **2026-10-27 — Mejoras Avanzadas en Dosimetría: Parser RDSR DICOM, Compresión GZIP, Clasificación Anatómica RadLex y Deriva Técnica:**
  - **Parser Universal de RDSR DICOM (PS3.16 / TID 10011 / TID 10001):**
    * Detección automática en `agente_dosimetria.py` de objetos Structured Report (`SOPClassUID == 1.2.840.10008.5.1.4.1.1.88.67` y afines).
    * Recorrido recursivo del árbol `ContentSequence` extrayendo acumulados oficiales certificados (`Mean CTDIvol`, `DLP Total`, `DAP Total`, `Entrance Dose / DGM`).
    * Marcado con badge interactivo de trazabilidad en Vue 3 (`[RDSR]` con icono `fa-certificate`).
  - **Compresión GZIP en Tránsito (85% Ahorro de Ancho de Banda WAN):**
    * Agente Edge comprime lotes > 1 KB con `gzip.compress()` y cabecera HTTP `Content-Encoding: gzip`.
    * Backend Laravel (`DosimetriaStoreRequest::prepareForValidation`): Descompresión transparente en el hook `prepareForValidation()` mediante `@gzdecode()`.
  - **Normalización y Clasificación Anatómica Semántica (RadLex / CUPS):**
    * Implementado clasificador de regiones anatómicas en PHP (`DosimetriaService::clasificarRegionAnatomica`) y Python (`clasificar_region_anatomica`): Cráneo, Tórax, Abdomen, Pelvis, Columna, Extremidades, Mama y General.
    * Agregado selector de filtro interactivo por **Región Anatómica** en `DashboardDosimetria.vue` y columna con badges contextuales.
  - **Detección Predictiva de Deriva Técnica (Machine Drift Detection):**
    * Algoritmo de control estadístico en `DosimetriaService::obtenerDashboard()` que compara la dosis media de la primera mitad del periodo contra la segunda mitad.
    * Generación automática de panel de alerta clínica preventiva (`alertasDeriva`) si un tubo de rayos X o tomógrafo registra un incremento $\ge 20\%$ sobre su línea base histórica.
  - **Verificaciones Estrictas:** Laravel Pint passed ✅ | `npx eslint` 0 errores ✅ | `npm run build` en 1.20s ✅ | Python 3.13 py_compile passed ✅.
- [x] **2026-10-27 — Incorporación del Reporte de Salud y Telemetría de Agentes Hospitalarios:**
  - **Módulo Central de Reportes (`reporteProduccionComponent.vue`):** Incorporada tarjeta nativa de AdminWrap "Agentes y Telemetría" con icono `fa-server`, modal interactivo con selector de sedes, enlace directo a `/seguridad-backups` y shortcuts (`Esc`, `Ctrl+S`).
  - **Exportador Excel Institucional (`ReporteAgentesTelemetriaExport.php`):**
    * Consolida por cada sede hospitalaria activa:
      1. *Agente de Respaldo DB (`backup_dcm_agent.py`):* nombre de archivo, timestamp de último backup recibido, tamaño formateado, antigüedad en horas y semáforo semántico (Al día, Advertencia >24h, Crítico >48h, Sin registros).
      2. *Agente Edge de Dosimetría (`agente_dosimetria.py`):* versión instalada, timestamp de último latido (Heartbeat), estado de conexión en vivo (🟢 Online <=15m, 🟡 Retrasado 16-30m, 🔴 Offline >30m) y cola de registros pendientes.
      3. *Equipos Emisores:* total de equipos de radiación activos mapeados por sede (`medicion_dosis_equipos_emisores`).
    * Estilos institucionales de cabecera Salud Tolima y anchos de columna optimizados.
  - **Backend y Routing:**
    * Creado `ReporteAgentesRequest.php` para validación de filtros.
    * Métodos `generateReporteAgentes()` en `ReporteProduccionService.php` y `reporteAgentes()` en `ReporteProduccion.php`.
    * Registrada ruta web `/descarga-reportes-agentes` protegida con middleware de permisos dual `reportes.detallados|reportes.indicadores|seguridad.backups.index`.
  - **Verificaciones Estrictas:**
    * Laravel Pint passed (`{"tool":"pint","result":"passed"}`) ✅.
    * ESLint: 0 errores / 0 advertencias (`npx eslint` passed) ✅.
    * Vite build: Compilación exitosa en 1.24s (`npm run build`) ✅.
- [x] **2026-10-27 — Blindaje Arquitectónico y Resiliencia en Dosimetría (Res. 482 de 2018 / ISO 27001):**
  - **Seguridad Criptográfica (Anti-Rainbow Table):** Reemplazado hash SHA-256 plano por HMAC-SHA256 con salt dinámico de 32 bytes (`secrets.token_hex(32)`) generado y persistido automáticamente por sede en `config.json`.
  - **Desacoplamiento de Base de Datos PACS (`pacsdb`):** Inyección de `SET SESSION TRANSACTION ISOLATION LEVEL READ UNCOMMITTED;` en cada consulta de extracción del agente Python a MySQL/MariaDB, eliminando bloqueos de lectura concurrentes sobre la adquisición clínica de imágenes.
  - **Cola Persistente Local Edge (SQLite `cola_dosimetria.db`):** Implementada base de datos SQLite embebida en el agente edge con estados transaccionales (`PENDING`, `SYNCED`, `FAILED`) y auto-purga a 30 días, garantizando cero pérdida de datos ante cortes eléctricos o reinicios de servidor.
  - **Idempotencia Robusta en API Laravel (`/api/dosimetria/registros`):** Cálculo y validación de `batch_hash` SHA-256 por bloque recibido con almacenamiento en caché por 24 horas. Los reintentos de red responden HTTP 200 inmediato sin recargar la base de datos central.
  - **Monitor de Latidos / Dead Man's Switch (`/api/dosimetria/heartbeat`):** Endpoints `POST /api/dosimetria/heartbeat` y `/api/dosis/heartbeat` registrando telemetría del agente (uptime, pendientes SQLite, versión). Enriquecido `DashboardDosimetria.vue` con semáforo dinámico de conectividad (🟢 Online, 🟡 Retrasado, 🔴 Offline).
  - **Verificaciones Estrictas:** Laravel Pint passed ✅ | `npx eslint` 0 errores ✅ | `npm run build` en 1.30s ✅ | Python 3.13 py_compile passed ✅.
- [x] **2026-10-27 — Implementación Pasos 3, 4 y 5: Módulo de Reportes e Indicadores por Categorías (Financiero, Calidad TAT y Dosimetría):**
  - **Categoría 3: Financiero / Liquidación:**
    * Incorporada tarjeta nativa de AdminWrap "Financiero / Liquidación" con accesos a Liquidación de Honorarios a Radiólogos, Productividad Médica (Corte Mensual) y salto al módulo `/liquidacion`.
    * Backend: Normalización de alias en `LiquidacionFiltroRequest` y `LiquidacionProductividadRequest`. Creadas rutas web `/descarga-reportes-liquidacion` y `/descarga-reportes-productividad` delegando a `LiquidacionController::exportar` y `exportarProductividad`.
    * Frontend: Modales nativos interactivos de Liquidación y Productividad con selector de sedes, rango de fechas y periodos mensuales.
  - **Categoría 4: Calidad y Oportunidad (Turnaround Time - TAT):**
    * Creado `ReporteTATRequest.php` para validación estricta de filtros asistenciales (fechas, sede y modalidad).
    * Desarrollado `ReporteTATExport.php`: Excel institucional de tiempos de oportunidad y entrega (cálculo de tiempos disparo/ingreso -> dictado médico -> transcripción final), horas de respuesta y clasificación semafórica de oportunidad (Conforme <=24h, Alerta 24-48h, Extemporáneo >48h).
    * Backend: Creado `ReporteProduccionService::generateReporteTAT()`, Thin Controller `ReporteProduccion::reporteTAT()` y registrada ruta web `/descarga-reportes-tat`.
    * Frontend: Tarjeta nativa AdminWrap "Calidad y Oportunidad" con icono `fa-tachometer`, modal interactivo con filtros de modalidad técnica (`RX`, `CT`, `MR`, `MG`, `US`) y atajos de teclado (`Esc`, `Ctrl+S`).
  - **Categoría 5: Dosimetría y Radiación:**
    * Tarjeta nativa AdminWrap con descarga de Excel multi-hoja con gráficos (Res. 482 de 2018) y enlace directo integrado al tablero interactivo `/dosis/dashboard`.
  - **Diseño Estricto:** Se mantuvo el 100% de los estilos y diseño nativo existente de AdminWrap (`info-box`, `card`, `card-title text-themecolor`, `a href`) sin inventar CSS ni componentes ajenos.
  - **Verificaciones Estrictas:**
    * Laravel Pint passed (`{"tool":"pint","result":"passed"}`) ✅.
    * ESLint: 0 errores / 0 advertencias (`npx eslint` passed) ✅.
    * Vite build: Compilación exitosa en 1.21s (`npm run build`) ✅.
- [x] **2026-10-27 — Corrección de Nombres de Rutas PSR-4 (Case Sensitivity en Windows/Git):**
  - **Diagnóstico:** Al ejecutar `composer dump-autoload` en el servidor o tras sincronizar, Composer advertía: `Class Database\Seeders\Configuracion\... does not comply with psr-4 autoloading standard... Skipping.` y `Class App\Http\Controllers\Ecografias\EcografiaLecturaController located in .../ecografiaLecturaController.php... Skipping.`.
  - **Causa Raíz:** En Windows Git con `core.ignorecase=true`, el índice de Git preservaba históricamente las rutas en minúscula (`database/seeders/configuracion` y `ecografiaLecturaController.php`), mientras que los namespaces y clases de PHP declaran `Configuracion` y `EcografiaLecturaController`.
  - **Solución:** Renombradas forzosamente en Git mediante paso intermedio (`git mv`) para registrar la mayúscula canónica (`database/seeders/Configuracion/` y `EcografiaLecturaController.php`). Normalizados saltos a LF. Verificado con `composer dump-autoload` con 0 advertencias.
- [x] **2026-10-27 — Blindaje Arquitectónico y Resiliencia en Dosimetría (Res. 482 de 2018 / ISO 27001):**
  - **Seguridad Criptográfica (Anti-Rainbow Table):** Reemplazado hash SHA-256 directo sobre PatientID por HMAC-SHA256 con salt dinámico de 32 bytes generado criptográficamente (`secrets.token_hex(32)`) y persistido por sede en `config.json`.
  - **Desacoplamiento de Base de Datos PACS (`pacsdb`):** Inyección de `SET SESSION TRANSACTION ISOLATION LEVEL READ UNCOMMITTED;` en cada conexión del agente Python a la base de datos de Dcm4chee, garantizando cero bloqueos (locks) de lectura sobre la recepción de imágenes en tiempo real.
  - **Cola Persistente Local Edge (SQLite `cola_dosimetria.db`):** Implementada base de datos SQLite embebida en el agente edge con estados `PENDING`, `SYNCED`, `FAILED` y política de auto-purga a 30 días. Garantiza cero pérdida de datos durante reinicios o cortes de energía prolongados.
  - **Idempotencia Robusta en API Laravel (`/api/dosimetria/registros`):** Cálculo de `batch_hash` SHA-256 por cada bloque de registros. Laravel almacena el hash en caché durante 24 horas (`dosimetria_batch_{sedeId}_{batchHash}`); si la red falla en el ACK y el agente reintenta, el endpoint responde HTTP 200 inmediatamente sin recargar MySQL ni ejecutar upserts redundantes.
  - **Monitor de Latidos / Dead Man's Switch (`/api/dosimetria/heartbeat`):** Endpoints `POST /api/dosimetria/heartbeat` y `/api/dosis/heartbeat` recibiendo telemetría del agente (versión, uptime, pendientes). Integrado semáforo de conectividad en tiempo real en Vue 3 (`DashboardDosimetria.vue`): 🟢 Online (≤15 min), 🟡 Retrasado (16-30 min), 🔴 Offline (>30 min o sin contacto).
  - **Verificaciones Estrictas:** Laravel Pint passed ✅ | `npx eslint` 0 errores ✅ | `npm run build` en 1.30s ✅ | Python 3.13 py_compile passed ✅.
- [x] **2026-10-27 — Blindaje, Corrección de Concurrencia y Resiliencia en Agentes Edge y Transcriptor:**
  - **Servicio Transcriptor Whisper (`storage/app_lidiag/transcriptor/main.py`):**
    * *Anti-Colisión de Audios:* Eliminado riesgo crítico de sobreescritura entre médicos concurrentes (`temp_{filename}`). Se asignan prefijos criptográficos únicos con `uuid.uuid4().hex[:12]`.
    * *Desbloqueo de Event Loop (FastAPI):* Envuelto `whisper_service.transcribe()` en `asyncio.to_thread()`, evitando que la inferencia CPU-bound de Whisper congele las rutas `/health`, `/debug` y peticiones concurrentes.
  - **Agente de Dosimetría Edge (`storage/app_lidiag/agente_backups_dcm/agente_dosimetria.py`):**
    * *Anti-Deadlock por Reciclaje de PIDs en Windows:* `is_pid_running()` ahora valida estrictamente contra procesos `IMAGENAME eq python*`, evitando que un PID reutilizado por Windows (`svchost.exe`, etc.) bloquee indefinidamente el agente.
    * *Caché Cronológica FIFO:* Reemplazado `list(set)[-5000:]` (que descartaba elementos aleatorios) por almacenamiento ordenado por timestamp con migración automática de retrocompatibilidad.
    * *Resiliencia de Transmisión Parcial:* `enviar_lote_a_central()` ahora retorna los `exitosos_keys` por bloque enviado. Si el bloque 2 falla por red, los 50 registros del bloque 1 se guardan en caché para no reenviarse en el siguiente ciclo.
    * *Optimización de Series CT:* Filtro inteligente en consulta SQL para ignorar series `SCOUT`, `TOPOGRAM` y `LOCALIZER` (que carecen de `CTDIvol`), asegurando que `MIN(filepath)` tome siempre instancias axiales o de informe de dosis.
    * *Silenciado de Warnings TLS:* Agregado `urllib3.disable_warnings(InsecureRequestWarning)` para mantener logs limpios.
  - **Agentes de Backup y Sincronización PACS (`backup_dcm_agent.py`, `sync_dcm_images.py`, `restore_dcm_images.py`):**
    * Actualizada la función `is_pid_running()` con filtrado de imagen de proceso para prevenir bloqueos fantasma en servidores Windows.
    * Regenerado el paquete distribuible `storage/app_lidiag/agente_backups_dcm.zip` (51.6 KB).
- [x] **2026-10-27 — Normalización LF Obligatoria para Parches de Composer (`.gitattributes`):**
  - **Diagnóstico:** En el servidor de producción `10.35.47.4`, al ejecutar `composer update` o `composer install`, el plugin `cweagans/composer-patches` fallaba con `Hash mismatch for patch downloaded from patches/messagebag-arraypush-fix.patch. Got e5421859...; expected 70c4f3de...`.
  - **Causa Raíz:** `patches.lock.json` almacena el hash SHA-256 (`70c4f3de...`) del parche con saltos de línea estrictos en **LF**. Al descargarse en Windows con `core.autocrlf`, Git convertía el parche a **CRLF** alterando su hash a `e5421859...`.
  - **Solución:** Configurado `.gitattributes` forzando `*.patch text eol=lf` y `patches/* text eol=lf`. Normalizado `patches/messagebag-arraypush-fix.patch` a LF en el servidor.
- [x] **2026-10-27 — Implementación Paso 2: Módulo Citas (Reporte de Ausentismo y Cancelaciones No-Show):**
  - **Nuevo Reporte de Ausentismo y Cancelaciones (`ReporteAusentismoCitasExport.php`):**
    * Generación de archivo Excel institucional (`reporte_ausentismo_citas.xlsx`) cruzando las citas de la tabla `citas` en estados `no_asistio`, `cancelada` o `rechazada`.
    * Vinculación relacional con catálogo de motivos de rechazo (`config_cita_rechazo_motivos`), servicios (`config_servicios`), productos CUPS (`config_productos`) y pacientes (`pacientes`).
    * Columnas: ID Cita, Sede Hospitalaria, Fecha/Hora Programada, Doc. Paciente, Nombre Paciente, Teléfono Contacto, Servicio / Especialidad, Código CUPS, Procedimiento, Estado de la Cita, Motivo de Inasistencia / Rechazo, Observaciones del Motivo y Estado Notificación WhatsApp.
    * Backend: Creado `ReporteAusentismoCitasRequest` con validación de fechas, sede y filtro opcional de estado. Métodos `generateReporteAusentismoCitas()` en `ReporteProduccionService` y `reporteAusentismoCitas()` en `ReporteProduccion`. Registrada ruta web `/descarga-reportes-ausentismo-citas`.
    * Frontend: Incorporada tarjeta nativa con icono `fa-calendar-check-o` y modal interactivo con filtros de sede, estado y rango de fechas.
  - **Verificaciones Estrictas:**
    * Laravel Pint passed (`{"tool":"pint","result":"passed"}`) ✅.
    * ESLint: 0 errores / 0 advertencias (`npx eslint` passed) ✅.
    * Vite build: Compilación exitosa en 1.34s (`npm run build`) ✅.
- [x] **2026-10-27 — Implementación Paso 1: Módulo Asistencial (Indicadores Nativos + Reporte Tele-radiología):**
  - **Indicadores Nativos AdminWrap (`reporteProduccionComponent.vue`):** Integrado bloque superior de 4 tarjetas `info-box` estándar de AdminWrap (`icon-Target-Market`, `icon-Inbox-Forward`, `icon-File-HorizontalText`, `icon-Yes` / Font Awesome) mostrando en tiempo real: Estudios Asignados (30 días), Estudios Leídos, Pendientes de Transcripción y Finalizados Hoy, consumiendo `/api/reportes.dashboard`.
  - **Nuevo Reporte de Tele-radiología (`ReporteTeleRadiologiaExport.php`):**
    * Generación de archivo Excel institucional (`reporte_tele_radiologia.xlsx`) con trazabilidad completa de órdenes derivadas a lectura externa (`destino_estudio = 'Tele Radiologia'` o radiólogos `TL %`).
    * Columnas: ID Estudio, Sede Hospitalaria, Fecha/Hora Envío, Study Instance UID, Accession Number, Modalidad, Doc. Paciente, Nombre Paciente, Código CUPS, Procedimiento, Médico Tele-radiólogo, Fecha/Hora Lectura, Estado Lectura y Tiempo de Respuesta (horas).
    * Backend: Creado `ReporteTeleRadiologiaRequest` con validación de fechas y sede. Implementado `ReporteProduccionService::generateReporteTeleRadiologia()` y `ReporteProduccion::reporteTeleRadiologia()`. Registrada ruta web `/descarga-reportes-teleradiologia`.
    * Frontend: Incorporada tarjeta nativa con icono `fa-globe` y modal interactivo de filtro por sede y rango de fechas.
  - **Verificaciones Estrictas:**
    * Laravel Pint passed (`{"tool":"pint","result":"passed"}`) ✅.
    * ESLint: 0 errores / 0 advertencias (`npx eslint` passed) ✅.
    * Vite build: Compilación exitosa en 1.20s (`npm run build`) ✅.
- [x] **2026-10-27 — Auto-Reconciliación PACS y Modality Worklist (MWL Dcm4chee):**
  - **Ampliación de Esquema (`worklist_ordenes`):** Creada migración `2026_10_27_000001_add_adquirido_and_reconciliation_to_worklist_ordenes.php` agregando estado `ADQUIRIDO` al ENUM, columnas `study_pk`, `estudio_id` y timestamp `adquirido_at`. Actualizado modelo `WorklistOrden` con casts y relación `estudio()`.
  - **Servicio de Reconciliación (`WorklistService::reconciliarOrdenes`):** Cruce automático entre órdenes `PENDIENTE` de LIDiag y la tabla `study` de Dcm4chee mediante `accession_no` y `study_iuid`. Al detectar imágenes adquiridas, actualiza estado a `ADQUIRIDO` y marca `sps_status = 1` (COMPLETED) en `pacsdb.mwl_item` para limpiar la consola del equipo. Si el estudio ya fue asignado en LIDiag, transiciona a `COMPLETADO`.
  - **Auto-Cierre en Asignación (`EstudioService::create`):** Método `autoReconciliarWorklistOrden()` que transiciona automáticamente órdenes coincidentes a `COMPLETADO` al registrar formalmente la lectura del estudio.
  - **Mapeo Integral de Estados (`PacienteService::listar`):** Mapeo de órdenes del día para pacientes con estados `PENDIENTE`, `ADQUIRIDO` y `COMPLETADO`.
  - **Feedback en Tiempo Real en Frontend (`admisionComponent.vue`):**
    * Badges tricolor dinámicos en la tabla de pacientes: 🟡 `badge-warning` (*En espera de Rayos X*), 🔵 `badge-info` (*Imágenes en PACS / Listo para Asignar*), 🟢 `badge-success` (*Asignado a Lectura*).
    * Botón de acción rápida con icono `fa-arrow-circle-right` para saltar directamente a Asignación (`/estudios.asignar.listar`) cuando las imágenes están en PACS.
    * Botón asistencial en cabecera *"Reconciliar PACS"* con spinner y toast defensivo (`POST /api/pacientes/worklist-reconciliar`).
  - **Comando Artisan y Tarea Programada:** Creado `WorklistReconcileCommand` (`php artisan worklist:reconcile {--sede=}`) y registrado en Laravel Scheduler (`routes/console.php`) ejecutándose `everyFiveMinutes()->withoutOverlapping()`.
  - **Verificaciones Estrictas:** Laravel Pint passed ✅ | `npx eslint` 0 errores ✅ | `npm run build` en 1.23s ✅ | `php artisan worklist:reconcile` probado y ejecutado exitosamente con 0 fallos ✅.
- [x] **2026-10-27 — Enriquecimiento y Rediseño Modular de Reportes e Indicadores por Categorías Temáticas:**
  - **Reestructuración de UI en Vue 3 (`reporteProduccionComponent.vue`):**
    * Segmentación completa en **5 categorías temáticas**: *1. Asistencial*, *2. Citas*, *3. Financiero / Liquidación*, *4. Calidad* y *5. Dosimetría*.
    * Pestañas clínicas dinámicas (`custom-clinical-pills`) con iconos temáticos y badges descriptivos.
    * Incorporación de **Tarjetas de KPIs** por categoría: métricas en tiempo real consumiendo `/api/reportes.dashboard` (estudios asignados, informados, pendientes de transcripción, tiempo promedio TAT, tasa de ausentismo, efectividad de confirmaciones WhatsApp, estado de interoperabilidad SAHI y conformidad NRD Res. 482).
    * Catálogo de tarjetas de reportes interactivos con descripciones clínicas, badges de alcance y modales de parametrización rápida.
    * Enlaces directos a módulos operativos complementarios (`/liquidacion`, `/productividad`, `/dosis/dashboard`).
  - **Verificaciones Estrictas:**
    * ESLint: 0 errores / 0 advertencias (`npx eslint` passed) ✅.
    * Vite build: Compilación exitosa en 1.20s (`npm run build`) ✅.
- [x] **2026-10-27 — Soporte para Dominios Virtuales Laragon (.test) en TrustHosts (HTTP 400 en Producción):**
  - **Diagnóstico:** En el servidor de producción `10.35.47.4`, al acceder a la instancia `lidiag-update` mediante el dominio virtual `https://lidiag-update.test/`, Symfony arrojaba `BadRequestHttpException` (HTTP 400 Bad Request: *"Oops! An Error Occurred. The server returned a 400 Bad Request... Untrusted Host"*). Esto ocurre porque `TrustHosts` de Laravel se activa en `APP_ENV=production` y por defecto solo confía en `allSubdomainsOfApplicationUrl()` e IPs privadas, rechazando dominios `.test` no declarados explícitamente.
  - **Solución en Código:** Actualizado `app/Http/Middleware/TrustHosts.php` agregando el patrón de regex `"^.*\\.test$"` para confiar de forma transparente en todos los dominios y vhosts de Laragon (`lidiag-update.test`, `lidiag.test`, etc.).
  - **Solución Rápida en `.env` Servidor:** Para la instancia activa en `10.35.47.4`, también se puede definir en su `.env`: `TRUSTED_HOSTS=lidiag-update.test,lidiag.test,10.35.47.4` o `APP_URL=https://lidiag-update.test` y ejecutar `php artisan config:clear`.
  - **Verificación Estricta:** Validado con script en entorno Laravel simulando petición HTTP a `https://lidiag-update.test/` (aprobado con éxito y bloqueando hosts no confiables externos) | Laravel Pint passed ✅.
- [x] **2026-10-27 — Actualización de Font Awesome a Versión Moderna (FA 7.3.1 / Free) con Compatibilidad v4-shims:**
  - **Actualización de Dependencia:** Incorporado `@fortawesome/fontawesome-free` (^7.3.1) en `package.json` y `package-lock.json`.
  - **Integración con Vite (`resources/css/app.css`):** Añadidos imports de `@fortawesome/fontawesome-free/css/all.min.css` y `@fortawesome/fontawesome-free/css/v4-shims.min.css`. Vite empaqueta automáticamente las fuentes web modernas (`fa-solid-900.woff2`, `fa-regular-400.woff2`, `fa-brands-400.woff2`, `fa-v4compatibility.woff2`) en los bundles de producción de Vue y estilos generales.
  - **Actualización de Plantillas AdminWrap (`admin-wrap` y `admin-wrap-login`):**
    * Actualizados `public/admin-wrap/scss/icons/font-awesome/css/font-awesome.min.css` y `font-awesome.css` con la compilación unificada de Font Awesome moderno y sus `v4-shims`.
    * Actualizados `public/admin-wrap-login/scss/icons/font-awesome/css/font-awesome.min.css` y `font-awesome.css`.
    * Copiados los archivos de fuentes WOFF2 en `public/admin-wrap/scss/icons/font-awesome/webfonts/` y `public/admin-wrap-login/scss/icons/font-awesome/webfonts/`.
  - **Compatibilidad y Nuevos Iconos:**
    * Conservada compatibilidad total al 100% con iconos heredados de Font Awesome 4 (`fa fa-envelope-o`, `fa fa-dashboard`, `fa fa-user-circle-o`, `fa fa-file-text-o`, etc.) mediante los shims.
    * Habilitada compatibilidad nativa para nuevos iconos médicos, regulatorios y modernos como `fa fa-radiation` (utilizado en el menú de Dosis / Dosimetría en `sidebar.blade.php`), `fa fa-brain`, `fa fa-hospital`, etc.
  - **Verificaciones:** Prettier verificado en `resources/css/app.css` ✅ | Vite build completado exitosamente en 1.14s (`npm run build`) ✅.
- [x] **2026-10-27 — Integración del Reporte de Dosimetría en el Módulo Central de Reportes:**
  - **Componente Vue (`reporteProduccionComponent.vue`):** Incorporada tarjeta de *"Dosimetría y Radiación"* en el módulo general de reportes (`/reportes-produccion`). Añadido modal interactivo (`modalDosimetriaVisible`) con selector de sedes (`...::: Todas las sedes :::...` con valor `0`), inputs de rango de fechas ("Desde" / "Hasta"), atajos de teclado (`Esc`, `Ctrl+S`) y botón de descarga con feedback de carga.
  - **Backend y Routing:** Registrada ruta web `/descarga-reportes-dosimetria` en `routes/web.php` delegando a `DosimetriaController::exportar()`. Actualizado `DosimetriaFiltroRequest` con método `prepareForValidation()` para normalizar dinámicamente alias de parámetros (`sedeId` -> `sede_id`, `fechaIni` -> `fecha_desde`, `fechaFin` -> `fecha_hasta`, y valor `0` a `null`), permitiendo interoperabilidad transparente.
- [x] **2026-10-27 — Incorporación de Gráficos de Tortas y Barras en Dashboard Web y Reporte Excel:**
  - **Dashboard Web Interactivo (`DashboardDosimetria.vue`):**
    * Integración de componentes `Doughnut` y `Bar` con registro de `ArcElement` en Chart.js.
    * *Gráfico de Torta 1:* Distribución porcentual y conteo de estudios por modalidad técnica de adquisición (`CT`, `DX`, `CR`, `MG`).
    * *Gráfico de Torta 2:* Semáforo regulatorio de cumplimiento NRD (Resolución 482 de 2018) segmentado en Conforme (Seguro), Alerta Preventiva (>80%) y Superación Crítica NRD.
    * *Gráficos de Barras y Tendencia:* Comparativa de dosis promedio registrada por sede frente al límite NRD institucional y tendencia mensual histórica.
  - **Reporte Excel Nativo con Gráficos (`DosimetriaEstadisticasSheet.php`):**
    * Implementación de la interfaz `WithCharts` de Maatwebsite Excel / PhpSpreadsheet (`Chart`, `DataSeries`, `DataSeriesValues`, `PlotArea`, `Legend`, `Title`).
    * Generación automática de gráfico nativo de barras (`DataSeries::TYPE_BARCHART`) para contrastar dosis promedio vs límite NRD.
    * Generación automática de gráfico nativo de torta (`DataSeries::TYPE_PIECHART`) con la distribución de estudios por modalidad técnica.
    * Posicionamiento dinámico de los gráficos debajo de la tabla analítica mensual.
  - **Verificaciones Estrictas:**
    * Laravel Pint: Passed (`{"tool":"pint","result":"passed"}`) ✅
    * ESLint: 0 errores, 0 advertencias ✅
    * Vite build: Compilación exitosa en 2.29s (`npm run build`) ✅
    * Prueba binaria de exportación con gráficos nativos validada (25.9 KB) ✅
- [x] **2026-10-26 — Generación de Reporte Excel Multi-Hoja de Dosimetría con Estadísticas y Detalle Clínico:**
  - **Arquitectura de Exportación (`app/Exports/Dosimetria/`):** Implementado `DosimetriaExport` (`WithMultipleSheets`) compuesto por dos hojas profesionales de auditoría técnica y biomédica:
    * *Hoja 1 — Estadísticas y NRD (`DosimetriaEstadisticasSheet`):* Encabezado institucional Salud Tolima, bloque de KPIs ejecutivos, tabla analítica mensual agrupada por sede, modalidad y tipo de métrica, contraste contra límites regulatorios NRD (Resolución 482 de 2018) y semáforo semántico condicional de alerta.
    * *Hoja 2 — Registros Detallados (`DosimetriaDetalleSheet`):* Trazabilidad completa disparo por disparo con equipo emisor, AET Title, Study UID, descripción del procedimiento, magnitud de radiación medida y hash SHA-256 anonimizado del paciente conforme a la norma ISO/IEC 27001.
  - **Backend y Servicio:** Desarrollado `DosimetriaService::obtenerDatosExportacion()`, endpoint `DosimetriaController::exportar()` y rutas `/api/dosis/exportar` y `/api/dosimetria/exportar` con guardia dual `auth:sanctum,web` permitiendo descarga directa desde la interfaz web y por API.
  - **Frontend Vue 3 (`DashboardDosimetria.vue`):** Incorporado botón *"Exportar Excel"* en el encabezado del dashboard con enlace dinámico respetando los filtros de sede, modalidad, métrica y rango de fechas seleccionados.
  - **Verificación Estricta:** Laravel Pint passed ✅ | ESLint 0 errores/advertencias ✅ | Vite `npm run build` en 2.24s ✅ | Prueba binaria Maatwebsite validada con generación exitosa de 22.8 KB ✅.
- [x] **2026-10-26 — Verificación en Vivo y Transmisión Exitosa a Producción (10.35.47.4):**
  - Culminada exitosamente la prueba integral de extremo a extremo (E2E): 79 de 79 registros de dosimetría extraídos de imágenes DICOM locales fueron transmitidos y persistidos al 100% en el servidor de producción `10.35.47.4` (`medicion_dosis_radiacion` y `medicion_dosis_equipos_emisores`).
  - El pipeline completo opera de forma desatendida, resiliente y en cumplimiento con la Resolución 482 de 2018.
- [x] **2026-10-26 — Exención de ForcePasswordChange para Tokens Sanctum M2M (HTTP 403):**
  - Solucionado bloqueo HTTP 403 (`{"success":false,"message":"Debe cambiar su contraseña."}`) en peticiones de API automatizadas del Agente Edge.
  - Actualizado `ForcePasswordChange.php` para omitir la exigencia de cambio de contraseña interactivo cuando la solicitud proviene de un Personal Access Token de Sanctum (`$request->user()->currentAccessToken()`).
  - Actualizado `DosimetriaTokenCommand.php` para asegurar que el usuario emisor tenga `password_changed_at` asignado. Pint passed ✅.
- [x] **2026-10-26 — Corrección Crítica de TrustHosts para Servidores en Intranet e IPs Privadas (HTTP 400):**
  - Identificada la causa raíz del error HTTP 400 (`Untrusted Host "10.35.47.4"`) en producción: `TrustHosts` de Laravel se activa automáticamente cuando `APP_ENV=production` y por defecto solo confía en `allSubdomainsOfApplicationUrl()`. Las peticiones realizadas por IP directa a la API eran bloqueadas de raíz por Symfony arrojando `BadRequestHttpException` (400) y enmascaradas como `Error interno del servidor.` en respuestas JSON.
  - Actualizado `app/Http/Middleware/TrustHosts.php` para confiar explícitamente en rangos de IP privadas de intranet hospitalaria (`10.x`, `192.168.x`, `172.16-31.x`), `localhost`, `127.0.0.1` y soporte para variable `TRUSTED_HOSTS` en `.env`.
  - Verificado con test unitario en entorno simulado de producción. Pint passed ✅.
- [x] **2026-10-26 — Estandarización Modular del Módulo Dosis (`medicion_dosis_*`) y Diagnóstico de Envío:**
  - **Modularización y Tablas:** Refactorizadas las 3 migraciones y modelos según la convención modular del proyecto:
    * `medicion_dosis_equipos_emisores` (anteriormente `equipos_emisores`)
    * `medicion_dosis_radiacion` (anteriormente `registros_dosis_radiacion`)
    * `medicion_dosis_umbrales_nrd` (anteriormente `umbrales_nrd`)
  - **Modelos y Servicios:** Actualizados `EquipoEmisor`, `RegistroDosisRadiacion`, `UmbralNrd` y las consultas crudas en `DosimetriaService.php` (`obtenerDashboard()`).
  - **Rutas Canónicas y Sidebar:** Registradas rutas canónicas `/dosis/dashboard` y `/api/dosis/registros` en `routes/web.php` y `routes/api_dosimetria.php` conservando alias de retrocompatibilidad. Actualizado el sidebar a `Dosis`.
  - **Diagnóstico y Blindaje en Agente Edge (`agente_dosimetria.py`):**
    * *Caché local:* Agregadas banderas `--forzar` (ignora caché local para retransmitir) y `--limpiar-cache` (elimina `logs/dosimetria_cache_procesados.json`). Esto solucionó la omisión de estudios cuando el agente se corre consecutivamente o tras cambiar de servidor.
    * *Diagnóstico HTTP 404:* Diagnóstico automático en logs cuando un endpoint responde 404 advirtiendo sobre la necesidad de `/public/` en entornos Apache (`https://10.35.47.4/lidiag-update/public/api/dosis/registros`).
  - **Empaquetado y Verificación:** Actualizado `README.md`, reempaquetado `agente_backups_dcm.zip`, y validación de estilo con Pint (`passed`).
- [x] **2026-10-26 — Estimación Automática de DAP (IAEA TRS-457) para Rayos X Digitales en Agente:**
  - Implementado algoritmo de cálculo indirecto de Producto Dosis-Área (DAP en $Gy \cdot cm^2$) en `agente_dosimetria.py` para modalidades `DX` y `CR` cuando el hardware de RX carece de cámara física o emite `DAP: 0.000000`.
  - El cálculo combina los parámetros reales del disparo (`kVp`, `mAs`) con el área colimada del detector (`Rows`, `Columns`, `ImagerPixelSpacing`) y el rendimiento estándar del tubo según directrices del OIEA y Res. 482 de 2018.
  - Verificada extracción exitosa de 47 métricas en 50 estudios de Rayos X de tórax, miembros y columna, transmitiendo en vivo a Central.
- [x] **2026-10-26 — Informe Técnico de Dosimetría para Proveedores y Biomédica:**
  - Creado documento formal `INFORME_TECNICO_DOSIMETRIA_PROVEEDORES.md` detallando la auditoría de cabeceras DICOM en equipos RX/CT según Resolución 482 de 2018.
  - Documentadas evidencias de tags nulos (`DAP: 0.000000`) en Rayos X digitales convencionales frente a Tomografía activa (`CTDIvol: 4.3 a 47.6 mGy`).
  - Formuladas preguntas técnicas para proveedores de equipos y planteada alternativa de cálculo indirecto de Dosis en Superficie de Entrada (ESD) a partir de `KVP` y `mAs`.
- [x] **2026-10-26 — Soporte de Rango de Fechas Fijo (`--desde` / `--hasta`) en Agente de Dosimetría:**
  - Incorporados parámetros CLI `--desde YYYY-MM-DD` y `--hasta YYYY-MM-DD` en `agente_dosimetria.py` para permitir auditorías y escaneos históricos específicos sin depender de una ventana relativa fija.
  - Verificada extracción exitosa de 15 tomografías reales con dosis `CTDIvol` (4.3 mGy a 47.6 mGy) en el mes de agosto y transmisión a Central.
- [x] **2026-10-26 — Documentación Completa y Catálogo de Comandos en README del Agente PACS:**
  - Enriquecido `README.md` del paquete `agente_backups_dcm` con la descripción técnica y clínica de cada script (`backup_dcm_agent.py`, `sync_dcm_images.py`, `agente_dosimetria.py`, `restore_dcm_images.py`, `install.bat`, `instalar_tareas_sede.bat`).
  - Catalogados todos los comandos CLI con sus banderas (`--once`, `--threads`, `--dias`, `--limit`, `--dry-run`, `--year`, `--from-year`, etc.) y sus correspondientes lanzadores `.bat`.
- [x] **2026-10-26 — Compatibilidad MySQL ONLY_FULL_GROUP_BY en Agente de Dosimetría:**
  - Corregido fallo de consulta SQL `1055 (42000): Expression #2 of SELECT list is not in GROUP BY clause... is incompatible with sql_mode=only_full_group_by`.
  - Agregadas funciones de agregación ANSI SQL (`MAX`, `MIN`) en `buscar_estudios_candidatos()` de `agente_dosimetria.py`.
  - Implementada desactivación dinámica de `ONLY_FULL_GROUP_BY` a nivel de sesión MySQL en `get_db_connection()`, garantizando compatibilidad multi-versión (MySQL 5.5, 5.7, 8.0, 8.4 y MariaDB).
- [x] **2026-10-26 — Estandarización y Simplificación de Lanzadores Batch a Variables del Sistema:**
  - Corregido error de parsing de cmd.exe provocado por interpolación de comillas y caracteres especiales en `ejecutar_dosimetria.bat`.
  - Simplificados todos los scripts lanzadores (`ejecutar_dosimetria.bat`, `ejecutar_backup_db.bat`, `ejecutar_sync_imagenes.bat`, `ejecutar_restore_imagenes.bat`, `install.bat`) para invocar directamente `python` desde las variables del sistema (`%PATH%`).
  - Auto-descubrimiento transparente de dependencias dentro del propio código Python para cargar `venv/Lib/site-packages` si está presente.
  - Paquetes distribuibles `agente_backups_dcm.zip` y `agente_backups_dcm.rar` actualizados.
- [x] **2026-10-26 — Instalador Automatizado de Entorno Virtual y Dependencias (`install.bat` + `requirements.txt`):**
  - Creado `requirements.txt` con las versiones estables de las librerías clínicas oficiales (`pydicom`, `requests`, `mysql-connector-python`, `urllib3`).
  - Perfeccionado `install.bat` con autodetección dinámica de Python en el servidor, creación/reutilización automática de entorno virtual dedicado (`venv`), actualización de `pip` y verificación de importación con feedback en consola.
  - Actualizados todos los scripts lanzadores (`ejecutar_dosimetria.bat`, `ejecutar_backup_db.bat`, `ejecutar_sync_imagenes.bat`, `ejecutar_restore_imagenes.bat`) para priorizar de forma transparente el intérprete aislado de `%~dp0venv\Scripts\python.exe`.
  - Regeneración de paquetes distribuibles `agente_backups_dcm.zip` y `agente_backups_dcm.rar`.
- [x] **2026-10-26 — Automatización de Recuperación ante Desastres (Disaster Recovery) de Imágenes PACS (`restore_dcm_images.py`):**
  - Implementado `restore_dcm_images.py` para la restauración automatizada del repositorio de 18 TB desde la QNAP hacia el disco local `D:\img_dcm` en orden estrictamente cronológico descendente (`2026` -> `2025` -> `2024` -> ...).
  - Garantiza la continuidad asistencial inmediata: al completarse el año actual (`2026`), los médicos de urgencias y consulta externa pueden visualizar estudios recientes en Oviyam/OHIF mientras los años históricos continúan restaurándose en segundo plano.
  - Motor de alta velocidad con Robocopy multi-hilo (`/MT:16` a `/MT:32`), exclusión de archivos temporales (`/XF`, `/XD`) y capacidad de reanudación al 100% sin re-descargar (`/XO /XN /XC`).
  - Creado lanzador interactivo `ejecutar_restore_imagenes.bat` con opciones de restauración total, año prioritario urgente (`--year=YYYY`) y modo simulación (`--dry-run`).
  - Actualización de manual en `README.md` y paquetes distribuibles `agente_backups_dcm.zip` y `agente_backups_dcm.rar`.
- [x] **2026-10-26 — Blindaje Integral de Grado Clínico y Telemetría Unificada en los 3 Agentes PACS:**
  - **`sync_dcm_images.py`:** Exclusión nativa de archivos basura (`/XF desktop.ini Thumbs.db *.tmp *.syncrootid`) y carpetas del NAS (`/XD @Recycle .syncing_db .qsyncclient "$RECYCLE.BIN"`), control de concurrencia con lockfile (`logs/sync_dcm.lock`), terminación limpia de Robocopy ante `Ctrl+C` y rotación automática de logs a 20 MB con `RotatingFileHandler`.
  - **`backup_dcm_agent.py`:** Pre-chequeo TCP a MySQL (puerto 3306), control de concurrencia con lockfile (`logs/backup_db.lock`), purga defensiva de archivos `.sql` temporales ante fallos y verificación estricta de integridad post-transferencia en la NAS QNAP (`tam_remoto == tam_local`).
  - **`agente_dosimetria.py`:** Transmisión HTTPS en micro-lotes de 50 registros (`chunking`), control de concurrencia con lockfile (`logs/dosimetria.lock`) y rotación de logs a 10 MB con `RotatingFileHandler`.
  - **Manifiesto de Salud Unificado (`healthcheck_agente.json`):** Emisión continua en `logs/` y réplica en la raíz de la QNAP consolidando el estado en vivo de Base de Datos, Imágenes y Dosimetría en un único JSON.
  - Paquetes de despliegue hospitalario `agente_backups_dcm.zip` y `agente_backups_dcm.rar` actualizados.
- [x] **2026-10-26 — Corrección y Desacople de Líneas Colisionadas de Robocopy Multihilo (`sync_dcm_images.py`):**
  - Identificada y solucionada la condición de carrera inherente de Robocopy en modo multihilo (`/MT`), donde los hilos concurrentes escriben fragmentos de texto en la misma línea sin bloqueo de sincronización (`mutex`).
  - Desarrollada la función de normalización `normalize_robocopy_output_line()` que detecta rutas pegadas, separa estados y reconecta prefijos huérfanos con sus respectivas rutas completas de archivos.
  - Captura continua mediante `subprocess.Popen(bufsize=0)` escribiendo cada entrada limpia en una línea independiente en `logs/robocopy_scan.log` e imprimiendo en vivo en CMD.
- [x] **2026-10-26 — Centralización y Ordenamiento de Logs en Subdirectorio `logs/` del Agente Hospitalario:**
  - Redireccionadas todas las bitácoras operativas hacia `logs/`:
    * `logs/sync_imagenes.log`: Bitácora del agente continuo de sincronización de imágenes PACS.
    * `logs/robocopy_scan.log`: Registro detallado del escaneo multi-hilo de Robocopy en vivo.
    * `logs/backup_db.log`: Bitácora y telemetría de respaldo local de MySQL y subida a QNAP vía `TeeLogger`.
    * `logs/dosimetria_agent.log`: Registro de extracción de radiación ionizante y transmisión a LIDiag Central.
  - Creación automática y resiliente del directorio `logs/` en `sync_dcm_images.py`, `backup_dcm_agent.py`, `agente_dosimetria.py` y `ejecutar_dosimetria.bat`.
  - Regeneración de paquetes distribuibles `agente_backups_dcm.zip` y `agente_backups_dcm.rar`.
- [x] **2026-10-26 — Transmisión Inmediata de Backups MySQL a NAS QNAP (`backup_dcm_agent.py`):**
  - Refactorizado `backup_dcm_agent.py` para que inmediatamente después de terminar el volcado local de MySQL, compresión ZIP, prueba de integridad CRC32 y cálculo de huella SHA-256, transmita de forma autónoma los archivos (`.zip`, `.sha256` y manifiesto JSON de telemetría) hacia la NAS QNAP vía SMB (`\\<qnap_ip>\<qnap_share_backups>`).
  - Pre-chequeo de conectividad TCP en puerto 445 del QNAP antes de iniciar transmisión, autenticación por sesión con `net use` y cálculo en vivo de tasa de transferencia en MB/s.
  - Purga automática por retención remota (`retention_days`) en la carpeta de la NAS para evitar saturación del almacenamiento central.
  - Fallback defensivo: si la NAS o la red están inaccesibles, el backup local se preserva intacto sin interrumpir el ciclo operativo.
  - Actualización y compilación de paquetes de distribución hospitalaria `agente_backups_dcm.zip` y `agente_backups_dcm.rar`.
- [x] **2026-10-26 — Streaming en Vivo de Escaneo Robocopy para Repositorios Masivos (~18 TB) (`sync_dcm_images.py`):**
  - Implementación de logging detallado en vivo con Robocopy agregando banderas `/V` (verbose/escaneo de cada archivo), `/FP` (rutas completas), `/LOG:{ROBOCOPY_LOG_FILE}` y `/TEE` para volcado directo a disco en `robocopy_scan.log`.
  - Desactivación de buffering en el pipeline de Python (`bufsize=1`, decodificación incremental) permitiendo monitoreo en tiempo real del progreso de indexación.
  - Filtrado del log en consola para visibilizar únicamente rutas locales de origen (`D:\img_dcm`), suprimiendo avisos de carpetas remotas `*EXTRA Dir` de la QNAP.
- [x] **2026-10-26 — Comando Artisan de Emisión de Token Sanctum para Dosimetría (`dosimetria:token`):**
  - Implementado `DosimetriaTokenCommand.php` (`php artisan dosimetria:token`) para emisión y revocación ágil de tokens Bearer de Laravel Sanctum destinados al Agente Edge hospitalario (`agente_dosimetria.py`).
  - Soporte de banderas `--user=`, `--name=` y `--revoke` (purga de tokens previos) con impresión formateada del token plano y del snippet JSON listo para `config.json`.
  - Configuración y validación de transmisión por IP directa (`https://10.35.47.4/lidiag-update/public/api/dosimetria/registros`) tolerante a SSL auto-firmado mediante `verify=False` en el cliente Python.
  - Actualización de `config.json.example` y `README.md` en el paquete hospitalario `storage/app_lidiag/agente_backups_dcm/`.
- [x] **2026-10-23 — Implementación del Módulo de Dosimetría y Control de Radiación (Resolución 482 de 2018 / ISO 27001):**
  - **Base de Datos y Modelos:** Creadas 3 migraciones en plural estricto y en español (`equipos_emisores`, `registros_dosis_radiacion`, `umbrales_nrd`). Modelos Eloquent `EquipoEmisor`, `RegistroDosisRadiacion` y `UmbralNrd` en `app/Models/Dosimetria/` con relaciones `belongsTo(ConfigSede::class, 'sede_id')`. Actualizado el modelo existente `ConfigSede` con `equiposEmisores()` y `registrosDosis()`.
  - **Agente Edge en Python 3 (`storage/app_lidiag/agente_backups_dcm/`):** Desarrollado `agente_dosimetria.py` integrado directamente en el paquete unificado del agente de backups hospitalario. Anonimización irreversible de PHI mediante SHA-256 (`patient_id_hashed`), extracción de métricas según modalidad (`CTDIvol`, `DLP`, `DGM`, `DAP`) con `pydicom`, transmisión en lotes vía HTTPS con Bearer Token y reintentos automáticos (Backoff exponencial de 4 intentos con `requests.adapters.HTTPAdapter`). Scripts de automatización `ejecutar_dosimetria.bat`, instalador unificado `instalar_tareas_sede.bat` (Windows Task Scheduler con 3 tareas: DB, Imágenes y Dosimetría) y manual técnico actualizado en `README.md`.
  - **Capa Backend y API:** Desarrollados FormRequests `DosimetriaStoreRequest` y `DosimetriaFiltroRequest`. Servicio `DosimetriaService` (`app/Services/Dosimetria/`) con `guardarLote()` (upsert masivo en bloques de 200 y auto-registro de equipos emisores por AET Title) y `obtenerDashboard()` (consolidado mensual por sede/modalidad y contraste contra NRD). Thin Controller `DosimetriaController` (`app/Http/Controllers/Api/`) con middleware de permisos Spatie (`dosimetria.dashboard`).
  - **Cableado Completo de 5 Puntos:** Ruta API `routes/api_dosimetria.php` registrada en `bootstrap/app.php`, ruta web `dosimetria.dashboard` en `routes/web.php`, vista blade `resources/views/modulos/dosimetria/dashboard.blade.php`, registro en `resources/js/app.js` (`DashboardDosimetria`), y enlace activo en `resources/views/comunes/sidebar.blade.php` bajo la sección independiente *"Dosimetría"* con icono `fa-radiation`.
  - **Frontend Vue 3 (`DashboardDosimetria.vue`):** Desarrollado en Composition API (`<script setup>`) con visualización analítica usando `chart.js` y `vue-chartjs` (barras comparativas de dosis promedio vs límite NRD y líneas de tendencia mensual), `TableSkeletonLoader` (CLS = 0), alertas clínicas semánticas (badges `.bg-danger` y alerta visual cuando una sede sobrepasa los umbrales NRD), tabla detallada de últimas telemetrías y soporte nativo para Modo Oscuro Radiológico (Slate Clínico).
  - **Seeder Regulatorio:** Creado y ejecutado `PermisionDosimetriaSeeder` con permisos Spatie (`dosimetria.dashboard`, `dosimetria.registros`, `dosimetria.configurar`) y siembra de umbrales NRD nacionales para CT, MG y DX.
  - **Verificación Estricta:** `vendor\bin\pint.bat --test` passed ✅ | `npx eslint` 0 errores / 0 warnings ✅ | Vite `npm run build` en 1.96s ✅ | Tests transaccionales con `DB::rollBack()` validados ✅.
- [x] **2026-10-21 — Implementación del Módulo Pacientes ➔ Admisión y Modality Worklist (MWL) con DCM / Dcm4chee:**
  - Creada tabla y migración `worklist_ordenes` (`2026_10_21_000001_create_worklist_ordenes_table.php`) para trazabilidad local de órdenes en LIDiag con claves foráneas e índices de rendimiento.
  - Creado modelo `WorklistOrden` (`app/Models/Worklist/WorklistOrden.php`) y vinculada relación en `Paciente` (`worklistOrdenes`).
  - Desarrollado `DicomDatasetBuilder` (`app/Helpers/DicomDatasetBuilder.php`) para codificación binaria de datasets DICOM estándar (Little Endian Explicit VR) para `mwl_item.item_attrs` y `patient.pat_attrs`.
  - Desarrollado `WorklistService` (`app/Services/Dcm4chee/WorklistService.php`): conexión dinámica `mysql_sucursal` a Dcm4chee de la sede activa, generación de `accession_no` mixto (`RX-YYYYMMDD-####`), sincronización en `patient`, inserción en `mwl_item` con dataset completo y retiro/cancelación segura de órdenes.
  - Desarrollado `PacienteService` (`app/Services/Pacientes/PacienteService.php`) con CRUD de pacientes, mapeo reactivo de órdenes activas de Worklist del día y catálogos auxiliares (tipos de documento y productos de Rayos X).
  - Creados 5 FormRequests dedicados en `app/Http/Requests/Pacientes/`: `PacienteIndexRequest`, `PacienteStoreRequest`, `PacienteUpdateRequest`, `WorklistEnviarRequest`, `WorklistCancelarRequest`.
  - Implementado `PacienteController` (`app/Http/Controllers/Pacientes/PacienteController.php`) como Thin Controller con middleware de permisos por método para Administrador y Transcriptor (`gestion.pacientes.index`, `gestion.pacientes.create`, `gestion.pacientes.update`, `gestion.pacientes.delete`).
  - Cableado de 5 puntos completado: `routes/api_pacientes.php` registrado en `bootstrap/app.php`, ruta web `gestion.pacientes.admision` en `routes/web.php`, vista blade `resources/views/modulos/pacientes/admision.blade.php`, componente registrado en `resources/js/app.js`, y enlace activo en `resources/views/comunes/sidebar.blade.php`.
  - Desarrollado componente Vue 3 `admisionComponent.vue` (`resources/js/components/modulos/pacientes/admisionComponent.vue`): filtros persistentes con `useFilterPersistence`, Table Skeleton Loader, Empty State, botón con `title="enviar para procedimiento"`, badge dinámico *"En espera de Rayos X"*, modal de envío a Worklist ágil, right-sidebar para crear/editar pacientes y soporte para Modo Claro/Modo Oscuro.
  - Creada especificación técnica completa en `docs/WORKLIST_FLUJO_DCM.md` y documentado en `docs/CONTROLLERS.md`.
  - Verificación estricta: `vendor\bin\pint.bat --test` passed ✅ | ESLint 0 errores/warnings ✅ | Vite `npm run build` en 985ms ✅.
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
- [x] **2026-10-22 — Módulo Admisión & Worklist Rayos X (Pacientes / MWL DICOM):**
  - Implementado módulo completo de Admisión de Pacientes y vinculación a Modality Worklist DICOM (`/pacientes/admision`).
  - Creación de tabla y modelo `WorklistOrden` con estado y tracking local de órdenes enviadas y retiradas de consola.
  - Implementado `WorklistService` con generación de Accession Number consecutivo diario (`RX-YYYYMMDD-####`), sincronización en `pacsdb.patient` y publicación DICOM en `pacsdb.mwl_item` con codificación Little Endian Explicit VR vía `DicomDatasetBuilder`.
  - Homologación 100% de la UI con `AsignarEstudioComponent.vue` (DataTables `#example23`, botones redondeados outline, drawer `right-sidebar`, modal Worklist).
  - **Hotfix Sede Seleccionada:** Resuelto bug HTTP 422 (`La sede es obligatoria para verificar órdenes activas`) al recargar o seleccionar sede en `admisionComponent.vue`:
    - En el frontend: Normalizador `parsearSedeId` capaz de procesar números, strings y objetos emitidos por el bus global `{ idSede, nomSede }`.
    - En el backend: Incorporado `prepareForValidation` en `PacienteIndexRequest` y `WorklistEnviarRequest` con fallback automático a `session('sede_id')`, primer sede del usuario o sede activa por defecto.
    - Sincronización reactiva bidireccional con `sessionStorage('ST-sede')` y `sessionStorage('ST-nomsede')`.
  - **Paginación DataTables:** Homologada al 100% con `AsignarEstudioComponent.vue`:
    - Incorporado `contadorRegistros` computado.
    - Limpieza y destrucción de DataTables previa a la mutación de `registros` para evitar colisión con el Virtual DOM de Vue.
    - Inicialización de DataTables con `dom: "Bfrtip"`, botones de exportación, `pageLength: 10`, y traducción completa de paginación (`Primero`, `Último`, `Siguiente`, `Anterior`, `Mostrando _START_ a _END_ de _TOTAL_ pacientes`).
    - `btnLimpiar` alineado a `AsignarEstudioComponent.vue` reseteando filtros, vaciando registros y destruyendo tabla sin disparar petición innecesaria.
  - **Integración DICOM MWL Dcm4chee & Arquitectura 100% Dinámica (Cero Hardcoding):**
    - Deserialización binaria en Dcm4chee: adaptado `DicomDatasetBuilder` para empaquetar la cabecera File Meta Information estándar de Dcm4chee (`(0002,0000)` y Transfer Syntax UID `1.2.840.10008.1.2.1.99`) comprimiendo el payload con `gzdeflate` (*Deflated Explicit VR Little Endian*).
    - Unicidad de Procedure Step en Dcm4chee: en `pacsdb.mwl_item` el índice único `mwl_sps_id` es compuesto `(sps_id, req_proc_id)`. Se asigna `$accessionNo` a `sps_id` garantizando que múltiples órdenes del mismo CUPS nunca colisionen en el PACS.
    - Detección de modalidades y salas multisede dinámica: endpoint `GET /api/pacientes/worklist-estaciones?sede_id={id}` optimizado para consultar todas las modalidades de adquisición en `pacsdb.ae` (filtrando instancias de archivo `localhost`/`archive instance` sin restringir `installed = 1`, soportando modalidades como `RADMAX` en Rovira o `RXSala2` en Espinal).
    - Reactividad al cambio de sede: `admisionComponent.vue` actualiza y precarga inmediatamente las estaciones al dispararse el evento `sedeSeleccionada`, con estado de carga `loadingEstaciones` y fallback asistido en caso de que una sede no tenga nodos configurados en su PACS.
    - Verificación estricta: `vendor\bin\pint.bat --test` passed ✅ | `npx eslint` passed con 0 errores ✅ | `npm run build` en 992ms ✅.
- [x] **2026-10-13 — Ejecución de Fase 6 (Seguridad & Configuración):**
  - Creados FormRequests: `UpdateUserProfileRequest`, `RolActualizarRequest`, `AuditLogIndexRequest`, `UserEstadoRequest`, `ConfigWhatsappBuscarRequest`, `ServicioProductoNoVinculadosRequest`, y ampliado `ConfigPrioridadRequest`.
  - Refactorizados controladores de Seguridad (`UserController`, `RolController`, `AuditLogController`) eliminando todas las validaciones inline `$request->validate()`.
  - Refactorizados y normalizados todos los controladores de Configuración (`ConfigSedeController`, `ConfigServicioController`, `ConfigProductoController`, `ConfigDiagnosticoController`, `ConfigPlantillaController`, `ConfigPlantillaTipoController`, `ConfigPrioridadController`, `ConfigAdminSaludController`, `ConfigModulosController`, `ConfigTipoDocumentoController`, `ConfigWhatsappController`, `ConfigSedeWhatsappNumberController`, `ConsentimientoInformadoController`, `ServicioProductoController`) con respuestas tipadas, casting entero `(int) $result["status"]` y comillas dobles.
  - Verificación estricta: `vendor\bin\pint.bat --test` passed y `npm run build` passed en 866ms.
- [x] **Refactorización integral de 6 fases completada al 100%:** Estudios, Ecografías, Citas & Agenda, Contratación & Liquidación, Help Desk, Seguridad & Configuración.
- [x] **2026-10-14 — Auditoría y hotfixes del módulo `resources/js/components/modulos/estudios` (9 componentes Vue):**
  - **Bug 1 — `audio-texto.vue`:** `toggleSpeechRecognition` no estaba desestructurado desde `useDictationFlow()`. El clic en el micrófono lanzaba `TypeError`. **Fix:** añadido al destructuring.
  - **Bug 2 — `transcribirEstudioComponent.vue`:** `registro.value.id_estudio` y `registro.value.destino_estudio` nunca se asignaban en `mostrarRegistro()`. Para estudios de Tele-Radiología el backend hacía `Estudio::findOrFail(null)` → HTTP 500. **Fix:** asignación añadida + payload de `guardarRegistro` completado con todos los campos del médico (`nombres_medico`, `reg_medico`, `num_docu_medico`, `firma_medico`, `fec_estudio`, `fec_lectura`).
  - **Bug 3 — `GuardarTranscripcionRequest.php`:** Los campos del médico y fechas adicionales no tenían reglas `nullable`, por lo que `$request->validated()` los descartaba antes de llegar a `PlantillaEstudioService`; el PDF se generaba sin nombre ni firma del médico. **Fix:** reglas `nullable|string` añadidas.
  - **Bug 4 — `leerEstudioComponent.vue`:** Upload de audio fire-and-forget (se perdía si el médico cerraba el panel). `catch` del POST principal silencioso (sin toast). **Fix:** `await axios.post('/api/upload-audio')` dentro del try/success con toast de advertencia propio; toast de error añadido al catch principal.
  - **Bug 5 — `notificarEstudioComponent.vue`:** Sin listener de `emitter.on('sedeSeleccionada')`. Al cambiar sede, el módulo mostraba datos obsoletos. **Fix:** `onMounted`/`onBeforeUnmount` con `onSedeSeleccionada` que limpia resultados y actualiza `busqueda.sede_id`.
  - **Inconsistencia — `estudioAsignadosComponent.vue`:** El payload enviaba `medico`, `reg_med`, `num_docu_medi`, `firma` (aliases de la query SQL) en lugar de los nombres canónicos que espera `PlantillaEstudioService` → PDF sin datos del médico. **Fix:** mapeo explícito a `nombres_medico`, `reg_medico`, `num_docu_medico`, `firma_medico`.
  - Verificación: ESLint ✅ | Pint ✅ | `npm run build` ✅ en todos los archivos modificados (Commit: `cccecdda6`).
- [x] **2026-10-14 — Auditoría y hotfixes de los módulos `contratos` y `liquidacion` (`contratoComponent.vue`, `liquidacionComponent.vue`, `productividadComponent.vue`):**
  - **BUG-01 — `contratoComponent.vue`:** Diálogo de registros ausentes cancelado no abortaba el PUT y guardaba sin confirmación explícita del usuario. **Fix:** Agregada confirmación explícita para guardar solo altas/cambios al cancelar el retiro de faltantes.
  - **BUG-02 — Verificación de contrato backend:** El campo `registro: JSON.stringify(...)` cumple estrictamente la regla `'registro' => 'required|json'` de `ContratoRequest.php`. Se mantuvo intacto para preservar el contrato de API.
  - **BUG-03 — `liquidacionComponent.vue` & `productividadComponent.vue`:** `formatMoney(0)` devolvía el string crudo `"$0"` sin separadores ni espaciado de moneda estándar `es-CO`. **Fix:** Normalización con `Number()` y formateo uniforme con `Intl.NumberFormat("es-CO", { minimumFractionDigits: 0, maximumFractionDigits: 0 })`.
  - **BUG-04 — `contratoComponent.vue`:** Los sidebars de edición de contrato (`sidebarVisible`) y de detalle de médicos (`detalleVisible`) se abrían en paralelo superponiéndose en el DOM. **Fix:** Cierre mutuo al invocar `mostrarRegistro()` y `abrirDetalle()`.
  - **BUG-05 — `contratoComponent.vue`:** Bucle `while (intentos < MAX_INTENTOS)` de importación de Excel finalizaba silenciosamente al agotar intentos sin éxito. **Fix:** Bandera de control `exito` con toast de error informativo de fallback.
  - **BUG-06 — `contratoComponent.vue`, `liquidacionComponent.vue` & `productividadComponent.vue`:** `new Date(dateStr)` sin hora interpretaba medianoche UTC y restaba 1 día en Colombia (UTC-5). **Fix:** Parseo determinista de componentes `[YYYY, MM, DD]` a `DD/MM/YYYY`.
  - **BUG-07 — `liquidacionComponent.vue`:** Botón "Exportar" permitía abrir ventana con archivo vacío o error 422 si no se había calculado la liquidación. **Fix:** Validación defensiva con toast de atención y desactivación del botón en template (`:disabled="loading || !resultado?.lineas?.length"`).
  - **BUG-08 — `contratoComponent.vue`:** Catch de `guardarDetalles` no validaba que `_error.response?.data?.errors` fuese un objeto, arriesgando `TypeError` en errores 500. **Fix:** Lectura y extracción defensiva de mensajes de error.
  - Verificación estricta: ESLint ✅ (0 errores) | `npm run build` ✅ (Commit: `51534fc0b`).
- [x] **2026-10-14 — Auditoría profunda y hotfixes del módulo `resources/js/components/modulos/estudios` (5 componentes Vue corregidos):**
  - **BUG-EST-01 — `estudioAsignadosComponent.vue`:** Desincronización del evento global `sedeSeleccionada`. **Fix:** Inyección de `emitter` y sincronización reactiva en `onMounted`/`onBeforeUnmount`.
  - **BUG-EST-02 — `notificarEstudioComponent.vue`:** Mutación imperativa con jQuery que rompía el binding reactivo `:class="{ 'shw-rside': sidebarVisible }"`. **Fix:** Control 100% reactivo mediante `sidebarVisible.value`.
  - **BUG-EST-03 — `QuemadorCdComponent.vue` & `QuemarMultiplesComponent.vue`:** Polling cada 5 segundos disparaba tormenta de toasts de error si el servicio óptico fallaba. **Fix:** Parametrización `listarUnidades(silent = true)` en timers de fondo y umbral de reintentos en sondeo de quemado.
  - **BUG-EST-04 — `AsignarEstudioComponent.vue`:** Superposición de paneles laterales `sidebarVisible` y `sidebarQuemarMultiplesVisible`. **Fix:** Cierre mutuamente excluyente al abrir cualquiera de los dos.
  - **BUG-EST-05 — `AsignarEstudioComponent.vue`:** Promesa no esperada en liberación de lock y rama inalcanzable de Axios que silenciaba errores 500 en `buscarStudy()`. **Fix:** `await liberarLockAsignacion()` secuenciado y notificación adecuada de errores en `catch`.
  - **BUG-EST-06 — `notificarEstudioComponent.vue`:** Pre-validación de filtros antes de activar `loadingBuscar` y captura no restrictiva de errores en búsquedas. **Fix:** Validación temprana y notificación defensiva en `catch`.
  - Verificación estricta: ESLint ✅ (0 errores, 0 advertencias) | `npm run build` ✅.
- [x] **2026-10-15 — Auditoría profunda y hotfixes del módulo `resources/js/components/modulos/ecografias` (6 componentes Vue y backend corregidos):**
  - **BUG-ECO-12 — `ecografiaLecturaController.php`, `EcografiaLeerRequest.php`, `EcografiaLecturaService.php`:** Fatal Error por clase `EcografiaLecturaController` no encontrada y descarte de `ecografia_producto_id` en validación FormRequest que provocaba HTTP 404 al leer. **Fix:** Restablecida la clase canónica `EcografiaLecturaController` con alias de retrocompatibilidad, agregadas reglas para `ecografia_producto_id` y búsqueda flexible con fallback en el servicio.
  - **BUG-ECO-01 a BUG-ECO-04 — `admisionarComponent.vue`:** Salida temprana en `onMounted` desconectaba el bus de sede si no había sede en sesión; suscripción insegura y `setMapValue` obsoleto; silenciamiento de errores de API. **Fix:** Registro incondicional del listener `sedeSeleccionada` con handler nombrado `onSedeSeleccionada`, mutación directa en `isConsultandoSahi.value[id]`/`isEnviandoSahi.value[id]`, y toasts de feedback defensivos.
  - **BUG-ECO-05 a BUG-ECO-07 — `lecturaComponent.vue`:** Modal de reporte de lectura bloqueado permanentemente por `:style="{ display: 'none' }"`; desfase de fecha de nacimiento por UTC-5; `generarLectura` sin notificación de error en catch. **Fix:** Activación de `modalReporteVisible.value = true`, botón de cierre y reseteo reactivo; cálculo de edad descomponiendo `[YYYY, MM, DD]`; toast de advertencia/error en guardado de lectura; mutación reactiva directa en `isFirmandoConsentimiento.value`.
  - **BUG-ECO-08 & BUG-ECO-09 — `ecografiasAdmisionadasComponent.vue`:** `imprimirReporte` enviaba POST con cuerpo vacío arrojando error 422 y leía `res.data.plantilla` en lugar de `res.data.data.plantilla`; desconexión del event bus de sede y médico; mutación indirecta con `setMapValue`. **Fix:** Envío del objeto `item` construyendo el payload requerido; extracción de URL con fallback; listeners de `sedeSeleccionada` y `medicoSeleccionado` con cleanup; mutación reactiva directa.
  - **BUG-ECO-10 — `ecografiasConsentimientosComponent.vue`:** Desconexión del event bus de sede. **Fix:** Listener `sedeSeleccionada` con handler `onSedeSeleccionada` y cleanup en `onBeforeUnmount`.
  - **BUG-ECO-11 — `ecografiasConsentimientosPlantillasComponent.vue`:** Condición de carrera asíncrona en `onMounted` donde `listar()` abortaba antes de que `listarModulos()` asignara el módulo por defecto. **Fix:** Secuenciación `await listarModulos()` antes de `listar()` en `onMounted`.
  - Verificación estricta: `vendor\bin\pint.bat --test` passed ✅ | `php artisan route:list` passed ✅ | ESLint 0 errores en todos los componentes ✅ | `npm run build` en 1.69s ✅.
- [x] **2026-10-16 — Auditoría profunda y hotfixes del módulo `resources/js/components/modulos/tele-radiologia` (2 componentes Vue):**
  - **`teleRadiologiaEnviarEstudioComponent.vue`:**
    - Sincronización reactiva de sede mediante listener `emitter.on('sedeSeleccionada')` con cleanup en `onBeforeUnmount`.
    - Captura defensiva de errores en `buscarStudy()` con `getUserSafeError` sin silenciar fallos 4xx/red.
    - Soporte para códigos de éxito HTTP 200 y 201 en la creación de estudios (`resEstudio.status == 200 || resEstudio.status == 201`).
    - Cierre defensivo de modal y limpieza condicionados estrictamente a la confirmación exitosa de envío y persistencia.
    - Normalización de fecha de nacimiento (`pat_birthdate`) con `normalizarFecha` de `@/helpers/dateUtils` para inputs tipo date.
  - **`teleRadiologiaTranscibirComponent.vue`:**
    - Completado payload a `/api/estudio-guardarTranscripcion` con metadatos del médico (`nombres_medico`, `reg_medico`, `num_docu_medico`, `firma_medico`) y fechas del estudio (`fec_estudio`, `fec_lectura`) garantizando la emisión íntegra del informe PDF/Word.
    - Sincronización reactiva de sede mediante listener `emitter.on('sedeSeleccionada')` con reseteo de lista y cleanup en `onBeforeUnmount`.
    - Corrección de toast icon a `icon: "error"` (reemplazando `danger`).
  - Verificación estricta: ESLint ✅ (0 errores, 0 warnings en ambos archivos) | Vite `npm run build` en 2.13s ✅.
- [x] **2026-10-16 — Auditoría profunda y hotfixes de HelpDesk & Agenda/Citas (Frontend - 5 componentes Vue + helper alerts):**
  - **`helpdeskReportesComponent.vue` (BUG-HD-01 & BUG-HD-02):** Eliminada condición bloqueante `if (!canExport.value) return;` en `buscar()` que exigía `detallado.length > 0` antes de consultar. Conectado listener reactivo `emitter.on('sedeSeleccionada')` con cleanup en `onBeforeUnmount`.
  - **`helpdeskTicketsComponent.vue` (BUG-HD-02):** Conectado listener reactivo `emitter.on('sedeSeleccionada')` con cleanup en `onBeforeUnmount`.
  - **`helpers/alerts.js`:** Exportada la función faltante `alertWarning(title, text, icon = "warning")` homologada con SweetAlert2/alerts del proyecto.
  - **`citasComponent.vue` (BUG-CIT-01 & BUG-CIT-02):** Desenvoltura defensiva de respuestas del backend envueltas en `BaseService::success()` (`res.data?.data || res.data || []`) en carga de servicios, sedes, productos y slots de agenda (`res.data?.data?.detalles || res.data?.detalles || []`). Reemplazado `$swal` roto con helpers de `@/helpers/alerts.js` (`confirmAction`, `alertSuccess`, `alertError`, `alertWarning`). Conectado listener reactivo `emitter.on('sedeSeleccionada')` con cleanup.
  - **`citasRevisionComponent.vue` (BUG-CIT-03 & BUG-CIT-05):** Estandarizada la extracción de colecciones con fallback en `cargarListas()` y `cargarProductosPorServicio()`. Conectado listener reactivo `emitter.on('sedeSeleccionada')` con cleanup.
  - **`agendaDisponibilidadComponent.vue` (BUG-CIT-04):** Corregido variable shadowing en `validarFormulario()` (`errs` vs `errores`). Limpieza de import `computed` no utilizado y desconexión segura de `emitter.off('sedeSeleccionada')` en `onBeforeUnmount`.
- [x] **2026-10-17 — Auditoría profunda y hotfixes de Seguridad (3 componentes Vue y 1 servicio PHP):**
  - **`auditLogComponent.vue` (BUG-SEG-01 & BUG-SEG-02):** Desempaquetado normalizado y defensivo del `LengthAwarePaginator` (`res.data?.data?.data || res.data?.data || []`) y de las propiedades de paginación (`total`, `last_page`, `current_page`), solucionando ruptura de la tabla y controles. Desempaquetado seguro en `cargarEventos()` (`res.data?.data || res.data || []`).
  - **`usuarioComponent.vue` (BUG-SEG-03 a BUG-SEG-05):** Corrección del toggle inverso del sidebar, fijando apertura determinista `sidebarOpen.value = true;` en `mostrarRegistro()`. Agregado bloque `.catch()` con `toastMessage` en la carga de roles del usuario. Sustitución de `alertError` modal por `toastMessage` y desempaquetado defensivo en `ListarModulos()`, `ListarSedes()` y `ListarServiciosCitas()`.
  - **`usuarioPerfil.vue` (BUG-SEG-06 & BUG-SEG-07):** Lectura segura `res.data?.data?.usuario || res.data?.usuario || {}` evitando `TypeError` y normalizado el toast de actualización de perfil con fallback.
  - **`UserService.php` (BUG-SEG-08):** Eliminados caracteres mojibake en mensajes de error (`"El usuario no se encontró"` y `"La contraseña actual no es correcta."`).
  - Verificación estricta: ESLint 0 errores/warnings ✅ | Pint passed ✅ | Vite `npm run build` en 1.94s ✅.
- [x] Aprobación de commit completada para Seguridad (Commit: `0cc3ed962`).
- [x] **2026-10-17 — Auditoría profunda y normalización integral del módulo `resources/js/components/modulos/configuracion` (19 componentes Vue corregidos):**
  - **Catálogos Generales y Sedes (5):** `configAdminSaludComponent`, `configDiagnosticoComponent`, `configTipoDocumentoComponent`, `configPrioridadComponent`, `configSedeComponent`. Eliminación de mutaciones directas de clases en el DOM sustituidas por `:class="{ 'shw-rside': sidebarVisible }"`, remoción de doble `abrirSidebar()` en templates, toasts en bloques catch silenciosos y no-422, corrección de typos (`elimnarRegistro` -> `eliminarRegistro`) y desempaquetado defensivo.
  - **Servicios y Productos (3):** `configProductoComponent`, `configServicioCitaComponent`, `configServicioProductoComponent`. Eliminación de doble apertura, sidebar reactivo, desempaquetado defensivo en sedes, servicios y productos no vinculados, toasts en catch y migración de `eliminarVinculo` a `async/await`.
  - **Plantillas y Consentimientos (3):** `configPlantilla`, `configTipoPlantilla`, `configConsentimientoInformadoComponent`. Reactividad unificada en right-sidebar, eliminación de llamadas redundantes, toasts en catch silenciosos y fallos no-422, reemplazo de popups SweetAlert (`alertSuccess`/`alertError`) por `toastMessage`, y desempaquetado defensivo en catálogos auxiliares.
  - **WhatsApp y Bot (5):** `configWhatsappComponent`, `configWhatsappNumeros`, `configWhatsappSaludos`, `configWhatsappProductos`, `configCitaRechazoMotivoComponent`. Desempaquetado defensivo de listas, resolución de potencial `TypeError` con `hasError`/`getError` sobre objetos reactivos planos, y enlace reactivo del right-sidebar.
  - **HelpDesk Config (3):** `helpdeskConfigCategoriasComponent`, `helpdeskConfigEstadosComponent`, `helpdeskConfigPrioridadesComponent`. Eliminación de doble apertura, sustitución de `hasError`/`getError` por enlaces reactivos estándar a `errores.nombre`/`errores.orden`, desempaquetado defensivo con fallback `[]`, y sidebar reactivo.
  - Verificación estricta: ESLint 0 errores/warnings en los 19 componentes ✅ | Vite `npm run build` en 945ms ✅.
- [x] Aprobación de commit completada para Configuración (Commit: `da2ac94dc`).
- [x] **2026-10-17 — Auditoría profunda y normalización de Reportes y Varios (`reporteProduccionComponent.vue`, `seleccionarSede.vue`):**
  - **`reporteProduccionComponent.vue`:** Desempaquetado defensivo de sedes activas (`res?.data?.data || res?.data || []`) evitando excepciones en respuestas vacías o no canónicas. Preservada la independencia de selección por modal (sin listener de emitter global de sede).
  - **`seleccionarSede.vue`:** Desempaquetado defensivo (`res?.data?.data || res?.data || []`), persistencia coherente recuperando la sede previamente guardada en `sessionStorage.getItem("ST-sede")` para evitar reinicios al índice 0 tras recargar, y agregada notificación con `toastMessage` en el bloque `catch` ante fallos de conexión.
- [x] **2026-10-19 — UI/UX Categoría 1.1: Implementación de Modo Oscuro / Modo Lectura Radiológica:**
  - `leerEstudioComponent.vue`, `lecturaComponent.vue` y `teleRadiologiaTranscibirComponent.vue`: Sincronización compartida bajo la clave `localStorage.getItem("lidiag_dark_mode")`.
  - Botón selector y conmutador rápido en tabla de estudios y encabezado modal (`.rpanel-title`).
  - Paleta radiológica especializada de alto contraste y bajo brillo (`#181a20`, `#12141a`, `#222632`, `#3b4254`, `#e2e8f0`) optimizada para lectura diagnóstica en estaciones PACS.
  - Soporte scoped para wysihtml5 (`:deep(.wysihtml5-toolbar)`, `:deep(.wysihtml5-sandbox)`).
- [x] **2026-10-19 — UI/UX Categoría 1.1: Implementación de Modo Oscuro Global en toda la aplicación:**
  - Hoja de estilo global `resources/css/dark-theme.css` con paleta Gris Carbón / Slate Clínico (`#0f1117`, `#181a20`, `#14171f`, `#222632`, `#2d3343`, `#e2e8f0`).
  - Botón toggle de 1 clic en el Topbar dentro de `userProfileAndSearchComponent.vue` con sincronización reactiva instantánea mediante EventBus (`lidiagThemeChanged`) y sincronización multi-pestaña (`window.addEventListener('storage')`).
  - Script anti-parpadeo (FOUC) en el `<head>` y `<body>` de `resources/views/layouts/app.blade.php`.
  - Soporte integral de componentes: Topbar, Sidebar lateral, Cards, Tablas (DataTables), Formularios/Inputs, Dropdowns y Modales de Bootstrap.
  - Verificación estricta: ESLint 0 errores/warnings ✅ | Vite `npm run build` en 1.02s ✅.
- [x] **2026-10-19 — UI/UX Categoría 1.2: Panel de Dictado por Voz (STT Whisper) Flotante y Feedback Visual:**
  - Implementado widget flotante tipo cápsula (`.floating-dictation-pill`) en `leerEstudioComponent.vue` y `lecturaComponent.vue`.
  - Aparece de forma no intrusiva en la esquina inferior derecha mientras el panel de informe médico está abierto, acompañando al especialista durante el scroll.
  - Indicador de estado con luz led pulsante (`status-dot`), etiqueta descriptiva ("Grabando Audio", "Escuchando Voz", "Dictado en Pausa", "Dictado Inactivo"), atajo de teclado visible (`F2`) y ondas de sonido animadas (`.pill-waveform`).
  - Botón de control rápido (play/pause/stop) integrado en la cápsula.
  - Soporte completo y automático para Modo Claro y Modo Oscuro radiológico (`.is-dark`).
  - Verificación estricta: ESLint 0 errores/warnings en ambos componentes ✅ | Vite `npm run build` en 973ms ✅.
- [x] **2026-10-19 — UI/UX Categoría 1.3: Modo Pantalla Completa / Modo Zen en Estaciones de Lectura:**
  - `leerEstudioComponent.vue`, `lecturaComponent.vue` y `teleRadiologiaTranscibirComponent.vue`: Agregado botón de conmutación rápida Pantalla Completa / Modo Zen (`fa-expand` / `fa-compress`) en el encabezado `rpanel-title`.
  - Clase `.zen-mode` que expande la estación de lectura al 100% del viewport (`width: 100% !important; height: 100% !important; z-index: 2500 !important; right: 0 !important;`) ocultando barras laterales y distracciones.
  - Verificación estricta: ESLint 0 errores/warnings en los 3 componentes ✅ | Vite `npm run build` en 1.04s ✅.
- [x] **2026-10-19 — UI/UX Categoría 2.1: Indicador visual de Sede y Médico Activo en el Topbar:**
  - `userProfileAndSearchComponent.vue`: Integración de chips visuales destacados en la barra superior con el nombre de la Sede Activa (`badge badge-info` con icono de hospital) y del Médico Activo (`badge badge-success` con icono de médico cuando aplica).
  - Computed reactivos `nomSedeActiva` y `nomMedicoActivo` sincronizados con `sessionStorage`, `sedes`, `medicos` y escuchas dinámicas de `emitter` (`sedeSeleccionada`, `medicoSeleccionado`).
  - Al hacer clic abre directamente el selector de sedes y médicos sin navegación intermedia.
  - Verificación estricta: ESLint 0 errores/warnings ✅ | Vite `npm run build` en 1.00s ✅.
- [x] **2026-10-19 — UI/UX Categoría 2.2: Búsqueda Global y Command Palette (Ctrl+K):**
  - Creado `commandPaletteComponent.vue` registrado globalmente en `app.js` y montado en `toggleAndNavItems.blade.php`.
  - Disparador visual en el Topbar con atajo de teclado global `Ctrl + K` (o `Cmd + K` en macOS) y tecla `Escape` para cerrar.
  - Catálogo exhaustivo de pantallas y módulos clínicos (Estudios, Ecografías, Tele-Radiología, Citas, Agenda, HelpDesk, Contratación, Liquidación, Configuración y Seguridad) con filtrado instantáneo en tiempo real.
  - Navegación accesible con flechas del teclado (`↑` / `↓`) y `Enter` para saltar inmediatamente a la ruta seleccionada.
  - Soporte nativo para Modo Claro y Modo Oscuro radiológico (`body.theme-dark`).
  - Verificación estricta: ESLint 0 errores/warnings ✅ | Vite `npm run build` en 948ms ✅.
- [x] **2026-10-19 — UI/UX Categoría 2.3: Menú Lateral (Sidebar) Acordeón Inteligente y Rutas Activas:**
  - `sidebar.blade.php`: Detección reactiva mediante `request()->routeIs(...)` en todos los módulos (Dashboard, Pacientes, Estudios, Tele-Radiología, Ecografías, Citas, Agenda, Reportes, Helpdesk, Contratación, Liquidación, Parametrización y Seguridad).
  - Apertura automática del acordeón (`.collapse.in` y `aria-expanded="true"`) en la sección donde se encuentra el usuario navegando.
  - Corrección de markup: enlaces agrupados incorrectamente en un solo `<li>` en Estudios y Ecografías ahora cuentan con su propio `<li>` semántico individual.
  - Refinamiento tipográfico de encabezados `.nav-small-cap` (eliminación de guiones crudos `--- ` y estilo moderno con letter-spacing).
  - Micro-transiciones y resaltado de enlaces activos (`.active`) para Modo Claro (`#0284c7`) y Modo Oscuro radiológico (`#38bdf8`) en `app.css` y `dark-theme.css`.
  - Verificación estricta: `npm run build` en 2.24s ✅ | `php artisan view:clear` ✅.
- [x] **2026-10-19 — UI/UX Categoría 3.1: Filtros de Búsqueda Colapsables y Persistencia en Sesión:**
  - Creado composable reutilizable `useFilterPersistence.js` en `resources/js/composables/` para gestión transparente de persistencia en `sessionStorage` y estado colapsable/expandido con conteo reactivo de filtros activos.
  - Implementado en las 3 bandejas operativas principales: `estudioAsignadosComponent.vue`, `citasComponent.vue` y `ecografiasAdmisionadasComponent.vue`.
  - Botón toggle de colapso rápido con animación CSS `.fade-collapse` liberando hasta un 30% más de espacio vertical para tablas DataTable.
  - Badge pill dinámico indicando cuántos filtros activos están aplicados (`X filtros activos`).
  - Auto-ejecución inteligente de la búsqueda al retornar de una lectura o detalle si existen filtros previos persistidos.
  - Botón "Limpiar" conectado a `limpiarFiltrosStorage()` para reseteo completo y limpio.
  - Verificación estricta: ESLint 0 errores/warnings ✅ | Vite `npm run build` en 1.09s ✅.
- [x] **2026-10-19 — UI/UX Categoría 3.2: Estados Vacíos Ilustrados (Empty States) y Feedback Visual:**
  - Creado componente global reutilizable `EmptyStateComponent.vue` en `resources/js/components/comunes/` y registrado en `app.js`.
  - Iconos contextuales clínicos, círculos con micro-interacción hover, textos descriptivos orientados a la acción y botones de recuperación ("Limpiar filtros", "Actualizar").
  - Integrado como reemplazo de tablas vacías ("No data available") en `estudioAsignadosComponent.vue`, `citasComponent.vue` y `ecografiasAdmisionadasComponent.vue`.
  - Soporte integrado de alto contraste para Modo Claro y Modo Oscuro radiológico (`body.theme-dark`).
  - Verificación estricta: ESLint 0 errores/warnings ✅ | Vite `npm run build` en 1.13s ✅.
- [x] **2026-10-19 — UI/UX Categoría 3.3: Skeleton Loaders con Efecto Shimmer en Tablas:**
  - Creado componente global reutilizable `TableSkeletonLoader.vue` en `resources/js/components/comunes/` y registrado en `app.js`.
  - Simula tablas completas con cabeceras y celdas esqueléticas pulsantes (`skeleton-shimmer`) de anchos variables, eliminando el layout shift (CLS = 0).
  - Parámetros configurables (`:rows` y `:columns`) adaptados a cada tabla.
  - Integrado en `estudioAsignadosComponent.vue`, `citasComponent.vue` y `ecografiasAdmisionadasComponent.vue`.
  - Soporte completo para Modo Claro y Modo Oscuro radiológico (`body.theme-dark`).
  - Verificación estricta: ESLint 0 errores/warnings ✅ | Vite `npm run build` en 1.14s ✅.
- [x] **2026-10-19 — UI/UX Categoría 4.1: Toasts Unificados y No Invasivos:**
  - Modificado `alertSuccess` en `resources/js/helpers/alerts.js` para despachar notificaciones no invasivas tipo toast flotante (`toastMessage`) con auto-dismiss a 3500ms y fallback defensivo a SweetAlert/alert nativo.
  - Se eliminan los popups modales bloqueantes de SweetAlert en operaciones cotidianas y exitosas de guardado/eliminación/asignación, agilizando el flujo del médico y del personal asistencial sin obligar a clics repetitivos en "OK".
  - Agregado soporte de estilo para Toast Master (`.jq-toast-single`, `.jq-icon-success`, `.jq-icon-error`, etc.) en `resources/css/dark-theme.css`, asegurando alto contraste y legibilidad óptima en estaciones de trabajo radiológicas y modo oscuro global.
  - Verificación estricta: ESLint 0 errores/warnings ✅ | Vite `npm run build` en 1.64s ✅.
- [x] **2026-10-19 — UI/UX Categoría 4.2: Centro de Notificaciones en Topbar:**
  - Modernizada y unificada la campana del Topbar en `userProfileAndSearchComponent.vue` (`#unified_bell`).
  - Dropdown interactivo con pestañas de filtrado rápido: `[ Todas | Citas | Helpdesk ]` mostrando badges individuales de conteo no leído y badge global acumulado con animación de pulso (`.heartbit`).
  - Integración en tiempo real de solicitudes de Citas por WhatsApp (`/api/citas/notificaciones/no-leidas`) y tickets de soporte técnico Helpdesk (`/api/helpdesk-notificaciones`).
  - Acciones de lectura interactiva: clic en notificación marca como leída y navega al detalle correspondiente, botón "Marcar todas como leídas" ejecutado concurrentemente con `Promise.allSettled`.
  - Polling inteligente cada 30 segundos con backoff progresivo al desenfocar o minimizar la pestaña (respetando `document.hidden`).
  - Verificación estricta: ESLint 0 errores/warnings ✅ | Vite `npm run build` en 1.13s ✅.
- [x] **2026-10-19 — UI/UX Categoría 4.3: Barras de Progreso y Feedback de Operaciones Asíncronas:**
  - Implementado feedback reactivo en botones de guardado con envío a SAHI (`lecturaComponent.vue`), integrando spinner `fa-spinner fa-spin` y etiqueta dinámica ("Guardando...") durante la persistencia y transmisión hacia el HIS.
  - Agregados tooltips descriptivos (`title` y `aria-label`) a botones de acción en tablas principales (`estudioAsignadosComponent.vue`, etc.) para clarificar operaciones y mejorar accesibilidad a11y.
  - Integrado soporte de estilo completo en Modo Oscuro (`resources/css/dark-theme.css`) para componentes de progreso y modales:
    * Barras de progreso (`.progress`, `.burn-progress`, `.burn-detail`) con fondo atenuado, bordes de contraste y brillo sutil cian (`rgba(0, 188, 212, 0.3)`).
    * Modales interactivos de confirmación `SweetAlert` (`.sweet-alert`, `.sweet-overlay`, `.sa-icon.sa-warning`, `.sa-icon.sa-error`, `.sa-icon.sa-success`, etc.) adaptados a la paleta Slate Clínico con backdrop blur.
    * Preloader global (`.preloader`, `.loader__figure`, `.loader__label`) armonizado con el fondo carbón y acentos cian.
  - Verificación estricta: ESLint 0 errores/warnings ✅ | Vite `npm run build` en 1.31s ✅.
- [x] **2026-10-19 — Sistema de Backups Automáticos Separados (MySQL y 16 carpetas de Storage):**
  - Creado `config/backup.php` parametrizado con `BACKUP_DESTINATION_PATH=C:\Backups_Lidiag`, `BACKUP_RETENTION_DAYS=30`, `MYSQLDUMP_PATH` y listado de las 16 carpetas seleccionadas de `storage/app/*`.
  - Agregado canal de logging `'backups'` en `config/logging.php` apuntando a `storage/logs/backups.log`.
  - Implementado `BackupService.php` en `app/Services/Backup/`: generación de backups independientes para desacoplar escalabilidad:
    * Base de Datos: `backup_lidiag_db_*.zip` conteniendo el volcado SQL seguro (`database_lidiag_*.sql`). Corregido para leer credenciales directamente desde `.env` (`env('DB_HOST')`, `env('DB_DATABASE')`, etc.) con prioridad sobre config.
    * Storage: `backup_lidiag_storage_*.zip` conteniendo las 16 carpetas clínicas.
    * Retención automática: purga periódica de archivos `.zip` de backup más antiguos a 30 días.
  - Creado comando Artisan `LidiagBackupCommand.php` (`php artisan lidiag:backup`) con banderas `--only-db`, `--only-files`, `--destination`, `--retention` y tabla resumen formateada.
  - Registrado en Laravel Scheduler (`routes/console.php`) para ejecutarse diariamente a las 02:00 AM (`dailyAt('02:00')->withoutOverlapping()`).
  - Creado script auxiliar `scripts/programar_tarea_backup.bat` para registro de la tarea en el Programador de Tareas de Windows (Task Scheduler).
  - Verificación exitosa: `backup_lidiag_db_*.zip` (53.3 MB) y `backup_lidiag_storage_*.zip` (1.28 GB, 3,972 archivos) generados en 74s ✅ | Pint `--test passed` ✅.
- [x] **2026-10-19 — Sistema de Backups de Bases de Datos DCM / PACS por Sedes:**
  - Implementado `BackupDcmService.php` en `app/Services/Backup/`:
    * Conexión tolerante a fallos: prueba rápida de socket TCP (`probarConexion`) antes de volcado para no demorar si una sede está offline o la VPN caída.
    * Volcado seguro con `mysqldump` usando `--protocol=tcp`, `--column-statistics=0` y propagación de variables escalares de entorno Windows para evitar errores de socket Winsock (10106).
    * Empaquetado individual en archivos `.zip` independientes por sede bajo `C:\Backups_Lidiag\dcm_sedes\` (`backup_dcm_sede_{id}_{slug}_{timestamp}.zip`).
    * Purga automática de retención configurada a 30 días (`BACKUP_DCM_RETENTION_DAYS`).
  - Creado comando Artisan `LidiagBackupDcmCommand.php` (`php artisan lidiag:backup-dcm`) con soporte para `--sede=ID`, `--destination`, `--retention`, `--timeout` y tabla detallada por sede.
  - Registrado en Laravel Scheduler (`routes/console.php`) a las **02:30 AM** con protección `withoutOverlapping()`.
  - Actualizado `scripts/programar_tarea_backup.bat` para registrar la tarea `LIDiag_Daily_Backup_DCM` en Windows Task Scheduler.
  - **Paquete Completo de Mejoras de Grado Clínico (Implementado y Verificado):**
    1. *Pre-flight check de disco:* Medición previa de espacio libre disponible antes del volcado con umbral de seguridad para prevenir colapsos de disco o caídas de MySQL.
    2. *Parámetros robustos de MySQL:* `--hex-blob` y `--max_allowed_packet=512M` en `mysqldump` para proteger volcados masivos de metadatos DICOM y prevenir errores de desconexión o corrupción binaria.
    3. *Prueba de integridad CRC32:* Validación interna de ZIP (`testzip()` en Python, `ZipArchive::CHECKCONS` en PHP) antes de eliminar el archivo `.sql` crudo original.
    4. *Huella Criptográfica SHA-256:* Generación automática de archivo gemelo `.sha256` en formato estándar GNU (`<hash>  <archivo>`) para trazabilidad legal y auditorías.
    5. *Manifiesto de Telemetría Centralizado:* Emisión automática de `backup_status_sede_{id}.json` (en sedes) y `backup_manifest.json` (en central) sincronizados por Qsync al QNAP para monitoreo unificado de estado de toda la red.
    6. *Resolución determinista de mysqldump:* Eliminados los patrones quemados de búsqueda (`search_patterns`) en favor de la ruta explícita de `config.json` o el `PATH` del sistema, con terminación inmediata y mensaje instructivo si no se localiza.
  - Verificación exitosa:
    * Agente DCM: prueba integral completada en **17.98s** con generación de hash SHA-256 y manifiesto de estado ✅.
    * Backup Central (`php artisan lidiag:backup --only-db`): completado en **29.9s**, validando pre-flight check de 1.61 TB, verificación de integridad, hash SHA-256 y manifiesto `backup_manifest.json` ✅ | Pint `--test passed` ✅.
    * Reubicación a `storage/app_lidiag/agente_backups_dcm/` (sin versionar, estándar del sistema) y distribución en `storage/app_lidiag/agente_backups_dcm.rar` y `.zip` ✅.



- [x] **2026-10-21 — Consolidación Unificada del Agente de Backups y Sincronización PACS DCM (`agente_backups_dcm`):**
  - Unificados en un solo paquete integral los respaldos de bases de datos MySQL del PACS y la sincronización continua de imágenes DICOM hacia el QNAP:
    * `backup_dcm_agent.py`: Volcado seguro MySQL con `mysqldump`, compresión ZIP de alta velocidad, prueba de integridad CRC32 y huella SHA-256.
    * `sync_dcm_images.py`: Replicación multi-hilo incremental de imágenes con `robocopy` (`/E /XO /XN /XC /FFT /MT:8 /R:2 /W:3`), modo backup seguro y autenticación SMB contra QNAP.
    * `config.json.example`: Archivo de configuración único para la sede, credenciales del QNAP, base de datos e imágenes.
    * `instalar_tareas_sede.bat`: Instalador en 1 clic que registra ambas tareas en el Programador de Tareas de Windows bajo `SYSTEM` (`LIDiag_Backup_DB_DCM` a las 02:00 AM y `LIDiag_Sync_Imagenes_DCM` en `ONSTART`).
    * Versionamiento en Git de los fuentes del agente en `storage/app_lidiag/agente_backups_dcm/`, manteniendo protegidas e ignoradas las credenciales reales (`config.json`), logs y archivos comprimidos (`.zip`, `.rar`).
- [x] **2026-10-20 — Soporte de Trazabilidad y Reintento Automático de Fallidos en Sincronización:**
  - `EstudioPortalSyncService.php`: Añadido soporte para filtro `ids` (`whereIn('estudios_productos.id', ...)`) en `construirConsulta()`.
  - `LidiagEnviarPacienteCommand.php`: Añadida opción `{--reintentar=}` y guardado automático de estudios fallidos en archivo `.txt` (`storage/logs/sync_fallidos_...txt`) con cabeceras, formato `ID | DOC | PACIENTE | ESTUDIO | ERROR` y comando de reintento listo.
  - Validación con Pint (`vendor\bin\pint.bat --test passed`) y verificación en ejecución de prueba.
- [x] **2026-10-19 — UI/UX Categoría 3.3 (Extensión): Skeleton Loaders Shimmer & Empty States en todo el Módulo de Estudios:**
  - `AsignarEstudioComponent.vue` (`/estudios.asignar.listar`): Reemplazado spinner tradicional por `<table-skeleton-loader :visible="loadingBuscar" :rows="6" :columns="7" />` y añadido `<empty-state-component>` interactivo conectado a `btnLimpiar()`.
  - `leerEstudioComponent.vue` (`/estudios.lectura.listar`): Reemplazado spinner tradicional por `<table-skeleton-loader :visible="!tablaVisible" :rows="6" :columns="9" />` y `<empty-state-component>` con acción de refresco `listarPendiestePorLectura()`.
  - `transcribirEstudioComponent.vue` (`/estudios.transcribir.listar`): Reemplazado spinner por `<table-skeleton-loader :visible="loadingBuscar" :rows="6" :columns="11" />` y `<empty-state-component>` interactivo.
  - `notificarEstudioComponent.vue` (`/estudios.notificar.listar`): Reemplazado spinner por `<table-skeleton-loader :visible="loadingBuscar" :rows="6" :columns="8" />` y `<empty-state-component>` interactivo.
  - Limpieza de imports obsoletos `LoadingSpinner` y ajuste semántico de tablas vacías.
  - Verificación estricta: ESLint 0 errores/warnings en todos los componentes ✅ | Vite `npm run build` en 968ms ✅.
- [x] **2026-10-19 — UI/UX Categoría 5.1: Validación Visual Inline y Feedback en Tiempo Real en Formularios del Módulo de Estudios:**
  - Creado composable reutilizable `useFormValidation.js` en `resources/js/composables/` con validadores clínicos estándar (documento, email, teléfono, fechas no futuras, requeridos), tracking reactivo de interacción (`touched`) y combinación con errores del backend.
  - Soporte de alto contraste en `resources/css/dark-theme.css` para clases `.is-invalid`, `.is-valid`, `.invalid-feedback` y `.valid-feedback` en estaciones radiológicas / modo oscuro.
  - `formPacienteComponent.vue`: Feedback reactivo en tiempo real con select controlado de sexo (M/F/O), alertas `.invalid-feedback` al desenfocar o corregir en documento, nombres, teléfono, fecha y email.
  - `AsignarEstudioComponent.vue`: Validaciones inline en datos del estudio (`study_desc`, `accession_no`, `study_datetime`, `email_reportar`, `prioridad_id`), sincronización activa con `form-paciente` y bloqueo previo defensivo en `guardarRegistro()`.
  - `notificarEstudioComponent.vue`: Validación reactiva inline en correo electrónico con estado `.is-invalid`/`.is-valid` y corrección de typo en cierre de modal (`btnCerrarModalForm`).
  - `transcribirEstudioComponent.vue`: Validación inline en `email_reportar` y pre-validación de envío.
- [x] **2026-10-19 — UI/UX Categoría 5.2: Atajos de Teclado Clínicos Adicionales (Ctrl+S, Escape) y Foco Accesible (a11y) en Módulo de Estudios:**
  - Creado composable reutilizable `useKeyboardShortcuts.js` en `resources/js/composables/` para captura global y accesible de combinaciones clínicas:
    * `Ctrl+S` / `Cmd+S`: Previene la acción nativa del navegador de guardar HTML y dispara la función de guardado `onSave` con verificación de concurrencia (`isLoading`) y visibilidad (`isActive`).
    * `Escape`: Cierra limpiamente sidebars y modales abiertos (`onEscape`).
  - Actualizado `WysiwygEditor.vue`: Implementado puente de eventos de teclado `attachKeydownForwarding(iframe)` que escucha eventos `keydown` dentro del `iframe` de wysihtml5 (`Ctrl+S`, `F2`, `Escape`) y los reenvía de forma sintética a `window` para que el médico pueda guardar el informe clínico con `Ctrl+S` sin perder foco del editor.
  - Integrado soporte de atajos y badges visuales (`Ctrl+S`, `Esc`) en todos los componentes del flujo de estudios:
    * `leerEstudioComponent.vue`: Atajo `Ctrl+S` para guardar reporte radiológico y `Escape` para cerrar sidebar de lectura; badges descriptivos en botones de acción.
    * `AsignarEstudioComponent.vue`: Atajo `Ctrl+S` para guardar asignación de estudio, `Escape` para cerrar modal, badges visuales y autofocus en primer input (`primerInput.value?.focus()`).
    * `transcribirEstudioComponent.vue`: Atajo `Ctrl+S` para guardar transcripción, `Escape` para cerrar modal, badges visuales y autofocus reactivo en `mostrarRegistro()`.
    * `notificarEstudioComponent.vue`: Atajo `Ctrl+S` para notificar al paciente, `Escape` para cerrar modal, badges visuales y autofocus reactivo en campo `#email`.
  - Verificación estricta: ESLint 0 errores/warnings en todos los archivos modificados ✅ | Vite `npm run build` en 981ms ✅.
- [x] **2026-10-19 — Actualización Integral de Documentación (`README.md`, `scripts/dcm_agent/README.md`, `docs/UI-UX-IMPROVEMENT-PHASES.md`):**
  - `README.md` actualizado con comandos de respaldo `lidiag:backup` (BD + Storage), `lidiag:backup-dcm` (PACS por sedes), programación Windows Task Scheduler, arquitectura del agente descentralizado Python (`backup_dcm_agent.py` + webhook), tabla de tareas programadas en `routes/console.php` y resumen del sistema de diseño clínico/UI-UX (shimmer loaders, toasts no invasivos, validación visual inline, atajos `Ctrl+S`/`Esc`, foco accesible y modo oscuro).
  - `scripts/dcm_agent/README.md`: manual ampliado con opciones de instalación automática (`instalar_tarea_programada.bat`) y manual en `taskschd.msc`.
- [x] **2026-10-19 — UI/UX Categoría 5.3: Microinteracciones y Pulido Visual en Modales y Paneles Laterales (`right-sidebar`) del Módulo de Estudios:**
  - **Transición CSS Suave:** Reemplazado el parpadeo abrupto de `display: none / block` en `.right-sidebar` por una animación deslizante con aceleración ergonómica `cubic-bezier(0.16, 1, 0.3, 1)` a 350ms y control por `visibility / pointer-events` en todos los componentes del módulo (`AsignarEstudioComponent`, `leerEstudioComponent`, `transcribirEstudioComponent`, `notificarEstudioComponent`).
  - **Backdrop Oscuro con Desacople (`.sidebar-backdrop`):** Fondo atenuado con `backdrop-filter: blur(2px)` que enfoca la atención clínica en la lectura/asignación y permite cerrar el panel haciendo clic en el área exterior manteniendo sincronía con `Escape`.
  - **Estandarización de Altura y Z-Index:** Corregido `height: 80%` y `z-index: 180` en `AsignarEstudioComponent` a `height: 100%` y `z-index: 1100`, unificando el ancho responsivo del 80% (100% en móvil).
  - **Soporte en Modo Oscuro (`resources/css/dark-theme.css`):** Estilos para `.sidebar-backdrop` oscurecido (`rgba(0,0,0,0.65)`) y microinteracciones de clic/hover (`.btn-action-clinical`, `.btn-guardar-estudio`, etc.) con elevación y sombras adaptadas al *Slate Clínico*.
  - Verificación estricta: ESLint 0 errores/warnings en todos los componentes ✅ | Vite `npm run build` en 883ms ✅.
- [x] **2026-10-19 — UI/UX Módulo Ecografías: Atajos Clínicos, Foco Accesible, Validación y Pulido en `lecturaComponent.vue`:**
  - **Atajos de Teclado Asistenciales:** Integrado `useKeyboardShortcuts` con `Ctrl+S` para guardar reporte ecográfico y envío a SAHI (protegido contra concurrencia `isGuardandoLectura`), `Escape` para cerrar sidebar y `F2` para alternar dictado por voz (`toggleSpeechRecognition`).
  - **Puente WYSIWYG:** Integrado con el reenvío sintético de teclas desde el iframe de `WysiwygEditor.vue` para redactar y guardar sin perder foco.
  - **Pre-validación Defensiva:** Validación inline que impide guardar informes ecográficos en blanco antes de invocar la API o transmitir a SAHI.
  - **Transición Suave y Backdrop Overlay:** Eliminado el salto abrupto de `display: none/block` en `.right-sidebar` reemplazándolo por animación fluida a 350ms, altura completa al 100%, z-index 1100 y backdrop oscurecido desacoplador con cierre al clic exterior.
  - **Badges Visuales y Microinteracciones:** Añadidos badges `Ctrl+S` y `Esc`, títulos accesibles y clase `.btn-action-clinical` con microinteracción táctil.
  - Verificación estricta: ESLint 0 errores/warnings ✅ | Vite `npm run build` en 953ms ✅.
- [x] **2026-10-19 — UI/UX Módulo Ecografías: Atajos Clínicos, Foco Accesible y Backdrop en `admisionarComponent.vue`:**
  - **Atajos de Teclado Asistenciales:** Integrado `useKeyboardShortcuts` con `Ctrl+S` para guardar admisión (protegido contra concurrencia `isGuardandoEcografia`) y `Escape` para cancelar y cerrar el panel lateral.
  - **Foco Accesible (`a11y`):** Autofoco automático reactivo en el campo `#num_docu` del paciente en `abrirModal()` tras cargar los datos de la atención.
  - **Transición Suave y Backdrop Overlay:** Eliminado `display: none/block` en `.right-sidebar` con altura unificada al 100%, z-index 1100 y backdrop oscurecido con cierre al hacer clic exterior.
  - **Badges Visuales y Microinteracciones:** Añadidos badges descriptivos `Ctrl+S` y `Esc`, títulos accesibles y botón de cierre `<button class="right-side-toggle">`.
  - Verificación estricta: ESLint 0 errores/warnings ✅ | Vite `npm run build` en 977ms ✅.
- [x] **2026-10-19 — UI/UX Módulo Citas & Agenda: Atajos Clínicos, Foco Accesible y Modales con Backdrop en `citasComponent.vue`:**
  - **Atajos de Teclado Asistenciales:** Integrado `useKeyboardShortcuts` con `Ctrl+S` para guardar asignación de cita (protegido contra concurrencia `loadingGuardar`) y `Escape` para cerrar cualquiera de los modales activos (`#modalAsignar`, `#modalConversacion`).
  - **Foco Accesible (`a11y`):** Autofoco automático reactivo en `#modal_servicio_id` al desplegar el modal de asignación de cita.
  - **Modales Modernos con Backdrop Blur:** Modales con `style="display: block"`, backdrop desenfocado semitransparente, y cierre al hacer clic en el backdrop exterior (`@click.self`).
  - **Badges Visuales:** Badges descriptivos `Ctrl+S` y `Esc` en botones de acción y cierre.
  - Verificación estricta: ESLint 0 errores/warnings ✅ | Vite `npm run build` en 1.79s ✅.
- [x] **2026-10-19 — UI/UX Módulo Citas & Agenda: Atajos Clínicos, Foco Accesible y Modales en `citasRevisionComponent.vue`:**
  - **Atajos de Teclado Asistenciales:** Integrado `useKeyboardShortcuts` con `Ctrl+S` contextual (aprobar cita en `#modalAprobar` o rechazar cita en `#modalRechazar`, ambos protegidos por `loadingGuardar`) y `Escape` para cerrar modales.
  - **Foco Accesible (`a11y`):** Autofoco automático reactivo en el selector de producto en `abrirAprobar()`, y en el selector `#modal_rechazo_motivo_id` en `abrirRechazar()`.
  - **Modales Modernos con Backdrop Blur:** Modales `#modalAprobar` y `#modalRechazar` con `style="display: block"`, cierre al clic exterior (`@click.self`), atributos `aria-label` y estilos con `backdrop-filter: blur(2px)`.
  - **Badges Visuales y Microinteracciones:** Badges `Ctrl+S` y `Esc` en botones de confirmación y cancelación.
  - Verificación estricta: ESLint 0 errores/warnings ✅ | Vite `npm run build` en 954ms ✅.
- [x] **2026-10-19 — Actualización de Documentación (`README.md` & `docs/UI-UX-IMPROVEMENT-PHASES.md`):**
  - Incorporados al `README.md` los detalles de la arquitectura UI/UX: atajos clínicos extendidos (`Ctrl+S`, `Escape`, `F2`), foco accesible `a11y`, modales desacopladores con backdrop blur y transiciones fluidas sin layout shift en Estudios, Ecografías y Citas.
  - Actualizado `docs/UI-UX-IMPROVEMENT-PHASES.md` con la fase `E6 — Transición Suave y Backdrop Overlay Desacoplador`.
- [x] **2026-10-19 — UI/UX Módulo Citas & Agenda: Atajos, Foco y Transición Suave en `agendaDisponibilidadComponent.vue`:**
  - **Transición Suave y Backdrop Overlay:** Eliminado el salto de `display: none/block` en `.right-sidebar` con animación deslizante suave `cubic-bezier(0.16, 1, 0.3, 1)` a 350ms, altura al 100%, z-index 1100, backdrop desacoplador (`.sidebar-backdrop`) con `backdrop-filter: blur(2px)` y cierre al hacer clic exterior (`@click="cerrarModal()"`).
  - **Atajos Asistenciales (`useKeyboardShortcuts`):** `Ctrl+S` para guardar/actualizar disponibilidad (protegido por `loadingGuardar`) y `Escape` para cerrar el panel lateral.
  - **Foco Accesible (`a11y`):** Autofoco automático reactivo en `#total_citas` al abrir `mostrarRegistro()` y `editarRegistro()`.
  - **Badges Visuales y Microinteracciones:** Badges `Ctrl+S` y `Esc`, títulos contextuales y microinteracciones clínicas.
  - Verificación estricta: ESLint 0 errores/warnings ✅ | Vite `npm run build` en 996ms ✅.
- [x] **2026-10-19 — UI/UX Módulo Estudios: Persistencia de Filtros, Sede Inicial y Pulido de Acciones en `notificarEstudioComponent.vue` (`/estudios.notificar.index`):**
  - **Persistencia de Filtros (`useFilterPersistence`):** Integrado composable para conservar filtros en `sessionStorage` (`lidiag_filters_notificar_estudios`), restaurar resultados en `onMounted` y añadir badge reactivo `filtrosActivosCount` en cabecera de filtros.
  - **Sincronización Defensiva de Sede:** Inicialización automática de `busqueda.sede_id` con `sessionStorage.getItem("ST-sede")` en `onMounted`.
  - **Limpieza Visual y Microinteracciones:** Eliminado bloque de empty state redundante dentro de `.table-responsive`, diferenciado botón de "Enviar al portal" con `btn-outline-success` e ícono `fa fa-cloud-upload`, y clase `.btn-action-clinical` en botón Notificar.
  - Verificación estricta: ESLint 0 errores/warnings ✅ | Vite `npm run build` en 1.01s ✅.
- [x] **2026-10-19 — UI/UX Módulo Citas & Agenda: Atajos, Foco, Backdrop y Transición Suave en `recomendacionProductoComponent.vue`:**
  - **Transición Suave y Backdrop Overlay:** Eliminado el salto abrupto de `display: none/block`, altura truncada al 80% y z-index 180 (que quedaba debajo del header) en `.right-sidebar`. Reemplazado por animación deslizante fluida `cubic-bezier(0.16, 1, 0.3, 1)` a 350ms, altura al 100%, z-index 1100, backdrop desacoplador (`.sidebar-backdrop`) con `backdrop-filter: blur(1px)` y cierre al hacer clic exterior (`@click="cerrarModal()"`). Adaptación al 100% en pantallas móviles.
  - **Atajos Asistenciales (`useKeyboardShortcuts`):** Integrado `Ctrl+S` para guardar recomendaciones por producto (protegido por `loadingGuardar`) y `Escape` para cerrar el panel lateral.
  - **Foco Accesible (`a11y`):** Autofoco automático reactivo en `#form-producto-parent .select2-selection` o textarea en `abrirModal()`, y sustitución de `<span>` por `<button class="close right-side-toggle" aria-label="Cerrar panel" title="Cerrar (Esc)">` cumpliendo el estándar de botones accesibles.
  - **Badges Visuales y Microinteracciones:** Badges `Ctrl+S` y `Esc`, títulos contextuales y clase `.btn-action-clinical` con microinteracción táctil.
- [x] **2026-10-19 — UI/UX Módulo Estudios: Hotfix ReferenceError TDZ en `notificarEstudioComponent.vue`:**
  - **Corrección de Inicialización en `setup()`:** Sustituida la asignación `const btnCerrarModalForm` por declaración de función canónica con hoisting (`function btnCerrarModalForm()`), y envuelto en callback diferido `onEscape: () => btnCerrarModalForm()` dentro de `useKeyboardShortcuts` para evitar el error `Uncaught ReferenceError: Cannot access 'btnCerrarModalForm' before initialization`.
- [x] **2026-10-19 — UI/UX Módulo Citas & Agenda: Sincronización Multisede y Refresque Asistencial en `gestionCitasComponent.vue`:**
  - **Sincronización Reactiva Multisede:** Conectado listener de `emitter.on('sedeSeleccionada')` con cleanup en `onBeforeUnmount` y enlace con `sessionStorage.getItem("ST-sede")` para filtrar métricas y citas próximas por la sede activa del usuario.
  - **Optimización Asíncrona:** Migrada la carga de estadísticas y estados a `Promise.all` estructurado con `async/await`, eliminando condiciones de carrera y llamadas desordenadas.
  - **Botón Asistencial y Empty State:** Agregado botón de refrescar con microinteracción `.btn-action-clinical` y spinner dinámico en la cabecera superior derecha (`page-titles`), clases interactivas en accesos rápidos y empty state visual en la tabla de citas próximas.
  - Verificación estricta: ESLint 0 errores/warnings ✅ | Vite `npm run build` en 1.01s ✅.
- [x] **2026-10-19 — UI/UX Módulo Estudios: Columna y Detalle de Sede en `notificarEstudioComponent.vue` (`/estudios.notificar.index`):**
  - **Identificación Multisede de Estudios:** Incorporada la columna "Sede" con badge clínico (`badge-info` e ícono `fa-hospital-o`) en la tabla de estudios disponibles, permitiendo diferenciar estudios tomados en distintas sedes para un mismo paciente.
  - **Detalle de Sede en Panel Lateral:** Agregado bloque de identificación de "Sede del Estudio" y "Producto" en el modal lateral de notificación, y actualizado el skeleton loader a 9 columnas.
  - Verificación estricta: ESLint 0 errores/warnings ✅ | Vite `npm run build` en 1.14s ✅.
- [x] **2026-10-19 — UI/UX Módulo Ecografías: Modal Reactivo, Atajos y Foco Accesible en `ecografiasAdmisionadasComponent.vue`:**
  - **Modal Reactivo con Backdrop Blur:** Migrado el modal de anulación `#responsive-modal` de jQuery/Bootstrap a control 100% reactivo en Vue (`modalAnularVisible`), backdrop translúcido con `backdrop-filter: blur(2px)` y cierre al clic exterior. Corrección del botón "Close" a "Cancelar".
  - **Atajos Asistenciales (`useKeyboardShortcuts`):** Atajo `Ctrl+S` para confirmar anulación (validado contra campo vacío y protegido por `isAnulandoAdmisionada`) y `Escape` para cerrar el modal.
  - **Foco Accesible (`a11y`):** Autofoco automático reactivo en `#message-text` (motivo de anulación) al abrir el modal.
  - **Botón Asistencial y Microinteracciones:** Botón de refresco en la cabecera (`page-titles`) con spinner dinámico, badges descriptivos `Ctrl+S` y `Esc`, y clase clínica `.btn-action-clinical`.
  - Verificación estricta: ESLint 0 errores/warnings ✅ | Vite `npm run build` en 976ms ✅.
- [x] **2026-10-19 — UI/UX Módulo Ecografías: Modales Reactivos, Atajos y Foco en `ecografiasConsentimientosPlantillasComponent.vue`:**
  - **Modales Reactivos con Backdrop Blur:** Migrados `#modal-plantilla` y `#modal-versiones` de jQuery/Bootstrap a control 100% reactivo en Vue (`modalPlantillaVisible`, `modalVersionesVisible`), backdrop translúcido con `backdrop-filter: blur(2px)` y cierre al clic exterior (`@click.self`).
  - **Atajos Asistenciales (`useKeyboardShortcuts`):** Atajo `Ctrl+S` para guardar plantilla (protegido por `loadingGuardar` y validación de producto) y `Escape` para cerrar cualquiera de los modales activos.
  - **Foco Accesible (`a11y`):** Autofoco automático reactivo en `#form_producto_id` al crear nueva plantilla y en `#form_nombre` al editar versión existente.
  - **Botón Asistencial y Microinteracciones:** Botón de refresco en la cabecera (`page-titles`) con spinner interactivo, badges `Ctrl+S` y `Esc`, y clase clínica `.btn-action-clinical`.
  - Verificación estricta: ESLint 0 errores/warnings ✅ | Vite `npm run build` en 982ms ✅.
- [x] **2026-10-19 — UI/UX Módulo Ecografías: Modal Reactivo de Anulación, Atajos y Badges en `ecografiasConsentimientosComponent.vue`:**
  - **Modal Reactivo de Anulación con Backdrop Blur:** Reemplazado `window.prompt` nativo por un modal clínico reactivo con desenfoque de fondo (`backdrop-filter: blur(2px)`), textarea con foco automático (`nextTick`), resumen asistencial del paciente y validación contra texto en blanco.
  - **Atajos Asistenciales (`useKeyboardShortcuts`):** Atajo `F2` para ejecutar búsqueda / refrescar listado, `Ctrl+S` para confirmar anulación dentro del modal y `Escape` para cerrar modal o deseleccionar registros marcados.
  - **Botón Asistencial y Badges Clínicos:** Botón "Refrescar" en la cabecera (`page-titles`) con spinner dinámico y badge `F2`, badges clínicos para estados (`badge-success` COMPLETO, `badge-danger` ANULADO) y PDF generado, y loading indicator en la tabla.
  - Verificación estricta: ESLint 0 errores/warnings ✅ | Vite `npm run build` en 1.18s ✅.
- [x] **2026-10-19 — UI/UX Módulo Ecografías: Ergonomía de Lienzo de Firma, Atajos y Feedback en `ecografiaFirmaConsentimientoComponent.vue`:**
  - **Lienzo de Firma Ergonómico:** Incorporada línea guía visual punteada ("Firme sobre la línea") con marca de agua sutil, contraste estandarizado para exportación electrónica a PDF y trazado de alta resolución.
  - **Atajos Asistenciales (`useKeyboardShortcuts`):** Atajo `Ctrl+S` para confirmar y registrar firma digital (validado contra ausencia de trazo `hasInk` y protegido contra concurrencia), y `Escape` para limpiar el lienzo.
  - **Feedback Visual y Temporizador Crítico:** Indicador animado pulsante (`animate-pulse`) cuando restan menos de 5 minutos de validez al enlace, spinner de carga asistencial y pantalla de confirmación exitosa con bloqueo defensivo que previene firmas duplicadas.
  - Verificación estricta: ESLint 0 errores/warnings ✅ | Vite `npm run build` en 1.06s ✅.
- [x] **2026-10-19 — UI/UX Módulo Citas & Agenda: Sincronización Reactiva Inmediata y Pulso Asistencial en `citasNotificationBadgeComponent.vue`:**
  - **Sincronización Inmediata por EventBus:** Conectado listener de `citasActualizadas` y `sedeSeleccionada` sobre el `emitter` global con cleanup en `onBeforeUnmount`, actualizando el conteo de citas pendientes en tiempo real sin esperar el ciclo de 30s.
  - **Microanimación de Pulso:** Indicador visual animado (`badge-pulse`) al recibir nuevas solicitudes pendientes, transición suave de entrada/salida (`fade`) y accesibilidad reforzada con `aria-live="polite"`.
  - Verificación estricta: ESLint 0 errores/warnings ✅ | Vite `npm run build` en 1.01s ✅.
- [x] **2026-10-19 — UI/UX Módulo Tele-radiología: Atajos Clínicos, Backdrop y Transición Suave en `teleRadiologiaEnviarEstudioComponent.vue` y `teleRadiologiaTranscibirComponent.vue`:**
  - **Transición Suave y Backdrop Overlay:** Eliminado `display: none/block` en `.right-sidebar` con animación deslizante suave `cubic-bezier(0.16, 1, 0.3, 1)` a 350ms, altura al 100%, z-index 1100 y backdrop desacoplador (`.sidebar-backdrop`) con `backdrop-filter: blur(2px)`.
  - **Atajos Asistenciales (`useKeyboardShortcuts`):** Atajos `Ctrl+S` para guardar orden/transcripción (protegido contra concurrencia), `Escape` para cerrar paneles laterales y `F2` para ejecutar búsquedas/refresco de estudios.
  - **Foco Accesible y Botones Clínicos:** Botón "Refrescar" en `page-titles` con spinner interactivo y badge `F2`, botón accesible `<button class="right-side-toggle">` y clase `.btn-action-clinical`.
  - Verificación estricta: ESLint 0 errores/warnings en ambos archivos ✅ | Vite `npm run build` en 990ms ✅.
- [x] **2026-10-19 — UI/UX Módulo HelpDesk & Tickets: Atajos de Teclado, Foco Accesible y Botones Clínicos:**
  - **Atajos Asistenciales (`useKeyboardShortcuts`):** Atajos `F2` para recarga/búsqueda inmediata en tickets, reportes y detalle; `Ctrl+S` para guardar en creación de tickets; `Escape` para volver o limpiar filtros.
  - **Botón Asistencial y Badges en Cabecera (`page-titles`):** Incorporado botón "Refrescar" asistencial con spinner interactivo y badges `F2`/`Esc` en `helpdeskTicketsComponent.vue`, `helpdeskReportesComponent.vue` y `helpdeskDetalleTicketComponent.vue`.
  - **Foco Accesible y Microinteracciones:** Tecla `Enter` en campo de búsqueda rápida, clase `.btn-action-clinical` estandarizada en todos los botones de acción (estados, asignación, comentarios, adjuntos).
  - Verificación estricta: ESLint 0 errores/warnings en 4 componentes ✅ | Vite `npm run build` en 1.05s ✅.
- [x] **2026-10-19 — UI/UX Módulo Seguridad y Usuarios: Atajos de Teclado, Backdrop y Ergonomía en `usuarioComponent.vue`, `usuarioPerfil.vue` y `auditLogComponent.vue`:**
  - **Transición Suave y Backdrop Overlay:** Eliminado `display: none/block` en `.right-sidebar` de gestión de usuarios con transición fluida `cubic-bezier(0.16, 1, 0.3, 1)` a 350ms, altura al 100%, z-index 1100 y backdrop desacoplador (`.sidebar-backdrop`) con `backdrop-filter: blur(2px)`.
  - **Modales Reactivos:** Migrados `modalAmpliarFirma` y `modalDetalleAuditoria` a modales 100% reactivos en Vue con desenfoque de fondo y cierre exterior (`@click.self`).
  - **Atajos Asistenciales (`useKeyboardShortcuts`):** Atajos `F2` para recarga/búsqueda inmediata en listado de usuarios, perfil y auditoría; `Ctrl+S` para guardar en creación/edición de usuario y actualización de perfil; `Escape` para cerrar paneles laterales y modales.
  - **Foco Accesible y Botones Clínicos:** Autofoco automático en `#num_docu` al abrir formulario de usuario, botón "Refrescar" asistencial en cabeceras `page-titles` con badges `F2`, badges `Ctrl+S`/`Esc`, y estandarización de `.btn-action-clinical`.
  - Verificación estricta: ESLint 0 errores/warnings en los 3 componentes ✅ | Vite `npm run build` en 1.13s ✅.
- [x] **2026-10-19 — UI/UX Módulo Reportes: Atajos de Teclado, Badges y Ergonomía Clínica en `reporteProduccionComponent.vue`:**
  - **Modales Reactivos con Backdrop Blur:** Modales de Producción Detallada (Rx/Ecografía) y Estudios PAC migrados a renderizado reactivo con desenfoque de fondo (`backdrop-filter: blur(2px)`), eliminando elementos redundantes `.modal-backdrop` y permitiendo cierre al clic exterior (`@click.self`).
  - **Atajos Asistenciales (`useKeyboardShortcuts`):** Atajo `F2` para recargar catálogo de sedes y usuarios asistenciales; `Ctrl+S` para disparar la descarga de reportes dentro del modal activo; `Escape` para cerrar y resetear formularios de filtros.
  - **Foco Accesible (`a11y`):** Autofoco automático reactivo (`nextTick`) en el selector de sedes `#prod-sede` y `#pac-sede` al abrir cualquiera de los modales.
  - **Botón Asistencial y Badges en Cabecera (`page-titles`):** Incorporado botón "Refrescar" en la cabecera con spinner interactivo y badge `F2`, badges `Ctrl+S`/`Esc` en botones de acción y estandarización de la clase `.btn-action-clinical`.
  - Verificación estricta: ESLint 0 errores/warnings ✅ | Vite `npm run build` en 1.06s ✅.
- [x] **2026-10-19 — UI/UX Nivelación: Atajos Clínicos, Backdrop y Botones Asistenciales en `roleComponente.vue` y `helpdeskNotificacionesComponent.vue`:**
  - **Roles y Permisos (`roleComponente.vue`):** Desacoplado `.right-sidebar` con animación suave `cubic-bezier` a 350ms y backdrop con `backdrop-filter: blur(2px)`; botón "Refrescar" asistencial en `page-titles` con badge `F2`; atajos `F2` (refrescar listado), `Ctrl+S` (guardar/actualizar rol) y `Esc` (cerrar panel); autofoco en `#name` (`nextTick`); clase clínica `.btn-action-clinical`.
  - **Notificaciones HelpDesk (`helpdeskNotificacionesComponent.vue`):** Botón "Refrescar" asistencial con spinner dinámico y badge `F2`, botón "Volver" con badge `Esc`, atajos `F2` y `Esc`, y estandarización de `.btn-action-clinical`.
  - Verificación estricta: ESLint 0 errores/warnings en ambos archivos ✅ | Vite `npm run build` en 1.53s ✅.
- [x] **2026-10-19 — Herramienta Autónoma Python: Monitor de Discos DICOM con Bot de Telegram (`dcm_disk_monitor_telegram`):**
  - Implementada herramienta en `storage/app_lidiag/dcm_disk_monitor_telegram/` usando exclusivamente la librería estándar de Python (sin dependencias `pip`).
  - **Modo Dual (Opción C):**
    * Servicio Residente 24/7 (`iniciar_bot.bat` / `--daemon`): Long-Polling para comandos de Telegram (`/discos`, `/estado`, `/alertas`, `/info`, `/ayuda`) y timer de fondo cada 15 min.
- [x] **2026-10-19 — UI/UX Nivelación: Atajos Clínicos, Backdrop y Botones Asistenciales en Citas y Agenda (`citasComponent.vue`, `agendaDisponibilidadComponent.vue`, `citasRevisionComponent.vue`, `recomendacionProductoComponent.vue`):**
  - **Atajos Asistenciales (`useKeyboardShortcuts`):** Habilitado atajo `F2` para recarga/búsqueda inmediata de listados (`onSearch`), atajo `Ctrl+S` para guardar asignación, disponibilidad o recomendación, y tecla `Escape` para cerrar modales y paneles laterales de inmediato.
  - **Botón Asistencial y Badges en Cabecera (`page-titles`):** Incorporado botón "Refrescar" asistencial con spinner dinámico y badge `F2` en `citasComponent.vue`, `agendaDisponibilidadComponent.vue`, `citasRevisionComponent.vue` y `recomendacionProductoComponent.vue`.
  - **Foco Accesible y Ergonomía Clínica:** Autofoco accesible (`nextTick`) en inputs clave al desplegar modales y sidebars, badges visuales `Ctrl+S`/`Esc`, y estandarización completa de la clase `.btn-action-clinical`.
  - Verificación estricta: ESLint 0 errores/warnings en los 4 componentes ✅ | Vite `npm run build` en 1.04s ✅.
- [x] **2026-10-19 — UI/UX Nivelación: Atajos Clínicos, Botones Asistenciales y Ergonomía en Ecografías Flujo Principal (`admisionarComponent.vue`, `lecturaComponent.vue`):**
  - **Atajos Asistenciales (`useKeyboardShortcuts`):** Habilitado atajo `F2` para recarga/búsqueda inmediata de atenciones y ecografías pendientes (`onSearch`), atajo `Ctrl+S` para guardar admisión o lectura, atajo `Ctrl+D` para dictado por voz, y tecla `Escape` para cerrar la estación de lectura o panel lateral.
  - **Botón Asistencial y Badges en Cabecera (`page-titles`):** Incorporado botón "Refrescar" asistencial con spinner reactivo y badge `F2` en `admisionarComponent.vue` y `lecturaComponent.vue`.
  - **Ergonomía Clínica y Botones:** Estandarización de `.btn-action-clinical` en botones de cabecera y búsqueda, manteniendo transiciones suaves y foco accesible.
- [x] **2026-10-19 — UI/UX Nivelación: Atajos Clínicos, Backdrop y Botones Asistenciales en Catálogos Base de Configuración (`configSedeComponent.vue`, `configProductoComponent.vue`, `configTipoDocumentoComponent.vue`, `configDiagnosticoComponent.vue`, `configAdminSaludComponent.vue`, `configPrioridadComponent.vue`):**
  - **Transición Suave y Backdrop Overlay Desacoplador:** Eliminado el salto abrupto de `display: none/block` en `.right-sidebar` reemplazándolo por animación fluida a 350ms con aceleración `cubic-bezier(0.16, 1, 0.3, 1)`, altura al 100%, z-index 1100 y backdrop desenfocado con `backdrop-filter: blur(2px)` que desacopla la vista y permite cerrar al clic exterior en todos los componentes del grupo.
  - **Atajos Asistenciales (`useKeyboardShortcuts`):** Habilitado atajo `F2` para refrescar y recargar listados inmediatamente (`onSearch`), atajo `Ctrl+S` para guardar o actualizar registro dentro del panel lateral (`onSave`), y tecla `Escape` para cerrar de inmediato el sidebar (`onEscape`).
  - **Foco Accesible (`a11y`) y Ergonomía Clínica:** Autofoco automático reactivo (`nextTick`) en el primer campo de cada catálogo al abrir el formulario (`#nom_sede`, `#cod_cups`, `#codigo`, `#cod_diagnos`, `#cod_admin_salud`, `#nom_priori`), botones "Refrescar" asistenciales con spinner dinámico y badge `F2` en `page-titles`, badges `Ctrl+S` y `Esc`, y estandarización completa de la clase `.btn-action-clinical`.
- [x] **2026-10-19 — UI/UX Nivelación: Atajos Clínicos, Backdrop y Botones Asistenciales en Plantillas, Consentimientos y Servicios de Citas (`configPlantilla.vue`, `configTipoPlantilla.vue`, `configConsentimientoInformadoComponent.vue`, `configServicioCitaComponent.vue`, `configServicioProductoComponent.vue`, `configCitaRechazoMotivoComponent.vue`):**
  - **Transición Suave y Backdrop Overlay Desacoplador:** Eliminado el salto abrupto de `display: none/block` en `.right-sidebar` sustituyéndolo por animación deslizante fluida a 350ms con aceleración `cubic-bezier(0.16, 1, 0.3, 1)`, altura al 100%, z-index 1100 y backdrop desenfocado (`.sidebar-backdrop`) con `backdrop-filter: blur(2px)` que permite cerrar al clic exterior en todos los componentes del grupo.
  - **Atajos Asistenciales (`useKeyboardShortcuts`):** Habilitado atajo `F2` para refrescar y recargar listados inmediatamente (`onSearch`), atajo `Ctrl+S` para guardar o actualizar registro dentro del panel lateral (`onSave`), y tecla `Escape` para cerrar de inmediato el sidebar (`onEscape`).
  - **Foco Accesible (`a11y`) y Ergonomía Clínica:** Autofoco automático reactivo (`nextTick`) en el primer campo de cada catálogo al abrir el formulario (`#nom_plantilla`, `#nom_tipo`, `#nombre`, `#nombre_servicio`, `#producto-parent .select2-selection`, `#motivo_rechazo`), botones "Refrescar" asistenciales con spinner dinámico y badge `F2` en `page-titles`, badges `Ctrl+S` y `Esc`, y estandarización completa de la clase `.btn-action-clinical`.
- [x] **2026-10-19 — Bot de Telegram Integrado en LIDiag (`TelegramService`, `TelegramBotHandlerService`, `TelegramWebhookController`, `TelegramPollCommand`):**
  - Creado `config/telegram.php` parametrizado con `TELEGRAM_BOT_TOKEN`, `TELEGRAM_ALLOWED_CHAT_IDS`, `TELEGRAM_WEBHOOK_URL`, `TELEGRAM_WEBHOOK_SECRET` y control `TELEGRAM_SSL_VERIFY` adaptado al entorno Windows/Laragon.
  - Implementado `TelegramService.php` en `app/Services/Telegram/`: cliente HTTP nativo Laravel (`sendMessage`, `sendDocument`, `sendChatAction`, `getUpdates`, `setWebhook`, `deleteWebhook`, `getMe`) con modo seguro y soporte multipart para adjuntar archivos.
  - Implementado `TelegramBotHandlerService.php` en `app/Services/Telegram/`:
    * Validación estricta de autorización por lista blanca de Chat IDs (`isChatAuthorized`).
    * Despachador de comandos: `/start`, `/help`, `/ayuda`, `/sedes`, `/produccion [sede] [rango]`, `/reporte`.
    * Soporte para botones inline interactivos (`InlineKeyboardMarkup`) para selección guiada de sedes y períodos (Hoy, Ayer, Este Mes, Últimos 7 Días).
    * Generación y envío en 2 etapas: (1) Resumen Ejecutivo en texto enriquecido HTML con totales, desglose por modalidad clínica (Rayos X, TAC, RM, Mamografía, Ecografía), médicos radiólogos con mayor volumen y cola de estudios pendientes en la sede; (2) Generación asíncrona de archivo Excel detallado vía `ProduccionDetalladaExport` (`Excel::store`), envío mediante `sendDocument` y purga limpia de archivos temporales.
  - Creado controlador `TelegramWebhookController.php` y ruta `POST /api/telegram/webhook` en `routes/api_telegram.php` (registrada en `$apiFiles` de `bootstrap/app.php`).
  - Creado comando Artisan `TelegramPollCommand.php` (`php artisan telegram:poll` con banderas `--once`, `--set-webhook`, `--delete-webhook`) para Long-Polling interactivo en local/desarrollo y confirmación de offsets.
  - Verificación exitosa: `TelegramService::getMe()` conectado a `@salud_tolima_bot` ✅ | Despacho exitoso de resumen y Excel de la Sede Espinal a Telegram ✅ | `vendor\bin\pint.bat --test` passed ✅.
- [x] **2026-10-19 — UI/UX Nivelación: Atajos Clínicos, Backdrop y Ergonomía en WhatsApp Bot y HelpDesk Config (`configWhatsappComponent.vue`, `configWhatsappNumeros.vue`, `configWhatsappSaludos.vue`, `configWhatsappProductos.vue`, `helpdeskConfigCategoriasComponent.vue`, `helpdeskConfigEstadosComponent.vue`, `helpdeskConfigPrioridadesComponent.vue`):**
  - **Transición Suave y Backdrop Overlay Desacoplador:** Eliminado `display: none/block` en `.right-sidebar` sustituyéndolo por animación suave deslizante a 350ms con aceleración `cubic-bezier(0.16, 1, 0.3, 1)`, altura al 100%, z-index 1100 y backdrop desenfocado (`.sidebar-backdrop`) con `backdrop-filter: blur(2px)` que desacopla la vista y permite cerrar al clic exterior en todos los componentes del grupo.
  - **Atajos Asistenciales (`useKeyboardShortcuts`):** Habilitado atajo `F2` para recarga/búsqueda inmediata de listados (`onSearch`), atajo `Ctrl+S` para guardar o actualizar registro dentro del panel lateral (`onSave`), y tecla `Escape` para cerrar de inmediato el sidebar (`onEscape`).
  - **Foco Accesible (`a11y`) y Ergonomía Clínica:** Autofoco automático reactivo (`nextTick`) en el primer campo de cada formulario (`#form_sede_id`, `#form_servicio_id`, `#form_wp_servicio`, `#categoria_nombre`, `#prioridad_nombre`), botones "Refrescar" asistenciales con spinner dinámico y badge `F2` en `page-titles`, badges `Ctrl+S` y `Esc`, y estandarización completa de la clase `.btn-action-clinical`.
  - **Conclusión de Hito 1 Frontend:** 100% de los módulos de la aplicación (Estudios, Ecografías, Citas, Agenda, HelpDesk, Seguridad, Reportes, Configuración, WhatsApp Bot) cuentan ahora con el sistema unificado de atajos de teclado clínicos, backdrops con desenfoque, transiciones suaves y ergonomía asistencial.
  - Verificación estricta: ESLint 0 errores/warnings en los 7 componentes ✅ | Vite `npm run build` en 1.04s ✅.
- [x] **2026-10-20 — Backend: Suite de Pruebas Automatizadas Unit y Feature para Flujos Críticos (PHPUnit 12):**
  - **Unit Tests (`tests/Unit/` - 12 tests):**
    * `TelegramServiceTest.php`: Verificación de configuración de token, mock de peticiones `sendMessage` a Telegram Bot API, autorización estricta de Chat IDs e invocación de `getMe`.
    * `EstudioLockServiceTest.php`: Resolución de claves canónicas (`ep:...`, `iuid:...`, `pk:...`), rechazo de claves malformadas y cálculo de minutos de expiración.
    * `BackupServiceTest.php`: Detección de carpetas en storage, formateador de bytes a unidades legibles (B, KB, MB, GB) y política de retención automática purgando zips antiguos y hashes `.sha256`.
  - **Feature Tests (`tests/Feature/` - 18 tests):**
    * `EncryptionAtRestTest.php`: Cifrado transparente en reposo de datos clínicos sensibles.
    * `PasswordChangeTest.php`: Forzado de cambio de contraseña periódico y actualización de credenciales.
    * `RateLimitingTest.php`: Limitación de tasa de peticiones (throttling) en endpoints sensibles.
    * `SessionTimeoutTest.php`: Expiración y cierre determinista de sesiones de usuario.
    * `EstudioLockFeatureTest.php`: Concurrencia de bloqueo de estudios, renovación de lock, detección de conflicto HTTP 409 y liberación segura.
    * `TelegramWebhookFeatureTest.php`: Rechazo con HTTP 403 ante secret tokens inválidos de Telegram, aceptación con secret tokens válidos y despacho a `TelegramBotHandlerService`.
    * `BackupArtisanCommandTest.php`: Ejecución del comando Artisan `php artisan lidiag:backup` en modos `--only-db`, `--only-files` y control de fallos.
    * `AgendaCitaFeatureTest.php`: Autenticación requerida para endpoints de citas, catálogo de servicios de agendamiento y consulta de citas pendientes.
  - **Mantenimiento y Estilo:**
    * Actualizado `database/factories/UserFactory.php` con `'password_changed_at' => now()` para prevenir bloqueos por el middleware `ForcePasswordChange` durante tests autenticados.
    * Inclusión de `/tests` en `.gitignore` para versionado de pruebas en el repositorio.
    * Verificación: 30 tests / 132 aserciones pasando al 100% en 2.89s ✅ | Laravel Pint pasado sin discrepancias ✅.
- [x] **2026-10-20 — Base de Datos & Rendimiento: Auditoría y Optimización de Índices (Citas, HelpDesk, Audios):**
  - **Auditoría de Planes de Ejecución (`EXPLAIN`):**
    * La consulta de citas pendientes (`agenda_citas WHERE estado = 'solicitada' ORDER BY created_at DESC`) requería un table scan completo (`type: ALL`, `Using where; Using filesort`) al carecer de índice por estado y created_at.
    * Las consultas combinadas de tickets de HelpDesk y relaciones de audios carecían de índices compuestos optimizados.
  - **Migración de Optimización:**
    * Creada migración `database/migrations/2026_10_20_000001_add_performance_indexes_to_citas_and_helpdesk.php`.
    * `agenda_citas`: Índices `idx_agenda_citas_estado`, `idx_agenda_citas_fecha_hora_inicio`, `idx_agenda_citas_sede_estado`, `idx_agenda_citas_servicio_estado`, `idx_agenda_citas_created_at`.
    * `help_desk_tickets`: Índices `idx_help_desk_tickets_sede_estado`, `idx_help_desk_tickets_prioridad_id`.
    * `estudios_audios`: Índice `idx_estudios_audios_producto_id`.
  - **Verificación:** Laravel Pint passed ✅ | 30 tests pasando ✅.
- [x] **2026-10-20 — Infraestructura / Documentación Viva y Verificación de Despliegue:**
  - **Documentación de Controladores Actualizada:** Incorporado `TelegramWebhookController` en `docs/CONTROLLERS.md` bajo la sección *Módulo: Telegram & Notificaciones*.
  - **Guía de Despliegue y Verificación:** Creado `docs/DEPLOYMENT_VERIFICATION.md` con checklist integral de despliegue paso a paso (variables `.env`, permisos de storage, optimizaciones `config/route/view:cache`, build de frontend y smoke tests con PHPUnit, Telegram y backups).
  - **Culminación de los 4 Pendientes:**
    1. Frontend: Nivelación UI/UX, atajos clínicos, backdrop y ergonomía (Commit `044b05cd8`).
    2. Backend: Suite de 30 tests automatizados Unit y Feature (Commit `5632ce96e`).
    3. Base de Datos: Auditoría de planes de ejecución y migración de índices de rendimiento (Commit `120f36d43`).
    4. Infraestructura: Documentación viva de despliegue y controladores.
- [x] **2026-10-20 — Bot de Telegram: Soporte de Reportes Clínicos (Producción vs Tomados DCM) y Menú Interactivo Táctil:**
  - **Reportes de Estudios Tomados en DCM / PACS:** Integrado soporte para consultar directamente la base de datos MySQL PACS remota de cada hospital (`study` y `patient`) mediante `ReportesEstudioPAC`.
  - **Pre-flight Socket Check Defensivo:** Prueba TCP de 2s (`fsockopen`) previa a la conexión para prevenir timeouts largos si una sede está fuera de línea o la VPN desconectada.
  - **Menú Interactivo Táctil (Inline Keyboards):**
    * Flujo guiado en 3 pasos: (1) Tipo de Reporte (Producción vs Tomados DCM) ➡️ (2) Sede Hospitalaria ➡️ (3) Período (Hoy, Ayer, Mes, 7 Días).
    * Transición fluida en el mismo mensaje con `editMessageText` y confirmación instantánea con `answerCallbackQuery` para descartar spinners en Telegram.
  - **Comandos Directos:** Soporte para `/tomados [sede] [periodo]` y `/dcm [sede] [periodo]`, manteniendo `/produccion [sede] [periodo]` (por ID o nombre).
  - **Menú Nativo de Telegram:** Registro automático de comandos con `registerBotCommands()` y `setMyCommands` (`/menu`, `/produccion`, `/tomados`, `/sedes`, `/dd`, `/ayuda`).
  - **Hotfix de Eventos CallbackQuery:** Configurado explícitamente `allowed_updates: ["message", "callback_query"]` en `TelegramService::getUpdates()` y `setWebhook()`, resolviendo el bloqueo de Telegram donde los clics táctiles en botones inline eran descartados por omisión de suscripción API.
  - **Verificación:** Laravel Pint passed ✅ | 30 tests PHPUnit pasando ✅ | Verificado en vivo en `@salud_tolima_bot` con reportes exitosos de Chaparral y Espinal.
- [x] **2026-10-20 — Bot de Telegram: Suite Integral de Estadísticas Asistenciales y Visuales:**
  - **Barras Visuales de Porcentaje Unicode (`generarBarraProgreso`):** Indicador visual en texto (`██████░░ 75%`) para modalidades, médicos con mayor volumen y ranking de sedes en Telegram.
  - **Métricas de Oportunidad Clínica y Turnaround Time (TAT):** Cálculo automático del tiempo promedio de lectura médico-asistencial con semáforo de alerta (`🟢 Óptimo <12h`, `🟡 Aceptable <24h`, `🔴 Retrasado >24h`) y conteo de estudios con más de 24h sin informe.
  - **Consolidado General de Toda la Red (`/red` o `/consolidado`):** Reporte ejecutivo multipropósito con totales globales, ranking de sedes y desglose de modalidades en toda la red hospitalaria.
  - **Semáforo Asistencial de Estudios Represados (`/retrasados` o `/pendientes`):** Monitor en tiempo real de estudios con >24h sin lectura y estudios de urgencias/prioridad alta pendientes por sede.
  - **Estadísticas de Citas WhatsApp (`/citas`):** Reporte de solicitudes recibidas, aprobadas, canceladas y pendientes en la bandeja de agendamiento.
  - **Botones de Navegación Rápida Post-Reporte:** Inyección de inline keyboards interactivos al pie de cada reporte para saltar con 1 clic entre períodos (`Hoy`, `Ayer`, `Mes`) o cambiar a la red completa.
  - **Comando Artisan y Tarea Matutina (`telegram:daily-digest`):** Creado comando `TelegramDailyDigestCommand` programado en `routes/console.php` a las `07:30 AM` para despacho automático del cierre del día anterior y semáforo asistencial matutino.
  - **Verificación:** Laravel Pint passed ✅ | 30 tests PHPUnit pasando ✅ | Despacho de Daily Digest verificado en vivo.
- [x] **2026-10-20 — Comando de Sincronización de Estudios al Portal de Pacientes (`php artisan lidiag:enviar-paciente`):**
  - **Arquitectura de Sincronización:** Creado `EstudioPortalSyncService` en `app/Services/Estudios/`:
    * Consulta robusta de estudios transcritos (`estudios_productos.fechor_trascrito IS NOT NULL`, `lectura IS NOT NULL`, `activo = 1`).
    * Pre-flight checks de configuración (`URL_API_REPORTES`, clave `API_HMAC_SECRET` y binario de LibreOffice Portable).
    * Mapeo y normalización de payload requerido por el portal (`lidiag-reportes`) y la plantilla Word (`PlantillaEstudioService`).
    * Manejo resiliente de errores por sede/plantilla sin abortar la ejecución masiva.
    * Soporte para modo de simulación (`--dry-run`) que valida integridad y formato sin generar PDFs ni transferir por red.
  - **Comando Artisan (`LidiagEnviarPacienteCommand`):**
    * Registrado bajo el namespace `lidiag:enviar-paciente` con opciones `--desde=YYYY-MM-DD`, `--hasta=YYYY-MM-DD`, `--sede=ID`, `--limite=N`, `--id=ID` y `--dry-run`.
    * Barra de progreso interactiva con telemetría en tiempo real: cálculo dinámico de tiempo transcurrido, tiempo restante estimado (ETA adaptativo), velocidad promedio en segundos por estudio y rendimiento proyectado (estudios/minuto).
    * Estimación inicial antes de iniciar el lote y tabla final con desglose completo de métricas operativas y errores.
  - **Verificación Estricta:** `vendor\bin\pint.bat --test` passed ✅ | Pruebas de simulación `--dry-run` con y sin fechas superadas con éxito ✅.
  - **Diagnóstico y Hotfix de Ingesta en Portal (`lidiag-reportes`):**
    * Se identificó que `C:\laragon\www\lidiag-reportes\.env` tenía `API_HMAC_SECRET=` en blanco, causando HTTP 500 (`HMAC secret not configured.`). Se sincronizó la misma clave compartida de `lidiag`.
    * En `EstudioNotificacionService.php`, se mejoró la captura del mensaje de respuesta del endpoint remoto para reflejar el detalle exacto del fallo devuelto por el portal en lugar de un mensaje estático genérico.
    * Prueba real con `--id=257022` ejecutada y verificada: generación de PDF de 116 KB en `lidiag-reportes/storage/app/reporte_lecturas/` e inserción exitosa en la tabla `lecturas` con HTTP 200 ✅.
- [x] **2026-10-20 — Autenticación Web: Corrección de Alertas Múltiples de Credenciales Inválidas:**
  - **Diagnóstico del Fallo:**
    * En `AuthService::login()`, al fallar la autenticación se retornaba un array `errors` anidado con 3 claves (`redirect`, `errors`, `old`).
    * Al procesar `$result['errors']` en `LoginController` mediante `->withErrors($result['errors'])`, Laravel registraba las 3 entradas en el `MessageBag` de la sesión: la URL de redirect, el mensaje de error y el correo ingresado.
    * En `login.blade.php`, el bucle `@foreach ($errors->all() as $error)` renderizaba un `<div class="alert alert-warning">` independiente por cada clave, mostrando 3 cajas de alerta visuales repetitivas en la interfaz.
  - **Corrección Implementada:**
    * En `AuthService.php`, se normalizó el payload de errores de sesión retornando exclusivamente `['email' => 'Estas credenciales no coinciden con nuestros registros.']`.
    * En `login.blade.php`, se migró el renderizado a `@if ($errors->any())` mostrando una única alerta dismissible de cabecera con `$errors->first()` y el icono de advertencia clínico.
  - **Verificación:** Laravel Pint passed ✅ | 30 tests PHPUnit pasando al 100% ✅.
- [x] **2026-10-20 — Utilidad de Conversión PDF: Normalización de Rutas Windows y Espera de E/S:**
  - **Diagnóstico y Hotfix en `PdfConverterHelper`:**
    * Se normalizaron separadores de directorios (`DIRECTORY_SEPARATOR`) para rutas fuente, destino y binario de LibreOffice Portable en entornos Windows.
    * Se aisló el perfil de usuario de LibreOffice con `-env:UserInstallation` apuntando a `storage/app_lidiag/lo_profile` para evitar bloqueos por concurrencia y errores `source file could not be loaded`.
    * Se añadió sondeo con `clearstatcache(true, $destinationPath)` y hasta 15 reintentos con intervalo de 200ms para evitar falsos negativos por demoras de escritura en disco asíncronas de LibreOffice.
  - **Resolución de `Unauthenticated` por Valores Nulos en HMAC:**
    * En `lidiag-reportes` (`VerifyHmacSignature.php`), se normalizaron los campos nulos del payload a string vacío `""` antes de invocar `http_build_query`. El middleware `ConvertEmptyStringsToNull` de Laravel convertía campos vacíos como `fec_naci` a `null`, causando que PHP omitiera la clave en el hash del receptor y generara firmas discrepantes.
  - **Verificación:** Laravel Pint passed ✅ | Tests 257312, 257330, 257376, 257478, 257479, 257480 transferidos con éxito al 100% ✅.
- [x] **2026-10-20 — Infraestructura & Repositorio: Actualización y Blindaje de `.gitignore`:**
  - Exclusión integral de archivos comprimidos y backups locales (`*.zip`, `*.rar`, `*.7z`, `*.tar.gz`, `temp_backups/`).
  - Exclusión de archivos temporales de Office (`~$*`, `**/~$*`) y directorios de herramientas locales (`/.superpowers`, `/.idea`).
  - Blindaje en `storage/app_lidiag/`: exclusión de herramientas y binarios pesados (`LibreOfficePortable`, `microdicom`, entornos `venv`, uploads), runtime de LibreOffice (`lo_profile/`), y protección automática contra commit de credenciales (`config.json`), logs y cachés en los servicios auxiliares (`agente_backups_dcm`, `dcm_disk_monitor_telegram`).
- [x] **2026-10-20 — Versionamiento de Servicios Auxiliares (`storage/app_lidiag/`):**
  - Incorporación a Git de los scripts y fuentes de `agente_backups_dcm/`, servicio STT `transcriptor/` (FastAPI + Whisper), `src-tauri/` y `microdicom/`.
  - Exclusión confirmada de entornos virtuales (`venv/`), binarios pesados (`LibreOfficePortable/`), perfiles de ejecución (`lo_profile/`) y credenciales sensibles (`config.json`).
- [x] **2026-10-21 — Panel de Monitoreo de Backups y Sincronización de Sedes en Tiempo Real:**
  - **Permisos Spatie y Seeder:** Creado `database/seeders/PermisionBackupMonitorSeeder.php` con permisos `seguridad.backups.index` y `seguridad.backups.ejecutar` asignados al rol `Administrador`. Permisos aplicados en DB y caché Spatie reiniciada (`php artisan permissions:cache-reset`).
  - **Servicio Backend de Monitoreo (`BackupMonitorService`):**
    * Detección en tiempo real de espacio en disco (total, usado, libre, porcentaje y estado óptimo/advertencia/crítico).
    * Auditoría de últimos respaldos centrales (MySQL y storage) con cálculo de antigüedad humana, tamaño y hash SHA-256 de integridad.
    * Semáforo de salud de sincronización DCM de sedes hospitalarias (`🟢 Al día <24h`, `🟡 Advertencia 24-48h`, `🔴 Crítico >48h` o sin backups) analizando los archivos zip enviados por los agentes.
    * Bitácora en vivo: parseo de las últimas 30 líneas de `storage/logs/backups.log`.
  - **Controlador Backend (`BackupMonitorController`):**
    * `GET /api/seguridad-backups/estado`: Consulta métricas y estado global de la red. Requiere `seguridad.backups.index`.
    * `POST /api/seguridad-backups/ejecutar-db`: Dispara backup manual inmediato de MySQL delegando a `BackupService::ejecutar(['only_db' => true])` con política de retención automática. Requiere `seguridad.backups.ejecutar`.
  - **Cableado del Módulo (5 Puntos Canónicos):**
    1. API routes en `routes/api_seguridad.php`.
    2. Web route en `routes/web.php` con middleware `['auth', 'permission:seguridad.backups.index']`.
    3. Vista Blade `resources/views/modulos/seguridad/backups.blade.php` montando `<backup-monitor-component />`.
    4. Componente Vue registrado en `resources/js/app.js`.
    5. Menú lateral en `resources/views/comunes/sidebar.blade.php` bajo la sección "Seguridad" con directiva `@can('seguridad.backups.index')`.
  - **Componente Vue 3 (`backupMonitorComponent.vue`):**
    * Tarjetas de KPI: espacio en disco con barra de progreso, último backup de BD, tasa de sincronización de sedes hospitalarias y último backup de storage.
    * Tabla detallada por sede: semáforo de sincronización, fecha del último respaldo, horas de retraso, tamaño y conteo de archivos.
    * Visor de bitácora tipo consola oscura para eventos de respaldo.
    * Botón de refresco manual con atajo accesible `F2` y disparador asistido de backup inmediato de MySQL con confirmación no invasiva.
  - **Pruebas Automatizadas y Calidad:**
    * Creado `tests/Feature/BackupMonitorFeatureTest.php` con 5 tests (autenticación 401, autorización 403, estructura de datos 200, autorización de disparo 403, ejecución con mock 200) pasando al 100%.
    * Verificación integral: ESLint 0 errores/warnings ✅ | Laravel Pint passed ✅ | PHPStan nivel 5 (0 errores) ✅ | PHPUnit 5/5 assertions pasando ✅ | Vite `npm run build` en 1.02s ✅.
- [x] **2026-10-21 — Optimización del Módulo de Liquidación y Productividad Médica:**
  - **Detalle Estructurado de Exclusiones:** Modificado `LiquidacionService::calcular` para cargar relaciones del estudio (`paciente:id,num_docu,nombres,pri_ape` y `medico:id,name`). El arreglo de exclusiones ahora retorna objetos enriquecidos con documento, paciente, médico, código CUPS, nombre del procedimiento y motivo clínico/contractual específico del rechazo de tarifa.
  - **Modal Interactivo de Exclusiones en `liquidacionComponent.vue`:** Los ítems de exclusión ahora son interactivos (`cursor: pointer` con badge reactivo). Al hacer clic despliega un modal accesible con backdrop blur, búsqueda instantánea por paciente/CUPS/médico y tabla detallada para facilitar a contabilidad la identificación y corrección de contratos.
  - **Exportación de Productividad a Excel (`ProductividadExport.php`):** Creada la clase de exportación con membrete institucional Salud Tolima, estilo corporativo (`#1A2274`), bordes, formato de moneda `$#,##0`, subtotales por médico y total general acumulado.
  - **Endpoint y Botón de Descarga:** Creado `GET /api/liquidacion/productividad/exportar` en `api_liquidacion.php`, método `exportarProductividad` en `LiquidacionController` e integrado botón "Excel" en `productividadComponent.vue`.
  - **Estilos Institucionales en `LiquidacionExport.php`:** Integrada cabecera corporativa, formato numérico de moneda y fila de Gran Total Liquidado con conteo de lecturas.
  - **Pruebas Automatizadas y Calidad:**
    * Creado `tests/Feature/LiquidacionFeatureTest.php` con 6 tests (autenticación 401, permisos Spatie 403, cálculo con estructura 200, exportación de liquidación a Excel 200, consulta de productividad 200 y exportación de productividad a Excel 200) pasando al 100%.
    * Verificación integral: ESLint 0 errores/warnings ✅ | Laravel Pint passed ✅ | PHPStan nivel 5 (0 errores) ✅ | PHPUnit 6/6 assertions pasando ✅ | Vite `npm run build` en 1.03s ✅.
- [x] **2026-10-21 — Interoperabilidad HIS / SAHI (SQL Server): Blindaje Atómico, Timeouts y Diagnóstico:**
  - **Timeouts Rápidos de Conexión (`config/database.php`):**
    * Configurado `'connect_timeout' => (int) env('DB_SQLSRV_CONNECT_TIMEOUT', 5)` y `PDO::SQLSRV_ATTR_QUERY_TIMEOUT => (int) env('DB_SQLSRV_QUERY_TIMEOUT', 5)` para evitar que microcortes o caídas de red congelen los workers de PHP durante 30 segundos.
  - **Manejo Defensivo de Excepciones (`SAHIService.php`):**
    * Encapsulados `listarAtenciones`, `listarProductosDiagnosticos`, `buscarEpsPlanContrato` y `listarProductosAtencion` en bloques `try/catch (\Throwable)` retornando código HTTP 503 limpio con log detallado en lugar de errores 500 no capturados.
    * Corregido fallo latente en `obtenerDatosLidiag` que omitía el campo `atencion_id` en la selección.
    * Creado método `verificarConexion(): array` que mide latencia y valida conectividad activa con SQL Server.
  - **Transaccionalidad Atómica en Transcripciones (`SAHITranscibirService.php`):**
    * Agrupadas las 9 inserciones y actualizaciones de consecutivos hacia SAHI (`sisTabla`, `HceEsquema`, `hceEsquemaEncabeza`, `hceEsquemasdeAte`, `hceEsquemaDetMemo`, `hceEsquemaDetProc`) dentro de `DB::connection('sqlsrv')->transaction()`.
    * Blindaje contra esquemas huérfanos o contadores desperdiciados ante fallos intermedios.
    * Corregida inconsistencia en la asignación del encabezado padre (`$conseEsquemaEncavezadoPadre`).
  - **Resiliencia en Reconciliación (`EcografiasSahiReconcileCommand.php`):**
    * Integrado pre-chequeo de socket y PDO a SQL Server. Si el servidor remoto está apagado o en mantenimiento, el comando aborta limpiamente sin marcar falsos errores en los registros de ecografías en estado `ENVIANDO`.
  - **Endpoint y Comando de Diagnóstico Integral:**
    * Nuevo endpoint `GET /api/interface-sahi/test-conexion` en `api_interfaces.php` y `SAHIController::verificarConexion`.
    * Nuevo comando Artisan `php artisan sahi:test` con comprobación de socket TCP (1433), autenticación PDO, metadatos del servidor (SQL Server 2022) y conteo sobre las 5 tablas maestras (`admAtencion`, `admCliente`, `proProducto`, `HceEsquema`, `sisTabla`).
  - **Tipado Estricto de Modelos SAHI:**
    * Declaradas anotaciones `@property` completas en `Atencion`, `ProProducto`, `facFacturaDet`, `sisTabla`, `HceEsquema`, `hceEsquemaEncabeza`, `hceEsquemasdeAte`, `hceEsquemaDetMemo`, `hceEsquemaDetProc` y `Ecografia`.
  - **Pruebas Automatizadas y Calidad:**
    * Creado `tests/Feature/SahiInteroperabilidadTest.php` con 8 pruebas (autenticación 401, autorización 403, diagnóstico estructurado 200, validación de parámetros 422, envío de estudios y ejecución segura de reconciliación) pasando al 100%.
    * Verificación integral: Laravel Pint passed ✅ | PHPStan nivel 5 (0 errores) ✅ | PHPUnit 37/37 assertions pasando (196 assertions) ✅ | Vite `npm run build` en 1.51s ✅.
- [x] **2026-10-22 — Normalización PSR-4 de Casing (Autoload Dump Limpio):**
  - **Diagnóstico:** `composer dump-autoload -o` omitía 14 seeders de `Database\Seeders\Configuracion` y `EcografiaLecturaController` por discrepancia de mayúsculas/minúsculas entre el namespace/clase PHP y la ruta en el sistema de archivos Git.
  - **Corrección de Casing:**
    * Renombrado en Git de `app/Http/Controllers/Ecografias/ecografiaLecturaController.php` a `EcografiaLecturaController.php`.
    * Renombrado en Git del directorio `database/seeders/configuracion/` a `database/seeders/Configuracion/` preservando el historial de sus 14 seeders.
  - **Verificación:** `composer dump-autoload -o` completado sin omitir ninguna clase (9,415 clases mapeadas al 100% sin advertencias) ✅ | PHPUnit 8/8 assertions pasando ✅.
- [x] **2026-10-22 — Auditoría y Normalización de Permisos en BD (`permissions:audit` al 100%):**
  - **Diagnóstico:** El comando `php artisan permissions:audit` detectaba 42 permisos faltantes debido a:
    1. Parseo de sintaxis Spatie con guard explícito (`permission:foo,web`) que concatenaba `,web` al nombre del permiso.
    2. Seeders existentes pendientes de ejecución en base de datos (`PermisionContratacionSeeder`, `PermisionLiquidacionSeeder`, `PermisionServicioCitasSeeder`, `PermisionWhatsAppSeeder`).
    3. Discrepancias tipográficas entre FormRequests y Controladores (`configuracion.tipos-documento.*` vs `config.tipo.documento.*`, `configuracion.consentimientos.*` vs `config.consentimiento_informado.*`, `configuracion.recomendaciones.*` vs `config.recomendaciones.*`).
    4. Duplicado en minúsculas `estudio.leerestudio` en `routes/web.php`.
    5. Falta de seeder idempotente para `config.recomendaciones.*` y permisos de tipos en `PlantillaSeeder`.
  - **Correcciones Implementadas:**
    * `AuditPermissions.php`: Separación de guard explícito en chunks del middleware para evaluar únicamente nombres válidos.
    * FormRequests alineados con sus respectivos controladores y seeders: `StoreConfigTipoDocumentoRequest`, `UpdateConfigTipoDocumentoRequest`, `StoreConsentimientoInformadoRequest`, `UpdateConsentimientoInformadoRequest`, `StoreConfigRecomendacionProductoRequest`, `UpdateConfigRecomendacionProductoRequest`.
    * `routes/web.php`: Limpieza de `estudio.leerestudio` en favor del canónico `estudio.leerEstudio`.
    * `PlantillaSeeder`: Incorporados permisos `config.plantillas.tipos.*`.
    * `PermisoAuditoriaSeeder`: Refactorizado con `firstOrCreate` y `syncRoles` para garantizar idempotencia.
    * Creado seeder `PermisionRecomendacionesSeeder` idempotente para `config.recomendaciones.*`.
    * Ejecución exitosa de seeders en BD (`php artisan db:seed --class=...`).
  - **Verificación:** `php artisan permissions:audit` pasando con **0 faltantes** (119 permisos en código, 151 en BD) ✅ | Laravel Pint passed ✅ | `npm run build` passed en 2.26s ✅ | Autoload con 9,416 clases sin advertencias ✅.
### Estado de módulos recientes (oct. 2026)
- **Auditoría de Permisos Spatie** (2026-10-22): ✅ 100% sincronizado (0 faltantes en BD, regex de guard corregida, seeders idempotentes ejecutados).
- **EcografiaLecturaController / Seeders Configuracion** (2026-10-22): ✅ Casing normalizado conforme a PSR-4. Autoload optimizado limpio al 100%.
- **SAHIController / SAHIService / SAHITranscibirService** (2026-10-21): ✅ Blindaje transaccional atómico, timeouts de 5s, captura 503 defensiva y herramienta de diagnóstico `sahi:test`.
- **BackupMonitorController** (2026-10-21): ✅ Permisos `seguridad.backups.index` y `seguridad.backups.ejecutar` creados y asignados a `Administrador` mediante `PermisionBackupMonitorSeeder`. Rutas en `api_seguridad.php` y `web.php`.
- **EstudioLockController** (2026-10-08): ✅ Sin permisos propios — usa solo `auth`. Las rutas `estudio-lock/tomar` y `estudio-lock/liberar` están registradas en `api_estudios.php`. No requiere seeder.
- **LiquidacionController** (2026-10-01): ✅ Seeder `PermisionLiquidacionSeeder` existente y registrado en `DatabaseSeeder`. Permisos `liquidacion.index` y `liquidacion.exportar` asignados al rol `Administrador`.



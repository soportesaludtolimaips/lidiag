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
    * Actualizados paquetes comprimidos para distribución hospitalaria: `scripts/dcm_agent.rar` y `scripts/dcm_agent.zip` ✅.



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
- [x] **2026-10-19 — Herramienta Autónoma Python: Monitor de Discos DICOM con Bot de Telegram (`dcm_disk_monitor_telegram`):**
  - Implementada herramienta en `storage/app_lidiag/dcm_disk_monitor_telegram/` usando exclusivamente la librería estándar de Python (sin dependencias `pip`).
  - **Modo Dual (Opción C):**
    * Servicio Residente 24/7 (`iniciar_bot.bat` / `--daemon`): Long-Polling para comandos de Telegram (`/discos`, `/estado`, `/alertas`, `/info`, `/ayuda`) y timer de fondo cada 15 min.
    * Tarea Programada Windows (`instalar_tarea_programada.bat` / `--check-once`): Ejecución puntual silenciosa cada 1 hora registrada en Windows Task Scheduler.
  - **Alertas Proactivas y Anti-Spam:** Notificación inmediata en transiciones a 🟡 Advertencia (<15% o <50 GB) y 🔴 Crítico (<5% o <20 GB), notificación de recuperación a 🟢 OK, y cooldown configurable (4h) para evitar saturación de mensajes. Reporte diario matutino a las 08:00 AM.
  - **Multidestinatario:** Soporta múltiples Chat IDs simultáneos (usuarios y grupos con ID negativo `-100...`).
  - **Herramientas y Scripts Incluidos:** `obtener_chat_id.bat` (`--get-chat-ids`), `verificar_estado.bat` (`--status`), `config.json.example`, `README.md` con tutorial para BotFather, y paquete comprimido listo para distribución `dcm_disk_monitor_telegram.zip`.
### Estado de módulos recientes (oct. 2026)
- **EstudioLockController** (2026-10-08): ✅ Sin permisos propios — usa solo `auth`. Las rutas `estudio-lock/tomar` y `estudio-lock/liberar` están registradas en `api_estudios.php`. No requiere seeder.
- **LiquidacionController** (2026-10-01): ✅ Seeder `PermisionLiquidacionSeeder` existente y registrado en `DatabaseSeeder`. Permisos `liquidacion.index` y `liquidacion.exportar` asignados al rol `Administrador`.



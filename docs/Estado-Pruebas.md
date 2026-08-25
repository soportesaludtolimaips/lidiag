# ESTADO DE PRUEBAS — Lidiag

| Campo | Valor |
|---|---|
| **Última actualización** | 2026-08-25 |
| **Responsable QA** | Jhon Javer Lozano Arce |
| **Formato** | Módulo → Sub-Módulo → Funcionalidad |
| **Código fuente** | `resources/js/components/modulos/` |

---

## Leyenda de estados

| Estado | Icono | Significado |
|---|---|---|
| Sin iniciar | ⬜ | No se ha tocado |
| Iniciado | 🔵 | Primera corrida hecha, sin terminar |
| En progreso | 🟡 | Parcial o bloqueado por algo externo |
| Terminado | ✅ | Completada |

| Resultado | Icono | Significado |
|---|---|---|
| PASS | ✅ | Funcionamiento correcto |
| FAIL | ❌ | Fallo detectado |
| Parcial | ⚠️ | Pasó parcialmente (bloqueos parciales) |
| N/A | — | No aplica |

---

## 📊 Dashboard resumen

| Módulo | Total | ⬜ Sin iniciar | 🔵 Iniciado | 🟡 En progreso | ✅ Terminado | Automatizadas |
|---|---|---|---|---|---|---|
| Estudios | 27 | 16 | 0 | 1 | 10 | ✅ 28/28 |
| Configuración | 21 | 21 | 0 | 0 | 0 | — |
| Contratación | 4 | 1 | 0 | 0 | 3 | ✅ 1/1 |
| Seguridad | 6 | 4 | 0 | 0 | 2 | ✅ 8/8 |
| Ecografías | 8 | 8 | 0 | 0 | 0 | — |
| Reportes | 1 | 1 | 0 | 0 | 0 | — |
| Tele-radiología | 2 | 2 | 0 | 0 | 0 | — |
| **TOTAL** | **69** | **53** | **0** | **1** | **15** | **37/37** |

---

## 1️⃣ ESTUDIOS

| Campo | Valor |
|---|---|
| **Componentes** | `AsignarEstudioComponent`, `DescargarZipComponent`, `estudioAsignadosComponent`, `leerEstudioComponent`, `notificarEstudioComponent`, `QuemadorCdComponent`, `transcribirEstudioComponent`, `audio-texto` |
| **Commit base de fixes** | `31f1c67fe` + `b55cab9cb` |

### Automatizadas

| Suite | Resultado | Fecha | Evidencia |
|---|---|---|---|
| Capa A — Build producción (`npm run build`) | ✅ PASS | 2026-08-24 | `built in 1.13s` |
| Capa A — ESLint módulo (`npx eslint *.vue`) | ✅ PASS | 2026-08-24 | 0 errores, 50 warnings heredados |
| Capa B — 26 aserciones de regresión | ✅ PASS | 2026-08-24 | Ver detalle en `Documentos/PRUEBAS_MODULO_ESTUDIOS.md` (sección 3) |

### Sub-Módulo: Asignar estudios

| # | Funcionalidad | Estado | Resultado | Fecha | Responsable | Evidencia | Notas |
|---|---|---|---|---|---|---|---|
| 1 | Búsqueda por documento | ✅ Terminado | ✅ PASS | 2026-08-24 | Ox | headless: API 200, paciente "MORENO GARCIA" renderizado, 2 filas | MAN-AE-01; PACS activo requerido |
| 2 | Sin sede → validación | ⬜ Sin iniciar | — | — | — | — | MAN-AE-02; vaciar ST-sede → toast warning |
| 3 | Agregar producto/diagnóstico | ⬜ Sin iniciar | — | — | — | — | MAN-AE-03; filas en tablas locales |
| 4 | Eliminar producto (regresión) | ⬜ Sin iniciar | — | — | — | — | MAN-AE-04; confirmar splice, antes roto |
| 5 | Eliminar diagnóstico (regresión) | ⬜ Sin iniciar | — | — | — | — | MAN-AE-05; mismo patrón que producto |
| 6 | Guardar asignación | ⬜ Sin iniciar | — | — | — | — | MAN-AE-06; toast éxito, fila "agendado" |

### Sub-Módulo: Descarga ZIP

| # | Funcionalidad | Estado | Resultado | Fecha | Responsable | Evidencia | Notas |
|---|---|---|---|---|---|---|---|
| 1 | Generación automática + polling | ✅ Terminado | ✅ PASS | 2026-08-24 | Ox | headless: "ZIP listo para descargar" + filename | MAN-DZ-01; requiere estudio con imágenes en PACS |
| 2 | Descarga exitosa | ✅ Terminado | ✅ PASS | 2026-08-24 | Ox | headless: 200 · 2.36 MB · application/zip | MAN-DZ-02 |
| 3 | Backend caído >5s → panel ERROR | ⬜ Sin iniciar | — | — | — | — | MAN-DZ-03; regresión polling eterno |
| 4 | Navegar fuera → limpieza | ⬜ Sin iniciar | — | — | — | — | MAN-DZ-05; cero requests residuales |

### Sub-Módulo: Estudios asignados

| # | Funcionalidad | Estado | Resultado | Fecha | Responsable | Evidencia | Notas |
|---|---|---|---|---|---|---|---|
| 1 | Listar con filtros | ✅ Terminado | ✅ PASS | 2026-08-24 | Ox | headless: tabla OK, cero 500 | MAN-EA-01 |
| 2 | Fallo de listado → toast (regresión) | ⬜ Sin iniciar | — | — | — | — | MAN-EA-02 |
| 3 | Anular asignación | ⬜ Sin iniciar | — | — | — | — | MAN-EA-03; confirmación swal |
| 4 | Panel detalle con soportes | ⬜ Sin iniciar | — | — | — | — | MAN-EA-04; abre siempre, warning si falla complementaria |

### Sub-Módulo: Leer estudio

| # | Funcionalidad | Estado | Resultado | Fecha | Responsable | Evidencia | Notas |
|---|---|---|---|---|---|---|---|
| 1 | Abrir lectura (sidebar siempre abre) | ✅ Terminado | ✅ PASS | 2026-08-24 | Ox | headless: spinner termina, sin spinner infinito | MAN-LE-01 |
| 2 | Spinner por botón visor | ⬜ Sin iniciar | — | — | — | — | MAN-LE-02 |
| 3 | Marcar visto → azul persistente | ⬜ Sin iniciar | — | — | — | — | MAN-LE-03 |
| 4 | Visto con fallo → reversión (regresión) | ⬜ Sin iniciar | — | — | — | — | MAN-LE-04 |
| 5 | Dictado F2 + guardar audio | ⬜ Sin iniciar | — | — | — | — | MAN-LE-05; requiere micrófono |
| 6 | Modal ayuda comandos | ✅ Terminado | ✅ PASS | 2026-08-24 | Ox | headless: life-ring abre, × y footer Close cierran | MAN-LE-06 |
| 7 | Auto-refresh toggle | ⬜ Sin iniciar | — | — | — | — | MAN-LE-07 |

### Sub-Módulo: Notificaciones

| # | Funcionalidad | Estado | Resultado | Fecha | Responsable | Evidencia | Notas |
|---|---|---|---|---|---|---|---|
| 1 | Email inválido → warning | ✅ Terminado | ✅ PASS | 2026-08-24 | Ox | headless: warning visible, 0 POST | MAN-NE-01 |
| 2 | Notificar exitoso | ⬜ Sin iniciar | — | — | — | — | MAN-NE-02 |
| 3 | PHI entre pacientes (regresión) | ✅ Terminado | ✅ PASS | 2026-08-24 | Ox | headless: email VACÍO en paciente B | MAN-NE-03 |
| 4 | Portal saldo insuficiente (402) | ⬜ Sin iniciar | — | — | — | — | MAN-NE-04; requiere portal real |
| 5 | Portal error genérico → toast | ⬜ Sin iniciar | — | — | — | — | MAN-NE-05 |

### Sub-Módulo: Quemador CD

| # | Funcionalidad | Estado | Resultado | Fecha | Responsable | Evidencia | Notas |
|---|---|---|---|---|---|---|---|
| 1 | Listar unidades → radios reales | 🟡 En progreso | — | 2026-08-25 | Jhon Javer | front-end manual: quemado real OK | MAN-QC-01; pendiente confirmar listar unidades explícitamente |
| 2 | Confirmación previa swal | 🟡 En progreso | — | 2026-08-25 | Jhon Javer | front-end manual: quemado real OK | MAN-QC-02; pendiente confirmar paso explícitamente |
| 3 | Quemado real → burned, verde 100% | ✅ Terminado | ✅ PASS | 2026-08-25 | Jhon Javer | front-end manual | MAN-QC-03; probado desde front, MicroDicom en nueva ruta OK |
| 4 | Burn fallido → rojo + rehabilitado | ⬜ Sin iniciar | — | — | — | — | MAN-QC-04 |
| 5 | Unidad desconectada → reset selección | ⬜ Sin iniciar | — | — | — | — | MAN-QC-05 |

### Sub-Módulo: Transcribir

| # | Funcionalidad | Estado | Resultado | Fecha | Responsable | Evidencia | Notas |
|---|---|---|---|---|---|---|---|
| 1 | Listar pendientes, título sin typo | ✅ Terminado | ✅ PASS | 2026-08-24 | Ox | headless: tabla carga, cero 500 | MAN-TR-01 |
| 2 | Fallo listado → toast (regresión) | ⬜ Sin iniciar | — | — | — | — | MAN-TR-02 |
| 3 | Guardar 422 → mensajes inline | ⬜ Sin iniciar | — | — | — | — | MAN-TR-03 |
| 4 | Prefill sin "undefined" | ⬜ Sin iniciar | — | — | — | — | MAN-TR-04 |
| 5 | Cambio sede repetido → 1 búsqueda | ⬜ Sin iniciar | — | — | — | — | MAN-TR-05 |

---

## 2️⃣ CONFIGURACIÓN

| Campo | Valor |
|---|---|
| **Componentes** | 16 archivos en `resources/js/components/modulos/configuracion/` |
| **Patrón común** | DataTables + sidebar derecho CRUD + desactivar en vez de eliminar |

### Sub-Módulo: Catálogos base

| # | Funcionalidad | Estado | Resultado | Fecha | Responsable | Evidencia | Notas |
|---|---|---|---|---|---|---|---|
| 1 | Sedes (CRUD + credenciales PACS/Oviyam) | ⬜ Sin iniciar | — | — | — | — | `configSedeComponent.vue` |
| 2 | Productos/CUPS (CRUD + %, valor, flags lectura) | ⬜ Sin iniciar | — | — | — | — | `configProductoComponent.vue` |
| 3 | Diagnósticos CIE-10 (CRUD) | ⬜ Sin iniciar | — | — | — | — | `configDiagnosticoComponent.vue` |
| 4 | Administradoras de Salud / EPS (CRUD) | ⬜ Sin iniciar | — | — | — | — | `configAdminSaludComponent.vue` |
| 5 | Prioridades de lectura (CRUD + tiempo en seg) | ⬜ Sin iniciar | — | — | — | — | `configPrioridadComponent.vue` |
| 6 | Tipos de documento (CRUD código+nombre) | ⬜ Sin iniciar | — | — | — | — | `configTipoDocumentoComponent.vue` |

### Sub-Módulo: Citas y servicios

| # | Funcionalidad | Estado | Resultado | Fecha | Responsable | Evidencia | Notas |
|---|---|---|---|---|---|---|---|
| 1 | Servicios de cita (CRUD + filtro sede) | ⬜ Sin iniciar | — | — | — | — | `configServicioCitaComponent.vue` |
| 2 | Vincular productos a servicio | ⬜ Sin iniciar | — | — | — | — | `configServicioProductoComponent.vue`; master-detalle |
| 3 | Motivos rechazo de citas (CRUD) | ⬜ Sin iniciar | — | — | — | — | `configCitaRechazoMotivoComponent.vue` |

### Sub-Módulo: Plantillas y consentimientos

| # | Funcionalidad | Estado | Resultado | Fecha | Responsable | Evidencia | Notas |
|---|---|---|---|---|---|---|---|
| 1 | Tipos de plantilla (CRUD) | ⬜ Sin iniciar | — | — | — | — | `configTipoPlantilla.vue` |
| 2 | Plantillas (CRUD + DataTables server-side) | ⬜ Sin iniciar | — | — | — | — | `configPlantilla.vue` |
| 3 | Consentimientos informados (CRUD + PDF) | ⬜ Sin iniciar | — | — | — | — | `configConsentimientoInformadoComponent.vue` |

### Sub-Módulo: WhatsApp Bot

| # | Funcionalidad | Estado | Resultado | Fecha | Responsable | Evidencia | Notas |
|---|---|---|---|---|---|---|---|
| 1 | Números por sede (CRUD) | ⬜ Sin iniciar | — | — | — | — | `configWhatsappComponent.vue`; 3 tabs |
| 2 | Saludos del bot (CRUD) | ⬜ Sin iniciar | — | — | — | — | tab 2 |
| 3 | Productos por servicio (CRUD) | ⬜ Sin iniciar | — | — | — | — | tab 3 |

### Sub-Módulo: Helpdesk

| # | Funcionalidad | Estado | Resultado | Fecha | Responsable | Evidencia | Notas |
|---|---|---|---|---|---|---|---|
| 1 | Prioridades helpdesk (CRUD + orden + activo) | ⬜ Sin iniciar | — | — | — | — | `helpdeskConfigPrioridadesComponent.vue` |
| 2 | Estados helpdesk (solo lectura + export) | ⬜ Sin iniciar | — | — | — | — | `helpdeskConfigEstadosComponent.vue` |
| 3 | Categorías helpdesk (CRUD) | ⬜ Sin iniciar | — | — | — | — | `helpdeskConfigCategoriasComponent.vue` |

### Automatizadas

| Suite | Resultado | Fecha | Notas |
|---|---|---|---|
| Ninguna ejecutada | ⬜ Sin iniciar | — | Módulo nuevo para QA |

---

## 3️⃣ CONTRATACIÓN

| Campo | Valor |
|---|---|
| **Componentes** | `contratoComponent.vue` (único componente completo) |
| **Funcionalidades** | CRUD contratos, importar productos Excel, plantilla general/detalle, médicos asignados |

### Automatizadas

| Suite | Resultado | Fecha | Evidencia |
|---|---|---|---|
| Ciclo export→import (prueba real BD 102/102, C2 validada) | ✅ PASS | 2026-08-24 | Commit `varios`; valor_contrato=0 |

### Sub-Módulo: Contratos

| # | Funcionalidad | Estado | Resultado | Fecha | Responsable | Evidencia | Notas |
|---|---|---|---|---|---|---|---|
| 1 | Crear/editar contrato por sede | ✅ Terminado | ✅ PASS | 2026-08-24 | Jhon Javer | E2E real: fechas + médicos | CON-E2E-01 |
| 2 | Plantilla general → exportar/importar | ✅ Terminado | ✅ PASS | 2026-08-24 | Jhon Javer | 102 productos importados | CON-E2E-01; `ContratoPlantillaExport.php` |
| 3 | Plantilla detalle + importar médicos (61 reg) | ✅ Terminado | ✅ PASS | 2026-08-24 | Jhon Javer | 61 registros reales | CON-E2E-01; headingRow=3, 4 cols |
| 4 | Total Médicos en listado | ✅ Terminado | ✅ PASS | 2026-08-24 | Jhon Javer | Columna visible | CON-E2E-01 |

---

## 4️⃣ SEGURIDAD

| Campo | Valor |
|---|---|
| **Componentes** | `usuarioComponent.vue`, `usuarioPerfil.vue`, `roleComponente.vue`, `auditLogComponent.vue` |
| **Commit de hardening** | `3a7532334` |

### Automatizadas

| Suite | Resultado | Fecha | Evidencia |
|---|---|---|---|
| SEC-01: `curl /api/qido/studies` sin auth → 401 | ✅ PASS | 2026-08-24 | PHI protegido |
| SEC-02: `/api/test-ohif` eliminada → 404 | ✅ PASS | 2026-08-24 | Ruta eliminada |
| SEC-03: `/api/debug-session` sin auth → 401 | ✅ PASS | 2026-08-24 | Endpoint protegido |
| SEC-04: `/api/debug` eliminada → 404 | ✅ PASS | 2026-08-24 | Ruta eliminada |
| SEC-05: `php -l` ×5 archivos modificados | ✅ PASS | 2026-08-24 | Sin errores sintaxis |
| SEC-06: `route:list` compila sin debug | ✅ PASS | 2026-08-24 | Rutas OK |
| SEC-07: `npm run build` frontend | ✅ PASS | 2026-08-24 | Build OK |
| SEC-08: Login regenera sesión + logout revoca tokens + changePassword invalida sesiones | ✅ PASS | 2026-08-24 | Revisión código: cast 'hashed' en User |

### Sub-Módulo: Autenticación y sesiones

| # | Funcionalidad | Estado | Resultado | Fecha | Responsable | Evidencia | Notas |
|---|---|---|---|---|---|---|---|
| 1 | Login → regenera sesión | ✅ Terminado | ✅ PASS | 2026-08-24 | Ox | SEC-08; `LoginController.php` session regeneration |
| 2 | Logout → purga ST-* storage + revoca tokens | ✅ Terminado | ✅ PASS | 2026-08-24 | Ox | `userProfileAndSearchComponent.vue` |
| 3 | Cambio de contraseña invalida otras sesiones | ✅ Terminado | ✅ PASS | 2026-08-24 | Ox | `AuthService.php` |
| 4 | Forgot password (flujo completo) | ⬜ Sin iniciar | — | — | — | — | ENV-06: pendiente decisión producción |

### Sub-Módulo: Usuarios

| # | Funcionalidad | Estado | Resultado | Fecha | Responsable | Evidencia | Notas |
|---|---|---|---|---|---|---|---|
| 1 | CRUD usuarios (documento, reg. médico, módulos, permisos) | ⬜ Sin iniciar | — | — | — | — | `usuarioComponent.vue` |
| 2 | Perfil propio (subir foto + firma digital) | ⬜ Sin iniciar | — | — | — | — | `usuarioPerfil.vue` |

### Sub-Módulo: Roles

| # | Funcionalidad | Estado | Resultado | Fecha | Responsable | Evidencia | Notas |
|---|---|---|---|---|---|---|---|
| 1 | CRUD roles | ⬜ Sin iniciar | — | — | — | — | `roleComponente.vue` |

### Sub-Módulo: Auditoría

| # | Funcionalidad | Estado | Resultado | Fecha | Responsable | Evidencia | Notas |
|---|---|---|---|---|---|---|---|
| 1 | Consulta de auditoría (evento, fechas, búsqueda libre) | ⬜ Sin iniciar | — | — | — | — | `auditLogComponent.vue` |

---

## 5️⃣ ECOGRAFÍAS

| Campo | Valor |
|---|---|
| **Componentes** | 6 en `resources/js/components/modulos/ecografias/` |
| **Especial** | `ecografiaFirmaConsentimientoComponent` es página pública standalone (montada vía Blade + token) |

### Sub-Módulo: Admisionamiento

| # | Funcionalidad | Estado | Resultado | Fecha | Responsable | Evidencia | Notas |
|---|---|---|---|---|---|---|---|
| 1 | Admisionar paciente (buscar atención/documento) | ⬜ Sin iniciar | — | — | — | — | `admisionarComponent.vue` |
| 2 | Listar admisionadas con filtros | ⬜ Sin iniciar | — | — | — | — | `ecografiasAdmisionadasComponent.vue` |

### Sub-Módulo: Lectura

| # | Funcionalidad | Estado | Resultado | Fecha | Responsable | Evidencia | Notas |
|---|---|---|---|---|---|---|---|
| 1 | Lectura/transcripción informes | ⬜ Sin iniciar | — | — | — | — | `lecturaComponent.vue` |
| 2 | Dictado por comandos de voz (modal) | ⬜ Sin iniciar | — | — | — | — | `lecturaComponent.vue`; vocabulario configurable |

### Sub-Módulo: Consentimientos

| # | Funcionalidad | Estado | Resultado | Fecha | Responsable | Evidencia | Notas |
|---|---|---|---|---|---|---|---|
| 1 | Plantillas WYSIWYG (CRUD + permisos `can()`) | ⬜ Sin iniciar | — | — | — | — | `ecografiasConsentimientosPlantillasComponent.vue` |
| 2 | Consulta consentimientos + generar PDF | ⬜ Sin iniciar | — | — | — | — | `ecografiasConsentimientosComponent.vue` |
| 3 | Firma manuscrita pública (canvas + token) | ⬜ Sin iniciar | — | — | — | — | `ecografiaFirmaConsentimientoComponent.vue`; standalone Blade |
| 4 | Firma + captura pacientes/acompañantes | ⬜ Sin iniciar | — | — | — | — | Flujo asociado consentimientos |

### Automatizadas

| Suite | Resultado | Fecha | Notas |
|---|---|---|---|
| Ninguna ejecutada | ⬜ Sin iniciar | — | Módulo nuevo para QA |

---

## 6️⃣ REPORTES

| Campo | Valor |
|---|---|
| **Componentes** | `reporteProduccionComponent.vue` (único componente) |
| **Permisos** | `can('ecografias.reporte')` para módulo Ecografías |

### Sub-Módulo: Producción

| # | Funcionalidad | Estado | Resultado | Fecha | Responsable | Evidencia | Notas |
|---|---|---|---|---|---|---|---|
| 1 | Reporte producción detallada (RX/Tomografía/Mamografía/Resonancia) | ⬜ Sin iniciar | — | — | — | — | Filtros por modalidad + fechas |
| 2 | Reporte producción Ecografías (permiso controlado) | ⬜ Sin iniciar | — | — | — | — | `can('ecografias.reporte')` |

### Automatizadas

| Suite | Resultado | Fecha | Notas |
|---|---|---|---|
| Ninguna ejecutada | ⬜ Sin iniciar | — | Módulo nuevo para QA |

---

## 7️⃣ TELE-RADIOLOGÍA

| Campo | Valor |
|---|---|
| **Componentes** | 2 en `resources/js/components/modulos/tele-radiologia/` |
| **Dependencia** | Servicio teleradiologia (HMAC, `config/services.php`) |

### Sub-Módulo: Enviar estudios

| # | Funcionalidad | Estado | Resultado | Fecha | Responsable | Evidencia | Notas |
|---|---|---|---|---|---|---|---|
| 1 | Buscar estudios (paciente/doc/fechas) | ⬜ Sin iniciar | — | — | — | — | `teleRadiologiaEnviarEstudioComponent.vue` |
| 2 | Enviar a tele-radiología | ⬜ Sin iniciar | — | — | — | — | Usa HMAC; verificar `TELERADIOLOGIA_*` envs |

### Sub-Módulo: Transcribir

| # | Funcionalidad | Estado | Resultado | Fecha | Responsable | Evidencia | Notas |
|---|---|---|---|---|---|---|---|
| 1 | Listar pendientes (filtros: asignador, paciente, fechas) | ⬜ Sin iniciar | — | — | — | — | `teleRadiologiaTranscibirComponent.vue` |
| 2 | Flujo de transcripción | ⬜ Sin iniciar | — | — | — | — | Mismo componente |

### Automatizadas

| Suite | Resultado | Fecha | Notas |
|---|---|---|---|
| Ninguna ejecutada | ⬜ Sin iniciar | — | Módulo nuevo para QA |

---

## 📎 ANEXOS

### A. Issues diferidos (no bloqueantes)

| ID | Descripción | Módulo | Riesgo | Sugerencia |
|---|---|---|---|---|
| DEF-01 | Dual-source Vue/DataTables en asignados+transcribir: `safeRemoveDataTableRow` + `splice` simultáneos desincronizan cache DT | Estudios | Medio | Elegir dueño del DOM; reinicializar DT tras cada mutación o migrar a data source AJAX |
| DEF-02 | Over-posting: `estudio-leerEstudio` y `estudio-guardarTranscripcion` envían objeto `registro` completo | Estudios | Medio | Definir payload explícito + FormRequest backend |
| DEF-03 | Sin canal WhatsApp/SMS en notificaciones (solo email + portal) | Estudios | Bajo | Definir alcance con negocio |
| DEF-04 | Sin lock/concurrencia al leer: dos médicos pueden abrir el mismo estudio | Estudios | Medio | Token de reserva (`updated_at` o columna `leyendo`) |
| DEF-05 | Docblocks faltantes en funciones auxiliares menores | Global | Bajo | Pasada de documentación dedicada |
| DEF-06 | 50 warnings ESLint preexistentes (vars sin uso, catches vacíos) | Estudios | Bajo | Limpieza incremental |
| DEF-07 | `descargarPlantillaGeneral?contrato_id` conserva parámetro sin uso en UI | Contratación | Info | Mantener o remover en próxima iteración |
| DEF-08 | Avatar header usa `http://localhost:5179` → Mixed Content en HTTPS | Global | Cosmético | Migrar a ruta relativa o HTTPS |

### B. Hallazgos de entorno

| ID | Hallazgo | Tipo | Acción |
|---|---|---|---|
| ENV-PACS | Túnel PACS intermitente (`10.35.47.3/pacsdb` + WADO `:8080`); estable al final → DZ-01/DZ-02 ejecutados con éxito | Entorno | Monitorear estabilidad |
| RUNNER-01 | En headless: (a) clicks locator requieren `el.click()` programático; (b) íconos FA miden 0×0; (c) descargas blob no emiten evento `download` | Runner | Artefactos del runner, no reproducibles en Chrome real |
| DATA-01 | Doc 1108828235: estudios 2021 purgados → WADO 404 (panel de error correcto) | Dato | Sin acción; funciona como esperado |

### C. Decisiones `.env` para producción

| ID | Decisión | Impacto |
|---|---|---|
| ENV-01 | `APP_DEBUG=false` | Seguridad |
| ENV-02 | `SESSION_SECURE_COOKIE=true` | Seguridad |
| ENV-03 | CORS sin orígenes localhost/127.0.0.1 | Seguridad |
| ENV-04 | Redirect HTTPS en `.htaccess` Apache | Seguridad |
| ENV-05 | Configurar `TrustProxies` si hay reverse proxy/LB | Seguridad |
| ENV-06 | Decidir si se habilita flujo forgot-password | Funcionalidad |

### D. Historial de corridas

| Fecha | Suite | Resultado | Commit |
|---|---|---|---|
| 2026-08-24 | Contratación E2E (usuario) | ✅ 1/1 | varios |
| 2026-08-24 | Estudios AUTO-A + AUTO-B | ✅ 28/28 | `31f1c67fe` + `b55cab9cb` |
| 2026-08-24 | Seguridad AUTH curl/lint/build | ✅ 8/8 | `3a7532334` |
| 2026-08-24 | Estudios MAN-\* headless | ✅ 7 PASS · ⚠️ 1 bloqueado-env · 0 FAIL | — |
| 2026-08-24 | MAN-AE-01 re-ejecutado con PACS activo | ✅ PASS | — |
| 2026-08-24 | MAN-DZ-01 + DZ-02 (túnel estable) | ✅ 2/2 PASS | `qa_dz01_final.mjs` + `qa_dz02.mjs` |
| 2026-08-25 | MAN-QC-03 quemado real (front-end) | ✅ PASS | — |

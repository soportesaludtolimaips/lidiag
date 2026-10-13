# ESTADO DE PRUEBAS — Lidiag

| Campo | Valor |
|---|---|
| **Última actualización** | 2026-09-22 |
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
| Estudios | 42 | 0 | 0 | 0 | 42 | ✅ 28/28 |
| Configuración | 21 | 0 | 0 | 0 | 21 | — |
| Contratación | 4 | 0 | 0 | 0 | 4 | ✅ 1/1 |
| Seguridad | 6 | 0 | 0 | 0 | 6 | ✅ 8/8 |
| Ecografías | 8 | 8 | 0 | 0 | 0 | — |
| Reportes | 3 | 0 | 0 | 0 | 3 | — |
| Tele-radiología | 2 | 2 | 0 | 0 | 0 | — |
| Liquidación | 7 | 7 | 0 | 0 | 0 | — |
| **TOTAL** | **93** | **17** | **0** | **0** | **76** | **37/37** |

---

## 1️⃣ ESTUDIOS

| Campo | Valor |
|---|---|
| **Componentes** | `AsignarEstudioComponent`, `DescargarZipComponent`, `estudioAsignadosComponent`, `leerEstudioComponent`, `notificarEstudioComponent`, `QuemadorCdComponent`, `QuemarMultiplesComponent`, `transcribirEstudioComponent`, `audio-texto` |
| **Commit base de fixes** | `31f1c67fe` + `b55cab9cb` + `08d3f3934` (SAHI diagnosticos/enfermedad actual) + `a834b1463` (SAHI URL typo + medico_id) + `c5c2e1fe1` (Quemador write speed) + `e98ed8a2d` (WMI drive detection) |

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
| 2 | Sin sede → validación | ✅ Terminado | ✅ PASS | 2026-08-25 | Ox | headless: toast jq-toast "Debes establece la sede..." visible + 0 peticiones API | MAN-AE-02 |
| 3 | Agregar producto/diagnóstico | ✅ Terminado | ✅ PASS | 2026-08-26 | Jhon Javer | front-end manual | MAN-AE-03; sede 5 Líbano, doc `111454545` |
| 4 | Eliminar producto (regresión) | ✅ Terminado | ✅ PASS | 2026-08-26 | Jhon Javer | front-end manual | MAN-AE-04 |
| 5 | Eliminar diagnóstico (regresión) | ✅ Terminado | ✅ PASS | 2026-08-26 | Jhon Javer | front-end manual | MAN-AE-05 |
| 6 | Guardar asignación | ✅ Terminado | ✅ PASS | 2026-08-26 | Jhon Javer | front-end manual | MAN-AE-06; toast éxito, fila "agendado" |

### Sub-Módulo: Descarga ZIP

| # | Funcionalidad | Estado | Resultado | Fecha | Responsable | Evidencia | Notas |
|---|---|---|---|---|---|---|---|
| 1 | Generación automática + polling | ✅ Terminado | ✅ PASS | 2026-08-24 | Ox | headless: "ZIP listo para descargar" + filename | MAN-DZ-01; requiere estudio con imágenes en PACS |
| 2 | Descarga exitosa | ✅ Terminado | ✅ PASS | 2026-08-24 | Ox | headless: 200 · 2.36 MB · application/zip | MAN-DZ-02 |
| 3 | Backend caído >5s → panel ERROR | ✅ Terminado | ✅ PASS | 2026-08-26 | Jhon Javer | front-end manual | MAN-DZ-03 |
| 4 | Navegar fuera → limpieza | ✅ Terminado | ✅ PASS | 2026-08-26 | Jhon Javer | front-end manual | MAN-DZ-05 |

### Sub-Módulo: Estudios asignados

| # | Funcionalidad | Estado | Resultado | Fecha | Responsable | Evidencia | Notas |
|---|---|---|---|---|---|---|---|
| 1 | Listar con filtros | ✅ Terminado | ✅ PASS | 2026-08-24 | Ox | headless: tabla OK, cero 500 | MAN-EA-01 |
| 2 | Fallo de listado → toast (regresión) | ✅ Terminado | ✅ PASS | 2026-08-26 | Jhon Javer | front-end manual | MAN-EA-02 |
| 3 | Anular asignación | ✅ Terminado | ✅ PASS | 2026-08-26 | Jhon Javer | front-end manual | MAN-EA-03; confirmación swal |
| 4 | Panel detalle con soportes | ✅ Terminado | ✅ PASS | 2026-08-26 | Jhon Javer | front-end manual | MAN-EA-04 |

### Sub-Módulo: Leer estudio

| # | Funcionalidad | Estado | Resultado | Fecha | Responsable | Evidencia | Notas |
|---|---|---|---|---|---|---|---|
| 1 | Abrir lectura (sidebar siempre abre) | ✅ Terminado | ✅ PASS | 2026-08-24 | Ox | headless: spinner termina, sin spinner infinito | MAN-LE-01 |
| 2 | Spinner por botón visor | ✅ Terminado | ✅ PASS | 2026-08-26 | Jhon Javer | front-end manual | MAN-LE-02 |
| 3 | Marcar visto → azul persistente | ✅ Terminado | ✅ PASS | 2026-08-26 | Jhon Javer | front-end manual | MAN-LE-03 |
| 4 | Visto con fallo → reversión (regresión) | ✅ Terminado | ✅ PASS | 2026-08-26 | Jhon Javer | front-end manual | MAN-LE-04 |
| 5 | Dictado F2 + comandos de voz | ✅ Terminado | ✅ PASS | 2026-08-26 | Jhon Javer | MAN-LE-05; F2 inicia/detiene, comandos: mayúsculas, puntuación, saltos, dos puntos aparte. 7 bugs corregidos |
| 6 | Modal ayuda comandos | ✅ Terminado | ✅ PASS | 2026-08-24 | Ox | headless: life-ring abre, × y footer Close cierran | MAN-LE-06 |
| 7 | Auto-refresh toggle | ✅ Terminado | ✅ PASS | 2026-08-26 | Jhon Javer | front-end manual | MAN-LE-07 |
| 8 | Cargar audio → transcripción UTF-8 | ✅ Terminado | ✅ PASS | 2026-08-26 | Jhon Javer | MAN-LE-08; sube mp3/webm, FFmpeg convierte, Whisper transcribe, sin caracteres raros. Fix: PYTHONUTF8=1 + file_get_contents |

### Sub-Módulo: Notificaciones

| # | Funcionalidad | Estado | Resultado | Fecha | Responsable | Evidencia | Notas |
|---|---|---|---|---|---|---|---|
| 1 | Email inválido → warning | ✅ Terminado | ✅ PASS | 2026-08-24 | Ox | headless: warning visible, 0 POST | MAN-NE-01 |
| 2 | Notificar exitoso | ✅ Terminado | ✅ PASS | 2026-08-26 | Jhon Javer | front-end manual | MAN-NE-02 |
| 3 | PHI entre pacientes (regresión) | ✅ Terminado | ✅ PASS | 2026-08-24 | Ox | headless: email VACÍO en paciente B | MAN-NE-03 |
| 4 | Enviar al portal de pacientes | ✅ Terminado | ✅ PASS | 2026-08-26 | Jhon Javer | front-end manual | MAN-NE-04 |
| 5 | Error del portal → toast | ✅ Terminado | ✅ PASS | 2026-08-26 | Jhon Javer | front-end manual | MAN-NE-05; fix: quitar toast "Procesando" duplicado |

### Sub-Módulo: Quemador CD

| # | Funcionalidad | Estado | Resultado | Fecha | Responsable | Evidencia | Notas |
|---|---|---|---|---|---|---|---|
| 1 | Listar unidades → radios reales | ✅ Terminado | ✅ PASS | 2026-08-26 | Jhon Javer | front-end manual | MAN-QC-01 |
| 2 | Confirmación previa swal | ✅ Terminado | ✅ PASS | 2026-08-26 | Jhon Javer | front-end manual | MAN-QC-02 |
| 3 | Quemado real → burned, verde 100% | ✅ Terminado | ✅ PASS | 2026-08-25 | Jhon Javer | front-end manual | MAN-QC-03; probado desde front, MicroDicom en nueva ruta OK |
| 4 | Burn fallido → rojo + rehabilitado | ✅ Terminado | ✅ PASS | 2026-08-26 | Jhon Javer | front-end manual | MAN-QC-04 |
| 5 | Unidad desconectada → reset selección | ✅ Terminado | ✅ PASS | 2026-08-26 | Jhon Javer | front-end manual | MAN-QC-05 |
| 6 | Multi-estudio: checkboxes + select-all | ✅ Terminado | ✅ PASS | 2026-09-22 | Jhon Javer | front-end manual | MAN-QC-06; `QuemarMultiplesComponent.vue`, vue-draggable reorder |
| 7 | Disc type badges (CD/DVD/empty) + auto-refresh 5s | ✅ Terminado | ✅ PASS | 2026-09-22 | Jhon Javer | front-end manual | MAN-QC-07; WMI MediaLoaded + IMAPI2 fallback |
| 8 | Write speed auto-negotiation (lowest) | ✅ Terminado | ✅ PASS | 2026-09-22 | Jhon Javer | front-end manual | MAN-QC-08; HP DS8A9SH + DVD fix |

### Sub-Módulo: Asignar Estudios (nuevas funcionalidades)

| # | Funcionalidad | Estado | Resultado | Fecha | Responsable | Evidencia | Notas |
|---|---|---|---|---|---|---|---|
| 7 | SAHI API URL typo fix (listar-productos-diagnosticos) | ✅ Terminado | ✅ PASS | 2026-09-22 | Jhon Javer | front-end manual | MAN-AE-07; fix extra "listar" + camelCase |
| 8 | medico_id validación frontend antes submit | ✅ Terminado | ✅ PASS | 2026-09-22 | Jhon Javer | front-end manual | MAN-AE-08; toast warning si vacío |
| 9 | SAHI diagnosticos + Enfermedad Actual loading | ✅ Terminado | ✅ PASS | 2026-09-22 | Jhon Javer | front-end manual | MAN-AE-09; select explícito + guarded=[] + fallback |

### Sub-Módulo: Transcribir

| # | Funcionalidad | Estado | Resultado | Fecha | Responsable | Evidencia | Notas |
|---|---|---|---|---|---|---|---|
| 1 | Listar pendientes, título sin typo | ✅ Terminado | ✅ PASS | 2026-08-24 | Ox | headless: tabla carga, cero 500 | MAN-TR-01 |
| 2 | Fallo listado → toast (regresión) | ✅ Terminado | ✅ PASS | 2026-08-26 | Jhon Javer | front-end manual | MAN-TR-02 |
| 3 | Guardar 422 → mensajes inline | ✅ Terminado | ✅ PASS | 2026-08-26 | Jhon Javer | front-end manual | MAN-TR-03 |
| 4 | Prefill sin "undefined" | ✅ Terminado | ✅ PASS | 2026-08-26 | Jhon Javer | front-end manual | MAN-TR-04 |
| 5 | Cambio sede repetido → 1 búsqueda | ✅ Terminado | ✅ PASS | 2026-08-26 | Jhon Javer | front-end manual | MAN-TR-05 |

---

## 2️⃣ CONFIGURACIÓN

| Campo | Valor |
|---|---|
| **Componentes** | 16 archivos en `resources/js/components/modulos/configuracion/` |
| **Patrón común** | DataTables + sidebar derecho CRUD + desactivar en vez de eliminar |

### Sub-Módulo: Catálogos base

| # | Funcionalidad | Estado | Resultado | Fecha | Responsable | Evidencia | Notas |
|---|---|---|---|---|---|---|---|
| 1 | Sedes (CRUD + credenciales PACS/Oviyam) | ✅ Terminado | ✅ PASS | 2026-08-26 | Jhon Javer | front-end manual | CFG-SED-01 |
| 2 | Productos/CUPS (CRUD + %, valor, flags lectura) | ✅ Terminado | ✅ PASS | 2026-08-26 | Jhon Javer | front-end manual | CFG-PRO-01 |
| 3 | Diagnósticos CIE-10 (CRUD) | ✅ Terminado | ✅ PASS | 2026-08-26 | Jhon Javer | front-end manual | CFG-DIA-01 |
| 4 | Administradoras de Salud / EPS (CRUD) | ✅ Terminado | ✅ PASS | 2026-08-26 | Jhon Javer | front-end manual | CFG-EPS-01 |
| 5 | Prioridades de lectura (CRUD + tiempo en seg) | ✅ Terminado | ✅ PASS | 2026-08-26 | Jhon Javer | front-end manual | CFG-PRI-01 |
| 6 | Tipos de documento (CRUD código+nombre) | ✅ Terminado | ✅ PASS | 2026-08-26 | Jhon Javer | front-end manual | CFG-TDO-01 |

### Sub-Módulo: Citas y servicios

| # | Funcionalidad | Estado | Resultado | Fecha | Responsable | Evidencia | Notas |
|---|---|---|---|---|---|---|---|
| 1 | Servicios de cita (CRUD + filtro sede) | ✅ Terminado | ✅ PASS | 2026-08-26 | Jhon Javer | front-end manual | CFG-SER-01 |
| 2 | Vincular productos a servicio | ✅ Terminado | ✅ PASS | 2026-08-26 | Jhon Javer | front-end manual | CFG-SER-02; master-detalle |
| 3 | Motivos rechazo de citas (CRUD) | ✅ Terminado | ✅ PASS | 2026-08-26 | Jhon Javer | front-end manual | CFG-MOT-01 |

### Sub-Módulo: Plantillas y consentimientos

| # | Funcionalidad | Estado | Resultado | Fecha | Responsable | Evidencia | Notas |
|---|---|---|---|---|---|---|---|
| 1 | Tipos de plantilla (CRUD) | ✅ Terminado | ✅ PASS | 2026-08-26 | Jhon Javer | front-end manual | CFG-TPL-01 |
| 2 | Plantillas (CRUD + DataTables server-side) | ✅ Terminado | ✅ PASS | 2026-08-26 | Jhon Javer | front-end manual | CFG-PLA-01 |
| 3 | Consentimientos informados (CRUD + PDF) | ✅ Terminado | ✅ PASS | 2026-08-26 | Jhon Javer | front-end manual | CFG-CON-01 |

### Sub-Módulo: WhatsApp Bot

| # | Funcionalidad | Estado | Resultado | Fecha | Responsable | Evidencia | Notas |
|---|---|---|---|---|---|---|---|
| 1 | Números por sede (CRUD) | ✅ Terminado | ✅ PASS | 2026-08-26 | Jhon Javer | front-end manual | CFG-WA-01; 3 tabs |
| 2 | Saludos del bot (CRUD) | ✅ Terminado | ✅ PASS | 2026-08-26 | Jhon Javer | front-end manual | CFG-WA-02; tab 2 |
| 3 | Productos por servicio (CRUD) | ✅ Terminado | ✅ PASS | 2026-08-26 | Jhon Javer | front-end manual | CFG-WA-03; tab 3 |

### Sub-Módulo: Helpdesk

| # | Funcionalidad | Estado | Resultado | Fecha | Responsable | Evidencia | Notas |
|---|---|---|---|---|---|---|---|
| 1 | Prioridades helpdesk (CRUD + orden + activo) | ✅ Terminado | ✅ PASS | 2026-08-26 | Jhon Javer | front-end manual | CFG-HD-01 |
| 2 | Estados helpdesk (solo lectura + export) | ✅ Terminado | ✅ PASS | 2026-08-26 | Jhon Javer | front-end manual | CFG-HD-02 |
| 3 | Categorías helpdesk (CRUD) | ✅ Terminado | ✅ PASS | 2026-08-26 | Jhon Javer | front-end manual | CFG-HD-03 |

### Automatizadas

| Suite | Resultado | Fecha | Notas |
|---|---|---|---|
| Ninguna ejecutada | ⬜ Sin iniciar | — | Módulo nuevo para QA |

---

## 3️⃣ CONTRATACIÓN

| Campo | Valor |
|---|---|
| **Componentes** | `contratoComponent.vue` (único componente completo) |
| **Funcionalidades** | CRUD contratos, plantilla de detalle e importar médicos-producto, cobertura, médicos asignados |

### Automatizadas

| Suite | Resultado | Fecha | Evidencia |
|---|---|---|---|
| Ciclo export→import (prueba real BD 102/102, C2 validada) | ✅ PASS | 2026-08-24 | Commit `varios`; valor_contrato=0 |

### Sub-Módulo: Contratos

| # | Funcionalidad | Estado | Resultado | Fecha | Responsable | Evidencia | Notas |
|---|---|---|---|---|---|---|---|
| 1 | Crear/editar contrato por sede | ✅ Terminado | ✅ PASS | 2026-08-24 | Jhon Javer | E2E real: fechas + médicos | CON-E2E-01 |
| 2 | Plantilla general → exportar/importar | ⛔ Retirada | — | 2026-10-06 | Jhon Javer | Módulo unificado a la plantilla de detalle | Eliminada junto a `ContratoPlantillaExport.php` y `ContratoProductoImport.php` |
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
| 4 | Forgot password (flujo completo) | ✅ Terminado | ✅ PASS | 2026-09-02 | Jhon Javer | front-end manual | ENV-06 |

### Sub-Módulo: Usuarios

| # | Funcionalidad | Estado | Resultado | Fecha | Responsable | Evidencia | Notas |
|---|---|---|---|---|---|---|---|
| 1 | CRUD usuarios (documento, reg. médico, módulos, permisos) | ✅ Terminado | ✅ PASS | 2026-09-02 | Jhon Javer | front-end manual | `usuarioComponent.vue` |
| 2 | Perfil propio (subir foto + firma digital) | ✅ Terminado | ✅ PASS | 2026-09-02 | Jhon Javer | front-end manual | `usuarioPerfil.vue` |

### Sub-Módulo: Roles

| # | Funcionalidad | Estado | Resultado | Fecha | Responsable | Evidencia | Notas |
|---|---|---|---|---|---|---|---|
| 1 | CRUD roles | ✅ Terminado | ✅ PASS | 2026-09-02 | Jhon Javer | front-end manual | `roleComponente.vue` |

### Sub-Módulo: Auditoría

| # | Funcionalidad | Estado | Resultado | Fecha | Responsable | Evidencia | Notas |
|---|---|---|---|---|---|---|---|
| 1 | Consulta de auditoría (evento, fechas, búsqueda libre) | ✅ Terminado | ✅ PASS | 2026-09-02 | Jhon Javer | front-end manual | `auditLogComponent.vue` |

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
| **Componentes** | `reporteProduccionComponent.vue` (Producción + Estudios PAC) |
| **Permisos** | `can('ecografias.reporte')` para módulo Ecografías |

### Sub-Módulo: Producción

| # | Funcionalidad | Estado | Resultado | Fecha | Responsable | Evidencia | Notas |
|---|---|---|---|---|---|---|---|
| 1 | Reporte producción detallada (RX/Tomografía/Mamografía/Resonancia) | ✅ Terminado | ✅ PASS | 2026-08-26 | Jhon Javer | front-end manual | REP-01; filtros: sede, quien asignó, médico, fechas |
| 2 | Reporte producción Ecografías (permiso controlado) | ✅ Terminado | ✅ PASS | 2026-08-26 | Jhon Javer | front-end manual | REP-02; `can('ecografias.reporte')` |

### Sub-Módulo: Estudios PAC

| # | Funcionalidad | Estado | Resultado | Fecha | Responsable | Evidencia | Notas |
|---|---|---|---|---|---|---|---|
| 1 | Reporte estudios tomados en PAC (filtro sede + fechas) | ✅ Terminado | ✅ PASS | 2026-08-26 | Jhon Javer | front-end manual | REP-PAC-01; fix: ReporteEstudiosPACRequest, toastMessage import |

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
| 3 | SAHI API URL typo fix (listar-productos-diagnosticos) | ✅ Terminado | ✅ PASS | 2026-09-22 | Jhon Javer | front-end manual | TR-01; mismo typo que Asignar Estudios |
| 4 | SAHI diagnosticos + Enfermedad Actual loading | ✅ Terminado | ✅ PASS | 2026-09-22 | Jhon Javer | front-end manual | TR-02; mismo fix que Asignar Estudios |

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

## 8️⃣ LIQUIDACIÓN

| Campo | Valor |
|---|---|
| **Componentes** | `liquidacionComponent.vue` (cálculo/exportación), `productividadComponent.vue` (corte de mes por médico) |
| **Backend** | `LiquidacionService` (calcular/registrarLectura/productividad), `LiquidacionController`, `LiquidacionExport`, modelo `LiquidacionLinea` |
| **Commits de construcción** | `67171ef6f` (cálculo) + `14cf686e6` (endpoint/rutas/permisos) + `b70c342af` (vista) + `6f144c82a` (ruta web/menú) + `67e54bfd5` + `8875a2267` (export) + `7213adb1f` (filtros contrato/médico) + `4e96c5463` (ignora fecha_ingreso) + `2e7bacdc1` (registro persistente por lectura) + `1ec4ee339` (productividad) — Fase 1 contabilidad médicos, 2026-09-30/10-01 |

### Automatizadas

| Suite | Resultado | Fecha | Notas |
|---|---|---|---|
| Verificación de implementación Fase 1: scripts PHP con rollback contra BD real (cálculo, API, guard D4, export), `pint`, `eslint`, `build` | ✅ PASS | 2026-09-30 / 2026-10-01 | Patrón Fase 0 (scripts en temp, no en repo); ejecutados al construir cada task |
| QA formal del módulo (manuales/automatizadas en este tracker) | ⬜ Sin iniciar | — | Pendiente de programar |

### Sub-Módulo: Cálculo de liquidación

| # | Funcionalidad | Estado | Resultado | Fecha | Responsable | Evidencia | Notas |
|---|---|---|---|---|---|---|---|
| 1 | Calcular líneas, exclusiones y totales por sede+rango | ⬜ Sin iniciar | — | — | — | — | Implementado 2026-09-30 (`67171ef6f`); QA formal pendiente |
| 2 | Filtros opcionales por contrato y médico | ⬜ Sin iniciar | — | — | — | — | Implementado 2026-10-01 (`7213adb1f`) |
| 3 | Exclusiones visibles con conteo (sin contrato, sin tarifa, fuera de detalle, anulada, sin asignado) | ⬜ Sin iniciar | — | — | — | — | Nunca silenciosas (spec Fase 1 §6) |
| 4 | Guard D4: solo el médico asignado puede registrar lectura (403); lectura sin asignado → 422 | ⬜ Sin iniciar | — | — | — | — | `EstudioService::leerEstudio()`; implementado 2026-09-30 |

### Sub-Módulo: Corte de mes (productividad)

| # | Funcionalidad | Estado | Resultado | Fecha | Responsable | Evidencia | Notas |
|---|---|---|---|---|---|---|---|
| 1 | Registro persistente por lectura en `liquidacion_lineas` (tarifa congelada) | ⬜ Sin iniciar | — | — | — | — | D6; implementado 2026-10-01 (`2e7bacdc1`); índices (sede_id, periodo) |
| 2 | Vista de productividad por médico y periodo | ⬜ Sin iniciar | — | — | — | — | `productividadComponent.vue`; implementado 2026-10-01 (`1ec4ee339`) |

### Sub-Módulo: Exportación

| # | Funcionalidad | Estado | Resultado | Fecha | Responsable | Evidencia | Notas |
|---|---|---|---|---|---|---|---|
| 1 | Exportar la liquidación a Excel (reutiliza `calcular()`) | ⬜ Sin iniciar | — | — | — | — | Implementado 2026-10-01 (`67e54bfd5` + fix cast `8875a2267`) |

> **Nota:** el módulo fue implementado completo (Fase 1 contabilidad médicos) y verificado con scripts contra BD real durante su construcción. Las filas anteriores registran el QA formal estilo tracker (como Estudios/Contratación), aún no ejecutado. El cierre de periodo con hash SHA256 (Fase 3 del plan de mejoras) fue **descartado por el dueño** (2026-10-13); diseño mínimo en memoria `obs-468` si se revive.

---

## 📎 ANEXOS

### A. Issues diferidos (no bloqueantes)

| ID | Descripción | Módulo | Riesgo | Sugerencia |
|---|---|---|---|---|
| DEF-01 | Dual-source Vue/DataTables en asignados+transcribir: mutaciones manuales pueden desincronizar la cache DT | Estudios | Medio | ✅ Cerrado 2026-10-08: se descartó la migración a server-side DataTables; se mantiene el patrón cliente estándar de los otros módulos (`registros` + `safeInitDataTable`/`safeDestroyDataTable` con reinicialización tras cada mutación). Verificado en navegador: asignados 804 registros / transcribir 73, paginación+botones+buscador, 0 errores consola |
| DEF-02 | Over-posting: `estudio-leerEstudio` y `estudio-guardarTranscripcion` envían objeto `registro` completo | Estudios | Medio | ✅ Cerrado 2026-10-08: `LeerEstudioRequest` + `GuardarTranscripcionRequest` con `validated()` (payloads frontend ya eran explícitos) |
| DEF-03 | Sin canal WhatsApp/SMS en notificaciones (solo email + portal) | Estudios | Bajo | Definir alcance con negocio |
| DEF-04 | Sin lock/concurrencia al leer: dos médicos pueden abrir el mismo estudio | Estudios | Medio | ✅ Cerrado 2026-10-08: reservas `estudio_locks` (2 min o `config_sedes.lock_min`) con heartbeat en lectura y asignación |
| DEF-05 | Docblocks faltantes en funciones auxiliares menores | Global | Bajo | ✅ Cerrado 2026-10-13: módulo Estudios documentado (2026-10-08) y resto de `app/` completado (2026-10-13, commit `c15b9369c`): 664 funciones en 207 archivos (Services, Controllers, Commands, Exports, Requests, Models, Resources, Helpers, Providers, Middleware, Traits, Rules, Notifications). Verificado con escáner token_get_all (0 pendientes) y `pint --test app` |
| DEF-06 | 50 warnings ESLint preexistentes (vars sin uso, catches vacíos) | Estudios | Bajo | ✅ Cerrado 2026-10-13: limpieza global `resources/js` 267→0 warnings (commit `b57ad1901`), incluyendo fix de bugs reales no-undef (refs sin declarar en `ecografiaFirmaConsentimientoComponent` y `FormattedTextEditor`, props sin prefijo en agenda, listener `onVisibilityChange` sin remover). `npx eslint resources/js` = 0 problems + `npm run build` ✓ |
| DEF-07 | `descargarPlantillaGeneral?contrato_id` conserva parámetro sin uso en UI | Contratación | Info | ✅ Cerrado 2026-10-06: la plantilla general fue retirada del módulo |
| DEF-08 | Avatar header usa `http://localhost:5179` → Mixed Content en HTTPS | Global | Cosmético | ✅ Cerrado 2026-10-08: el asset se referenciaba con `src="@/admin-wrap/..."` (alias Vite que en dev resuelve al dev server `localhost:5179`); migrado a ruta pública relativa `/admin-wrap/assets/images/users/avatar_default.png` con binding en runtime (`:src`) en `usuarioComponent.vue` y `usuarioPerfil.vue` para que Vite no lo trate como import. Build ✓ |

### B. Hallazgos de entorno

| ID | Hallazgo | Tipo | Acción |
|---|---|---|---|
| ENV-PACS | Túnel PACS intermitente (`10.35.47.3/pacsdb` + WADO `:8080`); estable al final → DZ-01/DZ-02 ejecutados con éxito | Entorno | Monitorear estabilidad |
| ENV-BROWSER | QA con browser-automation: Chromium patchright se instaló por CLI (`NODE_TLS_REJECT_UNAUTHORIZED=0` por proxy TLS corporativo); `page.evaluate` corre en mundo aislado → globals (`$`, `jQuery`) leen `undefined` aunque existan en la página | Herramienta | Verificar estado vía DOM o inyectar probes con `addScriptTag` (mundo principal) |
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
| 2026-10-13 | ESLint global resources/js (267→0) + build | ✅ PASS | `b57ad1901` |
| 2026-10-13 | Docblocks repo-wide app/ (664 funciones, escáner 0 + pint) | ✅ PASS | `c15b9369c` |
| 2026-09-22 | Estudios Quemador + SAHI + Asignar (MAN-\*) | ✅ 9/9 PASS | `e98ed8a2d`, `ad1ebf799`, `c5c2e1fe1`, `a834b1463`, `caa41a779`, `08d3f3934` |
| 2026-09-22 | Tele-radiología SAHI fixes | ✅ 2/2 PASS | `caa41a779`, `08d3f3934` |
| 2026-08-24 | Contratación E2E (usuario) | ✅ 1/1 | varios |
| 2026-08-24 | Estudios AUTO-A + AUTO-B | ✅ 28/28 | `31f1c67fe` + `b55cab9cb` |
| 2026-08-24 | Seguridad AUTH curl/lint/build | ✅ 8/8 | `3a7532334` |
| 2026-08-24 | Estudios MAN-\* headless | ✅ 7 PASS · ⚠️ 1 bloqueado-env · 0 FAIL | — |
| 2026-08-24 | MAN-AE-01 re-ejecutado con PACS activo | ✅ PASS | — |
| 2026-08-24 | MAN-DZ-01 + DZ-02 (túnel estable) | ✅ 2/2 PASS | `qa_dz01_final.mjs` + `qa_dz02.mjs` |
| 2026-08-25 | MAN-QC-03 quemado real (front-end) | ✅ PASS | — |
| 2026-09-02 | Seguridad MAN-\* front-end (6 funcionalidades) | ✅ 6/6 PASS | — |

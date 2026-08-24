# PRUEBAS — Módulo Estudios

| Campo | Valor |
|---|---|
| **Proyecto** | Lidiag (`C:\laragon\www\lidiag`) |
| **Módulo** | Estudios (`resources/js/components/modulos/estudios`) |
| **Fecha de ejecución** | 2026-08-24 |
| **Autor de las pruebas** | Jhon Javer Lozano Arce |
| **Commit base de fixes** | `31f1c67fe` + `b55cab9cb` |
| **Tipo** | Unitarias estáticas (aserciones sobre código) + Integración manual |

---

## 1. Alcance y metodología

Componentes bajo prueba (7 funcionalidades):

| # | Funcionalidad | Archivo(s) |
|---|---|---|
| 1 | Asignar estudios | `AsignarEstudioComponent.vue` (+ hijos `QuemadorCdComponent`, `DescargarZipComponent`) |
| 2 | Descarga ZIP | `DescargarZipComponent.vue` |
| 3 | Estudios asignados | `estudioAsignadosComponent.vue` |
| 4 | Leer estudio | `leerEstudioComponent.vue` |
| 5 | Notificaciones | `notificarEstudioComponent.vue` |
| 6 | Quemador de CD | `QuemadorCdComponent.vue` |
| 7 | Transcribir | `transcribirEstudioComponent.vue`, `audio-texto.vue` |

**Tres capas de verificación:**

1. **AUTOMÁTICA-A**: Build de producción Vite + ESLint del módulo.
2. **AUTOMÁTICA-B**: Aserciones estáticas (grep/regex) que validan que cada bug corregido quedó efectivamente corregido en el código fuente. Ejecutadas con PowerShell el 2026-08-24.
3. **MANUAL**: Protocolo de prueba funcional en navegador (`https://lidiag.test`) para QA humano.

Convención transversal validada: toasts con `toastMessage({heading, text, icon})`, confirmaciones con `confirmAction({title, text, icon})` retornando `Promise<boolean>`.

---

## 2. Resultados de la capa AUTOMÁTICA-A

| ID | Verificación | Comando | Resultado |
|---|---|---|---|
| AUTO-01 | Build producción sin errores | `npm run build` | ✅ PASS — `built in 1.13s` |
| AUTO-02 | ESLint módulo completo sin errores | `npx eslint "resources/js/components/modulos/estudios/*.vue"` | ✅ PASS — **0 errores**, 50 warnings preexistentes (vars sin uso heredadas) |

## 3. Resultados de la capa AUTOMÁTICA-B (regresión de fixes)

### 3.1 Transcribir / audio-texto.vue

| ID | Aserción | Resultado |
|---|---|---|
| AT-01 | El envío usa `/api/upload-audio` (antes `/upload-audio` → 404) | ✅ PASS |
| AT-02 | `audioBlob/audioUrl/microfono/recognizing` destructurados del composable (antes `ReferenceError`) | ✅ PASS |
| AT-03 | Catch del envío notifica al usuario con `getUserSafeError` (antes catch vacío = pérdida silenciosa) | ✅ PASS |
| TX-01 | Cero llamadas crudas `$.toast(` en todo el módulo (0 restantes) | ✅ PASS |

### 3.2 AsignarEstudioComponent.vue

| ID | Aserción | Resultado |
|---|---|---|
| AE-01 | Sin `data-dismiss` (botones × de quemador/ZIP usan `@click`) | ✅ PASS |
| AE-02 | `quitarProductoEstudio` usa `await confirmAction(...)` y splice directo (el borrado estaba roto) | ✅ PASS |
| AE-03 | `quitarDiagnosticoEstudio` ídem | ✅ PASS |
| AE-04 | `emitter.off("sedeSeleccionada")` en unmount (antes leak acumulando listeners) | ✅ PASS |
| AE-05 | Catálogos se cargan UNA sola vez al montar (antes doble fetch) | ✅ PASS |

### 3.3 QuemadorCdComponent.vue

| ID | Aserción | Resultado |
|---|---|---|
| QC-01 | `startPolling()` se invoca tras aceptar la orden de quemado (antes nunca se llamaba) | ✅ PASS |
| QC-02 | `emit("completado")` ocurre SOLO desde el polling cuando backend reporta `burned` (antes prematuro) | ✅ PASS |
| QC-03 | Condición duplicada `study_iuid \|\| study_iuid` eliminada | ✅ PASS |
| QC-04 | Progreso null-safe vía computed `progressPct` (evita `width: undefined%`) | ✅ PASS |

### 3.4 notificarEstudioComponent.vue

| ID | Aserción | Resultado |
|---|---|---|
| NE-01 | `limpiar()` resetea `email` | ✅ PASS |
| NE-02 | `limpiar()` resetea `email_reportar` y `direccion` (riesgo PHI) | ✅ PASS |
| NE-03 | `notificar()` valida formato de email antes del POST | ✅ PASS |
| NE-04 | Catch del portal con feedback para errores ≠ 402 (antes vacío) | ✅ PASS |

### 3.5 leerEstudioComponent.vue

| ID | Aserción | Resultado |
|---|---|---|
| LE-01 | Fallo en listado restaura `tablaVisible` (antes spinner infinito) | ✅ PASS |
| LE-02 | `abrirSidebar()` en `finally`: el panel abre aunque fallen diagnósticos/soportes | ✅ PASS |
| LE-03 | `visto()` revierte estado reactivo en fallo (antes divergencia DOM/estado) | ✅ PASS |
| LE-04 | `verImagen` del sidebar recibe 5 argumentos (spinner correcto por botón) | ✅ PASS |
| LE-05 | Sin `data-dismiss`; modal ayuda abre/cierra con handlers explícitos | ✅ PASS |

### 3.6 DescargarZipComponent.vue

| ID | Aserción | Resultado |
|---|---|---|
| DZ-01 | `onBeforeUnmount` detiene el polling (antes interval huérfano cada 1s) | ✅ PASS |
| DZ-02 | Polling termina tras 5 fallos consecutivos de red (antes infinito silencioso) | ✅ PASS |
| DZ-03 | Filename codificado con `encodeURIComponent` en la descarga | ✅ PASS |
| DZ-04 | Branch de error tiene prioridad sobre downloadReady (antes error invisible) | ✅ PASS |
| DZ-05 | `revokeObjectURL` diferido 2s (compatibilidad Firefox/Safari) | ✅ PASS |

**TOTAL AUTOMÁTICO-B: 26/26 aserciones PASS** (LE-05 falló en la primera corrida y fue corregido + re-verificado).

---

## 4. Suite MANUAL (protocolo QA en navegador)

Precondiciones globales: sesión con usuario médico (`123456`), sede seleccionada, estudios pendientes disponibles.

### 4.1 Asignar estudios (`/estudios` → Asignar)

| ID | Caso | Pasos | Esperado |
|---|---|---|---|
| MAN-AE-01 | Búsqueda por documento | Ingresar documento existente → Buscar | Tabla lista estudios; spinner desaparece |
| MAN-AE-02 | Validación sin sede | Vaciar `ST-sede` en sessionStorage → Buscar | Toast warning "Debes establecer la sede", sin petición |
| MAN-AE-03 | Agregar producto/diagnóstico | Abrir sidebar → agregar producto y diagnóstico | Filas aparecen en tablas locales |
| MAN-AE-04 | **Eliminar producto (regresión)** | Click 🗑 en producto → confirmar en swal | Fila ELIMINADA de la tabla local (bug: antes no eliminaba) |
| MAN-AE-05 | **Eliminar diagnóstico (regresión)** | Ídem con diagnóstico | Ídem |
| MAN-AE-06 | Guardar asignación | Completar obligatorios → Guardar | Toast éxito; fila actualizada a "agendado" |
| MAN-AE-07 | Un solo fetch de catálogos | DevTools Network al cargar página | Médicos/Prioridades/Productos/Diagnósticos: 1 request cada uno |
| MAN-AE-08 | Cambio de sede global | Cambiar sede en header | Lista se limpia y catálogos se recargan 1 vez (sin handlers duplicados) |

### 4.2 Descarga ZIP (modal desde Asignar)

| ID | Caso | Esperado |
|---|---|---|
| MAN-DZ-01 | Generación normal | Barra progresa → "ZIP listo" → botón habilitado |
| MAN-DZ-02 | Descarga exitosa | Archivo .zip baja; nombre correcto incluso con espacios |
| MAN-DZ-03 | **Fallo de red durante generación (regresión)** | Detener backend >5s durante progreso → aparece panel de ERROR con Reintentar (antes polling eterno) |
| MAN-DZ-04 | **Fallo de descarga visible (regresión)** | Simular 500 en download-zip → panel ERROR con Reintentar (antes seguía diciendo "listo") |
| MAN-DZ-05 | Navegar fuera durante generación | No quedan requests zip-status cada 1s (cleanup unmount) |

### 4.3 Estudios asignados

| ID | Caso | Esperado |
|---|---|---|
| MAN-EA-01 | Listar con filtros | Tabla pinta badges de prioridad/urgente correctamente |
| MAN-EA-02 | **Fallo de listado (regresión)** | Detener backend → toast de error visible (antes tabla vacía silenciosa) |
| MAN-EA-03 | Anular asignación | Confirmación swal → fila removida |
| MAN-EA-04 | Panel de detalle con soportes | Abre con datos; si falla carga complementaria muestra toast warning pero abre |

### 4.4 Leer estudio

| ID | Caso | Esperado |
|---|---|---|
| MAN-LE-01 | Abrir lectura | Sidebar abre SIEMPRE (aunque falle carga de soportes → toast warning); spinner del botón termina |
| MAN-LE-02 | Visor desde tabla vs sidebar | Ambos botones muestran spinner propio mientras cargan |
| MAN-LE-03 | Marcar visto OK | Texto cambia a azul; persiste tras refresh |
| MAN-LE-04 | **Marcar visto con fallo (regresión)** | Detener backend → toast error, texto vuelve a normal, botón reutilizable (antes quedaba trabado) |
| MAN-LE-05 | Dictado F2 + guardar audio (audio-texto) | Grabación inicia/detiene; audio preview visible; Guardar envía a `/api/upload-audio` (200) y emite `audio-saved` |
| MAN-LE-06 | Modal de ayuda comandos | Ícono life-ring abre modal; × y Close lo cierran |
| MAN-LE-07 | Auto-refresh toggle | Toasts info activado/desactivado; interval limpio al desmontar |

### 4.5 Notificaciones

| ID | Caso | Esperado |
|---|---|---|
| MAN-NE-01 | Notificar con email inválido | Toast warning "Ingrese un correo válido"; NO hay POST |
| MAN-NE-02 | Notificar exitoso | Toast éxito; modal cierra; formulario limpio |
| MAN-NE-03 | **PHI entre pacientes (regresión)** | Notificar paciente A → abrir paciente B sin email → campo email VACÍO (antes conservaba el de A) |
| MAN-NE-04 | Portal saldo insuficiente | Toast "Saldo insuficiente" (402) |
| MAN-NE-05 | Portal error genérico | Toast de error visible (antes silencioso) |

### 4.6 Quemador CD

| ID | Caso | Esperado |
|---|---|---|
| MAN-QC-01 | Listar unidades | Radios con unidades reales; selección default primera |
| MAN-QC-02 | Confirmación previa | Botón quemar pide swal antes de enviar orden |
| MAN-QC-03 | **Quemado real (regresión)** | Tras aceptar: estado "Quemando disco..." ~70%; `completado` SOLO cuando burn-status reporte `burned`; barra verde 100% |
| MAN-QC-04 | Burn fallido | Estado Fallido en rojo con mensaje; botón rehabilitado |
| MAN-QC-05 | Unidad desconectada | Al re-listar, selección inválida se resetea (canBurn false) |

### 4.7 Transcribir

| ID | Caso | Esperado |
|---|---|---|
| MAN-TR-01 | Listar pendientes | Tabla carga; título dice "Transcribir estudio" (typo corregido) |
| MAN-TR-02 | **Fallo de listado (regresión)** | Backend caído → toast error visible |
| MAN-TR-03 | Guardar con errores Laravel 422 | Mensajes inline bajo inputs aparecen (se maneja 400 Y 422) |
| MAN-TR-04 | Prefill de plantilla | Si estudio no trae lectura, NO aparece literal "undefined" |
| MAN-TR-05 | Cambio de sede repetido | Una sola búsqueda por cambio (sin listeners duplicados) |

---

## 5. Issues conocidos diferidos (no bloquean cierre)

| ID | Descripción | Riesgo | Sugerencia futura |
|---|---|---|---|
| DEF-01 | Dual-source Vue/DataTables en asignados+transcribir: `safeRemoveDataTableRow` + `splice` simultáneos pueden desincronizar cache de DT en búsquedas posteriores | Medio | Elegir un dueño del DOM: reinicializar tabla tras cada mutación o migrar a data source AJAX |
| DEF-02 | Over-posting: `estudio-leerEstudio` y `estudio-guardarTranscripcion` envían el objeto `registro` completo | Medio | Definir payload explícito + FormRequest backend |
| DEF-03 | Notificaciones solo email + portal; sin canal WhatsApp/SMS pese a mostrar `tel` | Bajo | Definir alcance de canales con negocio |
| DEF-04 | Sin lock/concurrencia al leer: dos médicos pueden abrir el mismo estudio | Medio | Token de reserva (`updated_at` o columna `leyendo`) |
| DEF-05 | Docblocks faltantes/incompletos en funciones auxiliares menores de varios componentes | Bajo | Pasada de documentación dedicada |
| DEF-06 | 50 warnings ESLint preexistentes (vars sin uso, catches defensivos vacíos) | Bajo | Limpieza incremental |
| DEF-07 | `descargarPlantillaGeneral?contrato_id` (contratación) conserva parámetro opcional hoy sin uso en UI | Info | Mantener o remover en próxima iteración |

---

## 6. Conclusión

- **Automatizado**: 2/2 capas A + 26/26 aserciones B en PASS.
- **Manual**: protocolo de 33 casos definido; los casos marcados *(regresión)* corresponden 1:1 a bugs corregidos en esta iteración y son prioritarios para QA.
- El módulo queda consistente con las convenciones del proyecto (toasts/confirmaciones objeto, endpoints `/api/*`, limpieza de listeners/timers, feedback explícito en fallos).

*Documento generado como cierre del ciclo de auditoría y estabilización del módulo Estudios.*

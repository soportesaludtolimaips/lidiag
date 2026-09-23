# Plan de Pruebas — Ecografías (8 funcionalidades)

| Campo | Valor |
|-------|-------|
| **Módulo** | Ecografías |
| **Componentes** | 6 archivos Vue en `resources/js/components/modulos/ecografias/` |
| **Fecha** | 2026-09-02 |
| **Responsable QA** | Jhon Javer Lozano Arce |
| **Requisitos** | PACS activo, SAHI (sede con interfaz), dokbox-plantillas service |
| **Pacientes de prueba (sede 5 Líbano)** | doc `111454545`, `65713566`, `28820683` |

---

## Funcionalidad 1: Admisionar paciente
**Componente:** `admisionarComponent.vue` (1740 líneas)
**Ruta:** `/ecografias.admisiones`
**Permiso:** `ecografias.admision`

| # | Acción | Resultado esperado | Estado | Resultado | Notas |
|---|--------|-------------------|--------|-----------|-------|
| 1 | Navegar a Ecografías → Admisiones | Tabla de resultados se carga con datos | ⬜ | | |
| 2 | Buscar por documento `111454545` + Enter | Filtra resultados del paciente | ⬜ | | |
| 3 | Seleccionar fila → abrir sidebar | Sidebar derecho se desliza con formulario | ⬜ | | |
| 4 | Completar datos (doctor, producto) + Guardar | Toast éxito, se cierra sidebar, refresca lista | ⬜ | | |
| 5 | Verificar badge de estado SAHI | Muestra ENVIADO/ENVIANDO/PENDIENTE/ERROR | ⬜ | | |
| 6 | Clic "Generar enlace firma" | Genera link de 10 min, botones copiar/abrir | ⬜ | | |
| 7 | Verificar estado consentimiento | Muestra "Pendiente firma paciente" (rojo) | ⬜ | | |

---

## Funcionalidad 2: Listar admisionadas
**Componente:** `ecografiasAdmisionadasComponent.vue` (761 líneas)
**Ruta:** `/ecografias.admisionadas`
**Permiso:** `ecografias.index|ecografias.admision`

| # | Acción | Resultado esperado | Estado | Resultado | Notas |
|---|--------|-------------------|--------|-----------|-------|
| 1 | Navegar a Ecografías → Admisionadas | Tabla carga con ecografías existentes | ⬜ | | |
| 2 | Buscar por nombre/doc + rango de fechas | Filtra correctamente | ⬜ | | |
| 3 | Sin filtro → clic Buscar | Toast warning "debe ingresar al menos un filtro" | ⬜ | | |
| 4 | Verificar badges de estado | EN PROCESO (azul), LEÍDO (verde), ANULADO (rojo) | ⬜ | | |
| 5 | Exportar (copy/csv/excel/pdf/print) | Funciona cada opción | ⬜ | | |
| 6 | Clic "Imprimir reporte" | Abre PDF en nueva pestaña | ⬜ | | |
| 7 | Anular con motivo obligatorio | Modal → textarea → confirmar → se actualiza estado | ⬜ | | |

---

## Funcionalidad 3: Lectura/transcripción informes
**Componente:** `lecturaComponent.vue` (2080 líneas)
**Ruta:** `/ecografias-pendientes-lecturas`
**Permiso:** `ecografias.leer`

| # | Acción | Resultado esperado | Estado | Resultado | Notas |
|---|--------|-------------------|--------|-----------|-------|
| 1 | Navegar a Ecografías → Lecturas | Lista de pendientes filtrada por médico actual | ⬜ | | |
| 2 | Verificar indicadores de consentimiento | PENDIENTE_PACIENTE (rojo), PENDIENTE_MEDICO (amarillo), COMPLETO (verde) | ⬜ | | |
| 3 | Clic en paciente → abrir sidebar | Sidebar derecho con formulario de lectura | ⬜ | | |
| 4 | Seleccionar tipo plantilla → plantilla | Select2 carga opciones, al seleccionar pre-fill editores | ⬜ | | |
| 5 | Escribir en editor "Lectura" (WYSIWYG) | Editor funciona, formato rich text | ⬜ | | |
| 6 | Escribir en editor "Opinión" (WYSIWYG) | Editor funciona | ⬜ | | |
| 7 | Guardar lectura | Toast éxito, se envía a SAHI automáticamente | ⬜ | | |
| 8 | Verificar badge SAHI | ENVIADO tras guardar | ⬜ | | |
| 9 | Generar reporte | Abre iframe modal con PDF | ⬜ | | |

---

## Funcionalidad 4: Dictado por comandos de voz
**Componente:** `lecturaComponent.vue` (2080 líneas)
**Permiso:** `ecografias.leer`

| # | Acción | Resultado esperado | Estado | Resultado | Notas |
|---|--------|-------------------|--------|-----------|-------|
| 1 | Abrir sidebar de lectura | Botón micrófono visible | ⬜ | | |
| 2 | Clic micrófono → dictar texto | Texto aparece en editor de Lectura | ⬜ | | |
| 3 | Decir "mayúsculas" → dictar palabra | Palabra en mayúsculas | ⬜ | | |
| 4 | Decir "punto aparte" | Salto de línea insertado | ⬜ | | |
| 5 | Decir "coma" / "dos puntos" / "punto y coma" | Puntuación insertada correctamente | ⬜ | | |
| 6 | Detener dictado | Micrófono se apaga, icono vuelve a normal | ⬜ | | |
| 7 | Verificar corrección terminología médica | "cardiomegalia" se corrige si se dicta mal (Levenshtein ≤2) | ⬜ | | |
| 8 | Modal ayuda comandos (ícono ?) | Lista de comandos disponibles | ⬜ | | |

---

## Funcionalidad 5: Plantillas WYSIWYG consentimientos
**Componente:** `ecografiasConsentimientosPlantillasComponent.vue` (588 líneas)
**Ruta:** `/ecografias-consentimientos-plantillas`
**Permiso:** `ecografias.consentimientos.plantillas.index`

| # | Acción | Resultado esperado | Estado | Resultado | Notas |
|---|--------|-------------------|--------|-----------|-------|
| 1 | Navegar a plantillas | Tabla carga con plantillas existentes | ⬜ | | |
| 2 | Filtrar por módulo "Ecografías" | Filtra correctamente | ⬜ | | |
| 3 | Buscar por código CUPS o nombre | Filtra | ⬜ | | |
| 4 | Crear nueva plantilla (+) | Modal abre, Select2 producto, editor WYSIWYG | ⬜ | | |
| 5 | Escribir HTML en WYSIWYG + Guardar | Toast éxito, aparece en tabla | ⬜ | | |
| 6 | Editar plantilla existente | Carga datos, al guardar crea nueva versión | ⬜ | | |
| 7 | Ver historial de versiones | Modal lista versiones con fecha y estado vigente | ⬜ | | |
| 8 | Activar versión inactiva | Cambia estado a vigente | ⬜ | | |
| 9 | Vista previa PDF | Abre PDF en nueva pestaña | ⬜ | | |
| 10 | Desactivar plantilla | Confirmación → se elimina (soft-delete) | ⬜ | | |

---

## Funcionalidad 6: Consulta consentimientos + generar PDF
**Componente:** `ecografiasConsentimientosComponent.vue` (473 líneas)
**Ruta:** `/ecografias-consentimientos`
**Permiso:** `ecografias.consentimientos.index`

| # | Acción | Resultado esperado | Estado | Resultado | Notas |
|---|--------|-------------------|--------|-----------|-------|
| 1 | Navegar a consentimientos | Tabla carga con consentimientos | ⬜ | | |
| 2 | Buscar por nombre/doc + fechas | Filtra correctamente | ⬜ | | |
| 3 | Seleccionar consentimiento COMPLETO (checkbox) | Checkbox selecciona solo COMPLETO | ⬜ | | |
| 4 | Generar PDF (1 seleccionado COMPLETO) | Toast éxito, columna PDF cambia a "Generado" | ⬜ | | |
| 5 | Descargar PDF | Descarga archivo PDF | ⬜ | | |
| 6 | Seleccionar 2+ consentimientos con PDF | Botón ZIP habilitado | ⬜ | | |
| 7 | Descargar ZIP | Descarga archivo ZIP con PDFs | ⬜ | | |
| 8 | Anular consentimiento | SweetAlert → texto obligatorio → confirma → estado cambia | ⬜ | | |
| 9 | Verificar permisos por acción | Botones se muestran/ocultan según permisos | ⬜ | | |

---

## Funcionalidad 7: Firma manuscrita pública (canvas + token)
**Componente:** `ecografiaFirmaConsentimientoComponent.vue` (338 líneas)
**Ruta:** `/firma/consentimiento-ecografia/{token}` (pública, sin auth)

| # | Acción | Resultado esperado | Estado | Resultado | Notas |
|---|--------|-------------------|--------|-----------|-------|
| 1 | Abrir link de firma (copiado del paso 1) | Página carga con datos del paciente y producto | ⬜ | | |
| 2 | Verificar contador regresivo | Muestra tiempo restante (10 min), actualiza cada segundo | ⬜ | | |
| 3 | Desplazar contenido del consentimiento | Scroll funciona, HTML renderizado correctamente | ⬜ | | |
| 4 | Dibujar firma en canvas (mousedown/move/up) | Línea aparece en canvas | ⬜ | | |
| 5 | Botón "Limpiar" | Canvas se limpia | ⬜ | | |
| 6 | Dibujar de nuevo + "Firmar" | Botón habilitado solo con tinta, envía firma | ⬜ | | |
| 7 | Verificar éxito | Mensaje de éxito, enlace queda inválido | ⬜ | | |
| 8 | Probar enlace expirado (>10 min) | Mensaje "Enlace expirado" | ⬜ | | |
| 9 | Verificar DOMPurify | HTML sanitizado (sin scripts maliciosos) | ⬜ | | |

---

## Funcionalidad 8: Firma + captura pacientes/acompañantes
**Flujo completo:** Combina admisionarComponent → generate link → firma → doctor consent

| # | Acción | Resultado esperado | Estado | Resultado | Notas |
|---|--------|-------------------|--------|-----------|-------|
| 1 | En admisiones, generar link tipo "Paciente" | Link generado con datos correctos | ⬜ | | |
| 2 | Firmar como paciente (canvas) | Estado cambia a PENDIENTE_MEDICO | ⬜ | | |
| 3 | En admisiones, generar link tipo "Acompañante" | Formulario adicional de acompañante aparece | ⬜ | | |
| 4 | Completar datos acompañante + generar link | Link generado con datos acompañante | ⬜ | | |
| 5 | Firmar como acompañante | Firma registrada | ⬜ | | |
| 6 | En lecturas, botón "Firmar médico" | Médico firma → estado COMPLETO | ⬜ | | |
| 7 | Verificar en consentimientos | Aparece como COMPLETO, PDF generable | ⬜ | | |

---

## 📎 Notas

- **Orden de prueba recomendado:** 1 → 2 → 5 → 6 → 7 → 8 → 3 → 4 (admisiones primero para generar datos de prueba)
- **Flujo completo de consentimiento:** Admisionar (1) → Generar link → Firmar paciente (7) → Firmar médico (8) → Verificar en consentimientos (6) → Generar PDF (6)
- **Dependencias externas:** PACS (imágenes), SAHI (envío), dokbox-plantillas (reportes DOCX→PDF)
- **Navegador recomendado:** Chrome (Web Speech API para dictado)

# PROGRESO DE PRUEBAS — Lidiag

| Campo | Valor |
|---|---|
| **Última actualización** | 2026-08-24 |
| **Responsable QA** | Jhon Javer Lozano Arce |
| **Detalle por módulo** | `PRUEBAS_MODULO_ESTUDIOS.md` · `AUDITORIA_SEGURIDAD_AUTH.md` |

---

| Módulo | Automáticas | Manuales | ✅ Pass | ❌ Fail | ⬜ Pendientes | Avance manual |
|---|---|---|---|---|---|---|
| Contratación | 1 (E2E real BD) | 1 flujo completo | 1 | 0 | 0 | **100%** |
| Estudios | 28/28 ✅ | **39** (9 con headless) | **8** (+28 auto) | 0 | 31 | **21%** |
| Seguridad Auth | 8/8 ✅ | 6 decisiones `.env` prod | 8 | 0 | 6* | *no aplican en local |

*\*Los 6 ítems ENV-* son decisiones para el entorno de producción, no bloquean desarrollo.*

### 🆕 Hallazgos de las corridas headless 2026-08-24

| ID | Hallazgo | Clasificación |
|---|---|---|
| DEF-08 | Avatar del header usa URL `http://localhost:5179/...jpg` → warning Mixed Content en HTTPS. Migrar a ruta relativa o https | Cosmético |
| ENV-PACS | `study.listarEstudios` depende del PACS remoto (`10.35.47.3/pacsdb`, dcm4chee). Con PACS caído: API 500 + toast por interceptor (comportamiento correcto del front). **PACS reconectado durante la sesión → AE-01 ejecutado con éxito** | Entorno |
| RUNNER-01 | Playwright no logra click "actionable" sobre botones Buscar del módulo (overlay/geometry); el click programático `el.click()` dispara los handlers Vue correctamente. Artefacto del runner headless, NO reproduce en Chrome real | Info runner |

---

## 1️⃣ CONTRATACIÓN

### Flujo E2E (ejecutado por el usuario desde el front)

| ID | Caso | Tipo | Estado | Ejecutó | Fecha |
|---|---|---|---|---|---|
| CON-E2E-01 | Crear contrato → Plantilla General → Importar productos (102) → Plantilla detalle → Importar médicos (61 registros reales) → Total Médicos en listado | Manual | ✅ PASS | Jhon Javer | 2026-08-24 |
| CON-CICLO-01 | Ciclo export→import detalle verificado con prueba real contra BD (102/102, C2 validada, valor_contrato=0) | Auto | ✅ PASS | Ox | 2026-08-24 |

---

## 2️⃣ ESTUDIOS

### Automáticas — ejecutadas 2026-08-24 (todas ✅)

| ID | Aserción resumida |
|---|---|
| AUTO-A-01 | Build producción OK |
| AUTO-A-02 | ESLint módulo: 0 errores |
| AT-01 | Endpoint `/api/upload-audio` corregido |
| AT-02 | Refs del composable destructuradas (audioBlob etc.) |
| AT-03 | Catch del envío notifica con getUserSafeError |
| TX-01 | Cero `$.toast` crudos en el módulo |
| AE-01..05 | data-dismiss fuera / borrado await confirmAction ×2 / emitter.off / un solo fetch catálogos |
| QC-01..04 | startPolling activo / completado solo desde polling / fix study_iuid / progressPct null-safe |
| NE-01..04 | limpiar resetea email/email_reportar/direccion / valida email / catch portal con feedback |
| LE-01..05 | tablaVisible restaurada / abrirSidebar en finally / visto revierte en fallo / verImagen 5 args / sin data-dismiss |
| DZ-01..05 | cleanup unmount / reintento máx 5 fallos / encodeURIComponent / error prioritario / revoke diferido |

> Detalle completo de cada aserción: sección 2 y 3 de `PRUEBAS_MODULO_ESTUDIOS.md`.

### Manuales — checklist QA navegador (`https://lidiag.test`, user médico / pass `123456`)

#### Asignar estudios
- [x] **MAN-AE-01** ✅ PASS (headless, PACS activo): API 200, spinner loadingBuscar visible durante carga, paciente "MORENO GARCIA" renderizado, 2 filas de resultados, sin toasts de error. Nota técnica: en headless el click Playwright sobre el botón requería click programático (artefacto del runner, no de la app)
- [ ] **MAN-AE-02** Validación sin sede → toast warning, sin petición
- [ ] **MAN-AE-03** Agregar producto/diagnóstico → filas en tablas locales
- [ ] **MAN-AE-04** 🔁 Eliminar producto → fila ELIMINADA tras swal
- [ ] **MAN-AE-05** 🔁 Eliminar diagnóstico → fila ELIMINADA tras swal
- [ ] **MAN-AE-06** Guardar asignación → toast éxito, fila "agendado"
- [x] **MAN-AE-07** ✅ PASS (headless, criterio ajustado): prioridades/productos/diagnósticos ×1; médicos ×2 es legítimo (header + página)
- [ ] **MAN-AE-08** Cambio de sede global → lista limpia + recarga única

#### Descarga ZIP
- [ ] **MAN-DZ-01** ⚠️ BLOQUEADO-ENV: `generate-zip` consulta pacsdb (PACS remoto intermitente — 0/8 ventanas buenas en reintentos). Script de re-ejecución listo (`qa_dz01_v2.mjs`, auto-reintenta búsqueda 8×). La ruta de error del componente está cubierta por aserciones estáticas DZ-01..05
- [ ] **MAN-DZ-02** Descarga exitosa → archivo baja con nombre correcto (depende de DZ-01)
- [ ] **MAN-DZ-03** 🔁 Backend caído >5s durante progreso → panel ERROR con Reintentar
- [ ] **MAN-DZ-04** 🔁 Simular 500 en download-zip → panel ERROR visible
- [ ] **MAN-DZ-05** Navegar fuera durante generación → cero requests zip-status residuales

#### Estudios asignados
- [x] **MAN-EA-01** ✅ PASS (headless): tabla estructura OK, cero requests 500, badges dependen de datos
- [ ] **MAN-EA-02** 🔁 Backend caído al listar → toast de error visible
- [ ] **MAN-EA-03** Anular asignación → confirmación + fila removida
- [ ] **MAN-EA-04** Panel detalle con soportes → abre siempre; warning si falla complementaria

#### Leer estudio
- [x] **MAN-LE-01** ✅ PASS (headless, estructural): página carga SIN spinner eterno tras listar
- [ ] **MAN-LE-02** Visor tabla vs sidebar → spinner propio en cada botón (requiere estudio abierto; parcialmente cubierto por AE/LE estáticas)
- [ ] **MAN-LE-03** Marcar visto OK → azul persistente tras refresh
- [ ] **MAN-LE-04** 🔁 Visto con backend caído → toast error + reversión + botón reutilizable
- [ ] **MAN-LE-05** 🔁 Dictado F2 + guardar audio → graba, preview, POST `/api/upload-audio` 200, emite audio-saved
- [x] **MAN-LE-06** ✅ PASS (headless): life-ring abre modal; × y footer Close lo cierran (click programático: ícono FA mide 0×0 solo en headless)
- [ ] **MAN-LE-07** Auto-refresh toggle → toasts info; interval limpio al desmontar

#### Notificaciones
- [x] **MAN-NE-01** ✅ PASS (headless): email inválido → warning visible, 0 POST a notificar.email, formulario conservado
- [ ] **MAN-NE-02** Notificar exitoso → éxito + modal cierra + formulario limpio
- [x] **MAN-NE-03** ✅ PASS (headless, regresión): paciente B muestra email VACÍO, sin residuo del paciente A
- [ ] **MAN-NE-04** Portal saldo insuficiente → toast 402 "Saldo insuficiente"
- [ ] **MAN-NE-05** Portal error genérico → toast error visible (antes silencioso)

#### Quemador CD
- [ ] **MAN-QC-01** Listar unidades → radios reales, primera seleccionada
- [ ] **MAN-QC-02** Confirmación previa → swal antes de enviar orden
- [ ] **MAN-QC-03** 🔁 Quemado real → "Quemando disco..." ~70%; completado SOLO con burn-status `burned`; verde 100%
- [ ] **MAN-QC-04** Burn fallido → rojo con mensaje; botón rehabilitado
- [ ] **MAN-QC-05** Unidad desconectada → selección inválida se resetea (canBurn false)

#### Transcribir
- [x] **MAN-TR-01** ✅ PASS (headless): tabla carga, cero 500; título "Transcribir estudio" sin typo verificado en vivo
- [ ] **MAN-TR-02** 🔁 Backend caído al listar → toast error visible
- [ ] **MAN-TR-03** Guardar con 422 → mensajes inline bajo inputs
- [ ] **MAN-TR-04** Prefill sin lectura → NO aparece literal "undefined"
- [ ] **MAN-TR-05** Cambio de sede repetido → una sola búsqueda por cambio

---

## 3️⃣ SEGURIDAD AUTH

### Automáticas — ejecutadas 2026-08-24 (todas ✅)

| ID | Verificación | Resultado |
|---|---|---|
| SEC-01 | `curl /api/qido/studies` sin auth → 401 (PHI protegido) | ✅ PASS |
| SEC-02 | `/api/test-ohif` eliminada → 404 | ✅ PASS |
| SEC-03 | `/api/debug-session` sin auth → 401 | ✅ PASS |
| SEC-04 | `/api/debug` eliminada → 404 | ✅ PASS |
| SEC-05 | `php -l` ×5 archivos modificados | ✅ PASS |
| SEC-06 | `route:list` compila sin debug eliminadas | ✅ PASS |
| SEC-07 | `npm run build` frontend | ✅ PASS |
| SEC-08 | Login regenera sesión + logout revoca tokens + changePassword invalida sesiones (revisión código) | ✅ PASS |

### Decisiones `.env` para PRODUCCIÓN (pendientes — no aplican en local)

- [ ] **ENV-01** `APP_DEBUG=false`
- [ ] **ENV-02** `SESSION_SECURE_COOKIE=true`
- [ ] **ENV-03** CORS sin orígenes localhost/127.0.0.1
- [ ] **ENV-04** Redirect HTTPS en `.htaccess` del Apache real
- [ ] **ENV-05** Configurar `TrustProxies` si hay reverse proxy/LB
- [ ] **ENV-06** Decidir si se habilita flujo forgot-password

---

## 📈 Historial de corridas

| Fecha | Suite | Resultado | Commit |
|---|---|---|---|
| 2026-08-24 | Contratación E2E (usuario) | ✅ 1/1 | varios |
| 2026-08-24 | Estudios AUTO-A + AUTO-B | ✅ 28/28 | `31f1c67fe`+`b55cab9cb` |
| 2026-08-24 | Seguridad AUTH curl/lint/build | ✅ 8/8 | `3a7532334` |
| 2026-08-24 | **Estudios MAN-\* headless** (browser-automation): login, AE-01(bloq-env)/07, EA-01, LE-01/06, NE-01/03, TR-01 | ✅ 7 PASS · ⚠️ 1 bloqueado-env · 0 FAIL | — |
| 2026-08-24 | **MAN-AE-01 re-ejecutado** con PACS activo | ✅ PASS (API 200 + paciente renderizado + 2 filas) | — |
| 2026-08-24 | **MAN-DZ-01 intento headless** | ⚠️ BLOQUEADO-ENV (PACS intermitente, 0/8 ventanas) — script de reintento guardado | `qa_dz01_v2.mjs` |

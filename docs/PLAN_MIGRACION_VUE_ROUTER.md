# Plan de Migración a Vue Router — LIDiag SPA

Este documento contiene la hoja de ruta integral, técnica y exhaustiva para migrar la arquitectura de interfaz de usuario de **LIDiag** desde el modelo actual MPA (*Multi-Page Application* con renderizado de vistas Blade e islas Vue 3) hacia una **Single Page Application (SPA)** impulsada por **Vue Router 4**, preservando la seguridad de Spatie Permissions, la compatibilidad con el tema AdminWrap y la operatividad de los servicios clínicos (PACS, Audio/Whisper, SAHI).

---

## Leyenda de Estados de Control

| Insignia | Estado | Descripción |
| :---: | :--- | :--- |
| 🔴 | **Sin Iniciar** | Tarea planificada y especificada, pendiente de ejecución. |
| 🟡 | **Iniciado** | Tarea actualmente en desarrollo o pruebas activas. |
| 🟢 | **Terminado** | Tarea completada, validada funcionalmente y verificada con linter/build. |

---

## Resumen Global del Progreso

```
Progreso General: [■■■■■■□□□□] 60%
- Tareas Totales: 36
- Terminadas: 18
- En Progreso: 1
- Sin Iniciar: 17
```

---

## Desglose de Fases de Migración

### Fase 0: Auditoría Técnica y Diagnóstico de Viabilidad
> **Objetivo:** Analizar dependencias, scripts de AdminWrap (jQuery), rutas web y patrones de props de Blade.

| Estado | Tarea | Componentes / Archivos Afectados | Responsable / Notas |
| :---: | :--- | :--- | :--- |
| 🟢 | **0.1 Auditoría de dependencias frontend** | `package.json` | `vue-router: ^4.1.6` ya instalado en el proyecto. |
| 🟢 | **0.2 Mapeo de vistas Blade y componentes raíz** | `routes/web.php`, `resources/js/app.js` | 82 componentes totales, 48 páginas async identificadas. |
| 🟢 | **0.3 Diagnóstico de acoplamiento a Blade & jQuery** | `layouts/app.blade.php`, `sidebar.blade.php` | Identificados scripts de AdminWrap (`sidebarmenu.js`, `custom.js`). |

---

### Fase 1: Capa de Estado Reactivo y Autenticación (`useAuth`)
> **Objetivo:** Reemplazar la inyección manual de props de Blade (`:usuarioactual`, `:permisos`) por un composable reactivo centralizado en Vue.

| Estado | Tarea | Componentes / Archivos Afectados | Notas de Implementación |
| :---: | :--- | :--- | :--- |
| 🟢 | **1.1 Inicialización de estado en el Layout** | `resources/views/layouts/app.blade.php` | Exponer `window.__INITIAL_STATE__ = { user, permissions, roles, sedes }`. |
| 🟢 | **1.2 Crear Composable `useAuth`** | `resources/js/composables/useAuth.js` | Implementar reactividad para `user`, `permissions`, `roles` y helper `can(permission)`. |
| 🟢 | **1.3 Integración de contexto de Sede y Médico** | `resources/js/composables/useAuth.js`, `userProfileAndSearchComponent.vue` | Sincronización reactiva con `sessionStorage` y `localStorage` implementada. |
| 🟢 | **1.4 Endpoint de refresco de sesión** | `resources/js/composables/useAuth.js` | Método `fetchUser()` con fallback a `/api/user`. |

---

### Fase 2: Configuración del Core de Vue Router 4
> **Objetivo:** Estructurar el enrutador en el cliente con historial HTML5, guardias de navegación y metadatos clínicos.

| Estado | Tarea | Componentes / Archivos Afectados | Notas de Implementación |
| :---: | :--- | :--- | :--- |
| 🟢 | **2.1 Crear instancia del Router** | `resources/js/router/index.js` | `createRouter` con `createWebHistory()` y scroll restoration. |
| 🟢 | **2.2 Configurar Guardia Global (`beforeEach`)** | `resources/js/router/index.js` | Validación de `to.meta.permission` con `useAuth().can()`. Redirección a `/403`. |
| 🟢 | **2.3 Soporte de Títulos y Scroll Restoration** | `resources/js/router/index.js` | `document.title = `${to.meta.title} — LIDiag`` configurado. |
| 🟢 | **2.4 Montar Router en la App Vue** | `resources/js/app.js` | `app.use(router)` integrado antes de `app.mount("#app")`. |
| 🟢 | **2.5 Vistas de Estado 404 y 403** | `NotFoundComponent.vue`, `ForbiddenComponent.vue` | Componentes SPA creados para rutas inexistentes o no autorizadas. |
| 🟢 | **2.6 Mapeo Completo de 48 Rutas** | `resources/js/router/routes.js` | Rutas asíncronas registradas con Vite code-splitting y meta permissions. |

---

### Fase 3: Construcción del Shell SPA Reactivo
> **Objetivo:** Desacoplar el Layout de Blade y convertir el Sidebar y Header en componentes Vue interactivos.

| Estado | Tarea | Componentes / Archivos Afectados | Notas de Implementación |
| :---: | :--- | :--- | :--- |
| 🟢 | **3.1 Crear `AppLayout.vue`** | `resources/js/layouts/AppLayout.vue` | Contenedor principal con Header, Sidebar, `<router-view>` y Footer. |
| 🟢 | **3.2 Migrar Menú Lateral a `AppSidebar.vue`** | `resources/js/layouts/AppSidebar.vue` | 561 líneas de `sidebar.blade.php` portadas a Vue con `v-if="can('...')"` y `<router-link>`. |
| 🟢 | **3.3 Reemplazar lógica jQuery de Menú** | `resources/js/layouts/AppSidebar.vue` | Acordeones reactivos en Vue con auto-apertura según la ruta activa. |
| 🟢 | **3.4 Header y Perfil en SPA** | `resources/js/layouts/AppHeader.vue` | Toggles de mini-sidebar y móvil reactivos; integración de command palette y perfil. |

---

### Fase 4: Adaptador de Laravel Web y Enrutamiento Híbrido
> **Objetivo:** Configurar el fallback de Laravel respetando rutas exclusivas de servidor (Auth, descargas, visores externos).

| Estado | Tarea | Componentes / Archivos Afectados | Notas de Implementación |
| :---: | :--- | :--- | :--- |
| 🟢 | **4.1 Blindar rutas excluidas del SPA** | `routes/web.php` | Rutas `/login`, `/logout`, `/password/*`, descargas y `/firma/*` conservadas en Blade. |
| 🟢 | **4.2 Definir Entrada y Fallback SPA** | `routes/web.php` | Ruta `Route::get('/spa/{any?}', ...)` montada en `layouts.app`. |
| 🟢 | **4.3 Soporte Híbrido en Layout Maestro** | `resources/views/layouts/app.blade.php` | Directiva `@hasSection('content')` para renderizar MPA o `<app-layout />` sin conflictos. |

---

### Fase 5: Migración Piloto (Módulos Críticos)
> **Objetivo:** Probar y validar la navegación SPA en 3 módulos de alto impacto clínico.

| Estado | Tarea | Componentes / Archivos Afectados | Criterio de Aceptación |
| :---: | :--- | :--- | :--- |
| 🔴 | **5.1 Piloto 1: Dashboard Principal** | `dashboardComponent.vue` (`/dashboard`) | KPIs y filtros responden inmediatamente; no recarga scripts. |
| 🔴 | **5.2 Piloto 2: Admisión de Pacientes** | `admisionComponent.vue` (`/pacientes/admision`) | Formularios con Select2 reactivo; navegación ágil sin perder estado. |
| 🔴 | **5.3 Piloto 3: Tele-Radiología Transcribir** | `teleRadiologiaTranscibirComponent.vue` (`/tele-radiologia-transcribir`) | Drawer de transcripción, filtros y visor DICOM operan sin interferencias. |
| 🔴 | **5.4 Validación de Audio / Whisper** | `leerEstudioComponent.vue`, `transcribirEstudioComponent.vue` | Las grabaciones de audio no se interrumpen por cambios de interfaz. |

---

### Fase 6: Migración Masiva de Módulos (Inventario de Rutas)
> **Objetivo:** Integrar el resto de submódulos al archivo de rutas `resources/js/router/routes.js`.

| Estado | Submódulo | Rutas Incluidas | Componente Vue Asociado | Permiso Requerido |
| :---: | :--- | :--- | :--- | :--- |
| 🔴 | **Citas y Agenda** | `/citas`, `/citas/gestion`, `/citas/revision`, `/citas/recomendaciones`, `/agenda`, `/agenda-disponibilidad` | `citasComponent.vue`, `gestionCitasComponent.vue`, `agendaDisponibilidadComponent.vue`, etc. | `citas.index`, `agenda.index` |
| 🔴 | **Estudios Radiológicos** | `/estudios.asignar.listar`, `/estudios.asignados.listar`, `/estudios.transcribir.listar`, `/estudios.leer.estudio.listar`, `/estudios.notificar.index` | `AsignarEstudioComponent.vue`, `estudioAsignadosComponent.vue`, `transcribirEstudioComponent.vue`, etc. | `estudio.asignar`, `estudio.transcribir`, `estudio.leerEstudio` |
| 🔴 | **Ecografías** | `/ecografias.admisiones`, `/ecografias.admisionadas`, `/ecografias-pendientes-lecturas`, `/ecografias-consentimientos` | `admisionarComponent.vue`, `lecturaComponent.vue`, `ecografiasConsentimientosComponent.vue`, etc. | `ecografias.index`, `ecografias.leer` |
| 🔴 | **Tele-Radiología** | `/tele-radiologia`, `/tele-radiologia-transcribir` | `teleRadiologiaEnviarEstudioComponent.vue`, `teleRadiologiaTranscibirComponent.vue` | `tele.ragiologia.index`, `tele.ragiologia.transcribir` |
| 🔴 | **Dosimetría (Res. 482)** | `/dosimetria/dashboard`, `/dosis/dashboard` | `DashboardDosimetria.vue` | `dosimetria.dashboard` |
| 🔴 | **Helpdesk** | `/helpdesk-tickets`, `/helpdesk-tickets/crear`, `/helpdesk-tickets/:ticket_id`, `/helpdesk-reportes`, `/helpdesk-notificaciones` | `helpdeskTicketsComponent.vue`, `helpdeskDetalleTicketComponent.vue`, etc. | `helpdesk.tickets.index`, `helpdesk.tickets.show` |
| 🔴 | **Seguridad** | `/seguridad.usuarios.listar`, `/seguridad.roles.listar`, `/seguridad.perfil`, `/seguridad-auditoria`, `/seguridad-backups`, `/seguridad-tokens` | `usuarioComponent.vue`, `roleComponente.vue`, `backupMonitorComponent.vue`, `seguridadTokensComponent.vue`, etc. | `seguridad.usuarios.index`, `seguridad.roles.index`, `seguridad.backups.index` |
| 🔴 | **Configuración** | `/config-adminsalud-listar`, `/config-diagnosticos-listar`, `/config-prioridades-listar`, `/config-productos-listar`, `/config-sedes-listar`, `/config-plantillas-listar`, etc. | `configAdminSaludComponent.vue`, `configDiagnosticoComponent.vue`, `configSedeComponent.vue`, etc. | `configuracion.index` |
| 🔴 | **Contratación y Liquidación** | `/contratos`, `/liquidacion`, `/productividad` | `contratoComponent.vue`, `liquidacionComponent.vue`, `productividadComponent.vue` | `contratos.index`, `liquidacion.index` |
| 🔴 | **Reportes** | `/reportes-produccion`, `/reportes-estudios-pac` | `reporteProduccionComponent.vue`, `reporteEstudiosPACComponent.vue` | `reportes.produccion.index`, `reportes.pac.index` |

---

### Fase 7: Prevención de Memory Leaks y Gestión de Eventos
> **Objetivo:** Sanear el bus de eventos global `mitt` y los ciclos de vida de plugins terceros.

| Estado | Tarea | Componentes / Archivos Afectados | Notas de Implementación |
| :---: | :--- | :--- | :--- |
| 🔴 | **7.1 Desuscripción de `emitter.on` en `onUnmounted`** | `userProfileAndSearchComponent.vue`, `footerComponent.vue` | Asegurar que todo evento registrado se libere al destruir o cambiar componentes. |
| 🔴 | **7.2 Desacoplamiento de Plugins DOM (Select2, Datepicker)** | Componentes de filtros y formularios | Garantizar destrucción de instancias jQuery en `onBeforeUnmount` para evitar fugas de memoria. |
| 🔴 | **7.3 Reemplazo de `window.location.href`** | `commandPaletteComponent.vue`, `dashboardComponent.vue` | Cambiar `window.location.href = url` por `router.push(url)` para navegación interna. |

---

### Fase 8: Rendimiento, Code-Splitting y Verificaciones
> **Objetivo:** Optimizar tiempos de carga, bundles de Vite y verificar compatibilidad en producción.

| Estado | Tarea | Herramienta / Comando | Criterio de Éxito |
| :---: | :--- | :--- | :--- |
| 🔴 | **8.1 Code-Splitting con Vite** | `vite.config.js` | Rutas cargadas mediante imports dinámicos `() => import(...)`. |
| 🔴 | **8.2 Caché de Vistas Frecuentes con `<KeepAlive>`** | `AppLayout.vue` | Mantener formularios clínicos activos sin reiniciar al cambiar de pestaña. |
| 🔴 | **8.3 Verificación de Estilo PHP y Backend** | `vendor\bin\pint.bat --test` | 100% de cumplimiento en rutas y controladores modificados. |
| 🔴 | **8.4 Verificación de Linter y Formato JS/Vue** | `npx eslint "resources/js/**/*.{js,vue}"` | 0 errores y advertencias de ESLint. |
| 🔴 | **8.5 Compilación de Producción Vite** | `npm run build` | Bundle generado exitosamente sin advertencias de dependencias circulares. |

---

## Bitácora de Avances y Control de Cambios

| Fecha | Fase | Tarea Realizada | Estado | Autor |
| :---: | :---: | :--- | :---: | :--- |
| **2026-11-09** | **Fase 0** | Auditoría profunda de la arquitectura MPA, componentes y dependencias de Vue Router. | 🟢 Terminado | Antigravity AI |
| **2026-11-09** | **Doc** | Creación y estructuración del plan de migración con tablero de estados de control (`docs/PLAN_MIGRACION_VUE_ROUTER.md`). | 🟢 Terminado | Antigravity AI |
| **2026-11-09** | **Fase 1** | Implementación de `useAuth.js` e inyección de `window.__INITIAL_STATE__` en `layouts/app.blade.php`. | 🟢 Terminado | Antigravity AI |
| **2026-11-09** | **Fase 2** | Creación de `router/index.js`, `routes.js`, componentes 404/403 y montaje de `app.use(router)`. | 🟢 Terminado | Antigravity AI |
| **2026-11-09** | **Fase 3** | Construcción de `AppLayout.vue`, `AppHeader.vue` y `AppSidebar.vue` (561 líneas portadas de Blade a Vue con `can(...)`). | 🟢 Terminado | Antigravity AI |
| **2026-11-09** | **Fase 4** | Adaptador híbrido en `layouts/app.blade.php` (`@hasSection('content')`) y punto de entrada SPA `/spa/{any?}` en `routes/web.php`. | 🟢 Terminado | Antigravity AI |

---
*Documento administrado por el equipo de desarrollo de LIDiag. Actualizar este archivo al iniciar o completar cada tarea.*

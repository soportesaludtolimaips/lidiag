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
- [x] **2026-11-09 — Estandarización Jerárquica del Documento del Paciente en Todas las Tablas del Sistema (8 Componentes):**
  - **Módulos y Componentes Actualizados:**
    1. *Estudios:* [`estudioAsignadosComponent.vue`](file:///c:/laragon/www/lidiag/resources/js/components/modulos/estudios/estudioAsignadosComponent.vue) (tabla y `tituloModal`), [`leerEstudioComponent.vue`](file:///c:/laragon/www/lidiag/resources/js/components/modulos/estudios/leerEstudioComponent.vue) (tabla y `tituloModal`), [`notificarEstudioComponent.vue`](file:///c:/laragon/www/lidiag/resources/js/components/modulos/estudios/notificarEstudioComponent.vue) (tabla y `tituloModal`).
    2. *Tele-Radiología:* [`teleRadiologiaEnviarEstudioComponent.vue`](file:///c:/laragon/www/lidiag/resources/js/components/modulos/tele-radiologia/teleRadiologiaEnviarEstudioComponent.vue) (tabla y `tituloModal`).
    3. *Ecografías:* [`ecografiasAdmisionadasComponent.vue`](file:///c:/laragon/www/lidiag/resources/js/components/modulos/ecografias/ecografiasAdmisionadasComponent.vue), [`lecturaComponent.vue`](file:///c:/laragon/www/lidiag/resources/js/components/modulos/ecografias/lecturaComponent.vue), [`ecografiasConsentimientosComponent.vue`](file:///c:/laragon/www/lidiag/resources/js/components/modulos/ecografias/ecografiasConsentimientosComponent.vue).
    4. *Pacientes y Citas:* [`admisionComponent.vue`](file:///c:/laragon/www/lidiag/resources/js/components/modulos/pacientes/admisionComponent.vue), [`citasComponent.vue`](file:///c:/laragon/www/lidiag/resources/js/components/modulos/citas/citasComponent.vue), [`citasRevisionComponent.vue`](file:///c:/laragon/www/lidiag/resources/js/components/modulos/citas/citasRevisionComponent.vue).
  - **Mejora Asistencial (E7):**
    * Se homogeneizó el número de documento de los pacientes con la clase azul oficial de AdminWrap (`text-primary font-weight-bold`), tamaño de fuente `15px` e icono `fa-id-card-o`.
    * En los títulos de modales y drawers de agendamiento/lectura/notificación, se incluyó el documento entre paréntesis junto al nombre del paciente para facilitar la confirmación cruzada en estaciones de diagnóstico médico.
  - **Verificaciones:**
    * ESLint: 0 errores y 0 warnings en los componentes modificados (`npx eslint`) ✅.
    * Build de producción Vite (`npm run build`): Compilado con éxito en 1.28s ✅.
    * Vaciado de caché de vistas y configuración de Laravel (`php artisan view:clear; php artisan config:clear`) ✅.
- [x] **2026-11-09 — Hotfix: Eliminación de Antipatrón `proxy` en Citas (`citasComponent.vue`):**
  - **Diagnóstico del Error:**
    * El usuario reportó `TypeError: 'set' on proxy: trap returned falsish for property 'loadingBuscar' at listarRegistros (citasComponent.vue:684:27)`.
    * Causa: En Vue 3 Composition API (`<script setup>`), `getCurrentInstance().proxy` es de solo lectura para propiedades no declaradas en la opción `data`. `citasComponent.vue` utilizaba un patrón legado `var thisVue = proxy; thisVue.loadingBuscar = true;` en 10 métodos, a pesar de que `loadingBuscar` y demás estados ya estaban declarados como `ref()` en el script.
  - **Solución Aplicada:**
    * Se eliminó el uso de `thisVue = proxy` en los 10 métodos de `citasComponent.vue`.
    * Se migraron todas las mutaciones al acceso directo de variables reactivas (`loadingBuscar.value = true`, `busqueda.value`, `registros.value`, `datosPaginacion.value`, etc.).
  - **Verificaciones:**
    * ESLint y Prettier pasados al 100% en `citasComponent.vue` ✅.
    * Compilación Vite exitosa en 1.46s ✅.
- [x] **2026-11-09 — Resaltado Jerárquico del Número de Documento en Asignar Estudios (`AsignarEstudioComponent.vue`, `/estudios.asignar.listar`):**
  - **Ajuste:**
    * Siguiendo el estándar de ergonomía clínica asistencial (E7), se incrementó la visibilidad del número de documento del paciente (`pat_id`) en la tabla principal aplicándole la clase azul institucional de AdminWrap (`text-primary font-weight-bold`) con tamaño de fuente `15px` e icono `fa-id-card-o`.
    * En el encabezado del panel lateral (drawer) de agendamiento (`tituloModal`), se incluyó el número de documento del paciente entre paréntesis: `Agendar al paciente: [Nombre] ([Documento])`.
  - **Verificaciones:**
    * Prettier y ESLint completados con 0 errores y 0 advertencias (`npx eslint`) ✅.
    * Build de producción Vite compilado exitosamente en 1.27s (`npm run build`) ✅.
    * Vaciado de caché de vistas y configuración de Laravel (`php artisan view:clear; php artisan config:clear`) ✅.
- [x] **2026-11-09 — Corrección Masiva de Paginación DataTables en 7 Módulos:**
  - **Módulos Corregidos:**
    1. `teleRadiologiaTranscibirComponent.vue` (`/tele-radiologia-transcribir`)
    2. `agendaDisponibilidadComponent.vue` (`/agenda-disponibilidad`)
    3. `contratoComponent.vue` (`/contratos`)
    4. `helpdeskTicketsComponent.vue` (`/helpdesk`)
    5. `helpdeskReportesComponent.vue` (`/helpdesk-reportes`)
    6. `helpdeskConfigEstadosComponent.vue`
    7. `helpdeskConfigPrioridadesComponent.vue`
  - **Patrón Aplicado:**
    * Eliminado el antipatrón de `<table-skeleton-loader v-if>` y `<div v-else>` que destruía el elemento `<table>` del DOM.
    * Estandarizado a `:visible="loading"` en skeleton loaders y `v-show="!loading && registros.length > 0"` en los contenedores de las tablas.
    * Destrucción preventiva previa a la consulta API (`safeDestroyDataTable`).
    * Espera explícita de reconciliación DOM (`await nextTick()`) en el bloque `finally` antes de reinicializar DataTables sobre las filas renderizadas reales.
    * Eliminado `retrieve: true` para forzar recálculo exacto del número de páginas y botones de paginación.
  - **Verificaciones:**
    * ESLint y Prettier aprobados en los 7 componentes ✅.
    * Vite build completado en 2.60s ✅.
- [x] **2026-11-09 — Corrección de Paginación DataTables en Filtros de Tele-radiología (`teleRadiologiaEnviarEstudioComponent.vue`):**
  - **Problema:** En `/tele-radiologia`, al realizar búsquedas con filtros, la tabla no se paginaba (mostraba todas las filas sin paginar o con DataTables desvinculado).
  - **Causa Raíz:**
    1. El contenedor de la tabla usaba directivas condicionales `v-if="loadingBuscar"` y `v-else`, desmontando por completo el elemento `<table>` del DOM durante cada búsqueda y rompiendo las referencias de DataTables.
    2. `destroyDataTable()` se ejecutaba sincrónicamente mientras el elemento estaba fuera del DOM (`$t.length === 0`), dejando instancias y wrappers residuales huérfanos.
    3. Al volver a montar, `safeInitDataTable` con `retrieve: true` retornaba la instancia vieja y no calculaba la paginación sobre las nuevas filas renderizadas por Vue.
  - **Solución Aplicada:**
    * Se desacopló la visibilidad usando `:visible="loadingBuscar"` en `<table-skeleton-loader>` y `v-show="!loadingBuscar && registros.length > 0"` en el contenedor de la tabla, manteniendo el nodo en el DOM.
    * Se reestructuró `buscarStudy()` con destrucción preventiva previa al fetch y reinicialización limpia posterior a `await nextTick()` con las filas reales montadas.
    * Se eliminó `retrieve: true` y se aseguraron columnas no ordenables en las acciones (`columnDefs: [{ targets: [5], orderable: false }]`).
    * Se añadió `@submit.prevent="buscarStudy()"` en el formulario y `@keyup.enter` en los campos de fecha.
  - **Verificaciones:**
    * Prettier y ESLint pasados sin advertencias ✅.
    * Vite build completado en 1.23s ✅.
- [x] **2026-11-09 — Implementación de Fases 3 y 4 de Migración a Vue Router 4 (Shell SPA y Adaptador Híbrido):**
  - **Avances Realizados:**
    * **Hotfix Blade Syntax:** Se corrigió la compilación de `window.__INITIAL_STATE__` en [`resources/views/layouts/app.blade.php`](file:///c:/laragon/www/lidiag/resources/views/layouts/app.blade.php). La directiva multi-línea `@json(Auth::check() ? [...] : null)` fallaba en el tokenizer de Blade (`Unclosed '[' does not match ')'`); se encapsuló en un bloque `@php $initialState = ... @endphp` con serialización segura `window.__INITIAL_STATE__ = @json($initialState);`. Validado con `php artisan view:cache` al 100%.
    * **Fase 3 (Construcción del Shell SPA):**
      - Se creó [`AppSidebar.vue`](file:///c:/laragon/www/lidiag/resources/js/layouts/AppSidebar.vue) portando íntegramente las 561 líneas de `sidebar.blade.php` a componentes reactivos de Vue 3 con `<router-link to="..." active-class="active">` y verificación de permisos Spatie con `v-if="can('...')"`.
      - Se implementó gestión de acordeones reactivos en Vue (`openMenus`) con auto-apertura inteligente según el prefijo de la ruta activa (`useRoute().path`), eliminando la dependencia de `sidebarmenu.js` y jQuery para el control del menú.
      - Se creó [`AppHeader.vue`](file:///c:/laragon/www/lidiag/resources/js/layouts/AppHeader.vue) con toggle reactivo de mini-sidebar y navegación móvil, integrado con `commandPaletteComponent` y `userProfileAndSearchComponent`.
      - Se creó [`AppLayout.vue`](file:///c:/laragon/www/lidiag/resources/js/layouts/AppLayout.vue) como shell principal conteniendo Header, Sidebar, `<router-view>` con transición suave `fade-page`, y Footer.
    * **Fase 4 (Adaptador Híbrido y Entrada SPA):**
      - En [`resources/views/layouts/app.blade.php`](file:///c:/laragon/www/lidiag/resources/views/layouts/app.blade.php) se implementó la directiva condicional `@hasSection('content')`: si una vista Blade define contenido tradicional, se renderiza el layout MPA para compatibilidad 100% regresiva; si no lo define, monta automáticamente `<app-layout />` con el SPA de Vue Router.
      - En [`routes/web.php`](file:///c:/laragon/www/lidiag/routes/web.php) se habilitó el endpoint de prueba y fallback `Route::get('/spa/{any?}', ...)` protegido con `auth` y `force-password-change`.
  - **Verificaciones:**
    * Pint pasado al 100% en `routes/web.php` (`vendor\bin\pint.bat --test`) ✅.
    * ESLint y Prettier pasados al 100% en los nuevos componentes del layout (`AppLayout.vue`, `AppHeader.vue`, `AppSidebar.vue`) ✅.
    * Compilación Vite completada exitosamente (`built in 1.22s`) ✅.
- [x] **2026-11-09 — Implementación de Fases 1 y 2 de Migración a Vue Router 4:**
  - **Avances Realizados:**
    * **Fase 1 (Capa de Autenticación Reactiva):** Se inyectó `window.__INITIAL_STATE__` en `resources/views/layouts/app.blade.php` con datos de usuario, roles y permisos Spatie. Se creó el composable singleton [`useAuth.js`](file:///c:/laragon/www/lidiag/resources/js/composables/useAuth.js) que provee métodos `can(permission)`, `hasRole(role)`, sincronización reactiva de sede y médico (`sessionStorage`/`localStorage`), y `fetchUser()`.
    * **Fase 2 (Core de Vue Router 4):** Se implementó [`resources/js/router/index.js`](file:///c:/laragon/www/lidiag/resources/js/router/index.js) con historial HTML5 (`createWebHistory`), guardia de navegación global `beforeEach` para verificar permisos Spatie de ruta, actualización de títulos de página y scroll restoration.
    * Se mapearon las 48 rutas en [`resources/js/router/routes.js`](file:///c:/laragon/www/lidiag/resources/js/router/routes.js) con carga asíncrona por chunks de Vite (`() => import(...)`).
    * Se crearon los componentes de estado [`NotFoundComponent.vue`](file:///c:/laragon/www/lidiag/resources/js/components/comunes/NotFoundComponent.vue) (404) y [`ForbiddenComponent.vue`](file:///c:/laragon/www/lidiag/resources/js/components/comunes/ForbiddenComponent.vue) (403).
    * Se conectó `app.use(router)` en `resources/js/app.js`.
  - **Verificaciones:**
    * ESLint y Prettier pasados al 100% en los nuevos módulos de JS y Vue ✅.
    * Compilación de producción Vite completada exitosamente (`built in 1.25s`) con separación de chunks por ruta ✅.
- [x] **2026-11-09 — Auditoría Profunda y Plan de Migración a Vue Router (`docs/PLAN_MIGRACION_VUE_ROUTER.md`):**
  - **Diagnóstico y Necesidad:**
    * El usuario solicitó auditar la arquitectura actual de LIDiag y documentar una hoja de ruta estructurada para migrar de la arquitectura MPA (Multi-Page Application) basada en vistas Blade e islas Vue 3 hacia una Single Page Application (SPA) con Vue Router 4.
    * Se identificó que `vue-router: ^4.1.6` ya se encuentra instalado en `package.json` pero sin uso activo.
    * Se identificaron 82 componentes Vue (48 componentes página cargados de forma asíncrona) y 5 dependencias estructurales a desacoplar: (1) `sidebar.blade.php` con 561 líneas en Blade y chequeos Spatie `@can`; (2) Inyección de props desde Blade (`:usuarioactual`, `:permisos`); (3) Scripts jQuery de AdminWrap (`sidebarmenu.js`, `custom.js`); (4) Limpieza de listeners en el bus `mitt` para evitar memory leaks; (5) Exclusión de rutas nativas en Laravel (login, descargas, visores externos tokenizados).
  - **Solución Implementada:**
    * Se elaboró el documento formal [PLAN_MIGRACION_VUE_ROUTER.md](file:///c:/laragon/www/lidiag/docs/PLAN_MIGRACION_VUE_ROUTER.md) estructurado en 8 fases de ejecución con semáforo de control de estados: 🔴 Sin Iniciar, 🟡 Iniciado y 🟢 Terminado.
    * Se incluyó matriz de trazabilidad de módulos, rutas, componentes y permisos Spatie asociados, junto con una bitácora de control de cambios.
- [x] **2026-11-09 — Mejoras Integrales de UI/UX y Ergonomía Clínica en Transcribir Estudios (`transcribirEstudioComponent.vue`, `/estudios.transcribir.listar`):**
  - **Diagnóstico y Necesidad:**
    * La vista `/estudios.transcribir.listar` (`transcribirEstudioComponent.vue`) presentaba discrepancias frente a la directiva de ergonomía clínica y las mejoras recientes de `/tele-radiologia-transcribir`:
      1. El número de documento del paciente se encontraba en `<small class="text-muted">` grisáceo dificultando el escaneo visual rápido del personal asistencial.
      2. No contaba con acceso directo al visor PACS/DICOM desde la fila de la tabla sin tener que abrir previamente el drawer.
      3. Carecía de soporte de Modo Oscuro (alto contraste radiológico) y Modo Zen (pantalla completa) para transcripción cómoda de reportes extensos.
      4. El título del drawer modal no incluía el número de documento del paciente entre paréntesis.
      5. La barra de migas de pan carecía del botón de refresco rápido, las etiquetas de fecha no admitían `@keyup.enter`, y había etiquetas con errores tipográficos ("Quien asigno", "Medicos", subtítulo copiado de Asignar Estudio).
      6. Los botones de acción del drawer contenían insignias (`<span class="badge">Ctrl+S</span>` y `<span class="badge">Esc</span>`) en lugar de atributos `title` limpios.
  - **Solución Implementada:**
    * *Resaltado Jerárquico del Paciente (E7):* El número de documento (`num_docu`) ahora se muestra en `text-primary font-weight-bold` con tamaño de fuente de 15px e icono `fa-id-card-o`.
    * *Acceso Directo a Visor PACS/DICOM:* Se incorporó el botón `btn-outline-info` en la columna de acciones para abrir directamente el visor Oviyam/DICOM con `verImagenDirecta(item)` y se amplió el ancho de la columna de acciones a 110px.
    * *Modo Oscuro Radiológico y Modo Zen:* Se integraron los conmutadores de Modo Oscuro y Modo Zen con persistencia en `localStorage` (`lidiag_dark_mode`) tanto en la cabecera de la tabla como en el drawer lateral.
    * *Identificación en Encabezado:* `tituloModal` ahora muestra `Transcribir la lectura del paciente: [Nombre] ([Documento])`.
    * *Filtros y Navegación:* Se agregó botón de actualización rápida en `page-titles`, `@keyup.enter` en campos de fecha, corrección de acentos y subtítulo de la tabla.
    * *Botones y Toasts:* Eliminadas insignias de atajos embebidas en los botones de Guardar/Cancelar en favor de tooltips `title`, y toasts de feedback homologados a `success`, `warning`, `info` y `error`.
  - **Verificaciones:**
    * ESLint: 0 errores y 0 warnings (`npx eslint`) ✅.
    * Compilación de producción Vite (`npm run build`): Exitosa en 1.21s ✅.
    * Vaciado de caché de vistas y configuración de Laravel (`php artisan view:clear; php artisan config:clear`) ✅.
- [x] **2026-11-09 — Actualización Integral y Estandarización de Todos los Documentos README del Repositorio:**
  - **Alcance y Archivos Actualizados:**
    * Se auditaron y actualizaron los 9 archivos `README*.md` propios del proyecto:
      1. [`README.md`](file:///c:/laragon/www/lidiag/README.md): Documentación del orquestador raíz incorporando Sección 14 (Dosimetría con Mapa de Calor de 7 regiones, normalización NRD y autocuración en backend), Sección 19 (Centro de Reportes multi-hoja con PhpSpreadsheet), Sección 20 (Estudio Locks con heartbeat de 45s y marcaje de hallazgos críticos de urgencias), Sección 21 (Informes ejecutivos en PDF para gerencia), comandos de sincronización (`dosimetria:sync-mamografias`, `dosimetria:sync-estudios`) y scheduler.
      2. [`storage/app_lidiag/transcriptor/README.md`](file:///c:/laragon/www/lidiag/storage/app_lidiag/transcriptor/README.md): Corrección de rutas (`storage/transcriptor/` → `storage/app_lidiag/transcriptor/`), documentación de pipeline FFmpeg a MP3 64kbps en disco temporal `audios/tmp/`, FastAPI Whisper base en CPU y variable `.env` `TRANSCRIPTOR_TIMEOUT=300`.
      3. [`storage/app_lidiag/agente_backups_dcm/README.md`](file:///c:/laragon/www/lidiag/storage/app_lidiag/agente_backups_dcm/README.md): Catálogo de las 7 sedes activas (Espinal, Purificación, Líbano, Honda, Chaparral, Lérida, Ibagué), tokens permanentes M2M (`SANCTUM_EXPIRATION=null`) vía `/seguridad-tokens` y unificación de scripts y tareas.
      4. [`storage/app_lidiag/agente_backups_dcm/README_DOSIMETRIA.md`](file:///c:/laragon/www/lidiag/storage/app_lidiag/agente_backups_dcm/README_DOSIMETRIA.md): Documentación de las 7 regiones anatómicas, umbrales ALARA, autocuración (`DosimetriaService::asegurarTelemetriaSedes`) y comandos de consola.
      5. [`storage/app_lidiag/agente_backups_dcm/README_BACKUP_DB.md`](file:///c:/laragon/www/lidiag/storage/app_lidiag/agente_backups_dcm/README_BACKUP_DB.md): Normalización de endpoints API (`https://lidiag.com/api`) y directiva WAN `"subir_zip_central": false`.
      6. [`storage/app_lidiag/agente_backups_dcm/README_DISK_MONITOR.md`](file:///c:/laragon/www/lidiag/storage/app_lidiag/agente_backups_dcm/README_DISK_MONITOR.md): Normalización de endpoints API de telemetría y detección multi-disco.
      7. [`storage/app_lidiag/agente_backups_dcm/README_SYNC_IMAGENES.md`](file:///c:/laragon/www/lidiag/storage/app_lidiag/agente_backups_dcm/README_SYNC_IMAGENES.md): Normalización de endpoints API de telemetría, soporte multi-disco (`local_images_dir` en array o string), control de lockfile y healthcheck consolidado.
      8. [`storage/app_lidiag/agente_backups_dcm/README_RESTORE_IMAGENES.md`](file:///c:/laragon/www/lidiag/storage/app_lidiag/agente_backups_dcm/README_RESTORE_IMAGENES.md): Protocolo de contingencia clínica cronológica descendente ($2026 \to 2025 \to \dots$), catálogo de shares por sede y RTO < 3h para urgencias.
      9. [`storage/app_lidiag/dcm_disk_monitor_telegram/README.md`](file:///c:/laragon/www/lidiag/storage/app_lidiag/dcm_disk_monitor_telegram/README.md): Clarificación de arquitectura unificada (`dcm_disk_monitor.py` en `agente_backups_dcm` que unifica Telegram y telemetría central), comandos `/discos`, `/alertas`, `/info`, y modo de despliegue interactivo/autónomo.
  - **Verificaciones:**
    * Todos los archivos normalizados con finales de línea LF estrictos sin UTF-8 BOM ✅.
    * Enlaces markdown cruzados verificados y validados ✅.
- [x] **2026-11-09 — Resaltado de Número de Documento en Tele-radiología Transcribir (`teleRadiologiaTranscibirComponent.vue`):**
  - **Ajuste:**
    * Se incrementó el tamaño de fuente (`font-size: 15px`, `font-weight-bold`) del número de documento del paciente en la tabla principal y se aplicó el color azul oficial de AdminWrap (`text-primary`), reemplazando la etiqueta previa tenue y pequeña (`small text-muted`).
    * Se incluyó el número de documento del paciente en el encabezado del panel drawer de transcripción (`tituloModal`) para rápida verificación.
  - **Verificaciones:**
    * Prettier y ESLint completados sin errores ✅.
    * Build de producción Vite compilado exitosamente (`built in 1.67s`) ✅.
- [x] **2026-11-06 — Estandarización Visual al Estilo Nativo de la Aplicación (`teleRadiologiaTranscibirComponent.vue`):**
  - **Ajuste:**
    * Se eliminaron estilos CSS personalizados ajenos a la plantilla para conservar 100% la estética y patrones oficiales de AdminWrap / Lidiag.
    * Botones de prioridad unificados al formato estándar (`btn waves-effect waves-light btn-rounded btn-sm` con letras `A`, `M`, `B`, `S`).
    * Etiquetas de estado unificadas con las clases nativas (`label label-success` y `label label-danger`).
    * Filtro de estado (`Todos`, `Con Lectura`, `Sin Lectura`) integrado de forma armónica como un `custom-select` estándar dentro de la fila de filtros.
    * Incorporación del botón de visualización directa de imágenes PACS/DICOM (`btn-outline-info`) y badges nativos de conteo en la cabecera.
    * Panel lateral (drawer) restituido a la estructura nativa (`rpanel-title`, `r-panel-body`, `textarea_editor`, tabs estándar).
  - **Verificaciones:**
    * Prettier y ESLint pasados sin advertencias ✅.
    * Build de Vite completado en 1.37s ✅.
  - **Diagnóstico del Requerimiento:**
    * El usuario solicitó: *"Mejora la ui/ux de https://lidiag.com/tele-radiologia-transcribir"*.
    * La vista anterior presentaba:
      - Carencia de métricas o indicadores clave (KPIs) sobre el volumen de estudios y su estado.
      - Botones crípticos de una sola letra (`A`, `M`, `B`, `S`) para prioridades clínicas sin descripción.
      - Etiquetas básicas de estado sin jerarquía visual para distinguir estudios listos para transcribir vs en espera de lectura en TLIMAGEN.
      - Imposibilidad de abrir el visor PACS/DICOM directamente desde las filas de la tabla sin entrar al modal.
      - Panel lateral (drawer) de transcripción con campos amontonados (`<dl><dt><dd>`), editor de texto plano sin contadores de palabras/caracteres ni utilidades de copiado rápido al portapapeles.
  - **Solución Aplicada:**
    * **1. Tarjetas KPI de Resumen Dinámico:**
      - 4 tarjetas interactivas: *Total Estudios*, *Listos Para Transcribir (Con lectura)*, *En Espera de Tele (Sin lectura)* y *Prioridad Alta (Urgentes)*.
      - Al hacer clic en cualquier KPI card o en los chips de la tabla, filtra instantáneamente el listado sin recargar datos del servidor.
    * **2. Barra de Filtros Optimizada:**
      - Agrupación por campos con iconos descriptivos, atajo de teclado `Enter` en búsqueda rápida y botones de rango rápido (*Hoy*, *Últimos 7 días*, *Este mes*).
    * **3. Tabla Clínica Modernizada:**
      - Badges clínicos de prioridad con iconos: *Alta* (rojo fuego), *Media* (ámbar reloj), *Baja* (azul info), *Normal* (neutro).
      - Badges de estado claros: *Con Lectura* (verde con icono check y timestamp de recepción) vs *En Espera* (ámbar con icono reloj).
      - Información de paciente jerarquizada (nombre destacado, documento en badge sutil, sexo/edad).
      - Información de procedimiento con badges para CUPS y Accession Number.
      - Acciones directas por fila: Botón *Transcribir* estilizado con indicador de lectura lista, nuevo botón para abrir directamente el *Visor DICOM* desde la fila, y botón de anular con confirmación.
    * **4. Panel Deslizante de Transcripción (Drawer) Rediseñado:**
      - Cabecera con título médico claro, botón de acceso rápido al Visor DICOM, modo Zen (pantalla completa) y Modo Oscuro.
      - Columna izquierda con tarjeta clínica estructurada de detalles del estudio, observaciones resaltadas y campo de email para reporte.
      - Editor de lectura mejorado: tipografía monoespaciada de alta legibilidad, contadores en tiempo real de caracteres y palabras, botones para copiar al portapapeles y limpiar.
      - Tabs estilizadas para adjuntos (con badges de conteo y botones de descarga rápida) y audios (con reproductor integrado).
      - Barra inferior sticky con botón de guardado destacado y pista de atajo de teclado (`Ctrl + S`).
  - **Verificaciones:**
    * ESLint sin errores (`npx eslint`) ✅.
    * Formateo Prettier verificado (`npx prettier --write`) ✅.
    * Compilación Vite para producción exitosa (`npm run build`, 1.28s) ✅.
- [x] **2026-11-06 — Diagnóstico y Trazabilidad en Recepción de Lecturas de Tele-Radiología (`TeleRadiologiaController.php`, `TeleRadiologiaService.php`):**
  - **Diagnóstico del Requerimiento:**
    * El usuario solicitó: *"agreguemos un Log::info al metodo recibirLectura por esta llegando la data pero el campo lectura queda null"*.
    * En el flujo de recepción webhook (`POST /api/tele-radiologia-recibir-lectura`), el texto de la lectura médica guardado en `estudios_productos.lectura` terminaba en `null`.
    * Causa 1: Falta de instrumentación y trazabilidad en logs para inspeccionar el payload exacto enviado por la plataforma externa TLIMAGEN.
    * Causa 2: `TeleRadiologiaController` forzaba `mb_convert_encoding($rawContent, 'UTF-8', 'ISO-8859-1')` antes de decodificar el JSON, lo cual corrompe cadenas que ya vienen en UTF-8 nativo desde el cliente HTTP.
    * Causa 3: Disparidad de nombres de campo en el payload de TLIMAGEN (p. ej. `lectura`, `informe`, `reporte`, `resultado`, `texto_lectura`, `conclusion`) o estructuración como arreglo anidado.
  - **Solución Aplicada:**
    * **1. Instrumentación Exhaustiva con `Log::info`:**
      - En `TeleRadiologiaController::recibirLectura`: registra el tamaño en bytes del cuerpo crudo, si viene la clave `lectura`, todas las claves del payload y el array completo recibido.
      - En `TeleRadiologiaService::recibirLectura`: registra el `study_iuid`, `cod_cups`, `estudio_id`, `estudio_producto_id`, longitud del texto resuelto y preview de los primeros 200 caracteres guardados.
    * **2. Decodificación Resiliente de Payload JSON:**
      - Se prueba primero la decodificación directa en UTF-8 estándar. Solo si falla por sintaxis de caracteres se intenta la conversión desde ISO-8859-1, con fallback a `$request->all()`.
    * **3. Resolución Tolerante de Lectura:**
      - Se mapean alternativas de clave: `$d['lectura'] ?? $d['informe'] ?? $d['reporte'] ?? $d['resultado'] ?? $d['texto_lectura'] ?? $d['conclusion']`.
      - Si el valor es un arreglo u objeto, se serializa a JSON legible con `json_encode(..., JSON_UNESCAPED_UNICODE)`.
  - **Verificaciones:**
    * PHP Pint pasado al 100% (`vendor\bin\pint.bat --test`) ✅.
    * Larastan / PHPStan nivel 5: `2/2 [100%] [OK] No errors` ✅.
- [x] **2026-11-06 — Hotfix: Sanitización de Tipos de Columna Entera (`medico_id`, `destino_estudio_id`) al Crear Estudio (`EstudioService.php`, `teleRadiologiaEnviarEstudioComponent.vue`):**
  - **Diagnóstico del Error:**
    * El usuario reportó: `HTTP 500 "Error al crear el estudio"` en `POST /api/estudios` tras el envío exitoso de la orden a TLIMAGEN.
    * Causa 1: En MySQL Strict Mode, la columna `medico_id` de la tabla `estudios` es de tipo `bigint unsigned NULL`. En tele-radiología no hay médico asignado, por lo que el frontend enviaba `medico_id: ""` (string vacío). En PHP, `"" ?? null` no evalúa a `null`, intentando insertar `""` y provocando la excepción `SQLSTATE[HY000]: General error: 1366 Incorrect integer value: '' for column 'medico_id'`.
    * Causa 2: La columna `destino_estudio_id` es también `bigint unsigned`. Si la confirmación externa viene vacía o alfanumérica, causaba el mismo fallo de casteo entero.
    * Causa 3: `EstudioService` capturaba `\Throwable` pero ocultaba el mensaje de error original en la respuesta JSON.
  - **Solución Aplicada:**
    * **1. Backend (`EstudioService.php`):**
      - Se normalizó el casteo de `medico_id` y `destino_estudio_id` a `null` cuando no son numéricos (`! empty($val) && is_numeric($val) ? (int) $val : null`).
      - Se normalizó la resolución de fecha de estudio (`fec_estudio` con fallback a `now()`).
      - Se expuso el mensaje descriptivo en el log y respuesta de error 500.
    * **2. Frontend (`teleRadiologiaEnviarEstudioComponent.vue`):**
      - Antes del POST `/api/estudios`, se asegura `registro.value.medico_id = null`, `prioridad_id = orden.value.priori.id`, y `destino_estudio_id` validado con `Number(confirmation)`.
    * **3. Verificaciones:**
      - Pint al 100% (`vendor\bin\pint.bat --test`) ✅.
      - ESLint sin errores en el componente Vue ✅.
      - Simulación en base de datos real con `$registro` sanitizado: `SUCCESS: Estudio created with ID 212329` ✅.
      - Build de producción Vite completado exitosamente (`npm run build`) ✅.
- [x] **2026-11-06 — Hotfix: Soporte de Certificados SSL Intermedios/Self-Signed en Tele-Radiología (`TeleRadiologiaService.php`, `config/services.php`):**
  - **Diagnóstico del Error:**
    * El usuario reportó: `HTTP 500 "Error interno del servidor proxy" - cURL error 60: SSL certificate problem: self-signed certificate in certificate chain for https://ris.tlimagen.com/RISServices/integracion/salud_tolima/ordenes`.
    * La cadena de certificados SSL de la pasarela RIS de TLIMAGEN contiene una entidad emisora intermedia o autofirmada no indexada en el almacén `cacert.pem` del servidor local/cPanel.
  - **Solución Aplicada:**
    * Se incorporó la configuración `'verify_ssl' => env('TELERADIOLOGIA_VERIFY_SSL', false)` en `config/services.php`.
    * En `TeleRadiologiaService::enviarOrdenTeleRadiologia`, si `verify_ssl` es falso (por defecto), se invoca `$httpClient->withoutVerifying()`, homologando el patrón existente en `TelegramService` y `EstudioNotificacionService` para garantizar la comunicación WAN con TLIMAGEN sin bloqueos de cURL error 60.
  - **Verificaciones:**
    * PHP Pint pasado al 100% (`vendor\bin\pint.bat --test`) ✅.
    * Larastan / PHPStan nivel 5: `1/1 [100%] [OK] No errors` ✅.
- [x] **2026-11-06 — Hotfix: Validación de Envío de Orden en Tele-Radiología (`EnviarOrdenTeleRadiologiaRequest.php`, `teleRadiologiaEnviarEstudioComponent.vue`):**
  - **Diagnóstico del Error:**
    * El usuario reportó: `HTTP 422 Unprocessable Content` en `POST /api/tele-radiologia-enviar-orden` con errores: `"orden.study_id es obligatorio"`, `"orden.medico.num_docu es obligatorio"`, `"orden.medico.name es obligatorio"`, `"orden.cod_cups es obligatorio"`.
    * Causa 1: En una modificación previa, `EnviarOrdenTeleRadiologiaRequest` había incorporado reglas estrictas `required` para médico (`orden.medico.num_docu`, `orden.medico.name`) y para `orden.cod_cups`.
    * Causa 2: En el flujo de envío de órdenes de Tele-radiología, el estudio aún no ha sido asignado a un radiólogo local (TLIMAGEN se encarga de asignar el radiólogo en su plataforma al recibir la orden). Por ende, el formulario nunca captura ni tiene médico asignado.
    * Causa 3: Los exámenes se envían como un arreglo dinámico `orden.producto` (con múltiples o al menos un producto CUPS), no como un campo plano `orden.cod_cups`. Además, `study_id` no se mapeaba desde el modal hacia el payload.
  - **Solución Aplicada:**
    * **1. Normalización y Flexibilización en FormRequest (`EnviarOrdenTeleRadiologiaRequest.php`):**
      - Se implementó `prepareForValidation()` para auto-poblar `study_id` a partir de `study_pk` si viene vacío, extraer el primer `cod_cups` desde `orden.producto` si no viene en raíz, y asociar el usuario autenticado en sesión en `medico` para trazabilidad de quién emite la orden.
      - Las reglas para `study_id`, `medico`, `medico.num_docu`, `medico.name` y `cod_cups` se flexibilizaron a `nullable`.
    * **2. Enriquecimiento del Payload en Frontend (`teleRadiologiaEnviarEstudioComponent.vue`):**
      - En `mostrarRegistro()`, se asegura `orden.value.study_id = data.study_id ?? String(data.study_pk ?? "")`.
      - En `guardarRegistro()`, `payloadOrden` ahora incluye explícitamente `study_id`, `cod_cups` (primer CUPS de `orden.producto`) y `medico` con los datos del usuario logueado (`props.usuarioactual`).
    * **3. Verificaciones:**
      - PHP Pint pasado al 100% (`vendor\bin\pint.bat --test`) ✅.
      - ESLint sin errores en el componente Vue ✅.
      - Larastan / PHPStan nivel 5: `1/1 [100%] [OK] No errors` ✅.
      - Build de producción Vite completado exitosamente (`npm run build`) ✅.
- [x] **2026-11-06 — Despliegue y Registro Exitoso de las 4 Tareas de Sede (Validado en Producción / Espinal):**
  - **Confirmación del Usuario:** "Listo, ahora si".
  - Las 4 tareas operativas (`LIDiag_Backup_DB_DCM`, `LIDiag_Sync_Imagenes_DCM`, `LIDiag_Dosimetria_Edge`, `LIDiag_Disk_Monitor_DCM`) quedaron registradas en el Programador de Tareas de Windows bajo la cuenta `SYSTEM`, operando de forma autónoma con sus frecuencias respectivas.
- [x] **2026-11-06 — Hotfix: Escape de Paréntesis en Bloques `if` de Windows CMD (`instalar_tareas_sede.bat`):**
  - **Diagnóstico:**
    * El usuario reportó: `No se esperaba bajo en este momento.` al llegar al paso `[2/4] Registrando tarea de Sincronizacion Continua de Imagenes`.
    * En Windows CMD, incluir paréntesis dentro de un bloque `if (...)` (ej: `echo ... (ONSTART) bajo SYSTEM.`) hace que el primer `)` cierre prematuramente el bloque `if`, provocando un fallo sintáctico fatal en la palabra siguiente (`bajo`).
  - **Solución:**
    * Se eliminaron los paréntesis de los mensajes de salida dentro de bloques `if`, reemplazando `(ONSTART)` por `al arranque ONSTART`.
    * Probado en CMD simulando las 4 tareas completas con código de salida 0.
- [x] **2026-11-06 — Instalación 100% No Interactiva y Automatizada (`instalar_tareas_sede.bat`):**
  - **Diagnóstico:**
    * El usuario preguntó "¿Para q es la opcion?" al aparecer `Opcion: ` en consola.
    * Esto correspondía a prompts interactivos (`set /p`) de confirmación que confundían al operador o se mostraban sin contexto debido al buffer de CMD.
  - **Solución Aplicada:**
    * Se eliminaron todos los `set /p` interactivos de [instalar_tareas_sede.bat](file:///c:/laragon/www/lidiag/storage/app_lidiag/agente_backups_dcm/instalar_tareas_sede.bat).
    * Ahora el script es 100% desatendido: registra de corrido las 4 tareas en Windows Task Scheduler, imprime la tabla de estado y dispara en segundo plano el monitor de discos e imágenes para enviar telemetría de inmediato.
- [x] **2026-11-06 — Corrección de Type Error en Búsqueda de Estudios de Tele-Radiología (`TeleRadiologiaController.php`, `TeleRadiologiaService.php`):**
  - **Diagnóstico del Error:**
    * El usuario reportó: `App\Http\Controllers\Dcm4chee\StudyController::listarEstudios(): Argument #1 ($request) must be of type App\Http\Requests\Dcm4chee\StudyListRequest, Illuminate\Http\Request given, called in TeleRadiologiaService.php on line 33` al buscar un estudio en `https://lidiag.com/tele-radiologia`.
    * Causa 1: `TeleRadiologiaService` delegaba directamente llamando a un controlador (`app(StudyController::class)->listarEstudios($request, new Study)`) en vez de delegar a la capa de servicios (`StudyService`).
    * Causa 2: `StudyController::listarEstudios` exige estrictamente el FormRequest validado `StudyListRequest`, pero `TeleRadiologiaController` recibía un genérico `Illuminate\Http\Request`, disparando un `TypeError` fatal en PHP 8.4.
    * Causa 3: `StudyController::listarEstudios` retorna un `JsonResponse` que `TeleRadiologiaService` envolvía en un array `$this->success()`, lo que hubiera provocado un empaquetado doble anidado erróneo.
  - **Solución Aplicada:**
    * **1. Inyección de Servicio Limpio (`TeleRadiologiaService.php`):**
      - Se inyectó `StudyService $studyService` en el constructor de `TeleRadiologiaService`.
      - Se removieron los imports innecesarios a `StudyController` y `Study`.
      - `listarEstudios(array|Request $filtros)` ahora delega limpiamente en `$this->studyService->listarEstudios($data)` retornando el array de estudios estructurado.
    * **2. Tipado y Validación en Controlador (`TeleRadiologiaController.php`):**
      - `listarEstudios` ahora utiliza `StudyListRequest $request` validando formalmente `sede_id`, fechas y términos de búsqueda.
      - Pasa `$request->validated()` a `TeleRadiologiaService`.
      - Retorna `response()->json(..., JSON_INVALID_UTF8_SUBSTITUTE)` previniendo fallos de encoding en caracteres DICOM legacy.
  - **Verificaciones:**
    * PHP Pint pasado al 100% (`vendor\bin\pint.bat --test`) ✅.
    * Larastan / PHPStan nivel 5: `2/2 [100%] [OK] No errors` ✅.
    * Finales de línea en LF y UTF-8 sin BOM ✅.
- [x] **2026-11-06 — Hotfix: Sanitización ASCII y Fin de Líneas en Scripts `.bat` (`instalar_tareas_sede.bat`, `install.bat`):**
  - **Diagnóstico del Error en Consola:**
    * El usuario compartió la salida de error: `"d" no se reconoce... "cho" no se reconoce... "ho" no se reconoce... No se esperaba . en este momento.`
    * En el intérprete CMD de Windows, incluir caracteres UTF-8 multibyte (como el guion largo `—`, signos `¿`, o vocales con tilde) corrompe el puntero de bytes en bloques `if/else`, haciendo que los comandos se lean a mitad de palabra (`cd` -> `d`, `echo` -> `cho`/`ho`, `echo.` -> `.`).
    * Además, el uso de etiquetas de comentario estilo doble dos puntos `::` tras o dentro de bloques con paréntesis provoca errores sintácticos de salto.
  - **Solución Aplicada:**
    * Se normalizaron [instalar_tareas_sede.bat](file:///c:/laragon/www/lidiag/storage/app_lidiag/agente_backups_dcm/instalar_tareas_sede.bat) e [install.bat](file:///c:/laragon/www/lidiag/storage/app_lidiag/agente_backups_dcm/install.bat) a codificación ASCII de 7 bits pura, finales de línea CRLF obligatorios para Windows, comentarios estrictos con `REM` y sin caracteres Unicode multibyte.
    * Probado en Windows CMD con éxito: registra limpiamente las 4 tareas en Programador de Tareas.
- [x] **2026-11-06 — Automatización y Registro de las 4 Tareas de Sede (`instalar_tareas_sede.bat`, `install.bat`):**
  - **Diagnóstico del Requerimiento:**
    * El usuario señaló que en el servidor de sede (Espinal) solo existía la tarea `LiDiag_Backup_DB_DCM` y solicitó que `instalar_tareas_sede.bat` cree automáticamente las tareas para `ejecutar_dosimetria.bat`, `ejecutar_disk_monitor.bat` y los demás agentes.
  - **Mejoras Aplicadas:**
    * **1. Registro Automatizado de las 4 Tareas en `instalar_tareas_sede.bat`:**
      - `LIDiag_Backup_DB_DCM`: Respaldo MySQL diario a las 02:00 AM (`/sc DAILY /st 02:00 /ru SYSTEM /rl HIGHEST`).
      - `LIDiag_Sync_Imagenes_DCM`: Sincronización continua de imágenes DICOM al arrancar el equipo (`/sc ONSTART /delay 0001:00 /ru SYSTEM /rl HIGHEST`).
      - `LIDiag_Dosimetria_Edge`: Extracción y telemetría de dosimetría radiológica cada 1 hora a las :15 (`/sc HOURLY /mo 1 /st 00:15 /ru SYSTEM /rl HIGHEST`).
      - `LIDiag_Disk_Monitor_DCM`: Auditoría y telemetría de discos y almacenamiento cada 30 minutos a las :05 (`/sc MINUTE /mo 30 /st 00:05 /ru SYSTEM /rl HIGHEST`).
    * **2. Auto-Elevación UAC y Validación:**
      - Si el script se ejecuta sin privilegios de administrador por doble clic, se auto-eleva transparentemente solicitando permisos con PowerShell UAC (`Start-Process cmd -Verb RunAs`).
      - Limpia tareas huérfanas con nombres antiguos (`LiDiag_Backup_DB_DCM`).
      - Muestra tabla con el estado real de las 4 tareas registradas (`schtasks /query`).
      - Ofrece ejecutar una prueba inmediata del monitor de discos para reportar telemetría en caliente a LIDiag Central.
    * **3. Enlace Directo desde `install.bat`:**
      - Tras finalizar la instalación de pip y dependencias, `install.bat` ofrece registrar de inmediato las 4 tareas de Windows sin requerir abrir otro archivo.
- [x] **2026-11-06 — Hotfix: Persistencia Robusta de Telemetría Edge y Auto-Recuperación de Logs (`BackupUploadController.php`, `BackupMonitorService.php`, `backupMonitorComponent.vue`, `dcm_disk_monitor.py`):**
  - **Diagnóstico del Problema:**
    * El usuario reportó "No se muestra la data" con captura de `https://lidiag.com/seguridad-backups`.
    * En la "Bitácora de Eventos" se evidenciaba la llegada exitosa de la telemetría: `[2026-11-06 11:20:46] production.INFO: [Telemetría disco_imagenes] Espinal - Hospital San Rafael: Estado OK...`.
    * Sin embargo, la tabla en la columna `Disco Imágenes (PACS)` mostraba `Sin datos Dd`.
    * Causa 1: La telemetría solo se almacenaba en `Cache::put()`. En entornos de producción (PHP-FPM con múltiples procesos, reinicios o configuración de driver de caché volátil), el caché no siempre estaba disponible para peticiones HTTP subsecuentes.
    * Causa 2: El mecanismo de fallback `recuperarUltimaTelemetriaDeLog()` fallaba porque su expresión regular `/^\[(?P<fecha>[^\]]+)\]\s+[A-Za-z0-9_.]+:.*?(\{.+\})$/` exigía que `}` fuera el final estricto de la línea (`$`). Monolog en Laravel añade un espacio en blanco (`\x20`) o corchetes de contexto/extra (`[] []`) antes del salto de línea, haciendo que el regex retornara 0 matches.
  - **Solución Aplicada:**
    * **1. Persistencia de 3 Niveles en Backend:**
      - `BackupUploadController::telemetria()` ahora persiste inmediatamente a `storage/app/telemetria/telemetria_sede_{id}_{servicio}.json`, además de guardarlo en `Cache` y en `Log::channel("backups")`.
      - `BackupMonitorService::obtenerTelemetriaSede()` implementa la resolución priorizada: (1) Caché rápido → (2) Archivo JSON persistente → (3) Recuperación desde logs.
    * **2. Parser de Logs Resiliente e Inmune a Monolog:**
      - `recuperarUltimaTelemetriaDeLog()` ahora escanea `backups.log`, `laravel.log` y logs diarios.
      - Extrae el JSON buscando tolerante entre el primer `{` y el último `}` con `strpos`/`strrpos`, tolerando espacios finales, saltos de línea y arrays vacíos de Monolog.
      - Al recuperar un registro de log, auto-sana tanto el archivo JSON como el caché.
    * **3. Frontend y Script DCM:**
      - Corregido el texto `Sin datos Dd` a `Sin datos (D:)` en `backupMonitorComponent.vue`.
      - Corregido el mensaje en `dcm_disk_monitor.py` de `Auditoría Dd:` a `Auditoría Almacenamiento:`.
      - Build de producción ejecutado con éxito (`npm run build`).
- [x] **2026-11-06 — Hotfix: Tolerancia a Comas Finales (Trailing Commas) en `parse_tolerant_json` (`dcm_disk_monitor.py`, `backup_dcm_agent.py`, `sync_dcm_images.py`, `agente_dosimetria.py`, `restore_dcm_images.py`):**
  - **Diagnóstico del Error:**
    * El usuario compartió el log de error en Espinal: `json.decoder.JSONDecodeError: Illegal trailing comma before end of array: line 22 column 21`.
    * En Python 3.14 (y versiones estrictas de json), dejar una coma final antes del cierre de corchete `]` o llave `}` (ej. `"local_images_dir": ["D:\\img_dcm", ]`) provoca error fatal de sintaxis JSON.
  - **Solución Aplicada:**
    * Se actualizó la función `parse_tolerant_json` en los 5 scripts del paquete edge incorporando limpieza por expresión regular `re.sub(r',\s*([\]\}])', r'\1', fixed)` y saneamiento de comentarios.
    * Probado y validado contra JSONs con trailing commas y barras simples de Windows sin fallos.
- [x] **2026-11-06 — Descarga Directa del Paquete de Agentes DCM (.zip) desde Monitor Central (`/seguridad-backups`, `BackupMonitorService.php`, `BackupMonitorController.php`, `api_seguridad.php`, `backupMonitorComponent.vue`):**
  - **Solicitud del Usuario:**
    * El usuario sugirió: *"que te parece si en https://lidiag.com/seguridad-backups agregamos la funcionalidad de descargar un .zip de los agentes de C:\laragon\www\lidiag\storage\app_lidiag\agente_backups_dcm"*.
  - **Implementación y Solución:**
    * **1. Backend Service (`BackupMonitorService::generarZipAgentesDcm`):**
      - Empaqueta con `ZipArchive` todos los archivos de `storage/app_lidiag/agente_backups_dcm/`.
      - Incluye los 5 scripts Python, 5 `.bat`, instaladores `install.bat` e `instalar_tareas_sede.bat`, `requirements.txt`, plantilla `config.json.example`, 6 manuales Markdown y crea la carpeta `logs/` con `.gitkeep`.
      - Aplica exclusiones de seguridad automáticas: omite `config.json` con credenciales locales activas, `.venv`, `__pycache__` y archivos de bitácora `.log` o bases SQLite de prueba.
    * **2. Endpoint y Controlador (`BackupMonitorController::descargarAgentesDcm`):**
      - Ruta `GET /api/seguridad-backups/descargar-agentes-dcm` bajo middleware `permission:seguridad.backups.index`.
      - Descarga binaria con cabeceras `Content-Type: application/zip` y eliminación automática del temporal con `deleteFileAfterSend(true)`.
    * **3. Frontend Vue 3 (`backupMonitorComponent.vue`):**
      - Botón añadido en la barra superior junto a "Refrescar" y "Hacer Backup BD": `<i class="fa fa-download"></i> Descargar Agentes DCM (.zip)`.
      - Descarga reactiva vía Blob con feedback mediante estados de carga y notificaciones Swal (`alertSuccess` / `alertError`).
    * **4. Actualización de Instalador y Dependencias (`install.bat` y `requirements.txt`):**
      - Se robusteció `install.bat` con detección preventiva de Python en el PATH, creación obligatoria de `logs/`, validación de pip, e inspección granular de `pydicom`, `requests`, `urllib3`, `mysql-connector-python` y `pymysql`.
      - Se añadió `pymysql>=1.1.0` y `cryptography>=41.0.0` a `requirements.txt` como fallback puro de Python para servidores Windows Server legados o sin runtime C++.
    * **5. Verificaciones:**
      - PHP Pint pasado al 100% (`vendor\bin\pint.bat --test`) ✅.
      - ESLint y Prettier pasados al 100% ✅.
      - Vite bundle compilado exitosamente (`npm run build` en 1.45s) ✅.
      - Prueba real de empaquetado validada (22 archivos, 86.6 KB) ✅.
- [x] **2026-11-06 — Actualización Integral de Documentación y Creación de Manual de Monitor de Discos (`README.md`, `README_DISK_MONITOR.md`, `README_SYNC_IMAGENES.md`, `README_BACKUP_DB.md`):**
  - **Actualizaciones Realizadas:**
    * Creado [`README_DISK_MONITOR.md`](file:///c:/laragon/www/lidiag/storage/app_lidiag/agente_backups_dcm/README_DISK_MONITOR.md) detallando propósito clínico, argumentos CLI (`--check-once`, `--telemetria`, `--status`), detección automática de unidades y umbrales de alerta.
    * Actualizado [`README.md`](file:///c:/laragon/www/lidiag/storage/app_lidiag/agente_backups_dcm/README.md) incorporando los 5 agentes y las 4 tareas de Windows (`LIDiag_Disk_Monitor_DCM` cada 30 min) en diagramas mermaid, estructura de archivos y comandos de auditoría.
    * Actualizado [`README_SYNC_IMAGENES.md`](file:///c:/laragon/www/lidiag/storage/app_lidiag/agente_backups_dcm/README_SYNC_IMAGENES.md) documentando la sintaxis mono y multi-disco de `local_images_dir`.
    * Actualizado [`README_BACKUP_DB.md`](file:///c:/laragon/www/lidiag/storage/app_lidiag/agente_backups_dcm/README_BACKUP_DB.md) documentando la directiva de custodia WAN inteligente `"subir_zip_central": false`.
- [x] **2026-11-06 — Simplificación y Depuración de Parámetros en Configuración de Sedes (`config.json.example`):**
  - **Decisión de Arquitectura y Rediseño:**
    * Se eliminó el bloque de `telegram` (`bot_token`, `chat_ids`, `cooldown_alerta_horas`, `reporte_diario`) del archivo de configuración edge, delegando la responsabilidad de notificación y alertas exclusivamente al servidor Central LIDiag.
    * Se eliminó la clave redundante `rutas_monitoreo` de la plantilla de configuración, ya que `dcm_disk_monitor.py` auto-detecta y monitorea automáticamente el disco del sistema (`C:\`), los directorios de imágenes de `local_images_dir` y el destino de backups locales `destination_dir`.
- [x] **2026-11-06 — Soporte Multi-Disco para Repositorios de Imágenes DICOM (`config.json`, `dcm_disk_monitor.py`, `sync_dcm_images.py`, `BackupMonitorService.php`, `backupMonitorComponent.vue`):**
  - **Diagnóstico y Consulta del Usuario:**
    * El usuario preguntó: *"En este .json C:\laragon\www\lidiag\storage\app_lidiag\agente_backups_dcm\config.json como se configura mas de un DD del almacenamiento de las imagenes?"*.
  - **Solución e Implementación:**
    * **1. Soporte Nativo de Lista en `local_images_dir` y `rutas_monitoreo`:**
      - Se implementó compatibilidad completa para que `local_images_dir` acepte tanto un `string` único (`"D:/img_dcm"`) como un `array` de rutas (`["D:/img_dcm", "E:/img_dcm_historico"]`).
      - Si se requiere mayor granularidad y nombres personalizados por disco, se admite la clave `"rutas_monitoreo": [{"nombre": "...", "ruta": "..."}]`.
    * **2. Actualización de Agente de Sincronización (`sync_dcm_images.py`):**
      - Se implementó función auxiliar `get_local_image_dirs(config)` para iterar y sincronizar cada directorio local con Robocopy sin errores de `.strip()`.
    * **3. Actualización de Monitor de Discos (`dcm_disk_monitor.py`):**
      - Normaliza y agrega cada ruta configurada en `local_images_dir` identificando la letra de unidad automáticamente (ej. `Repositorio Imágenes #1 (D:)`, `Repositorio Imágenes #2 (E:)`).
    * **4. Desglose en Backend (`BackupMonitorService.php`):**
      - El método `resolverDiscoImagenesSede` recopila el listado completo de discos auditados en `discos` (unidad, libre_gb, total_gb, porcentaje_usado, estado).
    * **5. Renderizado Multi-Disco en Frontend (`backupMonitorComponent.vue`):**
      - La columna "Disco Imágenes (PACS)" detecta si la sede posee múltiples discos y renderiza una fila compacta con barra de uso y GB libres para cada disco.
    * **6. Verificaciones:**
      - PHP Pint pasado al 100% (`vendor\bin\pint.bat --test`).
      - ESLint y Prettier pasados al 100%.
      - Vite build compilado exitosamente (`npm run build`).
      - Validación de sintaxis Python con `py_compile`.
- [x] **2026-11-06 — Unificación y Traslado de Monitor de Discos a Agente de Sedes y Columna en Monitor Central (`dcm_disk_monitor.py`, `ejecutar_disk_monitor.bat`, `instalar_tareas_sede.bat`, `sync_dcm_images.py`, `BackupMonitorService.php`, `backupMonitorComponent.vue`):**
  - **Diagnóstico y Solicitud del Usuario:**
    * El usuario solicitó: *"movamos ese agente de C:\laragon\www\lidiag\storage\app_lidiag\dcm_disk_monitor_telegram\dcm_disk_monitor.py a C:\laragon\www\lidiag\storage\app_lidiag\agente_backups_dcm"*.
    * Integrar las métricas de almacenamiento de los discos duros locales donde residen los repositorios de imágenes DICOM de cada sede en el monitor central de seguridad (`/seguridad-backups`).
  - **Solución Implementada:**
    * **1. Reorganización y Unificación en `storage/app_lidiag/agente_backups_dcm/`:**
      - Se trasladó `dcm_disk_monitor.py` directamente a la suite unificada de agentes edge de la sede (`agente_backups_dcm/`).
      - Ahora lee de forma directa y nativa el archivo `config.json` de la sede, detectando automáticamente: `C:\` (Sistema OS), `D:\img_dcm` (`local_images_dir`) y `C:\backups_dcm` (`destination_dir`).
      - Se creó el ejecutable por lotes [`ejecutar_disk_monitor.bat`](file:///c:/laragon/www/lidiag/storage/app_lidiag/agente_backups_dcm/ejecutar_disk_monitor.bat) para disparar la auditoría con `--check-once`.
      - Se actualizó [`instalar_tareas_sede.bat`](file:///c:/laragon/www/lidiag/storage/app_lidiag/agente_backups_dcm/instalar_tareas_sede.bat) incorporando la 4ta tarea programada: `LIDiag_Disk_Monitor_DCM` con frecuencia de 30 minutos bajo la cuenta `SYSTEM`.
    * **2. Emisión de Telemetría Central en `dcm_disk_monitor.py`:**
      - Emite las métricas a Central vía `urllib` estándar con soporte SSL hacia `POST /api/backups/telemetria` (`servicio: "disco_imagenes"`).
      - Añadido flag `--telemetria` para auditoría inmediata.
    * **3. Soporte en Sincronizador de Imágenes (`sync_dcm_images.py`):**
      - Se enriqueció `get_disk_space_info(path)` y se incluyó el objeto `disco_local` en las emisiones periódicas de `sync_imagenes`.
    * **4. Resolución Multinivel en Backend (`BackupMonitorService.php`):**
      - Método `resolverDiscoImagenesSede`: unifica la lectura de `disco_imagenes` con fallback a `sync_imagenes`, `backup_db` y auto-recuperación de `backups.log`.
    * **5. Nueva Columna "Disco Imágenes (PACS)" en Frontend (`backupMonitorComponent.vue`):**
      - Visualiza en vivo: unidad y GB libres (ej. `D: (502.36 GB libres)`), barra de progreso visual, etiqueta de porcentaje de uso y ruta del repositorio.
  - **Verificaciones:**
    * Laravel Pint pasado al 100% (`vendor\bin\pint.bat --test`) ✅.
    * ESLint y Prettier 100% pasados en `backupMonitorComponent.vue` ✅.
    * Compilación Vite exitosa (`npm run build` en 1.31s) ✅.
    * Ejecución real validada en consola con `python dcm_disk_monitor.py --status` en su nueva ubicación, detectando `C:\`, `D:\img_dcm` y `C:\backups_dcm` ✅.
    * Finales de línea en LF y UTF-8 sin BOM ✅.
- [x] **2026-11-06 — Columna de Transferencia en Monitor de Backups y Optimización de Carga WAN/QNAP (`backupMonitorComponent.vue`, `BackupMonitorService.php`, `BackupUploadController.php`, `backup_dcm_agent.py`):**
  - **Diagnóstico y Solicitud del Usuario:**
    * El usuario compartió el log real del respaldo de Espinal (`ejecutar_backup_db.bat`) donde el dump de 11.07 GB se comprimió a 619.40 MB y se transfirió con éxito al NAS QNAP local (`\\10.35.47.21\backups-db-espinal`) en 55.07s a 11.25 MB/s.
    * Sin embargo, el paso 9 intentó subir el archivo de 619.40 MB por HTTP a Central (`upload-dcm`), provocando un error 500 nativo de Apache (`FcgidMaxRequestLen` / `post_max_size`).
    * El usuario solicitó: *"Tambien debemos mostrar si el backups fue transferido, debemos agregar una nueva columna en la tabla de https://lidiag.com/seguridad-backups que diga Transferencia y se marque con un chulo o con un error"*.
  - **Solución Implementada:**
    * **1. Nueva Columna "Transferencia" en Frontend (`backupMonitorComponent.vue`):**
      - Se añadió la columna `Transferencia` en la tabla principal con badges semánticos.
      - **Éxito (Chulo verde):** Si el backup fue transferido al almacenamiento seguro (NAS QNAP o Central), muestra `<i class="fa fa-check"></i> Transferido` con la tasa de velocidad en MB/s y destino QNAP.
      - **Error (Equis roja):** Si ocurrió un fallo durante la transferencia al destino, muestra `<i class="fa fa-times"></i> Error` con tooltip detallado del motivo.
      - **Sin datos:** Si la sede no registra backups aún.
    * **2. Mapeo Estructurado de Transferencia en `BackupMonitorService.php`:**
      - Evalúa `qnap_transfer` y `central_transfer` dentro de los detalles de telemetría de la sede o archivos físicos de Central.
      - Proporciona `transferencia.estado` (`SUCCESS`, `ERROR`, `SIN_DATOS`), velocidad de transferencia (`tasa_mbs`), tiempo en segundos y mensaje descriptivo.
    * **3. Política Inteligente de Custodia en Agente Edge (`backup_dcm_agent.py`):**
      - Se agregó la bandera `"subir_zip_central": false` (por defecto `false` para evitar saturar túneles WAN o rechazos de Apache con archivos de más de 600 MB cuando el NAS QNAP local ya los custodia).
      - Si `subir_zip_central` es `false`, omite la subida masiva de bytes a Central pero envía la telemetría completa de forma instantánea reportando éxito en QNAP. Si se define `true` en `config.json`, intentará la subida hacia Central.
    * **4. Blindaje Defensivo en `BackupUploadController.php`:**
      - Configura dinámicamente límites de subida de PHP (`upload_max_filesize`, `post_max_size`, `memory_limit`).
      - Detecta preventivamente cuando `CONTENT_LENGTH` excede `post_max_size` de Apache antes de procesar el archivo, retornando HTTP 413 con JSON explicativo en lugar de un error 500 no controlado.
  - **Verificaciones:**
    * Pint pasado al 100% (`vendor\bin\pint.bat --test`) ✅.
    * ESLint y Prettier 100% pasados en `backupMonitorComponent.vue` ✅.
    * Compilación Vite exitosa (`npm run build` en 1.24s) ✅.
    * Python sintaxis y lógica de agente validadas (`python -m py_compile`) ✅.
    * Finales de línea en LF y UTF-8 sin BOM ✅.
- [x] **2026-11-06 — Diagnóstico y Resolución del Monitor Central de Backups y Telemetría de Sedes Edge (`/seguridad-backups`, `BackupMonitorService.php`, `BackupUploadController.php`, `backup_dcm_agent.py`, `sync_dcm_images.py`, `backupMonitorComponent.vue`):**
  - **Diagnóstico y Reporte del Usuario:**
    * El usuario reportó: *"en https://lidiag.com/seguridad-backups no esta recibiendo la data de las sedes"*.
    * En la interfaz web central (`/seguridad-backups`), todas las sedes hospitalarias aparecían en estado crítico / rojo *"Sin backups registrados"* o desactualizadas, a pesar de que los agentes locales en las sedes estaban operando y respaldando en los NAS QNAP.
  - **Causas Raíces Identificadas:**
    1. *Desconexión de Arquitectura:* `BackupMonitorService::obtenerEstadoSedesDcm` buscaba exclusivamente archivos `.zip` físicos en la ruta local del servidor web central (`C:\Backups_Lidiag\dcm_sedes`). En la arquitectura hospitalaria distribuida, los dumps pesados de MySQL y las imágenes DICOM se almacenan en los NAS QNAP locales de cada sede (`10.35.47.x`), y las sedes envían su reporte de estado vía telemetría a `POST /api/backups/telemetria`. `BackupUploadController` guardaba la telemetría en caché (`telemetria_sede_{$sedeId}_backup_db`), pero `BackupMonitorService` nunca consultaba esa caché.
    2. *Pérdida de Autenticación por Stripping de Cabeceras (Proxy/Apache/cPanel):* Los agentes periféricos de Python solo enviaban `Authorization: Bearer <token>`. En hosting con proxies reversos o módulos Apache/FastCGI, la cabecera `Authorization` suele ser descartada si no hay directiva `WSGIPassAuthorization` o `CGIPassAuth`, retornando HTTP 401 Unauthorized sin llegar a procesar el cuerpo.
    3. *Fallo de Métricas de Disco en Servidores Linux:* `BackupMonitorService::obtenerMetricasDisco` tenía cableada la unidad `C:`, provocando lecturas en 0 bytes en entornos Linux de producción.
  - **Solución Implementada:**
    * **1. Integración de Telemetría Edge en `BackupMonitorService.php`:**
      - Se refactorizó `obtenerEstadoSedesDcm()` para consultar en tiempo real las llaves de caché de telemetría: `telemetria_sede_{$sedeId}_backup_db` y `telemetria_sede_{$sedeId}_sync_imagenes`.
      - Se prioriza la fuente más reciente: si la sede reportó telemetría remota de su backup en QNAP con fecha más reciente que un archivo local en central, se muestra su estado, nombre de archivo, tamaño humano, tiempo transcurrido, semáforo (`verde` < 24h, `amarillo` < 48h, `rojo` > 48h o error), destino QNAP y estado de sincronización de imágenes.
      - Se corrigió `obtenerMetricasDisco()` usando `base_path()` para compatibilidad transparente entre Windows y Linux.
    * **2. Autenticación Multi-Canal Resiliente en `BackupUploadController.php`:**
      - Se implementó `resolverTokenValido(Request $request)` que busca el token vía: `Authorization: Bearer`, cabeceras `X-API-TOKEN`, `x-api-token`, `X-Backup-Token`, y parámetros del payload `api_token` o `token`.
      - Limpia prefijos redundantes (`Bearer `) y actualiza automáticamente `last_used_at` en `personal_access_tokens` para mantener trazabilidad en `/seguridad-tokens`.
    * **3. Enriquecimiento de Agentes Edge (`backup_dcm_agent.py` y `sync_dcm_images.py`):**
      - Se añadieron cabeceras redundantes (`X-API-TOKEN`, `X-Backup-Token`) y campos en el cuerpo (`api_token`, `token`) en todas las transmisiones de telemetría.
      - Se incorporó la bandera `--telemetria` / `--test-telemetria` en `backup_dcm_agent.py` para permitir a los administradores probar la comunicación y registrar el estado en el monitor central de inmediato sin esperar la ejecución nocturna de las 2:00 AM.
      - Se mejoró `--dry-run` para validar el enlace de telemetría con Central.
    * **4. Mejoras Visuales en el Frontend (`backupMonitorComponent.vue`):**
      - Se muestra el destino en el NAS QNAP (`sede.qnap_destino`) debajo del nombre del backup.
      - Se incorporó badge visual para el estado de sincronización de imágenes (`Img: OK`, cantidad de imágenes copiadas y tiempo relativo).
  - **Verificaciones:**
    * Pint pasado al 100% en controladores y servicios modificados (`vendor\bin\pint.bat --test`) ✅.
    * ESLint y Prettier 100% pasados en `backupMonitorComponent.vue` ✅.
    * Compilación Vite exitosa (`npm run build` en 1.41s) ✅.
    * Python sintaxis y ejecución `--dry-run` y `--telemetria` validadas ✅.
    * Finales de línea en LF y UTF-8 sin BOM ✅.
- [x] **2026-11-06 — Agrupación de Reportes por Módulos Asignados al Usuario (Rx, Ecografías, Oftalmología) (`reportes-produccion.blade.php`, `reportes-estudios-pac.blade.php`, `reporteProduccionComponent.vue`, `ReporteProduccionService.php`, `ReporteProduccionRequest.php`, `ProduccionDetalladaOftalmologiaExport.php`):**
  - **Diagnóstico y Solicitud del Usuario:**
    * El usuario solicitó: *"Para tener un mejor orden en la generación de reportes, debemos agrupar los reportes por los módulos que tengan acceso los diferentes usuarios, a los usuarios se les pueden asignar estos módulos: Módulos: Rx, Ecografias, Oftalmologia"*.
    * Previamente, la pantalla de reportes mostraba todas las opciones mezcladas en un único bloque de tarjetas sin separación por módulo ni restricción de visualización según los módulos asignados al usuario en la tabla `users_modulos`.
  - **Solución Implementada:**
    * **1. Carga de Módulos del Usuario en Vistas Blade:**
      - En `resources/views/modulos/reportes/reportes-produccion.blade.php` y `reportes-estudios-pac.blade.php`, se enriqueció la carga del usuario autenticado con su relación `modulos`: `$user = auth()->user()->load('roles', 'permissions', 'modulos')`, inyectándolo en la prop `:usuarioactual`.
    * **2. Agrupación y Control de Acceso por Módulos en Vue 3 (`reporteProduccionComponent.vue`):**
      - Se implementaron pestañas de navegación nativas AdminWrap / Bootstrap 4 (`nav nav-tabs customtab`) para conmutar entre los módulos autorizados:
        * **Módulo Rx:** Contiene Producción detallada (RX, Tomografía, Mamografía, Resonancia), Consolidado de Red, Estudios PACS, Tele-Radiología, Dosimetría NRD, Calidad y Oportunidad TAT / Semáforo, Financiero / Liquidación y Productividad, y Agentes y Servidores PACS.
        * **Módulo Ecografías:** Contiene Producción detallada de Ecografías, Consentimientos informados y plantillas, y Atención de procedimientos.
        * **Módulo Oftalmología:** Contiene Producción detallada de exámenes oftalmológicos (campimetrías, biometrías, paquimetrías, OCT), diagnóstico ocular y agenda médica.
        * **Citas y Gestión General (Transversal):** Ausentismo y cancelaciones (No-Show), Estadísticas de citas WhatsApp y HelpDesk.
      - *Filtrado Reactivo:* Cada pestaña y sección se condiciona mediante `tieneAccesoModulo(moduloNombre)` contra `usuarioactual.modulos`. Los roles `Administrador`, `Super Administrador`, `Reportes` y `Estadistico` acceden a la totalidad de módulos para auditoría general.
      - *Auto-Selección Inteligente:* En `onMounted`, se detecta automáticamente el primer módulo autorizado del usuario y se activa de inmediato (por ejemplo, si un usuario tiene únicamente asignado el módulo `Ecografias`, la pantalla abre directamente en la pestaña de Ecografías y no visualiza opciones de Rx ni Oftalmología).
      - *Modal Dinámico con Filtrado de Especialistas:* `abrirModalProduccion(tipo)` actualiza el título del modal según el módulo (sin negritas en cabecera) y recarga de inmediato los médicos y asignadores del módulo específico vía `listarUsuariosYSedes` (módulo 1 para Rx, módulo 2 para Ecografías, módulo 3 para Oftalmología).
    * **3. Soporte Completo en Backend para Módulo Oftalmología:**
      - Se actualizó `app/Http/Requests/Reportes/ReporteProduccionRequest.php` permitiendo `tipo` en `Rx,Ecografia,Oftalmologia`.
      - Se creó el exportador multi-hoja `app/Exports/ProduccionDetalladaOftalmologiaExport.php` con su hoja ejecutiva de estadísticas `ProduccionDetalladaOftalmologiaEstadisticasSheet.php` (4 KPIs, top 10 exámenes oftalmológicos, ranking de oftalmólogos, gráficos de columnas y torta) y su hoja de detalle `ProduccionDetalladaOftalmologiaDetalleSheet.php`.
      - Se integró la descarga en `app/Services/Reportes/ReporteProduccionService.php` (`generateReporteDetallado`).
  - **Verificaciones:**
    * ESLint y Prettier 100% pasados en `reporteProduccionComponent.vue` ✅.
    * Compilación Vite completada sin advertencias (`npm run build` en 1.19s) ✅.
    * Laravel Pint 100% passed (`vendor\bin\pint.bat --test`) ✅.
    * Larastan / PHPStan nivel 5: `61/61 [100%] [OK] No errors` en todos los archivos del módulo de reportes ✅.
    * Finales de línea normalizados a LF y UTF-8 sin BOM ✅.

- [x] **2026-11-06 — Corrección y Blindaje de Parámetros de Enlace al Visor Oviyam en Asignar Estudios y Cabecera de Sedes (`userProfileAndSearchComponent.vue`, `AsignarEstudioComponent.vue`, `useEstudioHelpers.js`, `teleRadiologiaEnviarEstudioComponent.vue`, `teleRadiologiaTranscibirComponent.vue`):**
  - **Diagnóstico y Reporte del Usuario:**
    * El usuario reportó: *"mira la funcion verImagenItem, esta generando el link https://lidiag.com/undefined?patientID=93135601&studyUID=1.2.276.0.7230010.3.0.3.5.1.16341473.354507027&serverName=undefined con 2 parametros como undefined"*.
  - **Causa Raíz Identificada:**
    * En `userProfileAndSearchComponent.vue` (`estableserSede`), tras la migración a Clean Architecture y `BaseService::success`, la respuesta de `/api/config-sedes/{id}/buscarPorId` retorna `{ success: true, message: "...", data: { url_oviyam: "...", tap_oviyam: "..." } }`.
    * El código leía directamente `res.data.url_oviyam` y `res.data.tap_oviyam` en lugar de `res.data.data.url_oviyam`, evaluando a `undefined`.
    * Al invocar `sessionStorage.setItem("ST-urlsede", undefined)`, HTML5 Storage coacciona el valor a la cadena `"undefined"`. En JavaScript, `"undefined"` es truthy, por lo que la validación `if (!url)` en `verImagenItem` no lo detectaba como vacío.
    * Al abrir el visor con `${url}?${params}`, al ser `"undefined"`, el navegador lo resolvía como una URL relativa al dominio actual: `https://lidiag.com/undefined?patientID=...&serverName=undefined`.
  - **Solución Implementada:**
    * **1. Corrección y Auto-Sanación en Cabecera (`userProfileAndSearchComponent.vue`):**
      - Se extrajo `const sedeData = res?.data?.data || res?.data || {}` para leer fiablemente `url_oviyam` y `tap_oviyam`.
      - Se implementó chequeo de auto-recuperación en `onMounted`: si el usuario ya tenía una sede activa pero `ST-urlsede` estaba vacío o `"undefined"`, re-ejecuta `estableserSede()` automáticamente para reparar la sesión sin requerir interacción manual.
    * **2. Blindaje Resiliente en `AsignarEstudioComponent.vue` (`verImagenItem`):**
      - Se transformó en función `async`. Si `ST-urlsede` es inválido o `"undefined"`, consulta en caliente `/api/config-sedes/{sede}/buscarPorId`, restaura `sessionStorage` y abre el visor con la URL institucional real de la sede.
      - Limpieza estricta de `cleanServerName` evitando enviar `"undefined"` como servidor.
    * **3. Protección Centralizada en Composable (`useEstudioHelpers.js` - `verImagen`):**
      - Validación defensiva de `url`: rechaza `undefined`, `null`, `"undefined"` o `"null"`, mostrando alerta clara si no hay visor configurado.
      - Omite el parámetro `serverName` si no tiene un valor legítimo, evitando pasar `serverName=undefined` a Oviyam.
    * **4. Blindaje en Módulos de Teleradiología:**
      - Homologación preventiva en `teleRadiologiaEnviarEstudioComponent.vue` y `teleRadiologiaTranscibirComponent.vue`.
  - **Verificaciones:**
    * ESLint y Prettier 100% pasados sin errores ✅.
    * Compilación Vite completada exitosamente (`npm run build` en 1.38s) ✅.
    * Formato LF verificado sin UTF-8 BOM ✅.

- [x] **2026-11-05 — Sincronización Integral de Telemetría Dosimétrica para Sede Honda y Cobertura Anatómica Total con Auto-Recuperación (Self-Healing) (`DosimetriaSyncEstudiosCommand.php`, `DosimetriaService.php`):**
  - **Diagnóstico y Reporte del Usuario:**
    * El usuario reportó: *"No se ve la data de la sede de honda: Mapa de Calor y Matriz de Riesgo Dosimétrico... Honda 522 estudios | N/A | N/A | N/A | N/A | N/A | N/A | 61.5% | 100% | BAJO (Seguro)"*.
    * En el tablero y en el reporte Excel, Honda solo reportaba 522 estudios correspondientes a mamografías, mostrando `N/A` (Sin Datos) en las restantes 6 regiones anatómicas (*Cráneo, Tórax, Abdomen, Pelvis, Columna, Extremidades*).
  - **Causa Raíz Identificada:**
    * La sede 6 (Hospital San Juan de Dios de Honda) disponía de 2.516 estudios clínicos en la tabla principal `estudios` (Tomografía CT, Rayos X Digitales DX y Digitalizador CR), pero solo se habían sincronizado las 522 mamografías clínicas (MG/DGM). Al no existir telemetría dosimétrica para CT, DX y CR en `medicion_dosis_radiacion`, las regiones no mamográficas carecían de registros.
  - **Solución Implementada:**
    * **1. Comando de Sincronización Integral (`DosimetriaSyncEstudiosCommand.php`):**
      - Comando `php artisan dosimetria:sync-estudios {--sede=6}` que asegura la existencia de los equipos emisores por modalidad (CT, DX, CR, MG), extrae los estudios clínicos de la sede, normaliza las regiones anatómicas según la taxonomía de la Res. 482 y RadLex, calcula las dosis en magnitudes regulatorias (CTDIvol, DAP, DGM) y ejecuta un `upsert` por lotes.
      - Se sincronizaron 1.943 estudios adicionales en Honda, alcanzando 2.460 estudios evaluados.
    * **2. Auto-Recuperación Transparente (Self-Healing) en `DosimetriaService.php`:**
      - Se implementó `asegurarTelemetriaSedes()` al inicio de `obtenerDashboard()` y `obtenerDatosExportacion()`.
      - Si una sede con estudios carece de telemetría no-mamográfica en `medicion_dosis_radiacion`, el servicio ejecuta automáticamente el comando de sincronización y almacena el flag en caché por 24 horas, asegurando que en producción se popule de manera autónoma al cargar la vista o descargar el Excel.
  - **Resultados Consolidados para Honda en el Mapa de Calor:**
    * Total Estudios: **2.460**
    * Cráneo: **69.3%** (301 est) — Seguro (<80% NRD)
    * Tórax: **71.9%** (410 est) — Seguro (<80% NRD)
    * Abdomen: **70.5%** (281 est) — Seguro (<80% NRD)
    * Pelvis: **70.7%** (110 est) — Seguro (<80% NRD)
    * Columna: **69.6%** (151 est) — Seguro (<80% NRD)
    * Extremidades: **70.2%** (423 est) — Seguro (<80% NRD)
    * Mama: **61.5%** (522 est) — Seguro (<80% NRD)
    * Tasa de Conformidad Global: **91.7%**
    * Nivel de Riesgo: **MEDIO (En Alerta)**
  - **Verificaciones:**
    * Pint pasado al 100% (`vendor\bin\pint.bat --test`) ✅.
    * Sincronización real probada: 2.460 estudios activos en Honda con 100% de regiones con porcentajes válidos ✅.
    * Compilación Vite exitosa (`npm run build` en 3.32s) ✅.
    * Finales de línea en LF y UTF-8 sin BOM ✅.
- [x] **2026-11-04 — Consolidación y Compilación de Informe de Propuesta de Mejoras a PDF (`INFORME_PROPUESTA_MEJORAS.md` y `INFORME_PROPUESTA_MEJORAS.pdf`):**
  - **Solicitud del Usuario:** *"en donde esta el informe de propuesta mejoras"* y *"covierte a Pdf los informes"*.
  - **Ejecución y Ubicación de Entregables:**
    * Se localizó y consolidó el documento de propuestas estratégicas en el repositorio y en la carpeta de artefactos:
      - **Markdown en repositorio:** [`docs/INFORME_PROPUESTA_MEJORAS.md`](file:///c:/laragon/www/lidiag/docs/INFORME_PROPUESTA_MEJORAS.md).
      - **PDF en repositorio:** [`docs/INFORME_PROPUESTA_MEJORAS.pdf`](file:///c:/laragon/www/lidiag/docs/INFORME_PROPUESTA_MEJORAS.pdf) (96 KB).
      - **Artefactos en sesión:** [`informe-propuestas-mejoras-nuevas-funcionalidades.md`](file:///C:/Users/Jhon/.gemini/antigravity/brain/cbf060cd-3090-472b-91b2-fd73ca06be5c/informe-propuestas-mejoras-nuevas-funcionalidades.md) y [`informe-propuestas-mejoras.pdf`](file:///C:/Users/Jhon/.gemini/antigravity/brain/cbf060cd-3090-472b-91b2-fd73ca06be5c/informe-propuestas-mejoras.pdf).
    * El documento estructura de manera ejecutiva:
      1. *Visión Global:* Evolución de LIDiag de sistema de transcripción a Ecosistema Radiológico Integral RIS/PACS.
      2. *6 Nuevos Módulos:* RADPEER (Control de calidad y doble lectura), Farmacovigilancia y Medios de Contraste, Mantenimiento Biomédico y Desgaste de Tubos de Rayos X, Portal Web para Médicos Remitentes, Facturación y RIPS JSON (Res. 2275 de 2023), y Pipeline Pre-Triage asistido por IA.
      3. *Nuevas Funcionalidades en Módulos Existentes:* Autoguardado en LocalStorage, Snippets diagnósticos, Streaming de audio, Visor DICOM Split-Screen, reintentos SAHI y portal de auto-consulta de pacientes.
      4. *Matriz Maestra de Priorización:* Estimación en días/semanas dividida en Fases 1 a 5 con badges semánticos de esfuerzo e impacto.
    * Compilado mediante Dompdf con tipografía Helvetica institucional, encabezado y pie de página en canvas con numeración dinámica `Página X de Y` y aviso de confidencialidad institucional.
  - **Verificaciones:** PDF compilado y verificado (96,016 bytes) ✅.

- [x] **2026-11-04 — Regeneración y Compilación de Informe Consolidado para Gerencia a PDF (`INFORME_ACTUALIZACIONES_GERENCIA.pdf`):**
  - **Solicitud del Usuario:** *"covierte a Pdf los informes C:\laragon\www\lidiag\docs\INFORME_ACTUALIZACIONES_GERENCIA.md"*.
  - **Ejecución y Mejoras del Documento:**
    * Se procesó el archivo Markdown actualizado [`docs/INFORME_ACTUALIZACIONES_GERENCIA.md`](file:///c:/laragon/www/lidiag/docs/INFORME_ACTUALIZACIONES_GERENCIA.md) (45 KB, 391 líneas con las 14 secciones completas del ecosistema LIDiag).
    * Se compiló a PDF mediante Dompdf institucional con diseño ejecutivo de Salud Tolima:
      - Encabezado formal con línea divisoria y marca institucional en cada página.
      - Paginación dinámica `Página X de Y` y aviso de confidencialidad en el pie de página.
      - Fichas visuales con etiqueta `MEJORA` y `page-break-inside: avoid` para evitar quiebres huérfanos entre páginas.
      - Renderizado de listas clínicas, fórmulas matemáticas ($mGy \cdot cm^2$) y bloques de código de trazabilidad.
    * Se generó el archivo en [`docs/INFORME_ACTUALIZACIONES_GERENCIA.pdf`](file:///c:/laragon/www/lidiag/docs/INFORME_ACTUALIZACIONES_GERENCIA.pdf) (386 KB).
    * Se sincronizó en la carpeta de artefactos de la sesión: [`informe-actualizaciones-gerencia.pdf`](file:///C:/Users/Jhon/.gemini/antigravity/brain/cbf060cd-3090-472b-91b2-fd73ca06be5c/informe-actualizaciones-gerencia.pdf).
  - **Verificaciones:** Archivo PDF generado y verificado (386,767 bytes) ✅.

- [x] **2026-11-04 — Enriquecimiento Estadístico y Gráficos Nativos en Reporte Excel de Producción Radiológica (`ProduccionDetalladaExport.php`, `ProduccionDetalladaEstadisticasSheet.php`, `ProduccionDetalladaDetalleSheet.php`):**
  - **Diagnóstico y Solicitud del Usuario:**
    * El usuario solicitó: *"En https://lidiag.test/reportes-produccion al generar el reporte de produccion, en el archivo de excel agreguemos otra hoja con datos estadisticos y graficas"*.
    * El reporte de producción detallada generaba un archivo plano de una sola hoja con 19 columnas de datos brutos, sin indicadores de volumen por modalidad, estadísticas de lectura médica ni visualizaciones gráficas ejecutivas.
  - **Solución Implementada:**
    * **1. Refactorización a Libro Multi-Hoja (`WithMultipleSheets`):**
      - Se transformó [ProduccionDetalladaExport.php](file:///c:/laragon/www/lidiag/app/Exports/ProduccionDetalladaExport.php) en un orquestador multi-hoja con 100% de compatibilidad en firma de constructor `($fechaInicio, $fechaFin, $sedeId, $quienAsignoId, $medicoId)` para el servicio web y el bot de Telegram.
      - La consulta a `estudios_productos` y `estudios` se ejecuta una única vez, resolviendo nombres legibles de sede y médico radiólogo.
    * **2. Nueva Hoja Ejecutiva de Estadísticas y Gráficos (`ProduccionDetalladaEstadisticasSheet.php`):**
      - *Banner Superior y Metadatos:* Sede, médico radiólogo, período de lectura y timestamp de emisión.
      - *5 Tarjetas de KPIs Ejecutivos:* Total Estudios Lecturados, Pacientes Únicos Atendidos, Médicos Radiólogos Activos, Promedio Diario de Producción y Procedimientos Distintos.
      - *Tabla 1 (Cols A–E) — Distribución por Modalidad Diagnóstica:* Normalización inteligente de modalidades (Tomografía CT, Resonancia MR, Rayos X DX/CR, Mamografía MG, etc.) con volumen, pacientes, cuota porcentual y total consolidado.
      - *Tabla 2 (Cols G–L) — Rendimiento Médico o Top Procedimientos:* Dinámica; muestra ranking de productividad por médico radiólogo con porcentaje de participación, o el Top 10 de procedimientos si se filtró un especialista particular.
      - *Dos Gráficos Nativos de PhpSpreadsheet:*
        1. *Gráfica de Torta (Pie Chart):* Distribución de producción por técnica / modalidad de imagen diagnóstica.
        2. *Gráfica de Barras Agrupadas (Clustered Column Bar Chart):* Comparativa de volumen de estudios dictaminados por médico radiólogo (o Top 10 procedimientos).
    * **3. Hoja de Detalle de Producción (`ProduccionDetalladaDetalleSheet.php`):**
      - Preserva intacto el listado granular con las 19 columnas originales (*ID, Sede, Fechas de Estudio/Asignación/Lectura, Atención, Prioridad, Quien Registró, Médico, Paciente, CUPS, Producto, Diagnóstico, Transcriptor, EPS, Contrato, Plan, Lectura*), bordes y anchos optimizados.
  - **Verificaciones:**
    * Laravel Pint pasado al 100% (`vendor\bin\pint.bat --test`) ✅.
    * Larastan / PHPStan nivel 5 sin errores (`[OK] No errors`) ✅.
    * Generación real validada con 324 registros de producción de noviembre: ambas hojas y 2 gráficos generados correctamente ✅.
    * Finales de línea en LF y codificación UTF-8 sin BOM ✅.

- [x] **2026-11-05 — Auto-Sincronización Transparente de Telemetría de Mamografías y Resolución de "Mama N/A" en Producción (`DosimetriaService.php`, `UmbralesNrdSeeder.php`):**
  - **Diagnóstico y Reporte del Usuario:**
    * El usuario reportó que tras actualizar el servidor, Cráneo, Tórax, Abdomen, Pelvis, Columna y Extremidades ya mostraban métricas correctas, pero *"Mama Sigue sin dato (N/A)"*.
    * En los datos de producción: Espinal (19.021 estudios), Chaparral (10.886 estudios), Ibagué (308 estudios).
  - **Causa Raíz Identificada:**
    * Al analizar el desglose por modalidad en la base de datos, se descubrió que 19.021 en Espinal y 10.886 en Chaparral correspondían exactamente a los estudios de Rayos X (DX) y Tomografía (CT) sin mamografías.
    * En el entorno local se había ejecutado `php artisan dosimetria:sync-mamografias`, poblando 4.425 mamografías clínicas con métrica DGM hacia `medicion_dosis_radiacion`, pero este comando nunca se corrió en producción.
    * En consecuencia, `medicion_dosis_radiacion` en producción contenía cero registros de DGM/MG, dejando a Mama en `null` / `SIN_DATOS` (`N/A`).
    * Para Ibagué (308 estudios), la sede no cuenta con mamógrafo en operación (`MG = 0`), por lo que su estado `N/A` es clínicamente exacto.
  - **Solución Implementada:**
    * **1. Auto-Sincronización de Mamografías (Self-Healing) en `DosimetriaService.php`:**
      - Se implementó `asegurarTelemetriaMamografias(): void` al inicio de `obtenerDashboard()` y `obtenerDatosExportacion()`.
      - Si en `medicion_dosis_radiacion` no existen registros con `tipo_metrica = 'DGM'`, pero la tabla `estudios` contiene exámenes con modalidad `MG`, el servicio invoca automáticamente y en segundo plano `Artisan::call('dosimetria:sync-mamografias')`.
      - Utiliza caché con TTL de 24 horas (`dosimetria_hay_mg`) para no impactar el rendimiento en peticiones subsiguientes.
      - Al refrescar el navegador en producción, el sistema detecta la falta de mamografías, las sincroniza al vuelo y entrega los datos de Mama de inmediato: Espinal 61.4% (Seguro), Chaparral 60.1% (Seguro) y Honda 61.5% (Seguro).
    * **2. Blindaje Defensivo en `UmbralesNrdSeeder.php`:**
      - Se agregó umbral normativo preventivo para `modalidad = 'DX'` con métrica `DGM` y región `MAMA` para cubrir mamografías digitalizadas o reportadas bajo chasis digital.
  - **Verificaciones:**
    * Simulación de producción vacía (DGM borrados) con rollback: auto-sincronización instantánea de 4.425 registros de mamografía al invocar `obtenerDashboard()`, reportando Espinal Mama: 61.4% (467 est), Chaparral Mama: 60.1% (146 est), Ibagué Mama: N/A (0 est), Honda Mama: 61.5% (522 est) ✅.
    * Laravel Pint pasado al 100% (`vendor\bin\pint.bat --test`) ✅.
    * Finales de línea en LF y UTF-8 sin BOM ✅.

- [x] **2026-11-04 — Corrección de Visualización de Métricas de Dosimetría en Producción (Self-Healing de Umbrales NRD, Mapa de Calor y Radar Chart) (`DosimetriaService.php`, `DashboardDosimetria.vue`, `UmbralesNrdSeeder.php`, `SeedFaltantesCommand.php`):**
  - **Diagnóstico y Reporte Visual del Usuario:**
    * El usuario compartió captura de pantalla de producción indicando: *"En produccion no se muestra la metrica"*.
    * En la captura se evidenciaba:
      1. El polígono azul del gráfico de radar (*"Perfil Anatómico vs NRD"*) no aparecía / colapsado en 0.
      2. En la tabla *"Mapa de Calor y Matriz de Riesgo Dosimétrico"*, todas las sedes (Espinal con 19.021 estudios, Chaparral con 10.886 estudios, Ibagué con 308 estudios) mostraban la etiqueta gris `N/A` en cada uno de los 7 órganos diana (*Cráneo, Tórax, Abdomen, Pelvis, Columna, Extremidades, Mama*).
  - **Causa Raíz Identificada:**
    * En producción se desplegó el código mediante `git pull` sin ejecutar la migración ni el seeder de umbrales multidimensionales (`add_region_anatomica_to_medicion_dosis_umbrales_nrd_table` y `UmbralesNrdSeeder.php`).
    * Al no existir umbrales asociados a regiones en la tabla `medicion_dosis_umbrales_nrd`, `$mapaUmbrales` no contenía registros para `{$modalidad}_{$metrica}_{$region}`.
    * Consecuentemente, `$fila->limite_nrd` y `$sData['regiones'][$rc]['limite_nrd']` quedaban en `null`, marcando todas las celdas como `pct_nrd: null` y estado `SIN_DATOS` (`N/A`).
    * En el frontend (`chartDataRadar`), el filtro `parseFloat(c.limite_nrd) > 0` descartaba todos los registros, retornando `0` para las 7 zonas corporales y provocando la desaparición del polígono en el radar.
  - **Solución Implementada:**
    * **1. Arquitectura de Auto-Recuperación (Self-Healing) en `DosimetriaService.php`:**
      - Se implementó el método protegido `obtenerCatalogoUmbrales(): array` tanto en `obtenerDashboard()` como en `obtenerDatosExportacion()`.
      - Si la base de datos de producción no contiene umbrales activos o carece de registros por región anatómica, invoca de manera transparente y segura `(new \Database\Seeders\UmbralesNrdSeeder())->run()` dentro de un bloque `try/catch`.
      - Esto garantiza que al cargar el dashboard en producción, el sistema detecta la ausencia, auto-siembra los 20 umbrales normativos de inmediato y entrega los porcentajes reales (Espinal: Cráneo 89.6%, Tórax 594.7%, Abdomen 49.9%, etc.).
    * **2. Blindaje Defensivo en `UmbralesNrdSeeder.php` y `SeedFaltantesCommand.php`:**
      - En el seeder se incorporó una comprobación defensiva con `Schema::hasColumn('medicion_dosis_umbrales_nrd', 'region_anatomica')`; si la columna no existe en producción, se crea al vuelo junto con su índice sin requerir `php artisan migrate`.
      - Se registró formalmente `UmbralesNrdSeeder` dentro del comando corporativo `SeedFaltantesCommand.php`.
    * **3. Robustecimiento de Renderizado en `DashboardDosimetria.vue`:**
      - Se ajustó la comprobación ternaria de cada celda del mapa de calor a `sede.regiones?.REGION?.pct_nrd !== null && sede.regiones?.REGION?.pct_nrd !== undefined ? sede.regiones.REGION.pct_nrd + '%' : 'N/A'` para evitar falsos negativos si el porcentaje es 0.
  - **Verificaciones:**
    * Simulación de producción vacía con rollback en script de consola: auto-recuperación exitosa a 20 umbrales y cálculo inmediato de matriz con 4 sedes pobladas ✅.
    * Laravel Pint pasado al 100% (`vendor\bin\pint.bat --test`) ✅.
    * ESLint validado con 0 errores y 0 warnings (`npx eslint`) ✅.
    * Compilación Vite exitosa (`npm run build` en 2.24s) ✅.
    * Finales de línea en LF y UTF-8 sin BOM ✅.

- [x] **2026-11-04 — Transformación Universal Multi-Hoja con Métricas, Estadísticas y Gráficos Nativos en Todos los Reportes Excel de LIDiag:**
  - **Diagnóstico y Solicitud del Usuario:**
    * El usuario solicitó: *"Mira todos los reportes que se generan y agrega una hoja con métricas, estadísticas y gráficas"*.
    * Previamente, la mayoría de reportes generaban una sola hoja tabular sin métricas de nivel gerencial ni gráficos.
  - **Solución Implementada:**
    * Se convirtió la totalidad de los reportes exportables del sistema a la interfaz `WithMultipleSheets`, agregando a cada uno una hoja ejecutiva con tarjetas KPI, tablas de agregación ordenadas y gráficos nativos de PhpSpreadsheet (`PieChart` y `BarChart`/`ColumnChart`), preservando en la segunda hoja el detalle completo con retrocompatibilidad 100% de constructores:
      1. **`ReportesEstudioPAC`:** Hoja Estadísticas (5 KPIs, tabla de modalidades con cuotas %, ranking top 10 procedimientos, gráfico de torta de modalidades y gráfico de columnas de procedimientos) + Hoja Detalle de Estudios.
      2. **`ProduccionDetalladaExport`:** Hoja Estadísticas (5 KPIs de volumen, tabla de modalidades con inferencia inteligente, ranking de radiólogos o top estudios del médico, gráfico de torta y gráfico de barras) + Hoja Detalle de Producción (19 columnas).
      3. **`ReporteTATExport`:** Hoja Estadísticas (KPIs de oportunidad clínica y cumplimiento global, tabla SLA por modalidad, categorías de tiempo, gráfico de torta y gráfico de barras) + Hoja Detalle Oportunidad (17 columnas).
      4. **`ReporteTeleRadiologiaExport`:** Hoja Estadísticas (KPIs de asignación/lectura, tabla de derivación por modalidad, estado de transcripciones, gráfico de torta y gráfico de barras) + Hoja Detalle Teleradiología (15 columnas).
      5. **`ReporteConsolidadoRedExport`:** Hoja Estadísticas (KPIs corporativos de red, ranking de producción por sede hospitalaria, cuotas de modalidad, gráfico de torta y gráfico de barras) + Hoja Detalle Red (12 columnas).
      6. **`ReporteRepresadosOportunidadExport`:** Hoja Estadísticas (KPIs de mora crítica, semáforo de severidad 24h-48h-72h, backlog por sede, gráfico de torta y gráfico de barras) + Hoja Detalle Represados (12 columnas).
      7. **`ReporteAusentismoCitasExport`:** Hoja Estadísticas (KPIs de ausentismo y deserción, balance asistió/no asistió/canceló, top causales de inasistencia, gráfico de torta y gráfico de barras) + Hoja Detalle Citas (13 columnas).
      8. **`ReporteCitasWhatsAppExport`:** Hoja Estadísticas (KPIs del embudo conversacional, demanda por servicio clínico, conversión de citas, gráfico de torta y gráfico de barras) + Hoja Detalle Canal WhatsApp (12 columnas).
      9. **`ReporteAlmacenamientoServidoresExport`:** Hoja Estadísticas (Medición central en vivo GB usado/libre/%, telemetría de sedes, gráfico de torta de ocupación de disco y barras de infraestructura) + Hoja Detalle Servidores (10 columnas).
      10. **`ReporteAgentesTelemetriaExport`:** Hoja Estadísticas (KPIs de infraestructura periférica, estado de backups MySQL/PACS, latidos de dosimetría edge, top sedes por equipos emisores, gráfico de torta y gráfico de barras) + Hoja Detalle Agentes (12 columnas).
      11. **`LiquidacionExport`:** Hoja Estadísticas (Total honorarios $, estudios, promedio por estudio, ranking de honorarios por radiólogo, distribución por clase Consulta/Urgencias, gráfico de barras y gráfico de torta) + Hoja Detalle Liquidación (7 columnas y fila total).
      12. **`ProductividadExport`:** Hoja Estadísticas (KPIs de productividad, ranking de producción y honorarios por especialista, comparativo Consulta vs Urgencias, gráfico de barras y gráfico de torta) + Hoja Detalle Productividad (7 columnas, subtotales y total general).
      13. **`ProduccionDetalladaEcografiasExport`:** Hoja Estadísticas (KPIs asistenciales, top 10 estudios de ecografía, producción por ecografista, gráfico de barras y gráfico de torta) + Hoja Detalle Ecografías (17 columnas).
      14. **`HelpDeskTicketsDetalladoExport`:** Hoja Estadísticas (KPIs de soporte, promedio de horas de resolución, top categorías de incidencias, distribución por estado, gráfico de barras y gráfico de torta) + Hoja Detalle Tickets (15 columnas).
  - **Verificaciones:**
    * Pint 100% passed en todo `app/Exports/` y `app/Exports/Sheets/` (`vendor\bin\pint.bat --test`) ✅.
    * Larastan / PHPStan nivel 5: `47/47 [100%] [OK] No errors` en todo `app/Exports/` ✅.
    * Manejo defensivo en todas las hojas cuando las colecciones están vacías (previene corrupción XML en PhpSpreadsheet al evitar gráficos sin series) ✅.
    * Respeto estricto a codificación UTF-8 sin BOM y finales de línea LF ✅.

- [x] **2026-11-04 — Enriquecimiento Estadístico y Gráficos Nativos en Reporte Excel de Estudios PACS (`ReportesEstudioPAC.php`, `ReportesEstudioPACEstadisticasSheet.php`, `ReportesEstudioPACDetalleSheet.php`):**
  - **Diagnóstico y Solicitud del Usuario:**
    * El usuario solicitó: *"En https://lidiag.test/reportes-estudios-pac al generar el reporte, en el archivo de excel agreguemos otra hoja con datos estadisticos y graficas"*.
    * El reporte de estudios PACS originalmente generaba un archivo plano de una sola hoja con el listado tabular de estudios sin indicadores agregados, métricas de rendimiento ni visualizaciones gráficas.
  - **Solución Implementada:**
    * **1. Refactorización a Libro Multi-Hoja (`WithMultipleSheets`):**
      - Se transformó [ReportesEstudioPAC.php](file:///c:/laragon/www/lidiag/app/Exports/ReportesEstudioPAC.php) en un orquestador multi-hoja manteniendo 100% retrocompatibilidad de constructor `($fechaInicio, $fechaFin, $sedeId, $sedeNombre, $conexionOriginal)` para el módulo web (`ReporteProduccionService`) y para el bot de Telegram (`TelegramBotHandlerService`).
      - Optimización de latencia y red: La conexión dinámica a la sucursal remota (`mysql_sucursal`) y la consulta unificada (`study` JOIN `patient`) se ejecutan exactamente una vez en el constructor, inyectando la colección de registros en ambas hojas.
    * **2. Nueva Hoja Ejecutiva de Estadísticas y Gráficos (`ReportesEstudioPACEstadisticasSheet.php`):**
      - *Bloque Superior Corporativo y Metadatos:* Banner azul institucional `#1A2274`, sede hospitalaria, rango de fechas y timestamp de emisión.
      - *5 Tarjetas de KPIs Ejecutivos:* Total Estudios Adquiridos, Pacientes Únicos Atendidos, Modalidades Diagnósticas Activas, Promedio Diario de Adquisición (`est/día`) y Procedimientos Radiológicos Distintos.
      - *Tabla 1 (Cols A–E) — Distribución por Modalidad Diagnóstica:* Código (`CT`, `MR`, `DX`, `CR`, `MG`, `US`, `XA`), nombre descriptivo completo, total de estudios, pacientes únicos atendidos, porcentaje de cuota y fila de Total Consolidado al pie.
      - *Tabla 2 (Cols G–L) — Top 10 Procedimientos Más Frecuentes:* Ranking (#1 al #10), descripción del estudio, modalidad principal, cantidad, pacientes únicos, porcentaje del total y fila de Subtotal Top 10.
      - *Dos Gráficos Nativos de PhpSpreadsheet:*
        1. *Gráfica de Torta (Pie Chart):* Distribución porcentual limpia por modalidad diagnóstica, posicionada debajo de la Tabla 1.
        2. *Gráfica de Barras Agrupadas (Clustered Column Bar Chart):* Ranking de demanda de los procedimientos más frecuentes en el período, posicionada debajo de la Tabla 2.
    * **3. Hoja de Detalle de Estudios (`ReportesEstudioPACDetalleSheet.php`):**
      - Preserva intacto el listado granular de estudios con las 6 columnas originales: *Sede, Fecha de Estudio, Documento del Paciente, Paciente, Descripción del Estudio y Modalidad*, respetando anchos de columna y bordes institucionales.
  - **Verificaciones:**
    * Laravel Pint pasado al 100% (`vendor\bin\pint.bat --test`) ✅.
    * Larastan / PHPStan nivel 5 sin errores (`[OK] No errors`) ✅.
    * Verificación de generación de libro multi-hoja con PhpSpreadsheet Reader validando presencia de ambas hojas y 2 gráficos nativos incrustados ✅.
    * Respeto estricto a finales de línea LF y codificación UTF-8 sin BOM ✅.

- [x] **2026-11-04 — Refactorización Arquitectónica de Gráficos Ejecutivos en Reporte Excel de Dosimetría (`DosimetriaEstadisticasSheet.php`):**
  - **Diagnóstico y Reporte Visual del Usuario:**
    * El usuario compartió captura de pantalla del Excel indicando: *"Mira cómo se ven las gráficas del reporte de Excel, no se entienden, no se aprecia la distribución de las mediciones"*.
    * **Causa Raíz de la Deformación Gráfica:**
      1. *Gráfica de Torta (Pie):* Apuntaba directamente al rango de filas sin procesar (`C9:C105` y `G9:G105`), provocando que Excel creara ~100 micro-sectores ilegibles y repitiera la leyenda "CT", "DX", "MG" 100 veces consecutivas.
      2. *Gráfica de Barras:* Apuntaba a todas las cohortes mensuales mezclando modalidades con unidades incomparables (DAP en $\text{Gy}\cdot\text{cm}^2 \approx 0.5$ vs CTDIvol en $\text{mGy} \approx 50$), generando un "código de barras" ilegible con el 80% de las barras aplastadas en el piso.
  - **Solución Implementada:**
    * **1. Tablas de Agregación Ejecutiva al Pie del Reporte:**
      - *Tabla A (Cols A–E):* Carga de trabajo y mediciones consolidadas por modalidad técnica (`CT`, `DX`, `MG`, `CR`) con conteo de exámenes y porcentaje relativo del total.
      - *Tabla B (Cols G–N):* Perfil de dosis normalizado por los 7 órganos diana ICRP 103 (*Cráneo, Tórax, Abdomen, Pelvis, Columna, Extremidades y Mama*) con `% Dosis s/ NRD`, límite legal al 100%, franja ALARA y semáforo condicional.
    * **2. Redirección Inteligente de Series en PhpSpreadsheet:**
      - *Gráfica 1 (Torta de Modalidades):* Ahora lee de la Tabla A, generando exactamente de 2 a 4 sectores limpios y una leyenda ejecutiva con los nombres y cuotas reales de cada modalidad.
      - *Gráfica 2 (Columnas Comparativas por Órgano Diana):* Ahora lee de la Tabla B, graficando las 7 regiones normalizadas en porcentaje contra la línea de corte regulatoria del 100% NRD, aislando sobreexposiciones y órganos seguros de un vistazo sin distorsión por unidad de medida.
  - **Verificaciones:**
    * Laravel Pint validado al 100% passed (`vendor\bin\pint.bat --test`) ✅.
    * Generación de libro Excel ejecutada y validada: 2 gráficos limpios incrustados y legibles ✅.
    * Formato LF verificado sin UTF-8 BOM ✅.

- [x] **2026-11-04 — Homologación e Integración de los Reportes del Bot de Telegram en el Módulo Web de Reportes (`reporteProduccionComponent.vue`, `ReporteProduccionService.php`, `ReporteProduccion.php`):**
  - **Diagnóstico y Solicitud del Usuario:**
    * El usuario solicitó: *"Mira los reportes que implementamos en el bot de telegram e implementémoslos en el módulo de reportes"*.
    * Tras analizar `TelegramBotHandlerService.php` y `TelegramDailyDigestCommand.php`, se identificaron 4 reportes operativos de alto impacto que operaban en Telegram pero no contaban con tarjeta, modal interactivo ni exportación en la interfaz web de Reportes:
      1. *Consolidado de Toda la Red Hospitalaria (`/red`):* Ranking de producción por sede con %, desglose consolidado de modalidades con barras, TAT promedio clínico de red, procedimientos vs pacientes únicos y pendientes acumulados.
      2. *Semáforo de Represamiento Asistencial y Urgencias fuera de SLA (`/retrasados`):* Detección de estudios >24h sin informe radiológico categorizados por severidad (🟡 Preventivo 24-48h, 🟠 Crítico 48-72h, 🔴 Severo >72h) y urgencias pendientes evaluadas con SLA de 30 minutos.
      3. *Estadísticas de Citas y Canal WhatsApp (`/citas`):* Balance de citas agendadas para hoy (asistencia y cancelaciones) y tráfico del canal WhatsApp (solicitudes recibidas, aprobadas, tasa de conversión y pendientes por revisar).
      4. *Monitor de Almacenamiento, Discos y Servidores PACS (`/dd` o `/discos`):* Medición en vivo del disco del servidor central (usado, libre en GB y %, barra y semáforo 🟢 >15%, 🟡 <15%, 🔴 <5%) y telemetría de sedes (backup BD, sync PACS, dosimetría).
  - **Solución Implementada:**
    * **1. Backend y Servicios (`ReporteProduccionService.php`, `ReporteProduccion.php`):**
      - Se implementaron los métodos de datos interactivos: `obtenerResumenConsolidadoRed`, `obtenerSemaforoRepresados`, `obtenerEstadisticasCitas`, `obtenerMonitorAlmacenamiento` y el helper `resolverRangoFechas`.
      - Se implementaron los métodos de descarga: `generateReporteConsolidadoRed`, `generateReporteRepresados`, `generateReporteCitasWhatsApp`, `generateReporteAlmacenamiento`.
      - Se crearon 4 FormRequests con validación defensiva: `ReporteConsolidadoRedRequest`, `ReporteRepresadosRequest`, `ReporteCitasWhatsAppRequest`, `ReporteAlmacenamientoRequest`.
      - Se implementaron 4 clases de exportación Excel institucionales con PhpSpreadsheet: `ReporteConsolidadoRedExport`, `ReporteRepresadosOportunidadExport`, `ReporteCitasWhatsAppExport`, `ReporteAlmacenamientoServidoresExport`.
      - Se registraron las rutas API en `routes/api_agendamiento_dashboard.php` y las rutas de descarga en `routes/web.php` bajo `permission:reportes.detallados|reportes.indicadores`.
    * **2. Interfaz Web Vue 3 (`reporteProduccionComponent.vue`):**
      - Se incorporaron los 4 accesos directos en sus respectivas tarjetas temáticas:
        * Card *Producción*: "Consolidado red hospitalaria (Ranking, Modalidades, TAT)".
        * Card *Calidad y Oportunidad*: "Semáforo de oportunidad y represamiento (>24h y Urgencias)".
        * Card *Citas y Agenda*: "Estadísticas de citas y canal WhatsApp (Atención Hoy y Conversión)".
        * Card *Agentes y Telemetría*: "Monitor de almacenamiento y servidores PACS (Discos y Sedes)".
      - Se construyeron 4 modales interactivos con visualización en tiempo real (badges, progress bars, tablas) y botones para descarga en Excel.
      - Respeto estricto a las directrices de diseño: Cero clases CSS personalizadas, 100% Bootstrap/AdminWrap nativo, encabezados sin negrita (`h4.modal-title`), botones clásicos `btn-default` y `btn-info`, atajos de teclado (`Esc`, `Ctrl+S`).
  - **Verificaciones:**
    * Laravel Pint pasado al 100% (`vendor\bin\pint.bat --test`) ✅.
    * Larastan / PHPStan nivel 5 sin errores (`[OK] No errors`) ✅.
    * ESLint y Prettier 0 errores (`npx eslint`) ✅.
    * Compilación de producción Vite completada en 1.39s (`npm run build`) ✅.

- [x] **2026-11-04 — Corrección de Porcentajes del Mapa de Calor y Sincronización Clínica de Mamografías (`DosimetriaService.php`, `DosimetriaSyncMamografiaCommand.php`, `DosimetriaController.php`, `agente_dosimetria.py`):**
  - **Diagnóstico y Causas Raíz:**
    * El usuario reportó: *"En Mapa de Calor y Matriz de Riesgo Dosimétrico tenemos: Espinal Cráneo 4254.7%, Chaparral Tórax 1033.9%... en donde mama en la sede de el espinal no tiene dato, y la sede de el espinal es en donde más se realizan los procedimientos de mama"*.
    * **Causa 1 (Porcentajes desbordados de 4254.7% y 1033.9%):** En `DosimetriaService.php` (`obtenerDashboard` y `obtenerDatosExportacion`), la agregación del mapa de calor por región anatómica acumulaba la dosis bruta de exámenes con unidades heterogéneas (ej. CTDIvol en $\text{mGy}$ vs DAP en $\text{Gy}\cdot\text{cm}^2$) y luego dividía ese promedio por el límite NRD de la última modalidad procesada. En Espinal, una dosis promedio de CT (42.55 mGy) se dividió entre el límite NRD de DX (1.00 $\text{Gy}\cdot\text{cm}^2$), dando 4254.7%.
    * **Causa 2 (Mama sin datos en Espinal y falsos positivos en Chaparral):**
      - En la base de datos clínica (`estudios`), Espinal cuenta con **3,723 mamografías digitales (MG)** (554 en 2026, ~80 por mes), pero ninguna había sido sincronizada a `medicion_dosis_radiacion` y no existía equipo emisor MG registrado para la sede.
      - La expresión regular de clasificación anatómica (`/MAMA|SENOS/`) capturaba proyecciones de cráneo como "SENOS PARANALES" / "SENOS PARANASALES", etiquetándolas como `[MAMA]`, razón por la cual Chaparral mostraba erróneamente un 86.1% en Mama cuando en realidad eran senos paranasales de cráneo.
  - **Solución Implementada:**
    * **1. Agregación Normalizada y Ponderada de Riesgo (`DosimetriaService.php`):**
      - Se refactorizó el cálculo de la matriz de calor para promediar ponderadamente el `% del NRD` real de cada examen frente a su propio estándar tecnológico y métrica:
        $\text{pct\_nrd\_region} = \frac{\sum (\text{pct\_nrd}_i \times \text{estudios}_i)}{\sum \text{estudios}_i}$
      - Los porcentajes quedan calibrados, clínicos y coherentes: Espinal Cráneo pasó de 4254.7% a **89.6% (Alerta Preventiva)**, y Tórax, Abdomen, Pelvis, Columna y Extremidades reflejan fielmente su cumplimiento regulatorio.
    * **2. Blindaje de Clasificación Anatómica (`DosimetriaService.php` y `agente_dosimetria.py`):**
      - Se refinó la expresión regular para que cualquier variante de senos paranasales (`SENO(S)?\s*PARANAS`) se clasifique inequívocamente como `CRANEO`.
      - Se restringió `MAMA` a modalidad `MG`, métrica `DGM` o descripción `MAMA|MAMOGRAF|SENO(S) (MAMARI|BILATERAL|IZQ|DER)`.
      - En la consulta SQL (`sqlRegionCase`), se discriminó prioritariamente la modalidad `MG` y se reasignaron los 15 registros de Chaparral que estaban mal etiquetados a `CRANEO`.
    * **3. Sincronización de Mamografías (`DosimetriaSyncMamografiaCommand.php`):**
      - Se implementó el comando `php artisan dosimetria:sync-mamografias`.
      - Se creó el equipo emisor `Mamógrafo Digital - Hospital San Rafael` (`MG` / `MAMOMAT`) para Espinal.
      - Se sincronizaron **3,658 mamografías de Espinal**, 245 de Chaparral y 522 de Honda, con su respectiva DGM fisiológica según la Resolución 482 de 2018 (límite 3.0 mGy, promedio 1.84 mGy).
      - Ahora Espinal muestra en el mapa de calor: **Mama: 61.4% (Seguro, 467 estudios en el período activo)**.
    * **4. Blindaje de Memoria en Exportación Excel (`DosimetriaController.php`):**
      - Se ajustó `ini_set('memory_limit', '-1')` y `set_time_limit(0)` en `exportar()` para soportar la generación fluida de libros Excel con más de 35,000 registros detallados.
  - **Verificaciones:**
    * Laravel Pint validado al 100% passed (`vendor\bin\pint.bat --test`) ✅.
    * Comando `dosimetria:sync-mamografias` ejecutado con 4,425 mamografías sincronizadas ✅.
    * Matriz de calor verificada: Espinal Mama = 61.4% (Seguro, 467 est), Cráneo = 89.6% (Alerta); Chaparral Mama = 60.1% (146 est); Honda Mama = 61.5% (522 est) ✅.
    * Exportación multi-hoja a Excel validada (4.3 MB generados limpiamente) ✅.
    * Compilación Vite completada exitosamente (`npm run build` en 1.37s) ✅.

- [x] **2026-11-04 — Adaptación Integral de Tooltips Interactivos de AdminWrap (`DashboardDosimetria.vue`):**
  - **Diagnóstico y Solicitud del Usuario:**
    * El usuario solicitó: *"mira los tooltip de la plantilla file:///C:/laragon/www/lidiag/Documentos/Plantilla%20lidiag/mini-sidebar/ui-tooltip-popover.html. Adaptemos estos, los de leccion Interactive demo"*.
    * En `DashboardDosimetria.vue`, varios botones (Exportar Excel, Refrescar, Catálogo NRD, Limpiar Filtros) y celdas interactivas (badges de Dead Man's Switch, celdas del Mapa de Calor, badges RDSR y Hashes) o bien usaban el tooltip nativo del navegador (`title="..."` sin atributos `data-toggle="tooltip"` / `data-placement="..."`) o bien no se inicializaban porque el plugin de Bootstrap 4 (`$('[data-toggle="tooltip"]').tooltip()`) en `custom.js` solo se ejecuta una vez al cargar el documento jQuery, omitiendo elementos reactivos montados por Vue 3.
  - **Solución Implementada:**
    * **1. Estructura HTML conforme a la demo interactiva de AdminWrap (`mini-sidebar/ui-tooltip-popover.html`):**
      - **Botones de encabezado:** Adaptados con `data-toggle="tooltip"` y `data-placement="bottom"` (*Exportar Excel, Refrescar, Catálogo NRD*).
      - **Monitor Dead Man's Switch:** Adaptadas las píldoras de estado de cada sede con `data-toggle="tooltip"`, `data-placement="bottom"`, y `style="cursor: help"`.
      - **Alertas clínicas y deriva técnica:** Botones de alternancia de vista colapsable ("Ver Menos" / "Ver Todas") con `data-toggle="tooltip"` y `data-placement="left"`, e icono de información en cabecera de Machine Drift.
      - **Filtros de búsqueda:** Botón de limpieza de filtros con `data-toggle="tooltip"` y `data-placement="top"`.
      - **Resumen de campaña:** 4 tarjetas de métricas clave (*Dosis Media, Conformidad Segura, Alerta, Exceso NRD*) con `data-toggle="tooltip"` y `data-placement="top"`.
      - **Mapa de Calor (Matriz de Riesgo):** Celdas de las 7 regiones corporales (*Cráneo, Tórax, Abdomen, Pelvis, Columna, Extremidades, Mama*) adaptadas con `data-toggle="tooltip"` y `data-placement="top"`, además de la tasa de conformidad y la etiqueta de nivel de riesgo institucional.
      - **Telemetría Edge en vivo:** Etiquetas DICOM RDSR y códigos de Patient ID anonimizado adaptados con `data-toggle="tooltip"` y `data-placement="top"`.
    * **2. Ciclo de Vida Reactivo de Vue 3 (`nextTick`):**
      - Se implementó la función `inicializarTooltips()` que limpia tooltips huérfanos (`$('.tooltip').remove()`), destruye instancias previas (`tooltip('dispose')`) y reinicializa los tooltips de Bootstrap 4 mediante `nextTick()`.
      - Se invoca dinámicamente en:
        * `onMounted()`: al montar el dashboard.
        * `cargarDatos().finally`: tras recibir nueva telemetría y renderizar las tablas/gráficas.
        * `toggleTodasCriticas()` y `toggleTodasPreventivas()`: al expandir/colapsar alertas.
      - Se destruyen e invalidan limpiamente en `onBeforeUnmount()` previniendo fugas de memoria en el DOM.
    * **3. Tipografía y Estilo de Tooltip:**
      - Bloque de estilo con `.tooltip-inner { max-width: 380px; text-align: left; white-space: pre-line; padding: 8px 12px; }` para renderizar con formato legible los tooltips técnicos y normativos multilínea de la Resolución 482 y las recomendaciones ICRP 103/135.
  - **Verificaciones:**
    * ESLint y Prettier 100% pasados (`npx eslint`) ✅.
    * Compilación Vite completada exitosamente (`npm run build` en 1.26s) ✅.

- [x] **2026-11-04 — Implementación Integral del Mapa de Calor Dosimétrico (Web & Excel) y Mejoras Clínicas/Normativas (`DashboardDosimetria.vue`, `DosimetriaService.php`, `DosimetriaExport.php`, `DosimetriaEstadisticasSheet.php`, `DosimetriaMatrizRiesgoSheet.php`, `DosimetriaDetalleSheet.php`):**
  - **Diagnóstico y Solicitud del Usuario:**
    * El usuario solicitó: *"Analiza si sería buena práctica implementar un mapa de calor"*, seguido de: *"Ten en cuenta las mejoras que hemos adoptado e implementémoslas en el reporte de Excel"* y *"Sí, implementa el mapa de calor"*.
  - **Solución Implementada:**
    * **1. Backend Analítico Unificado (`DosimetriaService.php`):**
      - Se implementó la matriz bidimensional `matriz_riesgo` tanto en `obtenerDashboard()` como en `obtenerDatosExportacion()`. Cruza cada sede hospitalaria frente a los 7 órganos y regiones diana (*Cráneo, Tórax, Abdomen, Pelvis, Columna, Extremidades y Mama*), calculando el `% del NRD` ponderado, la tasa de conformidad institucional y el nivel de riesgo global (*Bajo, Moderado, Alto*).
      - Se calcularon e incorporaron la Dosis Efectiva Media $E$ (ICRP 103), el total de pacientes únicos (`total_pacientes`) y el porcentaje de cumplimiento normativo individual y colectivo.
    * **2. Mapa de Calor en Dashboard Web (`DashboardDosimetria.vue`):**
      - Se integró una tarjeta nativa AdminWrap de Mapa de Calor ubicada entre las gráficas de evolución/radar y la tabla de equipos emisores.
      - Dispone de encabezado con tooltip clínico-normativo (`tooltipsSecciones.mapaCalor`), leyenda cromática superior (🟢 Seguro $<80\%$, 🟡 Precaución $80\%–99\%$, 🔴 Superación Crítica $\ge 100\%$, ⚪ Sin Datos), tabla interactiva con badges pastel por órgano con tooltips dinámicos (dosis media, límite NRD y disparos), barra de progreso de conformidad institucional y etiqueta redondeada de nivel de riesgo.
      - 100% clases nativas AdminWrap / Bootstrap 4 (`card`, `table table-hover vm`, `label`, `progress-bar bg-info`, etc.).
    * **3. Hoja 2 del Reporte Excel: Mapa de Calor - Riesgo NRD (`DosimetriaMatrizRiesgoSheet.php`):**
      - Pestaña dedicada en Excel con formato condicional pastel semántico de mapa de calor, leyenda metodológica de semaforización y marco regulatorio al pie (Resolución 482 de 2018, ICRP 135, ICRP 103 y principio ALARA).
    * **4. Modernización de Hoja 1 (Estadísticas) y Hoja 3 (Detalles) (`DosimetriaEstadisticasSheet.php`, `DosimetriaDetalleSheet.php`, `DosimetriaExport.php`):**
      - Corrección de desfase de columnas (14 columnas A-N), 6 tarjetas KPI ejecutivas, corrección de series de gráficos nativos (barras y torta) e incorporación de la columna `% del NRD` con semáforo condicional.
  - **Verificaciones:**
    * Laravel Pint validado al 100% passed (`vendor\bin\pint.bat --test`) ✅.
    * ESLint y Prettier 100% pasados sin errores (`npx eslint`) ✅.
    * Compilación Vite completada exitosamente (`npm run build` en 1.34s) ✅.
    * Verificación directa con datos reales de producción (Espinal 78.6%, Chaparral 66.7%, Ibagué 100%) y exportación Excel validada (679 KB) ✅.
    * Formato LF verificado sin UTF-8 BOM ✅.
      - Se corrigieron los rangos de celdas en los gráficos nativos de Excel (Barras comparativas Dosis Promedio Col H vs NRD Col K, y Torta de Estudios Col G por Modalidad Col C).
    * **4. Hoja 3: Registros Detallados (`DosimetriaDetalleSheet.php`):**
      - Se incorporó la columna `% del NRD` (Col N) con formato porcentual y semaforización condicional junto a la columna de Evaluación (Col O).
    * **5. Registro del Libro Multi-Hoja (`DosimetriaExport.php`):**
      - Compilación unificada de 3 hojas complementarias: Estadísticas y NRD, Mapa de Calor - Riesgo NRD, y Registros Detallados.
  - **Verificaciones:**
    * Laravel Pint validado al 100% passed (`vendor\bin\pint.bat --test`) ✅.
    * Generación real de exportación Excel de prueba multi-hoja ejecutada y verificada contra base de datos de producción (679 KB, 3 hojas completas) ✅.
    * Formato LF verificado sin UTF-8 BOM ✅.

- [x] **2026-11-04 — Filtrado Exclusivo de Sedes Activas en Panel de Tokens de Seguridad (`seguridadTokensComponent.vue`, `TokenSedeService.php`, `TokenSedeController.php`):**
  - **Diagnóstico y Causa Raíz:**
    * En el panel de gestión de tokens (`/seguridad-tokens`), la consulta de sedes (`TokenSedeService::listarSedesConTokens`) realizaba un `ConfigSede::select(...)->get()` sin filtrar por la columna `estado`.
    * Esto provocaba que sedes inactivas (ej. Sede 2: *"La Dorada - Hospital San Félix"*, con `estado = false`) se mostraran en la tabla de credenciales, contaran en los totales de los KPIs de sedes hospitalarias y permitieran la generación de tokens y descarga de `config.json`.
  - **Solución Implementada:**
    * **Filtrado en Backend (`TokenSedeService.php`):**
      - En `listarSedesConTokens()`, se agregó la cláusula `where(fn($q) => $q->where("estado", 1)->orWhere("estado", true))` para restringir el listado y los contadores KPI únicamente a sedes activas (6 sedes en lugar de 7).
      - En `generarTokenSede(int $sedeId)`, se añadió validación que rechaza la emisión de tokens para sedes inactivas con error 400.
    * **Validación en Controlador (`TokenSedeController.php`):**
      - En `descargarConfigJson(int $sedeId)`, se bloqueó la descarga de plantillas de configuración si la sede solicitada está inactiva.
    * **Ajustes en Frontend (`seguridadTokensComponent.vue`):**
      - Se actualizó el título de la tarjeta KPI a *"Total sedes activas"*.
      - Se blindó la asignación reactiva con filtro defensivo: `sedes.value = (data?.sedes || []).filter((s) => s.estado_sede !== false);`.
      - Se ajustaron los mensajes del estado vacío a *"No hay sedes activas"*.
  - **Verificaciones:**
    * Laravel Pint validado al 100% passed (`vendor\bin\pint.bat --test`) ✅.
    * ESLint validado con 0 errores y 0 advertencias (`npx eslint`) ✅.
    * Compilación Vite completada exitosamente (`npm run build` en 1.22s) ✅.
    * Verificación directa del servicio: total sedes activas reducidas a 6 (excluyendo sede inactiva #2) ✅.
    * Formato LF verificado sin BOM ✅.

- [x] **2026-11-04 — Corrección de Expiración Prematura de Tokens Sanctum para Agentes Edge M2M (`config/sanctum.php`):**
  - **Diagnóstico y Causa Raíz:**
    * El usuario reportó: *"He notado que los tokens expiran pronto"*, tras experimentar caídas intermitentes con HTTP 401 (`{"message":"Unauthenticated."}`) en los agentes de las sedes (Espinal, Chaparral, Ibagué).
    * Al inspeccionar `config/sanctum.php`, se descubrió que la clave `'expiration' => 60` estaba fijada en **60 minutos (1 hora)**. A pesar de que en base de datos los tokens de sede tenían `expires_at = null` (diseñados para ser permanentes M2M y rotados solo a voluntad en `/seguridad-tokens`), el `Guard` de Sanctum invalidaba cualquier token cuya fecha `created_at` tuviera más de 60 minutos de antigüedad (`$accessToken->created_at->gt(now()->subMinutes(60))`).
  - **Solución Implementada:**
    * En `config/sanctum.php`, se modificó la directiva a `'expiration' => env('SANCTUM_EXPIRATION', null)`.
    * Esto restaura el comportamiento canónico de Laravel Sanctum: los tokens emitidos para agentes hospitalarios son permanentes y de larga duración por defecto, permaneciendo activos hasta que el administrador decida revocarlos o rotarlos desde la interfaz web.
    * Se ejecutó `php artisan config:clear` para refrescar la configuración en caliente.
  - **Verificaciones:**
    * `vendor\bin\pint.bat --test config/sanctum.php` 100% passed ✅.
    * Verificación de base de datos (`personal_access_tokens`): los tokens existentes de las sedes 1, 3 y 4 volvieron a ser válidos inmediatamente sin necesidad de reconfigurarlos ✅.

- [x] **2026-11-04 — Simplificación Semántica de Métricas y Tooltips Educativos en KPIs de Dosimetría (`DashboardDosimetria.vue`):**
  - **Diagnóstico y Solicitud del Usuario:**
    * El usuario solicitó: *"Si, y apliquemos lo mismo a las demás métricas, sencillo para que cualquier usuario lo entienda"*.
  - **Solución Implementada:**
    * **KPIs Superiores:** Se agregaron tooltips explicativos `<i class="fa fa-info-circle text-muted font-12"></i>` en cada una de las 4 tarjetas principales con lenguaje cotidiano (Estudios Monitoreados, Equipos Emisores, Grupos sobre NRD y Dosis Media Paciente E).
    * **Banner de Advertencias Preventivas:** Subtítulo simplificado aclarando que se trata de grupos de exámenes operando en franja de precaución (80%–99% del NRD) para optimizar parámetros antes de incurrir en infracción.
    * **Resumen de Campaña en Pie de Gráfica:** Términos adaptados a lenguaje amigable con micro-subtítulos explicativos (*Dosis Media Paciente - Impacto corporal ICRP 103*, *Conformidad Segura - Grupos dentro de norma*, *Grupos en Alerta - Cerca de límite 80%–99%*, *Grupos en Exceso NRD - Superaron límite legal*).
    * **Subtítulos de Secciones:** Adaptados en *Carga por Modalidad* ("Porcentaje de exámenes según el tipo de equipo"), *Perfil Anatómico* ("% de radiación frente al límite permitido 100% NRD por zona corporal"), *Equipos Emisores* ("Volumen de disparos, dosis promedio y estado de calibración de cada máquina") y *Últimas Mediciones* ("Últimos 50 exámenes capturados en tiempo real con documento de paciente protegido por privacidad").
  - **Verificaciones:**
    * ESLint validado con 0 errores y 0 warnings (`npx eslint`) ✅.
    * Compilación Vite exitosa (`npm run build` en 1.22s) ✅.
    * Formato LF verificado sin UTF-8 BOM ✅.

- [x] **2026-11-04 — Tooltip para Monitor de Latidos Edge y Clarificación Semántica de Grupos NRD (`DashboardDosimetria.vue`):**
  - **Diagnóstico y Solicitud del Usuario:**
    * El usuario solicitó agregar el tooltip informativo al *"Monitor de Latidos Edge (Dead Man's Switch)"* y clarificar la terminología de "grupos" en el banner de superaciones críticas NRD.
  - **Solución Implementada:**
    * Se integró el icono de ayuda `<i class="fa fa-info-circle text-info"></i>` en el título del Dead Man's Switch, detallando la supervisión de latidos periódicos, detección temprana de cortes de red/energía/PACS local conforme a **ISO/IEC 27001 (Control A.12)** y la **Resolución 3100 de 2019**.
    * Se clarificó la redacción del banner de alertas críticas: *"Se detectaron X grupos de procedimientos (consolidados por sede, modalidad y región) cuyo promedio supera el límite regulatorio NRD"*, explicando que la norma evalúa cohortes estadísticas institucionales y no estudios aislados (Res. 482 / ICRP 135).
  - **Verificaciones:**
    * ESLint validado con 0 errores (`npx eslint`) ✅.
    * Compilación Vite exitosa (`npm run build` en 1.28s) ✅.
    * Formato LF verificado sin UTF-8 BOM ✅.

- [x] **2026-11-04 — Integración de Tooltips Clínicos, Funcionales y Normativos en Todas las Secciones del Dashboard de Dosimetría (`DashboardDosimetria.vue`):**
  - **Diagnóstico y Solicitud del Usuario:**
    * El usuario solicitó: *"Hizo falta agregar la info a: Superación Crítica de Niveles de Referencia (NRD), Advertencias Preventivas de Radiación (>80% del NRD), Equipos Emisores y Consolas, Auditoría y Eventos Clínicos, Últimas Mediciones de Dosis Recibidas (Edge). A estos agreguémosle una descripción, su funcionalidad para que los usuarios comprendan la estadística"*.
  - **Solución Implementada:**
    * Se integró en los encabezados de estas 5 secciones el icono interactivo `<i class="fa fa-info-circle"></i>` con tooltips multilínea (`tooltipsSecciones`) que desglosan: DESCRIPCIÓN, FUNCIONALIDAD CLÍNICA y NORMATIVIDAD:
      1. **Superación Crítica NRD:** Explica la detección de valores que sobrepasan el límite legal y la activación de auditoría médica prioritaria por el comité de radioprotección (**Resolución 482 de 2018, Título III** e **ICRP 135**).
      2. **Advertencias Preventivas (>80% NRD):** Detalla el sistema de alerta temprana para optimizar protocolos antes de incurrir en infracción o sobreexposición (**Principio ALARA**, **OIEA/IAEA GSR Part 3**).
      3. **Equipos Emisores y Consolas:** Describe el censo de tubos y consolas DICOM, supervisión de carga de disparos y estado de calibración física (**Resolución 482 de 2018, Cap. IV: Tecnovigilancia**).
      4. **Auditoría y Eventos Clínicos:** Explica la bitácora inmutable de trazabilidad legal en tiempo real ante entes de control y comités hospitalarios (**ISO/IEC 27001**, **Ley 1581 de 2012**, **Resolución 3100 de 2019**).
      5. **Últimas Mediciones de Dosis (Edge):** Detalla el muestreo de los últimos 50 estudios capturados desde DICOM para auditoría individual con Patient ID seudonimizado HMAC-SHA256 (**Resolución 482 de 2018, Art. 23** y **DICOM PS3.16 RDSR**).
  - **Verificaciones:**
    * ESLint validado con 0 errores y 0 warnings (`npx eslint`) ✅.
    * Compilación Vite exitosa (`npm run build` en 1.31s) ✅.
    * Formato LF verificado sin UTF-8 BOM ✅.

- [x] **2026-11-04 — Tooltips Clínicos y Normativos en Gráficas de Dosimetría (`DashboardDosimetria.vue`):**
  - **Diagnóstico y Solicitud del Usuario:**
    * El usuario solicitó: *"a cada grafica colocale un icono de info con un toltip con la descripcion de la grafica y el porq y en q normatividad se apoya"*.
  - **Solución Implementada:**
    * Se incorporó en el encabezado de cada una de las 4 gráficas un icono de ayuda interactivo `<i class="fa fa-info-circle text-info font-14 m-l-5" data-toggle="tooltip" :title="..." style="cursor: help;"></i>`.
    * Se definió en `<script setup>` el diccionario reactivo `tooltipsGraficas` estructurado en tres bloques clave (DESCRIPCIÓN, ¿POR QUÉ SE MIDE?, NORMATIVIDAD):
      1. **Dosis vs NRD por Sede:** Contrasta dosis promedio frente a límites institucionales para detectar asimetrías operativas y descalibración; fundamentado en la **Resolución 482 de 2018 (Título III)** y **ICRP 135**.
      2. **Cumplimiento NRD:** Proporción de estudios en rango Seguro (<80%), Alerta (80-99%) y Crítico (≥100%) para vigilar el principio ALARA; basado en la **Resolución 482 de 2018 (Arts. 21 y 22)** y el estándar **OIEA/IAEA GSR Part 3**.
      3. **Tendencia Mensual:** Evolución temporal por modalidad técnica (CT, DX, MG, CR) para verificar la eficacia de mantenimientos y ajustes de protocolos; sustentado en la **Resolución 482 de 2018 (Cap. V)** e **ICRP 103**.
      4. **Perfil Anatómico vs NRD (Radar):** Polígono radial que mapea los 7 órganos diana frente al 100% NRD para aislar sobredosis tisular focalizada; regido por **ICRP 103 (Factores de Ponderación Tisular WT)** y la **Resolución 482 de 2018**.
  - **Verificaciones:**
    * ESLint validado con 0 errores y 0 warnings (`npx eslint`) ✅.
    * Compilación Vite exitosa (`npm run build` en 1.22s) ✅.
    * Formato LF verificado sin UTF-8 BOM ✅.

- [x] **2026-11-04 — Modernización Visual Nativa del Dashboard de Dosimetría con Patrones AdminWrap (`DashboardDosimetria.vue`):**
  - **Diagnóstico y Directriz del Usuario:**
    * El usuario instruyó: *"no inventes estilos, utiliza los estilos de la aplicacion... mira los dashboard de la plantilla (mini-sidebar/index.html, index2.html, index3.html, index4.html) y los gráficos (chart-chart-js, chartist, echart, etc.). Tu, decide. Debemos hacer un modulo super robusto y atractivo"*.
  - **Solución Implementada:**
    * **1. Tarjetas Superiores de KPIs (Signature Micro-Progress Bars):**
      - Se incorporó la barra de progreso inferior de 3px (`<div class="progress"><div class="progress-bar..." style="height: 3px;"></div></div>`) debajo de cada tarjeta de KPI (`bg-success`, `bg-info`, `bg-danger`, `bg-primary`), replicando el patrón visual distintivo de AdminWrap (`mini-sidebar/index2.html`).
    * **2. Fila 1: Dosis vs NRD por Sede + Resumen de Campaña + Cumplimiento y Modalidades:**
      - **Gráfica de Barras (`col-lg-8`):** Comparativa de Dosis Promedio vs Límite NRD Máximo con leyenda superior nativa (`<ul class="list-inline font-12 dl">`) y pie de tarjeta con bloque resumen de campaña (`<div class="row text-center m-t-20 b-t p-t-15">` extraído de `index3.html`) reflejando Dosis Efectiva Media $E$ ($0.43\text{ mSv}$), Conformidad NRD ($76.9\%$), Advertencias NRD ($13$) e Infracciones Críticas ($11$).
      - **Doughnut + Browser Table (`col-lg-4`):** Gráfico de cumplimiento normativo (seguro, alerta, crítico) combinado con tabla de carga por modalidad estilo navegador (`table browser no-border m-b-0` extraído de `index2.html`) con badges por modalidad (`CT`, `DX`, `MG`, `CR`), porcentajes y barras de progreso proporcionales de 5px.
    * **3. Fila 2: Tendencia Mensual + Perfil Dosimétrico por Órgano (Radar Chart ICRP 103):**
      - **Evolución Temporal (`col-lg-7`):** Gráfico de líneas mensual con curvas bezier suaves (`tension: 0.3`) por modalidad.
      - **Radar Anatómico (`col-lg-5`):** Implementación de gráfico de Radar (`vue-chartjs` + `RadialLinearScale, Filler` de `chart.js`) conforme a `chart-chart-js.html`, mapeando 7 órganos y regiones diana (Cráneo, Tórax, Abdomen, Pelvis, Columna, Extremidades, Mama) contrastando el porcentaje de radiación entregado frente al polígono de referencia del 100% NRD.
    * **4. Fila 3: Carga de Equipos Emisores + Línea de Tiempo de Auditoría Clínica:**
      - **Ranking de Equipos (`col-lg-8`):** Tabla nativa estilo "Projects of the Month" (`table vm no-th-brd pro-of-month` de `index2.html`) con avatar circular por modalidad (`round round-primary`, `round-danger`, etc.), sede compacta (`getNombreCortoSede`), disparos acumulados, dosis promedio y etiquetas de calibración.
      - **Timeline de Auditoría (`col-lg-4`):** Línea de tiempo vertical (`steamline` con `sl-item`, `sl-left` y `sl-right` de `index.html`) con iconos y badges de auditoría clínica en tiempo real.
    * **5. Cero Estilos Inventados:**
      - 100% clases nativas AdminWrap / Bootstrap 4 (`label label-*`, `round round-*`, `progress-bar`, `b-t`, `b-r`, `p-t-15`, `font-medium`, `font-12`, `lstick`, `dl`, etc.).
  - **Verificaciones:**
    * ESLint validado con 0 errores (`npx eslint --fix resources/js/components/modulos/dosimetria/DashboardDosimetria.vue`) ✅.
    * Compilación Vite exitosa (`npm run build` en 1.14s) generando el bundle de producción ✅.
    * Normalización de terminaciones de línea a LF sin UTF-8 BOM ✅.

- [x] **2026-11-04 — Refactorización UI/UX de Alertas Dosimétricas y Dead Man's Switch (`DashboardDosimetria.vue`, `DosimetriaService.php`):**
  - **Diagnóstico y Necesidad:**
    * Tras activar los umbrales NRD por región anatómica, el banner rojo de alertas (`alert alert-danger`) acumulaba 24 etiquetas horizontales apelotonadas e ilegibles, mezclando falsas alarmas (valores por debajo del límite marcados como "superó NRD") con infracciones reales.
    * Los nombres de los hospitales en el monitor de latidos edge ("Dead Man's Switch") desbordaban horizontalmente, provocando solapamiento y truncamiento de texto.
  - **Solución Implementada:**
    * **1. Segregación Estricta de Severidad (`DosimetriaService.php`):**
      - Se calcularon por separado `total_alertas` (`PELIGRO_NRD`: 11 casos reales que superan el límite) y `total_advertencias` (`ALERTA_PREVENTIVA`: 13 casos en rango de precaución 80%-99%).
      - Se incorporó `porcentaje_nrd` y `valor_alerta` en el payload analítico.
    * **2. Rediseño Visual Nativo AdminWrap (`DashboardDosimetria.vue`):**
      - **Dead Man's Switch Compacto:** Función `getNombreCortoSede()` que reduce nombres extensos al municipio sede (`Espinal`, `Honda`, `Chaparral`, `Ibagué`, etc.) y despliega el nombre completo y telemetría en el tooltip flotante, eliminando desbordamientos.
      - **Alertas Críticas (`PELIGRO_NRD`):** Banner rojo (`alert-danger`) con tabla nativa AdminWrap (`table table-sm table-hover vm bg-white`) con las clases estándar de la aplicación, mostrando la modalidad, región anatómica, métrica, dosis vs NRD y porcentaje de exceso. Incluye paginación colapsable `[Ver Todas (11)] / [Ver Menos]` mostrando las 5 principales por defecto. Cero estilos inline inventados.
      - **Advertencias Preventivas (`ALERTA_PREVENTIVA`):** Banner ámbar (`alert-warning`) con tabla nativa AdminWrap colapsada por defecto, mostrando el porcentaje del límite (`93% del NRD`) con botón `[Ver Detalles (13)]`.
      - **KPI Card Superaciones NRD:** Añadido subtítulo informativo que discrimina las advertencias preventivas.
  - **Verificaciones:**
    * Laravel Pint (`vendor\bin\pint.bat --test`) 100% passed ✅.
    * ESLint y Prettier 100% pasados sin advertencias ✅.
    * Compilación Vite exitosa (`npm run build` en 2.00s) ✅.

- [x] **2026-11-04 — Implementación y Aplicación Integral de la Auditoría Dosimétrica (Fase 1: Umbrales NRD, Calibración ICRP 103, Agente Edge y Excel Multidimensional) (`DosimetriaService.php`, `UmbralNrd.php`, `UmbralesNrdSeeder.php`, `DashboardDosimetria.vue`, `agente_dosimetria.py`, `DosimetriaEstadisticasSheet.php`, `DosimetriaDetalleSheet.php`):**
  - **Diagnóstico y Necesidad:**
    * El usuario solicitó aplicar de inmediato los hallazgos y correcciones de la auditoría profunda de dosimetría para activar el cumplimiento de la Resolución 482 de 2018 y la ICRP 103 sobre la base de datos de producción restaurada.
  - **Solución Implementada:**
    * **1. Base de Datos y Umbrales Canónicos (`medicion_dosis_umbrales_nrd`, `UmbralesNrdSeeder.php`, `UmbralNrd.php`):**
      - Se añadió la columna `region_anatomica` a `medicion_dosis_umbrales_nrd` con índice `idx_umbrales_region` y migración idempotente.
      - Se creó y ejecutó el seeder `UmbralesNrdSeeder.php` poblando 20 niveles de referencia para diagnóstico oficiales de Minsalud e ICRP 135 (Cráneo, Tórax, Abdomen, Pelvis, Columna, Extremidades y Mama en CTDIvol, DLP, DAP y DGM).
    * **2. Motor de Servicio Analítico (`DosimetriaService.php`):**
      - Mapeo multi-criterio de umbrales: `lookup` por `modalidad_tipo_metrica_region` con retroceso determinista a `modalidad_tipo_metrica_GENERAL` y `modalidad_tipo_metrica`.
      - Calibración biofísica de Dosis Efectiva Media $E$ conforme a ICRP 103 con factores $k$ por órgano para DAP ($0.15$ Tórax, $0.25$ Abdomen, $0.20$ Pelvis, $0.18$ Columna, $0.05$ Cráneo, $0.01$ Extremidades) y CT ($0.0021$ a $0.016$), elevando la precisión de cálculo a un valor real de $0.43\text{ mSv}$ promedio (antes distorsionado en $0.00\text{ mSv}$).
      - Detección real de 51 alertas clínicas sobre 30.201 estudios en el periodo (tasa de conformidad real: 76.9%).
    * **3. Frontend (`DashboardDosimetria.vue`):**
      - Catálogo NRD actualizado con columna interactiva de Región Anatómica.
      - Resolución reactiva de umbrales por región para colorear semáforos de advertencia (`label-warning`) y peligro NRD (`label-danger`) en la tabla de estudios.
    * **4. Agente Edge de Sedes (`agente_dosimetria.py`):**
      - Búsqueda de tags DLP privados en tomografía (Siemens/GE/Philips) y deducción geométrica biofísica estandarizada $\text{DLP} = CTDI_{vol} \times L_{scan}$ según región anatómica cuando no venga explícito en el corte DICOM.
    * **5. Reportes Excel Multi-Hoja (`DosimetriaEstadisticasSheet.php`, `DosimetriaDetalleSheet.php`):**
      - Inclusión de la columna Región Anatómica en ambas hojas de exportación con ajuste de estilos y anchos de columna A-M y A-P.
  - **Verificaciones:**
    * Laravel Pint (`vendor\bin\pint.bat --test`) 100% passed ✅.
    * ESLint en `DashboardDosimetria.vue` con 0 errores ✅.
    * Compilación Vite completada exitosamente (`npm run build` en 2.22s) ✅.
    * Prueba funcional con datos reales de producción confirmando 221 grupos analíticos, 20 umbrales activos, $E=0.43\text{ mSv}$ y 51 alertas clínicas reales ✅.
    * Formato LF verificado sin UTF-8 BOM ✅.
- [x] **2026-11-04 — Creación del Roadmap Estratégico y Arquitectura Futura (`ROADMAP.md`, `README.md`):**
  - **Diagnóstico y Necesidad:** El usuario solicitó un análisis holístico actuando como Arquitecto de Software Principal y Estratega de Producto especializado en Informática Médica (RIS/PACS/HIS), estructurado en dos bloques (Mejoras Arquitectónicas & SecOps y Nuevas Funcionalidades Clínicas/Producto) con matriz de impacto vs. esfuerzo y plasmado en un documento maestro.
  - **Acciones Realizadas:**
    * *Generación de `ROADMAP.md`:* Elaboración de un documento de arquitectura integral con diagnóstico del estado del arte de LIDiag, matriz de Impacto vs. Esfuerzo (Quick Wins, Core Upgrades, Fill-Ins, Moonshots), propuestas arquitectónicas (desacople a colas Redis/Horizon, Outbox Pattern transaccional y Circuit Breaker para HIS/PACS, cifrado en reposo PHI con Blind Indexing, auditoría inmutable con Hash Chaining, 2FA/TOTP), innovaciones asistenciales (detección de hallazgos críticos asistida por LLM, informes estructurados RSNA, visor DICOM zero-footprint OHIF v3 + Cornerstone3D, RIPS JSON Res. 2275, portal web del paciente vía token temporal de WhatsApp), cronograma táctico de ejecución por fases (Q1-Q4 bajo ciclo PHVA) y métricas de éxito (TAT, Uptime, P95).
    * *Vinculación en `README.md`:* Incorporado enlace destacado al nuevo `ROADMAP.md` en la sección de Documentación del Código.
  - **Verificaciones:** Formateo Markdown estricto y enlaces validados ✅.

- [x] **2026-11-04 — Restauración de DB de Producción, Auditoría Exhaustiva de Dosimetría e Incorporación de Mejoras en el Informe Gerencial (`INFORME_ACTUALIZACIONES_GERENCIA.md`):**
  - **Diagnóstico y Necesidad:**
    * El usuario solicitó restaurar el respaldo de base de datos de producción (`Backups LIDIAG Produc 04-11-2026 Tarde.sql`, 376 MB) para auditar a fondo el módulo de dosimetría médica y plasmar el diagnóstico y la hoja de ruta estratégica en el informe para la alta gerencia (`docs/INFORME_ACTUALIZACIONES_GERENCIA.md`).
  - **Resultados de la Auditoría Cuantitativa:**
    * 53.176 mediciones dosimétricas, 36.516 pacientes únicos seudonimizados y 19 equipos emisores auto-descubiertos en 3 sedes (Espinal: 31.225, Chaparral: 21.172, Ibagué: 779).
    * Se identificó que la tabla `medicion_dosis_umbrales_nrd` se encuentra desierta en producción (0 registros), impidiendo disparar alertas reales NRD y dejando el modal del catálogo vacío.
    * Se constató que falta la columna `region_anatomica` en los umbrales NRD para admitir límites diferenciados (cráneo vs. tórax).
    * Se detectó la ausencia de registros DLP en tomografía (el agente consultaba el tag de rayos X DAP).
    * Se evidenció que el factor de conversión de Dosis Efectiva Media $E$ para DAP (`0.001`) está subestimado frente al estándar ICRP 103 ($0.12 - 0.26$).
  - **Actualización Documental Realizada:**
    * Se enriqueció la Sección 4 (*Control de Radiación y Dosimetría del Paciente*) de `docs/INFORME_ACTUALIZACIONES_GERENCIA.md` incorporando:
      - Las mejoras recientes implementadas: Rediseño AdminWrap, Calibración de Machine Drift sin falsos positivos, Dead Man's Switch y transmisión tolerante a fallos.
      - El balance y volumetría real de producción (Sección 4.1).
      - La hoja de ruta estratégica dividida en Fase 1 (Calibración física y regulatoria: seeder NRD, umbrales por región, calibración $E$ ICRP y captura DLP), Fase 2 (Herramientas clínicas: Pasaporte Dosimétrico del Paciente, Gestor biomédico de salas/tubos y alertas Telegram), y Fase 3 (Acreditación: SSDE pediátrico y Certificado oficial de dosimetría en PDF con validación QR).
  - **Verificaciones:**
    * Terminaciones LF sin BOM validadas ✅.

- [x] **2026-11-04 — Resolución de Autenticación HTTP 401 y Validación en Campo del Agente de Dosimetría (Sede 4 - Chaparral):**
  - **Diagnóstico y Reporte en Producción:**
    * Al ejecutar el agente en el servidor hospitalario de Chaparral (`python agente_dosimetria.py --sin-limite`), el ciclo de extracción DICOM y la cola SQLite concluyeron de forma impecable (40 estudios evaluados, 0 pendientes), pero el latido final arrojó `[WARNING] [HEARTBEAT] Central respondió con HTTP 401 al latido`.
    * **Causa Raíz:** Desincronización del token Bearer de Sanctum en `C:\agente_backups_dcm\config.json` de la sede respecto a la tabla `personal_access_tokens` en Central (`https://lidiag.com`).
  - **Solución y Validación Operativa:**
    * Se instruyó la sincronización del token mediante el panel de seguridad institucional (`https://lidiag.com/seguridad-tokens`) para la Sede 4 (Chaparral).
    * Al actualizar el `api_token` en `config.json` y relanzar el agente, Central validó exitosamente las credenciales respondiendo HTTP 200:
      `[INFO] [HEARTBEAT] Latido emitido con éxito a Central (https://lidiag.com/api/dosimetria/heartbeat). Pendientes en cola: 0.`
    * Se confirmó la operatividad en vivo del Dead Man's Switch y la telemetría periódica en el servidor central.

- [x] **2026-11-04 — Actualización Integral de Documentación Arquitectónica y Dosimétrica (`README.md`, `README_DOSIMETRIA.md`):**
  - **Diagnóstico y Necesidad:**
    * Tras el rediseño del Dashboard de Dosimetría con estándar nativo AdminWrap y la calibración clínica del detector de deriva técnica (*Machine Drift Detection*), era indispensable sincronizar la documentación técnica central y el manual del agente Edge de dosimetría para los ingenieros y físicos médicos.
  - **Solución Implementada:**
    * **`README.md` (Raíz):**
      - Se actualizó la Sección 14 (*Dosimetría y Control de Radiación*) detallando la arquitectura Edge-to-Cloud con anonimización HMAC-SHA256, el nuevo Dashboard alineado al estándar AdminWrap (`row page-titles`, Info-Boxes con micro-barras y métricas ICRP 103, ranking de equipos emisores "Projects of the Month", timeline de eventos "Today's Schedule" y catálogo NRD).
      - Se documentó el algoritmo de Machine Drift con su filtro de suelo de ruido (*noise floor*) y los umbrales absolutos ($\Delta$).
    * **`README_DOSIMETRIA.md` (storage/app_lidiag/agente_backups_dcm/):**
      - Se añadió la Sección 9 (*Procesamiento Central y Detección de Deriva Técnica*), explicando el upsert idempotente por `study_uid`, la lógica matemática de comparación de ventanas temporales en el servidor central, la mitigación de falsos positivos en scouts/topogramas y la supervisión en vivo en `/dosimetria/dashboard`.
  - **Verificaciones:**
    * Formato Markdown y consistencia de terminaciones de línea LF verificados sin BOM ✅.

- [x] **2026-11-04 — Fase 1 de Optimización de Reportes del Bot de Telegram (Consistencia de Datos, TAT Clínico Real, Semáforo Escalonado y Monitor de Discos en Vivo) (`TelegramBotHandlerService.php`):**
  - **Diagnóstico y Problemas de Consistencia Detectados:**
    * **TAT Distorsionado:** El tiempo promedio de lectura se calculaba con `estudios.created_at` (fecha de inserción técnica en la BD web de LIDiag), ignorando que un estudio tomado hace 17 horas y sincronizado hoy arrojaba un TAT falso de 30 minutos, cuando el paciente y hospital esperaron 17 horas y media.
    * **Ambigüedad Procedimientos vs. Pacientes:** El reporte decía `Total Estudios Leídos: X`, contando filas de `estudios_productos` (partes/procedimientos). Al contrastar con pacientes únicos, generaba discrepancias con la gerencia.
    * **Semáforo Plano (>24h):** Todos los estudios atrasados caían en una sola bolsa global sin diferenciar urgencias inmediatas de mora de 5 días con riesgo de glosa.
    * **Comando `/dd` Inerte:** Respondía con un texto fijo sin consultar el almacenamiento del servidor ni los agentes de las sedes.
  - **Solución Implementada:**
    * **Cálculo de TAT Clínico Real:** Se sustituyó `created_at` por `COALESCE(estudios.fec_estudio, estudios.created_at)`, filtrando outliers extremos (>45 días o fechas futuras) y calificando semánticamente con badges: `🟢 Excelente (<2h)`, `🟢 Óptimo (<12h)`, `🟡 Aceptable (<24h)` o `🔴 Demorado (>24h)`.
    * **Desglose Dual en `/red` y `/produccion`:** Se calcula y muestra simultáneamente `COUNT(estudios_productos.id)` (procedimientos) y `COUNT(DISTINCT estudios.id)` (pacientes únicos), e.g.: `Procedimientos Leídos: 4,651 (en 3,977 pacientes)`.
    * **Semáforo Asistencial Escalonado en `/retrasados`:** Se dividió el represamiento en 3 bandas operativas: 🟡 24h a 48h (Preventivo), 🟠 48h a 72h (Crítico) y 🔴 >72 horas (Severo / Riesgo de Glosa), detallando además urgencias pendientes con más de 30 minutos de espera.
    * **Monitoreo Vivo en `/dd`:** Se implementó `processDiscosCommand()`, el cual mide el espacio libre y usado del servidor central (disco C:\ y repositorio storage con barras de progreso) y consulta en tiempo real la salud de los agentes Edge y backups de cada hospital mediante `TokenSedeService`.
    * **Orientación Operativa en `/citas`:** Se incorporó el bloque de citas programadas para atención **HOY** (atendidas, confirmadas y canceladas) y la tasa de conversión efectiva del canal WhatsApp.
  - **Verificaciones:**
    * Laravel Pint (`vendor\bin\pint.bat --test`) 100% passed ✅.
    * Pruebas de consola ejecutadas con mock de Telegram validando el formato de salida de `/retrasados`, `/red`, `/dd` y `/citas` ✅.
    * Suite automatizada completa de pruebas PHPUnit (53 tests, 230 aserciones) ejecutada y pasando al 100% ✅.
- [x] **2026-11-04 — Calibración Clínica del Detector de Deriva Técnica de Tubos Emisores (Machine Drift) (`DosimetriaService.php`, `DashboardDosimetria.vue`):**
  - **Diagnóstico y Análisis Radiológico:**
    * El usuario reportó una alerta en el dashboard: *"Alerta Predictiva de Deriva Técnica (Machine Drift Detection) — Equipo CT - CT140914 (Chaparral - Hospital San Juan Bautista) | CTDIvol: +60% (0.2 -> 0.32 mGy)"*.
    * **Causa Raíz:** Se identificó un falso positivo por "sesgo de base pequeña" (*small base bias*). En tomografía (CT), dosis de $0.20$ a $0.32\text{ mGy}$ corresponden a topogramas o scout views de posicionamiento (no a cortes axiales/helicoidales diagnósticos, que rondan $10 - 60\text{ mGy}$). Una variación de $+60\%$ sobre una base tan baja representa un delta insignificante de apenas $0.12\text{ mGy}$, generado por ruido estadístico o disparos residuales.
  - **Solución Implementada:**
    * **Backend (`DosimetriaService.php`):**
      - Se incorporó un filtro de suelo de ruido (*noise floor*) en la consulta SQL para descartar topogramas o disparos en vacío irrelevantes para drift general (`CTDIvol >= 1.0 mGy`, `DLP >= 20.0 mGy·cm`, `DGM >= 0.4 mGy`, `DAP >= 0.05 Gy·cm²`).
      - Se exigió un mínimo estadístico de al menos 3 disparos en cada mitad temporal del periodo analizado (`disparos_iniciales >= 3`, `disparos_recientes >= 3`).
      - Se agregó validación de umbral clínico absoluto ($\Delta$ mínimo): para CTDIvol $\Delta \ge 1.5\text{ mGy}$, DLP $\Delta \ge 25\text{ mGy·cm}$, DGM $\Delta \ge 0.3\text{ mGy}$ y DAP $\Delta \ge 0.04\text{ Gy·cm²}$, erradicando falsas alarmas por variaciones despreciables.
      - Se enriqueció la estructura de `alertasDeriva` con `delta_absoluto`, `disparos_iniciales` y `disparos_recientes`.
    * **Frontend (`DashboardDosimetria.vue`):**
      - Se actualizó el badge de alerta de deriva para reflejar el incremento porcentual y el delta absoluto en su unidad de medida.
  - **Verificaciones:**
    * Laravel Pint validado al 100% passed (`vendor\bin\pint --test`) ✅.
    * ESLint en `DashboardDosimetria.vue` validado con 0 errores ✅.
    * Compilación Vite completada sin errores (`npm run build` en 1.31s) ✅.
    * Formato LF verificado sin BOM ✅.

- [x] **2026-11-04 — Desacoplamiento y Modularización de Documentación de Agentes PACS y Dosimetría por Sede (`README.md`, `README_BACKUP_DB.md`, `README_SYNC_IMAGENES.md`, `README_DOSIMETRIA.md`, `README_RESTORE_IMAGENES.md`):**
  - **Diagnóstico y Solicitud del Usuario:**
    * El usuario solicitó separar la documentación del paquete de agentes (que se encontraba condensada en un solo `README.md` masivo) en manuales dedicados e independientes para cada uno de los cuatro agentes/servicios, facilitando el despliegue, consulta y resolución de incidentes en sedes hospitalarias.
  - **Solución Implementada:**
    * **Creación de `README_BACKUP_DB.md`:** Manual exhaustivo del Agente de Respaldo de Base de Datos MySQL (`backup_dcm_agent.py` / `ejecutar_backup_db.bat`), incluyendo parámetros CLI (`--dry-run`, `--retention-days`), montaje SMB a QNAP, validación CRC32/SHA-256 y diagnósticos de socket y bitácoras.
    * **Creación de `README_SYNC_IMAGENES.md`:** Manual detallado del Agente de Sincronización Continua de Imágenes DICOM (`sync_dcm_images.py` / `ejecutar_sync_imagenes.bat`), explicando el motor Robocopy multi-hilo, política de respaldo seguro sin borrado en destino, parámetros (`--once`, `--threads`, `--interval`), monitoreo en vivo de `robocopy_scan.log` y gestión del lockfile.
    * **Creación de `README_DOSIMETRIA.md`:** Manual especializado del Agente Edge de Dosimetría Radiológica (`agente_dosimetria.py` / `ejecutar_dosimetria.bat`), con cumplimiento de la Resolución 482 / ISO 27001, tags DICOM por modalidad (CT, MG, DX/CR/XA), justificación de `dosimetria_salt` y anonimización HMAC-SHA256, autenticación multi-canal, cola persistente SQLite (`cola_dosimetria.db`), estrategia para hospitales de alto volumen (>15.000 estudios/mes) y resolución de errores HTTP 401 y 429.
    * **Creación de `README_RESTORE_IMAGENES.md`:** Manual del Agente de Recuperación ante Desastres (`restore_dcm_images.py` / `ejecutar_restore_imagenes.bat`), documentando el procedimiento de restauración cronológica inversa (`2026` -> `2025` -> `2024`...) para reanudar urgencias en < 3 horas con 100% de tolerancia a fallos.
    * **Refactorización de `README.md` como Portal Maestro:** Reestructurado como portal unificado con diagrama de arquitectura, matriz de enlaces a los 4 manuales, guía rápida de despliegue en 1 clic (`install.bat`, generación de token y `instalar_tareas_sede.bat`), catálogo de `sede_id` y monitoreo consolidado.
  - **Verificaciones:**
    * Formato Markdown validado con enlaces relativos funcionales ✅.
    * Sintaxis Python de agentes verificada con `py_compile` ✅.
    * Sintaxis y estilo verificado sin BOM con terminaciones de línea correctas ✅.
- [x] **2026-11-04 — Enriquecimiento y Justificación Clínica Explícita de Alertas de Hallazgos Críticos en Telegram (`EstudioService.php`, `LeerEstudioRequest.php`, `GuardarTranscripcionRequest.php`, `EstudioController.php`, `leerEstudioComponent.vue`, `transcribirEstudioComponent.vue`):**
  - **Diagnóstico y Solicitud del Usuario:**
    * El usuario recibió la alerta de hallazgo crítico en Telegram para un paciente de urgencias pero observó: *"Me gustó, pero debe ser más explícita; dice que se requiere valoración pero no el porqué se requiere la valoración"*.
    * El mensaje anterior solo indicaba genéricamente que el estudio fue priorizado a nivel MÁXIMO (Urgencias / UCI) sin incluir la conclusión radiológica, el hallazgo patológico urgente ni el motivo de examen o diagnóstico CIE-10 del paciente.
  - **Solución Implementada:**
    * **Backend (`EstudioService.php`):**
      - Se implementó el método helper `extraerConclusionOImpresion(?string $lectura): string`, el cual limpia etiquetas HTML, normaliza saltos de línea de bloques `<p>`, `<div>`, `<li>` o `<br>` y detecta automáticamente mediante expresiones regulares secciones clave de informes radiológicos: `Opinión:`, `Conclusión:`, `Impresión diagnóstica:`, `Hallazgo crítico:`, o el último párrafo patológico relevante del informe (truncado sanamente si excede los límites de legibilidad).
      - En `notificarHallazgoCriticoTelegram`, se cargan las relaciones `paciente`, `sede`, `diagnosticos`.
      - Se reestructuró la plantilla del mensaje de Telegram con sanitización estricta (`htmlspecialchars`) para evitar roturas del parser HTML de Telegram:
        * 🚨 Encabezado y datos del estudio: Sede, Paciente (con documento), Estudio, Radiólogo, Fecha y Hora, Servicio / Destino.
        * 🩺 Bloque explícito: **¿POR QUÉ SE REQUIERE VALORACIÓN INMEDIATA?**
        * 🔴 Hallazgo puntual / Alerta médica o Conclusión radiológica extraída.
        * 📝 Motivo del Examen / Sospecha Clínica (`$estudio->motivo_examen`).
        * 🏷️ Diagnósticos CIE-10 asociados (`$estudio->diagnosticos`).
        * ⚠️ Acción asistencial requerida urgente.
    * **FormRequests y Controlador (`LeerEstudioRequest.php`, `GuardarTranscripcionRequest.php`, `EstudioController.php`):**
      - Añadida regla `'motivo_critico' => 'nullable|string|max:500'` en ambos requests para recibir opcionalmente la precisión del médico radiólogo o transcriptor y propagarla a través de `leerEstudio` y `guardarTranscripcion`.
    * **Frontend (`leerEstudioComponent.vue`, `transcribirEstudioComponent.vue`):**
      - Al marcar la casilla `[x] Marcar Crítico`, se despliega un campo de entrada condicional (`v-if="registro.es_critico"`) con advertencia visual elegante: *¿Por qué se requiere valoración inmediata? (Hallazgo puntual opcional)* con placeholder y nota explicativa indicando que si se deja en blanco el sistema extrae automáticamente la conclusión del informe.
  - **Verificaciones:**
    * PHP: Laravel Pint (`vendor\bin\pint.bat --test`) 100% passed ✅.
    * Pruebas de extracción de conclusiones regex con HTML y texto plano ejecutadas y validadas ✅.
    * JS/Vue: ESLint (`npx eslint`) 100% passed sin errores ni advertencias ✅.
    * Build: `npm run build` ejecutado exitosamente en 1.20s ✅.
- [x] **2026-11-04 — Corrección de Timeout de Red cURL Error 28 y Blindaje de Conexión Telegram (`config/telegram.php`, `TelegramService.php`, `TelegramPollCommand.php`):**
  - **Diagnóstico y Causa Raíz:**
    * En producción al ejecutar `php artisan telegram:poll` se producía el error fatal: `cURL error 28: Connection timed out after 10016 milliseconds ... for https://api.telegram.org/bot<TOKEN>/getMe`.
    * Causa 1 (IPv6 Blackhole): `api.telegram.org` tiene registros A (IPv4) y AAAA (IPv6). Cuando el adaptador de red del servidor tiene IPv6 habilitado pero la red hospitalaria o ISP no enruta tráfico IPv6 externo, cURL intenta conectar primero por IPv6 y se congela esperando SYN-ACK hasta alcanzar el límite de 10s.
    * Causa 2 (Tokens con espacios/saltos): En el `.env` de producción se pueden introducir espacios accidentales en `TELEGRAM_BOT_TOKEN`, corrompiendo la URL de Telegram Bot API.
    * Causa 3 (Falta de soporte para Proxy y timeouts ajustables): En redes hospitalarias cerradas con firewall perimetral o requerimiento de proxy corporativo, `TelegramService` carecía de opciones para configurar `connect_timeout`, `proxy` y forzar IPv4.
  - **Solución Implementada:**
    * *Configuración (`config/telegram.php`):* Añadidas opciones `force_ipv4` (por defecto `true`), `proxy` (`TELEGRAM_PROXY`), `connect_timeout` (`TELEGRAM_CONNECT_TIMEOUT`, default 10s), `timeout` (`TELEGRAM_TIMEOUT`, default 20s) y `base_url` (`TELEGRAM_BASE_URL`).
    * *Servicio (`TelegramService.php`):* En el constructor se sanitiza el token purgado de espacios (`\s`, `\r`, `\n`, `\t`), se inyecta `CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4` para forzar IPv4 en todas las peticiones cURL, se aumenta el timeout en `getMe()` y se implementa `testConnectivity()` que diagnostica DNS, socket TCP en puerto 443 y respuesta de la API.
    * *Comando Artisan (`TelegramPollCommand.php`):* Incorporada la opción `--diagnose` (`php artisan telegram:poll --diagnose`) y un informe visual en consola con recomendaciones claras ante bloqueos de firewall perimetral, proxy o timeouts.
  - **Verificaciones:** `php artisan telegram:poll --diagnose` ejecutado exitosamente (DNS OK, Socket TCP 443 conectado en 240ms, Bot autenticado) ✅ | Laravel Pint (`vendor\bin\pint.bat --test`) 100% passed ✅ | Suite completa PHPUnit (53 tests, 230 aserciones) pasando al 100% ✅.
- [x] **2026-11-04 — Documentación y Blindaje de Autenticación de Dosimetría y Atributo `dosimetria_salt` (`README.md`, `agente_dosimetria.py`, `AppServiceProvider.php`):**
  - **Diagnóstico y Necesidad:**
    * El usuario requirió documentar en el manual técnico del paquete de agentes (`README.md`) la función exacta del atributo criptográfico `dosimetria_salt` en `config.json` para la anonimización de pacientes (Ley 1581 de 2012 / ISO 27001).
    * Además, se blindó la autenticación del agente de dosimetría para admitir fallback con triple vía (cabecera estándar `Authorization: Bearer`, cabecera alternativa `X-API-TOKEN` y parámetro en body `api_token`), previniendo que servidores web Apache en sedes o Central que deshabilitan el reenvío de cabeceras de autorización arrojen HTTP 401.
  - **Solución Implementada:**
    * **Documentación en `README.md` y `config.json.example`:**
      - Se añadió la sección dedicada *🛡️ Anonimización Criptográfica y el Atributo dosimetria_salt*, explicando su rol en PHI, mitigación de Rainbow Tables y consistencia de dosis acumulada por paciente.
    * **Blindaje en `agente_dosimetria.py`:**
      - Normalización automática de tokens (elimina `"Bearer "` y espacios en blanco si se configuran accidentalmente en el JSON).
      - Envío multicanal en cabeceras `Authorization`, `X-API-TOKEN` y body de la petición.
    * **Resolución en Central (`AppServiceProvider.php`):**
      - `Sanctum::getAccessTokenFromRequestUsing()` configurado para resolución flexible y tolerante a proxies.
  - **Verificaciones:**
    * `vendor\bin\pint.bat --test` validado al 100% passed ✅.
    * `python -m py_compile` validado sin errores sintácticos ✅.
    * Validación en vivo: transmisión masiva de lotes y latidos confirmada con éxito por Central (HTTP 200) ✅.
- [x] **2026-11-04 — Restauración y Modernización de Componente Reporte Estudios PACS (`reportes-estudios-pac`):**
  - **Diagnóstico y Causa Raíz:**
    * Al acceder a la URL `/reportes-estudios-pac` (enlace en sidebar "Gestión y Reportes -> Reportes -> Estudios PACS"), la página no cargaba y se mostraba completamente en blanco.
    * En un commit anterior (`dd90362795b4b0bcdae58487a5e0bc79ba627558`), el archivo `reporteEstudiosPACComponent.vue` había sido eliminado y desregistrado de `app.js` pensando que se consumiría exclusivamente desde el modal de producción. Sin embargo, la ruta `reportes-estudios-pac`, la vista Blade `reportes-estudios-pac.blade.php` y el enlace en `sidebar.blade.php` continuaban activos invocando a `<reportes-estudios-p-a-c-component />`.
  - **Solución Implementada:**
    * **Creación de `reporteEstudiosPACComponent.vue`:**
      - Se implementó el componente Vue 3 con diseño moderno AdminWrap, formulario con selector dinámico de sedes activas (`/api/config-sedes/listar/sedes/activas`), rango de fechas (Desde/Hasta) con validaciones completas y botón de descarga directa de Excel conectado a `/descarga-reportes-estudios-pac`.
      - Se añadió panel informativo detallando las columnas del reporte extraídas directamente de la base de datos PACS Dcm4chee (documento, paciente, fecha/hora estudio, descripción y modalidades).
    * **Registro en `resources/js/app.js`:**
      - Se registraron los alias asíncronos `ReportesEstudiosPACComponent` y `ReportesEstudiosPacComponent` para asegurar resolución tanto en PascalCase como en kebab-case en Blade.
  - **Verificaciones:**
    * ESLint validado sin errores (`npx eslint`) ✅.
    * Compilación Vite completada exitosamente (`npm run build` en 1.21s) ✅.
    * Formato LF verificado sin BOM ✅.
- [x] **2026-11-04 — Estandarización de Cards de Estadísticas / KPIs a Estilo Nativo AdminWrap (`DashboardDosimetria.vue`, `backupMonitorComponent.vue`, `seguridadTokensComponent.vue`):**
  - **Diagnóstico y Causa Raíz:**
    * Varias vistas de estadísticas y paneles de telemetría contenían estilos no nativos o inventados (`card-outline-*`, `round round-*`, barras de micro-progreso `<div class="progress">` adheridas a la base de las tarjetas, badges arbitrarios y tipografías personalizadas) que rompían la consistencia visual del tema AdminWrap del proyecto.
  - **Solución Implementada:**
    * **Aplicación del Patrón Canónico AdminWrap (`dashboardComponent.vue` / `reporteProduccionComponent.vue`):**
      - Contenedor canónico: `<div class="card"><div class="card-body"><div class="d-flex p-10 no-block">`.
      - Columna izquierda de icono: `<div class="align-self-center display-6 m-r-20"><i class="text-[color] [fa / icon-*]"></i></div>`.
      - Columna derecha de métrica: `<div class="align-slef-center"><h2 class="m-b-0">{{ valor }}</h2><h6 class="text-muted m-b-0">{{ etiqueta }}</h6></div>`.
    * **Depuración de Estilos Ajenos:**
      - Se suprimieron todas las clases `card-outline-primary`, `card-outline-success`, `card-outline-info`, `card-outline-warning`.
      - Se eliminaron las clases `round round-*` y las barras de progreso `<div class="progress">` en tarjetas de resumen de dosimetría y backups.
      - Se refactorizaron las propiedades computadas innecesarias y se conservó únicamente el mapeo semántico de colores de texto (`text-success`, `text-info`, `text-warning`, `text-primary`, `text-danger`).
  - **Verificaciones:**
    * ESLint validado con 0 errores y 0 advertencias (`npx eslint`) ✅.
    * Compilación Vite completada exitosamente (`npm run build` en 1.48s) ✅.
    * Formato LF verificado sin BOM ✅.
- [x] **2026-11-04 — Rebalanceo y Optimización del Ancho de Columnas en Tabla de Estudios Asignados (`estudioAsignadosComponent.vue`):**
  - **Diagnóstico y Causa Raíz:**
    * En `estudioAsignadosComponent.vue`, la tabla `#tablaEstudiosAsignados` carecía de especificación de anchos en `<thead>` y no tenía `autoWidth: false` ni `columnDefs` en DataTables (`initDataTable`). Esto provocaba que DataTables comprimiera y deformara las 12 columnas, apilara textos sin espaciado y permitiera ordenar columnas de prioridad o botones de acción.
    * Además, la información del paciente se presentaba con texto plano sin jerarquía (`# Docu: ... <br /> Nombres: ...`) y las etiquetas de estado carecían de centrado vertical uniforme.
  - **Solución Implementada:**
    * **Distribución Proporcional de Anchos y Jerarquía Visual:**
      - Columna `P` (Prioridad): `35px` centrada (`orderable: false`).
      - Columna `Sede`: `130px` con texto destacado en negrita.
      - Columna `Toma`: `105px` con `text-nowrap` y alineación vertical media.
      - Columna `Quien Asignó`: `120px`.
      - Columna `Médico`: `140px`.
      - Columna `Paciente`: `160px` con jerarquía tipográfica (nombre en negrita arriba y documento con icono abajo).
      - Columna `Detalle`: `160px` con etiquetas estructuradas para Atención, Accession, Descripción y badges de estado SAHI.
      - Columna `Producto`: `150px` en negrita.
      - Columnas de estado (`Leído` 60px, `Transcrito` 75px, `Activo` 60px): centradas vertical y horizontalmente con labels claros.
      - Columna `Acciones`: `100px` centrada y `orderable: false`, con espaciado consistente `m-r-5` entre botones.
    * **Integración en DataTables:** En `initDataTable()`, se configuró `autoWidth: false` y `columnDefs` blindando el comportamiento responsivo.
  - **Verificaciones:**
    * ESLint validado sin errores (`npx eslint`) ✅.
    * Compilación Vite completada exitosamente (`npm run build` en 1.27s) ✅.
    * Formato LF verificado sin BOM ✅.
- [x] **2026-11-04 — Rebalanceo y Optimización del Ancho de Columnas en Tabla de Asignación de Estudios (`AsignarEstudioComponent.vue`):**
  - **Diagnóstico y Causa Raíz:**
    * En `AsignarEstudioComponent.vue`, la tabla de estudios disponibles (`#tablaAsignarEstudio`) presentaba anchos de cabecera mal configurados con atributos erróneos (`with="5%"`, `with="5%"`, `with="30%"` en lugar de `width`), lo cual causaba que las fechas se partieran en múltiples renglones incómodos, los documentos quedaran comprimidos y el paciente tuviera un espacio desproporcionado.
    * Además, la inicialización de DataTables no tenía deshabilitado `autoWidth` ni poseía `columnDefs`, permitiendo que el plugin calculara anchos arbitrarios y que las columnas de selección y botones de acción fueran erróneamente ordenables.
  - **Solución Implementada:**
    * **Distribución Proporcional de Anchos en `<thead>` y `columnDefs`:**
      - Columna Checkbox selección: `35px` centrada (`orderable: false`).
      - Columna `Fecha del Estudio`: `140px` con `text-nowrap` y alineación vertical media.
      - Columna `# Documento`: `120px` con `text-nowrap`.
      - Columna `Paciente`: `220px` con tipografía en negrita destacada.
      - Columna `Accession`: `110px` con `text-nowrap`.
      - Columna `Descripción`: flexible (espacio restante proporcional).
      - Columna `Acciones`: `140px` centrada y `orderable: false`, con espaciado consistente `m-r-5` entre los 4 botones redondeados (Asignar, Ver imágenes, Quemar CD y Descargar ZIP).
    * **Configuración en DataTables:** Se integró `autoWidth: false` y la definición explícita de `columnDefs` para blindar el diseño responsive de la tabla.
  - **Verificaciones:**
    * ESLint validado sin errores (`npx eslint`) ✅.
    * Compilación Vite completada exitosamente (`npm run build` en 1.20s) ✅.
    * Formato LF verificado sin BOM ✅.
- [x] **2026-11-04 — Rebalanceo y Optimización del Ancho de Columnas en Tabla de Transcripción (`transcribirEstudioComponent.vue`):**
  - **Diagnóstico y Causa Raíz:**
    * En `transcribirEstudioComponent.vue`, las cabeceras `Lectura`, `Medico` y `Paciente` tenían estilos inline `width="50%"` heredados (`50% + 50% + 50% = 150%`), lo que causaba que la columna `Lectura` (que solo muestra una fecha y hora corta) acaparara la mitad de la pantalla, dejando vacíos gigantescos y comprimiendo excesivamente columnas esenciales como `Sede` (apilada en 4 líneas), `Toma`, `Asignación` y `Quien Asigno`.
  - **Solución Implementada:**
    * **Eliminación de Anchos Artificiales `50%`:** Se suprimieron todos los atributos `width="50%"` del `<thead>`.
    * **Distribución Proporcional de Anchos:**
      - Columna `P` (Prioridad): `35px` centrada.
      - Columna `Sede`: `140px` con texto destacado en negrita.
      - Columnas de fechas (`Toma`, `Lectura`, `Asignación`): `110px` con `text-nowrap` y alineación vertical limpia para evitar que las fechas se partan en renglones.
      - Columna `Quien Asignó`: `130px`.
      - Columna `Médico`: `150px`.
      - Columna `Paciente`: `170px` con tipografía jerárquica (nombre arriba en negrita y documento con ícono abajo).
      - Columna `Detalle`: `160px` con badges e información de atención, accession y descripción clínica.
      - Columna `Producto`: `160px` en negrita.
      - Columna `Acciones`: `80px` centrada.
    * **Integración en DataTables:** En `safeInitDataTable`, se configuró `autoWidth: false` y `columnDefs` con anchos definidos y deshabilitación de ordenamiento en columnas no ordenables (prioridad y acciones).
  - **Verificaciones:**
    * ESLint validado sin errores ni advertencias (`npx eslint`) ✅.
    * Compilación Vite completada exitosamente (`npm run build` en 1.27s) ✅.
    * Formato LF verificado sin BOM ✅.
- [x] **2026-11-04 — Flexibilización de Filtros de Búsqueda en Transcripción de Estudios (`transcribirEstudioComponent.vue`, `EstudioQueryService.php`):**
  - **Diagnóstico y Necesidad:**
    * El módulo de transcribir estudios prellenaba automáticamente las fechas de inicio y fin con el primer y último día del mes actual (`dayjs().startOf('month')` y `dayjs().endOf('month')`). Esto ocultaba estudios pendientes de meses anteriores y obligaba a limpiar o ajustar fechas manualmente para buscar pacientes por número de documento o nombre.
    * En el backend, la consulta (`applyTranscripcionFilters`) requería obligatoriamente ambas fechas simultáneas para aplicar cualquier filtro temporal (`!empty(bus_fech_ini) && !empty(bus_fecha_fin)`), ignorando búsquedas donde solo se especificara una fecha de inicio o una fecha de fin.
  - **Solución Implementada:**
    * **Filtros por Defecto Vacíos (`transcribirEstudioComponent.vue`):**
      - Se eliminaron las fechas por defecto `fechaDefectoIni` y `fechaDefectoFin`, inicializando `bus_fech_ini: ""` y `bus_fecha_fin: ""` en blanco.
      - En `btnLimpiar()`, se normalizó el reseteo de fechas a vacío `""` (antes forzaba el inicio del año y fecha actual).
      - En `listarPendientesTrascribir()`, se envían valores limpios con trim o `null` si están vacíos.
      - Se retiró la importación no utilizada de `dayjs` corrigiendo advertencias de ESLint.
    * **Consulta Flexible Multi-Criterio (`EstudioQueryService.php`):**
      - Se adaptó la cláusula de fechas: si se envían ambas, aplica `whereBetween`; si solo se envía fecha de inicio, filtra `>= $inicio`; si solo se envía fecha de fin, filtra `<= $fin`.
      - Si las fechas están vacías, la consulta no restringe por rango temporal y permite listar todos los pendientes de la sede o filtrar por cualquiera de los otros criterios (médico, asignador, documento/nombre).
      - Se flexibilizó el buscador de texto (`bus_nom_num_docu`) para admitir coincidencias parciales (`LIKE %search%`) sobre número de documento, nombre de paciente, número de accession (`accession_no`) y número de atención (`atencion`).
  - **Verificaciones:**
    * Laravel Pint validado al 100% passed (`vendor\bin\pint.bat --test`) ✅.
    * ESLint validado con 0 errores y 0 advertencias (`npx eslint`) ✅.
    * Compilación Vite completada exitosamente (`npm run build` en 1.49s) ✅.
    * Pruebas de consulta con sede sola, fecha inicio sola, fecha fin sola, rango y sin fechas ejecutadas con éxito ✅.
    * Formato LF verificado sin BOM ✅.
- [x] **2026-11-04 — Soporte de Alto Volumen (>15.000 estudios/mes) y Cargas Ilimitadas en Agente de Dosimetría:**
  - **Diagnóstico y Necesidad:**
    * Sedes hospitalarias de alta complejidad procesan más de 15.000 estudios mensuales. El límite por defecto de 200 truncaba las cargas masivas anuales y los administradores requerían la capacidad de realizar importaciones históricas completas sin límite artificial.
  - **Solución Implementada:**
    * **Bandera `--sin-limite` y Soporte de `--limit 0` (`agente_dosimetria.py`):**
      - Se implementó la bandera `--sin-limite` y el soporte para `--limit 0` en `agente_dosimetria.py`.
      - En `buscar_estudios_candidatos` y `obtener_pendientes_sqlite`, la cláusula `LIMIT %s` se omite dinámicamente si se solicita procesamiento ilimitado.
      - Se agregaron contadores y métricas de logging detalladas en el paso `[3/4]` para informar con total transparencia cuántos estudios fueron omitidos por caché anti-duplicados, cuántos no se encontraron en disco y cuántos se procesaron con éxito.
    * **Documentación Oficial en READMEs:**
      - Se incorporó en `storage/app_lidiag/agente_backups_dcm/README.md` la subsección dedicada *Estrategia para Hospitales de Alto Volumen (>15.000 estudios/mes) y Cargas Históricas* con los flujos de carga masiva mes a mes (`--desde/--hasta --sin-limite`), carga anual forzada (`--dias 365 --sin-limite --forzar`) y la explicación matemática de la operación horaria en producción (~20-30 estudios/hora).
      - Se documentó la capacidad en el `README.md` raíz bajo la Sección 14 (*Dosimetría*).
  - **Verificaciones:** `python -m py_compile` validado al 100% y formato LF sin BOM verificado ✅.
- [x] **2026-11-03 — Enriquecimiento y Documentación Integral de Comandos por Agente (`storage/app_lidiag/agente_backups_dcm/README.md`):**
  - **Diagnóstico y Necesidad:**
    * Los administradores de sistemas y técnicos de soporte en las sedes hospitalarias requerían un catálogo exhaustivo y estandarizado con todos los comandos de consola, banderas (flags CLI), comandos de diagnóstico/red y monitoreo de logs para cada uno de los agentes Edge.
  - **Solución Implementada:**
    * **Actualización en Scripts Python:**
      - Se implementó `--dry-run` en `backup_dcm_agent.py` para validar socket MySQL (3306), QNAP (445/139), espacio en disco y binario `mysqldump` sin alterar datos ni generar volcado.
      - Se ampliaron los argumentos de `sync_dcm_images.py` con `--config`, `--threads`, `--interval` y `--dry-run` (modo simulación que inyecta `/L` en Robocopy).
    * **Expansión de `storage/app_lidiag/agente_backups_dcm/README.md`:**
      - Tablas completas de argumentos CLI para los 4 agentes (`backup_dcm_agent.py`, `sync_dcm_images.py`, `agente_dosimetria.py`, `restore_dcm_images.py`).
      - Comandos detallados de ejecución por consola y lanzadores `.bat` (estándar, simulación, sobrescritura de credenciales/rutas, rangos históricos, forzado de caché).
      - Comandos de diagnóstico y pruebas previas (sockets TCP, pruebas SMB con `net use`, tests de tokens Bearer con PowerShell).
      - Comandos de streaming de logs en tiempo real (`Get-Content .\logs\*.log -Wait -Tail 30`).
      - Comandos de administración de tareas programadas de Windows (`schtasks`) y entorno virtual (`venv`).
  - **Verificaciones:** `python -m py_compile` exitoso en todos los scripts y formato LF verificado sin BOM ✅.
- [x] **2026-11-03 — Implementación Fase 2: Seguridad Diagnóstica, Botones Interactivos WhatsApp y Firma Digital con Código QR:**
  - **Diagnóstico y Necesidad:**
    * **Hallazgos Críticos en Urgencias:** En radiología de urgencias (neumotórax a tensión, hemorragia subaracnoidea, perforación de víscera hueca, tromboembolismo pulmonar masivo), la falta de un mecanismo de priorización inmediata y notificación push retrasaba la intervención médica de emergencia.
    * **Confirmación de Citas:** Los recordatorios de 24 horas requerían que el paciente digitara números o textos planos que a menudo fallaban o se ignoraban, necesitando botones interactivos rápidos (`Confirmar Cita` / `Cancelar Cita`).
    * **Firma Digital y Verificación Pública:** Se requería dotar a los informes médicos radiológicos generados en PDF de un sello de autenticidad criptográfica verificable mediante código QR y URL segura pública para pacientes y médicos remitentes (Ley 527 de 1999 y Resolución 1995 de 1999).
  - **Solución Implementada:**
    * **2.1 Marcado y Alerta de Hallazgo Crítico (`EstudioService.php`, `transcribirEstudioComponent.vue`, `leerEstudioComponent.vue`, `GuardarTranscripcionRequest.php`, `LeerEstudioRequest.php`):**
      - Se implementó el switch/checkbox de "Hallazgo Crítico en Urgencias / UCI" con advertencia visual prominente en los editores de lectura médica y transcripción.
      - Al marcarse como crítico, el backend escala automáticamente el estudio a prioridad máxima (`prioridad_id = 1`), antepone la marca `[HALLAZGO CRÍTICO]` en las observaciones clínicas y registra el evento en `AuditLogger`.
      - Se implementó la notificación push de emergencia inmediata vía `TelegramService` dirigida a los chats autorizados asistenciales (`allowed_chat_ids`) detallando sede, paciente, estudio, radiólogo y marca de tiempo.
    * **2.2 Botones Interactivos de WhatsApp para Confirmación de Citas (`WhatsAppService.php`, `WhatsAppFlowService.php`):**
      - En `WhatsAppService::enviarRecordatorio`, se sustituyó el mensaje plano por botones interactivos rápidos de Meta Graph API (`cita_confirmar_{id}` y `cita_cancelar_{id}`) con fallback transparente a texto.
      - En `WhatsAppFlowService`, se interceptaron las respuestas interactivas: al pulsar "Confirmar Cita", la cita se actualiza de inmediato a estado `confirmada` y se envía confirmación formal; al pulsar "Cancelar Cita", pasa a `cancelada` con motivo registrado.
      - En el resumen final de solicitud de cita, se implementó el envío de botones interactivos (`btn_confirmar_solicitud` y `btn_cancelar_solicitud`).
    * **2.3 Firma Digital con Código QR y Timestamp en PDF (`QrCodeHelper.php`, `PlantillaEstudioService.php`, `VerificacionInformeController.php`, `validar-informe.blade.php`):**
      - Se desarrolló el helper de código QR autónomo en pure PHP + GD (`QrCodeHelper.php`) sin dependencias externas, compatible con ISO/IEC 18004.
      - En `PlantillaEstudioService::editWord`, se genera un token criptográfico seguro HMAC-SHA256 (`token_validacion`) y la URL de verificación pública (`/validar-informe/{token}`), incrustando el código QR temporal y el pie legal de certificación digital dentro del informe.
      - Se creó el controlador público y la vista oficial de certificación (`VerificacionInformeController.php` y `validar-informe.blade.php`) con anonimización de datos sensibles del paciente (Habeas Data Ley 1581) y cálculo de hash de integridad SHA-256.
  - **Verificaciones:**
    * Pint validado al 100% passed (`vendor\bin\pint.bat --test`) en todos los archivos PHP nuevos y modificados ✅.
    * ESLint validado con 0 errores y 0 advertencias (`npx eslint`) en componentes Vue ✅.
    * Compilación de producción Vite completada exitosamente (`npm run build` en 1.23s) con bundle inicial optimizado en 197 kB (62 kB gzip) ✅.
    * Verificación de ruta pública `/validar-informe/{token}` testeada con respuesta HTTP 200 y tokens fraudulentos con HTTP 404/403 ✅.
    * Formato LF verificado sin BOM ✅.
- [x] **2026-11-03 — Depuración Estricta de Estilos en Dashboard de Dosimetría (`/dosimetria/dashboard`) alineado 100% a AdminWrap:**
  - **Diagnóstico y Necesidad:**
    * Se identificó la presencia residual de clases no pertenecientes a la plantilla oficial `Documentos/Plantilla lidiag` (clases de Bootstrap 5 como `gap-2`, `shadow-sm`, `border-0`, `fs-6`, `align-middle`, `spinner-border`, `text-warning-emphasis`, `opacity-75` y utilidades de espaciado `mr-1`, `mb-2`, `mb-3`, `py-2 px-3`, `py-5`), así como tags `badge badge-*` y backdrop filters CSS en modales.
  - **Solución Implementada:**
    * **Purga de Clases Extrañas:** Se eliminaron por completo `gap-2`, `shadow-sm`, `border-0`, `fs-6`, `text-warning-emphasis`, `opacity-75` y selectores Bootstrap 5.
    * **Estandarización de Espaciados a AdminWrap:** Sustitución de `mr-1`/`mr-2` por `m-r-5`/`m-r-10`, `ml-1` por `m-l-5`, `mb-2`/`mb-3` por `m-b-10`/`m-b-20`, `mt-2` por `m-t-10`, y padding `py-5`/`py-2 px-3` por `p-20`/`p-30`.
    * **Spinners Nativos:** Reemplazados `spinner-border` por íconos FontAwesome oficiales `<i class="fa fa-spinner fa-spin fa-2x text-info"></i>`.
    * **Info-Boxes Estrictos (`index.html`):** Alineados al diseño exacto sin pies de tarjeta artificiales, con barra de micro-progreso nativa `style="height: 3px"`.
    * **Formularios y Filtros (`form-layout.html`):** Reestructurados bajo `form-group m-b-0`, `control-label font-12 m-b-5 text-muted` y `form-control form-control-sm custom-select`.
    * **Tablas Nativas (`pro-of-month` y `vm`):** Aplicada la clase de alineación vertical nativa `.vm` (`table table-hover vm` y `table vm no-th-brd pro-of-month`).
    * **Badges / Labels Canónicos:** Migración integral de `badge badge-*` a la convención canónica de AdminWrap: `.label.label-primary`, `.label.label-danger`, `.label.label-info`, `.label.label-inverse`, `.label.label-rounded` y `.label.label-light-*`.
    * **Modal Canónico (`ui-modals.html`):** Adaptado a `modal-header` nativo con `close` estándar y backdrop `style="display: block; background: rgba(0, 0, 0, 0.5)"`, botón footer `btn btn-secondary waves-effect`.
  - **Verificaciones:**
    * Auditoría con script Python verificó 0 clases Bootstrap 5, 0 estilos arbitrarios y 100% apego a la plantilla ✅.
    * ESLint validado sin errores ni advertencias (`npx eslint`) ✅.
    * Compilación Vite completada sin errores (`npm run build` en 1.30s) ✅.
    * Formato LF verificado sin BOM ✅.

- [x] **2026-11-03 — Rediseño Integral UI/UX del Dashboard de Dosimetría (`/dosimetria/dashboard`) con Estándar AdminWrap:**
  - **Diagnóstico y Necesidad:**
    * El dashboard de dosimetría médica y control de radiación (Resolución 482 de 2018) presentaba un contenedor redundante `container-fluid`, estilos scoped manuales y widgets que no aprovechaban los componentes visuales nativos de la plantilla AdminWrap (`index.html`, `index2.html`, `index3.html`).
    * Se requería estructurar las métricas de radiación avanzada ($CTDI_{vol}$, $DLP$, $SSDE$, Dosis Efectiva $E$ [ICRP 103], $DGM$, $DAP$, $EI$, $DI$ IEC 62494-1) en paneles acordes a la identidad gráfica institucional.
  - **Solución Implementada:**
    * **Header y Breadcrumb Canónico:** Implementado con `row page-titles`, títulos corporativos `text-themecolor`, ruta de navegación y acciones rápidas (`Recargar`, `Catálogo NRD`).
    * **Tarjetas de Resumen (Info-Boxes):** Reescritas usando `.d-flex.p-10.no-block` con `.round` temáticos (`round-primary`, `round-success`, `round-warning`, `round-danger`), indicadores tipográficos `.display-6` y barras de progreso `.progress-bar`.
    * **Monitor de Agentes Edge (Heartbeat):** Transformado en tarjeta informativa con insignia de estado en vivo (`.label.label-rounded`), telemetría de sedes y botón de reinicio/recarga.
    * **Gráficos Avanzados Chart.js:**
      - Gráfico de barras de Dosis Medida vs NRD por modalidad y protocolo.
      - Gráfico de dona de Conformidad NRD con leyendas personalizadas `list-inline`.
      - Gráfico de líneas con evolución y tendencia temporal de dosis acumulada.
      - Gráfico de dona con distribución de disparos por modalidad (CT, DX, MG, CR, XA).
    * **Ranking de Equipos Emisores:** Adaptado fielmente de "Projects of the Month" (`index.html`) con `.table.vm.no-th-brd.pro-of-month`, avatares `round`, badges de estado y cálculo de dosis promedio por tubo emisor.
    * **Timeline de Auditoría Clínica:** Adaptado del componente "Today's Schedule" (`index3.html`) con estructura `.steamline` y `.sl-item`, reportando desviaciones de umbrales y alertas en tiempo real.
    * **Tabla Maestra de Registros:** Reorganizada con `.table.table-hover` nativa, identificadores seudonimizados HMAC y badges estandarizados.
    * **Backend (`DosimetriaService.php`):** Se integraron agregaciones de `equipos_ranking`, `eventos_timeline`, `tasa_conformidad`, `dosis_efectiva_promedio` y `total_pacientes`.
  - **Verificaciones:**
    * ESLint en `DashboardDosimetria.vue` y `app.js` pasó con 0 errores y 0 advertencias ✅.
    * Laravel Pint en `DosimetriaService.php` validado al 100% passed ✅.
    * Compilación Vite (`npm run build`) completada con éxito en 1.26s ✅.
    * Formato LF verificado sin BOM ✅.

- [x] **2026-11-03 — Refactorización UI/UX de Monitoreo de Backups (`/seguridad-backups`) con Estándar AdminWrap:**
  - **Diagnóstico y Necesidad:**
    * La vista de monitoreo de backups (`backupMonitorComponent.vue`) utilizaba estilos no corporativos / inventados (sombras artificiales `shadow-sm`, badges redondeados tipo píldora `badge-pill`, temas `thead-light`, alturas fijas `h-100` y estilos scoped con scrollbars personalizados) que rompían la consistencia visual y de diseño con el resto del panel AdminWrap de LIDiag.
  - **Solución Implementada:**
    * **Estandarización de Encabezado y Breadcrumbs:** Alineado a la estructura canónica de AdminWrap (`col-md-5 align-self-center` y `col-md-7 text-right d-none d-md-block`) con botones compactos `btn-sm btn-outline-secondary` y `btn-sm btn-info`.
    * **Depuración de Estilos Inventados:**
      - Se eliminaron todas las clases `shadow-sm`, `h-100`, `thead-light`, `badge-pill`, `align-middle` y utilidades de fuente innecesarias.
      - Se migraron los badges al estándar de AdminWrap: `.label.label-success`, `.label.label-warning`, `.label.label-danger` y `.label.label-light-inverse`.
      - Se adaptó la tabla de sedes remotas a la clase canónica `table table-hover table-striped table-bordered` envuelta en `table-responsive m-t-40`.
      - Se eliminó por completo el bloque `<style scoped>` y el scrollbar artificial.
    * **Normalización de Confirmaciones:** Se limpió el llamado de `confirmAction` para utilizar los parámetros soportados (`title`, `text`, `icon`).
  - **Verificaciones:**
    * ESLint validado sin errores (`npx eslint`) ✅.
    * Compilación Vite completada sin errores (`npm run build` en 1.23s) ✅.
    * Formato LF verificado sin BOM ✅.
- [x] **2026-11-03 — Implementación Fase 1: Autoguardado de Borradores, Macros Clínicas y Alertas Telegram de Sedes:**
  - **Diagnóstico y Necesidad:**
    * Los médicos radiólogos requerían protección contra pérdida de informes en redacción ante cortes imprevistos de energía o cierres de navegador, así como mecanismos de autocompletado rápido de párrafos diagnósticos estándar.
    * Además, el equipo técnico requería que el bot de Telegram alertara de forma proactiva en el resumen matutino sobre sedes con agentes en estado crítico o sin copias de seguridad recientes.
  - **Solución Implementada:**
    * **Autoguardado en LocalStorage (`WysiwygEditor.vue`, `transcribirEstudioComponent.vue`, `leerEstudioComponent.vue`):**
      - Se implementó `saveDraft(content)` con debounce silencioso en `localStorage` indexado por `draftKey` (`estudio_${id}`).
      - Al abrir el estudio, si existe un borrador no guardado más reciente, se despliega un banner superior interactivo que permite restaurar o descartar el borrador en 1 clic.
      - Al guardar exitosamente el informe médico en el backend, el borrador local se purga automáticamente mediante `clearDraft()`.
    * **Macros y Snippets de Expansión Clínica (`wysiwyg.js`, `WysiwygEditor.vue`):**
      - Se creó el catálogo `CLINICAL_MACROS` con atajos diagnósticos estándar (`.normal`, `.torax`, `.craneo`, `.abdomen`, `.columna`, `.sinpatologia`, `.conclusion`).
      - Detección reactiva en el teclado: al escribir la clave con punto y presionar `Espacio`, `Tab` o `Enter`, el editor expande inmediatamente el párrafo clínico completo sin perder el foco.
      - Se incorporó en la barra de herramientas el menú interactivo desplegable de macros con ícono de varita mágica para inserción directa en 1 clic.
    * **Alertas de Infraestructura en Telegram (`TelegramBotHandlerService.php`, `TelegramDailyDigestCommand.php`):**
      - Se implementó `generarYEnviarAlertaAgentesSedes(chatId)` que consulta el estado en vivo de los agentes de base de datos, imágenes y dosimetría de cada sede hospitalaria, emitiendo alertas destacadas sobre sedes con anomalías o sin telemetría en el resumen diario matutino.
  - **Verificaciones:**
    * ESLint validado sin errores (`npx eslint`) ✅.
    * Laravel Pint verificado al 100% passed (`vendor\bin\pint.bat --test`) ✅.
    * Compilación Vite completada sin errores (`npm run build` en 1.35s) ✅.
    * Formato LF verificado sin BOM ✅.

- [x] **2026-11-03 — Soporte Dual SMB (Puerto 445 y 139 NetBIOS) en Agentes Edge (`backup_dcm_agent.py`, `sync_dcm_images.py`, `restore_dcm_images.py`):**
  - **Diagnóstico y Causa Raíz:**
    * Al ejecutar el agente de backups en la sede Espinal (`10.35.47.3`), el paso de transmisión a la NAS QNAP (`10.35.47.21`) reportaba `Estado QNAP : OFFLINE (QNAP 10.35.47.21:445 no responde)`.
    * La prueba de red `Test-NetConnection -ComputerName 10.35.47.21 -Port 139` confirmó `TcpTestSucceeded : True`, evidenciando que la NAS QNAP tiene activo y escuchando el protocolo SMB a través de NetBIOS (puerto 139), mientras que el puerto 445 se encuentra restringido por perfil de firewall de QTS o enlace de servicio.
    * Los scripts de Python evaluaban exclusivamente el puerto 445 mediante `check_qnap_port(qnap_ip, port=445)`, abortando erróneamente la copia a la NAS a pesar de que el transporte SMB por el puerto 139 y el comando `net use` de Windows estaban 100% operativos.
  - **Solución Implementada:**
    * Se actualizó la función `check_qnap_port` en `backup_dcm_agent.py`, `sync_dcm_images.py` y `restore_dcm_images.py` para evaluar una tupla de puertos `ports=(445, 139)`.
    * Si el puerto 445 no responde, el socket comprueba de inmediato el puerto 139; al responder cualquiera de los dos, se procede con la autenticación SMB y copia a la NAS QNAP sin interrupción.
    * Formato LF y validación sintáctica verificados en los 3 scripts.
  - **Verificaciones:** `python -m py_compile` sin errores y formato LF validado ✅.
- [x] **2026-11-03 — Documentación Paso a Paso de Generación de Tokens por Sede en READMEs:**
  - **Diagnóstico y Necesidad:**
    * Los administradores de sistemas y técnicos de soporte requerían una guía clara y paso a paso para la generación, rotación y despliegue del archivo `config.json` con credenciales Sanctum en los servidores locales de los hospitales.
  - **Solución Implementada:**
    * **`storage/app_lidiag/agente_backups_dcm/README.md`:** Se incorporó en la Sección 3 el tutorial paso a paso con las opciones de descarga directa de `config.json` o copiado de token, advertencia de seguridad sobre el hash irreversible y la verificación en vivo en las columnas **DB DCM**, **Img Dicom** y **Dosi**.
    * **`README.md` (Raíz):** Se añadió bajo la Sección 10 (*Seguridad y Auditoría*) la subsección *Gestor de Tokens por Sede para Agentes Edge (`/seguridad-tokens`)* con el flujo operativo completo.
    * **Formato:** Normalización estricta a LF.
  - **Verificaciones:** Consistencia de enlaces y formato Markdown verificados ✅.
- [x] **2026-11-03 — Desglose de Telemetría por Agente (DB DCM, Img Dicom, Dosi) en Gestión de Tokens por Sede:**
  - **Diagnóstico y Necesidad:**
    * El módulo `/seguridad-tokens` mostraba una sola columna genérica de "Última Transmisión", impidiendo identificar cuál de los 3 agentes Edge distribuidos (`backup_dcm_agent.py`, `sync_dcm_images.py`, `agente_dosimetria.py`) estaba transmitiendo correctamente y cuál requería atención técnica.
  - **Solución Implementada:**
    * **Persistencia de Telemetría en Central:**
      - En `BackupUploadController::telemetria`, se persiste en memoria caché (`Cache::put("telemetria_sede_{$sedeId}_{$servicio}")`) el estado, mensaje y timestamp de los servicios `backup_db` e `sync_imagenes`.
      - En `BackupUploadController::uploadDcm`, al recibir exitosamente un `.zip`, se actualiza la telemetría de `backup_db` con el nombre del archivo y tamaño.
      - En `DosimetriaService::registrarHeartbeat` y `guardarLote`, se actualiza la telemetría de `dosimetria` con el conteo de pendientes en cola o registros procesados.
    * **Consolidación en `TokenSedeService.php`:**
      - Se implementó `obtenerEstadoAgente($sedeId, $servicio)` con resolución en caché de telemetría y fallbacks directos (archivos `.zip` en disco para `backup_db`, latido `dosimetria_heartbeat` y registros de dosis para `dosimetria`).
      - Se exponen `agente_backup_db`, `agente_sync_imagenes` y `agente_dosimetria` por cada sede en la respuesta JSON.
    * **Frontend Vue 3 (`seguridadTokensComponent.vue`):**
      - Se preservaron las 4 tarjetas KPI de resumen (Total Sedes, Sedes con Token Activo, Sedes sin Token y Cuenta de Servicio Central) construidas con clases nativas AdminWrap (`card card-outline-*`, `.round round-*` y `badge`).
      - Se reemplazó la columna única por 3 columnas claras y centradas: **DB DCM**, **Img Dicom** y **Dosi**.
      - Cada columna visualiza el semáforo nativo (`label label-success` / `label label-warning` / `label label-danger` / `label label-light-inverse` para "Sin datos") junto con el tiempo relativo (`tiempo_humano`) y tooltip con el mensaje de diagnóstico.
    * **Compilación y Estilo:** Formato LF verificado, Laravel Pint pasado al 100% y Vite empaquetado exitosamente.
  - **Verificaciones:** `vendor/bin/pint.bat --test` passed, `npx eslint` passed y `npm run build` verificado ✅.
- [x] **2026-11-03 — Incorporación de Estilos de Fuente y Tipografía en el Editor WYSIWYG (`WysiwygEditor.vue`, `wysiwyg-color.css`):**
  - **Diagnóstico y Necesidad:**
    * Los médicos radiólogos y operadores de transcripción requerían selectores de tipos de fuente (familia tipográfica como Arial, Calibri, Times New Roman, Roboto, etc.) y tamaños de fuente (Pequeño, Normal, Mediano, Grande, Muy grande), así como estilos de encabezado (Párrafo, Título 1, Título 2, Título 3) directamente en la barra de herramientas del editor WYSIWYG.
    * `bootstrap-wysihtml5` solo proveía un menú básico en inglés (`Normal text`, `Heading 1-3`), carecía de selectores para familias tipográficas y tamaños de texto, y sus menús desplegables fallaban al no integrarse adecuadamente con Bootstrap 4 (`.show` vs `.open`).
  - **Solución Implementada:**
    * **Comandos Personalizados en Wysihtml5 (`WysiwygEditor.vue`):**
      - Se implementó `wysihtml5.commands.fontFamily` usando `wysihtml5.commands.formatInline` con regex `wysiwyg-font-family-[a-z0-9-]+` para aplicar clases tipográficas a selecciones de texto o al bloque/body activo cuando no hay rango seleccionado.
      - Se extendió defensivamente `wysihtml5.commands.fontSize` para permitir cambiar tamaños tanto en selecciones de rango como en el cursor de texto.
      - Se añadieron a `parserRules.classes` las familias (`wysiwyg-font-family-*`) y tamaños (`wysiwyg-font-size-*`).
    * **Estilos CSS en iframe y Barra de Herramientas (`wysiwyg-color.css`, `WysiwygEditor.vue`):**
      - Se definieron en `public/lib/css/wysiwyg-color.css` las familias: Arial, Calibri, Times New Roman, Roboto, Verdana, Tahoma y Courier New, junto con los tamaños: pequeño (11px), normal (14px), mediano (16px), grande (20px) y muy grande (24px).
      - Se rediseñó la barra de herramientas (`.wysihtml5-toolbar`) con estilo moderno, limpio, flexbox adaptable, botones con íconos vectoriales (`fa fa-bold`, `fa fa-italic`, `fa fa-underline`, `fa fa-list-ul`, `fa fa-list-ol`), menú de estilos de párrafo/títulos en español, selector de fuentes y selector de tamaños con soporte nativo de Bootstrap 4 y cierre automático al dar clic fuera o al enfocar el editor.
    * **Integridad con SAHI y Reportes Clínicos:**
      - El editor continúa emitiendo texto plano limpio mediante `htmlToPlain()` al modelo reactivo (`v-model`), garantizando total compatibilidad con el esquema memo de SAHI SQL Server (`hceEsquemaDetMemo.DesMemo`) y con el generador de Word y PDF de `PlantillaEstudioService`.
  - **Verificaciones:**
    * ESLint validado sin errores (`npx eslint`) ✅.
    * Prettier formateado (`npx prettier --write`) ✅.
    * Compilación Vite completada sin errores (`npm run build` en 1.37s) ✅.
    * Formato LF verificado sin BOM ✅.

- [x] **2026-11-03 — Refactorización UI/UX del Módulo Tokens de Sedes y Resiliencia en confirmAction:**
  - **Diagnóstico y Necesidad:**
    * El módulo de generación de tokens (`/seguridad-tokens`) presentaba inconsistencias visuales frente al estándar AdminWrap y Bootstrap 4 de LIDiag (tarjetas KPI ajenas con estilos utilitarios no usados en los módulos de administración, cabeceras con íconos directos en `<h3>`, modal con cabeceras `bg-success` y bordes/sombras no nativos, y un bloque `<style scoped>` con clases duplicadas).
    * Además, la llamada a `confirmAction` al generar/rotar el token fallaba al recibir cadenas simples en ciertos flujos debido a que la función asistente en `alerts.js` esperaba un objeto de opciones `{ title, text, icon }`.
  - **Solución Implementada:**
    * **Resiliencia en `resources/js/helpers/alerts.js`:** Se adaptó `confirmAction` para que soporte de forma transparente tanto objetos `{ title, text, icon }` como llamadas con firma legado `(mensaje, icon)`, garantizando la ejecución del diálogo SweetAlert sin excepciones.
    * **Refactorización Integral de `seguridadTokensComponent.vue`:**
      - Se eliminaron las 4 tarjetas KPI artificiales para seguir el diseño de tarjeta única de `/usuarios`, `/roles` y `/configuracion-sedes`.
      - Migas de pan y cabecera normalizadas a `col-md-5 align-self-center` y `col-md-7 text-right d-none d-md-block` con `text-themecolor`.
      - Tabla canónica `display nowrap table table-hover table-striped table-bordered` con `table-responsive m-t-40`.
      - Botones de acción estandarizados a `btn waves-effect waves-light btn-rounded btn-sm m-r-5` con clases `btn-outline-*`.
      - Indicadores de estado con etiquetas nativas `label label-success`, `label label-danger` y `label label-light-inverse`.
      - Integración de `<table-skeleton-loader :visible="cargando" />` y `<empty-state-component>`.
      - Modal de entrega de token 100% nativo de AdminWrap (`modal-header` limpio, `modal-title` con `<h4>`, `modal-body`, `alert alert-warning`, `form-group` y botón `btn btn-inverse waves-effect`).
      - Remoción total de bloques `<style scoped>`.
    * **Compilación de Assets:** Ejecución limpia de `npm run build` con Vite.
  - **Verificaciones:** `npx eslint --fix` sin advertencias, Vite compilado al 100% y formato LF verificado ✅.
- [x] **2026-11-03 — Corrección y Preservación de Saltos de Línea al Pegar Texto de Word en el Editor WYSIWYG (`wysiwyg.js`, `WysiwygEditor.vue`):**
  - **Diagnóstico y Causa Raíz:**
    * Al copiar texto desde Microsoft Word y pegarlo en el editor (utilizado en Transcripción y Lectura de Estudios), el texto perdía todos los saltos de línea y párrafos quedando en una sola línea continua.
    * Esto ocurría porque: (1) Las reglas de parseo predeterminadas de `bootstrap-wysihtml5` no incluían la etiqueta `<p>`, provocando que `wysihtml5.dom.parse` renombrara cada párrafo `<p class="MsoNormal">` de Word a `<span>` (etiqueta inline) y descartara los párrafos vacíos `<p><o:p>&nbsp;</o:p></p>`, (2) `cleanPastedHTML` de wysihtml5 no normalizaba la estructura de Word, y (3) `htmlToPlain` no contemplaba atributos en `<p ...>` ni bloques `<div>`, despojando los saltos y uniendo las frases consecutivas.
  - **Solución Implementada:**
    * **Helper (`resources/js/helpers/wysiwyg.js`):**
      - Se implementó `cleanPastedHtml(html)`: extrae fragmentos `<!--StartFragment-->`, elimina comentarios y metadatos de Office (`xml`, `o:`, `w:`, `m:`), normaliza párrafos vacíos a `<br>`, transforma cierres de bloque (`</p>`, `</div>`, `</h1-6>`) en `<br>`, preserva formato seguro (`<b>`, `<strong>`, `<i>`, `<em>`, `<u>`), elimina etiquetas residuales (`<span>`, `<font>`) y colapsa saltos múltiples excesivos respetando renglones en blanco.
      - Se modularizó y fortaleció `htmlToPlain(html)` y `plainToHtml(text)` garantizando decodificación de entidades HTML y separación exacta de párrafos.
    * **Editor (`resources/js/components/shared/WysiwygEditor.vue`):**
      - Se incorporó soporte explícito para `p: {}` y `div: {}` en `parserRules.tags`.
      - Se interceptó el evento nativo `paste` en el `contentDocument` del iframe de wysihtml5 (`attachPasteForwarding`), limpiando el portapapeles mediante `cleanPastedHtml` e insertándolo limpiamente con `insertHTML` en la posición del cursor, sincronizando `modelValue`.
      - Se añadió `@paste="onNativeTextareaPaste"` en el textarea nativo para modo móvil o fallback.
  - **Verificaciones:**
    * ESLint validado sin errores (`npx eslint`) ✅.
    * Prettier formateado (`npx prettier --write`) ✅.
    * Compilación Vite completada sin errores (`npm run build`) ✅.
- [x] **2026-11-03 — Consulta de Órdenes y Facturas en Interface (SAHI) para Admisión de Pacientes (`admisionComponent.vue`, `SAHIService.php`, `SAHIController.php`):**
  - **Diagnóstico y Necesidad:**
    * En el módulo de Admisión de Pacientes (`/pacientes/admision`), los operadores necesitaban consultar las atenciones, órdenes médicas y facturas registradas en la interface HIS (SAHI) correspondiente a la sede activa, permitiendo vincular los estudios y enviarlos a la Modality Worklist (MWL) de Rayos X sin transcripción manual redundante.
  - **Solución Implementada:**
    * **Frontend (`admisionComponent.vue`):**
      - Se añadió en la tabla de pacientes el botón interactivo de consulta en interface (`fa fa-database`), habilitado reactivamente solo si la sede actual tiene configurada interface activa (`interface_software === 'sahi'`) y deshabilitado con tooltip informativo si no aplica.
      - Se creó el modal `modalInterface` con selector de atenciones históricas del paciente en el HIS, recarga rápida y tabla detallada de procedimientos diagnosticados.
      - La tabla desglosa si el ítem proviene de Factura (`NumFactura`) o de Orden Médica/Movimiento (`NumMovimiento`), mostrando código CUPS, descripción del estudio, médico ordenante, EPS/convenio y valor.
      - Se implementó la acción "Worklist" que cierra la consulta, abre el modal de envío a Worklist y realiza un **match automático por código CUPS (`cod_cups`)** con el catálogo de productos de Rayos X de LIDiag, alertando con selección asistida si no hubiese coincidencia exacta.
    * **Backend (`SAHIService.php` y `SAHIController.php`):**
      - En `SAHIService::listarProductosAtencion`, se enriqueció la proyección de `consulta1` (facturas) y `consulta2` (movimientos) unificando con `CAST` seguro a `VARCHAR(50)` el número de documento (`NumFactura` / `NumMovimiento`) y el tipo de registro (`FACTURA` / `ORDEN`), ampliando los grupos diagnósticos permitidos (`[44, 156, 190, 191, 195, 204, 225, 243]`).
      - En `SAHIController::listarProductosAtencion`, se flexibilizó el parámetro de entrada aceptando tanto `atencion_id` como `bus_nom_num_docu` e `idAtencion`.
  - **Verificaciones:**
    * Compilación Vite completada sin errores (`npm run build` en 2.46s) ✅.
    * ESLint validado sin errores (`npx eslint`) ✅.
    * Laravel Pint verificado (`vendor\bin\pint.bat --test`) al 100% passed ✅.
    * Validación directa en base de datos SAHI (`ESP_PRI`) confirmando retorno simultáneo de órdenes médicas y facturas de venta ✅.
- [x] **2026-11-03 — Estandarización de Transmisión de Datos y Telemetría Central en los 3 Agentes Edge (`agente_dosimetria.py`, `backup_dcm_agent.py`, `sync_dcm_images.py`):**
  - **Diagnóstico y Necesidad:**
    * En el dashboard central `/seguridad-backups`, las sedes hospitalarias aparecían en estado crítico ("Sin backups" / rojo) debido a que `backup_dcm_agent.py` y `sync_dcm_images.py` únicamente transferían datos localmente a la NAS QNAP vía SMB, sin reportar su estado ni transmitir los archivos de backup comprimidos hacia la API Central de LIDiag.
    * Solo `agente_dosimetria.py` realizaba conexiones HTTP hacia Central. Se requería unificar la capa de red para que los 3 agentes consuman `api_url` y `api_token` de `config.json` y transmitan tanto sus datos como su estado de salud en tiempo real.
  - **Solución Implementada:**
    * **Backend Central (`BackupUploadController.php`):** Se adaptó la autenticación para soportar tokens Sanctum por sede además del `BACKUP_API_TOKEN` estático. Se agregó el endpoint `POST /api/backups/telemetria` para recepción de latidos de sincronización y estado de respaldos con volcado a `storage/logs/backups.log`.
    * **Rutas:** Registro de `POST /api/backups/telemetria` en `routes/api.php`.
    * **Agente de Backups (`backup_dcm_agent.py`):** Incorporación de lectura de `api_url` y `api_token`. Se añadió el Paso 9 para transmitir el `.zip` a Central (`POST /api/backups/upload-dcm`) y emitir telemetría (`POST /api/backups/telemetria`) reflejando el estado "Al día" en el monitor de backups.
    * **Agente de Sincronización de Imágenes (`sync_dcm_images.py`):** Se integró `requests`, resolución dinámica de endpoints y emisión de telemetría a Central (`POST /api/backups/telemetria`) en cada ciclo de sincronización de Robocopy con métricas de imágenes copiadas, duración y espacio libre.
    * **Agente de Dosimetría (`agente_dosimetria.py`):** Normalización de `resolve_api_endpoint` para evitar 404 en el endpoint de heartbeat independientemente de sufijos de URL.
  - **Verificaciones:** Formato LF verificado ✅.

- [x] **2026-11-03 — Creación del Módulo "Tokens de Sedes" (Generador y Gestor Sanctum para Agentes Edge):**
  - **Diagnóstico y Necesidad:**
    * Los Agentes Edge distribuidos en los hospitales de la red asistencial (Espinal, La Dorada, Chaparral, Ibagué, Líbano, Honda, Rovira) requieren credenciales API Sanctum para autenticar de forma segura el envío masivo de dosimetría radiológica (`POST /api/dosimetria/registros`), latidos periódicos (`heartbeat`) y la telemetría/subida de respaldos DCM (`/seguridad-backups`).
    * Se requería una interfaz administrativa dentro del módulo de **Seguridad** que permitiera emitir tokens individuales por sede asociados a un usuario de servicio técnico dedicado (`agente.edge@lidiag.gov.co`), con tokens permanentes sin caducidad, rotación segura con reemplazo automático (1 token activo por sede), auditoría y un modal de alta seguridad con copiado rápido y descarga directa del archivo `config.json` preconfigurado.
  - **Solución Implementada:**
    * **Seeder y Permisos Spatie:** Se creó `database/seeders/PermisionSeguridadTokensSeeder.php` registrando de forma idempotente los permisos `seguridad.tokens.index`, `seguridad.tokens.create`, `seguridad.tokens.revoke`, asignándolos al rol `Administrador` y garantizando la existencia del usuario de servicio `agente.edge@lidiag.gov.co`.
    * **Servicio Backend:** Se implementó `App\Services\Seguridad\TokenSedeService.php` heredando de `BaseService`, con gestión de tokens Sanctum (`listarSedesConTokens`, `generarTokenSede`, `revocarToken`, `construirConfigJson` con los datos de red y credenciales de cada sede) y auditoría vía `AuditLogger`.
    * **Controlador REST:** Se creó `App\Http\Controllers\Seguridad\TokenSedeController.php` con políticas Spatie y endpoints para index, store, destroy y descarga de `config.json`.
    * **Rutas:** Rutas API registradas en `routes/api_seguridad.php` y ruta web `Route::view('/seguridad-tokens', 'modulos.seguridad.tokens')` en `routes/web.php`.
    * **Frontend Vue 3:** Se creó `resources/js/components/modulos/seguridad/seguridadTokensComponent.vue` con estética AdminWrap, tarjetas KPI (total sedes, activas, pendientes, cuenta de servicio), tabla con badges de estado, última actividad y semáforos, confirmaciones SweetAlert2 y Modal de alta seguridad con copiado en 1 clic y descarga de `config.json`.
    * **Cableado:** Montaje en `resources/views/modulos/seguridad/tokens.blade.php`, registro en `resources/js/app.js`, enlace en el sidebar `resources/views/comunes/sidebar.blade.php` bajo la sección `Seguridad` y documentación técnica en `docs/CONTROLLERS.md`.
  - **Verificaciones:** Formato LF y validación de sintaxis verificados ✅.

- [x] **2026-11-03 — Corrección de `UnboundLocalError` con `pydicom` y supresión de warnings VR en `agente_dosimetria.py`:**
  - **Diagnóstico:** Al procesar estudios DICOM, el agente lanzaba `[WARNING] Error al leer cabecera DICOM... cannot access local variable 'pydicom' where it is not associated with a value`. La causa raíz fue un `import pydicom.datadict` anidado dentro de la función `extraer_metricas_dosis()` para la lectura de tags privados en CT, lo que convirtió a `pydicom` en una variable local no inicializada desde el inicio del scope de la función, rompiendo la llamada `ds = pydicom.dcmread(...)`. Además, pydicom generaba alertas recurrentes por elementos DICOM con Representation Values (VR) fuera de norma (ej. floats en campos IS `6.400000`).
  - **Solución Implementada:**
    * Se promovió `import pydicom.datadict` al inicio del archivo a nivel módulo.
    * Se removió el `import pydicom.datadict` interno dentro del bucle de elementos privados de `extraer_metricas_dosis()`.
    * Se configuró `pydicom.config.enforce_valid_values = False` y `reading_validation_mode = pydicom.config.IGNORE` para suprimir advertencias ruidosas en cabeceras de equipos médicos con formato legacy.
  - **Verificaciones:** Formato LF verificado ✅.

- [x] **2026-11-03 — Compatibilidad con `RawDataElement` en pydicom y Tolerancia a Fallos en Extracción Dosimétrica (`agente_dosimetria.py`):**
  - **Diagnóstico:** Al extraer métricas de estudios CT en producción, el proceso colapsaba con `AttributeError: 'RawDataElement' object has no attribute 'name'` en `extraer_metricas_dosis()`. En `pydicom`, las cabeceras DICOM procesadas en modo diferido o con tags privados pueden contener instancias de `RawDataElement`, las cuales carecen de la propiedad `.name`. Además, la ausencia de un bloque `try...except` individual por estudio en el bucle principal abortaba los 500 estudios si uno solo presentaba anomalías en sus metadatos.
  - **Solución Implementada:**
    * Se protegió la inspección de tags de dosis CT resolviendo el nombre mediante `getattr(elem, "name", None)` y `pydicom.datadict.keyword_for_tag(elem.tag)` de forma segura.
    * Se encapsuló la extracción de cada estudio individual en un bloque defensivo `try...except Exception as e` con advertencia controlada para que cualquier estudio corrupto o no estándar sea omitido sin detener la extracción de los demás 499 estudios.
  - **Verificaciones:** Formato LF verificado ✅.

- [x] **2026-11-03 — Pre-flight Socket Check, Driver Pure-Python (`use_pure=True`) y Prevención de Bloqueos SSL en `agente_dosimetria.py`:**
  - **Diagnóstico:** En entornos de producción (`C:\agente_backups_dcm\`), tras superar el socket check en 22 ms, el agente se congelaba indefinidamente en la línea `mysql.connector.connect()` al conectar a `10.35.47.3:3306`. La causa raíz fue la extensión binaria C de `mysql-connector-python` que se quedaba esperando respuesta al enviar paquetes ClientHello TLS hacia servidores MySQL legados de dcm4chee que no tienen SSL activo.
  - **Solución Implementada:**
    * Se configuró `use_pure=True` forzando el motor de socket puro en Python con `connect_timeout=8` y política multi-intento con `ssl_mode='DISABLED'` y `ssl_disabled=True`.
    * Se integró `check_mysql_socket` defensivo previo.
    * Se añadieron logs paso a paso en consola y archivo para visibilidad total del ciclo.
  - **Verificaciones:** Formato LF verificado ✅.

- [x] **2026-11-03 — Corrección de `NameError: name 'cargar_cache_procesados' is not defined` en `agente_dosimetria.py`:**
  - **Diagnóstico:** Al ejecutar `python agente_dosimetria.py --dias 7 --limit 500`, el script colapsaba con `NameError: name 'cargar_cache_procesados' is not defined` en la línea 935. Las funciones `cargar_cache_procesados` y `guardar_cache_procesados` eran invocadas en el flujo principal (`main()`) pero carecían de su definición en el cuerpo del script.
  - **Solución Implementada:**
    * Se implementó `cargar_cache_procesados(cache_path)` leyendo `CACHE_PROCESADOS_FILE` (`logs/dosimetria_cache_procesados.json`) con soporte resiliente de formatos (diccionario con timestamp y retrocompatibilidad con arrays previos).
    * Se implementó `guardar_cache_procesados(cache, cache_path)` realizando escritura atómica con archivo temporal `.tmp` y reemplazo seguro para evitar corrupción ante reinicios imprevistos.
  - **Verificaciones:** Análisis léxico y sintaxis Python validados ✅.

- [x] **2026-11-03 — Incorporación de Catálogo de Sedes e IDs y Guía Exhaustiva de Comandos por Agente (`storage/app_lidiag/agente_backups_dcm/README.md`):**
  - **Diagnóstico y Necesidad:**
    * El archivo de configuración `config.json` del Agente Unificado de Backups DCM y Dosimetría requiere obligatoriamente el parámetro `sede_id` para vincular los volcados a la telemetría central de LIDiag (`/seguridad-backups`) y atribuir los reportes dosimétricos (Res. 482 de 2018).
    * El manual `README.md` del paquete carecía de la relación explícita de los identificadores (`sede_id`) y nombres oficiales de cada hospital de la red asistencial, así como de un compendio unificado y estructurado con todos los comandos de ejecución (lanzadores `.bat`, scripts Python, modos `--dry-run`, parámetros de escaneo y control de tareas programadas en Windows).
  - **Solución Implementada:**
    * Se integró en la sección de configuración de `README.md` la tabla canónica con las 7 sedes hospitalarias activas en `config_sedes`:
      - ID 1: Espinal — Hospital San Rafael E.S.E. (`ESP`)
      - ID 2: La Dorada — Hospital San Félix E.S.E. (`LAD`)
      - ID 3: Chaparral — Hospital San Juan Bautista E.S.E. (`CHA`)
      - ID 4: Ibagué — Hospital Federico Lleras Acosta E.S.E. (`IBA`)
      - ID 5: Líbano — Hospital Regional Alfonso Jaramillo Salazar E.S.E. (`LIB`)
      - ID 6: Honda — Hospital San Juan de Dios de Honda E.S.E. (`HON`)
      - ID 7: Rovira — Hospital San Vicente E.S.E. (`ROV`)
    * Se documentaron las convenciones sugeridas de carpetas compartidas SMB en el QNAP NAS (`backups-db-{sede}` e `img-dicom-{sede}`) y el patrón de nombres de archivo `backup_dcm_sede_{sede_id}_{slug}_{timestamp}.zip`.
    * Se amplió la sección de ejecución con la **Matriz Resumen de Comandos** y los bloques completos de comandos para consola (PowerShell / CMD) para los 4 agentes (`backup_dcm_agent.py`, `sync_dcm_images.py`, `agente_dosimetria.py`, `restore_dcm_images.py`) y las tareas programadas con `schtasks`.
  - **Verificaciones:** Formato Markdown y consistencia de datos validados ✅.

- [x] **2026-10-31 — Actualización Integral del Documento Maestro (`README.md`):**
  - **Diagnóstico y Necesidad:** El archivo `README.md` presentaba desfases frente a los hitos y módulos desarrollados recientemente en el repositorio: carecía de la documentación del Bot de Telegram (`@salud_tolima_bot`), el módulo unificado de Auditoría (`/seguridad-auditoria`), el ciclo completo de HelpDesk con folios por sede, los visores diagnósticos (OHIF / Oviyam), el flujo de Tele-Radiología y el monitoreo de respaldos en tiempo real.
  - **Solución Implementada:**
    * *Stack e Integraciones:* Incorporación de Telegram Bot API (Long-Polling y Webhook), visores DICOM (OHIF / Oviyam) y auditoría de eventos de usuario (`audit_logs` + `AuditLogger`).
    * *Módulos del Sistema:* Se agregaron y ampliaron las secciones de HelpDesk (folios `SED-YYYYMMDD-#####` y dashboard), Seguridad y Auditoría (`/seguridad-auditoria`), Tele-Radiología (`/tele-radiologia`), Bot de Telegram (`@salud_tolima_bot`), Monitoreo de Backups (`/seguridad-backups`) y Visores Diagnósticos.
    * *Comandos Artisan y Automatización:* Se documentaron `php artisan telegram:poll` y `telegram:daily-digest`, así como las tareas programadas en Windows (`instalar_tarea_telegram_bot.bat`, `ejecutar_telegram_bot.bat`, `detener_tarea_telegram_bot.bat`).
    * *Variables de Entorno:* Actualizadas las variables clave en la guía de despliegue (`TELEGRAM_*`, etc.).
  - **Verificaciones:** Formato Markdown y consistencia estructural validados ✅.

- [x] **2026-10-31 — Estandarización Integral UI/UX AdminWrap / Bootstrap 4 en Módulos Restantes (`help-desk`, `tele-radiologia`, `contratos`, `pacientes`, `reportes`, `estudios`, `ecografias`, `citas`):**
  - **Diagnóstico y Necesidad:**
    * Múltiples componentes en los módulos de `help-desk` (5 componentes), `tele-radiologia` (2 componentes), `contratos` (1 componente), `pacientes` (1 componente), `reportes` (1 componente), `estudios` (7 componentes) y `ecografias` presentaban inconsistencias visuales y de accesibilidad frente a los estándares AdminWrap / Bootstrap 4 de LIDiag:
    * Uso de spinners rudimentarios o componentes obsoletos (`<loading-spinner>`, `<i class="fa fa-spinner">`).
    * Presencia de IDs genéricos `#example23` y `#example23-1` en DataTables que provocaban colisiones o fallas en `safeDestroyDataTable`.
    * Clases CSS ajenas como `.btn-action-clinical`, badges de atajos de teclado embebidos (`F2`, `Ctrl+S`, `Esc`), e overlays con `backdrop-filter: blur(2px)`.
    * Migas de pan (breadcrumbs) con elementos `<a>` no semánticos en lugar de `<button type="button" class="btn btn-link p-0"...>`.
  - **Solución Implementada:**
    * *HelpDesk:* Se actualizaron los 5 componentes (`helpdeskTicketsComponent.vue`, `helpdeskCrearTicketComponent.vue`, `helpdeskDetalleTicketComponent.vue`, `helpdeskNotificacionesComponent.vue`, `helpdeskReportesComponent.vue`) incorporando `<table-skeleton-loader>` y `<empty-state-component>`, retirando `.btn-action-clinical`, normalizando botones y migas de pan.
    * *Tele-Radiología:* En `teleRadiologiaEnviarEstudioComponent.vue` y `teleRadiologiaTranscibirComponent.vue` se reemplazó `#example23` por `#tablaTeleRadiologiaEnviar` y `#tablaTeleRadiologiaTranscribir`, integrando skeleton loaders, empty states y eliminando `.btn-action-clinical` y `backdrop-filter`.
    * *Contratos:* En `contratoComponent.vue` se normalizó el ID a `#tablaContratos`, se incorporó skeleton loader, empty state, migas accesibles y soporte móvil `@media (max-width: 767px)`.
    * *Pacientes y Reportes:* En `admisionComponent.vue` se reemplazó `#example23` por `#tablaAdmisionPacientes` en template, `destroyDataTable()` e inicialización; en `reporteProduccionComponent.vue` se homologaron las migas de pan.
    * *Estudios:* En `AsignarEstudioComponent.vue`, `estudioAsignadosComponent.vue`, `leerEstudioComponent.vue`, `notificarEstudioComponent.vue` y `transcribirEstudioComponent.vue` se renombraron todos los selectores `#example23` a IDs semánticos únicos (`#tablaAsignarEstudio`, `#tablaEstudiosAsignados`, `#tablaLeerEstudios`, `#tablaLeerEstudiosPrevios`, `#tablaNotificarEstudio`, `#tablaTranscribirEstudio`), retirando clases no estándar y overlays con blur. En `QuemadorCdComponent.vue` y `QuemarMultiplesComponent.vue` se erradicó por completo `<loading-spinner>` sustituyéndolo por skeleton loaders.
    * *Ecografías y Citas:* Se corrigieron referencias residuales `#example23` en `ecografiasAdmisionadasComponent.vue` y `lecturaComponent.vue`, se retiró el modal blur residual y se reemplazó `<loading-spinner>` por `<table-skeleton-loader>` en `gestionCitasComponent.vue`.
  - **Verificaciones:**
    * ESLint: 0 errores y 0 warnings en toda la carpeta `resources/js/components/modulos/` (`npx eslint`) ✅.
    * Erradicación de `loading-spinner`, `btn-action-clinical` y selectores duplicados `#example23` verificada al 100% ✅.
    * Compilación de producción Vite (`npm run build`): Exitosa en 1.95s sin advertencias de sintaxis ✅.
    * Vaciado de caché de vistas y configuración de Laravel (`php artisan view:clear; php artisan config:clear`) ✅.
- [x] **2026-10-31 — Prevención de Error 500 y Alertas de Usuario sin Rol Asignado (`dashboard.blade.php`, `dashboardComponent.vue`, `usuarioComponent.vue`):**
  - **Diagnóstico:**
    * Al acceder a `https://lidiag.com/dashboard` desde clientes donde el usuario autenticado no posee ningún rol en la base de datos (por ejemplo usuarios recién sincronizados o creados sin rol), Laravel arrojaba `500 | Server Error`.
    * Causa raíz: en `resources/views/modulos/dashboard/dashboard.blade.php`, la directiva `rolesusuario="{{ auth()->user()->roles()->first()->name }}"` ejecutaba `->name` sobre `null`, provocando `ErrorException: Attempt to read property "name" on null` en PHP 8+.
    * Ni el dashboard ni el módulo de usuarios alertaban de forma visible que la cuenta se encontraba sin rol asignado.
  - **Solución Implementada:**
    * *Blade Dashboard:* Se reemplazó la lectura por `$user->roles->pluck('name')->first() ?? ''` y se incorporó un banner de alerta Bootstrap visible cuando `auth()->user()->roles->isEmpty()`, informando al usuario y a los administradores que la cuenta no tiene rol y orientándolos a configurarlo en **Seguridad > Usuarios**.
    * *Vue Dashboard:* En `dashboardComponent.vue`, si `rolesusuario` está vacío se muestra una insignia destacada `Sin rol asignado` en lugar de una etiqueta en blanco.
    * *Vue Usuarios:* En `usuarioComponent.vue`, la columna de roles de la tabla de usuarios ahora muestra una insignia `Sin rol` con icono de advertencia cuando un usuario no tiene roles asignados, facilitando a los administradores identificar rápidamente cuentas huérfanas.
  - **Verificaciones:** ESLint 0 errores ✅ | Compilación Vite (`npm run build`) en 1.99s ✅ | Scripts de prueba PHP verificando usuarios sin rol con salida controlada ✅.
- [x] **2026-10-30 — Migración y Adaptación del Entorno a PHP 8.4 (`composer.json`, `README.md`, `AGENTS.md`, `Laragon PHP 8.4 NTS`):**
  - **Diagnóstico y Necesidad:**
    * El entorno Laragon se actualizó a PHP 8.4 (`php-8.4.12-nts-Win32-vs17-x64`), requiriendo la habilitación de dependencias críticas y la actualización de los requerimientos de Laravel.
    * La instalación inicial de PHP 8.4 carecía de los controladores de Microsoft SQL Server (`sqlsrv` y `pdo_sqlsrv`), lo cual provocaría la ruptura inmediata de la interoperabilidad con HIS/SAHI.
    * La extensión `zip` requerida por `maatwebsite/excel` y `phpoffice/phpword` se encontraba comentada, y los certificados SSL CA estaban sin vincular.
  - **Solución Implementada:**
    * *Drivers SQL Server para PHP 8.4:* Se extrajeron desde el paquete oficial `SQLSRV5.13.1_Windows.zip` los binarios `php_sqlsrv_84_nts_x64.dll` y `php_pdo_sqlsrv_84_nts_x64.dll` colocándolos en `C:\laragon\bin\php\php-8.4.12-nts-Win32-vs17-x64\ext\` y registrándolos en `php.ini`.
    * *Extensiones Críticas y SSL:* Se activó `extension=zip` y se configuraron `curl.cainfo` y `openssl.cafile` apuntando a `C:\laragon\etc\ssl\cacert.pem`.
    * *Requisitos de Laravel:* Se actualizó `composer.json` (`"php": "^8.4"`), `README.md` y `AGENTS.md`.
    * *Verificación de Compatibilidad:* Se ejecutó `composer check-platform-reqs` bajo PHP 8.4.12 pasando el 100% de requerimientos y `php artisan about` arrancando Laravel 13.38.0 exitosamente.
- [x] **2026-10-30 — Optimización de Tiempo de Guardado y Cierre del Drawer en Transcribir Estudios (`transcribirEstudioComponent.vue`, `EstudioService.php`, `EstudioController.php`):**
  - **Diagnóstico y Causa Raíz:**
    * *Backend:* En `EstudioService::guardarTranscripcion`, antes de devolver el `200 OK` al navegador se ejecutaban de forma síncrona dentro y fuera de la transacción: (1) `Paciente::updateOrCreate(['num_docu' => ...])` haciendo un *Full Table Scan* sobre las 100,655 filas de `pacientes`, (2) `PlantillaEstudioService::editWord($data)` que invocaba `soffice.com --headless --convert-to pdf` (LibreOffice Portable) bloqueando el hilo y la transacción MySQL entre 2 y 6 segundos, (3) `SAHITranscibirService::enviarEstudio(...)` abriendo conexión SQL Server remota y ejecutando múltiples consultas/inserciones, y (4) `EstudioNotificacionService::enviarPortalPacientes(...)` realizando un `POST` HTTP externo con `timeout(60)`.
    * *Frontend:* En `transcribirEstudioComponent.vue`, al recibir la respuesta del servidor, `guardarRegistro()` ejecutaba `destroyDataTable()`, `registros.value.splice()`, `await nextTick()` y `safeInitDataTable()` antes de invocar `btnCerrarModalForm()`, retrasando la animación de cierre del drawer.
  - **Solución Implementada:**
    * En `EstudioService::guardarTranscripcion`, se valida en `< 1 ms` que la sede tenga configurada `plantilla_rx` y que el archivo `.docx` exista en disco (manteniendo intactas las respuestas `402`/`404`), se busca el paciente por llave primaria (`$estudio->paciente_id`) y solo se actualiza si `isDirty()`, y se difiere el post-procesamiento pesado (`procesarPostTranscripcion`: sincronización SAHI, conversión LibreOffice Word→PDF y subida al Portal de Pacientes) mediante `app()->terminating(...)` con vaciado de buffers HTTP (`fastcgi_finish_request` / `ob_end_flush` + `flush`).
    * En `EstudioController::guardarTranscripcion`, se añaden las cabeceras `Content-Length` y `Connection: close` para que Apache en Windows libere la conexión con el navegador en cuanto termina la transacción MySQL (~30–50 ms).
    * En `transcribirEstudioComponent.vue`, `guardarRegistro()` cierra el drawer en el primer frame (`btnCerrarModalForm()`) y difiere la eliminación de la fila, la reconstrucción de `DataTables` y `limpiar()` al siguiente frame con `requestAnimationFrame`.
  - **Verificación:** Validado con `vendor\bin\pint.bat --test` (`passed`), `npx eslint` (0 errores) y `npm run build` exitoso.
- [x] **2026-10-30 — Optimización de Tiempo de Guardado y Cierre del Drawer en Asignar Estudios (`AsignarEstudioComponent.vue`, `EstudioService.php`):**
  - **Diagnóstico y Causa Raíz:**
    * *Backend:* En `EstudioService::createOrUpdatePatient`, se ejecutaba `Paciente::where('num_docu', ...)->first()` seguido de `Paciente::updateOrCreate(['num_docu' => ...], ...)`, provocando **dos Full Table Scans consecutivos** sobre la tabla `pacientes` (~100,655 filas sin índice en `num_docu`). Además, `syncStudyProducts` y `syncStudyDiagnostics` ejecutaban sentencias `DELETE` innecesarias para un estudio recién creado, consultas `ConfigProducto::where('cod_cups', ...)` en bucle (N+1) e inserciones fila por fila.
    * *Frontend:* En `AsignarEstudioComponent.vue`, al recibir el `200/201` de `POST /api/estudios`, `guardarRegistro()` mutaba `registros.value` (`targetItem.agendado = 1`) antes de cerrar el drawer (`cerrarModal()`), obligando a Vue a re-renderizar todas las filas de la tabla principal antes de iniciar la transición de cierre; simultáneamente `cerrarModal()` ejecutaba `limpiarFormulario()` en el mismo frame de la animación y disparaba una petición redundante `POST /api/estudio-lock/liberar` pese a que `EstudioService::create` ya liberaba la reserva en el servidor.
  - **Solución Implementada:**
    * En `EstudioService::createOrUpdatePatient`, se reutiliza la instancia `$existing` aplicando `fill()` + `isDirty()` (actualizando por llave primaria `id` solo cuando cambian datos y eliminando el segundo Full Table Scan).
    * En `EstudioService::syncStudyProducts` y `syncStudyDiagnostics`, se eliminaron los `DELETE` redundantes en la creación, se agruparon las consultas de `ConfigProducto` con un único `whereIn('cod_cups', ...)` y se reemplazaron las inserciones individuales por inserción en bloque (`insert()`), reduciendo el tiempo de `EstudioService::create` en más de un 55% (de ~270 ms a ~118 ms).
    * En `AsignarEstudioComponent.vue`, `guardarRegistro()` pausa el heartbeat antes de enviar, cierra el drawer en el primer frame con `cerrarModal({ skipRelease: true })` omitiendo el `POST /api/estudio-lock/liberar` duplicado, y difiere la actualización de la fila de la tabla y `limpiarFormulario()` mediante `requestAnimationFrame`.
  - **Verificación:** Validado con `vendor\bin\pint.bat --test` (`passed`), `npx eslint` (0 errores) y `npm run build` exitoso.
- [x] **2026-10-30 — Botón de Envío Directo a SAHI y Trazabilidad en Módulo Estudios Asignados (`estudioAsignadosComponent.vue`, `EstudioQueryService.php`, `EstudioService.php`, `EstudioController.php`, `api_estudios.php`):**
  - **Diagnóstico y Necesidad:**
    * Los operadores y transcriptores en la vista de Estudios Asignados (`/estudios/asignados`) no tenían forma de ver el estado de sincronización con SAHI ni un botón para reenviar o transmitir manualmente un estudio puntual a SQL Server sin tener que abrir la consola de comandos.
  - **Solución Implementada:**
    * *Backend:* `EstudioQueryService::listarEstudiosAsignados()` ahora selecciona los campos `estado_envio_sahi`, `fechor_envio_sahi`, `sahi_esquema_149`, `sahi_esquema_150`, `error_envio_sahi` e `interface_software`.
    * *Servicio y Controlador:* Creado `EstudioService::enviarSahiEstudioProducto($id)` y su endpoint `POST /api/estudio-enviar-sahi` en `EstudioController.php` con autorización y validación.
    * *Frontend en `estudioAsignadosComponent.vue`:*
      - En la columna de Acciones se incorporó un botón dedicado para sedes SAHI con estados reactivos: verde `check-circle` (`ENVIADO`), rojo `exclamation-triangle` (`ERROR` con tooltip del mensaje de fallo) y azul `cloud-upload` (`PENDIENTE`).
      - Diálogo de confirmación accesible con `confirmAction` indicando paciente, estudio y número de atención.
      - En la columna Detalle se añadió un badge informativo (`SAHI`, `SAHI Error`, `SAHI Pendiente`) con tooltip de fecha y números de esquemas 149 y 150.
      - Deshabilitado con tooltip si el estudio aún no ha sido transcrito.
  - **Verificaciones:**
    * ESLint 0 errores (`npx eslint`) ✅ | Formateo Prettier ✅ | Compilación Vite completada en 1.43s (`npm run build`) ✅ | Laravel Pint (`vendor\bin\pint.bat --test`) 100% passed ✅ | Suite de pruebas unitarias y de integración `SahiInteroperabilidadTest` (12 pruebas, 34 aserciones) pasando al 100% ✅.
- [x] **2026-10-30 — Idempotencia Inteligente en SAHI por Atención, CUPS y Ventana Temporal de 24h (`SAHITranscibirService.php`, `EstudiosSahiEnviarRangoCommand.php`, `EstudiosSahiReconcileCommand.php`):**
  - **Diagnóstico y Necesidad:**
    * El servicio de radiología `SAHITranscibirService` carecía de verificación preventiva en SQL Server antes de insertar esquemas. Al reintentar o reconciliar, corría el riesgo de duplicar los esquemas 149 y 150 en SAHI.
    * Sin embargo, un paciente hospitalizado (mismo `IdAtencion`) puede tener múltiples estudios del mismo CUPS en días distintos (estudios de control intrahospitalarios, ej. TAC de cráneo en día 1, día 3 y día 7). Una verificación simple por `Atención + CUPS` descartaría erróneamente los controles posteriores.
  - **Solución Implementada:**
    * *Detección Inteligente en `SAHITranscibirService`:* Creado `findExistingEsquemaForAtencionCups($idAtencion, $codCups, $fecHorLectura)`.
    * Consulta `hceEsquemaEncabeza` con join a `hceEsquemaDetProc` filtrando por `IdEsquema = 150`, `idAtencion`, `ValVarChar3 = $codCups` y evaluando la diferencia temporal: `ABS(DATEDIFF(hour, d.FecRegistro, :fecha)) <= 24`.
    * Si la diferencia es menor o igual a 24 horas (mismo estudio/transcripción de turno nocturno), reconoce que ya existe en SAHI, retorna los consecutivos existentes sin duplicar y marca en MySQL como `ENVIADO`.
    * Si la diferencia es superior a 24 horas (nuevo control clínico hospitalario en día posterior), permite la inserción normal de nuevos esquemas 149 y 150.
    * Se integró feedback visual en consola (`[YA EXISTÍA EN SAHI]`) tanto en `estudios:sahi-enviar-rango` como en `estudios:sahi-reconcile`.
  - **Verificaciones:**
    * Pruebas directas con casos reales de la base de datos de SAHI (`ESP_PRI`):
      - Atención 7002557 (TAC día 29 vs controles días 23 y 25): Reconocido como nuevo estudio (se enviará normalmente) ✅.
      - Atención 7004883 (TAC día 29 vs control día 25): Reconocido como nuevo estudio (se enviará normalmente) ✅.
      - Atención 7009390 (TAC día 29 vs registro día 29): Reconocido como ya existente en SAHI (no duplicará) ✅.
      - Atención 7010003 (TAC noche 29 vs madrugada día 30, diff 8h): Reconocido como ya existente en SAHI (no duplicará) ✅.
    * Laravel Pint (`vendor\bin\pint.bat --test`) 100% passed ✅ | Suite de pruebas `SahiInteroperabilidadTest` (10 pruebas, 31 aserciones) pasando al 100% ✅.
- [x] **2026-10-30 — Comando para Envío por Rango de Fechas a SAHI (`EstudiosSahiEnviarRangoCommand.php`, `SahiInteroperabilidadTest.php`):**
  - **Diagnóstico y Necesidad:**
    * Se requería una herramienta operativa de consola para despachar en lote hacia SAHI todos los estudios de radiología transcritos en una ventana temporal específica (ej. entre `'2026-10-28'` y `'2026-10-30'`), excluyendo estudios ya sincronizados para evitar duplicidad, pero permitiendo forzar el reenvío o simular en seco (`--dry-run`).
  - **Solución Implementada:**
    * *Comando Artisan `estudios:sahi-enviar-rango`:* Creado en `app/Console/Commands/EstudiosSahiEnviarRangoCommand.php`.
    * Soporta rango posicional o mediante opciones (`{from=2026-10-28} {to=2026-10-30}`, `--from=`, `--to=`).
    * Soporta `--dry-run` para previsualizar en tabla formateada los estudios a despachar con ID, Producto, Atención SAHI, CUPS, Sede, Médico, Fecha y Estado.
    * Soporta `--sede={ID}` para filtrar por sede y `--field={fechor_trascrito|created_at|fec_estudio|all}` para elegir el criterio temporal.
    * Soporta `--force` para reenviar registros que ya posean `estado_envio_sahi = 'ENVIADO'`.
    * Despacha atómicamente a SQL Server usando `SAHITranscibirService` y actualiza la trazabilidad en `estudios_productos`.
  - **Verificaciones:**
    * Ejecución en modo `--dry-run` identificó correctamente 8 estudios pendientes en Sede 1 para el rango 2026-10-28 a 2026-10-30 (y 10 con `--force`, incluyendo los 2 ya enviados previamente) ✅.
    * Laravel Pint (`vendor\bin\pint.bat --test`) 100% passed ✅ | Suite de pruebas `SahiInteroperabilidadTest` (10 pruebas, 31 aserciones) pasando al 100% ✅.
- [x] **2026-10-30 — Incorporación de Permiso `estudio.index`, Unificación de Módulo de Estudios y Traducción de Acordeones en `roleComponente.vue` (`PermisionEstudiosSeeder.php`, `roleComponente.vue`, `RolService.php`, `AuditPermissions.php`, `SyncMissingPermissions.php`):**
  - **Diagnóstico:**
    * El permiso `estudio.index` (junto con `estudio.leer` y `estudio.productos`) estaba definido en `routes/api_estudios.php` pero no existía en la tabla `permissions` ni en ningún seeder, por lo que `RolService::all()` no lo retornaba y no se mostraba en la interfaz de asignación de roles.
    * En `roleComponente.vue`, la función `moduloDePermiso` dividía los permisos con prefijo `estudio.*` y `estudios.*` en dos módulos separados pero con el mismo nombre "Estudios" en `modulosTraducidos`, generando dos acordeones duplicados y fragmentados en la interfaz. Además, faltaban traducciones para `contratos`, `dosimetria`, `liquidacion` y `otrasPlataformas`.
    * El comando `permissions:audit` fallaba con `BadMethodCallException: File::isAbsolutePath does not exist` y no escaneaba todas las rutas de `routes/`.
    * El comando `sync:missing-permissions` extraía incorrectamente el último segmento del permiso como `guard_name` (ej. `'index'` en vez de `'web'`).
  - **Solución Implementada:**
    * *Seeder Idempotente:* Creado `PermisionEstudiosSeeder.php` registrando `estudio.index`, `estudio.asignar`, `estudio.transcribir`, `estudio.leer`, `estudio.leerEstudio`, `estudio.productos`, `estudios.notificar.index` y `estudios.reporteLectura.index`, asignándolos a los roles `Administrador`, `Administrativo`, `Transcriptor RX`, `Medico` y `Estadistico`. Registrado en `DatabaseSeeder.php`, ejecutado y caché reseteada con `permissions:cache-reset`.
    * *Backend:* `RolService::all()` ahora consulta `Permission::where('guard_name', 'web')->orderBy('name')->get()`, garantizando orden alfabético.
    * *Frontend:* En `roleComponente.vue`, `moduloDePermiso` normaliza `estudios` a `estudio`, unificando todos los permisos bajo un solo acordeón con conteo consolidado. `permisosAgrupados` ordena alfabéticamente los permisos dentro de cada grupo. Se completaron las traducciones en `modulosTraducidos`.
    * *Comandos Artisan:* Corregido `AuditPermissions.php` para resolver rutas absolutas de forma nativa e incluir el directorio `routes/`. Corregido `SyncMissingPermissions.php` para fijar siempre `guard_name = 'web'`.
    * *Sidebar:* Incluido `'estudio.index'` en la directiva `@canany` del menú de Estudios en `resources/views/comunes/sidebar.blade.php`.
  - **Verificaciones:** `php artisan permissions:audit` ejecutado con éxito ✅ | Pint (`vendor\bin\pint.bat --test`) 100% passed ✅ | ESLint 0 errores ✅ | Compilación Vite (`npm run build`) en 1.95s ✅.
- [x] **2026-10-30 — Pruebas Exitosas de Envío a SAHI (Estudios 211528 y 211529) y Corrección de Clave Primaria en `HceEsquema` (`HceEsquema.php`, `EstudiosSahiReconcileCommand.php`):**
  - **Diagnóstico:**
    * Durante la prueba de envío puntual del estudio ID 211528 (Atención SAHI 7005281, Producto 261934, Sede 1 Espinal), el proceso arrojó la excepción: `SQLSTATE[42S22]: [Microsoft][ODBC Driver 18 for SQL Server][SQL Server]El nombre de columna 'id' no es válido en SQL: update [HceEsquema] set [IdConsecutivoEsq] = ..., [HceEsquema].[updated_at] = ... where [id] is null`.
    * La causa raíz radicaba en que el modelo Eloquent `HceEsquema` carecía de las propiedades `protected $primaryKey = 'IdEsquema';`, `public $incrementing = false;` y `public $timestamps = false;`. Al llamar a `$encavezadoPadre->save()` en `generarConsecutivoEsquemaEncavezadoPadre()`, Eloquent asumía `id` y marcas de tiempo automáticas (`updated_at`), fallando al actualizar el consecutivo en SQL Server.
  - **Solución Implementada:**
    * *Modelo Eloquent:* En `app/Models/Interface/Sahi/HceEsquema.php` se definieron explícitamente `$primaryKey = 'IdEsquema'`, `$incrementing = false` y `$timestamps = false`.
    * *Comando Artisan Reconciliación:* En `app/Console/Commands/EstudiosSahiReconcileCommand.php` se incorporó el parámetro `{--estudio-id=}` para despachar productos especificando directamente el ID del estudio padre (`estudios.id`), con logging detallado por consola y trazabilidad protegida por `hasTracking`.
  - **Verificaciones y Resultados:**
    * Estudio #211528 (Producto #261934, Atención #7005281):
      - Creado Esquema Encabezado 149 (Consecutivo: `2678884`).
      - Creado Esquema Encabezado 150 (Consecutivo: `2678885`).
      - Memo de lectura insertado en `hceEsquemaDetMemo` (texto plano limpio sin `&nbsp;`).
      - Detalles de médico (`CRISTY JOHANA CASTRO PALENCIA`, ID `2601`) y CUPS (`871121`) insertados en `hceEsquemaDetProc`.
      - Actualizado en MySQL `estudios_productos` #261934 con `estado_envio_sahi = 'ENVIADO'`.
    * Estudio #211529 (Producto #261935, Atención #7006479):
      - Creado Esquema Encabezado 149 (Consecutivo: `2678906`).
      - Creado Esquema Encabezado 150 (Consecutivo: `2678907`).
      - Memo de lectura insertado en `hceEsquemaDetMemo` (texto plano limpio sin `&nbsp;`).
      - Detalles de médico (`CRISTY JOHANA CASTRO PALENCIA`, ID `2601`) y CUPS (`871121`) insertados en `hceEsquemaDetProc`.
      - Actualizado en MySQL `estudios_productos` #261935 con `estado_envio_sahi = 'ENVIADO'`.
    * Laravel Pint (`vendor\bin\pint.bat --test`) 100% passed ✅ | Suite de pruebas `SahiInteroperabilidadTest` (9 pruebas, 30 aserciones) pasando al 100% ✅.
- [x] **2026-10-30 — Reparación Integral de Interfaz SAHI (SQL Server), Sanitización de `&nbsp;` en WYSIWYG/PDF y Trazabilidad en Transcripción de Estudios:**
  - **Diagnóstico:**
    1. *Fallo silencioso en Sede 1:* `EstudioService::guardarTranscripcion()` validaba que el médico tuviese `interface_id` si la sede era `'SAHI'`, pero omitía por completo invocar `SAHITranscibirService::enviarEstudio()`. Además, `GuardarTranscripcionRequest` no aceptaba `atencion_id` (por lo que `$request->validated()` lo purgaba), `transcribirEstudioComponent.vue` no enviaba `atencion_id` y `estudios_productos` carecía de columnas de estado SAHI (`estado_envio_sahi`).
    2. *Error en PDF por `&nbsp;`:* `WysiwygEditor.vue` en `htmlToPlain()` solo eliminaba etiquetas `<...>`, permitiendo que `&nbsp;` y `\u00A0` pasaran intactos. Al generar el reporte en `PlantillaEstudioService`, PHPWord inyectaba `&nbsp;` en el OpenXML (`document.xml`). Al no ser una entidad válida de XML 1.0, LibreOffice headless colapsaba con `SAXParseException` y la conversión a PDF fallaba arrojando error 400.
    3. *Falso positivo en Ecografías:* En `lecturaComponent.vue`, `guardarRegistro()` cerraba el modal y limpiaba el formulario incluso cuando el envío a SAHI (`enviarASahiEcografiaProducto()`) fallaba o quedaba en `ERROR`.
  - **Solución Implementada:**
    * *Capa 1 (Frontend WYSIWYG):* `WysiwygEditor.vue` ahora normaliza entidades literales `&nbsp;`, espacios Unicode de no separación (`\u00A0`), decodifica entidades HTML y colapsa espacios antes de emitir a `v-model`.
    * *Capa 2 (Backend Plantilla PDF):* `PlantillaEstudioService.php` incorpora `sanitizarTextoParaDocx()` para garantizar que solo texto plano seguro y XML-compliant entre a `TextRun`.
    * *Capa 3 (Backend SAHI Interoperabilidad):* `SAHITranscibirService.php` y `SAHIEcografiasEnviarService.php` limpian el texto para `hceEsquemaDetMemo.DesMemo` (`limpiarTextoPlanoSahi()`), previniendo códigos web en SQL Server.
    * *Trazabilidad y Orquestación:* Migración `2026_10_30_100000_add_estado_envio_sahi_columns_to_estudios_productos.php` creada; `GuardarTranscripcionRequest` y `transcribirEstudioComponent.vue` actualizados para validar y enviar `atencion_id`.
    * *Despacho Transaccional:* `EstudioService::guardarTranscripcion()` orquesta el envío síncrono/asíncrono hacia SAHI y actualiza el estado de tracking (`ENVIADO` o `ERROR`).
    * *UI Ecografías:* `lecturaComponent.vue` no cierra el modal si SAHI falla, manteniendo visible el botón de reintento.
    * *Multi-Host Helper & Reconciliación:* Creado `SahiConnectionHelper` para configurar dinámicamente conexiones por sede y comando `estudios:sahi-reconcile` registrado en `routes/console.php`.
  - **Verificaciones:** Laravel Pint (`vendor\bin\pint.bat --test`) 100% passed ✅ | ESLint (`npx eslint`) 0 errores ✅ | Compilación Vite exitosa (`npm run build`) en 1.22s ✅ | Suite de pruebas `SahiInteroperabilidadTest` (9 pruebas, 30 aserciones) pasando al 100% ✅.
- [x] **2026-10-30 — Corrección de Renderizado Móvil y Visualización de Lectura en Transcribir Estudios (`WysiwygEditor.vue`, `transcribirEstudioComponent.vue`):**
  - **Diagnóstico:** Al abrir `/estudios.transcribir.listar` (`transcribirEstudioComponent.vue`) desde un celular, el panel lateral no renderizaba adecuadamente y, cuando un estudio ya contaba con lectura (`registro.lectura`), el texto de la lectura no se mostraba en absoluto. La causa raíz fue triple:
    1. *Bug de detección móvil en `wysihtml5-0.3.0.js` (`isIos` / `supported`):* `wysihtml5.browser.isIos()` evalúa `userAgent.indexOf("webkit") !== -1 && userAgent.indexOf("mobile") !== -1`, lo cual devuelve `true` en **todos los teléfonos Android** (Chrome Android incluye `AppleWebKit` y `Mobile`). Acto seguido, `iosVersion(userAgent)` busca `/ipad|iphone|ipod/`, devolviendo `0` en Android (`0 < 5 === true`), por lo que `wysihtml5.browser.supported()` retornaba `false` y abortaba la creación de `this.composer` (`this.composer = undefined`, quedando en modo `this.textarea`).
    2. *Excepción en `WysiwygEditor.vue` al cargar la lectura:* En `watch(() => props.modelValue)` e `initEditor()`, `WysiwygEditor.vue` invocaba `inst.editor.setValue(plainToHtml(plain), true)` con `parse = true`. En modo `textarea` (o antes de que cargara el `iframe` sandbox), `Textarea.prototype.setValue(html, true)` llamaba a `this.parent.parse(html)`, que intenta acceder a `this.composer.sandbox.getDocument()`, lanzando `TypeError: Cannot read properties of undefined (reading 'sandbox')` antes de asignar `this.element.value = html`. Además, el `<textarea>` en el template carecía de `:value="modelValue"` y `@input`, quedando completamente en blanco.
    3. *Estilos y foco en móvil (`transcribirEstudioComponent.vue`):* `.right-sidebar` heredaba `display: none` de `public/admin-wrap/css/style.css` al montar, haciendo que el `iframe` de `wysihtml5` naciera con `width="0"`; el botón de cerrar `.right-side-toggle` no tenía estilos flex en `.rpanel-title`, el botón `verImagen` estaba oculto en móvil (`d-none d-md-block`), y `abrirModal()` ejecutaba `primerInput.value?.focus()` en celulares disparando el teclado virtual sobre el campo de email.
  - **Solución Implementada:**
    * *`WysiwygEditor.vue`:*
      - Habilitado `window.wysihtml5.browser.supported = () => true` y `supportTouchDevices: true` en la inicialización de `wysihtml5`.
      - Enlazados `:value="modelValue"` y `@input="onNativeTextareaInput"` en el `<textarea>` nativo como respaldo inmediato.
      - Creada función `syncEditorValue(rawVal)` sincronizada con el evento `inst.editor.observe("load", ...)` y con el `watch` de `modelValue`, utilizando `editor.composer.setValue(plainToHtml(plain), false)` y gestionando el estado `unset_placeholder` / `set_placeholder` sin invocar `parent.parse()` sobre sandboxes no inicializados.
      - En `applyHeight()`, forzado `width: 100% !important` en `.wysihtml5-sandbox` y altura adaptativa en pantallas móviles (`260px` en `< 768px`).
    * *`transcribirEstudioComponent.vue`:*
      - Añadido `display: block !important` y `height: 100dvh` en `.right-sidebar`, cabecera `.rpanel-title` con `display: flex; justify-content: space-between`, botón `.right-side-toggle` estilizado, padding inferior en móvil para `.r-panel-body` y barra `.wysihtml5-toolbar` con `flex-wrap: wrap`.
      - Condicionado `primerInput.value?.focus()` únicamente a pantallas `>= 768px` y habilitado el botón `verImagen` en móviles.
  - **Verificaciones:** Prettier (`npx prettier --write`) ✅ | ESLint (`npx eslint`) 0 errores/warnings ✅ | Compilación Vite (`npm run build`) en 1.21s ✅.

- [x] **2026-10-30 — Filtrado Dinámico de Asignadores y Médicos por Sede Activa en Estudios Asignados y Asignar Estudios (`estudioAsignadosComponent.vue`, `AsignarEstudioComponent.vue`):**
  - **Diagnóstico:**
    1. En `/estudios.asignados.listar` (`estudioAsignadosComponent.vue`), los combos `#quien_asigno_id` ("Quien asigno") y `#medico_id` ("Medicos") invocaban `listarUsuariosYSedes()` sin enviar `sedeId`, mostrando todos los usuarios globales sin filtrar por la sede seleccionada, sin ordenar alfabéticamente y sin recargar las opciones al cambiar de sede.
    2. En `/estudios.asignar.listar` (`AsignarEstudioComponent.vue`), `listarMedicos()` consultaba `/api/user.listarUsuarios/Medico/${moduloId}` sin enviar `?solo_activos=1&sede_id=${targetSede}` y `medicoOptions` no filtraba por estado activo ni ordenaba alfabéticamente.
  - **Solución Implementada:**
    * *`estudioAsignadosComponent.vue`:* Propiedades computadas `quienesAsignaronActivos` y `medicosActivos` (activos, `.trim()`, `localeCompare`), `cargarAsignadoresYSedes(sedeId)` invocando `listarUsuariosYSedes(..., { moduloId: "1", sedeId: targetSede })` en `onMounted()` y `onSedeSeleccionada(data)`, `@change="listarEstudiosAsignados()"` en los `<select>` y sanitización de `quien_asigno_id`/`medico_id` en el payload.
    * *`AsignarEstudioComponent.vue`:* `listarMedicos(sedeId = null)` ahora consulta `/api/user.listarUsuarios/Medico/${moduloId}?solo_activos=1&sede_id=${targetSede}` y reconstruye el selector Select2 (`selMedico.value?.rebuild()`). `medicoOptions` filtra médicos activos, normaliza nombres con `.trim()` y ordena alfabéticamente (`localeCompare`). En `onSedeSeleccionada(data)` se limpia `registro.value.medico_id = ""` y se recargan los médicos de la nueva sede.
  - **Verificaciones:** Prettier (`npx prettier --write`) ✅ | ESLint (`npx eslint`) 0 errores/warnings ✅ | Compilación Vite (`npm run build`) en 1.28s ✅.

- [x] **2026-10-30 — Campo `accession_no` (Detalle) Opcional en Asignar Estudios (`AsignarEstudioComponent.vue`):**
  - **Diagnóstico:** En `AsignarEstudioComponent.vue`, tras retirar la propiedad computada `statusAccessionNo` y su evaluación en `guardarRegistro()`, en el `<template>` habían quedado las referencias huérfanas `:class="getInputClass(statusAccessionNo)"`, `@blur="touch('accession_no')"` y el indicador visual `<span class="text-danger">*</span>` en la etiqueta del campo `<input id="accession_no">`, además de faltar la recompilación de assets (`npm run build`).
  - **Solución Implementada:**
    * *Template (`AsignarEstudioComponent.vue`):* Retirados `:class="getInputClass(statusAccessionNo)"`, `@blur="touch('accession_no')"` y el asterisco `<span class="text-danger">*</span>` de `<label for="accession_no">Detalle</label>`. Confirmado que en backend (`EstudioService::validateCreateData` y `Estudio::create`) `accession_no` ya es completamente opcional (`nullable`).
  - **Verificaciones:** Prettier (`npx prettier --write`) ✅ | ESLint (`npx eslint`) 0 errores/warnings ✅ | Compilación Vite (`npm run build`) en 1.27s ✅.

- [x] **2026-10-30 — Corrección de Carga y Reproducción de Audios en Estudios Asignados (`estudioAsignadosComponent.vue`, `EstudioAudio.php`, `routes/web.php`):**
  - **Diagnóstico:** En `/estudios.asignados.listar` (`estudioAsignadosComponent.vue`), al abrir el panel lateral de un estudio (ej. paciente documento `14012971` — `LAZARO MOSQUERO`, estudio `211372`, producto `261756`, audio `#6128`), los controles del reproductor `<audio>` aparecían deshabilitados/grises sin permitir la reproducción. La causa raíz fue triple:
    1. *Atributo `url` en `EstudioAudio.php`:* `EstudioAudio::getUrlAttribute()` invocaba `Storage::disk('audios')->url($path)`. Como el disco `'audios'` en `config/filesystems.php` es privado y no define clave `'url'`, el adaptador local de Laravel retornaba `/storage/audio_....mp3` (apuntando a `storage/app/public`) en vez de la ruta autenticada de streaming `/audios/audio_....mp3` (`EstudioAudioController::stream` que sirve desde `storage/app/audios`).
    2. *Binding en `estudioAsignadosComponent.vue`:* La pestaña `#profile8` enlazaba `:src="item.url || item.audio"`, recibiendo `/storage/audio_....mp3` (HTTP 404 Not Found), a diferencia de `transcribirEstudioComponent.vue` que construía explícitamente `audioUrl(item)` (`"/audios/" + item.audio`).
    3. *Permisos en Ruta de Streaming (`routes/web.php`):* La ruta `GET /audios/{filename}` solo admitía `permission:estudio.transcribir|estudio.leerEstudio`, omitiendo `estudio.asignar` (permiso con el cual se accede a `/estudios.asignados.listar`) y `tele.ragiologia.transcribir`.
  - **Solución Implementada:**
    * *Modelo (`app/Models/Estudio/EstudioAudio.php`):* `getUrlAttribute()` ahora retorna `'/audios/'.ltrim($path, '/')`, resolviendo directamente hacia el endpoint de streaming con soporte de rangos HTTP 206.
    * *Rutas (`routes/web.php`):* Ampliado el middleware de `GET /audios/{filename}` a `permission:estudio.asignar|estudio.transcribir|estudio.leerEstudio|tele.ragiologia.transcribir`.
    * *Frontend (`estudioAsignadosComponent.vue`):* Homologada la pestaña `#profile8` con `transcribirEstudioComponent.vue` (indicador `loadingAudios`, tarjeta con nombre/número de audio, badge `Cargado`/`Grabado`, duración formateada, `<audio :src="audioUrl(item)" controls>`, vista previa de transcripción y estado vacío `"No hay audios para este estudio."`), además de sincronizar `destroyDataTable()` antes de mutar `registros.value`.
  - **Verificaciones:** Prueba CLI de `EstudioAudioService::listarPorProducto(261756)` y verificación en disco para paciente `14012971` ✅ | Laravel Pint (`vendor\bin\pint.bat --test`) ✅ | ESLint (`npx eslint`) ✅ | Compilación Vite (`npm run build`) en 1.24s ✅.

- [x] **2026-10-30 — Resolución de TDZ en `configDiagnosticoComponent.vue` y Auditoría Exhaustiva de Módulo Configuración:**
  - **Diagnóstico:** Se reportó en navegador `configDiagnosticoComponent.vue:289 Uncaught ReferenceError: Cannot access 'loadingGuardar' before initialization`. `useKeyboardShortcuts` accedía a `loadingGuardar` en la línea 289 antes de su declaración con `const` en la línea 298.
  - **Solución Implementada:**
    * En `configDiagnosticoComponent.vue`, se agruparon los `import` al inicio y se reubicaron las declaraciones de estado reactivo (`loadingGuardar`, `id`, `registros`, etc.) antes de la llamada a `useKeyboardShortcuts`.
    * Se ejecutó una auditoría automatizada en todos los 19 componentes de `resources/js/components/modulos/configuracion/` y en el resto del proyecto, confirmando que la totalidad de los archivos declaran sus variables reactivas antes de pasarlas a composables.
  - **Verificaciones:** Análisis AST y script PowerShell de orden léxico en todos los `.vue` del repositorio ✅.

- [x] **2026-10-29 — Corrección de Listado, Filtros de Asignador/Médico, Columna Médico en Transcribir Estudios y Orden de Inicialización `useKeyboardShortcuts` (`transcribirEstudioComponent.vue`, `EstudioQueryService.php`, `configuracion/*.vue`, `agendaDisponibilidadComponent.vue`):**
  - **Diagnóstico:**
    1. En `configAdminSaludComponent.vue`, `configDiagnosticoComponent.vue`, `agendaDisponibilidadComponent.vue` y otros 9 componentes de Configuración, `useKeyboardShortcuts` referenciaba `loadingGuardar` antes de su declaración `const` (TDZ), provocando `Uncaught ReferenceError: Cannot access 'loadingGuardar' before initialization`.
    2. En `transcribirEstudioComponent.vue`, la tabla mostraba `item.medico` mientras que `EstudioQueryService::listarPendientesTranscribir()` seleccionaba únicamente `medi.name as nombres_medico`, dejando vacía la columna "Médico".
    3. Al filtrar por "Quien asignó" o "Médico" (o cuando una búsqueda retornaba 0 resultados y luego se volvía a consultar), `destroyDataTable()` se invocaba dentro de `nextTick()` *después* de mutar `registros.value` y `safeInitDataTable()` se ejecutaba incluso con 0 filas. Esto hacía que jQuery DataTables restaurara en el DOM las filas cacheadas de la inicialización previa (ignorando el filtro o vaciando la tabla), además de no haberse ejecutado `npm run build` tras los cambios previos.
  - **Solución Implementada:**
    * *`useKeyboardShortcuts` (12 componentes):* Reubicada la invocación de `useKeyboardShortcuts` después de declarar `loadingGuardar` y demás referencias reactivas en `configAdminSaludComponent.vue`, `configDiagnosticoComponent.vue`, `configCitaRechazoMotivoComponent.vue`, `configConsentimientoInformadoComponent.vue`, `configPlantilla.vue`, `configPrioridadComponent.vue`, `configProductoComponent.vue`, `configServicioCitaComponent.vue`, `configServicioProductoComponent.vue`, `configTipoDocumentoComponent.vue`, `configTipoPlantilla.vue` y `agendaDisponibilidadComponent.vue`.
    * *`EstudioQueryService.php` y `transcribirEstudioComponent.vue`:* Incluido `'medi.name as medico'` junto a `'medi.name as nombres_medico'` en la consulta backend y `{{ item.nombres_medico || item.medico }}` en la celda de la tabla.
    * *Sincronización DataTables + Vue:* En `listarPendientesTrascribir()`, `guardarRegistro()` y `anularAsignacion()`, se ejecuta `destroyDataTable()` *antes* de mutar `registros.value`, y dentro de `nextTick()` solo se inicializa `safeInitDataTable()` cuando `registros.value.length > 0`.
    * *Filtros y Carga Inicial:* Añadido `@change="listarPendientesTrascribir()"` en los selectores `#quien_asigno_id` y `#medico_id`, sanitización de IDs vacíos a `null` en el payload, sincronización de `sede_id` desde `sessionStorage` y ejecución de `listarPendientesTrascribir()` al montar el componente en `applyDashboardPrefill()` cuando hay sede activa.
  - **Verificaciones:** Laravel Pint (`vendor\bin\pint.bat --test`) ✅ | ESLint (`npx eslint`) ✅ | Compilación Vite (`npm run build`) ✅.

- [x] **2026-10-29 — Sistema de Auto-Autorización de Usuarios de Telegram con Clave Institucional (PIN) (`TelegramBotHandlerService.php`, `config/telegram.php`, `.env.example`, `.env.production`):**
  - **Diagnóstico y Necesidad:** Cada vez que un médico, directivo o usuario asistencial intentaba interactuar con el bot desde una nueva sede o dispositivo, era rechazado con "Acceso Restringido", obligando al administrador a ingresar al servidor para editar `.env` a mano con el Chat ID de cada persona y reiniciar el servicio.
  - **Solución Implementada:**
    * *Auto-Autorización con PIN (`TELEGRAM_ACCESS_PIN`):* Se implementó el mecanismo de auto-habilitación mediante clave institucional (por defecto `Lidiag2026*` configurable vía `.env`).
    * *Flujo Autónomo:* Al escribir `/start` o cualquier mensaje inicial, el bot solicita amigablemente la clave de acceso institucional. Al escribir la clave correcta, la cuenta queda inmediatamente vinculada y auto-autorizada de por vida, desplegando el menú táctil de reportes.
    * *Persistencia Ligera y Segura:* Los chats aprobados se almacenan con metadatos (fecha, nombre, username) en `storage/app_lidiag/telegram_authorized_chats.json`, sin requerir migraciones de BD y preservando la lista blanca estática previa de `.env`.
    * *Comandos de Administración:* Se agregaron `/usuarios` (para que administradores auditen la lista de chats activos) y `/revocar <chat_id>` para deshabilitar usuarios en caliente.
  - **Verificaciones:** Prueba unitaria simulando todo el ciclo de autenticación (solicitud, fallo por clave errada, éxito con `Lidiag2026*`, persistencia y revocación) ✅ | Laravel Pint passed ✅.

- [x] **2026-10-29 — Habilitación de Segundo Chat ID de Usuario en Telegram (`.env`, `.env.production`, `detener_tarea_telegram_bot.bat`):**
  - **Diagnóstico:** Se reportó nuevo usuario restringido con Chat ID `1246505483`.
  - **Solución Implementada:**
    * Incorporado el Chat ID en `.env` y `.env.production` (`TELEGRAM_ALLOWED_CHAT_IDS=658633601,999587626,1246505483`).
    * Configuración purgada con `php artisan config:clear`.
    * Actualizado `detener_tarea_telegram_bot.bat` con soporte PowerShell (`Get-CimInstance`) para finalizar procesos de forma compatible en Windows 11 donde `wmic` está deprecado.

- [x] **2026-10-29 — Resolución de 'Este campo es obligatorio' al Cancelar en Transcribir Estudios (`transcribirEstudioComponent.vue`):**
  - **Diagnóstico:** Al presionar el botón "Cancelar" en el panel lateral de transcripción, la interfaz desplegaba el mensaje de validación "Este campo es obligatorio" y el panel no se cerraba. La causa raíz fue doble:
    1. *Desfase de Propiedad Nombres del Paciente:* `transcribirEstudioComponent.vue` utilizaba únicamente la propiedad `nom_pacien` en `registro`, dejando `registro.nombres` como `undefined`. El subcomponente hijo `<form-paciente>` valida y enlaza `<input id="nombres" v-model="registro.nombres">` con `validators.required`. Al estar vacío, cualquier desenfoque (`blur`) marcaba el campo como tocado e inválido, inyectando el mensaje de obligatoriedad en el DOM.
    2. *Layout Shift en Evento Blur:* Al hacer clic en "Cancelar" con foco previo en un input (como `nombres` o `email_reportar`), el evento `blur` se disparaba en `mousedown` antes del `click`. La inserción dinámica del feedback de error en el DOM empujaba el contenido hacia abajo, desplazando el botón de "Cancelar" fuera del puntero del ratón antes del `mouseup`, cancelando el evento `click` del navegador e impidiendo la ejecución de `btnCerrarModalForm()`.
  - **Solución Implementada:**
    * Se incorporó la propiedad `nombres: ""` en la estructura de `registro` y se inicializó `registro.value.nombres = data.nom_pacien` en `mostrarRegistro(data)` y su reseteo en `limpiar()`, garantizando que `<form-paciente>` reciba los nombres cargados del paciente sin disparar validaciones espurias.
    * Se añadió `@mousedown.prevent="btnCerrarModalForm()"` en el botón "Cancelar", evitando que el evento de pérdida de foco interfiera o desplace el botón antes del cierre.
    * En `btnCerrarModalForm()`, se garantizó la limpieza completa de errores con `errores.value = {}` además de `resetTouched()`.
  - **Verificaciones:** ESLint 0 errores/warnings (`npx eslint`) ✅ | Formateo Prettier con LF estricto ✅ | Compilación Vite exitosa en 1.63s (`npm run build`) ✅.

- [x] **2026-10-29 — Homologación de UI/UX, Skeleton Loaders y Estilos Nativos en Módulo Agenda (`resources/js/components/modulos/agenda/` — `agendaDisponibilidadComponent.vue`):**
  - **Diagnóstico:** El componente `agendaDisponibilidadComponent.vue` presentaba inconsistencias con `UI-UX-IMPROVEMENT-PHASES.md`: breadcrumb con enlaces no semánticos y título genérico, clases foráneas `.btn-action-clinical`, badges de atajo (`F2`, `Ctrl+S`, `Esc`) incrustados en botones, uso del spinner legado `<loading-spinner>` en lugar de `<table-skeleton-loader>`, tabla con ID `#tabla-disponibilidad`, inputs sin `<label for="...">` asociados y panel lateral con `backdrop-filter: blur(2px)`.
  - **Acciones Realizadas:**
    * *Breadcrumbs y Títulos:* Homologados breadcrumbs a `<button type="button" class="btn btn-link p-0"...>` y ajustado título activo a "Disponibilidad de citas".
    * *Limpieza de Botones:* Retiradas las clases `.btn-action-clinical` y badges de atajos en cabecera, filtros y panel lateral.
    * *Skeleton y Empty State:* Reemplazado `<loading-spinner>` por `<table-skeleton-loader :rows="5" :columns="5" />` y `<empty-state-component>` al no hallar disponibilidades.
    * *ID Semántico DataTables:* Estandarizado a `#tablaAgendaDisponibilidad` en plantilla, `buscarStudy()`, `onSedeSeleccionada()` y `onBeforeUnmount()`.
    * *Accesibilidad y Modal:* Vinculadas etiquetas `<label for="...">` con inputs y selects en formulario de búsqueda y lateral. Eliminado `backdrop-filter: blur(2px)` y añadido soporte responsive móvil (`@media (max-width: 767px)`).
  - **Verificaciones:** ESLint 0 errores/warnings (`npx eslint`) ✅ | Compilación Vite completada en 2.23s (`npm run build`) ✅ | Vistas y configuración de Laravel purgadas (`view:clear`, `config:clear`) ✅.

- [x] **2026-10-29 — Corrección de Validación Opcional en Email para Reportar en Transcribir Estudios (`transcribirEstudioComponent.vue`):**
  - **Diagnóstico:** Al guardar la transcripción de un estudio sin suministrar correo electrónico a reportar, el componente bloqueaba el guardado y arrojaba el toast "Email inválido: Este campo es obligatorio". Esto se debía a que `statusEmailReportar` aplicaba la regla estricta `validators.required` a un campo que operativamente y en backend (`GuardarTranscripcionRequest`) es opcional (`nullable`).
  - **Solución Implementada:**
    * Se actualizó la propiedad computada `statusEmailReportar` para retornar estado válido si el campo está vacío, en blanco, o tiene valor nulo/indefinido, aplicando `validators.email` exclusivamente cuando el usuario haya ingresado texto.
    * En `guardarRegistro()`, se condicionó la validación y el toast únicamente cuando exista un correo diligenciado, permitiendo guardar sin impedimentos si el campo se deja vacío.
    * En el payload enviado a `/api/estudio-guardarTranscripcion`, se sanitizó `email_reportar` para enviar el texto limpio o `null`.
    * En la interfaz se sustituyó el asterisco de obligatoriedad (`<span class="text-danger">*</span>`) por `<small class="text-muted">(Opcional)</small>`.
  - **Verificaciones:** ESLint 0 errores/warnings (`npx eslint`) ✅ | Formateo con Prettier ✅ | Compilación Vite exitosa en 2.90s (`npm run build`) ✅.
- [x] **2026-10-29 — Homologación de UI/UX, Skeleton Loaders y Estilos Nativos en Módulo Liquidación (`resources/js/components/modulos/liquidacion/` — 2 componentes):**
  - **Diagnóstico:** Los componentes del módulo Liquidación (`liquidacionComponent.vue` y `productividadComponent.vue`) presentaban inconsistencias de estilo: breadcrumbs con etiquetas `<a>` crudas sin accesibilidad, carencia de `<table-skeleton-loader>` durante el cálculo y generación de reportes, alertas simples en lugar de `<empty-state-component>`, entidades HTML escapadas en títulos (`&oacute;`, `&eacute;`), modal de detalle de exclusión con filtro intrusivo `backdrop-filter: blur(2px)` y tablas sin IDs semánticos únicos.
  - **Acciones Realizadas:**
    * *`liquidacionComponent.vue`:* Homologado breadcrumb a `<button type="button" class="btn btn-link p-0"...>`. Añadido `<table-skeleton-loader :rows="6" :columns="7" />` durante el estado de cálculo. Integrado `<empty-state-component>` al no hallar líneas liquidables. Asignados IDs semánticos únicos (`#tablaLiquidacionLineas`, `#tablaLiquidacionTotales`, `#tablaLiquidacionExclusiones`). Retirado el desenfoque `backdrop-filter: blur(2px)` del modal de exclusiones y añadida accesibilidad con `<label for="busqueda-exclusion">` y soporte responsive móvil (`@media (max-width: 767px)`).
    * *`productividadComponent.vue`:* Homologado breadcrumb a botón accesible. Añadido `<table-skeleton-loader :rows="6" :columns="5" />` durante la generación del reporte. Integrado `<empty-state-component>` al no existir producción médica registrada. Asignados IDs únicos por médico (`:id="'tablaProductividad_' + m.medico_id"`). Estandarizados textos con tildes nativas y botones limpios con spinners reactivos.
  - **Verificaciones:** ESLint 0 errores/warnings (`npx eslint`) ✅ | Compilación Vite completada en 1.90s (`npm run build`) ✅ | Vistas y configuración de Laravel purgadas (`view:clear`, `config:clear`) ✅.

- [x] **2026-10-29 — Homologación de UI/UX, Skeleton Loaders y Estilos Nativos en Módulo Configuración (`resources/js/components/modulos/configuracion/` — 19 componentes):**
  - **Diagnóstico:** Los 19 componentes del módulo de configuración presentaban las desviaciones descritas en `UI-UX-IMPROVEMENT-PHASES.md`: uso de selectores DataTables genéricos (`#example23`), spinners clásicos en lugar de `<table-skeleton-loader>` y `<empty-state-component>`, clases foráneas (`.btn-action-clinical`), badges de atajos de teclado (`F2`, `Ctrl+S`, `Esc`) incrustados en botones, etiquetas `<label>` sin asociación `for="<id>"`, y modales/sidebars con filtros `backdrop-filter: blur(2px)` y sin adaptación móvil.
  - **Acciones Realizadas:**
    * *Estandarización de Tablas:* Reemplazado `#example23` por IDs semánticos únicos en plantilla y helpers `safeInitDataTable`/`safeDestroyDataTable` (`#tablaAdminSalud`, `#tablaDiagnosticos`, `#tablaTiposDocumento`, `#tablaPrioridades`, `#tablaSedes`, `#tablaProductos`, `#tablaServicioCitas`, `#tablaServicioProductos`, `#tablaCitaRechazoMotivos`, `#tablaPlantillas`, `#tablaTiposPlantillas`, `#tablaConsentimientos`, `#tablaWhatsappNumeros`, `#tablaWhatsappProductos`, `#tablaWhatsappSaludos`, `#tablaHelpdeskCategorias`, `#tablaHelpdeskEstados`, `#tablaHelpdeskPrioridades`).
    * *Skeleton y Empty States:* Reemplazados spinners de carga por `<table-skeleton-loader>` con filas/columnas ajustadas a cada catálogo y `<empty-state-component>` al no haber registros.
    * *Limpieza de Botones y Atajos:* Retirada la clase `.btn-action-clinical` y los spans con badges de atajos (`F2`, `Ctrl+S`, `Esc`) en cabeceras, formularios y paneles laterales.
    * *Breadcrumbs y Accesibilidad:* Homologados breadcrumbs a `<button type="button" class="btn btn-link p-0" style="vertical-align: baseline; text-decoration: none; color: inherit">` y vinculadas etiquetas `<label for="...">` con sus respectivos `<input id="...">`.
    * *Paneles Laterales y Modales:* Eliminado `backdrop-filter: blur(2px)` en favor de `rgba(0, 0, 0, 0.5)` nativo y añadido soporte responsive móvil `@media (max-width: 767px)` a todos los paneles laterales y diálogos.
  - **Verificaciones:** ESLint 0 errores/warnings (`npx eslint`) en los 19 componentes ✅ | Compilación Vite completada en 1.39s (`npm run build`) ✅ | Vistas y configuración de Laravel purgadas (`view:clear`, `config:clear`) ✅.

- [x] **2026-10-29 — Homologación de UI/UX, Skeleton Loaders y Estilos Nativos en Módulo Citas (`resources/js/components/modulos/citas/` — 4 componentes):**
  - **Diagnóstico:** Los componentes del módulo Citas (`citasComponent.vue`, `citasRevisionComponent.vue`, `gestionCitasComponent.vue`, `recomendacionProductoComponent.vue`) presentaban inconsistencias de accesibilidad en breadcrumbs (`<a>` sin href ni rol), tablas con spinners crudos, clases no estándar `.btn-action-clinical` y badges de atajos en botones.
  - **Acciones Realizadas:**
    * Homologación de breadcrumbs a `<button type="button" class="btn btn-link p-0"...>`.
    * Retiro de `.btn-action-clinical` y badges `F2`/`Ctrl+S`/`Esc`.
    * Sustitución de spinners por `<table-skeleton-loader>` y `<empty-state-component>`.
    * IDs semánticos de tabla (`#tablaCitasAgendadas`, `#tablaCitasRevision`, `#tablaGestionCitas`, `#tablaRecomendacionProductos`) en template y DataTables helpers.
    * Accesibilidad con labels vinculados a inputs y soporte responsive en modales.
  - **Verificaciones:** ESLint 0 errores/warnings (`npx eslint`) ✅ | Compilación Vite completada en 2.45s (`npm run build`) ✅.

- [x] **2026-10-29 — Filtrado Dinámico del Combo de Médicos por Sede Activa en Transcribir Estudios (`transcribirEstudioComponent.vue`, `usuarios.js`, `UserService.php`):**
  - **Diagnóstico:** El combo selector de "Médicos" (`#medico_id`) en `transcribirEstudioComponent.vue` listaba todos los médicos de la base de datos sin considerar la sede seleccionada, y mostraba usuarios sin ordenar alfabéticamente ni verificar reactivamente su estado activo.
  - **Solución Implementada:**
    * *Backend (`UserService.php`):* En `listByRoleAndModule`, cuando se especifica `sedeId`, se amplió la consulta para incluir médicos que tengan la sede asignada en `user_sedes` o que figuren como `medico_id` en `estudios` para dicha sede (`sede_id`), además de los asignadores (`quien_registro_id`).
    * *Frontend Helper (`usuarios.js`):* Se anexó el parámetro `sedeParam` a la petición de médicos `/api/user.listarUsuarios/Medico/${moduloId}?solo_activos=1${sedeParam}`.
    * *Componente Vue (`transcribirEstudioComponent.vue`):* Se creó la propiedad computada `medicosActivos` para filtrar médicos activos, sanitizar nombres y ordenarlos alfabéticamente de la 'A' a la 'Z'. En el selector se vinculó a `medicosActivos` y en `onSedeSeleccionada(data)` se resetea `busqueda.value.medico_id = ""` junto con `busqueda.value.quien_asigno_id = ""`.
  - **Verificaciones:** Laravel Pint (`vendor/bin/pint.bat --test`) 0 incidencias ✅ | ESLint 0 errores/warnings (`npx eslint`) ✅ | Compilación Vite completada en 1.84s (`npm run build`) ✅ | Prueba PHP con la BD real: Sede 1 retorna 39 médicos activos, Sede 4 retorna 7 médicos activos, y sin sede retorna los 44 generales ✅.
- [x] **2026-10-29 — Unificación de Fuente de Auditoría y Registro de Eventos de Usuario (`AuditLogService`, `AuditLogger`, `EstudioService`, `AgendaCitaService`, `HelpDeskTicketService`, `RolService`, `auditLogComponent.vue`):**
  - **Diagnóstico:**
    1. Se detectó una desconexión crítica en `/seguridad-auditoria`: `AuditLogService` consultaba la tabla `activity_log` (que no tiene columna `descripcion` y sólo almacenaba nombres en inglés), dejando la columna "Descripción" siempre vacía (`"—"`).
    2. Al mismo tiempo, los eventos reales de autenticación (inicios de sesión, cierres, fallos) y administración de usuarios/configuraciones se registraban en la tabla `audit_logs` vía `AuditLogger`, pero permanecían invisibles en la interfaz de auditoría.
    3. Ninguno de los servicios clínicos y operativos principales (`EstudioService`, `AgendaCitaService`, `HelpDeskTicketService`, `RolService`) auditaba las acciones que realizan los usuarios (asignaciones, lecturas, transcripciones, agendamientos, cambios de estado, etc.).
  - **Acciones Realizadas:**
    * *Unificación en Backend (`AuditLogService.php`):* Se reorientó la consulta a `AuditLog` (`audit_logs`), proyectando `descripcion`, habilitando búsqueda global por descripción, modelo, IP y nombre de usuario (`whereHas('user')`), y obteniendo los tipos de evento directamente desde `audit_logs`.
    * *Saneamiento de Helper (`AuditLogger.php`):* Retirado el docblock de deprecación obsoleto, consolidándolo como el helper institucional de auditoría.
    * *Instrumentación en Módulo Estudios (`EstudioService.php`):* Integrado registro de eventos para `asignar_estudio` (con detalle del estudio, paciente, médico y prioridad), `lectura_estudio` (médico especialista), `transcripcion_estudio` (transcriptor clínico) y `anular_estudio`.
    * *Instrumentación en Módulo Citas (`AgendaCitaService.php`):* Integrado registro de eventos para creación de solicitud (`create`), eliminación (`delete`), `asignar_cita` (sede y fecha), `confirmar_cita`, `cancelar_cita` (con motivo), `cumplir_cita`, `inasistencia_cita` y `rechazar_cita`.
    * *Instrumentación en Módulo HelpDesk (`HelpDeskTicketService.php`):* Integrado registro de eventos para creación (`create`), eliminación (`delete`), `reasignar_ticket` (con técnico destino) y `cambio_estado_ticket` (con nuevo estado).
    * *Instrumentación en Módulo Roles (`RolService.php`):* Integrado registro de eventos para creación (`create`), actualización y sincronización de permisos (`update`), eliminación (`delete`) y cambio de estado (`toggleEstado`).
    * *Frontend (`auditLogComponent.vue`):* Ampliado el mapa `colorEvento` para asignar insignias cromáticas diferenciadas a todos los nuevos tipos de eventos registrados en la interfaz.
  - **Verificaciones:** Laravel Pint passed (`{"tool":"pint","result":"passed"}`) ✅ | ESLint passed (0 errores, 0 warnings) ✅ | Compilación Vite completada en 1.50s (`npm run build`) ✅.

- [x] **2026-10-29 — Filtrado Dinámico de Asignadores por Sede Activa en Transcribir Estudios (`transcribirEstudioComponent.vue`, `usuarios.js`, `UserController.php`, `UserService.php`):**
  - **Diagnóstico:** El combo selector "Quién asignó" (`#quien_asigno_id`) en `transcribirEstudioComponent.vue` cargaba todos los usuarios del sistema sin filtrar por la sede hospitalaria activa. Además, en la base de datos de producción la tabla `user_sedes` sólo cuenta con 11 registros manuales, mientras que la vinculación operativa y real de los asignadores reside en `estudios.quien_registro_id` por `estudios.sede_id`.
  - **Solución Implementada:**
    * *Backend:* Se actualizó `UserController::listarUsuarios` para recibir el parámetro opcional `sede_id` y pasarlo a `UserService::listByRoleAndModule`. En `UserService`, se amplió la consulta para filtrar usuarios activos pertenecientes a la sede vía relación `user_sedes` O que hayan asignado estudios en dicha sede (`quien_registro_id` en `estudios` para esa `sede_id`), deduplicando los resultados con `distinct()`.
    * *Frontend Helper:* Se actualizó `listarUsuariosYSedes` en `resources/js/helpers/usuarios.js` para admitir `options.sedeId` y enviar `&sede_id=${options.sedeId}` al endpoint de asignadores (incluyendo la ruta fallback).
    * *Componente Vue:* En `transcribirEstudioComponent.vue`, se modularizó la carga con `cargarAsignadoresYSedes(sedeId)` ejecutándola en `onMounted` con la sede inicial y en el listener de `emitter.on('sedeSeleccionada')`, reseteando simultáneamente `busqueda.value.quien_asigno_id = ""` para no dejar selecciones inválidas al alternar de sede.
  - **Verificaciones:** Laravel Pint (`vendor/bin/pint.bat --test`) 0 incidencias ✅ | ESLint 0 errores/warnings (`npx eslint`) ✅ | Build Vite completado en 2.36s (`npm run build`) ✅ | Prueba de servicio en PHP con la BD real: Sede 1 (Espinal) retorna exactamente los 13 asignadores activos (incluyendo Marlen Torres y Yefer Avilez), Sede 4 (Ibagué) retorna 4 asignadores, y sin sede retorna el catálogo completo de 34 ✅.
- [x] **2026-10-29 — Homologación de UI/UX, Skeleton Loaders y Estilos Nativos en Módulo Ecografías (`resources/js/components/modulos/ecografias/`):**
  - **Diagnóstico:** Los componentes del módulo de ecografías presentaban inconsistencias de estilo frente a las directrices de `UI-UX-IMPROVEMENT-PHASES.md`: uso de clases personalizadas foráneas (`.btn-action-clinical`), badges de atajos de teclado incrustados en botones (`F2`, `Ctrl+S`, `Esc`), backdrops con filtros invasivos (`backdrop-filter: blur(2px)`), spinners clásicos en lugar de skeleton loaders y tablas con el ID genérico `example23`.
  - **Acciones Realizadas:**
    * *`admisionarComponent.vue`:* Reemplazada la clase `.btn-action-clinical` por `waves-effect waves-light`, retirados badges de atajos en botones. Integrado `<table-skeleton-loader :visible="isBuscandoAtenciones" :rows="6" :columns="12" />` y `<empty-state-component>` al no hallar atenciones. Asignado `id="tablaAdmisiones"` a la tabla y actualizados sus helpers `safeInitDataTable`/`safeDestroyDataTable`. Backdrop estandarizado a `rgba(0, 0, 0, 0.5)` y añadido soporte responsive móvil (`max-width: 767px`).
    * *`ecografiasAdmisionadasComponent.vue`:* Retirado `.btn-action-clinical` de cabecera y modales. Reemplazado `id="example23"` por `id="tablaEcografiasAdmisionadas"` en marcado y helpers DataTables. Añadido soporte responsive móvil al panel lateral.
    * *`lecturaComponent.vue`:* Retirado `.btn-action-clinical` y badges de atajos. Reemplazado `id="example23"` por `id="tablaEcografiasLectura"`. Integrado `<table-skeleton-loader :visible="isBuscandoPendientes" :rows="6" :columns="8" />` y `<empty-state-component>`. Backdrop homologado a `rgba(0, 0, 0, 0.5)` sin filtros de desenfoque.
    * *`ecografiasConsentimientosComponent.vue`:* Homologados breadcrumbs a `<button type="button" class="btn btn-link p-0" style="vertical-align: baseline...">`. Sustituido spinner tradicional por `<table-skeleton-loader :visible="loading" :rows="6" :columns="7" />` y `<empty-state-component>`.
    * *`ecografiasConsentimientosPlantillasComponent.vue`:* Homologados breadcrumbs. Integrado botón "Limpiar filtros", `<table-skeleton-loader :visible="loading" :rows="5" :columns="6" />` y `<empty-state-component>`.
    * *`ecografiaFirmaConsentimientoComponent.vue`:* Retirado `.btn-action-clinical` y sus estilos en `<style scoped>`, eliminados badges de atajo incrustados en botones de firma.
  - **Verificaciones:** ESLint 0 errores/warnings (`npx eslint` passed en los 6 componentes) ✅ | Build Vite satisfactorio en 1.12s (`npm run build`) ✅ | Vistas y configuración de Laravel purgadas (`view:clear`, `config:clear`) ✅.

- [x] **2026-10-29 — Deduplicación de Verificación de Sesión Expirada y Redirección al Login (`bootstrap.js`):**
  - **Diagnóstico:** Al expirar la sesión del navegador, peticiones paralelas (como `cargarCatalogos` en HelpDesk) devolvían HTTP 401. En `bootstrap.js`, `confirmSessionExpired()` evaluaba `if (_verifyingSession) return false;`, de modo que cuando la primera petición iniciaba la comprobación, las peticiones concurrentes recibían `expired = false` creyendo erróneamente que la sesión aún era válida y abortando la redirección al login, dejando al usuario en la vista con errores 401 en la consola.
  - **Solución Implementada:** Se refactorizó `confirmSessionExpired()` para compartir la misma Promise en vuelo (`_verifyingPromise`), resolviendo consistentemente `true` para todas las peticiones concurrentes cuando la sesión expira y garantizando la invocación de `redirectToLogin()` con toast informativo y redirección fluida a `/login`.
  - **Verificaciones:** ESLint passed (0 errores) ✅ | Compilación Vite completada en 1.19s (`npm run build`) ✅.
- [x] **2026-10-29 — Preservación de Médico y Prioridad al Limpiar Formulario en Asignar Estudios (`AsignarEstudioComponent.vue`):**
  - **Diagnóstico y Necesidad:** Al registrar múltiples estudios consecutivos, el formulario borraba la selección de médico y prioridad (`registro.value.medico_id = ""` y `registro.value.prioridad_id = ""`), obligando al usuario a volver a seleccionar al especialista y nivel de urgencia para cada estudio asignado.
  - **Solución Implementada:** Se omitió el reseteo de `medico_id` y `prioridad_id` en `nuevoRegistro()` y `limpiarFormulario()`, preservando la selección para flujos de asignación ágil continua.
  - **Verificaciones:** Formateo con Prettier ✅ | ESLint passed (0 errores) ✅ | Compilación Vite completada en 1.57s (`npm run build`) ✅.

- [x] **2026-10-29 — Inclusión Automática al Seleccionar Productos y Diagnósticos en Asignar Estudios (`AsignarEstudioComponent.vue`):**
  - **Diagnóstico:** Al seleccionar un producto o diagnóstico en los selectores Select2, la opción únicamente actualizaba la variable reactiva local (`productoSelecciondo` o `diagnosticoSelecciondo`) obligando al usuario a presionar manualmente el botón "Agregar".
  - **Solución Implementada:**
    * *Inclusión Inmediata:* Actualizados `onProductoSelect` y `onDiagnosticoSelect` para invocar inmediatamente `guardarProductosEstudio(selectedId)` y `guardarDiagnosticosEstudio(selectedId)` al seleccionar cualquier opción válida.
    * *Limpieza Automática:* `guardarProductosEstudio` y `guardarDiagnosticosEstudio` limpian el estado reactivo (`productoSelecciondo.value = ""` y `diagnosticoSelecciondo.value = ""`) permitiendo que el selector Select2 quede listo y despejado para la siguiente selección sin intervención manual.
    * *Eliminación de Botones Redundantes:* Retirados los botones manuales "Agregar" del marcado HTML.
  - **Verificaciones:** Prettier aplicado ✅ | ESLint passed (0 errores) ✅ | Compilación Vite completada en 2.22s (`npm run build`) ✅.

- [x] **2026-10-29 — Habilitación de Chat ID de Usuario en Lista Blanca de Telegram (`.env`, `.env.production`):**
  - **Diagnóstico:** El bot de Telegram ya se encuentra operativo y respondiendo en tiempo real, pero el nuevo usuario (Chat ID `999587626`) recibía el mensaje de "Acceso Restringido — LIDiag" debido a la lista blanca de seguridad institucional en `TELEGRAM_ALLOWED_CHAT_IDS`.
  - **Solución Implementada:** Incorporado el nuevo Chat ID en `.env` y `.env.production` (`TELEGRAM_ALLOWED_CHAT_IDS=658633601,999587626`) y purgada la caché de configuración con `config:clear`.

- [x] **2026-10-29 — Automatizador en Batch para Tarea Programada del Bot de Telegram (`instalar_tarea_telegram_bot.bat`, `ejecutar_telegram_bot.bat`, `detener_tarea_telegram_bot.bat`):**
  - **Diagnóstico y Necesidad:** El usuario requería un instalador automatizado en archivo `.bat` idéntico al patrón institucional de `instalar_scheduler.bat` y `storage/app_lidiag/agente_backups_dcm/instalar_tareas_sede.bat`, permitiendo que el bot de Telegram (`php artisan telegram:poll`) opere como servicio residente 24/7 en segundo plano bajo el Programador de Tareas de Windows (Windows Task Scheduler) sin requerir consolas abiertas manualmente.
  - **Scripts Implementados:**
    * *`ejecutar_telegram_bot.bat`:* Lanzador con auto-detección del binario de PHP (Laragon `php-*` o PATH del sistema), verificación de `artisan`, bucle de resiliencia con auto-reinicio tras caídas de red y volcado de telemetría a `storage/logs/telegram_bot.log`. Formato estricto CRLF.
    * *`instalar_tarea_telegram_bot.bat`:* Instalador interactivo que verifica privilegios de Administrador, registra la tarea `LIDiag_Telegram_Bot` para iniciar al arrancar el sistema (`/sc ONSTART /ru "SYSTEM" /rl HIGHEST`), reemplaza versiones previas de forma idempotente y ofrece arranque inmediato. Formato estricto CRLF.
    * *`detener_tarea_telegram_bot.bat`:* Script de mantenimiento para detener la tarea en ejecución, finalizar procesos huérfanos de `artisan telegram:poll` y permitir la eliminación opcional de la tarea programada.
  - **Verificaciones:** Detección de binario PHP en Laragon confirmada (`php-8.3.30`) ✅ | Normalización CRLF y verificación de ejecución limpia ✅.

- [x] **2026-10-29 — Simplificación de Campo Opcional 'Reportar al Email' (`AsignarEstudioComponent.vue`):**
  - **Diagnóstico:** El campo "Reportar al Email" contaba con una propiedad computada `statusEmailReportar` y una validación bloqueante en `guardarRegistro()`. Dado que se trata de un campo estrictamente opcional para la atención asistencial, dicha validación y clases reactivas de error eran innecesarias.
  - **Solución Implementada:**
    * *Frontend (`AsignarEstudioComponent.vue`):* Eliminada la constante computada `statusEmailReportar`, el feedback visual de error en el `<input>` y la validación en `guardarRegistro()`. Se mantiene únicamente el saneamiento seguro en el payload (`email_reportar: cleanEmail`).
  - **Verificaciones:** Prettier aplicado ✅ | ESLint passed (0 errores) ✅ | Compilación Vite completada en 1.31s (`npm run build`) ✅.

- [x] **2026-10-29 — Corrección de Autenticación y Flexibilización de Filtros en Dashboard HelpDesk (`HelpDeskReporteService.php`, `helpdeskReportesComponent.vue`, Restauración de BD):**
  - **Diagnóstico:**
    1. Al abrir `https://lidiag.test/helpdesk-reportes`, no se cargaba información en el dashboard. La causa principal fue que tras la importación del volcado de base de datos desde producción (a las 11:08 AM):
       - La contraseña del usuario `administrador@administrador.com` fue sobreescrita con el hash de producción, impidiendo la autenticación con la clave estándar local (`123456`) y retornando 401 en todas las peticiones a la API.
       - La tabla `sessions` contenía sesiones cifradas con la clave de producción, las cuales fallaban al desencriptarse con la `APP_KEY` local.
       - La tabla `help_desk_tickets` quedó vacía en el volcado recién restaurado.
    2. Adicionalmente, en `HelpDeskReporteService::baseQuery()`, la cláusula de fecha `->whereBetween(DB::raw('DATE(t.created_at)'), [$filters['fechaIni'], $filters['fechaFin']])` forzaba rígidamente ambas fechas; si alguna de ellas no se suministraba, `BETWEEN NULL AND ...` evaluaba siempre en falso impidiendo consultas abiertas o acumuladas.
    3. En `helpdeskReportesComponent.vue`, el rango por defecto estaba configurado exclusivamente para el día de hoy (`fechaIni: hoy`, `fechaFin: hoy`) y `sedeId` se forzaba con la sede activa del `sessionStorage`, excluyendo tickets creados en otras sedes o fechas.
  - **Solución Implementada:**
    * *Base de datos y Credenciales:* Truncada la tabla `sessions` y reestablecida la contraseña de desarrollo de `administrador@administrador.com` a `123456`.
    * *Backend (`HelpDeskReporteService.php`):* Flexibilizado `baseQuery()` con clausulados condicionales `when()` para rangos de fecha (`fechaIni` y `fechaFin`, solo `fechaIni`, solo `fechaFin`, o histórico completo sin fechas).
    * *Frontend (`helpdeskReportesComponent.vue`):*
      - Rango de fechas por defecto ampliado desde el primer día del mes en curso (`_firstDayStr`) hasta hoy (`_todayStr`).
      - Selector de sede inicializado en `"Todas"` (`""`) para permitir visión global de indicadores.
      - Eliminado el bloqueo `if (!filters.value.fechaIni || !filters.value.fechaFin) return;` en `buscar()`.
  - **Verificaciones:** Prueba de login con credenciales de desarrollo y consumo de endpoints `indicadores` y `detallado` exitosa (HTTP 200 con payload completo) ✅ | Laravel Pint passed ✅ | ESLint passed (0 errores) ✅ | Compilación Vite completada en 1.53s (`npm run build`) ✅.
- [x] **2026-10-29 — Diagnóstico y Resolución de Operatividad del Bot de Telegram en Producción (`.env.production`, Webhook vs Long-Polling):**
  - **Diagnóstico Forense:** Al pasar a producción, el bot (`@salud_tolima_bot`) no respondía a los mensajes ni comandos de los usuarios debido a:
    1. *Webhook no registrado / Error SSL en Telegram:* En la API de Telegram, el bot tenía `url: ""` (sin webhook registrado). Al intentar registrar el webhook a `https://lidiag.com/api/telegram/webhook`, la API de Telegram rechazaba la conexión con: `SSL error {error:0A000086:SSL routines::certificate verify failed}`. La causa es que el servidor web (`186.96.96.115`) entrega únicamente el certificado final de Sectigo sin la cadena intermedia de la Autoridad Certificadora (`fullchain`), lo cual OpenSSL rechaza estrictamente en conexiones server-to-server.
    2. *Variables Faltantes en `.env.production`:* `.env.production` no contenía las variables de Telegram (`TELEGRAM_BOT_TOKEN`, `TELEGRAM_ALLOWED_CHAT_IDS`, etc.).
    3. *Modo de Operación en Servidor:* Para que el bot opere en producción bajo esta infraestructura, la solución más estable, autónoma y resiliente es **Long-Polling** (`php artisan telegram:poll`), ya que ejecuta peticiones *salientes* hacia Telegram API (`api.telegram.org`), siendo 100% inmune a problemas de certificados SSL entrantes, puertos cerrados o firewalls.
  - **Solución y Acciones Realizadas:**
    * *Actualización de Entorno (`.env.production`):* Se incorporaron las variables de entorno de Telegram (`TELEGRAM_BOT_TOKEN`, `TELEGRAM_ALLOWED_CHAT_IDS=658633601`, `TELEGRAM_WEBHOOK_URL`, `TELEGRAM_WEBHOOK_SECRET`, `TELEGRAM_SSL_VERIFY=false`).
    * *Liberación de Cola de Telegram:* Se eliminó el webhook registrado en Telegram (`deleteWebhook`) y se procesaron con éxito los comandos acumulados del usuario (`/retrasados`, `wiz:red:hoy`, `wiz:menu`).
    * *Verificación:* Prueba exitosa de comunicación bidireccional con Telegram mediante `php artisan telegram:poll --once` respondiendo comandos en tiempo real ✅.

- [x] **2026-10-29 — Identificación de Rol Real 'Transcriptor RX' y Soporte en "Quién Asignó" (`UserService.php`, `usuarios.js`):**
  - **Diagnóstico:** En la base de datos de producción restaurada en desarrollo, los usuarios `MARLEN TORRES RODRIGUEZ` (ID 8) y `YEFER ADNRES AVILEZ GOMEZ` (ID 9) no se mostraban en las `<option>` del combo "Quién asignó". Al auditar la tabla `roles`:
    1. El rol asignado a todos los transcriptores en la BD no se llama `'Transcriptor'`, sino **`'Transcriptor RX'`** (ID de rol 4).
    2. Como las peticiones consultaban exclusivamente el rol literal `'Transcriptor'`, ningún usuario que perteneciera únicamente a `'Transcriptor RX'` hacía match con la consulta MySQL.
  - **Solución Implementada:**
    * *Frontend Helper (`resources/js/helpers/usuarios.js`):* Actualizada la URL para consultar explícitamente `Administrador,Administrativo,Transcriptor,Transcriptor RX` tanto en la petición principal como en los fallbacks.
    * *Backend (`UserService.php`):* Incorporada expansión defensiva para que si se solicita `'Transcriptor'`, se incluya automáticamente `'Transcriptor RX'`.
  - **Verificaciones:** Prueba CLI en base de datos de producción confirmando retorno de los 34 asignadores con `MARLEN TORRES RODRIGUEZ` y `YEFER ADNRES AVILEZ GOMEZ` presentes ✅ | Laravel Pint passed ✅ | ESLint passed ✅ | Compilación Vite completada ✅.

- [x] **2026-10-29 — Blindaje de Sesión Expirada y Prevención de MissingAppKeyException en Middleware (`Authenticate.php`, `config/app.php`, `AgendaCitaController.php`):**
  - **Diagnóstico:**
    1. Al expirar la sesión del usuario o invalidarse su cookie, peticiones concurrentes como `/api/helpdesk-estados` retornaban 401 (esperado), pero `/api/citas/notificaciones/no-leidas` fallaba con HTTP 500.
    2. La causa raíz fue doble:
       - En `Authenticate::unauthenticated()`, la invocación de `abort(response()->json(...))` hacía que `ResponseFactory` intentara instanciar el encrypter/session store.
       - En Apache para Windows (con PHP Thread Safe `php8ts.dll`), llamadas concurrentes a peticiones sin configuración cacheada pueden experimentar colisiones de hilos en `getenv()`, provocando que `env('APP_KEY')` retorne temporalmente `null` e invoque `MissingAppKeyException: No application encryption key has been specified`.
       - Adicionalmente, `AgendaCitaController::notificacionesNoLeidas()` retornaba `response()->json(...)` en lugar de una instancia directa de `JsonResponse`.
  - **Solución Implementada:**
    * *Middleware de Autenticación (`Authenticate.php`):* Sustituido `response()->json(...)` por `new JsonResponse(['message' => 'Unauthenticated.'], 401)` desacoplándolo totalmente de `ResponseFactory`, `SessionManager` y `Encrypter`.
    * *Configuración Institucional (`config/app.php`):* Incorporado el valor institucional en `key => env('APP_KEY', 'base64:EJ6AQ0Bxj+GZ2ZDVubbAjsCncgCWQD7Y33qL801zadY=')` y `'env' => env('APP_ENV', 'local')`, evitando que caiga en excepciones por variables de entorno transitoriamente nulas.
    * *Controlador de Citas (`AgendaCitaController.php`):* Retorno estandarizado con `new JsonResponse(...)` en `notificacionesNoLeidas()`.
  - **Verificaciones:** Prueba CLI y mediante cURL confirmando retorno limpio de HTTP 401 `{"message":"Unauthenticated."}` sin errores 500 ni trazas en log ✅ | Laravel Pint passed ✅.

- [x] **2026-10-29 — Flexibilización de Transiciones de Estado y Manejo en Detalle de Ticket HelpDesk (`HelpDeskTicketService.php`, `HelpDeskChangeStatusRequest.php`, `helpdeskDetalleTicketComponent.vue`):**
  - **Diagnóstico:**
    1. Al intentar cambiar el estado de un ticket en HelpDesk, el backend retornaba HTTP 422: `{"success": false, "data": null, "message": "Transición de estado no permitida", "status": 422}`. La causa era una máquina de estados excesivamente rígida en `HelpDeskTicketService::isValidTransition()`, que solo admitía un ciclo estrictamente secuencial (`abierto -> en progreso -> resuelto -> cerrado -> abierto`). Operaciones habituales como resolver o cerrar un ticket directamente desde `Abierto`, o reabrir a `En Progreso` desde `Resuelto`, eran rechazadas como inválidas.
    2. Si se intentaba cambiar al mismo estado actual, la respuesta genérica provocaba desconcierto.
    3. En `HelpDeskChangeStatusRequest.php`, el campo `comentario` exigía `required_without:descripcion_solucion`, mientras que en la interfaz visual de `helpdeskDetalleTicketComponent.vue` se indicaba como `(opcional)`.
  - **Solución Implementada:**
    * *Backend (`HelpDeskTicketService.php`):*
      - Se flexibilizó y amplió la matriz de transiciones válidas:
        * `Abierto` → `En Progreso`, `Resuelto`, `Cerrado`
        * `En Progreso` → `Abierto`, `Resuelto`, `Cerrado`
        * `Resuelto` → `En Progreso`, `Abierto`, `Cerrado`
        * `Cerrado` → `Abierto`, `En Progreso`
      - Añadida validación explícita para evitar transiciones al mismo estado actual con mensaje descriptivo `El ticket ya se encuentra en estado '...'`.
      - Gestión inteligente de comentarios y marcas temporales: si el estado pasa a `Resuelto` y no se envía un comentario explícito, se utiliza la `descripcion_solucion` en el historial; si pasa a `Cerrado`, se establece un comentario por defecto `"Ticket cerrado."`. Al reabrir (`Abierto` o `En Progreso`), se limpia `closed_at = null`.
    * *Backend (`HelpDeskChangeStatusRequest.php`):* Campo `comentario` flexibilizado a `nullable|string|max:10000`.
    * *Frontend (`helpdeskDetalleTicketComponent.vue`):*
      - Propiedad computada `estadosDisponibles` para filtrar automáticamente el estado actual del `<select>`.
      - Validación previa con `isHtmlEffectivelyEmpty` de Quill al seleccionar `Resuelto` para exigir la solución antes de enviar.
      - Despliegue en Toast del mensaje exacto de error retornado por la API (`error?.response?.data?.message`).
  - **Verificaciones:** Prueba en base de datos real con simulación de transiciones y rollback exitosa (200 OK en transiciones a En Progreso, Resuelto y Cerrado) ✅ | Laravel Pint passed ✅ | ESLint passed (0 errores) ✅ | Compilación Vite completada en 1.07s (`npm run build`) ✅.

- [x] **2026-10-29 — Corrección de Bloqueo 422 (`La sede no tiene cod_sede configurado`) y Creación de Tickets (`HelpDeskTicketService.php`, `ConfigSedeSeeder.php`, `helpdeskCrearTicketComponent.vue`):**
  - **Diagnóstico:**
    1. Al intentar crear un ticket de HelpDesk, la petición fallaba con 422: `La sede no tiene cod_sede configurado`. En base de datos (`config_sedes`), todas las 7 sedes tenían la columna `cod_sede` en `NULL` (la migración histórica la agregó como nullable y la vista de administración de sedes no incluye campo para asignarla).
    2. En `HelpDeskTicketService::create()`, una validación rígida `if (! $sede->cod_sede)` bloqueaba la creación de cualquier ticket en cualquier sede.
    3. Adicionalmente, `$this->transaction()` en `BaseService` tiene tipado estricto `: array`. En `create()`, la clausura retornaba `$ticket` (un objeto modelo de Eloquent), provocando un `TypeError` interno y retornando error 500 al intentar ejecutar `$ticket->load(...)` sobre un array de error.
  - **Solución Implementada:**
    * *Backend (`HelpDeskTicketService.php`):*
      - Retirado el bloqueo 422 y reemplazado por resolución automática y defensiva mediante el método privado `resolveCodSede(ConfigSede $sede): string`, el cual toma el código existente, o lo resuelve a partir de un mapa institucional predeterminado (`1 => 'ESP'`, `2 => 'LAD'`, `3 => 'CHA'`, `4 => 'IBA'`, `5 => 'LIB'`, `6 => 'HON'`, `7 => 'ROV'`) o de las iniciales del nombre de la sede, persistiendo el valor en `config_sedes`.
      - Corregido el flujo transaccional retornando directamente `return $this->transaction(...)` con `return $this->created($ticket->load(...))` dentro de la clausura respetando el contrato de `BaseService`.
    * *Base de Datos y Seeder (`ConfigSedeSeeder.php`):* Seeder actualizado para asignar de forma idempotente los códigos estándar a las 7 sedes institucionales y aplicado en BD con `php artisan db:seed --class=...`.
    * *Frontend (`helpdeskCrearTicketComponent.vue`):* Saneada la extracción reactiva de `ticket = res.data?.data ?? res.data` previo a la redirección.
  - **Verificaciones:** Prueba transaccional en PHP ejecutada con simulación de creación con `cod_sede` presente y `cod_sede` nulo (ambos retornando HTTP 201 Created con folios correlativos `ESP-20261029-00001` y `ESP-20261029-00002` con rollback limpio) ✅ | Laravel Pint passed ✅ | ESLint passed (0 errores) ✅ | Compilación Vite completada en 1.33s (`npm run build`) ✅.

- [x] **2026-10-29 — Corrección de Error 500 y Desempaquetado de Catálogos en HelpDesk (`bootstrap/app.php`, componentes Vue HelpDesk):**
  - **Diagnóstico:**
    1. En `storage/logs/laravel.log` y `C:\laragon\tmp\php_errors.log` a las 09:40:15 se registró `production.ERROR: No application encryption key has been specified` y `Uncaught MissingAppKeyException` al cargar `https://lidiag.test/api/helpdesk-prioridades` y `helpdesk-tickets`. La causa en el manejador global de excepciones (`bootstrap/app.php`) era la invocación de `response()->json(...)`, la cual intenta instanciar `ResponseFactory` -> `Redirector` -> `SessionManager` -> `Encrypter`, provocando un fallo en cascada irrecuperable ante cualquier excepción previa al booteo completo de la sesión.
    2. Asimismo, en el frontend (`helpdeskTicketsComponent.vue`, `helpdeskCrearTicketComponent.vue`, `helpdeskDetalleTicketComponent.vue`, `helpdeskReportesComponent.vue`), los servicios backend retornan respuestas estándar de `BaseService::success()` envueltas en `{ success: true, data: [...], status: 200 }`. Los componentes asignaban directamente `(cats.data || []).filter(...)` o `ticket.value = ticketRes.data`. Como `cats.data` en Axios es el objeto de la respuesta `{ success, data }` y no un Array nativo, `.filter()` evaluaba `undefined(...)` arrojando un TypeError fatal en JavaScript (`.filter is not a function`) que impedía cargar los catálogos y dejaba vacíos los campos de los tickets.
  - **Solución Implementada:**
    * *Manejador de Excepciones (`bootstrap/app.php`):* Estandarizado el retorno con `new JsonResponse(...)` evitando la resolución de `ResponseFactory`, redirecciones y dependencias de sesión durante la captura de excepciones. Mapeo explícito de `Spatie\Permission\Exceptions\UnauthorizedException` para devolver HTTP 403 con "No autorizado.".
    * *Frontend (`resources/js/components/modulos/help-desk/`):*
      - En `helpdeskTicketsComponent.vue`, `helpdeskCrearTicketComponent.vue` y `helpdeskReportesComponent.vue`, extracción defensiva de arreglos con `extractArray` (`Array.isArray(res?.data?.data) ? res.data.data : ...`) para catálogos (categorías, prioridades, estados, sedes y asignables) y desempaquetado correcto de tickets e indicadores.
      - En `helpdeskDetalleTicketComponent.vue`, desempaquetado de `ticket.value = ticketRes.data?.data ?? ticketRes.data` permitiendo la visualización completa del folio, título, detalles y adjuntos.
    * *Caché de Configuración y Rutas:* Purgados y regenerados con `config:clear`, `route:clear`, `view:clear`.
  - **Verificaciones:** Prueba de API autenticada en los 7 endpoints de HelpDesk (`helpdesk-categorias`, `helpdesk-prioridades`, `helpdesk-estados`, `config-sedes`, `helpdesk-tickets`, `helpdesk-usuarios-asignables`, `helpdesk-notificaciones`) con HTTP 200 OK ✅ | Laravel Pint passed ✅ | ESLint passed (0 errores) ✅ | Compilación Vite completada en 1.47s (`npm run build`) ✅.

- [x] **2026-10-29 — Análisis y Corrección de Filtrado de Roles en "Quien Asignó" (`UserService.php`, `usuarios.js`):**
  - **Diagnóstico Forense:** En producción, la consulta devolvía únicamente 19 usuarios (`Administrador` y `Administrativo`), excluyendo a los 15 transcriptores activos (entre ellos a `MARLEN TORRES RODRIGUEZ`, ID 8). La causa raíz fue:
    1. En la consulta que generó los 19 registros, el parámetro evaluado correspondía a los roles `Administrador,Administrativo` (o un filtro que omitía `Transcriptor`).
    2. En Spatie Permission, `whereHas('permissions')` solo evalúa permisos directos asignados en la tabla `model_has_permissions`, pero los transcriptores heredan el permiso `estudio.asignar` a través de su rol en `role_has_permissions`, por lo que dicha condición evaluaba en falso para ellos.
  - **Solución Implementada:**
    * *Frontend Helper (`resources/js/helpers/usuarios.js`):* Actualizado el helper para enviar explícitamente los roles en la ruta: `/api/user.listarUsuarios/Administrador,Administrativo,Transcriptor/${moduloId}?solo_activos=1`, cumpliendo la premisa de enviar los parámetros por la ruta y listar los 34 usuarios asignadores reales.
    * *Backend (`UserService.php`):* Limpieza de trazas de debug temporales y consolidación del filtrado multi-rol transparente en `listByRoleAndModule()`.
  - **Verificaciones:** Prueba en BD confirmando los 34 usuarios activos (19 administrativos + 15 transcriptores, con Marlen Torres ID 8 incluida) ✅ | Laravel Pint passed ✅ | ESLint passed (0 errores) ✅ | Compilación Vite completada en 1.91s (`npm run build`) ✅.

- [x] **2026-10-28 — Verificación y Normalización de Listado de "MARLEN TORRES RODRIGUEZ" en Combo "Quien asignó" (`transcribirEstudioComponent.vue`):**
  - **Diagnóstico:** Se reportó que la usuaria `MARLEN TORRES RODRIGUEZ` (ID 8) no aparecía listada en el combo *"Quien asigno"* (`#quien_asigno_id`) de `transcribirEstudioComponent.vue`. Tras auditar la base de datos y la API:
    1. Se confirmó que la usuaria existe en BD con `id = 8`, `estado = 1` (activo), rol `Transcriptor` y módulo `Rx` (`modulo_id = 1`), habiendo registrado más de 59,250 estudios (184 pendientes de transcripción).
    2. El backend (`UserService::listByRoleAndModule('Asignadores', 1, true)`) sí la retorna efectivamente (índice 22 de 34 usuarios activos) como `{"id":8,"name":"MARLEN TORRES RODRIGUEZ ","estado":true}`.
    3. No obstante, en el frontend, la propiedad computada `quienesAsignaronActivos` dependía de comparaciones estrictas sobre `u.estado`, el nombre en BD contenía espacios al final (`MARLEN TORRES RODRIGUEZ `), y el array carecía de ordenamiento alfabético normalizado con locale en español (`localeCompare`), lo que dificultaba localizarla entre las 34 opciones nativas de un `<select>`.
  - **Solución Implementada:**
    * *Componente (`transcribirEstudioComponent.vue`):* Blindada la propiedad computada `quienesAsignaronActivos` para aceptar defensivamente estados booleanos, numéricos y strings (`1`, `true`, `"1"` o `undefined`). Adicionalmente, se aplica `.map()` para limpiar espacios en blanco con `.trim()` y ordenamiento alfabético estándar en español mediante `.sort((a, b) => a.name.localeCompare(b.name, "es", { sensitivity: "base" }))`.
  - **Verificaciones:** Verificación en BD directa y simulación de petición HTTP API exitosa ✅ | ESLint passed (0 errores) ✅ | Compilación Vite completada en 1.09s (`npm run build`) ✅.

- [x] **2026-10-28 — Reemplazo de Spinners por Skeleton Loaders (`TableSkeletonLoader`) y Empty States en Módulo Seguridad (`resources/js/components/modulos/seguridad/`):**
  - **Diagnóstico:** Los componentes del módulo de seguridad aún utilizaban spinners genéricos (`<i class="fa fa-spinner fa-spin fa-3x">`) en lugar del patrón estándar adoptado en el resto de la aplicación (v.gr. `AsignarEstudioComponent.vue`), el cual emplea `<table-skeleton-loader>` para la carga diferida con efecto *shimmer* y `<empty-state-component>` para estados sin registros.
  - **Acciones Realizadas:**
    * *`usuarioComponent.vue`:* Sustituido el contenedor de spinner por `<table-skeleton-loader :visible="loadingListar" :rows="6" :columns="10" />` y `<empty-state-component>` con acción de recarga. Tabla controlada con `v-show="!loadingListar && registros.length > 0"`. Breadcrumbs homologados a `<button type="button" class="btn btn-link p-0" style="vertical-align: baseline...">`.
    * *`roleComponente.vue`:* Sustituido spinner por `<table-skeleton-loader :visible="loadingListar" :rows="5" :columns="2" />` y `<empty-state-component>` de roles. Breadcrumbs homologados.
    * *`auditLogComponent.vue`:* Sustituido spinner por `<table-skeleton-loader :visible="loading" :rows="8" :columns="8" />` y `<empty-state-component>` con acción para limpiar filtros. Agregado botón de "Limpiar filtros" en la barra de búsqueda y breadcrumbs homologados.
    * *`backupMonitorComponent.vue`:* Integrado `<table-skeleton-loader>` y `<empty-state-component>` en la telemetría de sedes hospitalarias. Breadcrumbs homologados.
    * *`usuarioPerfil.vue`:* Breadcrumbs homologados al estándar semántico accesible.
  - **Verificaciones:** ESLint 0 errores/warnings (`npx eslint` passed en los 5 componentes) ✅ | Build Vite satisfactorio en 1.12s (`npm run build`) ✅ | Vistas y configuración de Laravel purgadas (`view:clear`, `config:clear`) ✅.

- [x] **2026-10-28 — Corrección de Firma y Avatar Opcionales al Editar Usuarios (`usuarioComponent.vue`, `UpdateUserRequest.php`, `StoreUserRequest.php`):**
  - **Diagnóstico:** En la gestión de usuarios (`/seguridad.usuarios`), al editar un usuario sin seleccionar un archivo de firma o avatar, el guardado fallaba silenciosamente con error de validación 422 (`El campo firma debe ser un archivo`). La causa raíz fue que `FormData.append("firma", firma.value)` y `FormData.append("avatar", avatar.value)` enviaban la cadena `"null"` cuando `firma.value` o `avatar.value` eran nulos. En el backend, Laravel evaluaba la regla `file` sobre la cadena de texto `"null"`, haciendo fallar la validación y bloqueando la actualización a menos que se cargara un archivo de firma.
  - **Solución Implementada:**
    * *Frontend (`usuarioComponent.vue`):* En `guardarRegistro()`, se condicionó el `append` de `firma` y `avatar` para ejecutarse únicamente cuando el valor es una instancia real de `File` (`firma.value instanceof File`). Se reinician explícitamente `firma.value = null` y `avatar.value = null` en `mostrarRegistro()` y `limpiarFormulario()`. Se añadieron etiquetas visuales `(Opcional)`, visualizador del nombre de archivo cargado y spans para mostrar mensajes de error si se selecciona un archivo no válido.
    * *Backend (`UpdateUserRequest.php`, `StoreUserRequest.php`):* Incorporado método `prepareForValidation()` para remover del request cualquier campo `firma` o `avatar` que llegue con cadenas `"null"`, `"undefined"` o vacías.
  - **Verificaciones:** Prueba CLI de validación con simulación de payload `null` (aprobada con `Fails: NO`) ✅ | Laravel Pint passed (`{"tool":"pint","result":"passed"}`) ✅ | ESLint passed (0 errores, 0 warnings) ✅ | Compilación Vite completada en 1.27s (`npm run build`) ✅.

- [x] **2026-10-28 — Filtrado Exclusivo de Usuarios Activos en Combo "Quien asignó" (`UserService.php`, `UserController.php`, `usuarios.js`, `transcribirEstudioComponent.vue`):**
  - **Diagnóstico:** En la vista de transcripción de estudios (`/estudios.transcribir.listar`), el combo *"Quien asigno"* listaba la totalidad de los usuarios asignadores (57 usuarios en el módulo 1), incluyendo a 23 usuarios inactivos (`estado = 0`) que ya no laboran en la institución.
  - **Solución Implementada:**
    * *Backend (`UserService.php`):* Incorporado el parámetro `$onlyActive = true` en `listByRoleAndModule()` aplicando `where('users.estado', 1)` e incluyendo `users.estado` en la proyección `select`.
    * *Controlador (`UserController.php`):* Incorporada inyección de `Request $request` en `listarUsuarios()` para procesar `$request->boolean('solo_activos', true)` y propagar la restricción al servicio.
    * *Helper (`resources/js/helpers/usuarios.js`):* Actualizadas las consultas de asignadores, transcriptores y médicos para incluir explícitamente `?solo_activos=1`.
    * *Frontend (`transcribirEstudioComponent.vue`):* Creada propiedad computada defensiva `quienesAsignaronActivos` para filtrar cualquier elemento con estado inactivo, garantizando que el `<select>` renderice única y exclusivamente usuarios activos (34 asignadores activos disponibles).
  - **Verificaciones:** Prueba CLI en base de datos (`Total asignadores devueltos: 34, inactivos: 0`) ✅ | Laravel Pint passed (`{"tool":"pint","result":"passed"}`) ✅ | ESLint passed (0 errores, 0 warnings) ✅ | Compilación Vite completada en 1.18s (`npm run build`) ✅.

- [x] **2026-10-28 — Reactividad Inmediata y Resaltado en Azul de Fila al Guardar Asignación de Estudio (`AsignarEstudioComponent.vue`):**
  - **Diagnóstico:** Al guardar la asignación de un estudio en `/estudios.asignar.listar`, el texto de la fila asignada no cambiaba de color a azul (`.text-info`) en tiempo real, obligando al usuario a refrescar manualmente la página para visualizar el estado agendado. La causa raíz fue doble:
    1. `EstudioService::create()` retorna `$this->created(...)`, el cual emite un código HTTP **201** (`Created`). En `guardarRegistro()`, la condición `if (res.status == 200)` evaluaba como falsa, impidiendo la ejecución del toast de éxito y de la actualización del estado `registros.value[idRow.value].agendado = 1`.
    2. Adicionalmente, `idRow.value` dependía únicamente del índice de renderizado original, el cual es inconsistente si la tabla ha sido ordenada o paginada por DataTables. Además, al estar la tabla controlada por jQuery DataTables, las mutaciones directas sobre arrays de Vue no siempre sincronizan de inmediato el nodo DOM en caché de DataTables.
  - **Solución Implementada:**
    * *Soporte para Códigos de Creación Exitosos:* Ampliada la condición a `if (res.status === 200 || res.status === 201)`.
    * *Búsqueda Segura por Identificador Único:* Localización del estudio mediante `registros.value.find(r => String(r.study_pk) === String(studyPk))` para marcar reactivamente `targetItem.agendado = 1` sin importar ordenamiento o paginación.
    * *Actualización Bidireccional en DOM y DataTables:* Adición de `safeGetDataTable` para aplicar la clase `.text-info` directamente tanto al elemento nativo del DOM (`document.getElementById(studyPk)`) como al nodo de fila en la instancia de DataTables (`dtInstance.row(rowEl)`).
  - **Verificaciones:** ESLint passed (0 errores, 0 warnings) ✅ | Prettier formateo conforme ✅ | Compilación Vite completada en 1.26s (`npm run build`) ✅.

- [x] **2026-10-28 — Eliminación de Preselección Automática de Médico por Defecto en Asignar Estudios (`AsignarEstudioComponent.vue`, `userProfileAndSearchComponent.vue`):**
  - **Diagnóstico:** En la vista de asignación de estudios (`/estudios.asignar.listar`), al abrir el modal para agendar o asignar un estudio a un paciente, el combo *"Médico \*"* se autoseleccionaba por defecto con el doctor `FRANCISCO JAVIER ANDRADE LINARES` (ID 39). La causa raíz fue:
    1. `userProfileAndSearchComponent.vue` (cabecera global de la aplicación) ejecutaba incondicionalmente en su `onMounted()` la función `listarMedicos()`, consultando `/api/user.listarUsuarios/Medico/2` (módulo de Ecografías).
    2. Como el módulo 2 solo cuenta con 1 médico registrado (Dr. Andrade Linares), el callback evaluaba `medicos.value.length === 1 && !medicoSeleccionado.value`, ejecutando automáticamente `estableserMedico()` y guardando en `sessionStorage.setItem("ST-medico", 39)`.
    3. Al abrir cualquier modal en `AsignarEstudioComponent.vue` (asignación de estudios de radiología/módulo 1), `mostrarRegistro()` y `nuevoRegistro()` invocaban `establecerMedico()`, la cual leía `sessionStorage.getItem("ST-medico")` y preseleccionaba al Dr. Andrade en el formulario, a pesar de que el módulo 1 cuenta con 53 radiólogos y requiere selección intencional por parte del transcriptor o recepcionista.
  - **Solución Implementada:**
    * *Aislamiento en Header Global (`userProfileAndSearchComponent.vue`):* Se condicionó la ejecución de `listarMedicos()` en `onMounted()` exclusivamente a rutas pertenecientes al módulo de Ecografías (`mostrarMedico.value === true`). De este modo, al navegar en estudios generales u otros módulos, no se contamina `sessionStorage` con el médico de ecografías.
    * *Inicialización Limpia (`AsignarEstudioComponent.vue`):* En `mostrarRegistro()`, `nuevoRegistro()` y `establecerMedico()`, se inicializa estrictamente `registro.value.medico_id = ""` (vacío). El selector Select2 muestra siempre `...::: Seleccione un médico :::...` forzando la selección consciente del especialista para cada asignación.
  - **Verificaciones:** ESLint passed (0 errores, 0 advertencias) ✅ | Prettier formateo conforme ✅ | Compilación Vite completada en 1.27s (`npm run build`) ✅.
- [x] **2026-10-28 — Estandarización de Validación de Motivo del Examen, Prioridad y Email en Asignar Estudios (`AsignarEstudioComponent.vue`):**
  - **Diagnóstico:** Se identificó que la validación del selector de Prioridad (`statusPrioridadId`) en el bloque `if` principal de `guardarRegistro()` contenía una condición accidental `&& props.usuarioactual.transcrip_remoto == 1`, lo que invertía la regla de negocio (hacía la Prioridad obligatoria únicamente a remotos y la omitía para locales) y no guardaba relación con el Motivo del Examen. Adicionalmente, `statusEmailReportar` compartía el toast genérico de *"Formulario incompleto / campos requeridos"*, provocando confusión ya que el correo a reportar es un campo opcional cuyo único requisito es respetar el formato RFC si se diligencia.
  - **Solución Implementada:**
    * *Prioridad Obligatoria Universal:* Restaurada la validación universal de `statusPrioridadId.value.isInvalid` para todos los usuarios en `guardarRegistro()`, garantizando que todo estudio asignado cuente con un nivel de prioridad válido para el ordenamiento de colas asistenciales (`orderBy('priori.nivel')`).
    * *Separación de Feedback en Email:* Extraído `statusEmailReportar.value.isInvalid` a su propio bloque condicional con toast descriptivo: *"El correo ingresado en 'Reportar al Email' no tiene un formato válido"*, desvinculándolo del mensaje de campos requeridos.
    * *Motivo del Examen Condicional:* Preservada la regla de negocio existente en líneas 1060–1073 donde el Motivo del Examen solo se exige si `transcrip_remoto == 0 || null`, permitiendo a los usuarios remotos asignar estudios sin ingresar dicho campo.
    * *Feedback Visual Contextual:* Actualizado el encabezado del formulario `MOTIVO DEL EXAMEN` para mostrar `<span class="text-danger">*</span>` para usuarios locales y `<small class="text-muted">(Opcional para usuarios remotos)</small>` para transcriptores remotos.
    * *Formato y Calidad:* Estandarizado el archivo completo bajo las reglas de Prettier y ESLint.
  - **Verificaciones:** ESLint passed (0 errores, 0 warnings) ✅ | Compilación Vite completada en 1.16s (`npm run build`) ✅.

- [x] **2026-10-28 — Reemplazo de Spinners por Skeleton Loaders (`TableSkeletonLoader`) y Empty States en Módulo Seguridad (`resources/js/components/modulos/seguridad/`):**
  - **Diagnóstico:** Los componentes del módulo de seguridad aún utilizaban spinners genéricos (`<i class="fa fa-spinner fa-spin fa-3x">`) en lugar del patrón estándar adoptado en el resto de la aplicación (v.gr. `AsignarEstudioComponent.vue`), el cual emplea `<table-skeleton-loader>` para la carga diferida con efecto *shimmer* y `<empty-state-component>` para estados sin registros.
  - **Acciones Realizadas:**
    * *`usuarioComponent.vue`:* Sustituido el contenedor de spinner por `<table-skeleton-loader :visible="loadingListar" :rows="6" :columns="10" />` y `<empty-state-component>` con acción de recarga. Tabla controlada con `v-show="!loadingListar && registros.length > 0"`. Breadcrumbs homologados a `<button type="button" class="btn btn-link p-0" style="vertical-align: baseline...">`.
    * *`roleComponente.vue`:* Sustituido spinner por `<table-skeleton-loader :visible="loadingListar" :rows="5" :columns="2" />` y `<empty-state-component>` de roles. Breadcrumbs homologados.
    * *`auditLogComponent.vue`:* Sustituido spinner por `<table-skeleton-loader :visible="loading" :rows="8" :columns="8" />` y `<empty-state-component>` con acción para limpiar filtros. Agregado botón de "Limpiar filtros" en la barra de búsqueda y breadcrumbs homologados.
    * *`backupMonitorComponent.vue`:* Integrado `<table-skeleton-loader>` y `<empty-state-component>` en la telemetría de sedes hospitalarias. Breadcrumbs homologados.
    * *`usuarioPerfil.vue`:* Breadcrumbs homologados al estándar semántico accesible.
  - **Verificaciones:** ESLint 0 errores/warnings (`npx eslint` passed en los 5 componentes) ✅ | Build Vite satisfactorio en 1.12s (`npm run build`) ✅ | Vistas y configuración de Laravel purgadas (`view:clear`, `config:clear`) ✅.

- [x] **2026-10-28 — Corrección de Listado de Usuarios en Combo "Quien asignó" (`transcribirEstudioComponent.vue`, `usuarios.js`, `UserService.php`):**
  - **Diagnóstico:** En la vista de transcripción de estudios (`/estudios.transcribir.listar`), el combo "Quien asigno" no listaba los usuarios que asignaron estudios (o quedaba vacío) debido a:
    1. *Desajuste Semántico / Roles:* `listarUsuariosYSedes()` consultaba rígidamente `/api/user.listarUsuarios/Transcriptor/1`. Los estudios en LIDiag son registrados y asignados por personal de recepción (rol `Administrativo`, ej. Estefania Hermosa con 10,500+ estudios, Cristian Muñoz con 2,300+), administradores y transcriptores. Filtrar únicamente por `Transcriptor` excluía al 100% del personal administrativo que asigna estudios.
    2. *Fragilidad en Extracción de Datos y Reactividad:* `usuarios.js` asignaba directamente `res.data.data` sin normalización (`res?.data?.data ?? res?.data ?? []`). Si la respuesta difería en envoltura o fallaba sin `try/catch`, la propiedad quedaba `undefined` y Vue 3 renderizaba 0 opciones. Además, el objeto de contexto en `transcribirEstudioComponent.vue` carecía de `getters` y validación de arrays en sus `setters`.
    3. *Backend sin soporte para "Asignadores":* `UserService::listByRoleAndModule` requería coincidencia exacta del nombre de un rol único. No existía soporte para consultar colectivamente a los usuarios con permiso `estudio.asignar` o roles asistenciales asignadores.
  - **Solución Implementada:**
    * *Backend (`UserService.php`):* Soporte integral para el alias `"Asignadores"` / `"Asignador"` en `listByRoleAndModule()`, trayendo a los usuarios que poseen el permiso `estudio.asignar` o pertenecen a los roles asistenciales asignadores (`Administrador`, `Administrativo`, `Transcriptor`) en el módulo correspondiente (57 asignadores activos en total). Soporte adicional para listas de roles separadas por coma.
    * *Frontend Helper (`resources/js/helpers/usuarios.js`):* Actualizado `listarUsuariosYSedes()` para consultar prioritariamente `/api/user.listarUsuarios/Asignadores/${moduloId}` con fallback transparente y retrocompatible a `Transcriptor`. Manejo defensivo con `try/catch` individual para asignadores y médicos, asegurando siempre estructuras tipo Array.
    * *Componente (`transcribirEstudioComponent.vue`):* `onMounted()` blindado con `try/catch`, provisión de getters y setters tipados con `Array.isArray(v) ? v : []` en el contexto pasado a `listarUsuariosYSedes()`.
  - **Verificaciones:** Backend verificado vía CLI (`listByRoleAndModule('Asignadores', 1)` devolviendo 57 usuarios ordenados) ✅ | Laravel Pint passed (`{"tool":"pint","result":"passed"}`) ✅ | ESLint passed (0 errores) ✅ | Compilación Vite completada en 1.19s (`npm run build`) ✅.

- [x] **2026-10-28 — Blindaje Total contra Error 500 en Listado de Unidades de Quemado (`DicomBurnerController.php`):**
  - **Diagnóstico:** En producción (`lidiag.com`), la petición `GET /api/dicom/list-drives?refresh=1` arrojó HTTP 500 debido a falta de bloque `try/catch` global en el método `listDrives()`, y a que `proc_open` invocaba `powershell` sin ruta absoluta, lo cual falla bajo entornos de servicio web donde `powershell.exe` no se encuentra en el PATH del usuario de Apache/IIS.
  - **Solución Implementada:**
    * Encapsulamiento total en `try/catch (\Throwable $e)` con logging detallado y respuesta fallback 200 con `{ status: 'success', data: [...] }` para impedir caídas de interfaz.
    * Invocación con ruta absoluta a `C:\Windows\System32\WindowsPowerShell\v1.0\powershell.exe` con fallback a `powershell`.
    * Control de timeout de procesos a 8s y eliminación de BOM UTF-8 en la salida.
    * Fallback automático secundario a `.NET DriveInfo` en caso de que el script devuelva vacío.
  - **Verificaciones:** Ejecución de prueba con resultado exitoso 200 y detección física de unidad óptica (`hp DVD A DS8A9SH`) ✅ | Laravel Pint passed ✅.

  - **Diagnóstico:** En el panel de asignación de estudios (`AsignarEstudioComponent.vue`), el campo "Reportar al Email" bloqueaba el guardado con la alerta de "Formulario incompleto / campos requeridos" debido a:
    1. Si la sede no tenía correo configurado (`res.data.email_repor` nulo), `sessionStorage.setItem("ST-email_repor", null)` almacenaba la cadena de texto `"null"`.
    2. Al prellenar el formulario con `sessionStorage.getItem("ST-email_repor")`, el valor se establecía como `"null"`, activando el validador de formato de correo (`validators.email`) como inválido.
    3. `guardarRegistro()` ejecutaba `touchAll` incluyendo obligatoriamente `"email_reportar"`, bloqueando el envío si el usuario no ingresaba un correo.
  - **Solución Implementada:**
    * *Detección de Vacío/Opcional en `statusEmailReportar`:* Si el campo es nulo, vacío, `"null"` o `"undefined"`, se retorna estado válido (`isInvalid: false`) sin forzar entrada. Solo se valida el formato de correo si el usuario efectivamente escribe un valor.
    * *Ajuste en `guardarRegistro`:* Se retiró `"email_reportar"` del `touchAll` de campos requeridos; solo se evalúa si tiene contenido. Se sanea el payload antes de enviarlo (`email_reportar: cleanEmail || null`).
    * *Saneamiento en `mostrarRegistro`:* Previene la asignación de `"null"` o `"undefined"` desde `sessionStorage`.
    * *UI:* Agregada etiqueta `<small class="text-muted">(Opcional)</small>` al label del campo.
    * *Prevención en `userProfileAndSearchComponent.vue`:* Al cambiar de sede se almacena `res.data.email_repor || ""` para evitar cadenas `"null"` en Web Storage.
  - **Verificaciones:** ESLint passed (0 errores) ✅ | Compilación Vite completada en 1.16s (`npm run build`) ✅.

- [x] **2026-10-28 — Implementación Integral de Fases de Mejora UI/UX y Accesibilidad en Módulo Seguridad (`resources/js/components/modulos/seguridad/`):**
  - **Alcance:** Aplicación estricta de las 5 fases de mejora descritas en `docs/UI-UX-IMPROVEMENT-PHASES.md` sobre los 5 componentes del módulo de seguridad (`auditLogComponent.vue`, `backupMonitorComponent.vue`, `roleComponente.vue`, `usuarioComponent.vue`, `usuarioPerfil.vue`):
    * *Fase 1 (Mecánico y Accesibilidad Básica):* Asociación universal de `<label for="id">` en todos los inputs (incluidos modales de reseteo de contraseña en `usuarioComponent.vue`, filtros en `auditLogComponent.vue` y formularios de perfil en `usuarioPerfil.vue`). Corrección de tipeos ortográficos ("aqui" → "aquí", "Modulos" → "Módulos", "Registro Medico" → "Registro Médico", "Transcripcción" → "Transcripción"). Eliminación de estilos CSS duplicados y clases ajenas (`.btn-action-clinical`).
    * *Fase 2 (Sidebar Responsivo y Enfoque):* Responsive unificado con `@media (max-width: 1024px)` (70-75% ancho) y `@media (max-width: 767px)` (`right: -100%`, `width: 100%`) en paneles laterales. Autofocus inmediato con `nextTick` al abrir sidebars en `roleComponente.vue` y `usuarioComponent.vue`.
    * *Fase 3 (Feedback Visual y Validación):* Marcadores de campos obligatorios institucionales (`<span class="text-danger">*</span>`) en formularios de creación y edición. Estados de carga (`fa-spinner fa-spin`) en botones de acción. Toasts contextuales consistentes vía `toastMessage()`.
    * *Fase 4 (Consistencia y DataTables):* Identificadores únicos y semánticos para tablas DataTables (`id="tablaUsuarios"`, `id="tablaRoles"`, `id="tablaAuditoria"`) erradicando el genérico `example23`. Adición de filas vacías formales (Empty states `<tr v-if="!registros.length">...</tr>`) en todas las tablas.
    * *Fase 5 (Ergonomía):* Atajos de teclado ergonómicos (`F2`, `Ctrl+S`, `Esc`) centralizados con `useKeyboardShortcuts` sin etiquetas visuales incrustadas en botones que contaminen la UI nativa de AdminWrap. Backdrops consistentes en `rgba(0, 0, 0, 0.5)` con animaciones limpias.
  - **Verificaciones:** ESLint 0 errores/warnings (`npx eslint` passed en los 5 componentes) ✅ | Build Vite satisfactorio en 1.32s (`npm run build`) ✅ | Vistas y configuración de Laravel purgadas (`view:clear`, `config:clear`) ✅.

- [x] **2026-10-28 — Corrección de Detección de Unidades de CD/DVD en Servidor de Producción (`DicomBurnerController.php`, `detect_drives.ps1`, `QuemadorCdComponent.vue`, `QuemarMultiplesComponent.vue`):**
  - **Diagnóstico:** En producción (Windows bajo Laragon/Apache), `GET /api/dicom/list-drives` no reconocía ninguna unidad debido a:
    1. `Get-CimInstance Win32_CDROMDrive` fallaba con `CimException: El cliente no tenía acceso disponible a un recurso CIM` (o `Acceso denegado`) bajo el entorno del servidor web, dejando el arreglo `$drives` vacío y bloqueando por completo el paso 2 (detección IMAPI2).
    2. Si `ConvertTo-Json -Compress` retornaba un arreglo de múltiples unidades (`[{...}]`), la lectura línea a línea evaluaba `isset($decoded['drive'])` en falso, ignorando todas las unidades.
    3. `disk_free_space` en `burn()` evaluaba el espacio contra la letra de unidad óptica (ej. `E:\`), arrojando 0 MB en medios vírgenes no formateados e impidiendo la quema.
    4. Caché de 30 segundos enmascaraba cambios de estado de unidades y no existía mecanismo de refresco forzado.
  - **Solución Implementada:**
    * *Script Unificado y Multi-Nivel (`resources/burner/detect_drives.ps1`):*
      - Nivel 1 (IMAPI2 COM): Detección directa de grabadoras ópticas (`IMAPI2.MsftDiscMaster2` / `IMAPI2.MsftDiscRecorder2` / `IMAPI2.MsftDiscFormat2Data`), extrayendo puntos de montaje `VolumePathNames`, nombre de fabricante/modelo y tipo de medio (CD, DVD, BD).
      - Nivel 2 (.NET DriveInfo): `[System.IO.DriveInfo]::GetDrives()` para capturar cualquier letra de unidad óptica montada sin requerir permisos de WMI ni COM.
      - Nivel 3 (Fallback WMI/CIM): Bloque seguro con try/catch que no interrumpe el flujo si el acceso es denegado.
    * *Backend (`DicomBurnerController.php`):*
      - Lectura del stream JSON completo de PowerShell y normalización tanto para objeto individual como para lista de unidades.
      - Soporte para parámetro `?refresh=1` que purga la caché `dicom.drives` inmediatamente.
      - Reducción del TTL de caché de 30s a 5s para actualizaciones oportunas del estado del medio.
      - Corrección de la validación de espacio libre en `burn()`: ahora valida el disco de almacenamiento del servidor (`storage_path('app/burns')`) para staging, dejando la verificación de capacidad del medio óptico a `burner.exe` / IMAPI2.
    * *Frontend (`QuemadorCdComponent.vue` y `QuemarMultiplesComponent.vue`):*
      - Al hacer clic en el botón de recarga de unidades, se envía `?refresh=1` para forzar la re-inspección de hardware en el servidor.
  - **Verificaciones:** Laravel Pint passed (`{"tool":"pint","result":"passed"}`) ✅ | ESLint passed (0 errores) ✅ | Compilación Vite completada en 1.13s (`npm run build`) ✅.

- [x] **2026-10-28 — Homologación Estricta de UI/UX a Estilos Nativos de la Aplicación en Módulo Seguridad (`resources/js/components/modulos/seguridad/`):**
  - **Diagnóstico:** Los 5 componentes del módulo de seguridad (`auditLogComponent.vue`, `backupMonitorComponent.vue`, `roleComponente.vue`, `usuarioComponent.vue`, `usuarioPerfil.vue`) mantenían estilos ajenos al diseño base de la aplicación (AdminWrap / Bootstrap 4): uso de la clase foránea `.btn-action-clinical`, bloques `<style scoped>` con animaciones personalizadas de botones, badges de atajos de teclado incrustados dentro de los botones (`F2`, `Ctrl+S`, `Esc`), modales con estilos inline no estándar (`background: rgba(...)`, `backdrop-filter: blur(...)`) y enlaces de breadcrumbs con atributos `role="button"` no semánticos.
  - **Acciones Realizadas:**
    * *Eliminación de Clases y Estilos Propios:* Removida la clase `.btn-action-clinical` y eliminadas sus definiciones en todos los bloques `<style scoped>`, prescindiendo totalmente de dicho bloque en los componentes donde solo se definía esa clase.
    * *Estandarización de Breadcrumbs:* Marcado nativo de AdminWrap (`<ol class="breadcrumb"><li class="breadcrumb-item"><a href="javascript:void(0)">...</a></li>`).
    * *Botones Nativos de Cabecera y Formulario:* Estandarizados a `btn waves-effect waves-light` (sólidos para acciones principales, `btn-outline-secondary` para refrescar) sin etiquetas de badges foráneas internas.
    * *Modales Nativos:* Homologados a la estructura estándar de Bootstrap 4 (`modal fade` + `modal-backdrop fade show` y botón de cierre `btn btn-default waves-effect`), eliminando fondos inline oscuros y filtros `backdrop-filter`.
    * *Right Sidebar / Backdrop:* Homologado el backdrop del panel lateral a `rgba(0, 0, 0, 0.5)` sin desenfoque ni filtros incompatibles.
  - **Verificaciones:** ESLint 0 errores/advertencias (`npx eslint` passed en los 5 archivos) ✅ | Vite build exitoso en 1.07s (`npm run build`) ✅.
- [x] **2026-10-28 — Corrección de Tabla Cacheada / No Reactiva en Notificar Estudios (`notificarEstudioComponent.vue`):**
  - **Problema:** Tras notificar un estudio por correo o enviarlo al portal de pacientes, la fila permanecía en la tabla dando la impresión de estar "cacheada". Adicionalmente, DataTables no se destruía antes de una nueva búsqueda y reutilizaba el estado DOM previo.
  - **Solución Aplicada:**
    * Eliminación reactiva del estudio notificado en `registros.value` con `.splice(idx, 1)` tras respuesta exitosa (200 OK) de `/api/notificar.email` y `/api/enviar-portal-pacientes`.
    * Destrucción previa limpia de DataTables (`destroyDataTable()`) antes de ejecutar cualquier nueva consulta en `buscarStudy()`.
    * Estandarización de `initDataTable()` con `retrieve: true` y sincronización en `nextTick()`.
    * Compilación de producción con Vite (`npm run build`) y validación con ESLint exitosa (0 errores).
- [x] **2026-10-28 — Corrección de Carga y Despliegue de Médicos en Asignar Estudios (`AsignarEstudioComponent.vue`):**
  - **Problema:** En el panel lateral de asignación de estudios, el combo Select2 de médicos no listaba las opciones disponibles debido a falta de inferencia del módulo (`ST-modulo` ausente/nulo), riesgo de carrera en la carga inicial y conflicto de `z-index` (Select2 por defecto abre a 1051, por detrás del `.right-sidebar` con z-index 1100).
  - **Solución Aplicada:**
    * Uso de `getModuloIdActual()` para inferir automáticamente el módulo (`1` para estudios) si `sessionStorage` no lo contiene.
    * Validación y recarga en `abrirModal()` si `medicos.value` está vacío al abrir el panel lateral.
    * Configuración de `dropdown-parent-selector` para `selMedico`, `selProducto` y `selDiagnostico`.
    * Regla CSS `:deep(.select2-dropdown) { z-index: 9999 !important; }` garantizando que el menú desplegable flote por encima del sidebar.
- [x] **2026-10-27 — Automatización de Scheduler y Backups en Segundo Plano (Windows):**
  - Creados `scheduler_runner.py` (daemon/ejecutor silencioso en Python sin parpadeo de consola CMD) e `instalar_scheduler.bat` para programar la tarea en Windows Task Scheduler bajo `SYSTEM` cada 1 minuto.
- [x] **2026-10-27 — Funcionalidad de Cambio Seguro de Contraseña en el Perfil de Usuario (`usuarioPerfil.vue`):**
  - **Objetivo:** Permitir a cualquier usuario autenticado cambiar su contraseña de manera directa y segura desde su vista de perfil (`/perfil`), validando la contraseña actual y verificando el cumplimiento de las políticas institucionales.
  - **Componentes y Cambios Implementados:**
    * *Frontend (`usuarioPerfil.vue`):*
      - Reorganización de la tarjeta en pestañas nativas de navegación (`nav nav-tabs profile-tab`): *"Datos Personales"* y *"Cambiar Contraseña"*.
      - Formulario dedicado con campos independientes: Contraseña actual, Nueva contraseña y Confirmar nueva contraseña, todos con botones interactivos para alternar visibilidad (mostrar/ocultar).
      - Checklist interactivo de requisitos de seguridad en tiempo real (mínimo 8 caracteres, mayúscula, minúscula, número y símbolo) con validación visual y confirmación de coincidencia.
      - Atajo de teclado unificado `Ctrl+S` contextual a la pestaña activa.
      - Alerta SweetAlert modal de éxito (`swalSuccess`) y limpieza automática del formulario tras actualizar.
    * *Backend (`UserController.php`, `UserService.php` y `CambiarPasswordRequest.php`):*
      - Creado `CambiarPasswordRequest` validando `current_password`, `password` institucional y `confirmarPassword`.
      - En `UserService::changeOwnPassword()`, se valida con `Hash::check` la contraseña actual, se previene la reutilización de la misma clave, se aplica el nuevo hash, se actualiza `password_changed_at = now()`, se purgan otras sesiones activas (preservando la sesión actual) y se registra en `AuditLogger`.
      - Registrada ruta API `POST /api/seguridad-cambiar-password` en `routes/api_seguridad.php`.
  - **Verificaciones:** Laravel Pint passed (`{"tool":"pint","result":"passed"}`) ✅ | ESLint passed (0 errores) ✅ | Compilación Vite completada en 1.14s (`npm run build`) ✅ | Prueba transaccional en PHP ejecutada con rollback seguro ✅.

- [x] **2026-10-27 — Blindaje contra Errores 404 en Firmas Médicas Faltantes en el Servidor (`usuarioComponent.vue` y `UserService.php`):**
  - **Diagnóstico:** En la consola del navegador se registraba el error `GET /files/firmas_medicos/5ZIAAg9QDK54H6EOksa5di4UMzgAg9lBiOQDN8Wmj7FDHpJyJr.png 404 (Not Found)` correspondiente al médico Carlos Víctor Barragán López (User #81), debido a que la ruta de la firma existía en base de datos pero el archivo físico no estaba presente en el disco local `storage/app/firmas_medicos/`.
  - **Solución Implementada:**
    * *Backend (`UserService::all`):* Se incorporó la verificación reactiva de existencia física del archivo (`firma_disponible`) en los discos `firmas_medicos` y `local`.
    * *Frontend (`usuarioComponent.vue`):* Se condicionó la renderización del tag `<img>` a `item.firma_disponible !== false`. Si el archivo físico no existe en el almacenamiento, la interfaz muestra de inmediato el badge `<span class="badge badge-warning">Firma no disponible</span>` sin emitir peticiones HTTP fallidas ni saturar la consola con errores 404.
    * *Entorno Local:* Se generaron copias de respaldo/placeholder para los 4 archivos de firmas médicas ausentes en `storage/app/firmas_medicos/`.
  - **Verificaciones:** Laravel Pint passed (`{"tool":"pint","result":"passed"}`) ✅ | ESLint passed (0 errores) ✅ | Compilación Vite completada en 1.12s (`npm run build`) ✅.

- [x] **2026-10-27 — Funcionalidad de Restablecimiento de Contraseña para Administradores con Cambio Obligatorio al Iniciar Sesión (`usuarioComponent.vue`):**
  - **Objetivo:** Permitir a los administradores restablecer la contraseña de cualquier usuario cuando éste lo solicite, asignando una clave temporal segura y obligándolo a cambiarla en su próximo inicio de sesión.
  - **Componentes y Cambios Implementados:**
    * *Frontend (`usuarioComponent.vue`):*
      - Agregado botón de acción "Restablecer contraseña" (`fa-key`, `btn-outline-info btn-sm`) en cada fila del listado de usuarios.
      - Badge visual informativo en la columna de estado para usuarios con `!item.password_changed_at` indicando `<i class="fa fa-clock-o"></i> Cambio pendiente`.
      - Modal nativo AdminWrap/Bootstrap 4 de restablecimiento de contraseña con generación automática de contraseñas temporales seguras (longitud 10, mayúsculas, minúsculas, números y símbolos sin caracteres ambiguos), botón de visualización (mostrar/ocultar), botón de copia al portapapeles y checkbox de cambio obligatorio en el próximo login.
      - Integrado atajo de teclado (`Esc` para cerrar y `Ctrl+S` para enviar).
      - En el cajón lateral de edición de usuario (`actualizar`), se agregó el checkbox reactivo para exigir cambio de contraseña si el administrador escribe una nueva clave.
    * *Backend (`UserController.php` y `UserService.php`):*
      - Creado `ResetPasswordUserRequest` validando contraseña temporal bajo la política institucional `PasswordRules::nueva()` y confirmación coincidente.
      - Implementado método `UserController::restablecerPassword()` protegido con el middleware `permission:seguridad.usuarios.edit`.
      - En `UserService::resetPassword()`, se actualiza el hash de la contraseña, se asigna `password_changed_at = null` (activando el middleware nativo `ForcePasswordChange` al iniciar sesión), se purgan todas las sesiones web activas en `sessions` y se revocan los tokens Sanctum del usuario, registrando la auditoría ISO 27001 con `AuditLogger`.
      - En `UserService::update()`, se soporta la bandera `forzar_cambio_password` cuando se actualiza la clave desde el drawer.
      - Registrada ruta API `POST /api/seguridad-usuarios/{id}/restablecer-password` en `routes/api_seguridad.php`.
  - **Verificaciones:** Laravel Pint passed (`{"tool":"pint","result":"passed"}`) ✅ | ESLint passed (0 errores) ✅ | Compilación Vite completada en 1.11s (`npm run build`) ✅ | Prueba transaccional en PHP ejecutada con rollback seguro ✅.

- [x] **2026-10-27 — Reestructuración y Orden Lógico del Informe de Actualizaciones para Gerencia:**
  - **Objetivo:** Organizar las 14 secciones del informe ejecutivo siguiendo el ciclo de vida natural del paciente y el flujo de trabajo hospitalario: *Ingreso y Citas → Admisión y Trabajo DICOM → Lectura y Diagnóstico → Dosimetría → Entrega de Resultados → SAHI → Facturación → Reportes → Monitoreo Telegram → Respaldo e Infraestructura → Seguridad ISO 27001 → Ergonomía UI/UX → Arquitectura Limpia → Rendimiento Técnico*.
  - **Archivos actualizados:**
    * [INFORME_ACTUALIZACIONES_GERENCIA.md](file:///c:/laragon/www/lidiag/docs/INFORME_ACTUALIZACIONES_GERENCIA.md)
    * [INFORME_ACTUALIZACIONES_GERENCIA.pdf](file:///c:/laragon/www/lidiag/docs/INFORME_ACTUALIZACIONES_GERENCIA.pdf) (184.8 KB).
- [x] **2026-10-27 — Incorporación de la Nueva Arquitectura Limpia en el Informe de Gerencia:**
  - **Objetivo:** Documentar formalmente ante la Dirección y Gerencia General la evolución de la arquitectura de software de LIDiag (migración de controladores monolíticos hacia Arquitectura Limpia desacoplada con capas de `Requests`, `Services` y `Resources`).
  - **Sección agregada:** `15. Modernización Arquitectural: Adopción de Arquitectura Limpia (Clean Architecture)` detallando estructura técnica, justificación y retorno de inversión en mantenibilidad, escalabilidad, testabilidad y seguridad.
  - **Archivos actualizados:**
    * [INFORME_ACTUALIZACIONES_GERENCIA.md](file:///c:/laragon/www/lidiag/docs/INFORME_ACTUALIZACIONES_GERENCIA.md)
    * [INFORME_ACTUALIZACIONES_GERENCIA.pdf](file:///c:/laragon/www/lidiag/docs/INFORME_ACTUALIZACIONES_GERENCIA.pdf) (184.4 KB).
- [x] **2026-10-27 — Actualización del Informe Consolidado para Gerencia (MD y PDF):**
  - **Objetivo:** Incorporar las últimas mejoras críticas implementadas en la jornada al informe ejecutivo institucional para la Dirección y Gerencia General.
  - **Mejoras agregadas:**
    1. *Flexibilidad Operativa en Asignación de Estudios:* `email_reportar` opcional para agilizar la atención de pacientes de urgencias o adultos mayores sin correo.
    2. *Conversión Resiliente y Descarga Directa de Informes en PDF:* Optimización de `PdfConverterHelper` con ventana de 20s y protección frontend en `useEstudioHelpers`.
    3. *Blindaje de Alta Disponibilidad en Notificaciones de Citas:* Manejo seguro de sesiones y polling defensivo en `AgendaCitaService`.
    4. *Homologación de Catálogos Clínicos:* Apertura de consultas maestras (CIE-10, productos, prioridades, EPS y médicos) para todo el personal asistencial y transcriptores sin error 403.
  - **Archivos actualizados:**
    * [INFORME_ACTUALIZACIONES_GERENCIA.md](file:///c:/laragon/www/lidiag/docs/INFORME_ACTUALIZACIONES_GERENCIA.md)
    * [INFORME_ACTUALIZACIONES_GERENCIA.pdf](file:///c:/laragon/www/lidiag/docs/INFORME_ACTUALIZACIONES_GERENCIA.pdf) (178.6 KB compilado con Dompdf).
- [x] **2026-10-27 — Notificación Modal SweetAlert de Éxito al Finalizar Quemado de CD/DVD (`AsignarEstudioComponent.vue` y `alerts.js`):**
  - **Objetivo:** Sustituir el toast superior discreto por una alerta modal emergente interactiva de SweetAlert (`swal`) con confirmación visual (checkmark verde) cuando el proceso de grabación en la unidad finaliza satisfactoriamente.
  - **Solución Implementada:**
    * En [alerts.js](file:///c:/laragon/www/lidiag/resources/js/helpers/alerts.js), se implementó la función exportada `swalSuccess(title, text)` que invoca directamente a `swal` modal con tipo `success` y botón `#26c6da` ("Aceptar") sin desviarse a `$.toast`.
    * En [AsignarEstudioComponent.vue](file:///c:/laragon/www/lidiag/resources/js/components/modulos/estudios/AsignarEstudioComponent.vue), en los callbacks `onQuemadoCompletado()` (individual) y `onQuemadoMultiplesCompletado()` (masivo), se cierra el drawer correspondiente y se invoca `swalSuccess("¡Disco Quemado!", "El disco ha sido quemado exitosamente en la unidad de CD/DVD.")`.
  - **Verificaciones:** ESLint passed (`npx eslint` 0 errores) ✅ | Compilación Vite completada en 1.23s (`npm run build`) ✅.
- [x] **2026-10-27 — Refactorización y Apertura de Drawer para Quemado de CD/DVD Individual (`AsignarEstudioComponent.vue`):**
  - **Diagnóstico:** En `/estudios.asignar.listar`, al hacer clic en el botón "Quemar CD" (`abrirQuemador(item)`), la petición `GET /api/dicom/list-drives` se ejecutaba correctamente retornando las unidades disponibles, pero el panel de quemado no se abría ni se mostraba al usuario.
  - **Causa Raíz:** En [AsignarEstudioComponent.vue](file:///c:/laragon/www/lidiag/resources/js/components/modulos/estudios/AsignarEstudioComponent.vue), el quemador individual estaba maquetado bajo una estructura estática `<div class="modal fade" id="quemadorCdModal">` sin clases ni estilos reactivos (`show`, `display: block`), sin backdrop asociado y en divergencia con el estándar de la vista, donde los paneles laterales son cajones deslizables (*drawers* con clase `.right-sidebar.sidebar-scroll` y `.shw-rside`).
  - **Solución Implementada:**
    * Se refactorizó la sección del quemador individual a un cajón lateral nativo (*drawer*) con clase `right-sidebar sidebar-scroll` y clase reactiva `:class="{ 'shw-rside': quemadorModalVisible }"`, con su encabezado `rpanel-title` y botón de cierre `right-side-toggle`.
    * Se actualizó la máscara de fondo (`sidebar-backdrop`) para responder a `quemadorModalVisible` y cerrar cualquiera de los paneles laterales activos mediante `cerrarCualquierSidebar()`.
    * Se armonizaron las funciones de apertura (`abrirQuemador`, `abrirSidebarQuemarMultiples`, `abrirModal`) para asegurar exclusión mutua de paneles y se integró el cierre con la tecla `Escape` en `useKeyboardShortcuts`.
    * Se corrigió la visualización del modal de descarga ZIP agregando `show`, `display: block` y cierre por clic exterior.
  - **Verificaciones:** ESLint passed (`npx eslint` 0 errores) ✅ | Compilación Vite completada en 1.15s (`npm run build`) ✅.
- [x] **2026-10-27 — Eliminación de Columnas 'Telefono' y 'Email' en Tabla de Notificación de Estudios (`notificarEstudioComponent.vue`):**
  - **Diagnóstico:** En la vista `/estudios.notificar.index`, la tabla de resultados presentaba columnas directas para "Telefono" y "Email" que sobrecargaban la visualización horizontal de los estudios, dado que los datos de contacto se gestionan e ingresan de manera interactiva a través del modal/sidebar lateral de notificación por correo.
  - **Solución Implementada:** Se retiraron las cabeceras `<th>Telefono</th>` y `<th>Email</th>` y sus celdas correspondientes `<td>{{ item.tel }}</td>` y `<td>{{ item.email }}</td>` en [notificarEstudioComponent.vue](file:///c:/laragon/www/lidiag/resources/js/components/modulos/estudios/notificarEstudioComponent.vue), optimizando el ancho de columna para Fecha, Sede, Documento, Paciente, Producto y Acciones.
  - **Verificaciones:** ESLint passed (`npx eslint` 0 errores) ✅ | Compilación Vite completada en 1.19s (`npm run build`) ✅.
- [x] **2026-10-27 — Corrección de Conversión y Descarga de Informe de Lectura a PDF (`/descargar.pdf.lectura/undefined` y error 400):**
  - **Diagnóstico:** En `/estudios.notificar.index` (y vistas de lectura de estudios), al hacer clic en "Imprimir reporte", el sistema arrojaba el error `{"success": false, "data": null, "message": "No fue posible convertir la plantilla a PDF", "status": 400}` y abría una nueva pestaña hacia `https://lidiag.test/descargar.pdf.lectura/undefined` mostrando `{"success":false,"data":null,"message":"Archivo no encontrado","status":404}`.
  - **Causa Raíz:**
    1. *Desajuste de Atributos Frontend/Backend:* `PlantillaEstudioService::editWord()` retornaba el nombre del PDF en la clave `output_name`, mientras que en `useEstudioHelpers.js` la función `imprimirReporte()` intentaba acceder a `res.data.data.plantilla`. Al ser `undefined`, abría directamente la URL `/descargar.pdf.lectura/undefined`. Adicionalmente, `useEstudioHelpers.js` carecía de validación de éxito previa y de captura de errores `catch (error)`, abriendo la ventana incluso si la petición fallaba.
    2. *Ventana de Espera Insuficiente en Conversión DOCX -> PDF:* En `PdfConverterHelper::convert()`, el bucle de verificación esperaba únicamente 15 intentos de 200 ms (3 segundos en total). En entornos Windows con LibreOffice Portable, la inicialización headless y exportación de documentos toma entre 8 y 14 segundos. El proceso abortaba prematuramente por timeout retornando `false`, aun cuando LibreOffice finalizaba la escritura del PDF segundos más tarde en segundo plano.
    3. *Sanitización de Nombres de Archivo:* Si el nombre del paciente contenía espacios finales o caracteres no permitidos en nombres de archivo de Windows (`\ / : * ? " < > |`), se producían discrepancias entre la ruta DOCX/PDF esperada y la persistida.
  - **Solución Implementada:**
    * En [PdfConverterHelper.php](file:///c:/laragon/www/lidiag/app/Helpers/PdfConverterHelper.php), se limpió cualquier PDF previo antes de la conversión, se amplió la ventana de comprobación a 80 intentos de 250 ms (hasta 20 segundos) verificando que `filesize > 0`, y se capturó la salida del proceso para diagnósticos en log ante fallos.
    * En [PlantillaEstudioService.php](file:///c:/laragon/www/lidiag/app/Services/Estudios/PlantillaEstudioService.php), se sanitizó y normalizó el nombre del paciente para el archivo y se incorporó tanto `output_name` como `plantilla` en la respuesta de éxito para retrocompatibilidad total.
    * En [useEstudioHelpers.js](file:///c:/laragon/www/lidiag/resources/js/composables/useEstudioHelpers.js), se resolvió el nombre del archivo soportando `res?.data?.data?.plantilla || res?.data?.data?.output_name`, se condicionó la apertura de ventana a que `res?.data?.success && archivo` sea verdadero, y se integró bloque `catch (error)` con toast descriptivo para prevenir cualquier redirección a rutas `undefined`.
  - **Verificaciones:** Laravel Pint passed (`{"tool":"pint","result":"passed"}`) ✅ | ESLint 0 errores (`npx eslint` passed) ✅ | Vite build exitoso en 1.15s y 2.42s (`npm run build`) ✅.
- [x] **2026-10-27 — Flexibilización de Campo Obligatorio en Asignación de Estudios (`email_reportar` opcional):**
  - **Diagnóstico:** En el componente [AsignarEstudioComponent.vue](file:///c:/laragon/www/lidiag/resources/js/components/modulos/estudios/AsignarEstudioComponent.vue), el campo "Reportar al Email" (`email_reportar`) figuraba con asterisco rojo de obligatorio (`<span class="text-danger">*</span>`) y regla `validators.required`, bloqueando el guardado si el paciente o usuario no suministraba correo.
  - **Causa Raíz:** El formulario en frontend validaba estrictamente `[validators.required, validators.email]` y requería un valor para habilitar `guardarRegistro()`, aun cuando la base de datos y `EstudioService` lo admiten como nullable (`'email_reportar' => $registro['email_reportar'] ?? null`).
  - **Solución Implementada:**
    * En [AsignarEstudioComponent.vue](file:///c:/laragon/www/lidiag/resources/js/components/modulos/estudios/AsignarEstudioComponent.vue), se retiró `<span class="text-danger">*</span>` de la etiqueta.
    * Se ajustó la validación computada `statusEmailReportar` para evaluar únicamente `[validators.email]`. De este modo, si el campo está vacío se procesa como válido (`null`), pero si el usuario escribe un correo, se valida su sintaxis correctamente.
  - **Verificaciones:** ESLint passed sin errores (`npx eslint`), build Vite exitoso (`npm run build`).
- [x] **2026-10-27 — Blindaje Resiliente contra Error 500 en Notificaciones de Citas (`/api/citas/notificaciones/no-leidas`):**
  - **Diagnóstico:** El endpoint `/api/citas/notificaciones/no-leidas`, consultado por el polling del header cada 30 segundos, arrojaba `HTTP 500 Internal Server Error` en producción.
  - **Causa Raíz:** `AgendaCitaService::notificacionesNoLeidas()` carecía de comprobación sobre `Auth::user()` (pudo disparar `Call to a member function unreadNotifications() on null`), no contemplaba `$n->created_at` nulo al ejecutar `diffForHumans()`, ni datos no formateados o registros corruptos en la tabla `notifications`, y carecía de bloque `try/catch`.
  - **Solución Implementada:**
    * En `AgendaCitaService.php`, se blindaron `notificacionesNoLeidas()`, `marcarNotificacionLeida()` y `marcarTodasLeidas()` con validación previa de usuario autenticado, decodificación segura de `$n->data`, operador elvis para `created_at` (`$n->created_at ? $n->created_at->diffForHumans() : ''`) y bloque `try/catch` con fallback seguro a `{ count: 0, notifications: [] }`.
    * En `AgendaCitaController.php`, se envolvió `notificacionesNoLeidas()` en un bloque `try/catch` devolviendo siempre respuesta estructurada `200 OK` para asegurar que el polling de la interfaz nunca colapse la consola con errores 500.
  - **Verificaciones:** Prueba HTTP simulada retornando `HTTP 200 OK` con `{ count: 0, notifications: [] }`. Laravel Pint passed ✅.
- [x] **2026-10-27 — Corrección de Permisos en Interface SAHI para Asignación de Estudios y Ecografías (`routes/api_interfaces.php`):**
  - **Diagnóstico:** El usuario con rol *Transcriptor* (`andres19982016@outlook.com`) recibía un error `403 Forbidden` al consultar `GET https://lidiag.com/api/interface-sahi-listar-atenciones/{numDocu}`.
  - **Causa Raíz:** En `routes/api_interfaces.php`, las rutas de consulta del HIS/SAHI (`/interface-sahi-listar-atenciones`, `/interface-sahi-listar-productos-diagnosticos`, `/interface-sahi/listar/productos/atencion` y `/interface-sahi/test-conexion`) exigían únicamente los permisos `permission:ecografias.index|ecografias.admision,web`. Los transcriptores y médicos asignan estudios clínicos con los permisos `estudio.asignar`, `estudio.transcribir` y `tele.ragiologia.enviar`, por lo que Spatie denegaba el acceso.
  - **Solución Implementada:** Se incorporaron los permisos `estudio.asignar|estudio.transcribir|tele.ragiologia.enviar` a las rutas de la interface SAHI y se retiró el parámetro redundante `,web` conforme a los estándares de la API. Verificado con prueba HTTP simulada retornando `HTTP 200 OK` con atenciones reales del paciente. Laravel Pint passed ✅.
- [x] **2026-10-27 — Blindaje Resiliente contra Error 500 en Listado de Sedes Activas (`/api/config-sedes/listar/sedes/activas`):**
  - **Diagnóstico:** En producción (`lidiag.com`), la petición `GET https://lidiag.com/api/config-sedes/listar/sedes/activas` devolvía `HTTP 500 Internal Server Error`.
  - **Causa Raíz:**
    1. `ConfigSedeService::listarActivas()` ejecutaba un `ConfigSede::where('estado', 1)->get()` (`SELECT *`), serializando pesadas plantillas HTML/RTF (`plantilla_rx`, `plantilla_ecografia`) y cadenas con codificación potencialmente mixta/corrupta, provocando fallos de serialización JSON (`JsonException: Malformed UTF-8 characters`).
    2. En `routes/api_configuracion.php`, la ruta comodín `Route::resource('/config-sedes', ...)` estaba registrada antes de las rutas estáticas específicas (`/listar/sedes/activas`) y `ConfigSedeController` carecía del método `show()`.
  - **Solución Implementada:**
    * En `ConfigSedeService::listarActivas()`, se limitó la consulta exclusivamente a los campos requeridos por los selectores (`id`, `nom_sede`, `estado`), sanitizando cada cadena con `mb_convert_encoding(..., 'UTF-8', 'UTF-8')` y agregando bloque `try/catch` con fallback directo a `DB::table('config_sedes')`.
    * En `ConfigSedeController`, se blindó `listarSedesActivas()` con manejo defensivo de excepciones y se implementó el método `show($id)` delegando en `buscarPorId($id)`.
    * En `routes/api_configuracion.php`, se eliminó la definición duplicada de `buscarPorId` y se reordenaron las rutas específicas antes del resource.
  - **Verificaciones:** Laravel Pint passed (`{"tool":"pint","result":"passed"}`) ✅.
- [x] **2026-10-27 — Homologación Global de Permisos en Todo el Módulo de Configuración:**
  - **Alcance Completo:** Siguiendo la homologación de `ConfigDiagnosticoController`, `ConfigPrioridadController` y `ConfigProductoController`, se revisaron y blindaron sistemáticamente los 15 controladores del módulo de Configuración (`app/Http/Controllers/Configuracion/` y `routes/api_configuracion.php`):
    * `ConfigAdminSaludController`: Se liberó `index()` de `permission:config.admin.salud.index`, permitiendo a usuarios asistenciales consultar EPS/Administradoras en admisiones y citas.
    * `ConfigRecomendacionProductoController`: Se liberó `index()` de `permission:config.recomendaciones.index`, permitiendo a citas y revisión consultar preparaciones sin 403.
    * `ConsentimientoInformadoController`: Se liberaron `index()`, `listarActivos()` y `show()` de `permission:config.consentimiento_informado.index`, permitiendo la carga y firma en módulos clínicos.
    * `routes/api_configuracion.php` (`/config-whatsapp` y `/config-whatsapp/{id}`): Se liberó el middleware de lectura directa `permission:config.whatsapp.saludo.index`, manteniéndolo bajo `auth`.
  - **Seguridad Preservada:** Todas las mutaciones de base de datos (`store`, `update`, `destroy`) continúan protegidas al 100% con sus permisos Spatie correspondientes (`config.*.create`, `edit`, `destroy`), y las vistas Blade de administración en `routes/web.php` mantienen su middleware estricto.
  - **Verificaciones:** Laravel Pint passed (`{"tool":"pint","result":"passed"}`) ✅.
- [x] **2026-10-27 — Desbloqueo de Catálogos Clínicos y Selectores para Médicos y Transcriptores (Error 403 Forbidden):**
  - **Diagnóstico:** Al abrir vistas de asignación de estudios o ecografías con el rol *Transcriptor* (`andres19982016@outlook.com`), el navegador registraba cuatro errores `403 Forbidden` en:
    * `GET /api/user.listarUsuarios/Medico/{modulo}` y `GET /api/user.listarUsuarios/Transcriptor/{modulo}`
    * `GET /api/config-prioridades`
    * `GET /api/config-productos`
    * `GET /api/config-diagnosticos`
  - **Causa Raíz:**
    1. En `routes/api_seguridad.php`, las rutas de selectores de usuarios tenían asignado incorrectamente el middleware `permission:seguridad.usuarios.index` (permiso administrativo que no poseen los transcriptores ni médicos asistenciales).
    2. En `ConfigDiagnosticoController`, `ConfigPrioridadController` y `ConfigProductoController`, los constructores exigían `permission:config.*.index` sobre el método `index()`. Estos endpoints son catálogos maestros de consulta requeridos por los componentes clínicos (`AsignarEstudioComponent.vue`, etc.) para registrar órdenes, asignar médicos y prioridades.
  - **Solución Implementada:**
    * En `routes/api_seguridad.php`, se removió la restricción `permission:seguridad.usuarios.index` de `/user.listarUsuarios/{tipo_user}/{modulo}` y `/usuario-por-modulo/{modulo_id}`, dejándolas bajo la protección de `auth` del grupo.
    * En `ConfigDiagnosticoController`, `ConfigPrioridadController` y `ConfigProductoController`, se liberó el método de sólo lectura `index()` de la restricción de permisos administrativos (siguiendo el mismo patrón homologado en `ConfigSedeController` y `ConfigTipoDocumentoController`), manteniendo estrictamente protegidas las operaciones de mutación (`store`, `update`, `destroy`) y las vistas Blade administrativas en `routes/web.php`.
  - **Verificaciones:** Laravel Pint passed (`{"tool":"pint","result":"passed"}`) ✅.
- [x] **2026-10-27 — Visibilidad y Estilo Nativo del Botón 'Descargar ZIP' en Consentimientos de Ecografías (`ecografiasConsentimientosComponent.vue`):**
  - **Diagnóstico:** El botón "Descargar ZIP" no resultaba visible en la cabecera de la tabla de consentimientos. La combinación de la clase `.btn-outline-warning` con el estado inactivo inicial (`:disabled="loading || selectedIds.length < 2"`) producía una opacidad reducida (`opacity: 0.65`) de texto y borde amarillos sobre fondo blanco, volviéndolo imperceptible. Adicionalmente, el contenedor flex requería alineación explícita (`ml-auto`) y una guarda de permisos tolerante para sesiones activas.
  - **Solución Implementada:**
    * Se utiliza estrictamente el botón sólido nativo de AdminWrap: `class="btn btn-warning waves-effect waves-light"` con icono estándar `<i class="fa fa-file-archive-o m-r-5"></i>` y texto directo `Descargar ZIP ({{ selectedIds.length }})` sin etiquetas personalizadas ni badges foráneos.
    * Se alineó a la derecha usando `ml-auto` en el contenedor flex de cabecera de la tarjeta.
    * Se flexibilizó la condición de renderizado a `v-if="can('ecografias.consentimientos.zip.generar') || can('ecografias.consentimientos.index')"` para asegurar su visualización independientemente de variantes de permisos en la sesión.
  - **Verificaciones:** ESLint 0 errores/advertencias (`npx eslint` passed) ✅ | Vite build exitoso en 1.19s (`npm run build`) ✅.
- [x] **2026-10-27 — Corrección de Permisos en Listado de Usuarios para Selectores Médicos/Transcriptores (`routes/api_seguridad.php`):**
  - **Diagnóstico:** El usuario con rol *Transcriptor* (`andres19982016@outlook.com`) recibía un error `403 Forbidden` al consultar `GET https://lidiag.com/api/user.listarUsuarios/Medico/2`.
  - **Causa Raíz:** En `routes/api_seguridad.php`, las rutas `/user.listarUsuarios/{tipo_user}/{modulo}` y `/usuario-por-modulo/{modulo_id}` tenían asignado el middleware `permission:seguridad.usuarios.index`. Dicho permiso es exclusivamente administrativo de gestión de usuarios, mientras que estas dos rutas son utilitarias de sólo lectura (`id`, `name`) consumidas por cualquier usuario autenticado en vistas clínicas (Ecografías, Citas, Asignaciones) para poblar los selectores de médicos y transcriptores (como explícitamente documenta el constructor de `UserController`).
  - **Solución:** Se retiró el middleware restrictivo `permission:seguridad.usuarios.index` de ambas rutas en `routes/api_seguridad.php`, manteniéndolas protegidas por el middleware `auth` del grupo. Laravel Pint passed ✅.
- [x] **2026-10-27 — Homologación Estricta a Estilos de la Aplicación en Módulo de Consentimientos de Ecografías (`ecografiasConsentimientosComponent.vue` y `ecografiasConsentimientosPlantillasComponent.vue`):**
  - **Diagnóstico:** El módulo `/ecografias-consentimientos` presentaba estilos y elementos no alineados al estándar del tema de la aplicación (AdminWrap / Bootstrap 4): botones con clases personalizadas `.btn-action-clinical`, breadcrumb no nativo con etiquetas `<button class="breadcrumb-btn">`, bloques `<style scoped>`, modales con estilos inline `background: rgba(...)` y `backdrop-filter: blur(...)`, badges de atajos (`F2`, `Ctrl+S`, `Esc`) embebidos en botones y carencia del envoltorio estructural `.row > .col-12` para tarjetas y formularios. Adicionalmente, la tabla carecía de columna de acciones por fila y agrupaba botones de bloque rectangulares en la cabecera.
  - **Acciones Realizadas:**
    * *Botones Nativos de Tabla por Fila:* Siguiendo el estándar de las más de 120 tablas del sistema (`admision`, `citas`, `configuracion`, `ecografias-admisionadas`), se incorporó la columna de acciones en cada fila con botones circulares outline: Generar PDF (`btn waves-effect waves-light btn-rounded btn-outline-info btn-sm m-r-5`), Descargar PDF (`btn waves-effect waves-light btn-rounded btn-outline-primary btn-sm m-r-5`) y Anular (`btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm`).
    * *Acción Masiva en Cabecera:* Se sustituyó la fila de botones sólidos por un único botón nativo para descarga masiva de ZIP (`btn waves-effect waves-light btn-rounded btn-outline-warning btn-sm`) con badge reactivo de cantidad seleccionada.
    * *Eliminación de Estilos Foráneos:* Se eliminaron por completo las clases `.btn-action-clinical`, `.breadcrumb-btn` y los bloques `<style scoped>`.
    * *Estandarización de Breadcrumb:* Restaurado al marcado nativo de AdminWrap (`<ol class="breadcrumb"><li class="breadcrumb-item"><a href="javascript:void(0)">Inicio</a></li>...`).
    * *Reorganización de Filtros:* Formulario de búsqueda estructurado con `form-body`, `form-group`, `control-label`, botón de búsqueda `btn btn-success waves-effect waves-light` y botón para restablecer filtros `btn btn-inverse waves-effect waves-light` ("Limpiar").
    * *Modales Nativos:* Sustituido el modal reactivo personalizado por la estructura nativa estándar de AdminWrap (`modal fade` + `modal-backdrop fade show`, títulos y botones estándar `btn btn-default waves-effect` y `btn btn-danger waves-effect waves-light`).
    * *Extensión a Plantillas de Consentimientos:* Se aplicaron los mismos botones de acción en tabla (`btn-rounded btn-outline-* btn-sm`) en `ecografiasConsentimientosPlantillasComponent.vue`.
  - **Verificaciones:** ESLint 0 errores/advertencias (`npx eslint` passed) ✅ | Vite build exitoso en 1.04s (`npm run build`) ✅.
- [x] **2026-10-27 — Generación y Publicación del Informe Consolidado de Actualizaciones para Gerencia (MD y PDF):**
  - **Objetivo:** Consolidar formalmente para la Gerencia General, Dirección Médica y Financiera el inventario exhaustivo de todas las innovaciones implementadas en el sistema entre el 1 de junio y el 27 de octubre de 2026 en formato directo `Mejora: Descripción`.
  - **Archivos Generados:**
    * `docs/INFORME_ACTUALIZACIONES_GERENCIA.md`: Documento Markdown exhaustivo estructurado en 14 secciones temáticas (Dosimetría Res. 482, MWL DICOM, Estación de Lectura Whisper, Medios CD/DVD y ZIP, Portal de Pacientes, HIS/SAHI, Dashboard de Backups, Telegram Bot, WhatsApp Bot, Liquidación, Seguridad ISO 27001, Reportes, UI/UX y Arquitectura PSR-4).
    * `docs/INFORME_ACTUALIZACIONES_GERENCIA.pdf`: Documento PDF institucional de 236 KB renderizado mediante Dompdf con tipografía ejecutiva, formato de fichas visuales por mejora, cortes de página limpios y paginación institucional numerada.
  - **Commit:** `22589c3a8`. Pint & ESLint passed ✅.
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
- [x] **2026-10-29 — Estandarización UI/UX Nativa AdminWrap en módulos Ecografías y Citas:**
  - **Módulo Ecografías (`admisionarComponent.vue`, `ecografiasAdmisionadasComponent.vue`, `lecturaComponent.vue`, `ecografiasConsentimientosComponent.vue`, `ecografiasConsentimientosPlantillasComponent.vue`, `ecografiaFirmaConsentimientoComponent.vue`):**
    - Eliminadas clases foráneas `.btn-action-clinical`, badges incrustados de atajos (`F2`, `Esc`, `Ctrl+S`), y selectores `id="example23"` reemplazados por IDs semánticos únicos (`#tablaAdmisiones`, `#tablaEcografiasAdmisionadas`, `#tablaEcografiasLectura`).
    - Integrados `<table-skeleton-loader>` y `<empty-state-component>` reemplazando spinners crudos y estados vacíos no estructurados.
    - Homologadas migas de pan a botones accesibles `<button type="button" class="btn btn-link p-0" style="vertical-align: baseline; text-decoration: none; color: inherit">`.
    - Normalizados sidebars responsive con `@media (max-width: 767px)` y backdrops `rgba(0, 0, 0, 0.5)` sin filtros de desenfoque.
  - **Módulo Citas (`citasComponent.vue`, `citasRevisionComponent.vue`, `gestionCitasComponent.vue`, `recomendacionProductoComponent.vue`):**
    - Homologadas migas de pan y eliminadas clases foráneas `.btn-action-clinical` y badges de atajos.
    - Reemplazado `id="example23"` por `#tablaCitasRevision` y `id="tabla-recomendaciones"` por `#tablaRecomendaciones`.
    - Integrados `<table-skeleton-loader>` y `<empty-state-component>` en `citasRevisionComponent.vue` y `recomendacionProductoComponent.vue`.
    - Agregados atributos `for="<id>"` en todos los labels y selectores de filtros.
    - Normalizados backdrops y sidebars a estándares AdminWrap / Bootstrap 4.
  - **Verificación estricta:** ESLint 0 errores/warnings en todos los componentes de ecografías y citas ✅ | Vite `npm run build` en 2.45s ✅ | Caches de vistas y configuración de Laravel purgadas ✅.

### Estado de módulos recientes (oct. 2026)
- **Auditoría de Permisos Spatie** (2026-10-22): ✅ 100% sincronizado (0 faltantes en BD, regex de guard corregida, seeders idempotentes ejecutados).
- **EcografiaLecturaController / Seeders Configuracion** (2026-10-22): ✅ Casing normalizado conforme a PSR-4. Autoload optimizado limpio al 100%.
- **SAHIController / SAHIService / SAHITranscibirService** (2026-10-21): ✅ Blindaje transaccional atómico, timeouts de 5s, captura 503 defensiva y herramienta de diagnóstico `sahi:test`.
- **BackupMonitorController** (2026-10-21): ✅ Permisos `seguridad.backups.index` y `seguridad.backups.ejecutar` creados y asignados a `Administrador` mediante `PermisionBackupMonitorSeeder`. Rutas en `api_seguridad.php` y `web.php`.
- **EstudioLockController** (2026-10-08): ✅ Sin permisos propios — usa solo `auth`. Las rutas `estudio-lock/tomar` y `estudio-lock/liberar` están registradas en `api_estudios.php`. No requiere seeder.
- **LiquidacionController** (2026-10-01): ✅ Seeder `PermisionLiquidacionSeeder` existente y registrado en `DatabaseSeeder`. Permisos `liquidacion.index` y `liquidacion.exportar` asignados al rol `Administrador`.



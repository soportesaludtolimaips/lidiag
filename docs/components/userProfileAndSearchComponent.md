# Documentación: userProfileAndSearchComponent.vue

## Descripción General

Componente de barra de navegación superior (navbar) que agrupa tres funcionalidades clave del sistema LIDiag en el header:

1. **Notificaciones de Helpdesk** — dropdown con campanita y badge de no leídos
2. **Selector de Sede y Médico** — mega-menu para establecer contexto de trabajo
3. **Perfil de usuario** — dropdown con "Mi Perfil", "Configuración" y "Logout"

Ruta: `resources/js/components/comunes/userProfileAndSearchComponent.vue`

---

## Props

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `usuarioactual` | Object | `null` | Usuario autenticado (Laravel Auth). Usa `.name` y `.email` |
| `perfilUrl` | String | `""` | URL personalizada para "Mi Perfil". Si vacío usa `/seguridad.perfil` |

---

## Funcionalidades

### 1. Notificaciones Helpdesk

**Template (líneas 6-54):**
- Ícono campanita `<i class="icon-Bell">` con animación de "heartbit/point" si hay no leídos
- Dropdown muestra hasta 10 notificaciones (limit: 10 en API)
- Cada item: ícono (check si leída, bell si no), folio del ticket, mensaje, tiempo relativo
- Botón "Marcar todas como leídas" al final si hay no leídas
- Link "Ver todas" → `/helpdesk-notificaciones`

**Estado reactivo:**
- `hdDisponible` — controla si el ícono es visible (se oculta si 403/404 de API)
- `hdLoading` — spinner de carga
- `hdItems` — array de notificaciones
- `hdUnreadCount` — computed: cantidad de items sin `read_at`

**APIs:**
```
GET /api/helpdesks-notificaciones?limit=10   → cargarHdNotificaciones()
POST /api/helpdesk-notificaciones/{id}/read  → marcarHdLeida(n)
POST /api/helpdesk-notificaciones/read-all   → marcarHdTodas()
```

**Comportamiento:**
- Al abrir dropdown: `onOpenHdDropdown()` → recarga inmediata
- Al hacer click en item: `abrirHdNotificacion(n)` → marca leída + redirige a `/helpdesk-tickets/{ticket_id}` si existe
- Polling inteligente: cada 30s si tab visible, 90s si oculto (backoff hasta 120s)
- Listener `visibilitychange`: recarga al volver a la pestaña

---

### 2. Selector de Sede y Médico

**Template (líneas 59-92):**
- Mega-menu con ícono `<i class="icon-Box-Close">`
- Select de **Sede** (siempre visible)
- Select de **Médico** (solo visible en rutas de Ecografías)

**Estado reactivo:**
- `sedes` / `sedeSeleccionada`
- `medicos` / `medicoSeleccionado` / `mostrarMedico`
- `nomMedicoSeleccionado`

**APIs:**
```
GET /api/config-sedes/listar/sedes/activas         → listarSedes()
GET /api/config-sedes/{id}/buscarPorId            → estableserSede()
GET /api/user.listarUsuarios/Medico/2             → listarMedicos()
```

**Persistencia (sessionStorage):**
| Clave | Valor |
|-------|-------|
| `ST-sede` | ID de sede |
| `ST-nomsede` | Nombre de sede |
| `ST-urlsede` | URL Oviyam de la sede |
| `ST-servername` | Tap Oviyam |
| `ST-email_repor` | Email de reportes |
| `ST-medico` | ID de médico |
| `ST-nomMedico` | Nombre de médico |
| `ST-ecografia-medico` | ID médico (localStorage) |
| `ST-ecografia-nomMedico` | Nombre médico (localStorage) |
| `ST-modulo` | ID módulo (1=Estudios, 2=Ecografías) |

**Eventos emitidos (vía `emitter`):**
- `"sedeSeleccionada"` → `{ idSede, nomSede, url, serverName, email_repor }`
- `"medicoSeleccionado"` → `{ idMedico, nomMedico }`

Estos eventos son consumidos por otros componentes (ej. visor de imágenes, transcripción).

**Lógica de módulo:** `establecerModuloActual()` usa `window.location.pathname`:
- Si incluye "ecografia" → módulo 2
- Si incluye "estudios" o "study" → módulo 1
- Default: 1

**Rutas de Ecografías que muestran el selector de Médico:**
```
/ecografias.admisiones
/ecografias.admisionadas
/ecografias-pendientes-lecturas
/ecografias-consentimientos
/ecografias-consentimientos-plantillas
```

---

### 3. Perfil de Usuario

**Template (líneas 96-141):**
- Imagen de avatar (estática: `admin-wrap/assets/images/users/1.jpg`)
- Nombre de usuario (oculto en md-down)
- Dropdown con:
  - Caja de info (avatar + nombre + email)
  - "Mi Perfil" → `:href="perfilUrl || '/seguridad.perfil'"`
  - "Configuración de cuenta" (placeholder, `#`)
  - "Logout" → `Logout()`

**Logout (líneas 204-221):**
1. `POST /logout` (Laravel UI Auth::routes)
2. En `finally`: purga TODAS las claves `ST-*` en sessionStorage y localStorage
3. Redirige a `/` (login)

Esto previene que el siguiente usuario herede sede/médico del anterior (aislamiento de PHI).

---

## Ciclo de Vida

**onMounted:**
1. `establecerModuloActual()` — set sessionStorage `ST-modulo`
2. `listarSedes()` — carga sedes activas
3. `listarMedicos()` — carga médicos del módulo
4. `cargarHdNotificaciones()` — carga inicial de notificaciones
5. Inicia polling inteligente (30s visible / 90s oculto)
6. Agrega listener `visibilitychange`
7. Determina si mostrar selector de médico según ruta actual

**onBeforeUnmount:**
- `clearTimeout(hdIntervalId)` — detiene polling
- `removeEventListener('visibilitychange')` — limpia listener

---

## Dependencias

| Dependencia | Uso |
|-------------|-----|
| `vue` | `ref`, `computed`, `onMounted`, `onBeforeUnmount`, `getCurrentInstance` |
| `axios` | Llamadas HTTP |
| `window.dayjs` | Formato de tiempo en notificaciones (`tz("America/Bogota")`) |
| `window.$` (jQuery) | Selector de opción seleccionada en selects |
| `emitter` (global) | Eventos entre componentes |

---

## Notas Técnicas

1. **Imagen de avatar hardcodeada**: usa `/users/1.jpg` estática, no la foto real del usuario
2. **Médico auto-seleccionado**: si solo hay 1 médico y no hay selección previa, se elige automáticamente
3. **Polling con backoff**: reduce frecuencia cuando la pestaña está oculta (ahorro de recursos)
4. **Aislamiento de sesión**: el logout purga TODO el contexto `ST-*` para evitar fugas de PHI entre usuarios
5. **Fallback de notificaciones**: si la API responde 403/404, el ícono se oculta silenciosamente (`hdDisponible = false`)

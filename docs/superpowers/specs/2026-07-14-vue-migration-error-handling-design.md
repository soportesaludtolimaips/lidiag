# Vue Migration + Error Handling + Toast Standardization

> **Date:** 2026-07-14
> **Status:** Approved
> **Goal:** Unificar manejo de errores HTTP→toast, migrar 63 componentes Vue de Options API a Composition API, y centralizar exception handling en backend.

---

## 1. Backend: Exception Handling Centralizado

### 1.1 App\Exceptions\Handler

Agregar `renderable()` callback que captura cualquier `Throwable` no manejado y devuelve el formato JSON estandarizado:

```json
{ "success": false, "data": null, "message": "...", "status": 500 }
```

Esto asegura que incluso errores no capturados por servicios tengan el mismo shape que los controllers ya devuelven en `response()->json($result, $result['status'])`.

### 1.2 BaseService::transaction()

Ya implementado. Captura `Throwable` dentro de `DB::transaction()`, loggea el error y retorna `$this->error('Error interno del servidor', 500)`.

### 1.3 Códigos HTTP

Los helpers de `BaseService` ya retornan códigos correctos:
- `success()` → 200
- `created()` → 201
- `deleted()` → 200
- `error()` → 400
- `notFound()` → 404
- `unauthorized()` → 403
- `validationError()` → 422

No se modifican. El backend ya tipifica correctamente.

---

## 2. Frontend: Axios Interceptor + Toast

### 2.1 Response Interceptor (success)

Se agrega al interceptor existente en `resources/js/bootstrap.js`:

- Si `response.data?.message` existe y el método NO es GET, muestra toast `success` automáticamente
- Si `response.status === 201`, muestra toast con icon `success`
- No interfiere con requests GET (listados)

### 2.2 Error Interceptor (mejora)

Se actualiza el interceptor de error existente para mostrar toasts automáticos según el código HTTP:

| Status | heading | icon | Comportamiento |
|--------|---------|------|----------------|
| 400 | "Solicitud incorrecta" | `warning` | Toast + reject |
| 401 | "Sesión expirada" | `warning` | Toast + redirect a /login (ya existe) |
| 403 | "No autorizado" | `error` | Toast + reject |
| 404 | "No encontrado" | `warning` | Toast + reject |
| 422 | "Error de validación" | `warning` | Toast con primer error + reject |
| 429 | "Demasiadas solicitudes" | `warning` | Toast + reject |
| 500 | "Error interno" | `error` | Toast + reject |
| 503 | "Servicio no disponible" | `error` | Toast + reject |
| otro | "Error inesperado" | `error` | Toast + reject |

El interceptor NO re-throws con toast visible (evita duplicados). Solo hace `return Promise.reject(error)`.

### 2.3 useToast Composable

Se crea `resources/js/composables/useToast.js`:

```js
export function useToast() {
    const defaults = { position: 'top-right', hideAfter: 3500, stack: 6 };
    
    function show(opts) {
        return window.$.toast({ ...defaults, ...opts });
    }
    
    function success(text, heading = 'Ok') {
        show({ heading, text, icon: 'success', loaderBg: '#28a745' });
    }
    
    function error(text, heading = 'Error') {
        show({ heading, text, icon: 'error', loaderBg: '#dc3545' });
    }
    
    function warning(text, heading = 'Atención') {
        show({ heading, text, icon: 'warning', loaderBg: '#ffc107' });
    }
    
    function info(text, heading = 'Información') {
        show({ heading, text, icon: 'info', loaderBg: '#17a2b8' });
    }

    return { show, success, error, warning, info };
}
```

### 2.4 handleAxiosErrorToast (deprecado)

Se deja como wrapper que llama al interceptor interno. Los componentes nuevos deben usar `useToast()` y depender del interceptor automático.

---

## 3. Migración Vue Options → Composition API

### 3.1 Estrategia

Script automatizado que transforma el 70% mecánico, + revisión manual del 30% restante.

### 3.2 Patrón de transformación

**Origen (Options API):**
```vue
<script>
import { handleAxiosErrorToast } from "@/helpers/alerts";
export default {
    props: { usuarioactual: Object, permisos: Array },
    data() { return { registros: [], loading: false, error: null }; },
    computed: { totalItems() { return this.registros.length; } },
    mounted() { this.cargarDatos(); },
    beforeUnmount() { this.limpiar(); },
    methods: {
        async cargarDatos() {
            this.loading = true;
            try {
                const res = await axios.get('/api/data');
                this.registros = res.data.data;
            } catch(e) { handleAxiosErrorToast(e); }
            finally { this.loading = false; }
        },
        limpiar() { this.registros = []; },
    }
};
</script>
```

**Destino (Composition API con `<script setup>`):**
```vue
<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import axios from 'axios';
const props = defineProps({ usuarioactual: Object, permisos: Array });
const registros = ref([]);
const loading = ref(false);
const error = ref(null);
const totalItems = computed(() => registros.value.length);
async function cargarDatos() {
    loading.value = true;
    try {
        const res = await axios.get('/api/data');
        registros.value = res.data.data;
    } catch(e) { /* interceptor handles toast */ }
    finally { loading.value = false; }
}
function limpiar() { registros.value = []; }
onMounted(cargarDatos);
onBeforeUnmount(limpiar);
</script>
```

### 3.3 Script de transformación

PowerShell script `scripts/migrate-vue.ps1` que por cada `.vue` file:

1. Lee el export default object
2. Extrae: `props`, `data()`, `computed`, `methods`, `watch`, `mounted`, `beforeUnmount`, `created`, `components`, `emits`
3. Genera el `<script setup>` equivalente:
   - `props` → `defineProps()`
   - `emits` → `defineEmits()`
   - `data()` keys → `ref()` / `reactive()` (objetos anidados)
   - `computed` → `computed(() => ...)`
   - `methods` → funciones planas reemplazando `this.` por `nombre.` (con `.value` para refs)
   - `mounted` → `onMounted(() => ...)`
   - `beforeUnmount` → `onBeforeUnmount(() => ...)`
   - `watch` → `watch()`
   - `components` → imports al inicio
4. Mueve todos los `import` al inicio del `<script setup>`
5. Preserva el template y styles exactamente igual

### 3.4 Reglas de transformación

| Origen | Destino | Notas |
|--------|---------|-------|
| `this.x` (data) | `x.value` | Solo si x es primitivo/array |
| `this.x` (computed) | `x.value` | |
| `this.x()` (method) | `x()` | Llamada directa |
| `this.$emit('evt', val)` | `emit('evt', val)` | `emit` via `defineEmits()` |
| `this.$refs.x` | `x.value` | `const x = ref(null);` con `ref="x"` |
| `this.$nextTick(fn)` | `nextTick(fn)` | Importar de vue |
| `this.$forceUpdate()` | Eliminar | No necesario en `<script setup>` |

### 3.5 Orden de migración

1. **Batch 1 (13 componentes — Configuración):** configSede, configProducto, configPrioridad, configDiagnostico, configAdminSalud, configWhatsapp, configTipoDocumento, configServicioCita, configServicioProducto, configPlantilla, configTipoPlantilla, configCitaRechazoMotivo, configConsentimientoInformado
2. **Batch 2 (8 componentes — HelpDesk):** helpdeskTickets, helpdeskCrearTicket, helpdeskDetalleTicket, helpdeskCategorias, helpdeskEstados, helpdeskPrioridades, helpdeskNotificaciones, helpdeskReportes
3. **Batch 3 (8 componentes — Estudios + Ecografías):** AsignarEstudio, estudioAsignados, leerEstudio, transcribirEstudio, notificarEstudio, audio-texto, admisionar, ecografiasAdmisionadas, lectura, ecografiasConsentimientos, ecografiasConsentimientosPlantillas, ecografiaFirmaConsentimiento
4. **Batch 4 (8 componentes — Seguridad + Reportes + Citas + Agenda + Otros):** usuario, role, auditLog, usuarioPerfil, reporteProduccion, citas, gestionCitas, citasRevision, citasNotificationBadge, recomendacionProducto, agendaDisponibilidad, teleRadiologiaEnviarEstudio, teleRadiologiaTranscibir, OtrasPlataformasAsignarEstudio
5. **Batch 5 (9 componentes — Shared/Commons + Auth + Dashboard):** login, dashboard, sidebar, footer, formComponent, formPaciente, datosPacienteDisplay, userProfileAndSearch, toggleAndNavItems, logo, LoadingSpinner, VoiceModal, Select2, FormattedTextEditor, EmojiPicker, App, Auth, seleccionarSede

### 3.6 Verificación

Cada componente migrado debe:
1. Mantener el template sin cambios
2. Pasar `vue-loader` build sin errores
3. El script reemplaza imports de `handleAxiosErrorToast` por `import axios from 'axios'` cuando ya no se necesita

---

## 4. Orden de Implementación

1. **Exception Handler** (backend, 1 archivo)
2. **Interceptor + useToast** (frontend, 2 archivos)
3. **Batch 1: Configuración** (~13 componentes, los más simples)
4. **Batch 2: HelpDesk** (~8 componentes)
5. **Batch 3: Estudios + Ecografías** (~12 componentes)
6. **Batch 4: Seguridad + Reportes + Citas + Agenda** (~13 componentes)
7. **Batch 5: Shared/Commons/Auth** (~17 componentes)

Cada Batch verifica con `npm run build` o revisión manual del componente transformado.

---

## 5. Archivos afectados

### Backend
- `app/Exceptions/Handler.php` — renderable() callback

### Frontend
- `resources/js/bootstrap.js` — interceptor Axios mejorado
- `resources/js/composables/useToast.js` — NUEVO
- ~63 archivos `.vue` — migración Options → Composition API
- `resources/js/helpers/alerts.js` — simplificar handleAxiosErrorToast

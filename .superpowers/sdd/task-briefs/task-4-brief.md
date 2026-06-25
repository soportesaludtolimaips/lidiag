# Task 4: Wire ecografiasAdmisionadasComponent.vue and admisionarComponent.vue

## Files
- Modify: `resources/js/components/modulos/ecografias/ecografiasAdmisionadasComponent.vue`
- Modify: `resources/js/components/modulos/ecografias/admisionarComponent.vue`

## Context
Both components have identical inline SAHI patterns:
- `sedeInterface` data property (boolean)
- `establecerSede()` method with inline axios call
- `consultarEstadoSahi(item)` method with inline axios GET
- `reenviarASahi(item)` method with inline axios POST
- `isConsultandoSahi: {}` and `isEnviandoSahi: {}` data properties (per-item maps)

The composables use different naming: `isConsultando` and `isEnviando` (no "Sahi" suffix).

## Changes Required (apply to BOTH files)

### 1. Add imports and `setup()` at the top of the `<script>` section

After the last existing import, add:
```js
import { useSede } from "@/composables/useSede";
import { useSahiInterface } from "@/composables/useSahiInterface";
```

Add `setup()` function before `data()`:
```js
setup() {
    const { sedeInterface, establecerSede } = useSede();
    return { sedeInterface, establecerSede };
},
```

### 2. Remove from `data()`

Remove `sedeInterface: false` from the data() return object.

Keep `isConsultandoSahi: {}` and `isEnviandoSahi: {}` — these are per-item maps, not the composable's booleans. They have different names so no collision.

### 3. Replace `establecerSede()` method

Current pattern (varies slightly between files):
```js
async establecerSede() {
    const sede = sessionStorage.getItem("ST-sede");
    if (sede != null) {
        this.busqueda.sede_id = sede;
    }
    try {
        const res = await axios.get(`/api/config-sedes/${this.busqueda.sede_id}/buscarPorId`);
        this.sedeInterface = res?.data?.interface_software === "SAHI";
    } catch (error) {
        this.sedeInterface = false;
    }
},
```

Replace with:
```js
async establecerSede() {
    const sede = sessionStorage.getItem("ST-sede");
    if (sede != null) {
        this.busqueda.sede_id = sede;
    }
    await this.establecerSede(); // composable call
},
```

### 4. Replace `consultarEstadoSahi(item)` method

Current pattern (same in both files):
```js
async consultarEstadoSahi(item) {
    if (!this.sedeInterface) return;
    if (!item?.ecografia_producto_id) return;

    const id = item.ecografia_producto_id;
    try {
        this.setMapValue("isConsultandoSahi", id, true);
        const res = await axios.get(
            `/api/interface-sahi-ecografias/estado/ecografia-producto/${id}`
        );
        const d = res.data?.data || {};
        item.estado_envio_sahi = d.estado_envio_sahi ?? item.estado_envio_sahi;
        item.fechor_envio_sahi = d.fechor_envio_sahi ?? item.fechor_envio_sahi;
        item.error_envio_sahi = d.error_envio_sahi ?? null;
        item.sahi_esquema_149 = d.sahi_esquema_149 ?? item.sahi_esquema_149;
        item.sahi_esquema_150 = d.sahi_esquema_150 ?? item.sahi_esquema_150;
        this.safeDestroyDataTable(this.$refs.dt || "#example23");
        this.safeInitDataTable(this.$refs.dt || "#example23");
    } catch (error) {
        $.toast({
            heading: "SAHI",
            text: error.response?.data?.message || "No se pudo consultar el estado SAHI.",
            position: "top-right",
            loaderBg: "#ff6849",
            icon: "warning",
            hideAfter: 3500, stack: 6,
        });
    } finally {
        this.setMapValue("isConsultandoSahi", id, false);
    }
},
```

Replace with:
```js
async consultarEstadoSahi(item) {
    if (!this.sedeInterface) return;
    if (!item?.ecografia_producto_id) return;

    const id = item.ecografia_producto_id;
    try {
        this.setMapValue("isConsultandoSahi", id, true);
        const estado = await this.consultarEstado(id);
        item.estado_envio_sahi = estado.estado_envio_sahi ?? item.estado_envio_sahi;
        item.fechor_envio_sahi = estado.fechor_envio_sahi ?? item.fechor_envio_sahi;
        item.error_envio_sahi = estado.error_envio_sahi ?? null;
        item.sahi_esquema_149 = estado.sahi_esquema_149 ?? item.sahi_esquema_149;
        item.sahi_esquema_150 = estado.sahi_esquema_150 ?? item.sahi_esquema_150;
        this.safeDestroyDataTable(this.$refs.dt || "#example23");
        this.safeInitDataTable(this.$refs.dt || "#example23");
    } catch (error) {
        $.toast({
            heading: "SAHI",
            text: error.response?.data?.message || "No se pudo consultar el estado SAHI.",
            position: "top-right",
            loaderBg: "#ff6849",
            icon: "warning",
            hideAfter: 3500, stack: 6,
        });
    } finally {
        this.setMapValue("isConsultandoSahi", id, false);
    }
},
```

### 5. Replace `reenviarASahi(item)` method

Current pattern (same in both files):
```js
async reenviarASahi(item) {
    if (!this.sedeInterface) return;
    if (!item?.ecografia_producto_id) return;
    const id = item.ecografia_producto_id;
    try {
        this.setMapValue("isEnviandoSahi", id, true);
        item.estado_envio_sahi = "ENVIANDO";
        item.error_envio_sahi = null;
        const res = await axios.post(
            "/api/interface-sahi-ecografias/enviar/ecografia-producto",
            { ecografia_producto_id: id }
        );
        $.toast({
            heading: "SAHI",
            text: res.data?.message || "Envío a SAHI encolado.",
            position: "top-right",
            loaderBg: "#ff6849", icon: "info",
            hideAfter: 3500, stack: 6,
        });
        this.safeDestroyDataTable(this.$refs.dt || "#example23");
        this.safeInitDataTable(this.$refs.dt || "#example23");
    } catch (error) {
        item.estado_envio_sahi = "ERROR";
        item.error_envio_sahi = error.response?.data?.message || "No se pudo reenviar a SAHI.";
        $.toast({
            heading: "SAHI",
            text: item.error_envio_sahi,
            position: "top-right",
            loaderBg: "#ff6849", icon: "warning",
            hideAfter: 4500, stack: 6,
        });
    } finally {
        this.setMapValue("isEnviandoSahi", id, false);
    }
},
```

Replace with:
```js
async reenviarASahi(item) {
    if (!this.sedeInterface) return;
    if (!item?.ecografia_producto_id) return;
    const id = item.ecografia_producto_id;
    try {
        this.setMapValue("isEnviandoSahi", id, true);
        item.estado_envio_sahi = "ENVIANDO";
        item.error_envio_sahi = null;
        await this.enviarEcografiaProducto(id);
        $.toast({
            heading: "SAHI",
            text: "Envío a SAHI encolado.",
            position: "top-right",
            loaderBg: "#ff6849", icon: "info",
            hideAfter: 3500, stack: 6,
        });
        this.safeDestroyDataTable(this.$refs.dt || "#example23");
        this.safeInitDataTable(this.$refs.dt || "#example23");
    } catch (error) {
        item.estado_envio_sahi = "ERROR";
        item.error_envio_sahi = error.response?.data?.message || "No se pudo reenviar a SAHI.";
        $.toast({
            heading: "SAHI",
            text: item.error_envio_sahi,
            position: "top-right",
            loaderBg: "#ff6849", icon: "warning",
            hideAfter: 4500, stack: 6,
        });
    } finally {
        this.setMapValue("isEnviandoSahi", id, false);
    }
},
```

### 6. Add `consultarEstado` to setup return

Since the methods above use `this.consultarEstado()` and `this.enviarEcografiaProducto()`, the setup() needs to include them:

Change setup() to:
```js
setup() {
    const { sedeInterface, establecerSede } = useSede();
    const { consultarEstado, enviarEcografiaProducto } = useSahiInterface();
    return { sedeInterface, establecerSede, consultarEstado, enviarEcografiaProducto };
},
```

## Verification
For both files:
1. Two new imports present
2. `setup()` function includes both composables
3. `sedeInterface` removed from `data()`
4. `establecerSede()` no longer has inline axios call
5. `consultarEstadoSahi()` uses `this.consultarEstado(id)`
6. `reenviarASahi()` uses `this.enviarEcografiaProducto(id)`
7. All template references unchanged (sedeInterface, consultarEstadoSahi, reenviarASahi stay the same)

## No JS tests exist
Manual verification only.

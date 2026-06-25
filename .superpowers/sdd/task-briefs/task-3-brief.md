# Task 3: Wire lecturaComponent.vue with composables

## Files
- Modify: `resources/js/components/modulos/ecografias/lecturaComponent.vue`

## Context
This is a 1846-line Vue 2 Options API component that has inline SAHI integration logic (sending, polling, status checking). We need to add a `setup()` function that imports and calls the composables created in Tasks 1-2, then delegate the SAHI methods to the composable.

The import path for composables is `@/composables/useSede` and `@/composables/useSahiInterface`.

## Changes Required

### 1. Add imports (after line 705)

After `import medicalTermsRaw from "@/data/terminos_medicos.txt?raw";`, add:
```js
import { useSede } from "@/composables/useSede";
import { useSahiInterface } from "@/composables/useSahiInterface";
```

### 2. Update `setup()` (line 717-720)

Change from:
```js
setup(props) {
    const { can } = usePermissions(props.permisos);
    return { can };
},
```
To:
```js
setup(props) {
    const { can } = usePermissions(props.permisos);
    const { sedeInterface, establecerSede } = useSede();
    const {
        isEnviando, isConsultando,
        enviarEcografiaProducto, consultarEstado,
        startPolling, stopPolling,
    } = useSahiInterface();
    return { can, sedeInterface, establecerSede, isEnviando, isConsultando, enviarEcografiaProducto, consultarEstado, startPolling, stopPolling };
},
```

### 3. Remove from `data()` (lines 797, 811, 814-816)

Remove these lines from the `data()` return object:
```
sedeInterface: false,           // line 797
isEnviandoSahi: false,          // line 811
isConsultandoSahi: false,       // line 814
sahiPollTimer: null,            // line 815
sahiPollCount: 0,               // line 816
```

### 4. Replace `establecerSede()` method

The current method is:
```js
async establecerSede() {
    this.registro.sede_id = sessionStorage.getItem("ST-sede");
    this.busqueda.sede_id = sessionStorage.getItem("ST-sede");
    this.sedeActual = sessionStorage.getItem("ST-sede");
    try {
        const res = await axios.get(`/api/config-sedes/${this.sedeActual}/buscarPorId`);
        this.sedeInterface = res?.data?.interface_software === "SAHI";
    } catch (error) {
        this.sedeInterface = false;
    }
    this.registros = [];
},
```

Replace with:
```js
async establecerSede() {
    this.registro.sede_id = sessionStorage.getItem("ST-sede");
    this.busqueda.sede_id = sessionStorage.getItem("ST-sede");
    this.sedeActual = sessionStorage.getItem("ST-sede");
    await this.establecerSede(); // composable call
    this.registros = [];
},
```

### 5. Replace `enviarASahiEcografiaProducto()` method

Current method sends to SAHI after lectura. Replace with:
```js
async enviarASahiEcografiaProducto() {
    if (!this.sedeInterface) return true;

    const id = this.registro.ecografia_producto_id;
    if (!id) {
        $.toast({
            heading: "Advertencia",
            text: "No se encontró ecografia_producto_id para enviar a SAHI.",
            position: "top-right",
            loaderBg: "#ff6849",
            icon: "warning",
            hideAfter: 3500, stack: 6,
        });
        return false;
    }

    try {
        this.registro.estado_envio_sahi = "ENVIANDO";
        const res = await this.enviarEcografiaProducto(id);

        const encolado = res.status === 202;
        $.toast({
            heading: "SAHI",
            text: res.data?.message || (encolado ? "Envío a SAHI encolado." : "Enviado a SAHI correctamente."),
            position: "top-right",
            loaderBg: "#ff6849",
            icon: encolado ? "info" : "success",
            hideAfter: 3500, stack: 6,
        });

        this.registro.estado_envio_sahi = encolado ? "ENVIANDO" : "ENVIADO";
        this.registro.fechor_envio_sahi = new Date().toISOString();
        this.registro.error_envio_sahi = null;
        if (!encolado && res.data?.data) {
            this.registro.sahi_esquema_149 = res.data.data.IdEsquemaEncabezado149 ?? this.registro.sahi_esquema_149;
            this.registro.sahi_esquema_150 = res.data.data.IdEsquemaEncabezado150 ?? this.registro.sahi_esquema_150;
        }

        if (encolado) {
            const onUpdate = (estado) => {
                if (estado.estado_envio_sahi != null) this.registro.estado_envio_sahi = estado.estado_envio_sahi;
                this.registro.fechor_envio_sahi = estado.fechor_envio_sahi ?? this.registro.fechor_envio_sahi;
                this.registro.error_envio_sahi = estado.error_envio_sahi ?? null;
                this.registro.sahi_esquema_149 = estado.sahi_esquema_149 ?? this.registro.sahi_esquema_149;
                this.registro.sahi_esquema_150 = estado.sahi_esquema_150 ?? this.registro.sahi_esquema_150;
            };
            const onError = (error) => {
                this.registro.estado_envio_sahi = "ERROR";
                this.registro.error_envio_sahi = error.response?.data?.message || "Error consultando SAHI.";
            };
            this.startPolling(id, onUpdate, onError);
        }

        return true;
    } catch (error) {
        $.toast({
            heading: "SAHI",
            text: error.response?.data?.message || "No se pudo enviar a SAHI.",
            position: "top-right",
            loaderBg: "#ff6849",
            icon: "warning",
            hideAfter: 4500, stack: 6,
        });
        this.registro.estado_envio_sahi = "ERROR";
        this.registro.error_envio_sahi = error.response?.data?.message || "No se pudo enviar a SAHI.";
        return false;
    }
},
```

### 6. Replace `consultarEstadoSahi()` method

```js
async consultarEstadoSahi(silent = false) {
    if (!this.sedeInterface) return;
    const id = this.registro.ecografia_producto_id;
    if (!id) return;

    try {
        const estado = await this.consultarEstado(id);
        if (estado.estado_envio_sahi != null) this.registro.estado_envio_sahi = estado.estado_envio_sahi;
        this.registro.fechor_envio_sahi = estado.fechor_envio_sahi ?? this.registro.fechor_envio_sahi;
        this.registro.error_envio_sahi = estado.error_envio_sahi ?? null;
        this.registro.sahi_esquema_149 = estado.sahi_esquema_149 ?? this.registro.sahi_esquema_149;
        this.registro.sahi_esquema_150 = estado.sahi_esquema_150 ?? this.registro.sahi_esquema_150;

        if (this.registro.estado_envio_sahi !== "ENVIANDO") {
            this.stopPolling();
        }

        if (!silent) {
            $.toast({
                heading: "SAHI",
                text: "Estado actualizado.",
                position: "top-right",
                loaderBg: "#ff6849",
                icon: "info",
                hideAfter: 2500,
            });
        }
    } catch (error) {
        if (!silent) {
            $.toast({
                heading: "SAHI",
                text: error.response?.data?.message || "No se pudo consultar el estado en SAHI.",
                position: "top-right",
                loaderBg: "#ff6849",
                icon: "warning",
                hideAfter: 3500, stack: 6,
            });
        }
    }
},
```

### 7. Replace `startSahiPolling()` and `stopSahiPolling()` methods

```js
startSahiPolling() {
    if (!this.sedeInterface) return;
    if (!this.registro?.ecografia_producto_id) return;

    const id = this.registro.ecografia_producto_id;
    const onUpdate = (estado) => {
        if (estado.estado_envio_sahi != null) this.registro.estado_envio_sahi = estado.estado_envio_sahi;
        this.registro.fechor_envio_sahi = estado.fechor_envio_sahi ?? this.registro.fechor_envio_sahi;
        this.registro.error_envio_sahi = estado.error_envio_sahi ?? null;
        this.registro.sahi_esquema_149 = estado.sahi_esquema_149 ?? this.registro.sahi_esquema_149;
        this.registro.sahi_esquema_150 = estado.sahi_esquema_150 ?? this.registro.sahi_esquema_150;
    };
    const onError = (error) => {
        this.registro.estado_envio_sahi = "ERROR";
        this.registro.error_envio_sahi = error.response?.data?.message || "Error consultando SAHI.";
    };
    this.startPolling(id, onUpdate, onError);
},
stopSahiPolling() {
    this.stopPolling();
},
```

## Verification
1. The component's `<script>` section has the two new imports
2. The `setup()` function includes both composables
3. The six SAHI-related data properties are removed from `data()`
4. The five SAHI method bodies are replaced as shown above
5. `beforeUnmount` and `beforeDestroy` still call `this.stopSahiPolling()` (unchanged)
6. The guard flow (`if (this.sedeInterface && accionActual === "TERMINAR_LECTURA")`) works via `this.sedeInterface` from setup
7. The `mostrarRegistro()` SAHI polling section still works via `this.startSahiPolling()`/`this.stopSahiPolling()`

## No JS tests exist
This project has no Vue/JS test infrastructure. Manual verification.

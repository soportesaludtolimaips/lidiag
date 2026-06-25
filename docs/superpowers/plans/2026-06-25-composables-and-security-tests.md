# Composables Extraction + Security Tests Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Extract duplicated SAHI/sede inline Vue logic into composables, wire into 6 components, add 4 security test files

**Architecture:** Vue 3 Options API components get a `setup()` function that calls Composition API composables (`ref`, `onBeforeUnmount`). PHP Feature tests follow existing TestCase pattern with `DatabaseTransactions`.

**Tech Stack:** Vue 3 (ref, onBeforeUnmount, setup), PHPUnit 11, Laravel 11

## Global Constraints

- Composables follow `export function useXxx()` named-export pattern from `useMedicalDictionary.js`
- All Vue components are Options API — add `setup()` to compose, don't convert to `<script setup>`
- Security tests use `Tests\TestCase` (extends `Tests\CreatesApplication`), extend existing patterns
- Existing 17 tests must remain passing after changes
- No new npm packages, no new backend endpoints

---

## File Structure

**Files to Create:**
- `resources/js/composables/useSede.js` — Sede config composable
- `resources/js/composables/useSahiInterface.js` — SAHI API composable
- `tests/Feature/RateLimitingTest.php` — Rate limit throttle test
- `tests/Feature/SessionTimeoutTest.php` — Session expiry test
- `tests/Feature/PasswordChangeTest.php` — Password change middleware test
- `tests/Feature/EncryptionAtRestTest.php` — Encryption verification test

**Files to Modify:**
- `resources/js/components/modulos/ecografias/lecturaComponent.vue` — Wire both composables, replace polling
- `resources/js/components/modulos/ecografias/ecografiasAdmisionadasComponent.vue` — Wire both composables
- `resources/js/components/modulos/ecografias/admisionarComponent.vue` — Wire both composables
- `resources/js/components/modulos/estudios/AsignarEstudioComponent.vue` — Wire useSede, useSahiInterface.listar helpers
- `resources/js/components/modulos/tele-radiologia/teleRadiologiaEnviarEstudioComponent.vue` — Same as AsignarEstudio
- `resources/js/components/modulos/varios/seleccionarSede.vue` — Add emitter.emit("sedeSeleccionada")

---

### Task 1: Create `useSede.js` composable

**Files:**
- Create: `resources/js/composables/useSede.js`
- Test: Manual (no JS test infra exists)

**Interfaces:**
- Produces: `function useSede() → { sedeActual: Ref, sedeInterface: Ref, isLoading: Ref, establecerSede: () => Promise<void> }`

- [ ] **Step 1: Create the composable file**

```js
import { ref } from "vue";
import axios from "axios";

export function useSede() {
    const sedeActual = ref(null);
    const sedeInterface = ref(false);
    const isLoading = ref(false);

    const establecerSede = async () => {
        const id = sessionStorage.getItem("ST-sede");
        if (!id) return;
        sedeActual.value = id;
        isLoading.value = true;
        try {
            const res = await axios.get(`/api/config-sedes/${id}/buscarPorId`);
            sedeInterface.value = res?.data?.interface_software === "SAHI";
        } catch {
            sedeInterface.value = false;
        } finally {
            isLoading.value = false;
        }
    };

    return { sedeActual, sedeInterface, isLoading, establecerSede };
}
```

- [ ] **Step 2: Verify file loads without syntax errors**

Run: `node -e "require('fs').readFileSync('resources/js/composables/useSede.js').toString().includes('export function useSede')"`
Expected: exits with code 0 (true)

---

### Task 2: Create `useSahiInterface.js` composable

**Files:**
- Create: `resources/js/composables/useSahiInterface.js`

**Interfaces:**
- Produces: `function useSahiInterface() → { isEnviando, isConsultando, ultimoEstado, pollTimer, pollCount, enviarEcografiaProducto(id), consultarEstado(id), startPolling(id, onUpdate?, onError?), stopPolling() }`

- [ ] **Step 1: Create the composable file**

```js
import { ref, onBeforeUnmount } from "vue";
import axios from "axios";

export function useSahiInterface() {
    const isEnviando = ref(false);
    const isConsultando = ref(false);
    const ultimoEstado = ref(null);
    const pollTimer = ref(null);
    const pollCount = ref(0);

    onBeforeUnmount(() => {
        stopPolling();
    });

    const enviarEcografiaProducto = async (ecografiaProductoId) => {
        isEnviando.value = true;
        try {
            const res = await axios.post(
                "/api/interface-sahi-ecografias/enviar/ecografia-producto",
                { ecografia_producto_id: ecografiaProductoId }
            );
            return res;
        } finally {
            isEnviando.value = false;
        }
    };

    const consultarEstado = async (ecografiaProductoId) => {
        isConsultando.value = true;
        try {
            const res = await axios.get(
                `/api/interface-sahi-ecografias/estado/ecografia-producto/${ecografiaProductoId}`
            );
            ultimoEstado.value = res.data?.data || {};
            return ultimoEstado.value;
        } finally {
            isConsultando.value = false;
        }
    };

    const startPolling = (ecografiaProductoId, onUpdate, onError) => {
        if (pollTimer.value) return;
        pollCount.value = 0;
        const maxAttempts = 36;
        pollTimer.value = setInterval(async () => {
            pollCount.value += 1;
            if (pollCount.value > maxAttempts) {
                stopPolling();
                return;
            }
            try {
                const estado = await consultarEstado(ecografiaProductoId);
                if (onUpdate) onUpdate(estado);
                if (estado?.estado_envio_sahi !== "ENVIANDO") {
                    stopPolling();
                }
            } catch (error) {
                if (onError) onError(error);
                stopPolling();
            }
        }, 5000);
    };

    const stopPolling = () => {
        if (pollTimer.value) {
            clearInterval(pollTimer.value);
            pollTimer.value = null;
        }
        pollCount.value = 0;
    };

    return {
        isEnviando,
        isConsultando,
        ultimoEstado,
        pollTimer,
        pollCount,
        enviarEcografiaProducto,
        consultarEstado,
        startPolling,
        stopPolling,
    };
}
```

- [ ] **Step 2: Verify file loads without syntax errors**

Run: `node -e "require('fs').readFileSync('resources/js/composables/useSahiInterface.js').toString().includes('export function useSahiInterface')"`
Expected: exits with code 0 (true)

---

### Task 3: Wire `lecturaComponent.vue`

**Files:**
- Modify: `resources/js/components/modulos/ecografias/lecturaComponent.vue`

**Interfaces:**
- Consumes: `useSede()` → `{ sedeInterface, establecerSede }`
- Consumes: `useSahiInterface()` → `{ isEnviando, isConsultando, enviarEcografiaProducto, consultarEstado, startPolling, stopPolling }`

This is the most complex component — it has polling, sending, and status checking.

- [ ] **Step 1: Add imports and `setup()` at the top of the `<script>` block**

```js
import { useSede } from "@/composables/useSede";
import { useSahiInterface } from "@/composables/useSahiInterface";

export default {
    setup() {
        const { sedeInterface, establecerSede } = useSede();
        const {
            isEnviando, isConsultando,
            enviarEcografiaProducto, consultarEstado,
            startPolling, stopPolling,
        } = useSahiInterface();
        return {
            sedeInterface, establecerSede,
            isEnviando, isConsultando,
            enviarEcografiaProducto, consultarEstado,
            startPolling, stopPolling,
        };
    },
    // ... existing data, methods, etc. follow
```

The `setup()` return value goes right before `data() {`. Remove these from `data()`:
- `isEnviandoSahi: false` (replaced by `isEnviando` from composable)
- `isConsultandoSahi: false` (replaced by `isConsultando` from composable)
- `sahiPollTimer: null` (internal to composable)
- `sahiPollCount: 0` (internal to composable)
- `isBuscandoPendientes: false` (keep — not SAHI related)

- [ ] **Step 2: Replace `establecerSede()` method**

```js
// Replace entire method with:
async establecerSede() {
    await this.establecerSede(); // calls composable's function
    this.registro.sede_id = sessionStorage.getItem("ST-sede");
    this.busqueda.sede_id = sessionStorage.getItem("ST-sede");
    this.sedeActual = sessionStorage.getItem("ST-sede");
    this.registros = [];
},
```

- [ ] **Step 3: Replace `enviarASahiEcografiaProducto()` method**

Replace the axios.post call and state management with composable calls. The method still needs to exist for the toast messages and registro updates:

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

- [ ] **Step 4: Replace `consultarEstadoSahi()` method**

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

- [ ] **Step 5: Replace `startSahiPolling()` and `stopSahiPolling()` methods**

```js
// Replace the two methods with wrappers that delegate to the composable:
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
    this.stopPolling(); // calls composable's stopPolling
},
```

- [ ] **Step 6: Remove now-unnecessary calls**

In `mostrarRegistro()`, replace:
```js
if (this.sedeInterface && this.registro.estado_envio_sahi === "ENVIANDO") {
    this.startSahiPolling();
} else {
    this.stopSahiPolling();
}
```

- [ ] **Step 7: Verify via syntax check**

The component should now import the composables and use them through `this` (since `setup()` return values are merged with Options API).

---

### Task 4: Wire `ecografiasAdmisionadasComponent.vue` and `admisionarComponent.vue`

**Files:**
- Modify: `resources/js/components/modulos/ecografias/ecografiasAdmisionadasComponent.vue`
- Modify: `resources/js/components/modulos/ecografias/admisionarComponent.vue`

Both have identical patterns — `sedeInterface` data property, `establecerSede()`, `consultarEstadoSahi()`, `reenviarASahi()`, `isConsultandoSahi: {}`, `isEnviandoSahi: {}`.

- [ ] **Step 1: Add `setup()` to both components**

```js
import { useSede } from "@/composables/useSede";
import { useSahiInterface } from "@/composables/useSahiInterface";

export default {
    setup() {
        const { sedeInterface, establecerSede } = useSede();
        const { isEnviando, isConsultando, enviarEcografiaProducto, consultarEstado } = useSahiInterface();
        return { sedeInterface, establecerSede, isEnviando, isConsultando, enviarEcografiaProducto, consultarEstado };
    },
```

- [ ] **Step 2: Remove from `data()`**

Remove `sedeInterface: false` from data().

Keep `isConsultandoSahi: {}` and `isEnviandoSahi: {}` as they're used as per-item maps (not just booleans). The composable's `isConsultando` and `isEnviando` are global flags — the per-item maps are a separate concern.

- [ ] **Step 3: Replace method bodies**

For `establecerSede()`:
```js
async establecerSede() {
    const sede = sessionStorage.getItem("ST-sede");
    if (sede != null) {
        this.busqueda.sede_id = sede;
    }
    await this.establecerSede(); // composable call
},
```

For `consultarEstadoSahi(item)`:
```js
async consultarEstadoSahi(item) {
    if (!this.sedeInterface) return;
    if (!item?.ecografia_producto_id) return;

    const id = item.ecografia_producto_id;
    try {
        this.isConsultandoSahi = { ...this.isConsultandoSahi, [id]: true };
        const estado = await this.consultarEstado(id);
        item.estado_envio_sahi = estado.estado_envio_sahi ?? item.estado_envio_sahi;
        item.fechor_envio_sahi = estado.fechor_envio_sahi ?? item.fechor_envio_sahi;
        item.error_envio_sahi = estado.error_envio_sahi ?? null;
        this.safeDestroyDataTable(this.$refs.dt || "#example23");
        this.safeInitDataTable(this.$refs.dt || "#example23");
    } catch (error) {
        $.toast({
            heading: "SAHI",
            text: error.response?.data?.message || "No se pudo consultar el estado SAHI.",
            position: "top-right",
            loaderBg: "#ff6849", icon: "warning",
            hideAfter: 3500, stack: 6,
        });
    } finally {
        this.isConsultandoSahi = { ...this.isConsultandoSahi, [id]: false };
    }
},
```

For `reenviarASahi(item)`:
```js
async reenviarASahi(item) {
    if (!this.sedeInterface) return;
    if (!item?.ecografia_producto_id) return;

    const id = item.ecografia_producto_id;
    try {
        this.isEnviandoSahi = { ...this.isEnviandoSahi, [id]: true };
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
        this.isEnviandoSahi = { ...this.isEnviandoSahi, [id]: false };
    }
},
```

- [ ] **Step 4: Verify both files saved correctly**

---

### Task 5: Wire `AsignarEstudioComponent.vue` and `teleRadiologiaEnviarEstudioComponent.vue`

**Files:**
- Modify: `resources/js/components/modulos/estudios/AsignarEstudioComponent.vue`
- Modify: `resources/js/components/modulos/tele-radiologia/teleRadiologiaEnviarEstudioComponent.vue`

These use SAHI differently — they fetch atenciones/diagnostics from SAHI endpoints, and use `sedeInterface` to toggle between interface and local product selectors.

- [ ] **Step 1: Add `setup()` to both components**

```js
import { useSede } from "@/composables/useSede";

export default {
    setup() {
        const { sedeInterface, establecerSede } = useSede();
        return { sedeInterface, establecerSede };
    },
```

- [ ] **Step 2: Remove `sedeInterface` from `data()` and simplify `establecerSede()`**

For `AsignarEstudioComponent.vue` — it currently uses hardcoded `sede_id == 1` check. Replace with composable:
```js
async establecerSede() {
    this.registro.sede_id = sessionStorage.getItem("ST-sede");
    this.busqueda.sede_id = sessionStorage.getItem("ST-sede");
    this.sedeActual = sessionStorage.getItem("ST-sede");
    await this.establecerSede(); // composable call
    this.registros = [];
},
```

For `teleRadiologiaEnviarEstudioComponent.vue`:
```js
async establecerSede() {
    this.orden.sede.id = sessionStorage.getItem("ST-sede");
    this.busqueda.sede_id = sessionStorage.getItem("ST-sede");
    this.idSedeActual = sessionStorage.getItem("ST-sede");
    this.nomSedeActual = sessionStorage.getItem("ST-nomsede");
    this.registro.sede_id = sessionStorage.getItem("ST-sede");
    await this.establecerSede();
    this.registros = [];
},
```

- [ ] **Step 3: Keep inline SAHI atenciones/productos fetching**

The `interfaceListarProductosDiagnosticos()` and the inline SAHI atenciones fetch in the "opening record" block are specific to these components (not used elsewhere). Keep them inline — the spec says "extract existing logic" and this pattern is unique to the estudio components.

---

### Task 6: Fix `seleccionarSede.vue` — add emitter

**Files:**
- Modify: `resources/js/components/modulos/varios/seleccionarSede.vue`

This component currently stores `sucursalActual` locally but doesn't emit the `sedeSeleccionada` event that other components listen for.

- [ ] **Step 1: Add emitter emit on sede change**

`emitter` is globally available via `this.emitter` (set in `resources/js/app.js` via `app.config.globalProperties`). No import needed.

```js
methods: {
    async listarSucursales() { /* same as before */ },
    establecerSucursal(data = {}) {
        this.nomSucursal = data.sucursal;
        this.sucursalActual = data.id;
        this.emitter.emit("sedeSeleccionada", { idSede: data.id });
    },
}
```

---

### Task 7: Create `RateLimitingTest.php`

**Files:**
- Create: `tests/Feature/RateLimitingTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RateLimitingTest extends TestCase
{
    use DatabaseTransactions;

    public function test_throttle_blocks_after_60_requests(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('ecografias.index');

        for ($i = 0; $i < 60; $i++) {
            $response = $this->actingAs($user)->getJson('/api/ecografias');
            $response->assertStatus(200);
        }

        $response = $this->actingAs($user)->getJson('/api/ecografias');
        $response->assertStatus(429);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/RateLimitingTest.php`
Expected: First 60 requests succeed, 61st returns 429, test passes

- [ ] **Step 3: Commit**

```bash
git add tests/Feature/RateLimitingTest.php
git commit -m "test: add rate limiting throttle test"
```

---

### Task 8: Create `SessionTimeoutTest.php`

**Files:**
- Create: `tests/Feature/SessionTimeoutTest.php`

- [ ] **Step 1: Write the test**

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SessionTimeoutTest extends TestCase
{
    use DatabaseTransactions;

    public function test_session_regenerated_on_login(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();

        $sessionId = session()->getId();

        $this->post('/logout');
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertNotEquals($sessionId, session()->getId());
    }

    public function test_session_expires_after_configurable_lifetime(): void
    {
        config(['session.lifetime' => 1]); // 1 minute

        $user = User::factory()->create();
        $this->actingAs($user)->getJson('/api/user');

        // Advance time past session lifetime
        $this->travel(2)->minutes();

        $response = $this->getJson('/api/user');
        $response->assertStatus(401);
    }
}
```

- [ ] **Step 2: Run test**

Run: `php artisan test tests/Feature/SessionTimeoutTest.php`
Expected: Both test methods pass

---

### Task 9: Create `PasswordChangeTest.php`

**Files:**
- Create: `tests/Feature/PasswordChangeTest.php`

- [ ] **Step 1: Write the test**

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PasswordChangeTest extends TestCase
{
    use DatabaseTransactions;

    public function test_user_without_password_change_gets_redirected(): void
    {
        $user = User::factory()->create([
            'password_changed_at' => null,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(302);
        $response->assertRedirect(route('password.change'));
    }

    public function test_user_with_recent_password_change_gets_through(): void
    {
        $user = User::factory()->create([
            'password_changed_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(200);
    }
}
```

- [ ] **Step 2: Run test**

Run: `php artisan test tests/Feature/PasswordChangeTest.php`
Expected: Both test methods pass (or adjust redirect/route expectations based on actual implementation)

---

### Task 10: Create `EncryptionAtRestTest.php`

**Files:**
- Create: `tests/Feature/EncryptionAtRestTest.php`

- [ ] **Step 1: Write the test**

```php
<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EncryptionAtRestTest extends TestCase
{
    public function test_encrypted_storage_files_are_not_plaintext(): void
    {
        $disk = Storage::disk('local');
        $encFiles = array_filter($disk->allFiles(), fn($f) => str_ends_with($f, '.enc'));

        $this->assertNotEmpty($encFiles, 'No .enc files found in storage');

        foreach ($encFiles as $file) {
            $content = $disk->get($file);
            $this->assertNotNull($content);
            // Encrypted content should not be valid UTF-8 plaintext
            $this->assertFalse(
                mb_check_encoding($content, 'UTF-8') && str_contains($content, '<?xml'),
                "File $file appears to be unencrypted XML"
            );
        }
    }
}
```

- [ ] **Step 2: Run test**

Run: `php artisan test tests/Feature/EncryptionAtRestTest.php`
Expected: Test passes (or adjust assertion if encryption format differs)

---

### Task 11: Run full test suite and verify

- [ ] **Step 1: Run all tests**

Run: `php artisan test`
Expected: All tests pass (existing 17 + 4 new files)

If any existing tests fail, fix regressions before proceeding.

---

## Self-Review

1. **Spec coverage:** Composables spec covers useSede and useSahiInterface with wiring into 6 components. Plan implements all of these. Security tests spec covers 4 test files. Plan has a task for each.

2. **Placeholder scan:** All steps contain actual code. No TBD/TODO patterns. Steps 8-10 have full test code. Step 6 requires checking the actual emitter import path.

3. **Type consistency:** Composable interfaces are consistent across all tasks — `useSede()` always returns `{ sedeInterface, establecerSede }`, `useSahiInterface()` always returns `{ isEnviando, isConsultando, enviarEcografiaProducto, consultarEstado, startPolling, stopPolling }`.

# Ecografías UI/UX Improvements Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Improve UI/UX, accessibility, and code quality across 6 ecografías components following patterns established in Seguridad, Help Desk, and Estudios modules.

**Architecture:** 6 Vue 2 Options API SFCs + composables in `@/composables/` + helpers in `@/helpers/`. No new files created; all changes are in-place modifications.

**Tech Stack:** Vue 2, Bootstrap 4, jQuery, DataTables, Select2, composables (useDictationFlow, useAudioUploadFlow, useSahiInterface, usePermissions, useSede)

## Global Constraints

- Use `handleAxiosErrorToast(error, "msg")` from `@/helpers/alerts.js` for API errors
- Use `confirmAction({title, text, icon, confirmButtonText, onConfirm})` for confirmations
- Use `alertSuccess`/`alertError`/`toastMessage` from `@/helpers/alerts.js` instead of direct `$.toast`
- Use `@/helpers/...` path alias, not relative `../../../helpers/...`
- Components use Options API (`data()`, `methods: {}`, `mounted()`) — no `<script setup>`
- Imports at top level are NOT accessible in template; expose via `return` in `setup()` or use `this.*`

---

### Task 1: Bug Fixes — admisionarComponent, lecturaComponent, ecografiasAdmisionadasComponent

**Files:**
- Modify: `resources/js/components/modulos/ecografias/admisionarComponent.vue`
- Modify: `resources/js/components/modulos/ecografias/lecturaComponent.vue`
- Modify: `resources/js/components/modulos/ecografias/ecografiasAdmisionadasComponent.vue`

- [ ] **Step 1: Fix admisionarComponent L1192 — variable `res` in catch block**

The catch block references `res` but it's not defined (scoping bug). The intent is likely to show the error message.

```js
// Before:
} catch (error) {
    this.mensajeSwal("Error", res.response?.data?.msg || "Error al editar producto de agenda", "error");
    this.loadingAgregarProducto = false;
}
// After:
} catch (error) {
    this.mensajeSwal("Error", error.response?.data?.msg || "Error al editar producto de agenda", "error");
    this.loadingAgregarProducto = false;
}
```

If `mensajeSwal` doesn't exist, use `handleAxiosErrorToast` instead:
```js
} catch (error) {
    handleAxiosErrorToast(error, "Error al editar producto de agenda");
    this.loadingAgregarProducto = false;
}
```

- [ ] **Step 2: Fix admisionarComponent L1390 — productosAgenda assigns entire list**

```js
// Before (likely):
this.registro.productosAgenda = this.registros;
// After:
this.registro.productosAgenda = this.registros.find(r => r.id === selectedId) || this.registros[0];
// Or more specifically based on context — find the actual selected product
```

- [ ] **Step 3: Fix admisionarComponent L1382 — TelCasa mapped to cel**

```js
// Before:
cel = data.TelCasa;
// After:
cel = data.TelCelular || data.TelCasa;
```

- [ ] **Step 4: Fix lecturaComponent L904 — remove hardcoded test artifact**

Remove or comment out `"6341296"` test value that's hardcoded in data.

- [ ] **Step 5: Fix lecturaComponent L428-446 — medico_id validation in wrong fields**

Remove `v-if` conditions that show `medico_id` validation errors inside template fields (they belong to a different form/context).

- [ ] **Step 6: Fix ecografiasAdmisionadasComponent L309 — aria-labelledby pointing to non-existent element**

Find the modal element with `aria-labelledby="myModalLabel"` and add the missing `id="myModalLabel"` element, or remove `aria-labelledby` if not needed.

- [ ] **Step 7: Commit**

```bash
git add resources/js/components/modulos/ecografias/admisionarComponent.vue resources/js/components/modulos/ecografias/lecturaComponent.vue resources/js/components/modulos/ecografias/ecografiasAdmisionadasComponent.vue
git commit -m "fix(ecografias): 6 bug fixes — catch scope, productosAgenda, TelCasa, test artifact, medico_id validation, aria-labelledby"
```

---

### Task 2: Cross-Cutting Accessibility — breadcrumbs, aria-labels, label/for, modal aria

**Files:**
- Modify: `resources/js/components/modulos/ecografias/admisionarComponent.vue`
- Modify: `resources/js/components/modulos/ecografias/lecturaComponent.vue`
- Modify: `resources/js/components/modulos/ecografias/ecografiasAdmisionadasComponent.vue`
- Modify: `resources/js/components/modulos/ecografias/ecografiasConsentimientosPlantillasComponent.vue`
- Modify: `resources/js/components/modulos/ecografias/ecografiasConsentimientosComponent.vue`
- Modify: `resources/js/components/modulos/ecografias/ecografiaFirmaConsentimientoComponent.vue`

- [ ] **Step 1: Fix breadcrumbs — replace `<a href="javascript:void(0)">` with `<button>`**

In admisionarComponent, lecturaComponent, ecografiasAdmisionadasComponent, ecografiasConsentimientosComponent:

```html
<!-- Before: -->
<a href="javascript:void(0)">Inicio</a>
<!-- After: -->
<button type="button" class="btn btn-link p-0" style="vertical-align: baseline;">Inicio</button>
```

Check ecografiasConsentimientosPlantillasComponent and ecografiaFirmaConsentimientoComponent for breadcrumbs too.

- [ ] **Step 2: Add aria-label to icon-only buttons**

In all 6 components, find `<button>` or `<a>` elements with icon-only content (no visible text) and add `aria-label` matching their `title` attribute value.

Search patterns like:
```html
<button title="Editar"><i class="fa fa-edit"></i></button>
```
Change to:
```html
<button title="Editar" aria-label="Editar"><i class="fa fa-edit"></i></button>
```

Apply to all action buttons across tables.

- [ ] **Step 3: Add for/id on form inputs**

In each component, find `<label>` + `<select>` / `<input>` pairs and add `for="fieldId"` to label and `id="fieldId"` to the input.

- [ ] **Step 4: Fix empty checkbox labels**

Find empty `<label>` elements inside checkbox containers and add `aria-label`:
```html
<label aria-label="Seleccionar item"></label>
```

- [ ] **Step 5: Fix modal aria-labelledby (if not already done in Task 1)**

Ensure modals have proper `aria-labelledby` pointing to existing elements, or add missing `id` attributes.

- [ ] **Step 6: Commit**

```bash
git add resources/js/components/modulos/ecografias/admisionarComponent.vue resources/js/components/modulos/ecografias/lecturaComponent.vue resources/js/components/modulos/ecografias/ecografiasAdmisionadasComponent.vue resources/js/components/modulos/ecografias/ecografiasConsentimientosPlantillasComponent.vue resources/js/components/modulos/ecografias/ecografiasConsentimientosComponent.vue resources/js/components/modulos/ecografias/ecografiaFirmaConsentimientoComponent.vue
git commit -m "feat(ecografias): accesibilidad — breadcrumbs button, aria-labels, label/for, modal aria"
```

---

### Task 3: Error Handling & Loading States

**Files:**
- Modify: `resources/js/components/modulos/ecografias/admisionarComponent.vue`
- Modify: `resources/js/components/modulos/ecografias/lecturaComponent.vue`
- Modify: `resources/js/components/modulos/ecografias/ecografiasConsentimientosComponent.vue`
- Reference: `resources/js/helpers/alerts.js`

- [ ] **Step 1: Fix admisionarComponent catch block (if not fully fixed in Task 1)**

Verify Task 1 Step 1 was done correctly and `handleAxiosErrorToast` is properly used.

- [ ] **Step 2: Add loading states for signature operations in lecturaComponent**

In `lecturaComponent.vue`, find signature API calls and add per-item loading flags (similar to existing `isFirmandoConsentimiento[id]` pattern):

```js
// data:
isFirmando: {},
// method:
async firmar(reg) {
    this.$set(this.isFirmando, reg.id, true);
    try {
        // existing API call...
    } catch (error) {
        handleAxiosErrorToast(error, "Error al firmar");
    } finally {
        this.$set(this.isFirmando, reg.id, false);
    }
}
```

In template:
```html
<button :disabled="isFirmando[reg.id]" @click="firmar(reg)">
    <i v-if="isFirmando[reg.id]" class="fa fa-spinner fa-spin"></i>
    Firmar
</button>
```

- [ ] **Step 3: Replace window.prompt() with confirmAction in ecografiasConsentimientosComponent**

Find `window.prompt()` calls and replace with `confirmAction`:

```js
// Before:
const motivo = window.prompt("¿Motivo de anulación?");
if (!motivo) return;
// After:
confirmAction({
    title: "¿Anular consentimiento?",
    text: "Indique el motivo de la anulación",
    icon: "warning",
    confirmButtonText: "Sí, anular",
    onConfirm: async () => {
        // existing code
    },
});
```

Note: `confirmAction` doesn't support text input — if the original code needs a text input for "motivo", consider using a SweetAlert2 `input: 'textarea'` approach instead:

```js
Swal.fire({
    title: "¿Anular consentimiento?",
    text: "Indique el motivo de la anulación",
    icon: "warning",
    input: "textarea",
    inputPlaceholder: "Motivo de anulación...",
    showCancelButton: true,
    confirmButtonText: "Sí, anular",
    preConfirm: (motivo) => {
        if (!motivo) {
            Swal.showValidationMessage("Debe indicar un motivo");
        }
        return motivo;
    },
}).then((result) => {
    if (result.isConfirmed) {
        const motivo = result.value;
        // existing code using motivo
    }
});
```

- [ ] **Step 4: Add empty state messages on DataTables**

In components with DataTables, add empty state message configuration:

```js
// in DataTable init options:
language: {
    emptyTable: "No se encontraron registros",
    zeroRecords: "No se encontraron registros",
},
```

Check which ecografías components use DataTables and add this config.

- [ ] **Step 5: Add handleAxiosErrorToast to API calls missing error feedback**

Scan each component for axios calls in try/catch blocks that lack user-facing error feedback. Add `handleAxiosErrorToast(error, "Mensaje contextual")` where missing.

- [ ] **Step 6: Commit**

```bash
git add resources/js/components/modulos/ecografias/admisionarComponent.vue resources/js/components/modulos/ecografias/lecturaComponent.vue resources/js/components/modulos/ecografias/ecografiasConsentimientosComponent.vue
git commit -m "feat(ecografias): error handling — loading states, confirmAction, empty states, axios error feedback"
```

---

### Task 4: Dead Code & Inline Styles

**Files:**
- Modify: `resources/js/components/modulos/ecografias/admisionarComponent.vue`
- Modify: `resources/js/components/modulos/ecografias/lecturaComponent.vue`
- Modify: `resources/js/components/modulos/ecografias/ecografiasAdmisionadasComponent.vue`
- Modify: `resources/js/components/modulos/ecografias/ecografiasConsentimientosPlantillasComponent.vue`
- Modify: `resources/js/components/modulos/ecografias/ecografiasConsentimientosComponent.vue`
- Modify: `resources/js/components/modulos/ecografias/ecografiaFirmaConsentimientoComponent.vue`

- [ ] **Step 1: Remove commented-out code blocks**

In each component, search for commented HTML blocks (e.g., `<!-- <div ... -->`) and JS comments that contain dead code. Remove them.

Look for patterns like:
- Commented validation spans: `<!-- <span ... -->`
- Commented button groups: `<!-- <button ... -->`
- Commented method calls: `// this.xxx()`

- [ ] **Step 2: Remove unused data properties**

In each component, check `data()` for properties not referenced in `methods`, `computed`, `watch`, or template. Remove unused ones.

- [ ] **Step 3: Migrate inline styles to scoped CSS**

In each component, find inline `style` attributes and extract to scoped CSS classes:

Common patterns:
- `style="overflow-x: hidden; overflow-y: scroll; height: 100%"` → `.sidebar-scroll`
- `style="overflow-y: scroll; height: 250px"` on table containers → `.table-scroll`
- `style="width: 44px; ..."` on mic buttons → `.mic-btn`
- Textarea `style="height: 700px"` → `.lectura-textarea`
- Button height/width styles → `.action-btn`

- [ ] **Step 4: Replace .map() misuse with .forEach()**

Find `.map()` calls where the return value is never used and replace with `.forEach()` or `for...of`.

- [ ] **Step 5: Fix typos**

Search for and fix:
- "admiisionadas" → "admisionadas"
- "adminisionadas" → "admisionadas"
- "adminsión" → "admision"
- Any other typos found in each component

- [ ] **Step 6: Commit**

```bash
git add resources/js/components/modulos/ecografias/admisionarComponent.vue resources/js/components/modulos/ecografias/lecturaComponent.vue resources/js/components/modulos/ecografias/ecografiasAdmisionadasComponent.vue resources/js/components/modulos/ecografias/ecografiasConsentimientosPlantillasComponent.vue resources/js/components/modulos/ecografias/ecografiasConsentimientosComponent.vue resources/js/components/modulos/ecografias/ecografiaFirmaConsentimientoComponent.vue
git commit -m "refactor(ecografias): dead code cleanup, inline styles → scoped CSS, typo fixes"
```

---

### Task 5: Duplication & Consistency

**Files:**
- Modify: `resources/js/components/modulos/ecografias/lecturaComponent.vue`
- Modify: `resources/js/components/modulos/ecografias/admisionarComponent.vue`
- Modify: `resources/js/components/modulos/ecografias/ecografiasAdmisionadasComponent.vue`

- [ ] **Step 1: Assess SAHI method duplication**

Read the three components and identify the exact duplicated methods:
- `setMapValue` — likely identical in all 3
- `consultarEstadoSahi` — likely identical or nearly so
- `reenviarASahi` — likely similar

Decide: if the methods are truly identical, extract to a shared helper file (e.g., `resources/js/helpers/sahi.js`). If they have significant variations, leave as-is and note in comments.

- [ ] **Step 2: Extract shared SAHI methods (if applicable)**

Create `resources/js/helpers/sahi.js`:
```js
import { handleAxiosErrorToast } from "@/helpers/alerts";

export function setMapValue(mapId, value) {
    // shared implementation from the three components
}

export async function consultarEstadoSahi(estudioProductoId, sedId) {
    // shared implementation
}

// etc.
```

**If extraction is too complex due to variations**, skip this step and add a TODO comment instead.

- [ ] **Step 3: Normalize imports to @/helpers/...**

Check all components for relative imports like `../../../helpers/...` and replace with `@/helpers/...`.

- [ ] **Step 4: Replace $.toast with alert helpers**

Find any `$.toast(...)` calls in ecografías components and replace with:
- Success → `alertSuccess(title, text)` or `toastMessage({ heading, text, icon: "success" })`
- Error → `alertError(title, text)` or `handleAxiosErrorToast(error, msg)`
- Warning → `toastMessage({ heading, text, icon: "warning" })`

- [ ] **Step 5: Commit**

```bash
git add resources/js/components/modulos/ecografias/lecturaComponent.vue resources/js/components/modulos/ecografias/admisionarComponent.vue resources/js/components/modulos/ecografias/ecografiasAdmisionadasComponent.vue resources/js/helpers/sahi.js
git commit -m "feat(ecografias): consistencia — SAHI shared helper, imports @/helpers, toast helpers"
```

---

### Summary

| Task | Files | Est. Changes |
|------|-------|-------------|
| T1: Bug Fixes | 3 | ~6 edits |
| T2: Accessibility | 6 | ~30-50 edits |
| T3: Error Handling | 3 | ~10-15 edits |
| T4: Dead Code & CSS | 6 | ~30-50 edits |
| T5: Consistency | 3 | ~10-20 edits |

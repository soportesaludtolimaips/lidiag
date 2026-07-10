# Estudios UI/UX Improvements Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Improve UI/UX, accessibility, and code quality across 6 estudios components following patterns established in Seguridad and Help Desk modules.

**Architecture:** 6 Vue 2 Options API SFCs + composables in `@/composables/` + helpers in `@/helpers/`. No new files created; all changes are in-place modifications.

**Tech Stack:** Vue 2, Bootstrap 4, jQuery, DataTables, Select2, WYSIHTML5, composables (useDictationFlow, useAudioUploadFlow)

## Global Constraints

- Use `handleAxiosErrorToast(error, "msg")` from `@/helpers/alerts.js` for API errors
- Use `confirmAction({title, text, icon, confirmButtonText, onConfirm})` for confirmations
- Use `alertSuccess`/`alertError`/`toastMessage` from `@/helpers/alerts.js` instead of direct `$.toast`
- Use `@/helpers/...` path alias, not relative `../../../helpers/...`
- Components use Options API (`data()`, `methods: {}`, `mounted()`) — no `<script setup>`
- Imports at top level are NOT accessible in template; expose via `return` in `setup()` or use `this.*`

---
### Task 1: Refactor audio-texto.vue to use useDictationFlow

**Files:**
- Modify: `resources/js/components/modulos/estudios/audio-texto.vue`
- Reference: `resources/js/composables/useDictationFlow.js` (read API)
- Reference: `resources/js/components/modulos/estudios/leerEstudioComponent.vue` (lines 1194-1266 for usage pattern)

**Interfaces:**
- Consumes: `useDictationFlow()` from `@/composables/useDictationFlow`
- Produces: refactored audio-texto.vue using composable instead of inline dictation

- [ ] **Step 1: Read current audio-texto.vue and useDictationFlow to understand mapping**

- [ ] **Step 2: Replace data() properties**

Old inline data to remove:
```js
data() {
    return {
        recognizing: false,
        recognitionActive: false,
        recording: false,
        microfono: "admin-wrap/assets/images/mic.gif",
        recognition: null,
        mediaRecorder: null,
        mediaStream: null,
        audioChunks: [],
        audioUrl: null,
        uppercase: false,
        // ... all voice commands and commandMap
    }
}
```

Replace with composable in `setup()`:
```js
import { useDictationFlow } from "@/composables/useDictationFlow";
// In setup():
const dictationFlow = useDictationFlow();
return {
    dictationFlow,
    recognizing: dictationFlow.recognizing,
    recording: dictationFlow.recording,
    microfono: dictationFlow.microfono,
    uppercase: dictationFlow.uppercase,
    toggleSpeechRecognition: dictationFlow.toggleSpeechRecognition,
};
```

Keep props: `estudioId`, `estudioProductoId`
Keep `sendToBackend()` wrapper method that calls `this.dictationFlow.sendToBackend(this.estudioId, this.estudioProductoId)`

- [ ] **Step 3: Update mounted() and beforeUnmount()**

```js
mounted() {
    if (!('webkitSpeechRecognition' in window) && !('SpeechRecognition' in window)) {
        toastMessage({
            heading: "Upss!!!",
            text: "Tu navegador no soporta reconocimiento de voz. Usa Chrome o Edge.",
            icon: "warning",
        });
    }
    this.dictationFlow.setCallbacks({
        onText: (text) => {
            if (this.$listeners && this.$listeners.input) {
                this.$emit("input", (this.value || "") + text);
            }
        },
        showToast: (cfg) => $.toast(cfg),
        onState: () => {},
        onAudioSaved: () => this.$emit("audio-saved"),
    });
    // Ensure ensureMicApiAvailable is NOT called here (it's called on demand by toggleSpeechRecognition)
    // setupKeyboardShortcut() — add only if F2 shortcut makes sense for this standalone component
},
beforeUnmount() {
    this.dictationFlow.stopAll();
    this.dictationFlow.teardownKeyboardShortcut();
    this.dictationFlow.clearAudioBuffer();
},
```

Note: The exact event interface depends on how audio-texto.vue's parent uses it. Check if parent uses `v-model` or `@input`/`@audio-saved` events. If not, keep the existing `onText` callback pattern.

- [ ] **Step 4: Update template**

Replace inline template bindings:
- `@click="toggleSpeechRecognition"` → keep (method name is the same)
- `:src="microfono"` → keep (ref name is the same)
- `v-if="uppercase"` → keep (ref name is the same)
- `v-if="recognizing"` → keep (ref name is the same)
- `v-if="recording"` → keep (ref name is the same)

Remove all inline command processing logic from template — composable handles it via `processCommands()` internally.

- [ ] **Step 5: Remove duplicated voice commands and methods**

Remove from methods:
- `toggleSpeechRecognition()` — now from composable
- `startRecording()`, `stopRecording()` — now from composable
- `processCommands()` — now from composable
- `ensureMicApiAvailable()` — now from composable
- `getMicErrorToastConfig()` — now from composable
- All voice command maps and command processing helpers

- [ ] **Step 6: Remove imports no longer needed**

Remove `import { getUserSafeError } from "@/helpers/alerts"` if no longer used directly by remaining methods.

- [ ] **Step 7: Commit**

```bash
git add resources/js/components/modulos/estudios/audio-texto.vue
git commit -m "feat(estudios): refactor audio-texto to use useDictationFlow composable, fix silent error handling"
```

---
### Task 2: Cross-cutting Accessibility — breadcrumbs, aria-labels, label/for, checkbox labels

**Files:**
- Modify: `resources/js/components/modulos/estudios/AsignarEstudioComponent.vue`
- Modify: `resources/js/components/modulos/estudios/estudioAsignadosComponent.vue`
- Modify: `resources/js/components/modulos/estudios/leerEstudioComponent.vue`
- Modify: `resources/js/components/modulos/estudios/notificarEstudioComponent.vue`
- Modify: `resources/js/components/modulos/estudios/transcribirEstudioComponent.vue`

- [ ] **Step 1: Fix breadcrumbs in all 5 components**

Replace `<a href="javascript:void(0)">Inicio</a>` with `<button type="button" class="btn btn-link p-0" style="vertical-align: baseline;">Inicio</button>` in each breadcrumb. Do the same for intermediate breadcrumb items.

- [ ] **Step 2: Add aria-label to icon-only buttons**

In all 5 components (plus audio-texto if it has icon buttons), add `aria-label` to each action button that currently only has `title`:

Search for patterns like:
```html
<button title="Ver imágenes"><i class="fa fa-image"></i></button>
```
Change to:
```html
<button title="Ver imágenes" aria-label="Ver imágenes"><i class="fa fa-image"></i></button>
```

Do for all action buttons in tables across the 5 components.

- [ ] **Step 3: Add for/id on selects**

In AsignarEstudioComponent.vue, find `<label>Médico</label>` + `<select>` and add `for="medicoSelect"` + `id="medicoSelect"`.

In estudioAsignadosComponent.vue and transcribirEstudioComponent.vue, find "Quien asigno" and "Medicos" selects and same pattern.

- [ ] **Step 4: Fix empty checkbox labels**

Find empty `<label>` elements inside checkbox containers and add `aria-label`:
```html
<label aria-label="Seleccionar producto"></label>
```

- [ ] **Step 5: Commit**

```bash
git add resources/js/components/modulos/estudios/AsignarEstudioComponent.vue resources/js/components/modulos/estudios/estudioAsignadosComponent.vue resources/js/components/modulos/estudios/leerEstudioComponent.vue resources/js/components/modulos/estudios/notificarEstudioComponent.vue resources/js/components/modulos/estudios/transcribirEstudioComponent.vue
git commit -m "feat(estudios): accesibilidad — breadcrumbs button, aria-labels, label/for, checkbox labels"
```

---
### Task 3: Error Handling & Validation

**Files:**
- Modify: `resources/js/components/modulos/estudios/audio-texto.vue` (if not already done in Task 1)
- Modify: `resources/js/components/modulos/estudios/notificarEstudioComponent.vue`

- [ ] **Step 1: Verify audio-texto.vue error handling**

Confirm Task 1 already added:
- SpeechRecognition API check in `mounted()` with toast
- `getUserMedia` rejection handling via composable's internal `ensureMicApiAvailable`

If Task 1 was done correctly, the composable handles these. If not, add manually.

- [ ] **Step 2: Fix notificarEstudio — imprimirReporte() error feedback**

In `notificarEstudioComponent.vue`, find `imprimirReporte()` method. After the try/catch, add error handling:

```js
async imprimirReporte(reg) {
    this.loadingImprimirId = reg.id;
    try {
        // existing axios call...
    } catch (error) {
        handleAxiosErrorToast(error, "No se pudo imprimir el reporte.");
    } finally {
        this.loadingImprimirId = null;
    }
}
```

- [ ] **Step 3: Fix notificarEstudio — enviarPortal() non-402 errors**

In `enviarPortal()`, add a general error handler:
```js
async enviarPortal(reg) {
    this.loadingPortalId = reg.id;
    try {
        // existing axios call...
    } catch (error) {
        if (error.response?.status === 402) {
            // existing 402 handling...
        } else {
            handleAxiosErrorToast(error, "No se pudo enviar al portal.");
        }
    } finally {
        this.loadingPortalId = null;
    }
}
```

- [ ] **Step 4: Remove empty mounted() in notificarEstudio**

```js
mounted() {
    // Just remove this empty block
},
```

- [ ] **Step 5: Commit**

```bash
git add resources/js/components/modulos/estudios/notificarEstudioComponent.vue
git commit -m "feat(estudios): error handling — feedback en imprimir/enviarPortal, check SpeechRecognition API"
```

---
### Task 4: Dead code + confirmAction + map fix + typo

**Files:**
- Modify: `resources/js/components/modulos/estudios/AsignarEstudioComponent.vue`
- Modify: `resources/js/components/modulos/estudios/leerEstudioComponent.vue`
- Modify: `resources/js/components/modulos/estudios/estudioAsignadosComponent.vue`
- Modify: `resources/js/components/modulos/estudios/notificarEstudioComponent.vue` (already handled mounted() in Task 3)
- Modify: `resources/js/components/modulos/estudios/transcribirEstudioComponent.vue`

- [ ] **Step 1: AsignarEstudio — native confirm() → confirmAction**

Find `confirm(...)` calls (2 locations) and replace:
```js
// Before:
if (!confirm("¿Está seguro?")) return;
// After:
confirmAction({
    title: "¿Está seguro?",
    text: "Esta acción no se puede deshacer.",
    icon: "warning",
    confirmButtonText: "Sí, continuar",
    onConfirm: async () => { /* previous code after confirm */ },
});
```

Also update the surrounding code to move the action inside `onConfirm`.

- [ ] **Step 2: AsignarEstudio — map() used as forEach**

Find `consultas.map(...)` and `diagnosticos.map(...)` where the return value is unused. Replace with `.forEach(...)` or `for...of`.

- [ ] **Step 3: AsignarEstudio — fix typo bus_fehc_ini**

Find all occurrences of `bus_fehc_ini` in AsignarEstudioComponent.vue (data, methods, template) and rename to `bus_fech_ini`.

- [ ] **Step 4: Remove commented-out code blocks**

In AsignarEstudioComponent.vue, remove:
- Commented Select2 init blocks (around lines 512-514, 1229-1239)
- Comment `// initProductosSelect2 ya no es necesario` (around line 1200)

In notificarEstudioComponent.vue, remove:
- Commented button `<!-- <button type="button" ...>Traer imagenes</button> -->` (lines 121-122)

- [ ] **Step 5: Remove unused data properties**

In leerEstudioComponent.vue, remove from `data()`:
- `searchQuery`
- `tipoPrioridad`

In estudioAsignadosComponent.vue, remove from `data()`:
- `estudioParaTranscribir`
- `actualizar`

- [ ] **Step 6: Fix duplicate id="email" in estudioAsignadosComponent**

Rename one of the `id="email"` inputs to `id="email-2"` or similar, and update any associated `for="email"` label.

- [ ] **Step 7: Simplify transcribirEstudio image data**

If `datosImagen` computed property and `registro.url_oviyam` etc. store the same data, pick one approach (keep `registro.*` for template simplicity, remove `datosImagen`).

- [ ] **Step 8: Commit**

```bash
git add resources/js/components/modulos/estudios/AsignarEstudioComponent.vue resources/js/components/modulos/estudios/leerEstudioComponent.vue resources/js/components/modulos/estudios/estudioAsignadosComponent.vue resources/js/components/modulos/estudios/transcribirEstudioComponent.vue
git commit -m "feat(estudios): dead code cleanup — confirmAction, map fix, typo, unused props, duplicate id"
```

---
### Task 5: Inline Styles → Scoped CSS

**Files:**
- Modify: `resources/js/components/modulos/estudios/AsignarEstudioComponent.vue`
- Modify: `resources/js/components/modulos/estudios/estudioAsignadosComponent.vue`
- Modify: `resources/js/components/modulos/estudios/leerEstudioComponent.vue`
- Modify: `resources/js/components/modulos/estudios/transcribirEstudioComponent.vue`

- [ ] **Step 1: Extract right-sidebar inline styles**

In each component, find right-sidebar `style="overflow-x: hidden; overflow-y: scroll; height: 100%"` and replace with `class="sidebar-scroll"`. Add to scoped CSS:

```css
.sidebar-scroll {
    overflow-x: hidden;
    overflow-y: scroll;
    height: 100%;
}
```

- [ ] **Step 2: Extract other inline styles**

In AsignarEstudioComponent.vue:
- `style="overflow-y: scroll; height: 250px"` on table container → `.table-scroll { overflow-y: scroll; height: 250px; }`
- `style="height: 6px"` on progress bar → `.progress-thin { height: 6px; }`

In leerEstudioComponent.vue:
- Button styles → `.dictation-btn { width: 44px; ... }` or use existing Bootstrap classes
- Textarea `style="height: 700px"` → `.lectura-textarea { height: 700px; }`
- Audio container styles → `.audio-container { min-width: 0; max-width: 100%; }`
- Download link styles → `.download-link { ... }`

In transcribirEstudioComponent.vue:
- Audio styles → `.audio-player { min-width: 0; max-width: 100%; }`
- Transcription preview styles → `.transcripcion-text { ... }`

- [ ] **Step 3: Fix audio-texto.vue inline styles (in Task 1 component)**

- Button `style="width:44px;..."` → `.mic-btn { width: 44px; ... }`
- Textarea `style="height: 700px"` → `.audio-textarea { height: 700px; }`

- [ ] **Step 4: Commit**

```bash
git add resources/js/components/modulos/estudios/AsignarEstudioComponent.vue resources/js/components/modulos/estudios/estudioAsignadosComponent.vue resources/js/components/modulos/estudios/leerEstudioComponent.vue resources/js/components/modulos/estudios/transcribirEstudioComponent.vue
git commit -m "feat(estudios): migrate inline styles to scoped CSS — sidebar, audio, textarea, buttons"
```

---
### Task 6: Consistency — imports, toast helpers

**Files:**
- Modify: `resources/js/components/modulos/estudios/estudioAsignadosComponent.vue`
- Modify: `resources/js/components/modulos/estudios/transcribirEstudioComponent.vue`
- Modify: `resources/js/components/modulos/estudios/leerEstudioComponent.vue`

- [ ] **Step 1: Fix relative imports**

In estudioAsignadosComponent.vue and transcribirEstudioComponent.vue:
```js
// Before:
import { listarUsuarios } from "../../../helpers/usuarios";
// After:
import { listarUsuarios } from "@/helpers/usuarios";
```

- [ ] **Step 2: Replace $.toast with alert helpers in leerEstudioComponent**

Find all `$.toast(...)` calls in leerEstudioComponent.vue and replace with appropriate helpers:
- Success toasts → `alertSuccess(title, text)` or `toastMessage({ heading, text, icon: "success" })`
- Error toasts → `alertError(title, text)` or `handleAxiosErrorToast(error, msg)`
- Warning toasts → `toastMessage({ heading, text, icon: "warning" })`

Verify that `alertSuccess`, `alertError` are imported from `@/helpers/alerts.js`. If not, add to the import line.

- [ ] **Step 3: Commit**

```bash
git add resources/js/components/modulos/estudios/estudioAsignadosComponent.vue resources/js/components/modulos/estudios/transcribirEstudioComponent.vue resources/js/components/modulos/estudios/leerEstudioComponent.vue
git commit -m "feat(estudios): consistencia — imports @/helpers, alertSuccess/alertError en lugar de $.toast"
```

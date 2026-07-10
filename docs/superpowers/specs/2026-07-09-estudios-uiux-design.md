# UI/UX Improvements — Módulo Estudios

## Objective

Improve UI/UX, accessibility, and code quality across the 6 components of the `estudios` module (AsignarEstudio, audio-texto, estudioAsignados, leerEstudio, notificarEstudio, transcribirEstudio) following the patterns established in the Seguridad and Help Desk modules.

## Components

| File | Lines | Purpose | Complexity |
|------|-------|---------|------------|
| `AsignarEstudioComponent.vue` | 1433 | Search/assign studies to doctors | High |
| `audio-texto.vue` | 443 | Speech-to-text / audio recording | Medium |
| `estudioAsignadosComponent.vue` | 1231 | Manage assigned studies | High |
| `leerEstudioComponent.vue` | 1846 | Doctor reading/transcription with dictation | Very High |
| `notificarEstudioComponent.vue` | 694 | Notify patients of results | Medium |
| `transcribirEstudioComponent.vue` | 1241 | Transcriptionist interface | High |

## Priority 1: Refactor audio-texto.vue to use useDictationFlow

### Problem
`audio-texto.vue` duplicates ~200 lines of voice commands and implements its own dictation flow instead of using the existing `useDictationFlow` composable that `leerEstudioComponent.vue` already uses. It also has silent error handling (`.catch(() => {})` on getUserMedia rejection).

### Solution
- Replace inline dictation implementation with `useDictationFlow()` composable
- Composable API to wire: `setCallbacks`, `toggleSpeechRecognition`, `stopAll`, `sendToBackend`, `clearAudioBuffer`
- Reactive refs to expose: `recognizing`, `recording`, `microfono`, `uppercase`
- Call `setCallbacks` in `mounted()` (onText, showToast, onAudioSaved)
- Call `setupKeyboardShortcut()` in `mounted()`, `teardownKeyboardShortcut()` in `beforeUnmount()`
- Add SpeechRecognition API availability check on mount with toast fallback
- Replace silent `.catch(() => {})` with toast feedback

### Files Changed
- `resources/js/components/modulos/estudios/audio-texto.vue`

### Risk
Low — composable is already proven by leerEstudioComponent.

---

## Priority 2: Cross-Cutting Accessibility

### Problems
1. **Breadcrumbs**: 5 components use `<a href="javascript:void(0)">` — non-navigable, no keyboard semantics
2. **Icon-only buttons**: All table action buttons rely on `title` only, no `aria-label`
3. **Label/for associations**: Selects in AsignarEstudio, estudioAsignados, transcribirEstudio lack `for` attributes
4. **Empty checkbox labels**: Checkboxes with `<label>` but no visible or accessible text

### Solutions

#### 2.1 Breadcrumbs → `<button>` with link styling
- Replace `<a href="javascript:void(0)">` with `<button type="button" class="btn btn-link p-0">` in:
  - AsignarEstudioComponent.vue
  - estudioAsignadosComponent.vue
  - leerEstudioComponent.vue
  - notificarEstudioComponent.vue
  - transcribirEstudioComponent.vue

#### 2.2 aria-label on icon-only buttons
- Add `aria-label` to all action buttons in tables:
  - "Ver imágenes", "Asignar estudio", "Editar", "Anular", "Abrir estudio", "Marcar como visto"
  - "Imprimir", "Notificar", "Enviar portal"

#### 2.3 label/for associations
- Add `for="selectId"` to `<label>` elements and `id="selectId"` to `<select>` elements

#### 2.4 Empty checkbox labels
- Add `aria-label` on empty `<label>` elements or add visible text

### Files Changed
- All 6 components

---

## Priority 3: Error Handling & Validation

### Problems
- `audio-texto.vue`: `getUserMedia` rejection silently swallowed
- `audio-texto.vue`: No SpeechRecognition API availability check
- `notificarEstudioComponent.vue`: `imprimirReporte()` has no user feedback on error
- `notificarEstudioComponent.vue`: `enviarPortal()` only handles HTTP 402; other errors silently dropped
- `notificarEstudioComponent.vue`: Empty `mounted() {}`
- `AsignarEstudioComponent.vue`: Native `confirm()` instead of `confirmAction` helper (2 locations)
- `AsignarEstudioComponent.vue`: `map()` used as `forEach` (creates unused arrays)

### Solutions
- Replace native `confirm()` with `confirmAction` from `@/helpers/alerts`
- Replace `.map()` misuse with `.forEach()` or `for...of`
- Add `handleAxiosErrorToast` calls where missing
- Add `catch` handlers in `audio-texto.vue` for getUserMedia rejection
- Add `beforeMount()` check for SpeechRecognition API support in `audio-texto.vue`
- Remove empty `mounted() {}`

### Files Changed
- `audio-texto.vue`, `notificarEstudioComponent.vue`, `AsignarEstudioComponent.vue`

---

## Priority 4: Dead Code & Inline Styles

### Problems
- Commented-out code blocks in AsignarEstudio and notificarEstudio
- Unused data properties: `searchQuery`, `tipoPrioridad` (leerEstudio), `estudioParaTranscribir`, `actualizar` (estudioAsignados)
- Inline styles in right-sidebar, textareas, buttons, audio elements across 4 components
- Data redundancy: `datosImagen` and `registro.*` store same image data in transcribirEstudio

### Solutions
- Remove commented blocks
- Remove unused data properties
- Migrate inline styles to scoped CSS classes
- Simplify transcribirEstudio image data storage

### Files Changed
- All 6 components

---

## Priority 5: Consistency

### Problems
- `estudioAsignadosComponent.vue` and `transcribirEstudioComponent.vue` use `../../../helpers/usuarios` instead of `@/helpers/usuarios`
- `leerEstudioComponent.vue` uses `$.toast` directly instead of `alertSuccess`/`alertError`/`handleAxiosErrorToast` helpers

### Solutions
- Update imports to use `@/helpers/` consistently
- Replace `$.toast` calls with `alertSuccess`, `alertError`, `handleAxiosErrorToast` where appropriate

### Files Changed
- `estudioAsignadosComponent.vue`, `transcribirEstudioComponent.vue`, `leerEstudioComponent.vue`

---

## Patterns to Follow (from previous modules)

- Error handling: `handleAxiosErrorToast(error, "User message")` from `@/helpers/alerts.js`
- Confirmations: `confirmAction({ title, text, icon, confirmButtonText, onConfirm })` from `@/helpers/alerts.js`
- Imports: Use `@/helpers/...` not relative `../../../helpers/...`
- Toast helpers: Prefer `alertSuccess`/`alertError`/`toastMessage` over direct `$.toast`
- Loading: Per-operation loading flags with disabled states
- Audio cleanup: `URL.revokeObjectURL()` in `beforeUnmount`

## Out of Scope

- Creating shared CSS files for right-sidebar (duplication is acceptable in scoped SFCs)
- Extracting `useDictationFlow` commands to a shared config (deferred to future refactor)
- Full rewrite or Vue 3 Composition API migration of Option API components

## File Impact Summary

| File | P1 | P2 | P3 | P4 | P5 |
|------|:--:|:--:|:--:|:--:|:--:|
| AsignarEstudioComponent.vue | | ✓ | ✓ | ✓ | |
| audio-texto.vue | ✓ | ✓ | ✓ | ✓ | |
| estudioAsignadosComponent.vue | | ✓ | | ✓ | ✓ |
| leerEstudioComponent.vue | | ✓ | | ✓ | ✓ |
| notificarEstudioComponent.vue | | ✓ | ✓ | ✓ | |
| transcribirEstudioComponent.vue | | ✓ | | ✓ | ✓ |

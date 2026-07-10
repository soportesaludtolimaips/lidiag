# UI/UX Improvements — Módulo Ecografías

## Objective

Improve UI/UX, accessibility, and code quality across the 6 components of the `ecografias` module (admisionar, lectura, ecografiasAdmisionadas, consentimientosPlantillas, consentimientos, firmaConsentimiento) following the patterns established in Seguridad, Help Desk, and Estudios modules.

## Components

| File | Lines | Purpose | Complexity |
|------|-------|---------|------------|
| `lecturaComponent.vue` | 1847 | Doctor reading/transcription with dictation, SAHI, templates, signature | Very High |
| `admisionarComponent.vue` | 1459 | Patient admission for ecografías | High |
| `ecografiasAdmisionadasComponent.vue` | 688 | List of admitted patients | Medium |
| `ecografiasConsentimientosPlantillasComponent.vue` | 495 | Consent form templates | Medium |
| `ecografiasConsentimientosComponent.vue` | 297 | Informed consent management | Low |
| `ecografiaFirmaConsentimientoComponent.vue` | 229 | Sign consent forms | Low |

## Priority 1: Bug Fixes

### Problems
1. **admisionarComponent L1192**: Variable `res` in catch block not defined — scoping bug will throw ReferenceError
2. **admisionarComponent L1390**: `this.registro.productosAgenda = this.registros` assigns entire list instead of selected product
3. **admisionarComponent L1382**: `cel = data.TelCasa` maps home phone to cell phone field
4. **lecturaComponent L904**: Test artifact `"6341296"` hardcoded in data
5. **lecturaComponent L428-446**: `medico_id` validation errors displayed in template fields (copy-paste bug)
6. **ecografiasAdmisionadasComponent L309**: `aria-labelledby="myModalLabel"` but no element with `id="myModalLabel"` exists

### Solutions
- Fix each bug individually with minimal change
- Add defensive checks and proper variable declarations

### Files Changed
- `admisionarComponent.vue`, `lecturaComponent.vue`, `ecografiasAdmisionadasComponent.vue`

---

## Priority 2: Cross-Cutting Accessibility

### Problems
1. **Breadcrumbs**: 4 components use `<a href="javascript:void(0)">` — non-navigable, no keyboard semantics
2. **Icon-only buttons**: Table action buttons rely on `title` only, no `aria-label`
3. **Label/for associations**: Form inputs lack `for`/`id` in most components
4. **Empty checkbox labels**: Checkboxes with `<label>` but no visible or accessible text
5. **Modal aria-labelledby**: Modal elements lack proper aria associations

### Solutions
- Breadcrumbs → `<button>` with link styling
- Add `aria-label` to all icon-only buttons
- Add `for`/`id` on form inputs
- Fix modal aria associations

### Files Changed
- All 6 components

---

## Priority 3: Error Handling & Loading States

### Problems
- `admisionarComponent` line 1192: variable scoping in catch will crash silently
- `lecturaComponent`: No per-item loading states for signature actions
- `consentimientosComponent`: Uses `window.prompt()` instead of `confirmAction` helper
- Missing `handleAxiosErrorToast` in some API calls
- No empty state messages on DataTables

### Solutions
- Fix catch block variable scope
- Add per-item loading states with disabled UI
- Replace `window.prompt()` with `confirmAction`
- Add `handleAxiosErrorToast` where missing
- Add "No se encontraron..." empty state messages

### Files Changed
- `admisionarComponent.vue`, `lecturaComponent.vue`, `ecografiasConsentimientosComponent.vue`

---

## Priority 4: Dead Code & Inline Styles

### Problems
- Commented-out validation spans and code blocks in 3 components
- Unused data properties and methods
- Inline styles in multiple components (sidebars, textareas, buttons)
- `map()` used as `forEach()` (creates unused arrays)
- Typos: "admiisionadas" / "adminisionadas" / "adminsión"

### Solutions
- Remove commented blocks
- Remove unused data properties
- Migrate inline styles to scoped CSS classes
- Replace `.map()` misuse with `.forEach()`
- Fix typos

### Files Changed
- All 6 components

---

## Priority 5: Duplication & Consistency

### Problems
- `setMapValue`, `consultarEstadoSahi`, `reenviarASahi` duplicated across `lecturaComponent`, `admisionarComponent`, `ecografiasAdmisionadasComponent`
- Inconsistent import patterns (relative vs `@/helpers/`)
- Some components use `$.toast` directly instead of alert helpers

### Solutions
- Extract duplicated SAHI methods to a shared helper or composable
- Normalize imports to `@/helpers/...` pattern
- Replace `$.toast` with `alertSuccess`/`alertError`/`handleAxiosErrorToast`

### Files Changed
- `lecturaComponent.vue`, `admisionarComponent.vue`, `ecografiasAdmisionadasComponent.vue`

---

## Patterns to Follow (from previous modules)

- Error handling: `handleAxiosErrorToast(error, "User message")` from `@/helpers/alerts.js`
- Confirmations: `confirmAction({ title, text, icon, confirmButtonText, onConfirm })` from `@/helpers/alerts.js`
- Imports: Use `@/helpers/...` not relative `../../../helpers/...`
- Toast helpers: Prefer `alertSuccess`/`alertError`/`toastMessage` over direct `$.toast`
- Loading: Per-operation loading flags with disabled states
- Empty states: "No se encontraron..." message in DataTables
- Breadcrumbs: `<button>` with `btn btn-link p-0` styling, not `<a href="javascript:void(0)">`

## Out of Scope

- Creating shared SAHI helper/composable (unless straightforward — deferred if complex)
- Full rewrite or Vue 3 Composition API migration
- Creating shared CSS files for right-sidebar (duplication acceptable in scoped SFCs)

## File Impact Summary

| File | P1 | P2 | P3 | P4 | P5 |
|------|:--:|:--:|:--:|:--:|:--:|
| lecturaComponent.vue | ✓ | ✓ | ✓ | ✓ | ✓ |
| admisionarComponent.vue | ✓ | ✓ | ✓ | ✓ | ✓ |
| ecografiasAdmisionadasComponent.vue | ✓ | ✓ | | ✓ | ✓ |
| ecografiasConsentimientosPlantillasComponent.vue | | ✓ | | ✓ | |
| ecografiasConsentimientosComponent.vue | | ✓ | ✓ | ✓ | |
| ecografiaFirmaConsentimientoComponent.vue | | ✓ | | ✓ | |

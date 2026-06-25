# Vue Composables Extraction + Security Test Suite

## Overview

Two independent tasks: (1) extract duplicated SAHI/sede inline logic into Vue 3 composables and wire them into existing components, (2) add comprehensive security tests for ISO 27001 compliance.

---

## Part 1: Composables

### `useSede.js`

**Purpose:** Single source of truth for the current sede configuration. Eliminates duplicate `establecerSede()` across 5+ components.

**API:**
- `sedeActual` (ref) — Current sede ID from sessionStorage
- `sedeInterface` (ref) — `true` when `interface_software === "SAHI"`
- `isLoading` (ref) — Loading state during fetch
- `establecerSede()` — Fetches `GET /api/config-sedes/{id}/buscarPorId` and sets `sedeInterface`

**Replaces inline `establecerSede()` in:** lecturaComponent, ecografiasAdmisionadasComponent, admisionarComponent, teleRadiologiaEnviarEstudioComponent, AsignarEstudioComponent

### `useSahiInterface.js`

**Purpose:** Encapsulates all SAHI API calls + polling logic currently duplicated across ecografía components.

**API:**
- `isEnviando`, `isConsultando` (refs) — Loading states
- `ultimoEstado` (ref) — Last SAHI status response
- `enviarEcografiaProducto(ecografiaProductoId)` — POST to `/api/interface-sahi-ecografias/enviar/ecografia-producto`
- `consultarEstado(ecografiaProductoId)` — GET status from `/api/interface-sahi-ecografias/estado/ecografia-producto/{id}`
- `listarAtenciones(numDocu)` — GET atenciones from SAHI
- `listarProductosDiagnosticos(idAtencion)` — GET products/diagnostics from SAHI
- `startPolling(ecografiaProductoId, onUpdate)` — 5s interval, max 36 attempts
- `stopPolling()` — Clear interval

**Polling lifecycle:** `onBeforeUnmount` cleanup built into composable via `watchEffect` or explicit call.

### Wiring Plan (per component)

| Component | Changes |
|-----------|---------|
| `lecturaComponent.vue` | Replace: `sedeInterface` data, `establecerSede()`, `enviarASahiEcografiaProducto()`, `consultarEstadoSahi()`, `startSahiPolling()`, `stopSahiPolling()`, SAHI data fields (isEnviandoSahi, etc.) |
| `ecografiasAdmisionadasComponent.vue` | Replace: `sedeInterface` data, `establecerSede()`, `consultarEstadoSahi()`, `reenviarASahi()`, `isConsultandoSahi`, `isEnviandoSahi` |
| `admisionarComponent.vue` | Same as ecografiasAdmisionadasComponent |
| `AsignarEstudioComponent.vue` | Replace: `sedeInterface`, `establecerSede()`, inline `interface_software` check, `interfaceListarProductosDiagnosticos()` |
| `teleRadiologiaEnviarEstudioComponent.vue` | Replace: same as AsignarEstudioComponent + inline SAHI atenciones fetch |
| `seleccionarSede.vue` | Add `emitter.emit("sedeSeleccionada", ...)` call |

### Composables Location

`resources/js/composables/useSede.js` and `resources/js/composables/useSahiInterface.js`

---

## Part 2: Security Test Suite

4 new test files under `tests/Feature/`:

### `RateLimitingTest.php`
- `test_throttle_blocks_after_60_requests()` — Hits a throttle-protected endpoint 61 times, asserts 429 on the 61st
- Uses `$this->withHeaders()` for consistent IP
- Targets any API route with `throttle:api` middleware (e.g. `/api/ecografias`)

### `SessionTimeoutTest.php`
- `test_session_expires_after_inactivity()` — Login, wait (simulate via manipulating session lifetime if possible, or assert that session cookie has reasonable max-age/expiry)
- `test_session_regenerated_on_login()` — Assert session ID changes after login

### `PasswordChangeTest.php`
- `test_user_without_password_change_gets_redirected()` — Create user with `password_changed_at = null`, access a protected route, assert redirect to password change page (or 403)
- `test_user_with_recent_password_change_gets_through()` — Same but with recent `password_changed_at`

### `EncryptionAtRestTest.php`
- `test_encrypted_file_exists_and_is_encrypted()` — Assert that storage path has `.enc` files, read one and verify it's not plaintext (doesn't contain expected original content)
- `test_encrypted_file_can_be_decrypted_with_correct_key()` — If feasible, decrypt with app key and assert original content

---

## Key Principles

- **YAGNI:** Composables only extract existing logic, no new features
- **Follow existing patterns:** Composables use the same `ref`/named-export pattern as `useMedicalDictionary.js`
- **No functional changes:** Composable extraction should produce identical behavior
- **Tests are self-contained:** Each test file uses `DatabaseTransactions` and `RefreshDatabase` as needed

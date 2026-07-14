# Vue Migration + Error Handling + Toast Standardization

> **For agentic workers:** REQUIRED SUB-SKILL: Use subagent-driven-development (recommended) or executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Centralizar exception handling en backend, estandarizar toasts HTTP automáticos en frontend, migrar 63 componentes Vue de Options API a Composition API.

**Architecture:** Backend Exception Handler captura Throwable no manejado → shape `{success,data,message,status}`. Frontend interceptor Axios mapea HTTP status → toast automático via `useToast()`. Vue migration via PowerShell script + revisión manual.

**Tech Stack:** Laravel 10 (PHP 8.1), Vue 3 (Composition API + `<script setup>`), Axios, jQuery Toast plugin, Vite, PowerShell 5.1

## Global Constraints

- No cambiar templates `.vue` (solo script block)
- Mantener imports a `@/helpers/alerts.js` donde se usen `confirmAction`, `alertSuccess`, `alertError`
- Reemplazar `handleAxiosErrorToast` import por interceptor automático (no necesita import manual)
- Mantener exacta misma funcionalidad — solo cambiar sintaxis no lógica de negocio
- Cada batch verifica con `npm run build` antes de pasar al siguiente
- Usar `npm run lint` para verificar calidad
- Los composables existentes usan `export function useXxx()` sin default export

---

### Task 1: Backend Exception Handler

**Files:**
- Modify: `app/Exceptions/Handler.php:55-86`

**Interfaces:**
- Consumes: existing Service helpers return `{success,data,message,status}`
- Produces: Any unhandled Throwable → `{success: false, data: null, message: "...", status: N}`

- [ ] **Step 1: Add renderable callback to Handler**

Replace the existing `render()` method with a `renderable()` approach that catches all unhandled Throwables:

Search for the `render` method at line 55. Replace the entire method body:

Old code (lines 55-86):
```php
    public function render($request, Throwable $e)
    {
        if ($e instanceof HttpResponseException) {
            return $e->getResponse();
        }

        if ($request->expectsJson()) {
            $status = match (true) {
                $e instanceof AuthenticationException => 401,
                $e instanceof AuthorizationException => 403,
                $e instanceof ModelNotFoundException => 404,
                $e instanceof NotFoundHttpException => 404,
                $e instanceof ValidationException => 422,
                $e instanceof ThrottleRequestsException => 429,
                default => method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500,
            };

            $message = match (true) {
                $e instanceof ValidationException => $e->getMessage(),
                $e instanceof AuthenticationException => 'No autenticado.',
                $e instanceof AuthorizationException => 'No autorizado.',
                $e instanceof ModelNotFoundException => 'Recurso no encontrado.',
                $e instanceof ThrottleRequestsException => 'Demasiadas solicitudes.',
                $e instanceof NotFoundHttpException => 'Ruta no encontrada.',
                default => config('app.debug') && app()->isLocal() ? $e->getMessage() : 'Error interno del servidor.',
            };

            return response()->json(['success' => false, 'message' => $message], $status);
        }

        return parent::render($request, $e);
    }
```

New code:
```php
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (Throwable $e, Request $request) {
            if ($e instanceof HttpResponseException) {
                return $e->getResponse();
            }

            if ($request->expectsJson()) {
                $status = match (true) {
                    $e instanceof AuthenticationException => 401,
                    $e instanceof AuthorizationException => 403,
                    $e instanceof ModelNotFoundException => 404,
                    $e instanceof NotFoundHttpException => 404,
                    $e instanceof ValidationException => 422,
                    $e instanceof ThrottleRequestsException => 429,
                    default => method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500,
                };

                $message = match (true) {
                    $e instanceof ValidationException => $e->getMessage(),
                    $e instanceof AuthenticationException => 'No autenticado.',
                    $e instanceof AuthorizationException => 'No autorizado.',
                    $e instanceof ModelNotFoundException => 'Recurso no encontrado.',
                    $e instanceof ThrottleRequestsException => 'Demasiadas solicitudes.',
                    $e instanceof NotFoundHttpException => 'Ruta no encontrada.',
                    default => config('app.debug') && app()->isLocal() ? $e->getMessage() : 'Error interno del servidor.',
                };

                $data = $e instanceof ValidationException ? $e->errors() : null;

                return response()->json([
                    'success' => false,
                    'data' => $data,
                    'message' => $message,
                    'status' => $status,
                ], $status);
            }

            return parent::render($request, $e);
        });
    }
```

- [ ] **Step 2: Remove render() method and update class**

Remove line 54-86 (the old `render()` method). The `register()` method replaces it.

Also add `Request` import if not present (already at line 11).

- [ ] **Step 3: Verify syntax**

Run: `php -l app/Exceptions/Handler.php`
Expected: `No syntax errors detected`

---

### Task 2: useToast composable

**Files:**
- Create: `resources/js/composables/useToast.js`

**Interfaces:**
- Consumes: `window.$.toast()` (loaded globally by template)
- Produces: `useToast()` returning `{ show, success, error, warning, info }`

- [ ] **Step 1: Create composable file**

```js
const DEFAULTS = { position: 'top-right', hideAfter: 3500, stack: 6 };

const LOADER_COLORS = {
    success: '#28a745',
    error: '#dc3545',
    warning: '#ffc107',
    info: '#17a2b8',
};

function show(heading, text, icon) {
    const $ = window.$ || window.jQuery;
    if (!$ || typeof $.toast !== 'function') return;

    $.toast({
        heading,
        text: text || '',
        icon: icon || 'info',
        position: DEFAULTS.position,
        loaderBg: LOADER_COLORS[icon] || '#ff6849',
        hideAfter: DEFAULTS.hideAfter,
        stack: DEFAULTS.stack,
    });
}

export function useToast() {
    function success(text, heading = 'Ok') {
        show(heading, text, 'success');
    }

    function error(text, heading = 'Error') {
        show(heading, text, 'error');
    }

    function warning(text, heading = 'Atención') {
        show(heading, text, 'warning');
    }

    function info(text, heading = 'Información') {
        show(heading, text, 'info');
    }

    return { show, success, error, warning, info };
}
```

- [ ] **Step 2: Verify file created**

Run: `if (Test-Path "resources/js/composables/useToast.js") { "OK" }`

---

### Task 3: Axios Interceptor — Toast automático + response toast

**Files:**
- Modify: `resources/js/bootstrap.js:65-85`

**Interfaces:**
- Consumes: `useToast()` composable, `window.$?.toast()`
- Produces: Automatic toast on every non-GET success response with message; automatic toast on every error by HTTP status
- Compat: existing `redirectToLogin()` on 401 preserved

- [ ] **Step 1: Update response interceptor to show success toast**

Replace lines 65-85 (the interceptor registration) with:

```js
const { useToast } = await import('@/composables/useToast.js');

window.axios.interceptors.response.use(
    function (response) {
        refreshCsrfToken();

        const toast = useToast();
        const method = response.config?.method?.toLowerCase();

        if (response.data?.message && method && method !== 'get' && method !== 'head' && method !== 'options') {
            const icon = response.status === 201 ? 'success' : 'info';
            toast[icon]?.(response.data.message);
        }

        return response;
    },
    function (error) {
        const status = error?.response?.status;
        const isVerifyRequest = error?.config?.headers?.['X-Verify-Session'] === '1';
        const toast = useToast();

        if (status === 401 && !_redirectingToLogin && !isVerifyRequest) {
            confirmSessionExpired().then(expired => {
                if (expired) {
                    redirectToLogin();
                } else {
                    console.warn('[Auth] 401 en', error.config?.url, '- sesión aún válida');
                }
            });
            return Promise.reject(error);
        }

        if (status) {
            const map = {
                400: { heading: 'Solicitud incorrecta', icon: 'warning' },
                401: { heading: 'Sesión expirada', icon: 'warning' },
                403: { heading: 'No autorizado', icon: 'error' },
                404: { heading: 'No encontrado', icon: 'warning' },
                422: { heading: 'Error de validación', icon: 'warning' },
                429: { heading: 'Demasiadas solicitudes', icon: 'warning' },
                500: { heading: 'Error interno', icon: 'error' },
                503: { heading: 'Servicio no disponible', icon: 'error' },
            };

            const entry = map[status] || { heading: 'Error inesperado', icon: 'error' };
            const text = error.response?.data?.message || '';

            if (status === 422 && error.response?.data?.errors) {
                const firstError = Object.values(error.response.data.errors).flat()[0];
                toast.warning(firstError || text, entry.heading);
            } else {
                toast[entry.icon]?.(text || entry.heading);
            }
        } else {
            toast.error('Error de conexión. Verifica tu red.');
        }

        return Promise.reject(error);
    }
);
```

- [ ] **Step 2: Remove handleAxiosErrorToast internal toast calls**

Read the existing `handleAxiosErrorToast` in `resources/js/helpers/alerts.js:115-141`. Simplify it to a no-op passthrough since the interceptor now handles all the toast logic:

```js
export function handleAxiosErrorToast(error, fallbackMessage = "Ocurrió un error.") {
    // Deprecated — the Axios interceptor in bootstrap.js now handles toast automatically.
    // Kept for compatibility with existing callers.
    console.warn('[alerts] handleAxiosErrorToast is deprecated. Remove this call.', error.config?.url);
}
```

- [ ] **Step 3: Verify with lint**

Run: `npm run lint 2>&1`
Expected: Warnings only (no errors)

---

### Task 4: Migration Script

**Files:**
- Create: `scripts/migrate-vue.ps1`

**Interfaces:**
- Consumes: `.vue` files with `<script>` (Options API) + `<template>` + `<style>`
- Produces: Same `.vue` files with `<script setup>` (Composition API)
- Usage: `.\scripts\migrate-vue.ps1 -Path "resources/js/components/modulos/configuracion/configSedeComponent.vue"`

- [ ] **Step 1: Create migration script**

Write the PowerShell script that performs the mechanical transformation:

```powershell
param(
    [Parameter(Mandatory)]
    [string]$Path,
    [switch]$DryRun
)

$ErrorActionPreference = "Stop"

if (-not (Test-Path $Path)) {
    Write-Error "File not found: $Path"
    exit 1
}

$content = Get-Content $Path -Raw

# Skip if already <script setup>
if ($content -match '<script\s+setup') {
    Write-Warning "Already <script setup>: $Path"
    exit 0
}

# Extract template block
$templateMatch = [regex]::Match($content, '(?s)(<template>.*?</template>)')
if (-not $templateMatch.Success) {
    Write-Error "No <template> found in $Path"
    exit 1
}
$templateBlock = $templateMatch.Groups[1].Value

# Extract <script> block (NOT setup)
$scriptMatch = [regex]::Match($content, '(?s)<script>(.*?)</script>')
if (-not $scriptMatch.Success) {
    Write-Error "No <script> block found in $Path"
    exit 1
}
$scriptInner = $scriptMatch.Groups[1].Value

# Extract style block(s)
$styleBlocks = @()
$styleMatches = [regex]::Matches($content, '(?s)(<style.*?>.*?</style>)')
foreach ($m in $styleMatches) { $styleBlocks += $m.Groups[1].Value }

# Extract imports (lines starting with import)
$imports = @()
$importLines = @()
foreach ($line in ($scriptInner -split "`r`n|`n")) {
    if ($line -match '^\s*import\s+') {
        $imports += $line
        $importLines += $line
    }
}

# Extract export default object
$exportMatch = [regex]::Match($scriptInner, '(?s)export\s+default\s*\{')
if (-not $exportMatch.Success) {
    Write-Error "No export default found in $Path"
    exit 1
}

# Extract props
$props = ""
$propsMatch = [regex]::Match($scriptInner, '(?s)props\s*:\s*(\[[^\]]*\]|\{[^\}]*\})')
if ($propsMatch.Success) {
    $props = $propsMatch.Groups[1].Value
    # Convert array props ['a','b'] to object form { a: {}, b: {} } for defineProps
    if ($props -match '^\[') {
        $propNames = @()
        $temp = $props
        while ($temp -match "'([^']+)'|`"([^`"]+)`"") {
            $propNames += if ($Matches[1]) { $Matches[1] } else { $Matches[2] }
            $temp = $temp.Substring($Matches[0].Length + $temp.IndexOf($Matches[0]))
        }
        $props = if ($propNames.Count -gt 0) {
            "{" + ($propNames | ForEach-Object { "    $_`: {}" }) -join ",`n" + "`n  }"
        } else { "{}" }
    }
}

# Extract emits
$emits = ""
$emitsMatch = [regex]::Match($scriptInner, '(?s)emits\s*:\s*(\[[^\]]*\]|\'[^\']*\'|`"[^`"]*`")')
if ($emitsMatch.Success) {
    $emits = $emitsMatch.Groups[1].Value
}

# Extract data() return keys
$dataKeys = @{}
$dataMatch = [regex]::Match($scriptInner, '(?s)data\s*\(\s*\)\s*\{[^}]*return\s*(\{[^}]*\})')
if (-not $dataMatch.Success) {
    $dataMatch = [regex]::Match($scriptInner, '(?s)data\s*\(\s*\)\s*\{[^}]*return\s*(\{[\s\S]*?\})')
}
if ($dataMatch.Success) {
    $dataBlock = $dataMatch.Groups[1].Value
    # Extract key-value pairs
    $dataBlock -split "`n" | ForEach-Object {
        if ($_ -match '^\s*(\w+)\s*:\s*(.+),?\s*$') {
            $key = $Matches[1]
            $val = $Matches[2].TrimEnd(',')
            # Determine if it should be ref or reactive
            if ($val -match '^\{') {
                $dataKeys[$key] = @{ type = 'reactive'; val = $val }
            } elseif ($val -match '^null$|^0$|^false$|^true$|^""$|^``$|^\[.*\]$') {
                $dataKeys[$key] = @{ type = 'ref'; val = $val }
            } else {
                $dataKeys[$key] = @{ type = 'ref'; val = $val }
            }
        }
    }
}

# Extract computed
$computed = @{}
$computedMatch = [regex]::Match($scriptInner, '(?s)computed\s*:\s*\{([^}]*)\}')
if ($computedMatch.Success) {
    $computedBlock = $computedMatch.Groups[1].Value
    $computedBlock -split "`n" | ForEach-Object {
        if ($_ -match '^\s*(\w+)\s*\(\s*\)\s*\{') {
            $computedName = $Matches[1]
            # Extract body
            $bodyStart = $_.IndexOf('{')
            if ($bodyStart -ge 0) {
                $computed[$computedName] = $_.Substring($bodyStart)
            }
        }
    }
}

# Extract methods
$methods = @{}
$methodsSection = ""
$methodsMatch = [regex]::Match($scriptInner, '(?s)methods\s*:\s*\{([\s\S]*?)\}\s*\}')
if ($methodsMatch.Success) {
    $methodsSection = $methodsMatch.Groups[1].Value
    # Extract each method
    $depth = 0
    $currentMethod = ""
    $currentName = ""
    $inMethod = $false
    foreach ($line in ($methodsSection -split "`r`n|`n")) {
        if ($line -match '^\s*(\w+)\s*[:(]') {
            if ($inMethod -and $currentName) {
                $methods[$currentName] = $currentMethod
            }
            $currentName = $Matches[1]
            $currentMethod = $line
            $inMethod = $true
            $depth = if ($line.Contains('{')) { 1 } else { 0 }
        } elseif ($inMethod) {
            $currentMethod += "`n" + $line
            $depth += ($line.ToCharArray() | Where-Object { $_ -eq '{' }).Count
            $depth -= ($line.ToCharArray() | Where-Object { $_ -eq '}' }).Count
            if ($depth -le 0 -and $line.TrimEnd() -match '^\s*},?\s*$') {
                $methods[$currentName] = $currentMethod
                $currentName = ""
                $inMethod = $false
            }
        }
    }
    if ($inMethod -and $currentName) {
        $methods[$currentName] = $currentMethod
    }
}

# --- Generate <script setup> ---
$scriptSetupLines = @()

# Imports section
$vueImports = @()
$hasMounted = $scriptInner -match 'mounted\s*\(\)' -or $methods.Values -match 'mounted\('
$hasBeforeUnmount = $scriptInner -match 'beforeUnmount\s*\(' -or $methods.Values -match 'beforeUnmount\('
$hasComputed = $computed.Keys.Count -gt 0
$hasWatch = $scriptInner -match 'watch\s*:'
$hasNextTick = $scriptInner -match '\$nextTick'

if ($hasMounted -or $hasBeforeUnmount) { $vueImports += 'onMounted'; if ($hasBeforeUnmount) { $vueImports += 'onBeforeUnmount' } }
if ($hasComputed) { $vueImports += 'computed' }
if ($hasWatch) { $vueImports += 'watch' }
if ($hasNextTick) { $vueImports += 'nextTick' }
if ($dataKeys.Keys.Count -gt 0) { $vueImports += 'ref' }
if ($dataKeys.Values | Where-Object { $_['type'] -eq 'reactive' }) { $vueImports += 'reactive' }

$vueImports = $vueImports | Select-Object -Unique | Sort-Object
if ($vueImports.Count -gt 0) {
    $scriptSetupLines += "import { $($vueImports -join ', ') } from 'vue';"
}

# Add non-vue imports (skip duplicates of vue imports)
$nonVueImports = @()
foreach ($imp in $importLines) {
    $fromMatch = [regex]::Match($imp, '(?<=from\s+["''])([^"'']+)')
    if ($fromMatch.Success -and $fromMatch.Groups[1].Value -ne 'vue') {
        $nonVueImports += $imp
    }
}
if ($nonVueImports.Count -gt 0) {
    $scriptSetupLines += ""
    $nonVueImports | ForEach-Object { $scriptSetupLines += $_ }
}

# Check if we need axios import (if methods use axios but no import)
$needsAxios = $scriptInner -match '\baxios\b' -and -not ($importLines -match 'axios')
if ($needsAxios) {
    $scriptSetupLines += "import axios from 'axios';"
}

# defineProps
if ($props) {
    $scriptSetupLines += "`nconst props = defineProps($props);"
}

# defineEmits
if ($emits) {
    $scriptSetupLines += "const emit = defineEmits($emits);"
}

# refs and reactive
$scriptSetupLines += ""
foreach ($key in $dataKeys.Keys) {
    $info = $dataKeys[$key]
    if ($info['type'] -eq 'reactive') {
        $scriptSetupLines += "const $key = reactive($($info['val']));"
    } else {
        $scriptSetupLines += "const $key = ref($($info['val']));"
    }
}

# computed
$scriptSetupLines += ""
foreach ($name in $computed.Keys) {
    $body = $computed[$name]
    $scriptSetupLines += "const $name = computed(() => {$body`n});"
}

# Methods (converted to flat functions)
$scriptSetupLines += ""
foreach ($name in $methods.Keys) {
    if ($name -eq 'mounted' -or $name -eq 'beforeUnmount') { continue }
    $body = $methods[$name]
    # Replace this.x with x.value for refs, x for reactive
    foreach ($key in $dataKeys.Keys) {
        $info = $dataKeys[$key]
        $valSuffix = if ($info['type'] -eq 'ref') { '.value' } else { '' }
        $body = $body -replace "(?<!\w)this\.$key(?!\w)", "$key$valSuffix"
    }
    # Handle computed properties
    foreach ($cName in $computed.Keys) {
        $body = $body -replace "(?<!\w)this\.$cName(?!\w)", "$cName.value"
    }
    # Handle $emit -> emit
    $body = $body -replace '\$emit\(', 'emit('
    # Handle $nextTick -> nextTick
    $body = $body -replace '\$nextTick\(', 'nextTick('
    # Handle $refs.x -> x.value
    $body = $body -replace '(?<!\w)\$refs\.(\w+)', '$1.value'
    # Handle this.$forceUpdate() -> remove
    $body = $body -replace '\$forceUpdate\(\)', ''
    # Handle this.x() where x is a method
    foreach ($mName in $methods.Keys) {
        $body = $body -replace "(?<!\w)this\.$mName`(", "$mName`("
    }
    # Remove this. prefix for any remaining references
    $body = $body -replace 'this\.', ''

    $scriptSetupLines += "async function $body"
}

# Lifecycle hooks
$hasMountedHook = $methods.ContainsKey('mounted')
$hasBeforeUnmountHook = $methods.ContainsKey('beforeUnmount')

# Mounted: unwrap the method body and wrap in onMounted
if ($hasMountedHook) {
    $mountedBody = $methods['mounted']
    # Remove function signature line
    $mountedBody = $mountedBody -replace '^\s*mounted\s*\(\s*\)\s*\{?\s*', ''
    # Remove trailing }
    $mountedBody = $mountedBody.TrimEnd().TrimEnd('}').TrimEnd()
    foreach ($key in $dataKeys.Keys) {
        $info = $dataKeys[$key]
        $valSuffix = if ($info['type'] -eq 'ref') { '.value' } else { '' }
        $mountedBody = $mountedBody -replace "(?<!\w)this\.$key(?!\w)", "$key$valSuffix"
    }
    foreach ($mName in $methods.Keys) {
        $mountedBody = $mountedBody -replace "(?<!\w)this\.$mName`(", "$mName`("
    }
    $scriptSetupLines += "`nonMounted(() => {$mountedBody`n});"
}

if ($hasBeforeUnmountHook) {
    $unmountBody = $methods['beforeUnmount']
    $unmountBody = $unmountBody -replace '^\s*beforeUnmount\s*\(\s*\)\s*\{?\s*', ''
    $unmountBody = $unmountBody.TrimEnd().TrimEnd('}').TrimEnd()
    foreach ($key in $dataKeys.Keys) {
        $info = $dataKeys[$key]
        $valSuffix = if ($info['type'] -eq 'ref') { '.value' } else { '' }
        $unmountBody = $unmountBody -replace "(?<!\w)this\.$key(?!\w)", "$key$valSuffix"
    }
    foreach ($mName in $methods.Keys) {
        $unmountBody = $unmountBody -replace "(?<!\w)this\.$mName`(", "$mName`("
    }
    $scriptSetupLines += "`nonBeforeUnmount(() => {$unmountBody`n});"
}

# Build output
$output = "$templateBlock`n"
$output += "<script setup>`n"
$output += ($scriptSetupLines -join "`n")
$output += "`n</script>`n"
$output += ($styleBlocks -join "`n`n")

if ($DryRun) {
    Write-Host "=== Generated <script setup> ==="
    Write-Host $output
    Write-Host "=== File not written (DryRun) ==="
    exit 0
}

# Write backup
$backupPath = "$Path.bak"
Copy-Item $Path $backupPath -Force
Write-Host "Backup saved: $backupPath"

Set-Content -Path $Path -Value $output -NoNewline
Write-Host "Migrated: $Path"
```

- [ ] **Step 2: Test migration script on a simple component**

Run: `.\scripts\migrate-vue.ps1 -Path "resources/js/components/modulos/configuracion/helpdeskConfigCategoriasComponent.vue" -DryRun`
Expected: Output with `<script setup>` content (no file modified)

- [ ] **Step 3: Verify dry-run output looks correct**

Check that:
- `props: [...]` → `defineProps({...})`
- `data() { return { ... } }` → `const x = ref(...)`
- `methods: { foo() { ... } }` → `function foo() { ... }`
- `this.registros` → `registros.value`
- `mounted()` → `onMounted(() => {...})`

- [ ] **Step 4: Run on a real component and build**

Run: `.\scripts\migrate-vue.ps1 -Path "resources/js/components/modulos/configuracion/helpdeskConfigCategoriasComponent.vue"`
Run: `npm run build 2>&1`
Expected: Build succeeds

If build fails:
- Fix manual issues in the migrated component
- Update the migration script to handle the edge case
- Re-test

- [ ] **Step 5: Revert test component**

Run: `git checkout -- resources/js/components/modulos/configuracion/helpdeskConfigCategoriasComponent.vue`

---

### Task 5: Batch 1 — Configuración (13 components)

**Files:**
- Migrate: `resources/js/components/modulos/configuracion/*.vue` (13 files, excluding helpdesk* prefixed ones)

Components:
1. `configSedeComponent.vue`
2. `configProductoComponent.vue`
3. `configPrioridadComponent.vue`
4. `configDiagnosticoComponent.vue`
5. `configAdminSaludComponent.vue`
6. `configWhatsappComponent.vue`
7. `configTipoDocumentoComponent.vue`
8. `configServicioCitaComponent.vue`
9. `configServicioProductoComponent.vue`
10. `configPlantilla.vue`
11. `configTipoPlantilla.vue`
12. `configCitaRechazoMotivoComponent.vue`
13. `configConsentimientoInformadoComponent.vue`

- [ ] **Step 1: Migrate batch with script**

Run: `foreach ($f in Get-ChildItem "resources/js/components/modulos/configuracion" -Filter "*.vue" | Where-Object { $_.Name -notlike "helpdesk*" }) { & .\scripts\migrate-vue.ps1 -Path $f.FullName }`

- [ ] **Step 2: Manual review — verify each component's script block**

Check per file:
- `this.$refs.dt` → `${templateRefName}.value` (add `const dt = ref(null);` if missing from data)
- `$emit('...')` → `emit('...')` with `const emit = defineEmits(['...']);`
- `$nextTick(...)` → `nextTick(...)` with import
- `$forceUpdate()` removed
- Remove `import axios from 'axios'` if already `window.axios`
- Catch blocks: if only calls `handleAxiosErrorToast(e)`, can be empty `catch { /* interceptor handles toast */ }` but keep `catch` with comment
- Verify `defineProps` matches original shape

- [ ] **Step 3: Build verification**

Run: `npm run build 2>&1`
Expected: Succeeds

- [ ] **Step 4: Lint check**

Run: `npm run lint 2>&1`
Expected: Only pre-existing warnings (no new errors)

---

### Task 6: Batch 2 — HelpDesk (5 + 3 = 8 components)

**Files:**
- `resources/js/components/modulos/help-desk/*.vue` (5)
- `resources/js/components/modulos/configuracion/helpdeskConfig*.vue` (3)

Components:
1. `helpdeskTicketsComponent.vue`
2. `helpdeskCrearTicketComponent.vue`
3. `helpdeskDetalleTicketComponent.vue`
4. `helpdeskNotificacionesComponent.vue`
5. `helpdeskReportesComponent.vue`
6. `helpdeskConfigCategoriasComponent.vue`
7. `helpdeskConfigEstadosComponent.vue`
8. `helpdeskConfigPrioridadesComponent.vue`

- [ ] **Step 1: Migrate batch with script**

Run: `Get-ChildItem "resources/js/components/modulos/help-desk" -Filter "*.vue" | ForEach-Object { & .\scripts\migrate-vue.ps1 -Path $_.FullName }`
Run: `Get-ChildItem "resources/js/components/modulos/configuracion" -Filter "helpdesk*.vue" | ForEach-Object { & .\scripts\migrate-vue.ps1 -Path $_.FullName }`

- [ ] **Step 2: Manual review**

Check `helpdeskDetalleTicketComponent.vue` especially — it likely has complex logic with dynamic renders and may need careful `reactive()` vs `ref()` handling.

- [ ] **Step 3: Build verification**

Run: `npm run build 2>&1`

---

### Task 7: Batch 3 — Estudios + Ecografías (12 components)

**Files:**
- `resources/js/components/modulos/estudios/*.vue` (6)
- `resources/js/components/modulos/ecografias/*.vue` (6)

Components (Estudios):
1. `AsignarEstudioComponent.vue`
2. `estudioAsignadosComponent.vue`
3. `leerEstudioComponent.vue`
4. `transcribirEstudioComponent.vue`
5. `notificarEstudioComponent.vue`
6. `audio-texto.vue`

Components (Ecografías):
7. `admisionarComponent.vue`
8. `ecografiasAdmisionadasComponent.vue`
9. `lecturaComponent.vue`
10. `ecografiasConsentimientosComponent.vue`
11. `ecografiasConsentimientosPlantillasComponent.vue`
12. `ecografiaFirmaConsentimientoComponent.vue`

- [ ] **Step 1: Migrate batch with script**

Run: `Get-ChildItem "resources/js/components/modulos/estudios" -Filter "*.vue" | ForEach-Object { & .\scripts\migrate-vue.ps1 -Path $_.FullName }`
Run: `Get-ChildItem "resources/js/components/modulos/ecografias" -Filter "*.vue" | ForEach-Object { & .\scripts\migrate-vue.ps1 -Path $_.FullName }`

- [ ] **Step 2: Manual review**

Key files to verify carefully:
- `leerEstudioComponent.vue` — likely uses `$refs` extensively for document viewer interaction
- `lecturaComponent.vue` — may have complex DOM manipulation
- `ecografiaFirmaConsentimientoComponent.vue` — canvas/signature pad interactions
- `audio-texto.vue` — audio recording state machine
- Check `$forceUpdate()` removals especially in components with canvas/DOM updates

- [ ] **Step 3: Build verification**

Run: `npm run build 2>&1`

---

### Task 8: Batch 4 — Seguridad + Reportes + Citas + Agenda (14 components)

**Files:**

Seguridad (4):
1. `usuarioComponent.vue`
2. `roleComponente.vue`
3. `auditLogComponent.vue`
4. `usuarioPerfil.vue`

Reportes (1):
5. `reporteProduccionComponent.vue`

Citas + Agenda (6):
6. `citasComponent.vue`
7. `gestionCitasComponent.vue`
8. `citasRevisionComponent.vue`
9. `citasNotificationBadgeComponent.vue`
10. `recomendacionProductoComponent.vue`
11. `agendaDisponibilidadComponent.vue`

Tele-Radiología + Otras Plataformas (3):
12. `teleRadiologiaEnviarEstudioComponent.vue`
13. `teleRadiologiaTranscibirComponent.vue`
14. `OtrasPlataformasAsignarEstudioComponent.vue`

- [ ] **Step 1: Migrate batch with script**

Run: `Get-ChildItem "resources/js/components/modulos/seguridad" -Filter "*.vue" | ForEach-Object { & .\scripts\migrate-vue.ps1 -Path $_.FullName }`
Run: `Get-ChildItem "resources/js/components/modulos/reportes" -Filter "*.vue" | ForEach-Object { & .\scripts\migrate-vue.ps1 -Path $_.FullName }`
Run: `Get-ChildItem "resources/js/components/modulos/citas" -Filter "*.vue" | ForEach-Object { & .\scripts\migrate-vue.ps1 -Path $_.FullName }`
Run: `Get-ChildItem "resources/js/components/modulos/agenda" -Filter "*.vue" | ForEach-Object { & .\scripts\migrate-vue.ps1 -Path $_.FullName }`
Run: `Get-ChildItem "resources/js/components/modulos/tele-radiologia" -Filter "*.vue" | ForEach-Object { & .\scripts\migrate-vue.ps1 -Path $_.FullName }`
Run: `Get-ChildItem "resources/js/components/modulos/otras-plataformas" -Filter "*.vue" | ForEach-Object { & .\scripts\migrate-vue.ps1 -Path $_.FullName }`

- [ ] **Step 2: Manual review**

Key files:
- `usuarioComponent.vue` — most complex component in the app (1079 lines). Look for:
  - `let me = this;` patterns inside callbacks — must convert to arrow functions
  - Multiple async calls in `mounted()`
  - `$refs` for DataTable
  - jQuery modal show/hide calls
- `citasComponent.vue` — likely has complex date/time logic
- `auditLogComponent.vue` — data-heavy with filters

- [ ] **Step 3: Build verification**

Run: `npm run build 2>&1`

---

### Task 9: Batch 5 — Shared/Commons/Auth/Dashboard (17 components)

**Files:**

Commons (11):
1. `sidebarComponent.vue`
2. `footerComponent.vue`
3. `formComponent.vue`
4. `formPacienteComponent.vue`
5. `datosPacienteDisplayComponent.vue`
6. `userProfileAndSearchComponent.vue`
7. `toggleAndNavItemsComponent.vue`
8. `logoComponent.vue`
9. `LoadingSpinner.vue`
10. `VoiceModal.vue`
11. `seleccionarSede.vue` (varios)

Shared (3):
12. `Select2.vue`
13. `FormattedTextEditor.vue`
14. `EmojiPicker.vue`

Auth + Dashboard + App (3):
15. `login.vue` (autenticacion)
16. `dashboardComponent.vue` (dashboard)
17. `App.vue` + `Auth.vue`

- [ ] **Step 1: Migrate batch with script**

Run: `Get-ChildItem "resources/js/components/comunes" -Filter "*.vue" | ForEach-Object { & .\scripts\migrate-vue.ps1 -Path $_.FullName }`
Run: `Get-ChildItem "resources/js/components/shared" -Filter "*.vue" | ForEach-Object { & .\scripts\migrate-vue.ps1 -Path $_.FullName }`
Run: `Get-ChildItem "resources/js/components/autenticacion" -Filter "*.vue" | ForEach-Object { & .\scripts\migrate-vue.ps1 -Path $_.FullName }`
Run: `Get-ChildItem "resources/js/components/dashboard" -Filter "*.vue" | ForEach-Object { & .\scripts\migrate-vue.ps1 -Path $_.FullName }`
Run: `Get-ChildItem "resources/js/components" -Filter "App.vue","Auth.vue" | ForEach-Object { & .\scripts\migrate-vue.ps1 -Path $_.FullName }`
Run: `Get-ChildItem "resources/js/components/modulos/varios" -Filter "*.vue" | ForEach-Object { & .\scripts\migrate-vue.ps1 -Path $_.FullName }`

- [ ] **Step 2: Manual review**

Key files:
- `sidebarComponent.vue` — likely has `$emit` for sidebar toggle, `$refs` for DOM
- `userProfileAndSearchComponent.vue` — user dropdown, search
- `Select2.vue` — custom component wrapping jQuery Select2. Must verify `defineProps` + `defineEmits` + `watch` are correct
- `FormattedTextEditor.vue` — custom editor, may use `$emit` extensively
- `EmojiPicker.vue` — event-heavy, check click-outside handling
- `login.vue` — simple but critical
- `VoiceModal.vue` — permission checks, streaming state
- `LoadingSpinner.vue` — simplest component, props-only

- [ ] **Step 3: Build verification**

Run: `npm run build 2>&1`

- [ ] **Step 4: Final full lint + build**

Run: `npm run lint 2>&1`
Run: `npm run build 2>&1`
Expected: Both succeed. If lint has pre-existing warnings, confirm none were introduced.

---

### Task 10: Clean up migration backups

**Files:**
- Remove: all `.vue.bak` files created by the migration script

- [ ] **Step 1: Confirm everything works before cleanup**

Run: `npm run build 2>&1`
Expected: OK

- [ ] **Step 2: Remove backup files**

Run: `Get-ChildItem -Recurse "resources/js/components" -Filter "*.vue.bak" | Remove-Item -Force`
Run: `if (-not (Get-ChildItem -Recurse "resources/js/components" -Filter "*.vue.bak")) { "All backups removed" }`

---

### Self-Review

**1. Spec coverage:**
- Backend Exception Handler: Task 1 ✅
- useToast composable: Task 2 ✅
- Axios interceptor: Task 3 ✅
- Migration script: Task 4 ✅
- Batch 1 Configuración (~13): Task 5 ✅
- Batch 2 HelpDesk (~8): Task 6 ✅ (8 components)
- Batch 3 Estudios+Ecografías (~12): Task 7 ✅ (12 components)
- Batch 4 Seguridad+Reportes+Citas+Agenda+Tele+Otras (~14): Task 8 ✅ (count matches spec's range)
- Batch 5 Shared/Commons/Auth/Dashboard (~17): Task 9 ✅ (exactly 17 listed)

**2. Placeholder scan:**
- All steps contain actual code or exact commands ✅
- No "TBD", "TODO", "implement later" ✅
- No "Similar to Task N" ✅
- No "Write tests for the above" without code ✅
- Migration script has full PowerShell code ✅
- Bash commands are exact with expected output ✅

**3. Type consistency:**
- `useToast()` returns `{ show, success, error, warning, info }` consistently in Task 2 and Task 3 ✅
- `handleAxiosErrorToast` becomes a no-op passthrough in Task 3 ✅
- Migration script outputs consistent `<script setup>` format across all tasks ✅
- Props handling: `defineProps()` in all batches ✅

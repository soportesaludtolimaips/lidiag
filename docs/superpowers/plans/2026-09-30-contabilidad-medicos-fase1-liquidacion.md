# Liquidación de pagos a médicos (Fase 1) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Calcular cuánto se le debe a cada médico por periodo (sede + rango de fechas) desde las lecturas, con exclusiones visibles, exportación a Excel y guard de lector asignado.

**Architecture:** `LiquidacionService::calcular()` une lecturas con contratos vigentes por sede+fecha y clasifica por prioridad; `LiquidacionController` expone calcular/exportar; el componente Vue muestra líneas y exclusiones; `leerEstudio()` rechaza a no asignados.

**Tech Stack:** Laravel 13, MySQL (`mysql`), Vue 3 (Options/Composition existente), Maatwebsite Excel, Spatie Permission.

## Global Constraints

- PowerShell 5.1: no `&&`; encadenar con `;` y `if ($?)`.
- NO commitear sin autorización explícita ("si, commitea" / "dale").
- NO ejecutar `migrate` ni `migrate:rollback`. El seeder de permisos de la Task 2 SÍ se ejecuta con `--class` (solo inserta permisos idempotentes, no toca el esquema).
- Scripts de verificación en `C:\Users\Jhon\AppData\Local\Temp\opencode\`, NUNCA en el repo, ejecutados con `php`.
- BD real `lidiag` (conexión `mysql`). Escrituras de verificación SIEMPRE en `DB::beginTransaction()` / `DB::rollBack()`; tablas `contratos*`, `estudios`, `estudios_productos` con conteos originales al terminar. Solo SELECT fuera de transacción.
- Pint en Windows: `vendor\bin\pint.bat` (sin extensión no da salida).
- Estilo PHP según `docs/CODING_STANDARDS.md`: PHPDoc con descripción, flujo del proceso numerado, `@param`, `@returns`, `@author Jhon Javer Lozano Arce`, `@date`.
- Estilo JS: JSDoc con `@param`/`@returns`/`@author`/`@date`, como `contratoComponent.vue`.
- `error(string $message, int $status = 400, ...)` — respetar ese orden.
- Sin tests automatizados (estándar del dueño); verificación = scripts PHP + `pint` + `eslint` + `build` + `route:list`.
- Archivos del working tree en LF (el proyecto exige LF; `core.autocrlf=true` los convierte a CRLF al pasar por git: si `pint` falla solo con `line_ending`, normalizar a LF).
- Decisiones de negocio vigentes (spec Fase 1, D1–D6): Alta → `valor_urgencias`, resto → `valor_consulta`; lector = `estudios.medico_id` con `fechor_lectura` existente; sin-asignado = anomalía; solo el asignado lee; transcribir no se paga; recálculo vivo.

---

## File Structure

- `app/Services/Liquidacion/LiquidacionService.php` (nuevo): `calcular()` — única fuente de la liquidación; también la usa el export.
- `app/Http/Controllers/Liquidacion/LiquidacionController.php` (nuevo): `calcular()` + `exportar()`, inyección del servicio por constructor como `ContratoController`.
- `routes/api_liquidacion.php` (nuevo) + registro en `bootstrap/app.php` junto a los demás `api_*.php`.
- `database/seeders/PermisionLiquidacionSeeder.php` (nuevo) + `$this->call()` en `DatabaseSeeder.php`: `liquidacion.index`, `liquidacion.exportar` (solo Administrador, idempotente como `PermisionContratacionSeeder`).
- `app/Services/Estudios/EstudioService.php` (modificar `leerEstudio`): guard D4.
- `app/Exports/LiquidacionExport.php` (nuevo): `FromCollection, WithHeadings, WithMapping, WithColumnWidths`; recibe sede+rango y reutiliza `LiquidacionService::calcular()`.
- `resources/js/components/modulos/liquidacion/liquidacionComponent.vue` (nuevo) + registro en `resources/js/app.js` + vista `resources/views/modulos/liquidacion/liquidacion.blade.php` + `Route::view` en `routes/web.php` + entrada en `resources/views/comunes/sidebar.blade.php`.
- `docs/CONTROLLERS.md` (nueva sección `Módulo: Liquidación`).

---

### Task 1: `LiquidacionService::calcular()` — el corazón ✅ COMPLETA (commit `67171ef6f`)

(Servicio implementado y verificado. Se conserva el resumen; el código vive en el repo.)

- Produce: `calcular(int $sedeId, string $fechaInicio, string $fechaFin): array` con `lineas`, `exclusiones` (5 categorías), `totales por_medico + gran_total`.

---

### Task 2: Controller, rutas y permisos

**Files:**
- Create: `app/Http/Controllers/Liquidacion/LiquidacionController.php`
- Create: `routes/api_liquidacion.php`
- Modify: `bootstrap/app.php` (registrar el archivo junto a los demás `api_*.php`)
- Create: `database/seeders/PermisionLiquidacionSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php` (agregar el `$this->call()`)

**Interfaces:**
- Consumes: `LiquidacionService::calcular()` de la Task 1.
- Produces: `GET /api/liquidacion?sede_id&fecha_inicio&fecha_fin` → JSON con líneas/exclusiones/totales; permisos `liquidacion.index`, `liquidacion.exportar` en BD (el `exportar` se expone en la Task 6).

- [ ] **Step 1: Crear el controller**

```php
<?php

namespace App\Http\Controllers\Liquidacion;

use App\Http\Controllers\Controller;
use App\Services\Liquidacion\LiquidacionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LiquidacionController extends Controller
{
    public function __construct(protected LiquidacionService $liquidacionService) {}

    /**
     * Calcula la liquidación de un periodo.
     *
     * @param  Request  $request  Filtros sede_id, fecha_inicio, fecha_fin.
     *
     * @returns \Illuminate\Http\JsonResponse Líneas, exclusiones y totales.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-09-30
     */
    public function calcular(Request $request): JsonResponse
    {
        $validated = $this->validarFiltros($request);

        $result = $this->liquidacionService->calcular(
            $validated['sede_id'],
            $validated['fecha_inicio'],
            $validated['fecha_fin']
        );

        return response()->json($result, $result['status']);
    }

    /**
     * Reglas de los filtros de liquidación.
     *
     * @param  Request  $request  Petición con los filtros.
     *
     * @return array<string, mixed> Datos ya validados.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-09-30
     */
    private function validarFiltros(Request $request): array
    {
        return $request->validate([
            'sede_id' => 'required|integer|exists:config_sedes,id',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
        ]);
    }
}
```

- [ ] **Step 2: Crear `routes/api_liquidacion.php` y registrarlo**

```php
<?php

use App\Http\Controllers\Liquidacion\LiquidacionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    // Calcular liquidación por sede y rango de fechas
    Route::get('/liquidacion', [LiquidacionController::class, 'calcular'])
        ->name('api.liquidacion.calcular')
        ->middleware(['permission:liquidacion.index']);
});
```

En `bootstrap/app.php`, agregar `'api_liquidacion.php',` a la lista junto a los demás `api_*.php`.

- [ ] **Step 3: Crear el seeder de permisos y registrarlo**

`database/seeders/PermisionLiquidacionSeeder.php` con el formato exacto de `PermisionContratacionSeeder` (namespace `Database\Seeders`, `forgetCachedPermissions`, `first`-o-create por `name`+`guard web`, `syncRoles(['Administrador'])`) y permisos `['liquidacion.index', 'liquidacion.exportar']`. Agregar `$this->call(PermisionLiquidacionSeeder::class);` en `DatabaseSeeder.php` con el `use` correspondiente.

- [ ] **Step 4: Ejecutar el seeder y listar rutas**

Run: `php artisan db:seed --class=Database\Seeders\PermisionLiquidacionSeeder`
Expected: sin errores (solo inserta 2 permisos idempotentes; re-ejecutable).

Run: `php artisan route:list --path=api/liquidacion`
Expected: `GET api/liquidacion` con nombre `api.liquidacion.calcular`.

- [ ] **Step 5: Verificar el endpoint contra la BD real**

Script `verify_liquidacion_api.php` (patrón Task 1, en transacción con rollback): crear contrato+detalle+estudios como en Task 1-Step 2, invocar `LiquidacionController::calcular` con Request de filtros, confirmar HTTP 200 + 2 líneas + gran_total 110000; 4 casos 422 (sin sede, sede inexistente, sin fechas, fin anterior a inicio); rollback con conteos originales.

- [ ] **Step 6: Verificar estilo**

Run: `vendor\bin\pint.bat --test app/Http/Controllers/Liquidacion/LiquidacionController.php routes/api_liquidacion.php database/seeders/PermisionLiquidacionSeeder.php`
Expected: `{"tool":"pint","result":"passed"}`

- [ ] **Step 7: Pedir autorización y commitear**

```bash
git add app/Http/Controllers/Liquidacion/LiquidacionController.php routes/api_liquidacion.php bootstrap/app.php database/seeders/PermisionLiquidacionSeeder.php database/seeders/DatabaseSeeder.php
git commit -m "feat(liquidacion): endpoint de calculo con rutas y permisos"
```

---

### Task 3: Guard D4 en `leerEstudio()` — solo el asignado lee

**Files:**
- Modify: `app/Services/Estudios/EstudioService.php` (método `leerEstudio`, líneas 224-245)

**Interfaces:**
- Consumes: `EstudioProducto::estudio()` (verificado que existe), `Auth::id()`, `BaseService::error()` con status explícito.
- Produces: `leerEstudio()` rechaza con 422 si no hay asignado y 403 si el lector no es el asignado. Sin cambios en el controller (propaga `status`).

- [ ] **Step 1: Agregar el guard dentro de la transacción**

En `leerEstudio()`, después de `$item = EstudioProducto::lockForUpdate()->findOrFail($estudioProductoId);` y antes del chequeo 409 existente, insertar:

```php
                $estudio = $item->estudio;

                if ($estudio === null || $estudio->medico_id === null) {
                    return $this->error('Este estudio no tiene médico asignado. Asigne un médico antes de leerlo.', 422);
                }

                if ((int) $estudio->medico_id !== (int) Auth::id()) {
                    return $this->error('Solo el médico asignado puede registrar la lectura de este estudio.', 403);
                }
```

Notas vinculantes:
- `$this->error('...', 403)` con status explícito (no `unauthorized()`, cuyo status no está verificado).
- `Auth` ya está importado en el archivo (usado en `create` y `guardarTranscripcion`); si no, agregar el `use`.
- No tocar el chequeo 409 ni el resto del método. El controller no cambia.

- [ ] **Step 2: Verificar el guard contra la BD real**

Script `verify_guard_lectura.php` (transacción con rollback; simular Auth con `Auth::loginUsingId($medicoA)` — verificar que el guard `web` lo permite, si no, probar el servicio con el usuario autenticado vía `actingAs` en el mismo proceso):
1. Estudio asignado a A + producto sin leer → leer como A → 200 y `fechor_lectura` fijada.
2. Estudio asignado a A + producto sin leer → leer como B → 403, `fechor_lectura` sigue null.
3. Estudio SIN asignado (`medico_id` null) → leer como A → 422.
4. Producto ya leído → 409 intacto (regresión).
5. Rollback con conteos originales.

Run: `php C:\Users\Jhon\AppData\Local\Temp\opencode\verify_guard_lectura.php`
Expected: `200, 403, 422, 409` en ese orden + rollback limpio.

- [ ] **Step 3: Verificar estilo**

Run: `vendor\bin\pint.bat --test app/Services/Estudios/EstudioService.php`
Expected: `{"tool":"pint","result":"passed"}`. OJO: `EstudioController.php` y `EstudioService.php` fallan `pint --test` del repo completo por `line_ending` preexistente; si el fallo es SOLO `line_ending` por CRLF del working tree, normalizar el archivo a LF y reintentar. Si hay otros fixers con cambios reales, reportarlo.

- [ ] **Step 4: Pedir autorización y commitear**

```bash
git add app/Services/Estudios/EstudioService.php
git commit -m "feat(estudios): solo el medico asignado puede registrar la lectura"
```

---

### Task 4: Frontend `liquidacionComponent.vue`

**Files:**
- Create: `resources/js/components/modulos/liquidacion/liquidacionComponent.vue`

**Interfaces:**
- Consumes: `GET /api/liquidacion?sede_id&fecha_inicio&fecha_fin` de la Task 2 (`res.data.data` con `lineas`, `exclusiones`, `totales`).
- Produces: componente con filtros, tabla de líneas, totales por médico y panel de exclusiones. Registro en `app.js` y ruta web en la Task 5.

- [ ] **Step 1: Crear el componente**

Estructura mínima (Composition API + `<script setup>`, como `contratoComponent.vue`):
- `const filtros = ref({ sede_id: '', fecha_inicio: '', fecha_fin: '' })`, `const resultado = ref(null)`, `const loading = ref(false)`.
- `async function calcular()`: valida sede y fechas no vacías (toast si faltan), `GET /api/liquidacion` con `params: filtros`, asigna `res.data.data`, catch con toast del mensaje.
- `async function exportar()`: `window.open` a `/api/liquidacion/exportar?sede_id=...&fecha_inicio=...&fecha_fin=...` (patrón de `contratoComponent.vue:1244`; la ruta existe desde la Task 6, pero el botón puede crearse ya: si da 404 antes de Task 6, es esperado).
- Template: filtros (select de sede con las sedes disponibles + 2 inputs `type="date"` + botón Calcular), tabla de `lineas` (médico, CUPS, producto, clase, valor, fecha, contrato), tabla de `totales.por_medico` + gran total, panel de `exclusiones` (categoría + conteo).
- JSDoc en cada función con `@param`/`@returns`/`@author Jhon Javer Lozano Arce`/`@date 2026-09-30`.
- Sedes para el select: reutilizar el endpoint que usa `contratoComponent` para sedes (verificar cuál es en el archivo; no inventar uno nuevo).

- [ ] **Step 2: Lint y build**

Run: `npx eslint resources/js/components/modulos/liquidacion/liquidacionComponent.vue`
Expected: 0 errors (con `--fix` solo para formato).

Run: `npm run build`
Expected: `✓ built in ...`

- [ ] **Step 3: Pedir autorización y commitear**

```bash
git add resources/js/components/modulos/liquidacion/liquidacionComponent.vue
git commit -m "feat(liquidacion): vista de lineas, totales y exclusiones"
```

---

### Task 5: Ruta web, blade, registro y menú

**Files:**
- Create: `resources/views/modulos/liquidacion/liquidacion.blade.php`
- Modify: `routes/web.php` (agregar `Route::view`)
- Modify: `resources/js/app.js` (import + `app.component`)
- Modify: `resources/views/comunes/sidebar.blade.php` (nueva sección)

**Interfaces:**
- Consumes: componente de la Task 4, permiso `liquidacion.index` de la Task 2.
- Produces: URL `/liquidacion` navegable desde el menú para administradores.

- [ ] **Step 1: Crear la vista blade**

`resources/views/modulos/liquidacion/liquidacion.blade.php` (espejo de `modulos/contratos/contratos.blade.php`):

```blade
@extends('layouts.app')

@section('content')
    <liquidacion-component :usuarioactual='@json(Auth::user())' />
@endsection
```

Verificar primero la estructura real de `contratos.blade.php` (layout y sección) y replicarla; si difiere, usar la real y reportarlo.

- [ ] **Step 2: Registrar la ruta web**

En `routes/web.php`, junto a la de contratos:

```php
Route::view('/liquidacion', 'modulos.liquidacion.liquidacion')
    ->name('liquidacion.ver')
    ->middleware(['auth', 'permission:liquidacion.index']);
```

- [ ] **Step 3: Registrar el componente en `app.js`**

Junto al registro de `ContratoComponent` (líneas 172-173):

```javascript
import liquidacionComponent from "./components/modulos/liquidacion/liquidacionComponent.vue";
app.component("LiquidacionComponent", liquidacionComponent);
```

- [ ] **Step 4: Agregar la entrada al sidebar**

En `sidebar.blade.php`, después del bloque `CONTRATACIÓN` (líneas 269-286), agregar espejo con `liquidacion.index`:

```blade
                    @if (auth()->user()->hasAnyPermission(['liquidacion.index']))
                        <li class="nav-small-cap">--- LIQUIDACIÓN</li>
                        <li>
                            <a class="has-arrow waves-effect waves-dark" href="#" aria-expanded="false">
                                <i class="icon-Calculator"></i>
                                <span class="hide-menu">Liquidación</span>
                            </a>
                            <ul aria-expanded="false" class="collapse">
                                @can('liquidacion.index')
                                    <li>
                                        <a href="{{ route('liquidacion.ver') }}">
                                            <i class="fa fa-money text-info"></i> Liquidación
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </li>
                    @endif
```

Si la clase del icono `icon-Calculator` no existe en el set usado, usar una de las existentes (`icon-Contrast`, `fa fa-money`) y reportarlo.

- [ ] **Step 5: Build y verificación de navegación**

Run: `npm run build` (el registro en `app.js` exige rebuild).
Expected: `✓ built in ...`.

Verificación: `php artisan route:list --name=liquidacion.ver` muestra la ruta; abrir `/liquidacion` en navegador como administrador (lo hace el dueño al probar; el implementador confirma route:list + build).

- [ ] **Step 6: Pedir autorización y commitear**

```bash
git add resources/views/modulos/liquidacion/liquidacion.blade.php routes/web.php resources/js/app.js resources/views/comunes/sidebar.blade.php
git commit -m "feat(liquidacion): ruta web, vista y menu"
```

---

### Task 6: Export a Excel

**Files:**
- Create: `app/Exports/LiquidacionExport.php`
- Modify: `app/Http/Controllers/Liquidacion/LiquidacionController.php` (agregar `exportar`)
- Modify: `routes/api_liquidacion.php` (agregar ruta con `liquidacion.exportar`)

**Interfaces:**
- Consumes: `LiquidacionService::calcular()` (reutiliza, no duplica), permiso `liquidacion.exportar` (creado en Task 2).
- Produces: `GET /api/liquidacion/exportar?sede_id&fecha_inicio&fecha_fin` → descarga `.xlsx`.

- [ ] **Step 1: Crear el export**

```php
<?php

namespace App\Exports;

use App\Services\Liquidacion\LiquidacionService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Export de la liquidación de un periodo a Excel.
 *
 * Reutiliza LiquidacionService::calcular() para no duplicar la lógica.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-09-30
 */
class LiquidacionExport implements FromCollection, WithColumnWidths, WithHeadings, WithMapping
{
    /**
     * @param  int     $sedeId       Sede liquidada.
     * @param  string  $fechaInicio  Inicio en Y-m-d.
     * @param  string  $fechaFin     Fin en Y-m-d.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-09-30
     */
    public function __construct(protected int $sedeId, protected string $fechaInicio, protected string $fechaFin) {}

    /**
     * @returns \Illuminate\Support\Collection<int, array> Líneas de la liquidación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-09-30
     */
    public function collection()
    {
        $result = app(LiquidacionService::class)->calcular($this->sedeId, $this->fechaInicio, $this->fechaFin);

        return collect($result['data']['lineas'] ?? []);
    }

    /**
     * @returns array<int, string> Encabezados en español.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-09-30
     */
    public function headings(): array
    {
        return ['Médico', 'CUPS', 'Producto', 'Clase', 'Valor', 'Fecha lectura', 'Contrato'];
    }

    /**
     * @param  array  $linea  Una línea de la liquidación.
     *
     * @returns array<int, mixed> Fila del Excel.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-09-30
     */
    public function map($linea): array
    {
        return [$linea['medico_name'], $linea['cod_cups'], $linea['nom_produc'], $linea['clase'], $linea['valor'], $linea['fecha_lectura'], $linea['contrato_id']];
    }

    /**
     * @returns array<string, float> Anchos de columna.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-09-30
     */
    public function columnWidths(): array
    {
        return ['A' => 30, 'B' => 15, 'C' => 45, 'D' => 12, 'E' => 15, 'F' => 15, 'G' => 12];
    }
}
```

Nota vinculante: `calcular()` devuelve `$this->success([...])` cuyo payload va en la clave `data` (patrón verificado en Tasks 1-2: `$result['data']['lineas']`). Si la forma real difiere, usar la real y reportarlo.

- [ ] **Step 2: Agregar `exportar` al controller y su ruta**

En el controller, después de `calcular`:

```php
    /**
     * Descarga la liquidación de un periodo en Excel.
     *
     * @param  Request  $request  Mismos filtros que calcular.
     *
     * @returns \Symfony\Component\HttpFoundation\BinaryFileResponse Archivo xlsx.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-09-30
     */
    public function exportar(Request $request): BinaryFileResponse
    {
        $validated = $this->validarFiltros($request);

        return Excel::download(
            new LiquidacionExport($validated['sede_id'], $validated['fecha_inicio'], $validated['fecha_fin']),
            'liquidacion.xlsx'
        );
    }
```

Agregar los `use` de `Excel`, `LiquidacionExport` y `BinaryFileResponse` (verificar los namespaces reales: `Maatwebsite\Excel\Facades\Excel` según el uso del proyecto; si difiere, usar el real).

En `routes/api_liquidacion.php`, antes del cierre del grupo:

```php
    // Descargar liquidación del periodo en Excel
    Route::get('/liquidacion/exportar', [LiquidacionController::class, 'exportar'])
        ->name('api.liquidacion.exportar')
        ->middleware(['permission:liquidacion.exportar']);
```

CUIDADO con el orden: `/liquidacion/exportar` debe declararse ANTES de `GET /liquidacion` solo si hubiera captura; como son distinto número de segmentos no hay conflicto, pero por legibilidad va junto a la otra ruta. Verificar con `route:list` que ambas resuelven.

- [ ] **Step 3: Verificar descarga contra la BD real**

Script `verify_liquidacion_export.php` (transacción con rollback): crear fixtures como Task 1, invocar `exportar` con Request de filtros y confirmar que la respuesta es un descargable con contenido no vacío (tamaño > 0) y que contiene los encabezados esperados (leer el xlsx generado con PhpSpreadsheet desde el mismo script); 422 con filtros inválidos; rollback limpio.

- [ ] **Step 4: Verificar estilo**

Run: `vendor\bin\pint.bat --test app/Exports/LiquidacionExport.php app/Http/Controllers/Liquidacion/LiquidacionController.php routes/api_liquidacion.php`
Expected: `{"tool":"pint","result":"passed"}`

- [ ] **Step 5: Pedir autorización y commitear**

```bash
git add app/Exports/LiquidacionExport.php app/Http/Controllers/Liquidacion/LiquidacionController.php routes/api_liquidacion.php
git commit -m "feat(liquidacion): exporta la liquidacion a Excel"
```

---

### Task 7: Documentación y cierre

**Files:**
- Modify: `docs/CONTROLLERS.md` (nueva sección `Módulo: Liquidación`)

**Interfaces:**
- Consumes: todo lo implementado.
- Produces: índice actualizado.

- [ ] **Step 1: Documentar en `docs/CONTROLLERS.md`**

Agregar antes de `## Módulo: Raíz (General)`, siguiendo el formato del archivo:

```markdown
## Módulo: Liquidación

### `LiquidacionController.php`

Calcula y exporta la liquidación de pagos a médicos por sede y periodo.

-   **`calcular`**: Devuelve líneas, exclusiones con conteo y totales por médico.
-   **`exportar`**: Descarga la liquidación del periodo en Excel.
```

- [ ] **Step 2: Pedir autorización y commitear**

```bash
git add docs/CONTROLLERS.md
git commit -m "docs(liquidacion): documenta el modulo de liquidacion"
```

---

## Self-Review

**1. Cobertura del spec:**

| Sección del spec | Tarea |
|---|---|
| Hecho liquidable (4 condiciones) | Task 1 |
| Lector D2 | Task 1 (deriva) + Task 3 (guard) |
| Clase de tarifa D1 | Task 1 |
| Contrato aplicable y valor | Task 1 |
| Exclusiones visibles | Task 1 (cálculo) + Task 4 (muestra) |
| D3 sin-asignado como anomalía | Task 1 (categoría) |
| D4 guard en leerEstudio | Task 3 |
| D5 sin pago por transcripción | Ninguna tarea lo implementa (correcto: alcance) |
| D6 recálculo vivo | Ninguna tabla de cierre (correcto: alcance) |
| Vista por sede+rango | Task 4 + Task 5 |
| Exportar a Excel | Task 6 |
| Q3/Q6 defaults | Documentados, sin tarea (correcto) |

Sin huecos.

**2. Placeholders:** ninguno. Cada paso trae código, comando o valores exactos. Las dos adaptaciones permitidas (relaciones del `with()`, namespaces de `Excel`) indican qué verificar y qué usar.

**3. Consistencia de tipos:** `calcular()` devuelve `lineas/exclusiones/totales`; Task 4 lee `res.data.data.*`; Task 6 usa `$result['data']['lineas']`; `exportar` devuelve `BinaryFileResponse`; rutas `api.liquidacion.calcular/exportar` + web `liquidacion.ver`; permisos `liquidacion.index/exportar` en seeder, rutas y sidebar con los mismos nombres.



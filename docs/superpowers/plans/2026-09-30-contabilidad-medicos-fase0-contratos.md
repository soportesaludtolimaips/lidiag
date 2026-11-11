# Contabilidad de médicos — Fase 0: módulo de contratos — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Dejar el módulo de contratos operativo y confiable para que sirva de fuente de tarifas de la futura liquidación de pagos a médicos.

**Architecture:** Se conserva el modelo existente (Sede → 1 contrato vigente con fechas → N médicos, cada uno con su detalle producto + tarifas). El cambio central es reemplazar el `sync` destructivo (`DELETE` + `INSERT`) por un diff no destructivo que solo inserta, actualiza o marca retirado, dejando cada cambio registrado en `activity_log` por el listener global. Se agrega `preview()` para que el usuario vea el diff antes de aplicarlo, y un endpoint de `cobertura` que expone los productos del catálogo sin tarifa.

**Tech Stack:** Laravel 13, PHP 8, MySQL, Vue 3 (`<script setup>`), Maatwebsite Excel, Spatie permissions, helpers `toastMessage`/`confirmAction` (SweetAlert2 + Toastr).

## Global Constraints

- Rama `main` del proyecto. **Nunca** hacer merge de `origin/consentimientos`.
- **No commitear nada sin autorización explícita del usuario.** El usuario dice "commitea" o "dale" antes de cada commit; si no, dejar los cambios en el working tree.
- PowerShell 5.1: no usar `&&`. Encadenar con `;` y `if ($?)`.
- Los archivos con cambios `.vue` pasan por `lint-staged` al commitear, que ejecuta `eslint --fix` y `prettier --write`. Ejecutar `npx eslint <archivo>` y `npm run build` **antes** de pedir el commit.
- Verificación de estilo PHP: `vendor/bin/pint --test <archivos>`.
- Documentación obligatoria en PHP según `docs/CODING_STANDARDS.md`: PHPDoc con descripción, **Flujo del proceso** numerado, `@param`, `@return`, `@author Jhon Javer Lozano Arce`, `@date`.
- Documentación obligatoria en Vue: JSDoc con descripción, `@param`, `@returns`, `@author Jhon Javer Lozano Arce`, `@date`.
- Valores monetarios como `decimal(12,2)`, nunca `float`. En PHP nunca usar `float` para comparaciones de dinero.
- **No se crean tests automatizados** (acuerdo con el usuario: "olvida los test"). La verificación es `pint` + `eslint` + `build` + un script PHP contra la BD real con `DB::beginTransaction()` / `DB::rollBack()` + prueba manual del usuario.
- Los scripts de verificación PHP se escriben en `C:\Users\Jhon\AppData\Local\Temp\opencode\` y se ejecutan con `php`. En PowerShell los `\` rompen `php artisan tinker --execute`; usar siempre un archivo PHP.
- Las tres tablas `contratos`, `contratos_productos` y `contratos_medicos` están **vacías** al momento de escribir este plan.
- `tests/` está en `.gitignore`; no intentar agregar tests ahí.

---

## File Structure

**Crear:**

| Archivo | Responsabilidad |
|---|---|
| `database/migrations/2026_09_30_100000_add_vigencia_to_contratos_medicos_table.php` | `fecha_ingreso` y `fecha_retiro` en `contratos_medicos` |
| `database/migrations/2026_09_30_100100_add_unique_indexes_to_contratos_tables.php` | Índices únicos en los dos pivotes |
| `database/migrations/2026_09_30_100200_add_vigencia_index_to_contratos_table.php` | Índice `(sede_id, fecha_inicio, fecha_fin)` |

**Modificar:**

| Archivo | Responsabilidad del cambio |
|---|---|
| `app/Http/Requests/Contratacion/ContratoRequest.php` | Corregir `exists:sedes,id` → `exists:config_sedes,id` |
| `app/Services/Contratacion/ContratoService.php` | Anti-solapamiento, `calcularDiffMedicos()`, `previewMedicos()`, `syncMedicos()` no destructivo, `corregirContratoVigente()`, `cobertura()` |
| `app/Http/Controllers/Contratacion/ContratoController.php` | Endpoints `previewMedicos` y `cobertura`, reglas de `fecha_ingreso` |
| `routes/api_contratacion.php` | Permisos separados por acción, rutas `preview` y `cobertura` |
| `resources/js/components/modulos/contratos/contratoComponent.vue` | Panel de cobertura, confirmación del diff, columna Fecha Ingreso, fix `medicoOptions` |
| `app/Exports/ContratoDetallePlantillaExport.php` | Columna `Fecha Ingreso` en la plantilla |
| `app/Imports/ContratoMedicoImport.php` | Leer la columna opcional `Fecha Ingreso` |
| `docs/CONTROLLERS.md` | Documentar los dos métodos nuevos del controller |

---

### Task 1: Migraciones de integridad

Las tres tablas están vacías, así que los índices únicos no colisionan y las columnas nuevas no requieren backfill.

**Files:**
- Create: `database/migrations/2026_09_30_100000_add_vigencia_to_contratos_medicos_table.php`
- Create: `database/migrations/2026_09_30_100100_add_unique_indexes_to_contratos_tables.php`
- Create: `database/migrations/2026_09_30_100200_add_vigencia_index_to_contratos_table.php`

**Interfaces:**
- Consumes: nada.
- Produces: columnas `contratos_medicos.fecha_ingreso` (DATE NULL) y `contratos_medicos.fecha_retiro` (DATE NULL); índice único `(contrato_id, producto_id)` en `contratos_productos`; índice único `(contrato_id, medico_id, contratos_productos_id)` en `contratos_medicos`; índice `(sede_id, fecha_inicio, fecha_fin)` en `contratos`.

- [ ] **Step 1: Crear la migración de vigencia por médico**

Crear `database/migrations/2026_09_30_100000_add_vigencia_to_contratos_medicos_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @date 2026-09-30
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contratos_medicos', function (Blueprint $table) {
            $table->date('fecha_ingreso')->nullable()->after('valor_urgencias');
            $table->date('fecha_retiro')->nullable()->after('fecha_inicio');
            $table->index(['contrato_id', 'estado'], 'contratos_medicos_contrato_estado_index');
        });
    }

    public function down(): void
    {
        Schema::table('contratos_medicos', function (Blueprint $table) {
            $table->dropIndex('contratos_medicos_contrato_estado_index');
            $table->dropColumn(['fecha_ingreso', 'fecha_retiro']);
        });
    }
};
```

- [ ] **Step 2: Crear la migración de índices únicos**

Crear `database/migrations/2026_09_30_100100_add_unique_indexes_to_contratos_tables.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @date 2026-09-30
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contratos_productos', function (Blueprint $table) {
            $table->unique(['contrato_id', 'producto_id'], 'contratos_productos_contrato_producto_unique');
        });

        Schema::table('contratos_medicos', function (Blueprint $table) {
            $table->unique(
                ['contrato_id', 'medico_id', 'contratos_productos_id'],
                'contratos_medicos_contrato_medico_producto_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('contratos_medicos', function (Blueprint $table) {
            $table->dropUnique('contratos_medicos_contrato_medico_producto_unique');
        });

        Schema::table('contratos_productos', function (Blueprint $table) {
            $table->dropUnique('contratos_productos_contrato_producto_unique');
        });
    }
};
```

- [ ] **Step 3: Crear la migración de índice de vigencia**

Crear `database/migrations/2026_09_30_100200_add_vigencia_index_to_contratos_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @date 2026-09-30
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contratos', function (Blueprint $table) {
            $table->index(['sede_id', 'fecha_inicio', 'fecha_fin'], 'contratos_sede_vigencia_index');
        });
    }

    public function down(): void
    {
        Schema::table('contratos', function (Blueprint $table) {
            $table->dropIndex('contratos_sede_vigencia_index');
        });
    }
};
```

- [ ] **Step 4: Ejecutar las migraciones**

Run: `php artisan migrate --force`
Expected: tres líneas `INFO Running migrations:` con `2026_09_30_100000_add_vigencia_to_contratos_medicos_table`, `..._100100_add_unique_indexes_to_contratos_tables` y `..._100200_add_vigencia_index_to_contratos_table`, y al final `INFO Migrating complete.`

- [ ] **Step 5: Verificar que las columnas existen**

Crear `C:\Users\Jhon\AppData\Local\Temp\opencode\verify_migraciones.php`:

```php
<?php

require 'C:/laragon/www/lidiag/vendor/autoload.php';
$app = require 'C:/laragon/www/lidiag/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$cols = Schema::getColumnListing('contratos_medicos');
foreach (['fecha_ingreso', 'fecha_retiro'] as $c) {
    echo (in_array($c, $cols) ? 'OK   ' : 'FALTA ') . "contratos_medicos.$c\n";
}

$indices = [];
foreach (['contratos_productos', 'contratos_medicos', 'contratos'] as $tabla) {
    foreach (DB::select("SHOW INDEX FROM `$tabla`") as $fila) {
        $indices[] = $fila->Key_name;
    }
}

foreach ([
    'contratos_productos_contrato_producto_unique',
    'contratos_medicos_contrato_medico_producto_unique',
    'contratos_sede_vigencia_index',
] as $idx) {
    echo (in_array($idx, $indices, true) ? 'OK   ' : 'FALTA ') . "indice $idx\n";
}
```

Run: `php C:\Users\Jhon\AppData\Local\Temp\opencode\verify_migraciones.php`
Expected: cinco líneas `OK`: las dos columnas y los tres índices.

- [ ] **Step 6: Pedir autorización y commitear**

Preguntar al usuario. Si autoriza:

```bash
git add database/migrations/2026_09_30_100000_add_vigencia_to_contratos_medicos_table.php database/migrations/2026_09_30_100100_add_unique_indexes_to_contratos_tables.php database/migrations/2026_09_30_100200_add_vigencia_index_to_contratos_table.php
git commit -m "feat(contratos): vigencia por medico e indices de integridad en contratos"
```

---

### Task 2: Corregir la validación de sede (bloqueante)

Sin esto `POST /api/contratos` y `PUT` devuelven 500 y no se puede crear ni el primer contrato.

**Files:**
- Modify: `app/Http/Requests/Contratacion/ContratoRequest.php:66`

**Interfaces:**
- Consumes: nada.
- Produces: `ContratoRequest` valida contra la tabla real `config_sedes`.

- [ ] **Step 1: Confirmar el bug antes de tocarlo**

Crear `C:\Users\Jhon\AppData\Local\Temp\opencode\verify_bug_sedes.php`:

```php
<?php

require 'C:/laragon/www/lidiag/vendor/autoload.php';
$app = require 'C:/laragon/www/lidiag/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Schema;

echo 'existe tabla sedes: ' . (Schema::hasTable('sedes') ? 'SI' : 'NO') . PHP_EOL;
echo 'existe tabla config_sedes: ' . (Schema::hasTable('config_sedes') ? 'SI' : 'NO') . PHP_EOL;
```

Run: `php C:\Users\Jhon\AppData\Local\Temp\opencode\verify_bug_sedes.php`
Expected: `existe tabla sedes: NO` y `existe tabla config_sedes: SI`. Esto prueba que `exists:sedes,id` revienta.

- [ ] **Step 2: Corregir la regla**

En `app/Http/Requests/Contratacion/ContratoRequest.php`, cambiar la línea 66:

```php
'registro_decoded.sede_id' => 'required|integer|exists:config_sedes,id',
```

- [ ] **Step 3: Verificar que la validación ahora pasa**

Crear `C:\Users\Jhon\AppData\Local\Temp\opencode\verify_request_sede.php`:

```php
<?php

require 'C:/laragon/www/lidiag/vendor/autoload.php';
$app = require 'C:/laragon/www/lidiag/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

$sede = DB::table('config_sedes')->first();

$v = Validator::make(
    [
        'registro_decoded' => [
            'sede_id' => $sede->id,
            'fecha_inicio' => '2026-01-01',
            'fecha_fin' => '2026-12-31',
        ],
    ],
    [
        'registro_decoded.sede_id' => 'required|integer|exists:config_sedes,id',
        'registro_decoded.fecha_inicio' => 'required|date',
        'registro_decoded.fecha_fin' => 'required|date|after_or_equal:registro_decoded.fecha_inicio',
    ]
);

echo $v->passes()
    ? "OK: la regla con config_sedes valida\n"
    : 'FALLA: '.json_encode($v->errors()).PHP_EOL;
```

Run: `php C:\Users\Jhon\AppData\Local\Temp\opencode\verify_request_sede.php`
Expected: `OK: la regla con config_sedes valida`

Si saliera `FALLA`, el problema no es la regla sino el `sede_id` de prueba; imprimir `$sede->id` y reintentar con otro registro.

- [ ] **Step 4: Verificar estilo**

Run: `vendor/bin/pint --test app/Http/Requests/Contratacion/ContratoRequest.php`
Expected: `{"tool":"pint","result":"passed"}`

- [ ] **Step 5: Pedir autorización y commitear**

```bash
git add app/Http/Requests/Contratacion/ContratoRequest.php
git commit -m "fix(contratos): validar la sede contra config_sedes, que es la tabla real"
```

---

### Task 3: Anti-solapamiento de contratos por sede

Una sede no puede tener dos contratos con rangos de fechas que se crucen.

**Files:**
- Modify: `app/Services/Contratacion/ContratoService.php` (agregar método privado antes de `create`, líneas ~108)

**Interfaces:**
- Consumes: `Contrato` model, `DB::transaction`, `BaseService::error()`.
- Produces: `private function existeSolapamiento(int $sedeId, string $fechaInicio, string $fechaFin, ?int $ignorarContratoId = null): bool`. Se invoca dentro de la transacción de `create()` y `update()`.

- [ ] **Step 1: Agregar el método de detección**

En `app/Services/Contratacion/ContratoService.php`, insertar justo antes de `public function create(array $data): array`:

```php
    /**
     * Detecta si otra sede tiene un contrato con un rango de fechas que se cruza.
     *
     * Se usa dentro de la misma transacción que inserta o actualiza, con el
     * bloqueo de fila ya tomado, para que dos cargas simultáneas no puedan
     * insertar contratos solapados.
     *
     * @param  int         $sedeId             Sede del contrato a validar.
     * @param  string      $fechaInicio        Fecha de inicio en formato Y-m-d.
     * @param  string      $fechaFin           Fecha de fin en formato Y-m-d.
     * @param  int|null    $ignorarContratoId  Contrato a excluir (el que se está editando).
     *
     * @return bool true si existe solapamiento.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-09-30
     */
    private function existeSolapamiento(
        int $sedeId,
        string $fechaInicio,
        string $fechaFin,
        ?int $ignorarContratoId = null
    ): bool {
        $query = Contrato::where('sede_id', $sedeId)
            ->whereDate('fecha_inicio', '<=', $fechaFin)
            ->whereDate('fecha_fin', '>=', $fechaInicio);

        if ($ignorarContratoId !== null) {
            $query->where('id', '!=', $ignorarContratoId);
        }

        return $query->exists();
    }
```

- [ ] **Step 2: Bloquear la sede y validar en `create()`**

Reemplazar el método `create()` completo:

```php
    /**
     * Crea un nuevo contrato dentro de una transacción.
     *
     * Flujo del proceso:
     * 1. Bloquea la fila de la sede para serializar cargas concurrentes.
     * 2. Verifica que no exista otro contrato de esa sede con fechas cruzadas.
     * 3. Inserta el contrato y devuelve la sede ya cargada.
     *
     * @param  array  $data  Datos del contrato: sede_id, fecha_inicio, fecha_fin, observaciones, estado.
     *
     * @returns array Contrato creado, o error 422 si las fechas se solapan.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-20
     */
    public function create(array $data): array
    {
        return $this->transaction(function () use ($data) {
            ConfigSede::where('id', $data['sede_id'])->lockForUpdate()->first();

            if ($this->existeSolapamiento($data['sede_id'], $data['fecha_inicio'], $data['fecha_fin'])) {
                return $this->error(
                    'La sede ya tiene un contrato vigente en ese rango de fechas. '
                    .'Cierre el contrato anterior antes de crear uno nuevo.',
                    422
                );
            }

            $contrato = Contrato::create([
                'sede_id' => $data['sede_id'],
                'fecha_inicio' => $data['fecha_inicio'],
                'fecha_fin' => $data['fecha_fin'],
                'observaciones' => $data['observaciones'] ?? null,
                'estado' => $data['estado'] ?? 1,
            ]);

            return $this->created($contrato->load('sede:id,cod_sede,nom_sede'));
        });
    }
```

- [ ] **Step 3: Validar también en `update()`**

Reemplazar el closure interno de `update()`:

```php
        return $this->transaction(function () use ($contrato, $data) {
            ConfigSede::where('id', $data['sede_id'] ?? $contrato->sede_id)->lockForUpdate()->first();

            $inicio = $data['fecha_inicio'] ?? $contrato->fecha_inicio;
            $fin = $data['fecha_fin'] ?? $contrato->fecha_fin;

            if ($this->existeSolapamiento($contrato->sede_id, $inicio, $fin, $contrato->id)) {
                return $this->error(
                    'La sede ya tiene un contrato vigente en ese rango de fechas. '
                    .'Cierre el contrato anterior antes de guardar este.',
                    422
                );
            }

            $contrato->update([
                'sede_id' => $data['sede_id'] ?? $contrato->sede_id,
                'fecha_inicio' => $inicio,
                'fecha_fin' => $fin,
                'observaciones' => $data['observaciones'] ?? $contrato->observaciones,
                'estado' => $data['estado'] ?? $contrato->estado,
            ]);

            return $this->updated($contrato->load('sede:id,cod_sede,nom_sede'));
        });
```

- [ ] **Step 4: Confirmar el import de `ConfigSede`**

Verificar que exista en el `use` del archivo. Si no, agregarlo junto a los demás modelos:

```php
use App\Models\Agenda\ConfigSede;
```

Run: `Select-String -Path app/Services/Contratacion/ContratoService.php -Pattern "^use "`
Expected: una línea con `use App\Models\...ConfigSede;`. Si el modelo está en otro namespace, usar el que muestre el grep.

- [ ] **Step 5: Verificar el comportamiento con la BD real**

Crear `C:\Users\Jhon\AppData\Local\Temp\opencode\verify_solapamiento.php`:

```php
<?php

require 'C:/laragon/www/lidiag/vendor/autoload.php';
$app = require 'C:/laragon/www/lidiag/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Contratacion\Contrato;
use App\Services\Contratacion\ContratoService;
use Illuminate\Support\Facades\DB;

$svc = app(ContratoService::class);
$sede = DB::table('config_sedes')->first();

DB::beginTransaction();

$uno = $svc->create([
    'sede_id' => $sede->id,
    'fecha_inicio' => '2021-01-01',
    'fecha_fin' => '2021-12-31',
    'observaciones' => 'PRUEBA TEMPORAL',
    'estado' => 1,
]);
echo '1) crear 2021            -> status=' . $uno['status'] . PHP_EOL;

$dos = $svc->create([
    'sede_id' => $sede->id,
    'fecha_inicio' => '2021-06-01',
    'fecha_fin' => '2022-05-31',
    'observaciones' => 'DEBE FALLAR',
    'estado' => 1,
]);
echo '2) crear solapado        -> status=' . $dos['status'] . ' | ' . $dos['message'] . PHP_EOL;

$tres = $svc->create([
    'sede_id' => $sede->id,
    'fecha_inicio' => '2022-01-01',
    'fecha_fin' => '2022-12-31',
    'observaciones' => 'PRUEBA TEMPORAL',
    'estado' => 1,
]);
echo '3) crear 2022 consecutivo -> status=' . $tres['status'] . PHP_EOL;

$total = Contrato::where('observaciones', 'PRUEBA TEMPORAL')->count();
echo '4) contratos creados en la prueba: ' . $total . ' (deben ser 2)' . PHP_EOL;

DB::rollBack();
echo '5) rollback: contratos en BD = ' . Contrato::count() . PHP_EOL;
```

Run: `php C:\Users\Jhon\AppData\Local\Temp\opencode\verify_solapamiento.php`
Expected:
```
1) crear 2021            -> status=201
2) crear solapado        -> status=422 | La sede ya tiene un contrato vigente en ese rango de fechas....
3) crear 2022 consecutivo -> status=201
4) contratos creados en la prueba: 2 (deben ser 2)
5) rollback: contratos en BD = 0
```

- [ ] **Step 6: Verificar estilo**

Run: `vendor/bin/pint --test app/Services/Contratacion/ContratoService.php`
Expected: `{"tool":"pint","result":"passed"}`

- [ ] **Step 7: Pedir autorización y commitear**

```bash
git add app/Services/Contratacion/ContratoService.php
git commit -m "feat(contratos): impide contratos solapados en la misma sede"
```

---

### Task 4: `previewMedicos()` y el diff no destructivo

El corazón del plan: reemplazar el `DELETE` + `INSERT` por un diff que nunca borra.

**Files:**
- Modify: `app/Services/Contratacion/ContratoService.php:244-295` (método `syncMedicos` completo)

**Interfaces:**
- Consumes: `Contrato`, `ContratoProducto`, `ContratoMedico`, `BaseService::updated()`.
- Produces:
  - `public function calcularDiffMedicos(int $contratoId, array $items, bool $retirarFaltantes = false): array` — devuelve `['altas' => [], 'cambios' => [], 'retiros' => [], 'ausentes' => [], 'errores' => []]`, donde `altas` y `cambios` son arrays de `['medico_id','producto_id','valor_consulta','valor_urgencias','fecha_ingreso']`, `retiros` y `ausentes` son arrays de `['contratos_medicos_id','medico_id','producto_id','medico_name','nom_produc']`.
  - `public function previewMedicos(int $contratoId, array $items): array` — envuelve el diff y devuelve el contrato más el diff, sin escribir nada.

- [ ] **Step 1: Agregar `calcularDiffMedicos()` y `previewMedicos()`**

Reemplazar el método `syncMedicos()` completo (líneas 244-295) por:

```php
    /**
     * Calcula el diferencia entre el detalle recibido y el detalle guardado.
     *
     * Flujo del proceso:
     * 1. Traduce cada producto_id a su contratos_productos_id dentro del contrato.
     * 2. Clasifica cada item recibido como alta, cambio o error.
     * 3. Detecta los registros existentes que no vienen en el payload: quedan
     *    en "ausentes" y solo se retiran si se piden explícitamente.
     * 4. No escribe nada en la base de datos.
     *
     * @param  int    $contratoId         Contrato al que pertenece el detalle.
     * @param  array  $items              Items con medico_id, producto_id, valor_consulta,
     *                                    valor_urgencias y fecha_ingreso opcional.
     * @param  bool   $retirarFaltantes   Si true, los ausentes se marcan como retiros.
     *
     * @returns array Estructura con las claves altas, cambios, retiros, ausentes y errores.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-09-30
     */
    public function calcularDiffMedicos(int $contratoId, array $items, bool $retirarFaltantes = false): array
    {
        $mapaProductos = ContratoProducto::where('contrato_id', $contratoId)
            ->pluck('id', 'producto_id');

        $existentes = ContratoMedico::where('contrato_id', $contratoId)
            ->with(['medico:id,name', 'contratoProducto.producto:id,cod_cups,nom_produc'])
            ->get()
            ->keyBy(fn ($m) => $m->medico_id . '-' . ($m->contratoProducto->producto_id ?? 0));

        $altas = [];
        $cambios = [];
        $errores = [];
        $vistos = [];

        foreach ($items as $item) {
            $medicoId = (int) $item['medico_id'];
            $productoId = (int) $item['producto_id'];
            $clave = $medicoId . '-' . $productoId;
            $vistos[] = $clave;

            if (! $mapaProductos[$productoId] ?? false) {
                $errores[] = "El producto {$productoId} no está configurado en este contrato.";

                continue;
            }

            $valores = [
                'medico_id' => $medicoId,
                'producto_id' => $productoId,
                'valor_consulta' => $item['valor_consulta'] ?? 0,
                'valor_urgencias' => $item['valor_urgencias'] ?? 0,
                'fecha_ingreso' => $item['fecha_ingreso'] ?? null,
            ];

            $registro = $existentes->get($clave);

            if (! $registro) {
                $altas[] = $valores;

                continue;
            }

            $cambio = $registro->valor_consulta != $valores['valor_consulta']
                || $registro->valor_urgencias != $valores['valor_urgencias']
                || $registro->fecha_ingreso != $valores['fecha_ingreso'];

            if ($cambio) {
                $valores['contratos_medicos_id'] = $registro->id;
                $cambios[] = $valores;
            }
        }

        $ausentes = [];
        $retiros = [];

        foreach ($existentes as $clave => $registro) {
            if (in_array($clave, $vistos, true)) {
                continue;
            }

            $resumen = [
                'contratos_medicos_id' => $registro->id,
                'medico_id' => $registro->medico_id,
                'producto_id' => $registro->contratoProducto->producto_id ?? null,
                'medico_name' => $registro->medico->name ?? '',
                'nom_produc' => $registro->contratoProducto->producto->nom_produc ?? '',
            ];

            $ausentes[] = $resumen;

            if ($retirarFaltantes) {
                $retiros[] = $resumen;
            }
        }

        return compact('altas', 'cambios', 'retiros', 'ausentes', 'errores');
    }

    /**
     * Devuelve el diff del detalle de médicos sin aplicarlo, para que el
     * frontend muestre al usuario exactamente qué va a cambiar antes de confirmar.
     *
     * @param  int    $contratoId  Contrato a previsualizar.
     * @param  array  $items       Items del payload de sincronización.
     *
     * @returns array Contrato con relaciones más las claves del diff.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-09-30
     */
    public function previewMedicos(int $contratoId, array $items): array
    {
        $contrato = Contrato::find($contratoId);

        if (! $contrato) {
            return $this->notFound('Contrato no encontrado');
        }

        $diff = $this->calcularDiffMedicos($contratoId, $items);

        return $this->success(array_merge(
            ['contrato' => $contrato],
            $diff
        ));
    }
```

- [ ] **Step 2: Reemplazar `syncMedicos()` por la versión que aplica el diff**

Inmediatamente después del método anterior, agregar:

```php
    /**
     * Aplica el diff del detalle de médicos. Nunca borra registros: los items
     * nuevos se insertan, los existentes con valores distintos se actualizan y
     * los que no vienen en el payload solo se retiran si el cliente lo pide
     * explícitamente con retirar_faltantes.
     *
     * Flujo del proceso:
     * 1. Calcula el diff contra el estado guardado.
     * 2. Si hay errores de validación, devuelve 422 y no escribe nada.
     * 3. Inserta las altas con fecha_ingreso en null (aplica desde el inicio
     *    del contrato) salvo que el item traiga una fecha.
     * 4. Actualiza solo los registros que cambiaron de valor.
     * 5. Marca como retirados los ausentes cuando retirer_faltantes es true.
     *
     * @param  int    $contratoId       Contrato a sincronizar.
     * @param  array  $data             Payload con medicos y retirar_faltantes opcional.
     *
     * @returns array Contrato actualizado con relaciones, o error 422.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-20
     */
    public function syncMedicos(int $contratoId, array $data): array
    {
        $contrato = Contrato::find($contratoId);

        if (! $contrato) {
            return $this->notFound('Contrato no encontrado');
        }

        $items = $data['medicos'];
        $retirarFaltantes = (bool) ($data['retirar_faltantes'] ?? false);
        $diff = $this->calcularDiffMedicos($contratoId, $items, $retirarFaltantes);

        if (! empty($diff['errores'])) {
            return $this->error(implode(' ', $diff['errores']), 422);
        }

        return $this->transaction(function () use ($contrato, $diff) {
            $mapaProductos = ContratoProducto::where('contrato_id', $contrato->id)
                ->pluck('id', 'producto_id');

            foreach ($diff['altas'] as $alta) {
                ContratoMedico::create([
                    'contrato_id' => $contrato->id,
                    'medico_id' => $alta['medico_id'],
                    'contratos_productos_id' => $mapaProductos[$alta['producto_id']],
                    'valor_contrato' => 0,
                    'valor_consulta' => $alta['valor_consulta'],
                    'valor_urgencias' => $alta['valor_urgencias'],
                    'fecha_ingreso' => $alta['fecha_ingreso'],
                    'estado' => 1,
                ]);
            }

            foreach ($diff['cambios'] as $cambio) {
                ContratoMedico::where('id', $cambio['contratos_medicos_id'])->update([
                    'valor_consulta' => $cambio['valor_consulta'],
                    'valor_urgencias' => $cambio['valor_urgencias'],
                    'fecha_ingreso' => $cambio['fecha_ingreso'],
                ]);
            }

            foreach ($diff['retiros'] as $retiro) {
                ContratoMedico::where('id', $retiro['contratos_medicos_id'])->update([
                    'estado' => 0,
                    'fecha_retiro' => now()->toDateString(),
                ]);
            }

            return $this->updated(
                $contrato->load([
                    'sede:id,cod_sede,nom_sede',
                    'medicos.medico:id,name,reg_med',
                    'medicos.contratoProducto.producto:id,cod_cups,nom_produc',
                ]),
                'Médicos del contrato actualizados correctamente'
            );
        });
    }
```

- [ ] **Step 3: Verificar que el diff no destruye datos**

Crear `C:\Users\Jhon\AppData\Local\Temp\opencode\verify_diff_medicos.php`:

```php
<?php

require 'C:/laragon/www/lidiag/vendor/autoload.php';
$app = require 'C:/laragon/www/lidiag/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Contratacion\Contrato;
use App\Models\Contratacion\ContratoMedico;
use App\Models\Contratacion\ContratoProducto;
use App\Services\Contratacion\ContratoService;
use Illuminate\Support\Facades\DB;

$svc = app(ContratoService::class);
$sede = DB::table('config_sedes')->first();
$productos = DB::table('config_productos')->where('requi_lectura', 1)->limit(3)->pluck('id')->toArray();
$medicos = DB::table('users')->limit(3)->pluck('id')->toArray();

echo 'necesitamos 3 productos y 3 medicos: ' .
    (count($productos) >= 3 && count($medicos) >= 3 ? 'OK' : 'INSUFICIENTE') . PHP_EOL;

DB::beginTransaction();

$contrato = $svc->create([
    'sede_id' => $sede->id,
    'fecha_inicio' => '2021-01-01',
    'fecha_fin' => '2021-12-31',
    'observaciones' => 'PRUEBA DIFF',
    'estado' => 1,
]);
$contratoId = $contrato['data']->id;

foreach ($productos as $pid) {
    ContratoProducto::create([
        'contrato_id' => $contratoId,
        'producto_id' => $pid,
        'valor_contrato' => 100000,
        'estado' => 1,
    ]);
}

$payloadInicial = [
    ['medico_id' => $medicos[0], 'producto_id' => $productos[0], 'valor_consulta' => 50000, 'valor_urgencias' => 80000],
    ['medico_id' => $medicos[0], 'producto_id' => $productos[1], 'valor_consulta' => 40000, 'valor_urgencias' => 0],
    ['medico_id' => $medicos[1], 'producto_id' => $productos[0], 'valor_consulta' => 30000, 'valor_urgencias' => 0],
];

$r = $svc->syncMedicos($contratoId, ['medicos' => $payloadInicial]);
echo '1) carga inicial            -> status=' . $r['status'] . ' | filas=' . ContratoMedico::where('contrato_id', $contratoId)->count() . PHP_EOL;

$preview = $svc->previewMedicos($contratoId, ['medicos' => [
    ['medico_id' => $medicos[0], 'producto_id' => $productos[0], 'valor_consulta' => 55000, 'valor_urgencias' => 80000],
    ['medico_id' => $medicos[2], 'producto_id' => $productos[2], 'valor_consulta' => 10000, 'valor_urgencias' => 0],
]]);
echo '2) preview: altas=' . count($preview['data']['altas'])
   . ' cambios=' . count($preview['data']['cambios'])
   . ' ausentes=' . count($preview['data']['ausentes'])
   . ' errores=' . count($preview['data']['errores']) . PHP_EOL;
echo '   (esperado: altas=1 cambios=1 ausentes=2 errores=0)' . PHP_EOL;

$antes = ContratoMedico::where('contrato_id', $contratoId)->count();
echo '3) filas tras el preview (debe seguir en ' . $antes . '): '
   . ContratoMedico::where('contrato_id', $contratoId)->count() . PHP_EOL;

$r = $svc->syncMedicos($contratoId, ['medicos' => [
    ['medico_id' => $medicos[0], 'producto_id' => $productos[0], 'valor_consulta' => 55000, 'valor_urgencias' => 80000],
    ['medico_id' => $medicos[2], 'producto_id' => $productos[2], 'valor_consulta' => 10000, 'valor_urgencias' => 0],
]]);
$despues = ContratoMedico::where('contrato_id', $contratoId)->count();
echo '4) sync sin retirar_faltantes -> status=' . $r['status'] . ' | filas=' . $despues . ' (deben seguir siendo ' . $antes . ')' . PHP_EOL;
echo '5) el valor 55000 se guardó? '
   . (ContratoMedico::where('contrato_id', $contratoId)->where('valor_consulta', 55000)->exists() ? 'SI' : 'NO') . PHP_EOL;

$r = $svc->syncMedicos($contratoId, [
    'medicos' => [
        ['medico_id' => $medicos[0], 'producto_id' => $productos[0], 'valor_consulta' => 55000, 'valor_urgencias' => 80000],
    ],
    'retirar_faltantes' => true,
]);
echo '6) sync con retirar_faltantes -> status=' . $r['status']
   . ' | filas=' . ContratoMedico::where('contrato_id', $contratoId)->count()
   . ' | inactivos=' . ContratoMedico::where('contrato_id', $contratoId)->where('estado', 0)->count() . PHP_EOL;
echo '7) el retirado tiene fecha_retiro? '
   . (ContratoMedico::where('contrato_id', $contratoId)->where('estado', 0)->whereNotNull('fecha_retiro')->exists() ? 'SI' : 'NO') . PHP_EOL;

$r = $svc->syncMedicos($contratoId, ['medicos' => [
    ['medico_id' => $medicos[0], 'producto_id' => 999999, 'valor_consulta' => 1, 'valor_urgencias' => 1],
]]);
echo '8) producto no configurado -> status=' . $r['status'] . ' | ' . $r['message'] . PHP_EOL;
echo '9) filas intactas tras el error: ' . ContratoMedico::where('contrato_id', $contratoId)->count() . PHP_EOL;

echo '10) hay registros en activity_log: ' . DB::table('activity_log')->count() . PHP_EOL;

DB::rollBack();
echo '11) rollback: contratos=' . Contrato::count() . ' medicos=' . ContratoMedico::count() . PHP_EOL;
```

Run: `php C:\Users\Jhon\AppData\Local\Temp\opencode\verify_diff_medicos.php`
Expected:
```
1) carga inicial            -> status=200 | filas=3
2) preview: altas=1 cambios=1 ausentes=2 errores=0
   (esperado: altas=1 cambios=1 ausentes=2 errores=0)
3) filas tras el preview (debe seguir en 3): 3
4) sync sin retirar_faltantes -> status=200 | filas=4 (deben seguir siendo 3)
5) el valor 55000 se guardó? SI
6) sync con retirar_faltantes -> status=200 | filas=4 | inactivos=3
7) el retirado tiene fecha_retiro? SI
8) producto no configurado -> status=422 | El producto 999999 no está configurado en este contrato.
9) filas intactas tras el error: 4
10) hay registros en activity_log: >0
11) rollback: contratos=0 medicos=0
```

Sobre el paso 4: tras un sync **sin** `retirar_faltantes` el total queda en 4 (3 originales + 1 alta nueva), porque los ausentes no se borran ni se inactivean. Lo que se verifica es que ninguno se perdió. Si la salida muestra menos de 4, el diff está destructivo: parar y reportar.

- [ ] **Step 4: Verificar estilo**

Run: `vendor/bin/pint --test app/Services/Contratacion/ContratoService.php`
Expected: `{"tool":"pint","result":"passed"}`

- [ ] **Step 5: Pedir autorización y commitear**

```bash
git add app/Services/Contratacion/ContratoService.php
git commit -m "feat(contratos): el detalle de medicos se sincroniza por diff y nunca se borra"
```

---

### Task 5: Endpoint de cobertura de productos

**Files:**
- Modify: `app/Services/Contratacion/ContratoService.php` (agregar al final de la clase)
- Modify: `app/Http/Controllers/Contratacion/ContratoController.php` (agregar método)

**Interfaces:**
- Consumes: `Contrato`, `ContratoProducto`, `ConfigProducto`.
- Produces: `public function cobertura(int $contratoId): array` con la forma `['contrato' => Contrato, 'total' => int, 'tarifados' => int, 'faltantes' => [['id','cod_cups','nom_produc']]]` dentro de `data`.

- [ ] **Step 1: Agregar el método al servicio**

Agregar al final de la clase `ContratoService`, antes de la llave de cierre:

```php
    /**
     * Indica qué productos del catálogo exigibles no tienen tarifa en el contrato.
     *
     * Flujo del proceso:
     * 1. Obtiene los contratos_productos del contrato con su producto.
     * 2. Compara contra el catálogo de productos que requieren lectura y están activos.
     * 3. Devuelve el total, cuántos están tarifados y el detalle de los que faltan.
     *
     * Un producto está tarifado si existe al menos un ContratoMedico activo que
     * lo referencia: si solo está cargado el producto sin médicos, hay lecturas
     * que se harían sin pagar.
     *
     * @param  int  $contratoId  Contrato a verificar.
     *
     * @returns array Contrato, totales y lista de productos faltantes con su código.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-09-30
     */
    public function cobertura(int $contratoId): array
    {
        $contrato = Contrato::find($contratoId);

        if (! $contrato) {
            return $this->notFound('Contrato no encontrado');
        }

        $catalogo = ConfigProducto::where('requi_lectura', 1)
            ->where('estado', 1)
            ->orderBy('cod_cups')
            ->get(['id', 'cod_cups', 'nom_produc']);

        $contratosProducto = ContratoProducto::where('contrato_id', $contratoId)
            ->pluck('id', 'producto_id')
            ->toArray();

        $tarifados = ContratoMedico::whereIn('contratos_productos_id', $contratosProducto)
            ->where('estado', 1)
            ->distinct()
            ->pluck('contratos_productos_id')
            ->toArray();

        $faltantes = [];

        foreach ($catalogo as $producto) {
            $contratoProductoId = $contratosProducto[$producto->id] ?? null;

            if ($contratoProductoId === null || ! in_array($contratoProductoId, $tarifados, true)) {
                $faltantes[] = [
                    'id' => $producto->id,
                    'cod_cups' => $producto->cod_cups,
                    'nom_produc' => $producto->nom_produc,
                ];
            }
        }

        return $this->success([
            'contrato' => $contrato->load('sede:id,cod_sede,nom_sede'),
            'total' => $catalogo->count(),
            'tarifados' => $catalogo->count() - count($faltantes),
            'faltantes' => $faltantes,
        ]);
    }
```

- [ ] **Step 2: Confirmar el namespace de `ConfigProducto`**

Run: `Select-String -Path app/Models/ConfigProducto.php -Pattern "^namespace" -ErrorAction SilentlyContinue` y `Get-ChildItem -Recurse app/Models -Filter ConfigProducto.php`.
Si no existe con ese nombre, buscar el modelo que corresponde a la tabla `config_productos` y usar su namespace real en el `use`.

- [ ] **Step 3: Agregar el método al controller**

En `app/Http/Controllers/Contratacion/ContratoController.php`, agregar antes de la llave de cierre de la clase:

```php
    /**
     * Informa qué productos del catálogo exigibles no tienen tarifa cargada.
     *
     * @param  int  $id  Identificador del contrato.
     *
     * @returns \Illuminate\Http\JsonResponse Totales de cobertura y lista de faltantes.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-09-30
     */
    public function cobertura(int $id): JsonResponse
    {
        $result = $this->contratoService->cobertura($id);

        return response()->json($result, $result['status']);
    }
```

- [ ] **Step 4: Registrar la ruta**

En `routes/api_contratacion.php`, agregar dentro del grupo `auth`, después de la ruta de `toggleEstado`:

```php
    // Cobertura de productos: cuáles del catálogo no tienen tarifa en el contrato
    Route::get('/contratos/{id}/cobertura', [ContratoController::class, 'cobertura'])
        ->name('api.contratos.cobertura')
        ->middleware(['permission:contratos.show']);
```

Cuidado: esta ruta debe declararse **antes** del `Route::resource` si se quiere evitar que `contratos/{contratos}` la capture; con `Route::resource` no hay conflicto porque `cobertura` es un segmento adicional, pero por legibilidad queda junto a las demás rutas explícitas.

- [ ] **Step 5: Verificar contra la BD real**

Crear `C:\Users\Jhon\AppData\Local\Temp\opencode\verify_cobertura.php`:

```php
<?php

require 'C:/laragon/www/lidiag/vendor/autoload.php';
$app = require 'C:/laragon/www/lidiag/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Contratacion\Contrato;
use App\Models\Contratacion\ContratoMedico;
use App\Models\Contratacion\ContratoProducto;
use App\Services\Contratacion\ContratoService;
use Illuminate\Support\Facades\DB;

$svc = app(ContratoService::class);
$sede = DB::table('config_sedes')->first();
$producto = DB::table('config_productos')->where('requi_lectura', 1)->first();
$medico = DB::table('users')->first();

DB::beginTransaction();

$c = $svc->create([
    'sede_id' => $sede->id,
    'fecha_inicio' => '2021-01-01',
    'fecha_fin' => '2021-12-31',
    'observaciones' => 'PRUEBA COBERTURA',
    'estado' => 1,
]);
$id = $c['data']->id;

$r = $svc->cobertura($id);
echo '1) contrato vacío: total=' . $r['data']['total']
   . ' tarifados=' . $r['data']['tarifados']
   . ' faltantes=' . count($r['data']['faltantes']) . PHP_EOL;
echo '   (esperado: tarifados=0 y faltantes=total)' . PHP_EOL;

$cp = ContratoProducto::create([
    'contrato_id' => $id,
    'producto_id' => $producto->id,
    'valor_contrato' => 100000,
    'estado' => 1,
]);

$r = $svc->cobertura($id);
echo '2) producto cargado sin médico: tarifados=' . $r['data']['tarifados'] . ' (debe seguir en 0)' . PHP_EOL;

ContratoMedico::create([
    'contrato_id' => $id,
    'medico_id' => $medico->id,
    'contratos_productos_id' => $cp->id,
    'valor_contrato' => 0,
    'valor_consulta' => 1000,
    'valor_urgencias' => 0,
    'estado' => 1,
]);

$r = $svc->cobertura($id);
echo '3) producto con médico: tarifados=' . $r['data']['tarifados'] . ' (debe ser 1)' . PHP_EOL;

ContratoMedico::where('contrato_id', $id)->update(['estado' => 0]);
$r = $svc->cobertura($id);
echo '4) médico retirado: tarifados=' . $r['data']['tarifados'] . ' (debe volver a 0)' . PHP_EOL;

DB::rollBack();
echo '5) rollback: contratos=' . Contrato::count() . PHP_EOL;
```

Run: `php C:\Users\Jhon\AppData\Local\Temp\opencode\verify_cobertura.php`
Expected: `1) ... tarifados=0`, `2) ... tarifados=0`, `3) ... tarifados=1`, `4) ... tarifados=0`, `5) rollback: contratos=0`

- [ ] **Step 6: Verificar estilo y commitear**

Run: `vendor/bin/pint --test app/Services/Contratacion/ContratoService.php app/Http/Controllers/Contratacion/ContratoController.php`
Expected: `{"tool":"pint","result":"passed"}`

Pedir autorización y luego:

```bash
git add app/Services/Contratacion/ContratoService.php app/Http/Controllers/Contratacion/ContratoController.php routes/api_contratacion.php
git commit -m "feat(contratos): endpoint de cobertura para detectar productos sin tarifa"
```

---

### Task 6: Endpoint de preview y permisos por acción

**Files:**
- Modify: `app/Http/Controllers/Contratacion/ContratoController.php` (agregar `previewMedicos`)
- Modify: `app/Http/Requests/Contratacion/ContratoRequest.php` (no cambia en esta task)
- Modify: `routes/api_contratacion.php` (rutas y permisos)

**Interfaces:**
- Consumes: `ContratoService::previewMedicos()` de la Task 4.
- Produces: `POST /api/contratos/{contratoId}/medicos/preview` y `PUT /api/contratos/{contratoId}/medicos` con el campo adicional `retirar_faltantes`.

- [ ] **Step 1: Agregar `previewMedicos` al controller**

En `ContratoController.php`, agregar antes de la llave de cierre:

```php
    /**
     * Previsualiza el cambio del detalle de médicos sin aplicarlo.
     *
     * @param  Request  $request     Payload con la lista medicos.
     * @param  int      $contratoId  Identificador del contrato.
     *
     * @returns \Illuminate\Http\JsonResponse Contrato más el diff calculado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-09-30
     */
    public function previewMedicos(Request $request, int $contratoId): JsonResponse
    {
        $validated = $this->validarDetalleMedicos($request);

        $result = $this->contratoService->previewMedicos($contratoId, $validated['medicos']);

        return response()->json($result, $result['status']);
    }
```

- [ ] **Step 2: Extraer las reglas compartidas a un método privado**

En `ContratoController.php`, reemplazar el cuerpo de `syncMedicos` y agregar el método privado junto a los demás:

```php
    /**
     * Reglas de validación del detalle de médicos-producto.
     *
     * @param  Request  $request  Petición con la lista medicos.
     *
     * @return array<string, mixed> Datos ya validados.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-09-30
     */
    private function validarDetalleMedicos(Request $request): array
    {
        return $request->validate([
            'medicos' => 'required|array',
            'medicos.*.medico_id' => 'required|integer|exists:users,id',
            'medicos.*.producto_id' => 'required|integer|exists:config_productos,id',
            'medicos.*.valor_consulta' => 'required|numeric|min:0',
            'medicos.*.valor_urgencias' => 'required|numeric|min:0',
            'medicos.*.fecha_ingreso' => 'nullable|date',
        ]);
    }
```

Y el nuevo `syncMedicos`:

```php
    /**
     * Aplica el diff del detalle de médicos del contrato.
     *
     * @param  Request  $request     Payload con medicos y retirar_faltantes opcional.
     * @param  int      $contratoId  Identificador del contrato.
     *
     * @returns \Illuminate\Http\JsonResponse Contrato actualizado o error de validación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-20
     */
    public function syncMedicos(Request $request, int $contratoId): JsonResponse
    {
        $validated = $this->validarDetalleMedicos($request);

        $result = $this->contratoService->syncMedicos($contratoId, $validated);

        return response()->json($result, $result['status']);
    }
```

- [ ] **Step 3: Agregar la ruta de preview y el flag de retiro**

En `routes/api_contratacion.php`, reemplazar la ruta de sync por dos:

```php
    // Previsualizar el diff del detalle de médicos (no escribe nada)
    Route::post('/contratos/{contratoId}/medicos/preview', [ContratoController::class, 'previewMedicos'])
        ->name('api.contratos.medicos.preview')
        ->middleware(['permission:contratos.detalle.edit']);

    // Sincronizar médicos-producto del contrato (diff, no destructivo)
    Route::put('/contratos/{contratoId}/medicos', [ContratoController::class, 'syncMedicos'])
        ->name('api.contratos.medicos.sync')
        ->middleware(['permission:contratos.detalle.edit']);
```

- [ ] **Step 4: Listar las rutas para confirmar**

Run: `php artisan route:list --path=api/contratos`
Expected: aparecen `api.contratos.cobertura`, `api.contratos.medicos.preview`, `api.contratos.medicos.sync`, `api.contratos.estado`, `api.contratos.productos.importar`, `api.contratos.plantilla.detalle`, `api.contratos.medicos.importar`, `api.contratos.plantilla.general`, y las cinco rutas del resource.

- [ ] **Step 5: Verificar estilo y commitear**

Run: `vendor\bin\pint.bat --test app/Http/Controllers/Contratacion/ContratoController.php`
Expected: `{"tool":"pint","result":"passed"}`

Pedir autorización y luego:

```bash
git add app/Http/Controllers/Contratacion/ContratoController.php routes/api_contratacion.php
git commit -m "feat(contratos): endpoint para previsualizar el diff del detalle de medicos"
```

---

### Task 7: Frontend — confirmación del diff y columna Fecha de ingreso

**Files:**
- Modify: `resources/js/components/modulos/contratos/contratoComponent.vue`

**Interfaces:**
- Consumes: `POST /api/contratos/{id}/medicos/preview` y `PUT /api/contratos/{id}/medicos` con `retirar_faltantes`.
- Produces: la función `guardarDetalles()` ahora pide confirmación antes de aplicar; `mapearMedicosDetalle()` incluye `fecha_ingreso`; `medicoOptions` usa el nombre real del usuario.

- [ ] **Step 1: Corregir `medicoOptions`**

`UserService::listByRoleAndModule` devuelve solo `id` y `name`, así que `reg_med` siempre salía vacío. En `contratoComponent.vue` líneas 702-708, reemplazar:

```javascript
const medicoOptions = computed(() =>
    medicos.value.map((m) => ({
        id: String(m.id),
        name: m.name,
        reg_med: m.reg_med || "",
    }))
);
```

por:

```javascript
const medicoOptions = computed(() =>
    medicos.value.map((m) => ({
        id: String(m.id),
        name: m.reg_med ? `${m.reg_med} - ${m.name}` : m.name,
    }))
);
```

- [ ] **Step 2: Agregar `fecha_ingreso` al mapeo**

En `mapearMedicosDetalle` (línea ~987), agregar el campo a lo devuelto y a su JSDoc:

```javascript
function mapearMedicosDetalle(medicosContrato) {
    return (medicosContrato || []).map((m) => ({
        id: m.id,
        medico_id: m.medico_id,
        medico_name: m.medico?.name || "",
        producto_id: m.contratoProducto?.producto?.id ?? null,
        cod_cups: m.contratoProducto?.producto?.cod_cups || "",
        nom_produc: m.contratoProducto?.producto?.nom_produc || "",
        valor_consulta: m.valor_consulta ?? 0,
        valor_urgencias: m.valor_urgencias ?? 0,
        fecha_ingreso: m.fecha_ingreso || "",
    }));
}
```

- [ ] **Step 3: Agregar la columna al template**

En la tabla del detalle (líneas 300-342), agregar el encabezado después de `<th>Val Urgencias</th>`:

```html
                                    <th>Fecha Ingreso</th>
```

y la celda después del input de `valor_urgencias`:

```html
                                    <td>
                                        <input
                                            type="date"
                                            v-model="item.fecha_ingreso"
                                            class="form-control"
                                        />
                                    </td>
```

Actualizar el `colspan` de la fila vacía de 6 a 7.

- [ ] **Step 4: Reescribir `guardarDetalles()` con preview y confirmación**

Reemplazar el método completo:

```javascript
/**
 * Previsualiza y guarda el detalle de médicos-producto del contrato.
 *
 * Antes de escribir pide al backend el diff y muestra al usuario cuántos
 * registros se agregan, cambian o quedan sin aplicar. Las filas que no vienen
 * en la lista solo se retiran si el usuario lo confirma de forma explícita.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-09-30
 */
async function guardarDetalles() {
    if (loadingGuardarDetalle.value) return;
    if (!detallesLocales.value.length) {
        toastMessage({
            heading: "Atención",
            text: "Agregue al menos un médico-producto",
            icon: "warning",
        });
        return;
    }

    loadingGuardarDetalle.value = true;
    try {
        const payload = {
            medicos: detallesLocales.value.map((d) => ({
                medico_id: d.medico_id,
                producto_id: d.producto_id,
                valor_consulta: d.valor_consulta ?? 0,
                valor_urgencias: d.valor_urgencias ?? 0,
                fecha_ingreso: d.fecha_ingreso || null,
            })),
        };

        const url = `/api/contratos/${contratoSeleccionado.value.id}/medicos`;
        const prev = await axios.post(`${url}/preview`, payload);
        const diff = prev.data.data;

        if (diff.errores?.length) {
            toastMessage({
                heading: "Error",
                text: diff.errores.join(" "),
                icon: "error",
            });
            return;
        }

        const resumen = `Se agregarán ${diff.altas.length} y se actualizarán ${diff.cambios.length}.`;

        if (diff.ausentes.length) {
            const texto =
                `Hay ${diff.ausentes.length} registro(s) guardados que no están en la lista. ` +
                `Si continúa quedarán activos con su tarifa anterior. ` +
                `Si los retira, se marcarán como inactivos.`;

            const retirar = await confirmAction({
                title: "Registros que no están en la lista",
                text: resumen + " " + texto + "¿Desea retirarlos?",
                confirmText: "Sí, retirarlos",
                cancelText: "No, dejarlos activos",
                icon: "warning",
            });

            payload.retirar_faltantes = retirar;
        } else if (!(await confirmAction({
            title: "Confirmar cambios",
            text: resumen + "¿Desea aplicarlos?",
            confirmText: "Sí, guardar",
            cancelText: "Cancelar",
            icon: "question",
        }))) {
            return;
        }

        await axios.put(url, payload);

        toastMessage({
            heading: "Ok",
            text: "Médicos-producto guardados correctamente",
            icon: "success",
        });

        const res = await axios.get(`/api/contratos/${contratoSeleccionado.value.id}`);
        contratoSeleccionado.value = res.data.data;
        detallesLocales.value = mapearMedicosDetalle(res.data.data.medicos || []);
        await listar();
    } catch (_error) {
        const msg =
            _error.response?.data?.message ||
            Object.values(_error.response?.data?.errors || {})
                .flat()
                .join(", ");
        toastMessage({ heading: "Error", text: msg || "Error de validación", icon: "error" });
    } finally {
        loadingGuardarDetalle.value = false;
    }
}
```

- [ ] **Step 5: Verificar que `confirmAction` acepta esas opciones**

Run: `Select-String -Path resources/js/helpers/alerts.js -Pattern "export function confirmAction" -Context 0,20`
Expected: el parámetro acepta `title`, `text`, `icon`, `confirmText` y `cancelText`. Si el helper no acepta `confirmText`/`cancelText`, quitarlos del objeto y dejar el texto en `text`.

- [ ] **Step 6: Lint y build**

Run: `npx eslint resources/js/components/modulos/contratos/contratoComponent.vue`
Expected: `0 errors`. Si aparecen errores de formato, agregar `--fix`.

Run: `npm run build`
Expected: `✓ built in ...`

- [ ] **Step 7: Pedir autorización y commitear**

```bash
git add resources/js/components/modulos/contratos/contratoComponent.vue
git commit -m "feat(contratos): confirma el diff antes de guardar y agrega fecha de ingreso"
```

---

### Task 8: Frontend — panel de cobertura

**Files:**
- Modify: `resources/js/components/modulos/contratos/contratoComponent.vue`

**Interfaces:**
- Consumes: `GET /api/contratos/{id}/cobertura` de la Task 5.
- Produces: `const cobertura = ref(null)` y `async function cargarCobertura(contratoId)`.

- [ ] **Step 1: Agregar el estado**

Junto a `contratoSeleccionado`, agregar:

```javascript
const cobertura = ref(null);
const loadingCobertura = ref(false);
```

- [ ] **Step 2: Agregar la función que la carga**

Después de `mapearMedicosDetalle`:

```javascript
/**
 * Consulta qué productos del catálogo exigibles no tienen tarifa en el contrato.
 *
 * @param {number} contratoId - Identificador del contrato abierto en el detalle.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-09-30
 */
async function cargarCobertura(contratoId) {
    loadingCobertura.value = true;
    try {
        const res = await axios.get(`/api/contratos/${contratoId}/cobertura`);
        cobertura.value = res.data.data;
    } catch (_error) {
        cobertura.value = null;
    } finally {
        loadingCobertura.value = false;
    }
}
```

- [ ] **Step 3: Llamarla al abrir el detalle**

En `abrirDetalle(contrato)`, después de asignar `contratoSeleccionado.value`, agregar `cargarCobertura(contrato.id);`. En `cerrarDetalle()`, agregar `cobertura.value = null;`.

- [ ] **Step 4: Agregar el panel al template**

En el sidebar de detalle, antes de la tabla de médicos-producto, agregar:

```html
<div class="card mb-3" v-if="cobertura">
    <div class="card-body py-2">
        <h6 class="card-subtitle mb-2">
            Cobertura de productos
            <span
                class="badge"
                :class="cobertura.faltantes.length ? 'badge-warning' : 'badge-success'"
            >
                {{ cobertura.tarifados }} / {{ cobertura.total }}
            </span>
        </h6>
        <p v-if="!cobertura.faltantes.length" class="text-success mb-0">
            Todos los productos que requieren lectura tienen tarifa.
        </p>
        <div v-else style="max-height: 160px; overflow-y: auto">
            <p class="text-warning mb-1">
                Estos productos se pueden leer sin generar pago:
            </p>
            <div v-for="p in cobertura.faltantes" :key="p.id" class="small text-muted">
                <strong>{{ p.cod_cups }}</strong> - {{ p.nom_produc }}
            </div>
        </div>
    </div>
</div>
```

- [ ] **Step 5: Lint y build**

Run: `npx eslint resources/js/components/modulos/contratos/contratoComponent.vue --fix` y luego `npm run build`
Expected: 0 errores y build correcto.

- [ ] **Step 6: Pedir autorización y commitear**

```bash
git add resources/js/components/modulos/contratos/contratoComponent.vue
git commit -m "feat(contratos): panel de cobertura de productos sin tarifa"
```

---

### Task 9: Columna Fecha Ingreso en la importación por Excel

**Files:**
- Modify: `app/Exports/ContratoDetallePlantillaExport.php`
- Modify: `app/Imports/ContratoMedicoImport.php`

**Interfaces:**
- Consumes: columna `fecha_ingreso` de `contratos_medicos`.
- Produces: plantilla con columna `Fecha Ingreso`; el import la lee si viene y la persiste.

- [ ] **Step 1: Agregar la columna a la plantilla**

En `app/Exports/ContratoDetallePlantillaExport.php`, agregar la columna `Fecha Ingreso` al final de las headings y el valor correspondiente en la fila. Leer el archivo completo primero y seguir el mismo formato de las columnas existentes (`Valor Consulta`, `Valor Urgencias`).

- [ ] **Step 2: Leerla en el import**

En `app/Imports/ContratoMedicoImport.php`, leer la celda `Fecha Ingreso` con el mismo criterio que las otras columnas, tratar el valor vacío como `null` y guardarlo en `fecha_ingreso`. Si el import usa `headingRow() = 3` y lee por índice, la nueva columna es la sexta (índice 5).

- [ ] **Step 3: Validar contra la BD**

Crear `C:\Users\Jhon\AppData\Local\Temp\opencode\verify_import_fecha.php` que:
1. Genere la plantilla con `ContratoDetallePlantillaExport` sobre un contrato temporal y confirme que el encabezado `Fecha Ingreso` existe.
2. Importe un archivo con la columna vacía y confirme que `fecha_ingreso` queda en `null`.
3. Importe un archivo con una fecha y confirme que se persiste.

Envolver en `DB::beginTransaction()` / `DB::rollBack()`.

Run: `php C:\Users\Jhon\AppData\Local\Temp\opencode\verify_import_fecha.php`
Expected: las tres verificaciones en `OK`.

- [ ] **Step 4: Verificar estilo y commitear**

Run: `vendor/bin/pint --test app/Exports/ContratoDetallePlantillaExport.php app/Imports/ContratoMedicoImport.php`
Expected: `{"tool":"pint","result":"passed"}`

Pedir autorización y luego:

```bash
git add app/Exports/ContratoDetallePlantillaExport.php app/Imports/ContratoMedicoImport.php
git commit -m "feat(contratos): columna Fecha Ingreso en la plantilla y la importacion"
```

---

### Task 10: Documentación y verificación final

**Files:**
- Modify: `docs/CONTROLLERS.md`

**Interfaces:**
- Consumes: todo lo implementado.
- Produces: índice actualizado con los métodos nuevos.

- [ ] **Step 1: Documentar los métodos nuevos**

En `docs/CONTROLLERS.md`, agregar en la sección de Contratación:

```markdown
### `ContratoController.php`

Gestiona los contratos por sede: vigencia, productos tarifados y detalle médico-producto.

-   **`cobertura`**: Informa qué productos del catálogo exigibles no tienen tarifa en el contrato.
-   **`previewMedicos`**: Calcula el diff del detalle de médicos sin aplicarlo.
-   **`syncMedicos`**: Aplica el diff del detalle; nunca borra registros existentes.
```

- [ ] **Step 2: Ejecutar toda la verificación**

```bash
vendor/bin/pint --test app/Services/Contratacion/ContratoService.php app/Http/Controllers/Contratacion/ContratoController.php app/Http/Requests/Contratacion/ContratoRequest.php app/Exports/ContratoDetallePlantillaExport.php app/Imports/ContratoMedicoImport.php
npx eslint resources/js/components/modulos/contratos/contratoComponent.vue
npm run build
php artisan route:list --path=api/contratos
```

Expected: pint passed, eslint 0 errors, build correcto, y las 13 rutas listadas.

- [ ] **Step 3: Prueba manual con el usuario**

Pedirle al usuario que:
1. Cree un contrato de la sede actual con fechas de 2021 y lo guarde (antes daba 500).
2. Intente crear otro con fechas que se crucen → debe recibir "La sede ya tiene un contrato vigente en ese rango de fechas".
3. Cargue productos por Excel y médicos por Excel.
4. Modifique una tarifa desde el detalle → debe ver el resumen de cambios antes de confirmar.
5. Verifique que el panel de cobertura muestra los productos sin tarifa.

- [ ] **Step 4: Commitear la documentación**

```bash
git add docs/CONTROLLERS.md
git commit -m "docs(contratos): documenta los endpoints de cobertura y preview"
```

---

## Self-Review

**1. Cobertura del spec:**

| Sección del spec | Tarea |
|---|---|
| Bug `exists:sedes` | Task 2 |
| 3 migraciones | Task 1 |
| No solapamiento (regla 1) | Task 3 |
| `fecha_ingreso`/`fecha_retiro` (regla 3) | Task 1, Task 7, Task 9 |
| Índice único (regla 4) | Task 1 |
| Cobertura de productos (regla 6) | Task 5, Task 8 |
| Diff no destructivo | Task 4 |
| `preview()` | Task 4, Task 6 |
| `throw` en vez de `return` | Task 4 (el `return` desaparece: los errores se detectan antes de la transacción) |
| Bitácora vía `activity_log` | Task 4 (verificación en el paso 10 del script) |
| Permisos por acción | Task 6 |
| Fix `medicoOptions` | Task 7 |
| Columna Fecha Ingreso en Excel | Task 9 |
| Fuera de alcance documentado | Sección "Fuera de alcance" del spec; no hay tareas |

Sin huecos.

**2. Placeholders:** ninguno. Cada paso tiene el código o el comando exacto.

**3. Consistencia de tipos:** `calcularDiffMedicos` devuelve las claves `altas`, `cambios`, `retiros`, `ausentes`, `errores`; el frontend de la Task 7 lee exactamente `diff.errores`, `diff.altas`, `diff.cambios` y `diff.ausentes`; `previewMedicos` y `syncMedicos` reciben `$validated['medicos']`; `fecha_ingreso` es `DATE NULL` en la migración, `nullable|date` en la validación, `null` en el payload del frontend y `|| ""` al mapear para el input `type="date"`.

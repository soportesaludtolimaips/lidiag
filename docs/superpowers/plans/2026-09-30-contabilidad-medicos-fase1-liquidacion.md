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

### Task 1: `LiquidacionService::calcular()` — el corazón

**Files:**
- Create: `app/Services/Liquidacion/LiquidacionService.php`

**Interfaces:**
- Consumes: `Estudio`, `EstudioProducto`, `Contrato`, `ContratoProducto`, `ContratoMedico`, `ConfigPrioridad`, `BaseService::success()/error()`.
- Produces: `public function calcular(int $sedeId, string $fechaInicio, string $fechaFin): array` — devuelve `['lineas' => [], 'exclusiones' => [], 'totales' => []]`.
  - `lineas`: cada una `['medico_id','medico_name','producto_id','cod_cups','nom_produc','clase' ('consulta'|'urgencias'),'valor','fecha_lectura','contrato_id']`.
  - `exclusiones`: cada una `['categoria','conteo','detalle' => [ids de estudios_productos]]`. Categorías: `sin_asignado`, `sin_contrato`, `producto_sin_tarifa`, `medico_fuera_detalle`, `anulada`.
  - `totales`: `['por_medico' => [['medico_id','medico_name','total_consulta','total_urgencias','total']], 'gran_total' => int]`.

- [ ] **Step 1: Crear el servicio con `calcular()`**

```php
<?php

namespace App\Services\Liquidacion;

use App\Models\Configuracion\ConfigPrioridad;
use App\Models\Contratacion\Contrato;
use App\Models\Contratacion\ContratoMedico;
use App\Models\Contratacion\ContratoProducto;
use App\Models\Estudio\EstudioProducto;
use App\Services\BaseService;
use Illuminate\Support\Facades\DB;

/**
 * Calcula la liquidación de pagos a médicos por sede y rango de fechas.
 *
 * Une cada lectura con el contrato vigente de su sede en la fecha de lectura
 * y valora con la tarifa del detalle. Lo que no liquida cae en exclusiones
 * visibles con conteo, nunca en silencio.
 *
 * Flujo del proceso:
 * 1. Carga las lecturas del periodo (con fecha de lectura, de la sede).
 * 2. Precarga contratos de la sede que tocan el rango y el detalle activo.
 * 3. Clasifica cada lectura: línea liquidable o categoría de exclusión.
 * 4. Agrega totales por médico y gran total.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-09-30
 */
class LiquidacionService extends BaseService
{
    /**
     * Calcula líneas, exclusiones y totales de un periodo.
     *
     * Flujo del proceso:
     * 1. Trae estudios_productos con fechor_lectura en el rango y su estudio.
     * 2. Resuelve prioridades de urgencias (nom_priori = 'Alta').
     * 3. Por cada lectura: valida asignado, activo, contrato, producto y médico.
     * 4. Valora con valor_urgencias (Alta) o valor_consulta (resto).
     * 5. Agrega por médico.
     *
     * @param  int     $sedeId       Sede a liquidar.
     * @param  string  $fechaInicio  Inicio del periodo en Y-m-d.
     * @param  string  $fechaFin     Fin del periodo en Y-m-d.
     *
     * @returns array Líneas, exclusiones con conteo y totales por médico.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-09-30
     */
    public function calcular(int $sedeId, string $fechaInicio, string $fechaFin): array
    {
        $urgentes = ConfigPrioridad::where('nom_priori', 'Alta')->pluck('id')->toArray();

        $lecturas = EstudioProducto::query()
            ->join('estudios as e', 'e.id', '=', 'estudios_productos.estudio_id')
            ->where('e.sede_id', $sedeId)
            ->whereDate('estudios_productos.fechor_lectura', '>=', $fechaInicio)
            ->whereDate('estudios_productos.fechor_lectura', '<=', $fechaFin)
            ->select('estudios_productos.*', 'e.medico_id as lector_id', 'e.prioridad_id')
            ->with(['medico:id,name', 'producto:id,cod_cups,nom_produc'])
            ->get();

        $contratos = Contrato::where('sede_id', $sedeId)
            ->where('fecha_inicio', '<=', $fechaFin)
            ->where('fecha_fin', '>=', $fechaInicio)
            ->with(['productos:id,contrato_id,producto_id', 'medicos' => fn ($q) => $q->where('contratos_medicos.estado', 1)])
            ->get();

        $lineas = [];
        $excluidos = ['sin_asignado' => [], 'sin_contrato' => [], 'producto_sin_tarifa' => [], 'medico_fuera_detalle' => [], 'anulada' => []];

        foreach ($lecturas as $l) {
            $fecha = substr((string) $l->fechor_lectura, 0, 10);

            if (! $l->activo) {
                $excluidos['anulada'][] = $l->id;

                continue;
            }

            if ($l->lector_id === null) {
                $excluidos['sin_asignado'][] = $l->id;

                continue;
            }

            $contrato = $contratos->first(fn ($c) => $c->fecha_inicio <= $fecha && $fecha <= $c->fecha_fin);

            if (! $contrato) {
                $excluidos['sin_contrato'][] = $l->id;

                continue;
            }

            $cp = $contrato->productos->firstWhere('producto_id', $l->producto_id);

            if (! $cp) {
                $excluidos['producto_sin_tarifa'][] = $l->id;

                continue;
            }

            $cm = $contrato->medicos->first(fn ($m) => $m->medico_id == $l->lector_id && $m->contratos_productos_id == $cp->id
                && ($m->fecha_ingreso === null || substr((string) $m->fecha_ingreso, 0, 10) <= $fecha));

            if (! $cm) {
                $excluidos['medico_fuera_detalle'][] = $l->id;

                continue;
            }

            $esUrgencia = in_array((int) $l->prioridad_id, $urgentes, true);
            $valor = $esUrgencia ? (float) $cm->valor_urgencias : (float) $cm->valor_consulta;

            $lineas[] = [
                'medico_id' => (int) $l->lector_id,
                'medico_name' => $cm->medico->name ?? '',
                'producto_id' => (int) $l->producto_id,
                'cod_cups' => $l->producto->cod_cups ?? '',
                'nom_produc' => $l->producto->nom_produc ?? '',
                'clase' => $esUrgencia ? 'urgencias' : 'consulta',
                'valor' => $valor,
                'fecha_lectura' => $fecha,
                'contrato_id' => $contrato->id,
            ];
        }

        $porMedico = [];

        foreach ($lineas as $linea) {
            $id = $linea['medico_id'];

            if (! isset($porMedico[$id])) {
                $porMedico[$id] = ['medico_id' => $id, 'medico_name' => $linea['medico_name'], 'total_consulta' => 0, 'total_urgencias' => 0, 'total' => 0];
            }

            if ($linea['clase'] === 'urgencias') {
                $porMedico[$id]['total_urgencias'] += $linea['valor'];
            } else {
                $porMedico[$id]['total_consulta'] += $linea['valor'];
            }

            $porMedico[$id]['total'] += $linea['valor'];
        }

        $exclusiones = [];

        foreach ($excluidos as $categoria => $ids) {
            $exclusiones[] = ['categoria' => $categoria, 'conteo' => count($ids), 'detalle' => array_values($ids)];
        }

        return $this->success([
            'lineas' => $lineas,
            'exclusiones' => $exclusiones,
            'totales' => ['por_medico' => array_values($porMedico), 'gran_total' => array_sum(array_column($porMedico, 'total'))],
        ]);
    }
}
```

Notas vinculantes (no son opcionales):
- `EstudioProducto::estudio()`, `Contrato::productos/medicos`, `ContratoMedico::medico/contratoProducto` existen (verificado). `EstudioProducto::medico` y `EstudioProducto::producto` NO están verificados: el plan usa `medico:id,name` y `producto:id,cod_cups,nom_produc` en el `with()`. Si alguna relación no existe con ese nombre, usar la real y reportarlo (p. ej. vía `contratoProducto->producto` ya verificado en Task 4).
- `$contrato->fecha_inicio` es Carbon por cast (Task 1): comparar con `<=` contra string Y-m-d funciona por `__toString`, pero si falla, normalizar con `->format('Y-m-d')` como en Task 3.
- `fecha_ingreso` del detalle también es Carbon o null: el `substr((string) ...)` lo tolera; si es null se trata como "desde el inicio" (Task 4).
- `whereDate` sobre `fechor_lectura` (datetime) filtra por día, correcto para el rango.

- [ ] **Step 2: Verificar el cálculo contra la BD real**

Crear `C:\Users\Jhon\AppData\Local\Temp\opencode\verify_liquidacion.php` (todo en `DB::beginTransaction()` / `DB::rollBack()`):
1. Tomar sede, 1 producto con `requi_lectura=1`, 2 médicos (users) y 1 paciente existentes.
2. Crear contrato 2021 de la sede + `ContratoProducto` + 2 `ContratoMedico` (médico A: consulta 50000 / urgencias 80000; médico B: consulta 30000 / urgencias 0), ambos `estado=1`, `fecha_ingreso` null.
3. Crear 2 estudios de la sede: uno con `prioridad_id=1` (Alta) asignado a A, otro con `prioridad_id=2` asignado a B; cada uno con 1 `estudios_productos` (`producto_id` el configurado, `fechor_lectura='2021-06-01 10:00:00'`, `activo=1`). Más 1 lectura anulada (`activo=0`) y 1 lectura de producto NO configurado en el contrato.
4. `calcular(sede, '2021-01-01', '2021-12-31')` y confirmar: 2 líneas (A urgencias 80000, B consulta 30000), gran_total 110000, exclusiones `anulada=1` y `producto_sin_tarifa=1`, resto 0.
5. Confirmar conteos originales tras rollback (`contratos`, `estudios`, `estudios_productos`).

Run: `php C:\Users\Jhon\AppData\Local\Temp\opencode\verify_liquidacion.php`
Expected: `lineas=2 (80000 urgencias + 30000 consulta), gran_total=110000, anulada=1, producto_sin_tarifa=1, rollback limpio`.

- [ ] **Step 3: Verificar estilo**

Run: `vendor\bin\pint.bat --test app/Services/Liquidacion/LiquidacionService.php`
Expected: `{"tool":"pint","result":"passed"}`

- [ ] **Step 4: Pedir autorización y commitear**

```bash
git add app/Services/Liquidacion/LiquidacionService.php
git commit -m "feat(liquidacion): calcula lineas, exclusiones y totales por periodo"
```

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
- Produces: `GET /api/liquidacion?se
...[truncated 9118 chars]
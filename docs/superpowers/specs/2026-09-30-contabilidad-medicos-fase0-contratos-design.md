# Contabilidad de pago a médicos — Fase 0: módulo de contratos

- **Fecha:** 2026-09-30
- **Estado:** aprobado en diseño
- **Alcance:** sub-proyecto 1 de 4 (ver *Fuera de alcance*)

---

## 1. Problema

No existe forma de saber cuánto se le debe pagar a un médico por su trabajo. El proyecto ya tiene el modelo de datos para calcularlo (`contratos` → `contratos_productos` → `contratos_medicos`, con `valor_consulta` y `valor_urgencias` por médico y producto) y 254.362 lecturas históricas, pero **el módulo de contratos está inoperante**: las tres tablas tienen cero registros y hay un bug que impide crear el primer contrato.

Sin tarifas cargadas no hay nada que liquidar. Por eso la Fase 0 es arreglar contratos, antes de tocar la contabilidad.

---

## 2. Hallazgos de la exploración

Verificados por lectura de código y consultas de solo lectura a la base de datos.

### 2.1 Bloqueante

`ContratoRequest.php:66` valida `registro_decoded.sede_id` con `exists:sedes,id`, pero la tabla real es `config_sedes` y **`sedes` no existe**. Ejecutando el validador se obtiene `QueryException: Table 'lidiag.sedes' doesn't exist`, por lo que `POST /api/contratos` y `PUT /api/contratos/{id}` devuelven 500 de forma consistente. Explica que `contratos` tenga 0 filas.

### 2.2 Integridad

| # | Hallazgo | Ubicación |
|---|---|---|
| 1 | `syncMedicos()` hace `DELETE` de todos los médicos-producto y reinserta: una recarga de Excel **destruye las tarifas anteriores** | `ContratoService.php:244-283` |
| 2 | Si un `producto_id` no está en el mapa, hace `return` **dentro** del closure transaccional → **confirma la transacción con el `DELETE` ejecutado** y devuelve 422: se pierden los médicos anteriores y aun así se reporta error | `ContratoService.php` (dentro de la closure) |
| 3 | `contratos_medicos.valor_contrato` se fuerza a `0` en todos los caminos de escritura: campo muerto | `ContratoService.php:279` y ambos imports |
| 4 | Sin índices únicos: nada impide tarifas duplicadas por SQL directo | pivotes |
| 5 | `medicoOptions` mapea `m.reg_med` pero `UserService::listByRoleAndModule` solo selecciona `users.id` y `users.name` → el selector siempre muestra vacío | `contratoComponent.vue:706` vs `UserService.php:32` |
| 6 | Middleware muerto en rutas: `permission:contratos.index\|contratos.create\|...` en una sola cadena significa "tiene cualquiera" y se aplica al `Route::resource` completo. **No es una vulnerabilidad**: `ContratoController::__construct` ya aplica `permission:...->only([...])` por acción, y ambos middleware se ejecutan en AND, así que el del constructor manda. Queda como deuda de claridad, y el mismo patrón se repite en `web.php`, `api_estudios.php`, `api_ecografias.php` y `api_interfaces.php` | `routes/api_contratacion.php:13-16` |

### 2.3 El modelo de datos es correcto y se conserva

La unidad es **Sede → 1 contrato vigente (con fechas) → N médicos, cada uno con su propio detalle (producto + valor consulta + valor urgencias)**. El grano del pivote `contratos_medicos` es `(contrato_id, medico_id, contratos_productos_id)`: médico-producto, no médico-sede. Las tarifas dependen de los tres ejes.

### 2.4 Auditoría

`app/Helpers/AuditLogger.php` está deprecado. El sistema canónico es `AppServiceProvider::registerGlobalActivityLog()`, que registra `created`/`updated`/`deleted` de cualquier modelo en `activity_log`, con columnas `old_values` y `new_values`, `user_id`, `ip_address` y `user_agent`. Solo escribe si `Auth::check()`.

**Consecuencia de diseño:** al pasar el `sync` de destructivo a diff, cada alta, cambio y retiro queda registrado automáticamente. **No se crea tabla de bitácora nueva.**

---

## 3. Decisiones tomadas

| # | Decisión | Alternativa descartada |
|---|---|---|
| 1 | Un solo contrato vigente por sede (sin solapamiento de fechas) | Varios simultáneos con desempate |
| 2 | Congelar la tarifa al liquidar + bitácora de cambios | Versionado SCD de tarifas; o solo hacia adelante |
| 3 | Cada médico tiene su propia fecha de ingreso dentro del contrato | Heredar la vigencia del contrato |
| 4 | Permite cargar contratos con fechas ya vencidas (histórico hacia atrás, secuenciales) | Solo contratos vigentes desde hoy |
| 5 | Enfoque A: arreglo con integridad, conservando el flujo de Excel | B: reescritura del módulo; C: solo el bug bloqueante |

**Buenas prácticas que condicionan el diseño:**

1. Congelar la tarifa al cerrar el período: la liquidación pasada nunca se recalcula.
2. Libro mayor append-only: las correcciones son notas de reversión, no `UPDATE` de lo ya liquidado.
3. Idempotencia: índice único por lectura en el detalle de liquidación.
4. Anular no es borrar: registrar actor, motivo y fecha.
5. `decimal(12,2)` en todos los valores, nunca `float`.
6. Períodos cerrados e inmutables; las correcciones van a un período de ajuste.

Las prácticas 1 a 6 condicionan la **Fase 1 (liquidación)**, no este sub-proyecto. Lo único que este sub-proyecto pone de su lado es la **bitácora**: al sustituir el `sync` destructivo por un diff, cada cambio queda registrado en `activity_log` con valores antes/después, lo que da la trazabilidad que la práctica 2 necesita para poder corregir sin reescribir.

---

## 4. Modelo de datos

Las tres tablas están vacías, por lo que las migraciones se aplican sin colisiones.

### Migración 1 — vigencia por médico

`contratos_medicos`:
- `fecha_ingreso` DATE NULL — nula significa "desde el inicio del contrato"
- `fecha_retiro` DATE NULL — con valor, el médico deja de aplicar después de esa fecha
- índice `(contrato_id, estado)`

### Migración 2 — integridad de los pivotes

- `UNIQUE (contrato_id, producto_id)` en `contratos_productos`
- `UNIQUE (contrato_id, medico_id, contratos_productos_id)` en `contratos_medicos`

### Migración 3 — resolución del contrato vigente

- `INDEX (sede_id, fecha_inicio, fecha_fin)` en `contratos`

Ninguna migración añade columnas monetarias: las tarifas ya están y no cambian de ubicación.

---

## 5. Reglas de negocio

1. **No solapamiento.** Al crear o editar un contrato, ningún otro de la misma sede puede tener un rango de fechas que se cruce. La regla es solo por fechas: un contrato cerrado (`estado = 0`) también ocupa su rango. Se valida dentro de la transacción con `lockForUpdate` sobre la sede, para que dos cargas simultáneas no incumplan la regla. El mensaje de error cita el contrato en conflicto con su vigencia (`La sede ya tiene el contrato #X (vigencia Y a Z)...`) y no aconseja cerrarlo.
2. `fecha_fin >= fecha_inicio` (ya existe en validación).
3. `fecha_retiro >= fecha_ingreso` y ambas fechas dentro del rango del contrato.
4. Un médico no puede aparecer dos veces con el mismo producto en un contrato (lo garantiza el índice único).
5. **Cerrar un contrato con lecturas sin liquidar se permite, pero avisa cuántas hay.** No se bloquea: puede ser un cierre administrativo legítimo.
6. **Cobertura de productos.** El detalle del contrato muestra qué productos del catálogo (`requi_lectura = 1` y `estado = 1`) no tienen tarifa. Hoy eso no es visible y es la vía más común para tener lecturas sin pagar.

---

## 6. Servicios

`App\Services\Contratacion\ContratoService`:

1. **`syncMedicos()` deja de ser destructivo.** Calcula un diff contra el estado actual y aplica solo lo que cambió:
   - Ítem nuevo → crea con `estado = 1` y `fecha_ingreso` en `null`, que significa "aplica desde el inicio del contrato". Si el payload trae `fecha_ingreso`, se respeta y se valida contra el rango del contrato (regla 3)
   - Ítem existente con valores distintos → actualiza
   - Ítem existente marcado como retirado → `estado = 0` y `fecha_retiro`
   - Ítem existente **ausente del archivo** → no se toca. Solo se retira si la petición incluye `retirar_faltantes: true`
2. **`preview()`** devuelve el diff sin aplicar nada: altas, cambios, bajas y ausentes.
3. **Corrección de transacción:** el caso de `producto_id` inexistente pasa de `return` a `throw`, para que la transacción se revierta.
4. **Anti-solapamiento** en `create()` y `update()`.
5. **`cobertura($contratoId)`** devuelve los productos del catálogo marcados como tarifados o faltantes.

---

## 7. API

| Método | Ruta | Permiso | Qué hace |
|---|---|---|---|
| `GET` | `/api/contratos/{id}/cobertura` | `contratos.show` | Productos del catálogo sin tarifa en el contrato |
| `POST` | `/api/contratos/{id}/medicos/preview` | `contratos.detalle.edit` | Diff del detalle de médicos, sin aplicar |
| `PUT` | `/api/contratos/{id}/medicos` | `contratos.detalle.edit` | Aplica el diff; acepta `retirar_faltantes` (bool) |
| `POST` | `/api/contratos/{id}/importar-medicos` | `contratos.detalle.create` |Igual que hoy, con columna opcional `Fecha Ingreso` |

Los permisos de las rutas de contratos **no se tocan**: el controller ya los aplica por acción en su constructor, y el patrón `a|b|c` de las rutas es convención del proyecto, no un defecto de este módulo.

---

## 8. Frontend

`resources/js/components/modulos/contratos/contratoComponent.vue`:

1. Panel **"Cobertura de productos"** en el detalle del contrato: cuántos productos están tarifados y cuáles faltan, con su código CUPS.
2. **Confirmación del diff** antes de guardar el detalle de médicos, reutilizando `confirmAction` de SweetAlert2. Si hay filas ausentes, el texto avisa explícitamente de que quedarán sin aplicar.
3. Columna **Fecha de ingreso** por médico, editable y opcional.
4. Corrección de `medicoOptions`: alinear el mapeo con los campos que realmente devuelve `UserService::listByRoleAndModule`.
5. Plantilla Excel de detalle con la columna `Fecha Ingreso`.

---

## 9. Verificación

| Capa | Método |
|---|---|
| Estilo y sintaxis | `vendor/bin/pint --test` y `npx eslint` |
| Build | `npm run build` |
| Lógica de negocio | Script PHP contra la BD real, con `DB::beginTransaction` / `rollBack`: contrato solapado rechazado; diff que no destruye; `retirar_faltantes` explícito; cobertura de productos; fee de validación `config_sedes` |
| Manual | Crear un contrato con fechas 2021, cargarlo por Excel, editar tarifas y ver el diff, verificar que en otra sede sigue habiendo un solo vigente |

---

## 10. Orden de ejecución

1. Las tres migraciones
2. Backend: bug de `config_sedes`, sync con diff, `throw` en vez de `return`, anti-solapamiento, `cobertura`, `preview`, middlewares de rutas
3. Frontend: panel de cobertura, confirmación del diff, columna Fecha de ingreso, fix de `medicoOptions`
4. Plantilla Excel
5. Verificación
6. **Carga real de contratos** (tarea del usuario con sus Exceles)

---

## 11. Fuera de alcance

Este sub-proyecto **no** incluye:

- **Motor de liquidación y tablas de pagos.** Es la Fase 1.
- **Modalidad consulta vs urgencias.** `valor_urgencias` no es cobrable: nada en el sistema clasifica una lectura. Hay que decidir si se agrega el campo, de dónde sale el dato (Interface/SAHI) o si se cobra siempre como consulta. **Bloqueante para la Fase 1.**
- **Atribución del médico lector.** Hoy el único campo es `estudios_productos.transcriptor_id`, que se llena en `guardarTranscripcion()` (registra al que transcribe, no al que lee) y está ausente en 10.281 lecturas. `leerEstudio()` no lo guarda. **Bloqueante para la Fase 1:** no se puede liquidar con fiabilidad hasta definir el hecho generador.
- Retenciones, IVA y anticipos: no existe dónde parameterizarlos.
- Liquidación del histórico de 254.362 lecturas, que requerirá cargar contratos de años anteriores con sus tarifas reales.

---

## 12. Riesgos

| Riesgo | Mitigación |
|---|---|
| El diff se implementa mal y aun así destruye tarifas | Nunca se borra: solo se actualiza o se marca retirado, y siempre con `preview` previo |
| Un Excel con menos filas deja tarifas vieja aplicables | No se retiran sin `retirar_faltantes: true`; la UI lo dice en el diff |
| El cálculo de solapamiento no es atómico bajo concurrencia | `lockForUpdate` sobre la sede dentro de la misma transacción que inserta |
| El middleware con `|` se "arregla" y rompe accesos existentes | Se separa por acción y se prueba cada una con un usuario real |
| El histórico queda con lecturas que no se pueden atribuir | Fuera de alcance y documentado en la sección 11; se decide en la Fase 1 |

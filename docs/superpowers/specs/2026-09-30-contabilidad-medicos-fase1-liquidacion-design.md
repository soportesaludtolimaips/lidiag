# Contabilidad de pagos a médicos — Fase 1: liquidación (design)

**Fecha:** 2026-09-30
**Base:** Fase 0 completa (contratos como fuente de tarifas, 11 commits en `main`).
**Decisiones del dueño ya tomadas:** D1 (prioridad Alta = urgencias), D2 (lector = médico asignado, vale con fecha de lectura).

---

## 1. Contexto

Fase 0 dejó los contratos operativos: vigencia sin solapes, detalle por diff, cobertura
de productos, preview y Fecha Ingreso en Excel. Fase 1 calcula **cuánto se le debe a cada
médico en un periodo**: por cada lectura liquidable, determina lector, clase de tarifa,
contrato aplicable y valor.

## 2. Hecho liquidable

Una fila de `estudios_productos` es liquidable si cumple todo:

1. `fechor_lectura IS NOT NULL` (la lectura ocurrió; `leerEstudio` la fija con guard 409:
   el primer lector gana, sin doble pago por relectura).
2. `activo = 1` (anulados con `anularAsignacion` no se pagan).
3. El estudio padre tiene `medico_id NOT NULL`. **Invariante del negocio (D3): no puede
   existir una lectura sin médico asignado.** Q2 lo garantiza hacia adelante; los casos
   viejos que aparezcan son anomalía de datos (ver §6).
4. Existe contrato vigente de la sede en la fecha de lectura con ese médico y ese producto
   tarifados (si no, cae en las categorías de exclusión de §6, no en silencio).

## 3. Lector (D2)

**Lector = `estudios.medico_id` cuando `fechor_lectura` existe.** Verificado en código:

- La asignación se fija al admisionar (`EstudioService::create`, mensaje "La asignación de
  la lectura se realizó correctamente").
- No hay reasignación: los únicos writes a `estudios.medico_id` son admisión, import DICOM
  y transcripción TeleRadiología (§7, caveat). Sin historial que mantener: el valor actual
  ES el asignado al leer (salvo Tele, ver §7).
- `leerEstudio()` (`EstudioService.php:224-245`) no registra quién lee; con D2 no necesita
  cambio para liquidar: el lector se deriva, no se almacena.
- NO reusar `transcriptor_id`: se llena al transcribir (`guardarTranscripcion` con
  `Auth::id()`), rol distinto al lector. El esquema de adendums ya separa `medico_id` de
  `transcriptor_id`; D2 sigue ese mismo criterio sin nueva columna.

## 4. Clase de tarifa (D1)

`estudios.prioridad_id` → `config_prioridades` (medido 2026-09-30, total = 206.962 = 100%
de estudios, sin nulos):

| Prioridad | Estudios | Tarifa |
|---|---|---|
| 1 Alta | 41.818 | `valor_urgencias` |
| 2 Media | 161.869 | `valor_consulta` |
| 3 Baja | 3.199 | `valor_consulta` |
| 4 Sin Prioridad | 76 | `valor_consulta` |

## 5. Contrato aplicable y valor

Por cada hecho liquidable, en orden:

1. Sede = `estudios.sede_id`; fecha = `DATE(fechor_lectura)`.
2. Contrato de la sede cuyo rango cubre la fecha (regla anti-solape de Fase 0 garantiza
   unicidad; si no hay, ver §6).
3. `contratos_productos` del contrato con `producto_id` = lectura (si no hay, ver §6:
   es exactamente lo que el panel de cobertura previene).
4. `contratos_medicos` con ese médico + producto y `estado = 1`; `fecha_ingreso` null =
   aplica desde el inicio del contrato (Task 4).
5. Valor = `valor_urgencias` si D1 es Alta, si no `valor_consulta`.

## 6. Exclusiones (visibles, nunca silenciosas)

Toda lectura con fecha que no liquida cae en una categoría con conteo:

- **Sin asignado**: no debería existir (D3). Si aparece, es anomalía de datos: se excluye
  y se lista aparte para corrección, no es una vía de pago.
- **Sin contrato vigente** en sede+fecha.
- **Producto sin tarifa** en el contrato (el panel de cobertura existe para vaciar esta
  categoría antes de liquidar).
- **Médico fuera del detalle** del contrato.
- **Anulada**: `activo = 0`.

## 7. Caveats medidos (no bloquean, quedan documentados)

- **TeleRadiología**: `guardarTranscripcion` sobreescribe `estudios.medico_id` cuando el
  destino es Tele (línea 474). Si un estudio leído localmente luego se tele-transcribe,
  la atribución deriva al tele-radiólogo. Raro, pero posible.
- **Lectura sin asignación**: `leerEstudio()` no verifica que el lector sea el asignado;
  cualquiera autenticado puede fijar `fechor_lectura` y el pago iría al asignado (ver Q2).
- **Transcripción**: `transcriptor_id` + `fechor_trascrito` existen; si transcribir se paga
  aparte, es otra regla (ver Q4).
- **Histórico irrecuperable**: `activity_log` vacío (0 filas), adendums vacío, 27.376
  lecturas sin `transcriptor_id`. La liquidación histórica anterior a Fase 1 tiene el mismo
  hueco de atribución que hoy: solo vale hacia adelante.

## 8. Decisiones (dueño, 2026-09-30)

- **D1**: prioridad Alta → `valor_urgencias`; resto → `valor_consulta`.
- **D2**: lector = `estudios.medico_id` cuando `fechor_lectura` existe.
- **D3 (Q1)**: no puede existir lectura sin médico asignado (invariante; viejos = anomalía).
- **D4 (Q2)**: `leerEstudio()` rechaza a quien no sea el asignado (cambio de conducta incluido).
- **D5 (Q4)**: transcribir NO se paga; el pago es solo a los médicos lectores.
- **D6 (Q5)**: recálculo vivo; NO se congelan tarifas (cambiar una tarifa reescribe el pasado).
- **Q3 (default, pendiente de veto)**: se acepta la deriva Tele (§7).
- **Q6 (default, pendiente de veto)**: cálculo a demanda por rango, sin estado de periodo.

## 9. Alcance de Fase 1 (con D1–D6)

1. Vista de liquidación: por sede + rango de fechas → filas por (médico, producto, clase)
   con valor, más exclusiones con conteo (incluida anomalía sin-asignado).
2. Exportar la liquidación a Excel.
3. Guard en `leerEstudio()`: solo el asignado puede leer (D4; 403 en otro caso).
4. Sin congelamiento (D6) y sin pago por transcripción (D5).

## 10. Verificación propuesta

Scripts PHP con rollback contra BD real (patrón Fase 0): un periodo conocido con conteos
esperados por médico, matriz de exclusiones, y `pint`/`eslint`/`build` según archivos.
Sin tests automatizados (estándar del proyecto según el dueño).

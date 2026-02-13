# Análisis del módulo Teleradiología - Pre pruebas

## Resumen del flujo

1. **LIDiag** lista estudios desde PACS (DCM4CHEE) → usuario selecciona → envía orden a TLIMAGEN → crea estudio local.
2. **TLIMAGEN** recibe orden, radiólogo realiza lectura, TLIMAGEN envía lectura a LIDiag.
3. **LIDiag** recibe lectura (HMAC), busca estudio por `study_pk` + `study_iuid`, actualiza EstudioProducto.

---

## Rutas y middlewares

| Ruta | Método | Auth | Descripción |
|------|--------|------|-------------|
| `/api/tele-radiologia-recibir-lectura` | POST | HMAC solo | Webhook TLIMAGEN → LIDiag |
| `/api/tele-radiologia-listar-estudios` | POST | Sanctum + permiso | Lista estudios PACS |
| `/api/tele-radiologia-enviar-orden` | POST | Sanctum + permiso | Envía orden a TLIMAGEN |
| `/api/estudios` | POST | Sanctum | Crea estudio tras enviar orden |

- `recibirLectura` excluido de auth en el controlador.
- Webhook protegido únicamente por `tele.hmac`.

---

## Condiciones críticas para recibirLectura

1. **Estudio existente:** debe existir en `estudios` con mismo `study_pk` y `study_iuid` que envía TLIMAGEN.
2. **EstudioProducto:** debe existir un producto con el `cod_cups` exacto que envía TLIMAGEN.
3. **HMAC:** `X-Timestamp` y `X-Signature` correctos; secreto idéntico en ambos sistemas.
4. **Formato de fechas:** `fec_estudio` y `fechor_lectura` en formato fecha válido (ej. `Y-m-d H:i:s`).
5. **sexo:** acepta cualquier string; se normaliza a M o F (por defecto M si no es válido).

---

## Configuración producción (lidiag.com)

- **URL webhook:** `https://lidiag.com/api/tele-radiologia-recibir-lectura`
- **PATH para firma HMAC:** `/api/tele-radiologia-recibir-lectura` (con / inicial)
- **TELERADIOLOGIA_HMAC_SECRET:** configurado en `.env` de producción.

---

## Posibles causas de fallo

| Código | Causa |
|--------|-------|
| 401 | Firma HMAC inválida, headers faltantes, timestamp >5 min |
| 400 | Payload inválido (sexo, fechas, campos requeridos) |
| 404 Estudio | `study_pk` o `study_iuid` no coinciden con estudio en LIDiag |
| 404 Producto | `cod_cups` no existe en EstudioProducto de ese estudio |
| 503 | `TELERADIOLOGIA_HMAC_SECRET` vacío en `.env` |

---

## Origen de study_pk y study_iuid

- Vienen del PACS (DCM4CHEE) al listar estudios.
- El frontend los asigna a `registro` y `orden` al seleccionar un estudio.
- La orden enviada a TLIMAGEN incluye `study_pk` y `study_iuid`.
- TLIMAGEN debe devolverlos exactamente igual en la lectura.

---

## Checklist pre-pruebas

- [ ] `TELERADIOLOGIA_HMAC_SECRET` en `.env` de producción
- [ ] TLIMAGEN tiene el mismo secreto
- [ ] TLIMAGEN apunta a `https://lidiag.com/api/tele-radiologia-recibir-lectura`
- [ ] Estudio de prueba creado (orden enviada antes) con `study_pk` y `study_iuid` conocidos
- [ ] `cod_cups` de la lectura coincide con un producto del estudio
- [ ] Código actualizado en producción (sincronizado desde GitHub)

---

## Correcciones aplicadas (verificación)

| Archivo | Problema | Solución |
|---------|----------|----------|
| `teleRadiologiaEnviarEstudioComponent.vue` | `quitarProductoEstudio(index)` usaba índice de `productosInterface` en vez del elemento en `orden.producto` | Ahora recibe `cod_cups` y elimina por coincidencia en ambos arrays |
| `teleRadiologiaEnviarEstudioComponent.vue` | `quitarProductoEstudio` no actualizaba `registro.productosEstudio` | Se actualizan ambos arrays al eliminar |
| `teleRadiologiaEnviarEstudioComponent.vue` | `onProductoSelect` llamaba `guardarProductosEstudio()` sin argumentos | Se busca el producto por id y se pasan `cod_cups` y `nom_produc` |
| `TeleRadiologiaController.php` | `sexo` solo aceptaba M/F estrictos | Validación más flexible; se normaliza a M/F antes de guardar |

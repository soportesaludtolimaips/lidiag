# Flujo del Módulo Teleradiología (TLIMAGEN ↔ SaludTolima/LIDiag)

## Resumen

1. **SaludTolima (LIDiag)** envía órdenes/estudios a **TLIMAGEN**.
2. **TLIMAGEN** realiza la lectura del estudio.
3. **TLIMAGEN** envía la lectura de vuelta a **LIDiag**.
4. **LIDiag** persiste la lectura en su BD.

---

## Fase 1: Envío de Orden (LIDiag → TLIMAGEN)

### 1.1 Interfaz de usuario

- Ruta: `/tele-radiologia`
- Componente: `teleRadiologiaEnviarEstudioComponent.vue`
- Usuario: Transcriptor/Administrador con permisos `tele.ragiologia.enviar` o `estudio.asignar`

### 1.2 Búsqueda de estudios (PACS)

1. El usuario selecciona sede y filtra por documento/fechas.
2. **Frontend:** `POST /api/tele-radiologia-listar-estudios` con `busqueda` (sede_id, bus_nom_num_docu, fechas).
3. **Backend:** `TeleRadiologiaController::listarEstudios` delega en `Dcm4chee\StudyController`.
4. Se consulta la base **DCM4CHEE** de la sede (PACS) y se cruza con `estudios` de LIDiag para marcar los ya enviados.

### 1.3 Envío de orden a TLIMAGEN

1. Usuario elige un estudio de la tabla y hace clic en "Ordenar estudio".
2. Completa el formulario: paciente, prioridad, productos (cod_cups), motivo, adjuntos.
3. **Frontend:** `POST /api/tele-radiologia-enviar-orden` con `{ orden: {...} }`.

   - `orden` incluye: `study_pk`, `paciente`, `priori`, `producto` (array con cod_cups), `sede`, etc.

4. **Backend:** `TeleRadiologiaController::enviarOrdenTeleRadiologia`
   - Valida `orden`.
   - Envía POST a `TELERADIOLOGIA_ORDEN_URL` (p. ej. `https://ris.tlimagen.com/.../ordenes`).
   - Devuelve `confirmation` (ID de orden en TLIMAGEN).

### 1.4 Creación del estudio en LIDiag

1. **Frontend:** tras éxito del envío de orden:
   - Asigna `registro.destino_estudio_id = confirmation`.
   - Envía `POST /api/estudios` con `registro` + archivos.

2. **Backend:** `EstudioController::store`
   - Crea/actualiza `Paciente`.
   - Crea `Estudio` con:
     - `study_pk`, `study_iuid`, `accession_no`, `study_desc`, etc.
     - `destino_estudio = 'Tele Radiologia'`
     - `destino_estudio_id` = confirmation de TLIMAGEN
   - Crea `EstudioProducto` por cada producto seleccionado (`cod_cups`, `nom_produc`).
   - No asigna `medico_id` (se asignará cuando llegue la lectura).

---

## Fase 2: Lectura en TLIMAGEN

- TLIMAGEN recibe la orden y asigna el estudio a un radiólogo.
- El radiólogo realiza la lectura e ingresa el informe.
- TLIMAGEN genera el payload **Lectura** y lo envía a LIDiag.

---

## Fase 3: Recepción de Lectura (TLIMAGEN → LIDiag)

### 3.1 Endpoint

- **POST** `/api/tele-radiologia-recibir-lectura`
- **Autenticación:** HMAC (headers `X-Timestamp`, `X-Signature`); no usa sesión/cookies.
- **Middleware:** `tele.hmac` (`VerifyTeleRadiologiaHmac`).

### 3.2 Payload Lectura (TLIMAGEN → LIDiag)

```json
{
  "study_pk": 208331,
  "study_iuid": "1.2.392.200036...",
  "fec_estudio": "2025-10-16 21:15:49",
  "accession_no": "74281",
  "study_desc": "TC CRANEO SIMPLE/URG/WSA/6595425",
  "mods_in_study": "CT",
  "sexo": "M",
  "paciente": {
    "num_docu": "5919726",
    "nom_pacien": "RAMIREZ RODRIGUEZ SALOMON",
    "fec_naci": null
  },
  "priori": { "id": 1, "nom_priori": "Alta" },
  "cod_cups": "879111",
  "nom_produc": "TOMOGRAFIA AXIAL COMPUTADA...",
  "fechor_lectura": "2025-10-16 23:10:50",
  "lectura": "Hallazgos...",
  "medico": {
    "reg_medico": "",
    "num_docu": "93284421",
    "name": "JOSE FABIO MARIN CASTAÑO",
    "firma_medico": "base64..."
  },
  "tipo_resultado": 1
}
```

### 3.3 Procesamiento en LIDiag (`recibirLectura`)

1. **Validación** del payload (reglas de Validator).
2. **Médico:** `User::firstOrCreate` por `num_docu`:
   - Si no existe: crea con `name = 'TL ' + nombre`, email temporal, etc.
3. **Paciente:** `Paciente::firstOrCreate` por `num_docu`:
   - Si no existe: crea con `tipo_documento_id=1`, nombres, sexo, fec_naci.
4. **Estudio:** busca con `study_pk` y `study_iuid`:
   - Si no existe → `404`.
5. **Asignación de médico:** si `estudio.medico_id` es null, se asigna el médico de la lectura.
6. **EstudioProducto:** busca por `estudio_id` + `cod_cups`:
   - Si no existe → `404`.
7. **Actualización** de `EstudioProducto`:
   - `fechor_lectura`, `lectura`, `transcriptor_id` = médico.

### 3.4 Condición de coincidencia del estudio

El estudio se localiza así:

```php
Estudio::where('study_pk', $d['study_pk'])
    ->where('study_iuid', $d['study_iuid'])
    ->first();
```

Es imprescindible que el `study_iuid` que envía TLIMAGEN coincida con el guardado al crear el estudio desde `/api/estudios`.

---

## Diagrama de flujo

```
┌─────────────────┐     listarEstudios      ┌──────────────┐
│  LIDiag (SPA)   │ ◄────────────────────── │  DCM4CHEE    │
│  /tele-radiologia│                          │  (PACS sede) │
└────────┬────────┘                          └──────────────┘
         │
         │ enviarOrden + store estudio
         ▼
┌─────────────────┐     POST ordenes         ┌──────────────┐
│ LIDiag Backend  │ ──────────────────────► │   TLIMAGEN   │
│                 │                          │   (RIS)      │
│ Estudio creado  │                          │              │
│ destino_estudio │                          │  Radiólogo   │
│ _id = confirm   │                          │  realiza     │
└────────┬────────┘                          │  lectura     │
         │                                    └──────┬──────┘
         │                                           │
         │                                    POST lectura (HMAC)
         │                                           │
         ▼                                           ▼
┌─────────────────┐     recibirLectura       ┌──────────────┐
│ LIDiag Backend  │ ◄────────────────────── │   TLIMAGEN   │
│                 │   /tele-radiologia-      │   (Java)     │
│ Actualiza       │   recibir-lectura        │              │
│ EstudioProducto │                          └──────────────┘
│ (lectura,       │
│ fechor_lectura) │
└─────────────────┘
```

---

## Rutas API relacionadas

| Método | Ruta | Auth | Descripción |
|--------|------|------|-------------|
| POST | `/api/tele-radiologia-recibir-lectura` | HMAC | Recibe lectura de TLIMAGEN |
| POST | `/api/tele-radiologia-listar-estudios` | Sanctum | Lista estudios desde PACS |
| POST | `/api/tele-radiologia-enviar-orden` | Sanctum | Envía orden a TLIMAGEN |
| POST | `/api/estudios` | Sanctum | Crea estudio tras enviar orden |

---

## Posibles fallos

1. **404 Estudio no encontrado:** `study_pk` o `study_iuid` no coinciden. Hay que asegurar que el estudio se cree con los mismos valores que en PACS/TLIMAGEN.
2. **404 Producto no encontrado:** el `cod_cups` de la lectura no existe en `EstudioProducto`. Los productos enviados en la orden deben ser los mismos que TLIMAGEN reporta.
3. **401 Firma inválida:** HMAC mal calculado o `TELERADIOLOGIA_HMAC_SECRET` distinto entre LIDiag y TLIMAGEN.

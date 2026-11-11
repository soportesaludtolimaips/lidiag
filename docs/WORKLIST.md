# Lista de trabajo (Worklist) desde Lidiag

Lidiag envía cada cita de imagen al PACS de la sede para que el equipo (DX, CR,
ecógrafo...) la vea en su lista de trabajo. El tecnólogo selecciona al paciente
en el equipo y los datos llegan solos: documento, nombre, nacimiento, sexo,
estudio y número de acceso. No se digita nada.

```
Lidiag (asigna la cita) --HL7 ORM^O01 / MLLP, puerto 2575--> dcm4chee (2.x o 5.x)
Equipo (DX) --consulta DICOM MWL (C-FIND), puerto 11112--> dcm4chee
```

## Cuándo se envía

| Acción en la agenda              | Mensaje al PACS                     |
|----------------------------------|-------------------------------------|
| Asignar cita                     | `NW` (crea el ítem)                 |
| Reasignar una cita ya asignada   | `XO` (actualiza fecha/estudio)      |
| Reasignar a otra sede            | `CA` en la sede vieja + `NW` en la nueva |
| Cancelar cita asignada/confirmada | `CA` (lo retira de la lista)       |
| Marcar "no asistió"              | `CA`                                |

Solo se envían productos de imagen: la modalidad sale del prefijo CUPS
(`config/worklist.php`): 870–875 → DX; 876 → DX salvo 8768 → MG (gana el
prefijo más largo); 879 → CT; 881–882 → US; 883 → MR. Si el PACS no responde,
la cita sigue normal y queda una advertencia en `storage/logs/laravel.log`
("Worklist: ...": id de cita, código de respuesta y, si no hubo conexión,
dirección:puerto; nunca datos del paciente). Éxito = ACK `AA` o `CA`. Cada
intento espera hasta 3 s la conexión y 5 s la respuesta.

Si la cita no aplica (producto sin modalidad, sede sin "Enviar citas al PACS"
o paciente sin documento) no se envía y **no se escribe nada** en el registro.

Reintento: solo al **asignar o reprogramar** (`NW`/`XO`). Si el PACS rechaza
(`AE`/`AR`) un `NW` (el ítem ya existía) o un `XO` (no existía, p. ej. la cita
se asignó antes de activar la sede), se reintenta una vez con el otro código.
`CA` (cancelar, no asistió) no se reintenta.

Límites conocidos (no hay cola de reenvíos):
- Si un `CA` falla (PACS sin conexión), el ítem queda en la lista del equipo.
- Si se desactiva el worklist de la sede, las cancelaciones posteriores ya no
  se envían.
- Nombres con caracteres fuera de ISO-8859-1 (emojis, símbolos raros) llegan
  como `?`; los caracteres de control se eliminan.

## Configuración en Lidiag

1. Crear las columnas de la sede (solo esta migración):
   `php artisan migrate --path=database/migrations/2026_10_11_000000_add_worklist_to_config_sedes_table.php`,
   luego `npm run build`.
2. Configuración > Sedes > editar la sede > bloque **Lista de trabajo (Worklist)**:
   - **Enviar citas al PACS**: activar.
   - **Puerto HL7**: el que entregue infraestructura.
   - **AET del PACS**: el de la sede (Ibagué `STDCMIBAGUE`; demás sedes `DCM4CHEE`).
   - **AET del equipo** (opcional): solo para 5.x configurado con `ORC_18` (ver abajo).
   - La dirección del PACS para HL7 es la misma **Ip BD Dcm4chee** de la sede
     (no hay campo aparte); la dirección y el puerto los entrega infraestructura.

## Datos que viajan

| Dato del equipo (DICOM)       | Origen en Lidiag                         |
|-------------------------------|------------------------------------------|
| Patient ID                    | `pacientes.num_docu`                     |
| Patient Name                  | `PRI_APE SEG_APE^NOMBRES SEG_NOM` (mayúsculas) |
| Birth Date / Sex              | `fec_naci` / `sexo` (M, F, otro → O)     |
| Accession / Requested Proc. ID / SPS ID | `C` + id de la cita (ej. `C1234`) |
| Procedimiento / descripción   | `cod_cups` + `nom_produc`                |
| Modalidad                     | prefijo CUPS                             |
| Fecha y hora programada       | `fecha_hora_inicio` de la cita           |
| Study Instance UID            | `2.25.<UUID>` fijo por cita (así XO/CA llegan al mismo ítem) |

## En el PACS

**dcm4chee 5.x**
- Recibe HL7 en `2575` (aplicación `DCM4CHEE|DCM4CHEE`). No requiere token.
- La lista de trabajo se consulta en el AE **`WORKLIST`**, puerto `11112`
  (el AE `DCM4CHEE` no responde consultas MWL).
- AET del equipo en el ítem: por defecto `SCHEDULEDSTATION`. Para que use el
  "AET del equipo" de la sede, poner el atributo `hl7ScheduledStationAETInOrder`
  = `ORC_18` en la configuración del archivo (dispositivo `dcm4chee-arc`, UI de
  configuración). Alternativa sin tocar Lidiag: reglas *HL7 Order Scheduled
  Station* del PACS por modalidad.

**dcm4chee 2.x**
- Servicio HL7 en `2575` (MBean `service=HL7Server`).
- La lista de trabajo se consulta en el AE del PACS de la sede (Ibagué
  `STDCMIBAGUE`; demás `DCM4CHEE`), puerto `11112`.
- AET del equipo en el ítem: lo pone el PACS, por defecto `UNKOWN` (sic).
  Cambiarlo en la consola JMX (`http://<pacs>:8080/jmx-console/`, solo desde la
  red interna) > MBean `dcm4chee.archive:service=HL7Service,type=ORM` >
  `DefaultStationAET` (o por procedimiento con el Device Service). El XSL
  estándar (`conf/dcm4chee-hl7/orm2dcm.xsl`) no lee `ORC-18`.
- `CA` borra el ítem de la tabla `mwl_item` (5.x lo marca cancelado).
- La consola JMX (8080) y la base de datos (3306) solo se alcanzan desde la red
  interna de la sede.

## En el equipo (Agfa NX)

En NX Service and Configuration Tool: proveedor de lista de trabajo = PACS de la
sede, puerto `11112`, AE remoto = AE de la sede (Ibagué `STDCMIBAGUE`; demás
`DCM4CHEE`; en 5.x `WORKLIST`). En
Gestionar listas > Worklist: sala vacía, fecha hoy, modalidad vacía o `DX`. Si
el equipo filtra por su propio AET, ese AET debe coincidir con el AET de
estación del ítem (ver arriba); si no filtra, basta con la modalidad y la fecha.

## Validado

- dcm4chee **5.34** y **2.18.3** en ambiente de pruebas (2026-10-11): `NW` crea
  el ítem, `XO` cambia la hora, `CA` lo retira; nombre con Ñ y tildes llega
  correcto; PACS sin conexión no bloquea la cita. En 2.x, `NW` tras `CA` lo
  recrea y `NW` repetido actualiza sin duplicar. Mapeo confirmado contra el
  `orm2dcm.xsl` oficial del paquete 2.18.3.
- Pruebas automáticas: `php vendor/bin/phpunit tests/Unit/WorklistServiceTest.php tests/Unit/MllpClientTest.php`.

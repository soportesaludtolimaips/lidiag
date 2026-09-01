# Código del equipo (NO desarrollado por nosotros)

> **Aviso de autoría:** el contenido de esta carpeta `_del_equipo/` proviene del
> proyecto `lidiag_actualizacion` y fue desarrollado por **el equipo de LIDiag**,
> no por Davide Rivera (Luis Castaño). Se copia aquí únicamente como **referencia**
> de lo que se integró a `lidiag-main`. **No lleva nuestra firma.**

## Qué contiene

### 1. Contratación
Módulo para gestionar contratos por sede, sus productos y los médicos asociados.

- `app/Http/Controllers/Contratacion/` — controladores REST.
- `app/Models/Contratacion/` — `Contrato`, `ContratoProducto`, `ContratoMedico`.
- `app/Services/Contratacion/` — lógica de negocio.
- `app/Http/Requests/Contratacion/` — validaciones.
- `database/seeders/PermisionContratacionSeeder.php` — 8 permisos `contratos.*`.
- `resources/js/components/modulos/contratos/`, `resources/views/modulos/contratos/`.
- `routes/api_contratacion.php`.

**Datos que maneja (tablas):**
- `contratos`: sede_id, fecha_inicio, fecha_fin, observaciones, estado.
- `contratos_productos`: contrato_id, producto_id, valor_contrato, estado.
- `contratos_medicos`: contrato_id, medico_id, contratos_productos_id, valor_contrato, valor_consulta, valor_urgencias, estado.

### 2. Quemador de CD DICOM
Prepara paquetes de estudios DICOM para grabar en CD/ISO o descargar en ZIP.

- `app/Services/DicomBurnerService.php`, `app/Http/Controllers/DicomBurnerController.php`.
- `app/Helpers/DicomHelper.php` — descarga imágenes DICOM del PACS vía **WADO** (`http://{ip_pacs}:8080/wado`).
- `resources/burner/`, `routes/api_burn.php`.

**Datos que maneja (tabla `cd_burn_logs`):** patient_id, study_instance_uids, user_id, device_letter, status, progress, error_message.

**Revisión de seguridad (corregida):** el *Service* y el *Helper* no ejecutan comandos, pero
el **`DicomBurnerController` SÍ usa `exec()`** para dos cosas en Windows:
1. Listar unidades de CD: `powershell -Command "Get-CimInstance Win32_CDROMDrive ..."`.
2. Grabar el disco: invoca `resources/burner/burner.exe <unidad> <carpeta>` (argumentos
   escapados con `escapeshellarg`). Ese `.exe` es un programa **C# (fuente `Program.cs` incluida)**
   que usa la API **IMAPI2** de Windows (Image Mastering API) para quemar el CD/DVD.

No hay URLs de terceros embebidas ni envío de datos afuera. La única llamada de red es un
**GET al PACS interno** (WADO) para traer las imágenes; el host del PACS se pasa por parámetro.

> **A tener en cuenta:** `burner.exe` es un **binario compilado** que la aplicación ejecuta en
> el servidor con permiso de escritura en la unidad óptica. Viene del equipo; su código fuente
> (`Program.cs`) está incluido para que puedas revisarlo/recompilarlo. No hace nada más que grabar.

### 3. Exports (reportes a Excel)
- `app/Exports/ContratoPlantillaExport.php`, `ContratoDetallePlantillaExport.php` — plantillas de tarifas de contratos.
- `app/Exports/ReportesEstudioPAC.php` — reporte de estudios; lee una **BD de sucursal**
  configurada por variables de entorno `DB_SUCURSAL_*` (conexión secundaria tuya, no un tercero).
- Usan `maatwebsite/excel` + `phpoffice/phpspreadsheet` + `phpoffice/phpword` (ya presentes en `vendor`).

Todos los reportes se generan a partir de **tu propia base de datos**.

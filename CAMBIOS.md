# LIDiag — Trabajo realizado (respaldo / pruebas)

**Autor:** Davide Rivera (Luis Castaño)

Snapshot del trabajo hecho. Replica la estructura del proyecto
`C:\xampp\htdocs\lidiag-main`, pero **solo con lo que se trabajó**.

- **Archivos de código:** ~83 (creados + modificados)
- **Pruebas generadas:** carpeta `_pruebas/` (imágenes exportadas + PDFs)

---

## 1. Módulo de Consentimientos Informados (completo)

Backend + frontend end-to-end: migraciones (11 tablas), modelos, servicios,
controladores, FormRequests, comando de expiración, middleware de bloqueo de
lecturas, seeders (permisos + datos/usuarios de prueba), rutas
`api_consentimientos.php`, 6 componentes Vue y 5 vistas Blade.

Flujo: **generar → token/QR → firma paciente (canvas) → firma médico → PDF**,
con marcas sobre imágenes, adjuntos, versionado (diff), estadísticas y bloqueo
de lecturas con liberación temporal.

### Imágenes marcables (última funcionalidad)
- Backend: `ConsentimientoPlantillaImagenService/Controller/Request`, disco
  `imagenes_consentimientos`, 4 rutas (subir/listar/ver/eliminar).
- Frontend: gestión de imágenes en el componente de Plantillas +
  `consentimientoMarcasCanvas.vue` (marcar zonas con clic sobre la imagen).

---

## 2. Exportación/envío de imágenes DICOM como JPG/JPEG (OHIF/PACS)

Para enviar estudios a sistemas externos que solo aceptan JPG/JPEG.

- `OhifVisorService::exportarJpeg()` — obtiene el JPEG del PACS vía WADO.
  - **Variante limpia** (solo el estudio).
  - **Variante "con datos"** — overlay tipo K-PACS **quemado en PHP** (GD):
    nombre, documento, fecha nacimiento, fecha estudio, modalidad. Toma los
    datos de la BD de LIDiag y, si el estudio no está, del **QIDO-RS del PACS**.
- `OhifVisorService::enviarImagenExterna()` — envía el JPEG al sistema receptor
  configurable (multipart o base64).
- Endpoints: `GET /api/dicom/export-jpeg`, `POST /api/dicom/enviar-jpeg`.
- Config en `config/services.php`:
  - `services.pacs` → `PACS_WADO_URL`, `PACS_QIDO_URL`, `PACS_AET`.
    - dcm4chee 2.x (prod): `http://{ip}:8080/wado`
    - dcm4chee-arc 5.x (pruebas): `http://{ip}:8080/dcm4chee-arc/aets/{aet}/wado`
  - `services.imagen_externa` → `IMAGEN_EXTERNA_ENABLED/URL/TOKEN/METODO/CAMPO`.

**Probado contra dcm4chee-arc 5.34.1 local** (Docker, `localhost:8080`).

---

## 3. Carpeta `_pruebas/` (muestras generadas)

```
_pruebas/
  imagenes/
    estudio_LIMPIA.jpg      (37 KB)  — imagen del estudio sin overlay
    estudio_CON_DATOS.jpg   (61 KB)  — con overlay de datos (Juan Pérez López, CT, 2021-09-24)
  pdfs/
    Consentimiento_111222333_14.pdf   — consentimiento firmado (paciente + médico) con firma real
    Consentimiento_777888999_15.pdf
```

Firma médica de las pruebas: `C:\Users\Davide\Pictures\Saved Pictures\Firma.jpg`.

> Nota: los acentos del overlay salen como `?` (fuente interna de GD es ASCII).
> Para acentos correctos hay que usar una fuente TTF (imagettftext).

---

## 4. Archivos modificados (existentes) — qué se cambió

| Archivo | Cambio |
|---|---|
| `bootstrap/app.php` | Registra `api_consentimientos.php` + alias middleware `consent.bloqueo` |
| `routes/web.php` | Vistas internas + firma por token del módulo |
| `routes/api_configuracion.php` | Ruta `comparar` + **fix** `config-modulos-listar`→`index` |
| `routes/api_ecografias.php` / `api_estudios.php` | Middleware `consent.bloqueo` en lecturas |
| `routes/api_OHIF_visor.php` | Rutas `export-jpeg` y `enviar-jpeg` |
| `routes/console.php` | Agenda `consentimientos:expirar` |
| `config/services.php` | Bloques `pacs` e `imagen_externa` |
| `config/filesystems.php` | Discos `adjuntos_consentimientos`, `imagenes_consentimientos` |
| `resources/js/app.js` | Registra componentes Vue de consentimientos |
| `resources/js/helpers/api.js` | Grupo API `consentimientos` |
| `app/Models/Consentimientos/ConsentimientoPlantilla.php` | Relaciones nuevas |
| `app/Services/Consentimientos/ConsentimientoPlantillaService.php` | `comparar` + diff |
| `app/Services/OHIF_visor/OhifVisorService.php` | `exportarJpeg`, overlay, `enviarImagenExterna` |
| `app/Http/Controllers/OHIF_visor/OhifVisorController.php` | `exportarJpeg`, `enviarJpeg` |
| `database/seeders/DatabaseSeeder.php` | Llama al seeder de permisos |
| `resources/views/comunes/sidebar.blade.php` | Entradas de menú |
| `app/Providers/AppServiceProvider.php` | `forceScheme` condicional |

---

## 5. Bugs preexistentes encontrados y arreglados

1. `config-modulos-listar` → método inexistente `listarModulos` → corregido a `index`.
2. `users.password_changed_at` faltante → bloqueaba TODO login → migración creada.
3. `barryvdh/laravel-dompdf` ausente → el módulo usa fallback a `Dompdf\Dompdf` nativo.

---

## 6. Cómo validar

- Servir: `php artisan serve` (http://localhost:8000) + `npm run build` + Ctrl+Shift+R.
- Usuarios de prueba (password `password`): `test.admin`, `test.medico`,
  `test.recepcion`, `test.transcriptor` @lidiag.test.
- Datos demo: 7 consentimientos en varios estados; pacientes `444555666`,
  `111222333`, `777888999`; plantilla Rx con productos 1-3.
- PACS de pruebas: dcm4chee-arc en `C:\pacs\dcm4chee` (Docker, localhost:8080).

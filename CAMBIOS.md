# LIDiag — Módulo de Consentimientos Informados

Snapshot de **todo lo realizado** (archivos creados y modificados), como respaldo
para validar. Esta carpeta replica la estructura del proyecto
`C:\xampp\htdocs\lidiag-main`, pero contiene **solo lo que se trabajó**, no todo el proyecto.

- **Archivos creados:** 59
- **Archivos modificados:** 14
- **Total:** 73

---

## 1. Archivos CREADOS (59)

### Migraciones (`database/migrations/`) — 10
Tablas del módulo genérico + fix de autenticación:
`consentimiento_plantilla_versiones`, `_imagenes`, `_productos`,
`consentimiento_documentos`, `_tokens`, `_marcas`, `_adjuntos`, `_auditoria`,
`consentimiento_bloqueo_liberaciones`, y `add_password_changed_at_to_users`.

### Modelos (`app/Models/Consentimientos/`) — 9
`ConsentimientoDocumento`, `ConsentimientoDocumentoToken`, `…Marca`, `…Adjunto`,
`…Auditoria`, `ConsentimientoPlantillaVersion`, `…Imagen`, `…Producto`,
`ConsentimientoBloqueoLiberacion`.

### Servicios (`app/Services/Consentimientos/`) — 6
`ConsentimientoDocumentoService` (flujo generar→token→firma×2→PDF),
`…MarcaService`, `…AdjuntoService`, `…BloqueoService`, `…EstadisticaService`,
`…PlantillaProductoService`.

### Controladores (`app/Http/Controllers/Consentimientos/`) — 6
`ConsentimientoDocumentoController`, `…MarcaController`, `…AdjuntoController`,
`…BloqueoController`, `…EstadisticaController`, `…PlantillaProductoController`.

### FormRequests (`app/Http/Requests/Consentimientos/`) — 10
Generar, CrearToken, FirmarMedico, Anular, MarcaStore, MarcaUpdate,
AdjuntoStore, LiberarBloqueo, Estadistica, PlantillaProductoStore.

### Middleware / Comando — 2
- `app/Http/Middleware/BloqueoLecturasConsentimientos.php` (bloqueo de lecturas, HTTP 423)
- `app/Console/Commands/ExpirarConsentimientos.php` (`consentimientos:expirar`)

### Seeders (`database/seeders/`) — 4
- `PermisionConsentimientosSeeder` (22 permisos Spatie)
- `ConsentimientosDemoSeeder` (plantilla + pacientes + asignaciones)
- `ConsentimientosDemoFlujoSeeder` (firmas + documentos pendientes)
- `ConsentimientosTestUsersSeeder` (usuarios de prueba)

### Rutas — 1
`routes/api_consentimientos.php` (todas las rutas API del módulo).

### Frontend Vue (`resources/js/components/modulos/consentimientos/`) — 5
`consentimientoFirmaComponent` (tablet), `consentimientosGenerarComponent`,
`consentimientosMedicoComponent`, `consentimientosPlantillasComponent`,
`consentimientosDashboardComponent`. (Composition API, `<script setup>`.)

### Vistas Blade (`resources/views/modulos/consentimientos/`) — 5
`firma`, `generar`, `medico`, `plantillas`, `dashboard`.

### Docs — 1
`docs/lidiag.test.vhost.conf` (vhost de Apache para XAMPP).

---

## 2. Archivos MODIFICADOS (14) — qué se cambió en cada uno

| Archivo | Cambio realizado |
|---|---|
| `bootstrap/app.php` | Se agregó `api_consentimientos.php` a la lista de rutas API + alias de middleware `consent.bloqueo`. |
| `routes/web.php` | Import del `ConsentimientoDocumentoController`; vistas internas (`/consentimientos-generar`, `-medico`, `-plantillas-config`, `-dashboard`); rutas públicas de firma por token (`/firma/consentimiento/{token}` + `/info` + `/firmar`). |
| `routes/api_configuracion.php` | Ruta nueva `consentimientos-plantillas/comparar`. **FIX**: `config-modulos-listar` apuntaba a `listarModulos` (inexistente) → ahora a `index`. |
| `routes/api_ecografias.php` | Middleware `consent.bloqueo` en `PUT ecografia-lectura/{id}`. |
| `routes/api_estudios.php` | Middleware `consent.bloqueo` en `POST estudio-leerEstudio`. |
| `routes/console.php` | Agendado `consentimientos:expirar` cada 5 min. |
| `resources/js/app.js` | Registro de los 5 componentes Vue de consentimientos. |
| `resources/js/helpers/api.js` | Grupo `consentimientos` con todos los endpoints (documentos, medicos, marcas, adjuntos, plantillas, comparar, asignaciones, bloqueo, estadísticas). |
| `app/Models/Consentimientos/ConsentimientoPlantilla.php` | Relaciones nuevas: `versiones()`, `imagenes()`, `productosAsignados()`. |
| `app/Services/Consentimientos/ConsentimientoPlantillaService.php` | Métodos `comparar()` + `diffLineas()` (diff por líneas con LCS). |
| `config/filesystems.php` | Disco `adjuntos_consentimientos` (local, private). |
| `database/seeders/DatabaseSeeder.php` | Llamada a `PermisionConsentimientosSeeder`. |
| `resources/views/comunes/sidebar.blade.php` | 4 entradas de menú + los permisos nuevos en el `hasAnyPermission`. |
| `app/Providers/AppServiceProvider.php` | `URL::forceScheme('https')` ahora es **condicional** (solo si `APP_URL` es https). Permite servir en `http://localhost:8000`. |

---

## 3. Cambios en Base de Datos (no son archivos)

- **Migraciones ejecutadas** (`php artisan migrate`): 11 tablas nuevas + columna `users.password_changed_at`.
- **Permisos sembrados**: `php artisan db:seed --class=PermisionConsentimientosSeeder` (22 permisos `consentimientos.*`).
- **Datos de prueba** (re-ejecutables):
  ```
  php artisan db:seed --class=ConsentimientosDemoSeeder
  php artisan db:seed --class=ConsentimientosDemoFlujoSeeder
  php artisan db:seed --class=ConsentimientosTestUsersSeeder
  ```

---

## 4. Cambios de entorno (temporales del chat, NO se copian)

- `.env`: `APP_URL=http://localhost:8000` y `SANCTUM_STATEFUL_DOMAINS` a localhost
  (respaldo en `.env.backup-serve`). Revertir con `cp .env.backup-serve .env`.
- Se eliminó `public/hot` para usar los assets compilados (`npm run build`).
- Apache/XAMPP: se agregó el vhost `lidiag.test` a `httpd-vhosts.conf` (falta la
  entrada en `hosts` + PHP Thread-Safe para que Apache lo sirva).

---

## 5. Bugs PREEXISTENTES encontrados y arreglados (no eran del módulo)

1. **`config-modulos-listar`** → apuntaba a un método inexistente `listarModulos`.
   Corregido a `index`. (Afectaba también al módulo de ecografías.)
2. **`users.password_changed_at`** faltaba: bloqueaba TODO login (el middleware
   `ForcePasswordChange` forzaba el cambio y fallaba al guardar). Migración creada.
3. **`barryvdh/laravel-dompdf`** no instalado aunque 3 servicios de producción lo
   usan: el servicio del módulo usa fallback a `Dompdf\Dompdf` nativo.

---

## 6. Cómo validar

1. Servir en `http://localhost:8000` con `php artisan serve` (PHP CLI 8.4 funciona;
   Apache no, por PHP NTS).
2. Hacer `npm run build` (assets) y **Ctrl+Shift+R** en el navegador.
3. Usuarios de prueba (contraseña `password`, sin cambio forzado):
   - `test.admin@lidiag.test` (Administrador)
   - `test.medico@lidiag.test` (Médico, con firma, 2 pendientes)
   - `test.recepcion@lidiag.test` (Administrativo)
   - `test.transcriptor@lidiag.test` (Transcriptor)
4. Menú **Configuración** → entradas de Consentimientos (Generar, Firmas Médico,
   Asignación/Versiones, Dashboard).
5. Documentos demo: 7 en varios estados (GENERADO, PENDIENTE_MEDICO, COMPLETO, EXPIRADO).
   Pacientes buscables: `444555666`, `111222333`, `777888999`.

---

## 7. Estado del módulo

Backend + frontend **funcionalmente completos** (Fases 1–5). Pendientes opcionales:
migración de datos `ecografias_consentimientos` → genérico, canvas de marcas sobre
imágenes, verificar dompdf TS en producción, eliminar tabla
`consentimiento_plantilla_versiones` sin uso (el versionado es por-fila).

# Documentación de Controladores - LIDiag

Este documento proporciona un resumen de todos los controladores del sistema LIDiag, organizados por módulo.

## Módulo: Ecografías

### `EcografiaController.php`

Gestión de ecografías.

-   **`index`**: Lista ecografías con filtros.
-   **`store`**: Crea nueva ecografía.
-   **`show`**: Muestra ecografía específica.
-   **`update`**: Actualiza ecografía.
-   **`destroy`**: Elimina ecografía.
-   **`listarPendientesPorLectura`**: Lista ecografías pendientes asignadas a un médico.

### `ecografiaLecturaController.php`

Gestión de lecturas de ecografías.

-   **`guardarLectura`**: Guarda/actualiza lectura de ecografía.

### `EcografiaProductoController.php`

Gestión de productos asociados a ecografías.

-   **`index`**: Lista productos de ecografía.
-   **`store`**: Asocia producto a ecografía.
-   **`update`**: Actualiza producto.
-   **`destroy`**: Elimina producto.

## Módulo: Agenda

### `AgendaController.php`

Controlador scaffold sin implementación actual.

### `AgendaProductoController.php`

Controlador scaffold sin implementación actual.

## Módulo: Configuración

### `ConfigAdminSaludController.php`

Gestión de Administradoras de Salud (EPS).

-   **`index`**: Lista administradoras.
-   **`store`**: Crea administradora.
-   **`update`**: Actualiza administradora.
-   **`destroy`**: Elimina administradora.

### `ConfigDiagnosticoController.php`

Gestión de Diagnósticos (CIE-10).

-   **`index`**: Lista diagnósticos.
-   **`store`**: Crea diagnóstico.
-   **`update`**: Actualiza diagnóstico.
-   **`destroy`**: Elimina diagnóstico.

### `ConfigModulosController.php`

Gestión de Módulos del sistema.

-   **`index`**: Lista módulos.
-   **`listarModulos`**: Alias para listar módulos.

### `ConfigPlantillaController.php`

Gestión de Plantillas de Lectura.

-   **`index`**: Lista plantillas.
-   **`store`**: Crea plantilla.
-   **`show`**: Muestra plantilla.
-   **`update`**: Actualiza plantilla.
-   **`destroy`**: Elimina plantilla.
-   **`listarActivos`**: Lista plantillas activas.

### `ConfigPlantillaTipoController.php`

Gestión de Tipos de Plantillas.

-   **`index`**: Lista tipos.
-   **`store`**: Crea tipo.
-   **`show`**: Muestra tipo.
-   **`update`**: Actualiza tipo.
-   **`destroy`**: Elimina tipo.
-   **`listarActivos`**: Lista tipos activos.

### `ConfigPrioridadController.php`

Gestión de Prioridades de atención.

-   **`index`**: Lista prioridades.
-   **`store`**: Crea prioridad.
-   **`update`**: Actualiza prioridad.
-   **`destroy`**: Elimina prioridad.

### `ConfigProductoController.php`

Gestión de Productos/Servicios (CUPS).

-   **`index`**: Lista productos.
-   **`show`**: Muestra producto.
-   **`store`**: Crea producto.
-   **`update`**: Actualiza producto.
-   **`destroy`**: Elimina producto.

### `ConfigSedeController.php`

Gestión de Sedes y configuración PACS/OVIYAM.

-   **`index`**: Lista sedes.
-   **`store`**: Crea sede y guarda plantillas.
-   **`buscarPorId`**: Retorna sede por ID.
-   **`listarSedesActivas`**: Lista sedes activas.
-   **`update`**: Actualiza sede.
-   **`destroy`**: Elimina sede.

### `ConfigTipoDocumentoController.php`

Gestión de Tipos de Documento.

-   **`index`**: Lista tipos de documento.
-   **`store`**: Crea tipo de documento.
-   **`update`**: Actualiza tipo de documento.
-   **`destroy`**: Elimina tipo de documento.

## Módulo: Estudios

### `EstudioController.php`

Gestión de estudios de imágenes diagnósticas.

-   **`index`**: Lista estudios con filtros.
-   **`store`**: Crea estudio, paciente, productos y diagnósticos.
-   **`listarPendientesMedico`**: Lista estudios pendientes de lectura.
-   **`leerEstudio`**: Obtiene datos para lectura.
-   **`guardarTranscripcion`**: Finaliza transcripción y genera reporte.
-   **`listarPendientesTrascribir`**: Lista estudios pendientes de transcribir.
-   **`listarEstudiosAsignados`**: Lista estudios asignados a un médico.
-   **`buscarEstudioPorNumDocu`**: Busca estudios por documento.
-   **`anularAsignacion`**: Anula asignación de estudio.
-   **`descargarPdfLectura`**: Descarga PDF de lectura.
-   **`notificarEmail`**: Envía notificación por email.

### `PlantillaEstudioController.php`

Generación de reportes PDF.

-   **`editWord`**: Genera reporte Word y lo convierte a PDF.
-   **`convertDocxToPdf`**: Utilidad de conversión usando `unoconv`.

### `EstudioNotificacionController.php`

Gestión de notificaciones.

-   **`listarEstudios`**: Lista estudios transcritos.
-   **`notificarEmail`**: Envía reporte por email.
-   **`descargaImgDicom`**: Descarga imágenes WADO.
-   **`enviarMensajeWhatsApp`**: Envía notificación por WhatsApp.

### `EstudioAudioController.php`

Gestión de audios de estudios.

-   **`uploadAudio`**: Sube y convierte audio a MP3 usando FFmpeg.
-   **`listarAudiosPorEstudio`**: Lista audios de un estudio.

### `EstudioDiagnosticoController.php`

Controlador scaffold sin implementación actual.

### `EstudioProductoController.php`

Gestión de productos de estudios.

-   **`index`**: Lista productos de un estudio.
-   **`store`**: Crea producto asociado a estudio.

### `EstudioSoporteHCController.php`

Gestión de soportes de historia clínica.

-   **`listarSoportesHistoriaClinicaPorPorEstudio`**: Lista soportes de un estudio.
-   **`descargarSoporte`**: Descarga archivo de soporte.

## Módulo: Dashboard

### `DashBoardController.php`

Estadísticas del dashboard.

-   **`dashboard`**: Obtiene estadísticas según perfil (Administrador/Médico).

## Módulo: Dcm4chee

### `StudyController.php`

Integración con PACS DCM4CHEE.

-   **`listarEstudios`**: Lista estudios desde base de datos DCM4CHEE con conexión dinámica por sede.

## Módulo: Interface

### `SAHIController.php`

Integración con sistema SAHI (General).

-   **`listarAtenciones`**: Lista ingresos de pacientes.
-   **`listarProductosDiagnosticos`**: Lista productos facturados.
-   **`buscarEpsPlanContrato`**: Datos de aseguradora.
-   **`listarProductosAtencion`**: Lista productos combinando facturación y movimientos.

### `SAHIEcografiasController.php`

Integración SAHI para Ecografías.

-   **`listarProductosAtencion`**: Lista productos de ecografía facturados.

### `SAHITranscibirController.php`

Transcripción de resultados hacia SAHI.

-   **`enviarEstudio`**: Proceso complejo de inserción de resultados en tablas de historia clínica de SAHI.

### `TeleRadiologiaController.php`

Integración con Teleradiología.

-   **`enviarOrdenTeleRadiologia`**: Proxy para envío de órdenes a API externa.

## Módulo: Reportes

### `ReporteProduccion.php`

Generación de reportes Excel.

-   **`reporteProduccionDetallado`**: Genera Excel de producción (Rx/Eco) con filtros.

### `ResporteLecturaController.php`

Generación de reportes PDF.

-   **`generar_reporte`**: Genera PDF de lectura usando Dompdf.

## Módulo: Seguridad

### `RolController.php`

Gestión de Roles y Permisos.

-   **`index`**: Lista roles y permisos.
-   **`store`**: Crea rol y asigna permisos.
-   **`show`**: Muestra rol con permisos asignados.
-   **`update`**: Actualiza rol y sincroniza permisos.
-   **`destroy`**: Elimina rol.

### `UserController.php`

Gestión de Usuarios.

-   **`index`**: Lista usuarios con módulos y roles.
-   **`listarUsuarios`**: Filtra usuarios por rol y módulo.
-   **`show`**: Muestra usuario con roles.
-   **`estadoUsuario`**: Activa/desactiva usuario.
-   **`store`**: Crea usuario con validación, roles, módulos y archivos.
-   **`update`**: Actualiza usuario con sincronización.
-   **`usuariosPorModulo`**: Lista usuarios de un módulo.

### `LoginController.php`

(Ubicado en `app/Http/Controllers/Auth`) Autenticación.

-   **`login`**: Procesa inicio de sesión.
-   **`logout`**: Cierra sesión.
-   **`showLoginForm`**: Muestra formulario de login.

## Módulo: Raíz (General)

### `HomeController.php`
<<<<<<< HEAD

Dashboard principal.

-   **`index`**: Renderiza vista home (requiere autenticación).

### Controladores Scaffold

Los siguientes controladores existen como estructura pero no tienen implementación actual:

-   **`AgendamientoCitaController.php`**
-   **`CitaController.php`**
-   **`HelpDeskTicketsController.php`**
-   **`EstudioDiagnosticoController.php`**
=======
Controlador raíz de la SPA.
- **`index`**: Retorna la vista blade principal que monta la aplicación Vue 3.

---

## Módulo: Pacientes y Modality Worklist (MWL)

### `PacienteController.php`
Controlador delgado (Thin Controller) para la administración del catálogo de pacientes y la emisión/cancelación de órdenes a la Modality Worklist DICOM de Dcm4chee.
- **`listar`**: Consulta pacientes filtrados por documento, nombre o rango de fechas, adjuntando el estado de órdenes activas de Worklist del día para la sede seleccionada.
- **`store`**: Registra un nuevo paciente validando unicidad de documento y formato.
- **`show`**: Retorna la ficha demográfica del paciente.
- **`update`**: Actualiza los datos del paciente asegurando que el documento no colisione con otro registro.
- **`destroy`**: Elimina un paciente siempre que no posea estudios, ecografías ni órdenes previas.
- **`obtenerCatalogos`**: Provee los tipos de documento y procedimientos CUPS de Rayos X activos para el modal de admisión.
- **`obtenerAccessionSugerido`**: Genera un número de acceso único secuencial con prefijo `RX-YYYYMMDD-####`.
- **`enviarWorklist`**: Inserta el paciente y la orden de Rayos X en la base de datos de Dcm4chee (`patient` y `mwl_item` con dataset binario DICOM `item_attrs`) y persiste la orden en LIDiag (`worklist_ordenes`).
- **`cancelarWorklist`**: Retira la orden activa de la consola del equipo de Rayos X (eliminando el registro en Dcm4chee `mwl_item`) y marca el estado en LIDiag como `CANCELADO`.
>>>>>>> 01b98816 (feat(worklist): modulo de admision y modality worklist (MWL DICOM) multisede dinamico)

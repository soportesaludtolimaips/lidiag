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

## Módulo: Contratación

### `ContratoController.php`

Gestiona los contratos por sede: vigencia, productos tarifados y detalle médico-producto.

-   **`cobertura`**: Informa qué productos del catálogo exigibles no tienen tarifa en el contrato.
-   **`previewMedicos`**: Calcula el diff del detalle de médicos sin aplicarlo.
-   **`syncMedicos`**: Aplica el diff del detalle; nunca borra registros existentes.

## Módulo: Liquidación

### `LiquidacionController.php`

Calcula y exporta la liquidación de pagos a médicos por sede y periodo.

-   **`calcular`**: Devuelve líneas, exclusiones con conteo y totales por médico.
-   **`exportar`**: Descarga la liquidación del periodo en Excel.

## Módulo: Raíz (General)

### `HomeController.php`

Dashboard principal.

-   **`index`**: Renderiza vista home (requiere autenticación).

### Controladores Scaffold

Los siguientes controladores existen como estructura pero no tienen implementación actual:

-   **`AgendamientoCitaController.php`**
-   **`CitaController.php`**
-   **`HelpDeskTicketsController.php`**
-   **`EstudioDiagnosticoController.php`**

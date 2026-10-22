# Documentación de Controladores — LIDiag

Este documento proporciona un resumen e índice de todos los controladores del sistema LIDiag, organizados por módulo funcional. Sirve como mapa de contexto para agentes de desarrollo.

---

## Módulo: Ecografías

### `EcografiaController.php`
Gestión integral del ciclo de vida de ecografías.
- **`index`**: Lista ecografías aplicando filtros dinámicos.
- **`store`**: Crea y almacena una nueva ecografía.
- **`show`**: Muestra el detalle de una ecografía específica.
- **`update`**: Actualiza los datos de una ecografía.
- **`destroy`**: Elimina de forma lógica o física una ecografía.
- **`listarPendientesPorLectura`**: Lista ecografías pendientes asignadas a un médico radiólogo.

### `EcografiaLecturaController.php`
Gestión de informes y lecturas del módulo de ecografías.
- **`guardarLectura`**: Guarda, actualiza y consolida la lectura/informe de una ecografía.

### `EcografiaProductoController.php`
Gestión de procedimientos y productos asociados a ecografías.
- **`index`**: Lista los productos asignados al módulo de ecografía.
- **`store`**: Asocia un nuevo producto/procedimiento a una ecografía.
- **`update`**: Actualiza los datos de un producto asociado.
- **`destroy`**: Elimina la asociación de un producto con la ecografía.

### `EcografiaConsentimientosController.php`
Gestión del flujo de consentimientos informados firmados de ecografías.
- **`show`**: Retorna el consentimiento vigente y el estado de firma del paciente.
- **`firmar`**: Registra la firma digital del paciente usando un token de acceso público.
- **`listar`**: Lista los consentimientos firmados asociados a una ecografía.

### `PlantillaEcografiaController.php`
Gestión de plantillas de texto base para informes de ecografía.
- **`index`**: Lista las plantillas de ecografía disponibles.
- **`store`**: Crea una nueva plantilla de ecografía.
- **`update`**: Actualiza el contenido de una plantilla existente.
- **`destroy`**: Elimina una plantilla de ecografía.

---

## Módulo: Agenda / Citas

### `AgendaCitaController.php`
Gestión de citas del módulo de agendamiento.
- **`index`**: Lista citas con filtros por sede, estado y rango de fechas.
- **`store`**: Crea una nueva cita asignando paciente, servicio y disponibilidad.
- **`show`**: Retorna el detalle de una cita específica.
- **`update`**: Actualiza los datos o estado de una cita.
- **`destroy`**: Elimina una cita del sistema.
- **`cambiarEstado`**: Cambia el estado de una cita con validación de transiciones permitidas.

### `AgendaDisponibilidaController.php`
Gestión de bloques de disponibilidad para agendamiento.
- **`index`**: Lista la disponibilidad por sede y servicio.
- **`store`**: Crea un nuevo bloque de disponibilidad para un médico/servicio.
- **`update`**: Modifica los parámetros de un bloque de disponibilidad.
- **`destroy`**: Elimina un bloque de disponibilidad.

### `AgendaDisponibilidaDetalleController.php`
Gestión del detalle de slots de tiempo dentro de bloques de disponibilidad.
- **`index`**: Lista los slots de tiempo disponibles para una disponibilidad.
- **`store`**: Registra un nuevo slot de tiempo.
- **`destroy`**: Elimina un slot de tiempo.

### `ConfigCitaRechazoMotivoController.php`
Gestión del catálogo de motivos de rechazo de citas.
- **`index`**: Lista los motivos de rechazo configurados.
- **`store`**: Crea un nuevo motivo de rechazo.
- **`update`**: Modifica un motivo de rechazo existente.
- **`destroy`**: Elimina un motivo de rechazo.

---

## Módulo: Configuración

### `ConfigAdminSaludController.php`
Gestión del catálogo de Administradoras de Salud (EPS).
- **`index`**: Lista las administradoras registradas.
- **`store`**: Crea y almacena una nueva administradora.
- **`update`**: Actualiza los datos de una administradora existente.
- **`destroy`**: Elimina una administradora de salud.

### `ConfigDiagnosticoController.php`
Gestión del catálogo de Diagnósticos médicos (Codificación CIE-10).
- **`index`**: Lista los diagnósticos del sistema.
- **`store`**: Crea y registra un nuevo diagnóstico CIE-10.
- **`update`**: Actualiza un diagnóstico existente.
- **`destroy`**: Elimina un diagnóstico del catálogo.

### `ConfigModulosController.php`
Gestión y control de los módulos activos de la plataforma.
- **`index`**: Lista los módulos del sistema.
- **`listarModulos`**: Alias funcional para el listado de módulos.

### `ConfigPlantillaController.php`
Gestión de Plantillas estructurales de Lectura médica.
- **`index`**: Lista las plantillas disponibles.
- **`store`**: Crea y almacena una nueva plantilla de lectura.
- **`show`**: Muestra los detalles y estructura de una plantilla.
- **`update`**: Actualiza los bloques de una plantilla.
- **`destroy`**: Elimina una plantilla del sistema.
- **`listarActivos`**: Filtra y retorna únicamente las plantillas marcadas como activas.

### `ConfigPlantillaTipoController.php`
Gestión de las categorías o Tipos de Plantillas.
- **`index`**: Lista los tipos de plantillas registrados.
- **`store`**: Crea un nuevo tipo de plantilla.
- **`show`**: Muestra el detalle de una categoría de plantilla.
- **`update`**: Actualiza la configuración de un tipo de plantilla.
- **`destroy`**: Elimina un tipo de plantilla.
- **`listarActivos`**: Retorna los tipos de plantillas activos.

### `ConfigPrioridadController.php`
Gestión de Prioridades para la atención y agendamiento de pacientes.
- **`index`**: Lista los niveles de prioridad configurados.
- **`store`**: Registra un nuevo nivel de prioridad.
- **`update`**: Modifica los parámetros de una prioridad.
- **`destroy`**: Elimina un nivel de prioridad.

### `ConfigProductoController.php`
Gestión del catálogo maestro de Productos, Servicios y Procedimientos (CUPS).
- **`index`**: Lista los productos/CUPS registrados.
- **`show`**: Muestra la ficha técnica de un procedimiento específico.
- **`store`**: Crea y registra un nuevo producto en el catálogo.
- **`update`**: Actualiza los datos o códigos de un producto.
- **`destroy`**: Elimina un procedimiento del catálogo maestro.

### `ConfigRecomendacionProductoController.php`
Gestión de recomendaciones de productos por servicio de citas.
- **`index`**: Lista las recomendaciones configuradas.
- **`store`**: Crea una nueva recomendación producto-servicio.
- **`update`**: Actualiza una recomendación existente.
- **`destroy`**: Elimina una recomendación.

### `ConfigSedeController.php`
Gestión de Sedes físicas y parametrización de nodos PACS/OVIYAM.
- **`index`**: Lista las sedes configuradas en el sistema.
- **`store`**: Crea una sede, inicializa sus parámetros y vincula sus plantillas base.
- **`buscarPorId`**: Retorna la información estructurada de una sede mediante su ID.
- **`listarSedesActivas`**: Lista las sedes habilitadas operativamente.
- **`update`**: Modifica la configuración física o de red PACS de una sede.
- **`destroy`**: Elimina una sede del sistema.

### `ConfigSedeWhatsappNumberController.php`
Gestión de números de WhatsApp asociados a sedes.
- **`index`**: Lista los números de WhatsApp configurados por sede.
- **`store`**: Registra un nuevo número de WhatsApp para una sede.
- **`update`**: Actualiza un número de WhatsApp existente.
- **`destroy`**: Elimina un número de WhatsApp de la configuración.

### `ConfigServicioController.php`
Gestión del catálogo de servicios disponibles para citas.
- **`index`**: Lista los servicios registrados.
- **`store`**: Crea un nuevo servicio.
- **`update`**: Actualiza los datos de un servicio.
- **`destroy`**: Elimina un servicio del catálogo.

### `ConfigTipoDocumentoController.php`
Gestión de Tipos de Documento de identidad aceptados.
- **`index`**: Lista los tipos de documento cargados.
- **`store`**: Registra una nueva tipología de documento de identidad.
- **`update`**: Modifica la descripción o siglas de un tipo de documento.
- **`destroy`**: Elimina un tipo de documento.

### `ConfigWhatsappController.php`
Gestión de la configuración global de WhatsApp (templates, credenciales, flujos).
- **`index`**: Lista la configuración de WhatsApp registrada.
- **`store`**: Crea o actualiza la configuración de WhatsApp.
- **`show`**: Retorna la configuración activa de WhatsApp.
- **`update`**: Actualiza parámetros de configuración de WhatsApp.

### `ConsentimientoInformadoController.php`
Gestión del catálogo maestro de documentos de Consentimiento Informado (PDF físico).
- **`index`**: Lista todos los consentimientos informados registrados.
- **`show`**: Muestra el detalle de un consentimiento específico.
- **`listarActivos`**: Lista únicamente los consentimientos activos.
- **`store`**: Crea un nuevo consentimiento informado con su archivo adjunto.
- **`update`**: Actualiza los datos y/o archivo de un consentimiento.
- **`destroy`**: Elimina un consentimiento del catálogo.

### `ServicioProductoController.php`
Gestión de la relación entre servicios de citas y productos CUPS.
- **`index`**: Lista los productos asociados a un servicio.
- **`store`**: Vincula un producto a un servicio de citas.
- **`destroy`**: Desvincula un producto de un servicio.

---

## Módulo: Consentimientos (Ecografías)

### `ConsentimientoPlantillasController.php`
Gestión de plantillas HTML versionadas de consentimiento informado para ecografías.
- **`index`**: Lista plantillas filtrando por módulo, búsqueda y vigencia.
- **`show`**: Retorna el detalle completo de una plantilla específica.
- **`store`**: Crea una nueva versión de plantilla de consentimiento (vinculada a un módulo y producto).
- **`update`**: Actualiza el contenido HTML de una plantilla existente.
- **`versions`**: Lista el historial de versiones de una plantilla por producto y módulo.
- **`setVigente`**: Marca una versión de plantilla como la vigente (desactiva las demás del mismo producto/módulo).
- **`destroy`**: Elimina una plantilla de consentimiento.
- **`previewPdf`**: Genera y transmite el PDF de previsualización de una plantilla.

---

## Módulo: Estudios

### `EstudioController.php`
Gestión centralizada del flujo y ciclo de vida de los estudios radiológicos complejos.
- **`index`**: Lista estudios aplicando filtros avanzados del negocio.
- **`store`**: Proceso atómico de registro: crea el estudio, el paciente (si no existe), productos y diagnósticos.
- **`listarPendientesMedico`**: Lista los estudios pendientes de interpretación asignados al médico en sesión.
- **`leerEstudio`**: Obtiene y unifica todos los datos necesarios para renderizar la pantalla de lectura médica.
- **`guardarTranscripcion`**: Finaliza el flujo de transcripción escrita y dispara la generación del reporte formal.
- **`listarPendientesTrascribir`**: Filtra los estudios que ya disponen de audio pero no han sido transcritos a texto.
- **`listarEstudiosAsignados`**: Muestra la carga de trabajo asignada a un especialista.
- **`buscarEstudioPorNumDocu`**: Buscador rápido de historial de estudios utilizando el documento del paciente.
- **`anularAsignacion`**: Revoca la asignación de un estudio a un médico devolviéndolo a la bolsa general.
- **`descargarPdfLectura`**: Transmite el flujo binario del PDF oficial de la lectura radiológica.
- **`notificarEmail`**: Despacha de forma manual o automática la notificación de resultados vía correo electrónico.

### `EstudioLockController.php`
Control de reservas (locks) de lectura y asignación para evitar edición concurrente.
> Los endpoints no requieren permisos adicionales — solo `auth`. La expiración del lock se configura con `lock_min` en `config_sedes`.
- **`tomar`**: Toma o renueva la reserva de un estudio para el usuario actual. Devuelve 409 si otro usuario lo tiene reservado.
  - Parámetros: `tipo` (`lectura|asignacion`), `estudio_producto_id` (si `tipo=lectura`), `study_iuid`, `study_pk`, `sede_id` (si `tipo=asignacion`).
- **`liberar`**: Libera la reserva activa del estudio si pertenece al usuario autenticado (operación idempotente).

### `PlantillaEstudioController.php`
Procesamiento de plantillas de texto complejas mediante servidores externos.
- **`editWord`**: Inyecta datos en plantillas `.docx` y delega la conversión a PDF a LibreOffice/unoconv.
- **`convertDocxToPdf`**: Envoltura ejecutable interna que procesa archivos mediante la utilidad de consola `unoconv`.

### `EstudioNotificacionController.php`
Gestión de canales y despacho de notificaciones salientes.
- **`listarEstudios`**: Lista los estudios completamente transcritos y listos para distribución.
- **`notificarEmail`**: Envía el informe clínico adjunto por correo electrónico.
- **`descargaImgDicom`**: Descarga y transmite imágenes médicas utilizando llamadas de protocolo WADO.
- **`enviarMensajeWhatsApp`**: Despacha notificaciones automáticas/recordatorios usando las APIs de Twilio WhatsApp.

### `EstudioAudioController.php`
Controlador de procesamiento multimedia del backend.
- **`uploadAudio`**: Recibe audios del dictado médico, valida e invoca FFmpeg para forzar conversión a MP3 de 64kbps en el disk local.
- **`listarAudiosPorEstudio`**: Lista las pistas de audio asociadas secuencialmente a un estudio radiológico.

### `EstudioDiagnosticoController.php`
- *Controlador scaffold sin implementación actual.*

### `EstudioProductoController.php`
Gestión fina de productos/procedimientos individuales dentro de una orden de estudio.
- **`index`**: Lista los ítems/procedimientos contenidos en un estudio específico.
- **`store`**: Añade de forma dinámica un nuevo procedimiento a un estudio ya creado.

### `EstudioSoporteHCController.php`
Gestión de documentos anexos e historias clínicas externas aportadas por el paciente.
- **`listarSoportesHistoriaClinicaPorPorEstudio`**: Recupera los metadatos de los archivos de soporte anexados al estudio.
- **`descargarSoporte`**: Descarga el archivo físico del soporte desde su disk correspondiente.

---

## Módulo: Dashboard

### `DashBoardController.php`
Métricas de rendimiento e indicadores en tiempo real de la plataforma.
- **`dashboard`**: Entrega estadísticas consolidadas adaptando los resultados según el rol del usuario (Admin vs Médico). Consume 12 vistas SQL dedicadas optimizadas con índices compuestos.

---

## Módulo: Reportes

### `ReporteProduccion.php`
Generación de reportes de producción y estudios en formato Excel.
- **`reporteProduccionDetallado`**: Genera el reporte detallado de producción por sede y rango de fechas en formato Excel. Requiere permiso `reportes.detallados|reportes.indicadores`.
- **`reporteEstudiosPAC`**: Genera el reporte de estudios tomados en PAC (Paciente Ambulatorio Certificado) por sede y rango de fechas en formato Excel. Requiere permiso `reportes.detallados|reportes.indicadores`.

---

## Módulo: Dcm4chee / PACS

### `StudyController.php`
Capa de abstracción e interoperabilidad directa con el servidor PACS.
- **`listarEstudios`**: Ejecuta consultas de lectura directa sobre las bases de datos de DCM4CHEE utilizando conexiones dinámicas inyectadas según la sede activa.

### `OhifVisorController.php`
Integración con el visor DICOM OHIF.
- **`index`**: Lista los estudios disponibles para visualización en OHIF.
- **`token`**: Genera y retorna el token de autenticación para el visor OHIF.

### `DicomBurnerController.php`
Control del proceso de quemado de CDs con imágenes DICOM.
- **`store`**: Inicia el proceso de quemado de un CD con los estudios seleccionados.
- **`index`**: Lista el historial de quemados con su estado de progreso.
- **`estado`**: Consulta el estado actual de un proceso de quemado en curso.

### `ImportarDicom.php`
Importación de estudios DICOM desde archivos externos al PACS.
- **`importar`**: Procesa y envía archivos DICOM al servidor Dcm4chee.

---

## Módulo: Interface

### `SAHIController.php`
Módulo core de interoperabilidad con el HIS hospitalario (SAHI) vía SQL Server.
- **`listarAtenciones`**: Consulta ingresos de pacientes directamente en las tablas del HIS. Incluye manejo defensivo de excepciones ante caídas de red retornando HTTP 503.
- **`listarProductosDiagnosticos`**: Recupera el desglose de productos y diagnósticos facturados en una orden externa con captura de errores.
- **`buscarEpsPlanContrato`**: Extrae datos contractuales y de aseguramiento del paciente desde el HIS.
- **`listarProductosAtencion`**: Cruza la información de facturación con movimientos internos para validar el estado de los servicios.
- **`verificarConexion`**: (2026-10-21) Verifica en tiempo real la conectividad y latencia TCP/PDO con el servidor SQL Server retornando métricas y diagnóstico de red.

### `SAHIEcografiasController.php`
Capa de integración HIS especializada exclusivamente para el flujo de ecografías.
- **`listarProductosAtencion`**: Recupera del HIS los procedimientos ecográficos facturados y aptos para ser admitidos.

### `SAHIEcografiasEnviarController.php`
Envío de resultados de ecografías hacia el HIS externo.
- **`enviar`**: Persiste el resultado de una ecografía en las tablas de historia clínica del HIS (SAHI) vía SQL Server.
- **Reconciliación Automática**: `EcografiasSahiReconcileCommand` reconcilia registros en estado `ENVIANDO` con pre-chequeo de conectividad a SQL Server para evitar falsos errores durante desconexiones o mantenimientos.

### `SAHITranscibirController.php`
Despacho y sincronización de informes clínicos hacia la Historia Clínica Electrónica externa.
- **`enviarEstudio`**: Ejecuta la transacción atómica de inserción de resultados finales directamente dentro de las tablas relacionales de la historia clínica de SAHI (`DB::connection('sqlsrv')->transaction()`), garantizando integridad en caso de microcortes de red.

### Comandos de Diagnóstico SAHI
- **`php artisan sahi:test`**: (2026-10-21) Herramienta de auditoría integral de interoperabilidad. Prueba socket TCP (puerto 1433), autenticación PDO, latencia de consulta y validación de lectura sobre las 5 tablas maestras (`admAtencion`, `admCliente`, `proProducto`, `HceEsquema`, `sisTabla`).

### `TeleRadiologiaController.php`
Integración pasarela con plataformas de lectura externa vía webhook.
- **`listarEstudios`**: Lista los estudios disponibles para envío a la plataforma de tele-radiología externa.
- **`enviarEstudio`**: Endpoint reservado — retorna 501 (no implementado). El envío real se hace a través de `enviarOrdenTeleRadiologia`.
- **`enviarOrdenTeleRadiologia`**: Envía una orden de lectura a la API externa de tele-radiología con todos los datos del estudio y el paciente.
- **`recibirLectura`**: Webhook receptor — recibe la lectura transcrita desde la plataforma externa, la decodifica (ISO-8859-1 → UTF-8) y la persiste en el estudio correspondiente.

---

## Módulo: Contratación

### `ContratoController.php`
API REST completa del módulo de gestión de contratos médico-institución.
- **`index`**: Lista todos los contratos con filtros por sede, estado y búsqueda libre. Retorna paginación con sede, productos y médicos incluidos.
- **`store`**: Crea un nuevo contrato decodificando el payload `registro` del formulario.
- **`show`**: Muestra un contrato específico con todas sus relaciones (sede, productos, médicos).
- **`update`**: Actualiza los datos de un contrato existente.
- **`destroy`**: Elimina un contrato junto con sus médicos y productos vinculados.
- **`toggleEstado`**: Cambia el estado activo/inactivo de un contrato.
- **`syncMedicos`**: Aplica el diff del detalle de médicos-producto del contrato (agrega, actualiza o retira según `retirar_faltantes`).
- **`previewMedicos`**: Previsualiza el cambio del detalle de médicos sin aplicarlo — retorna el diff calculado para confirmación del usuario.
- **`importarMedicos`**: Importa el detalle de médicos-producto desde un archivo Excel (formato con Contrato ID en celda C2). Soporta flujo de confirmación para CUPS faltantes (`crear_faltantes`).
- **`exportPlantillaDetalle`**: Descarga la plantilla Excel pre-poblada con los CUPS del contrato para diligenciar tarifas por médico.
- **`cobertura`**: Informa qué productos exigibles del catálogo no tienen tarifa cargada en el contrato (totales y lista de faltantes).

---

## Módulo: Liquidación

### `LiquidacionController.php`
API de liquidación de pagos a médicos por sede y período.
- **`calcular`**: Calcula la liquidación por sede y rango de fechas, delegando a `LiquidacionService::calcular()`. Devuelve líneas liquidables, exclusiones enriquecidas y totales por médico. Requiere permiso `liquidacion.index`.
- **`exportar`**: Descarga la liquidación calculada en formato Excel (`.xlsx`) con diseño institucional Salud Tolima, formato de moneda y totales. Requiere permiso `liquidacion.exportar`.
- **`productividad`**: Reporte de productividad por médico para un corte mensual (`periodo` en formato `YYYY-MM`). Lee los datos congelados del período. Requiere permiso `liquidacion.index`.
- **`exportarProductividad`**: Descarga el reporte de productividad mensual por médico a Excel (`.xlsx`) con subtotales y detalle de lecturas. Requiere permiso `liquidacion.exportar`.

---

## Módulo: Help Desk

### `HelpDeskTicketsController.php`
Gestión completa del ciclo de vida de tickets de soporte.
- **`index`**: Lista tickets con filtros por scope (`mine|all`), estado, categoría, prioridad, sede, rango de fechas y búsqueda libre. Retorna paginación de 50 por página.
- **`store`**: Crea un nuevo ticket con los datos del formulario validado.
- **`show`**: Muestra el detalle de un ticket con todas sus relaciones (historial, adjuntos, comentarios). Usa Route Model Binding.
- **`update`**: Actualiza título, descripción, categoría o prioridad de un ticket existente.
- **`destroy`**: Elimina un ticket (solo permitido en estado Abierto o Cerrado y para administradores).
- **`comment`**: Agrega un comentario a un ticket y notifica a las partes involucradas.
- **`reassign`**: Reasigna un ticket a otro usuario de soporte registrando el cambio en el historial.
- **`changeStatus`**: Cambia el estado del ticket validando las transiciones permitidas y notificando a las partes.

### `HelpDeskCategoriasController.php`
Gestión del catálogo de categorías de tickets.
- **`index`**: Lista todas las categorías ordenadas por nombre.
- **`store`**: Crea una nueva categoría.
- **`show`**: Muestra el detalle de una categoría.
- **`update`**: Actualiza nombre y estado de una categoría.
- **`destroy`**: Desactiva (eliminación lógica) una categoría.

### `HelpDeskEstadosController.php`
Catálogo de estados de tickets (solo lectura — los estados son fijos del sistema).
- **`index`**: Lista todos los estados ordenados por orden y nombre.
- **`show`**: Muestra el detalle de un estado.
- **`store`** / **`update`** / **`destroy`**: Retornan error 405 — operación no permitida (estados fijos).

### `HelpDeskPrioridadesController.php`
Gestión del catálogo de prioridades de tickets.
- **`index`**: Lista todas las prioridades ordenadas por orden y nombre.
- **`store`**: Crea una nueva prioridad con nombre, orden y estado.
- **`show`**: Muestra el detalle de una prioridad.
- **`update`**: Actualiza los datos de una prioridad existente.
- **`destroy`**: Desactiva (eliminación lógica) una prioridad.

### `HelpDeskAdjuntosController.php`
Gestión de archivos adjuntos de tickets.
- **`upload`**: Sube uno o varios archivos asociados a un ticket, genera el historial de la acción y notifica a las partes.
- **`download`**: Descarga un archivo adjunto previa validación de permisos sobre el ticket. Retorna 404 si el archivo físico no existe.
- **`destroy`**: Elimina un adjunto y su archivo físico del almacenamiento.

### `HelpDeskNotificacionesController.php`
Gestión de notificaciones internas del sistema de help desk.
- **`index`**: Lista las notificaciones del usuario autenticado con paginación y filtro opcional de no leídas (`unread_only`).
- **`markRead`**: Marca como leída una notificación específica por su ID.
- **`markAllRead`**: Marca como leídas todas las notificaciones pendientes del usuario autenticado.

### `HelpDeskReportesController.php`
Generación de reportes analíticos del help desk.
- **`indicadores`**: Genera indicadores agregados de tickets (totales, por estado, por categoría, tiempos promedio) según los filtros validados.
- **`detallado`**: Genera el listado detallado de tickets para el período y filtros indicados (límite: 500 registros por defecto).
- **`exportExcel`**: Exporta el reporte detallado de tickets a Excel (límite: 5000 registros). Retorna `BinaryFileResponse`.
- **`exportPdf`**: Exporta los indicadores del reporte a PDF. Retorna `BinaryFileResponse`.

### `HelpDeskUsuariosController.php`
Consulta de usuarios disponibles para asignación de tickets.
- **`asignables`**: Lista los usuarios que pueden ser asignados como técnicos de soporte, con sus roles.

---

## Módulo: Seguridad

### `UserController.php`
Gestión completa de usuarios de la plataforma.
- **`index`**: Lista todos los usuarios del sistema. Requiere permiso `seguridad.usuarios.index`.
- **`show`**: Muestra el detalle de un usuario por ID.
- **`store`**: Crea un nuevo usuario con validación de política de contraseñas (8+ chars, mayúscula, minúscula, número, símbolo), firma y avatar. Requiere permiso `seguridad.usuarios.create`.
- **`update`**: Actualiza los datos de un usuario, incluyendo archivos opcionales de firma y avatar. Requiere permiso `seguridad.usuarios.edit`.
- **`destroy`**: Elimina un usuario del sistema. Requiere permiso `seguridad.usuarios.destroy`.
- **`estadoUsuario`**: Activa o desactiva un usuario (toggle). Requiere permiso `seguridad.usuarios.edit`.
- **`actualizarPerfil`**: Permite al usuario autenticado actualizar su propio perfil (nombre, email, contraseña, avatar, firma). Valida que el ID de la ruta coincida con el usuario en sesión.
- **`listarPermisosUsuario`**: Retorna el mapa completo de permisos asignados a un usuario.
- **`listarUsuarios`**: Lista usuarios filtrados por rol y módulo (ej. médicos del módulo de estudios). Solo requiere `auth`.
- **`usuariosPorModulo`**: Lista usuarios asociados a un módulo específico. Solo requiere `auth` (usado por selectores globales del header).

### `RolController.php`
Gestión de roles y sus permisos asociados.
- **`index`**: Lista todos los roles con el catálogo completo de permisos disponibles. Requiere permiso `seguridad.roles.index`.
- **`store`**: Crea un nuevo rol con nombre y lista de permisos iniciales. Requiere permiso `seguridad.roles.create`.
- **`show`**: Retorna el rol, el catálogo de permisos y los permisos actualmente asignados al rol. Requiere permiso `seguridad.roles.show`.
- **`update`**: Actualiza el nombre y los permisos de un rol mediante el payload `registro.permisosRol[]`. Requiere permiso `seguridad.roles.edit`.
- **`destroy`**: Elimina un rol del sistema. Requiere permiso `seguridad.roles.destroy`.
- **`estadoRol`**: Activa o desactiva un rol (toggle de estado). Requiere permiso `seguridad.roles.edit`.

### `AuditLogController.php`
Consulta del log de auditoría de operaciones sensibles.
- **`index`**: Lista registros de auditoría con filtros por evento, usuario, búsqueda libre y rango de fechas. Paginación de 50 por página. Requiere permiso `seguridad.auditoria.index`.
- **`eventos`**: Retorna el catálogo de tipos de eventos registrables para poblar el filtro del frontend.

### `BackupMonitorController.php`
Controlador para el panel de monitoreo y gestión en tiempo real de copias de seguridad y sincronización de sedes hospitalarias.
- **`estado`**: Retorna métricas consolidadas de almacenamiento en disco, estado de backups centrales (MySQL y storage) y semáforo de sincronización de imágenes DCM por sede. Requiere permiso `seguridad.backups.index`.
- **`ejecutarBackupDb`**: Dispara la ejecución manual e inmediata de un respaldo de la base de datos principal MySQL con política de retención automática. Requiere permiso `seguridad.backups.ejecutar`.

---

## Módulo: Autenticación

### `LoginController.php`
Gestión del flujo de autenticación de usuarios.
- **`login`**: Autentica al usuario con email/contraseña, valida credenciales, genera token Sanctum y retorna datos del usuario con sus permisos y módulos accesibles.
- **`logout`**: Revoca el token Sanctum activo y cierra la sesión del usuario.

### `PasswordChangeController.php`
Gestión del cambio obligatorio de contraseña.
- **`show`**: Muestra el formulario de cambio de contraseña (cuando es obligatorio por política de seguridad).
- **`update`**: Procesa el cambio de contraseña validando la contraseña actual y aplicando las reglas de la política de seguridad.

### `ResetPasswordController.php`
Gestión del flujo de restablecimiento de contraseña olvidada.
- **`showResetForm`**: Muestra el formulario de restablecimiento con validación del token.
- **`reset`**: Procesa el restablecimiento de contraseña usando el token de correo electrónico.

### `VerificationController.php`
Controlador de verificación de email (scaffolding de Laravel UI).

---

## Módulo: Telegram & Notificaciones

### `TelegramWebhookController.php`
Receptor oficial de eventos y mensajes enviados por Telegram mediante Webhook.
- **`webhook`**: Valida la cabecera `X-Telegram-Bot-Api-Secret-Token`, despacha el evento al procesador `TelegramBotHandlerService` y confirma a Telegram con HTTP 200 `{"ok": true}`.

---

## Módulo: Archivos / Utilidades

### `FileAccessController.php`
Control de acceso a archivos protegidos del sistema.
- **`serve`**: Sirve archivos protegidos (firmas, audios, adjuntos) tras validar que el usuario autenticado tiene permiso para acceder al recurso solicitado.

### `HomeController.php`
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

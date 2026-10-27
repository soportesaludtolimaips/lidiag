# Informe Consolidado de Actualizaciones y Mejoras del Sistema LIDiag

---

## 1. Agendamiento, Citas y Comunicación con el Paciente (WhatsApp)

### Mejora: Notificaciones Automatizadas de Recordatorio de Citas
**Descripción:** Envío programado de mensajes por WhatsApp a los pacientes 24 horas y 2 horas antes de su cita para ecografía o procedimiento, reduciendo significativamente la tasa de inasistencia.

### Mejora: Entrega Digital de Instrucciones y Preparación Médica
**Descripción:** Respuestas automáticas con los requerimientos específicos de preparación previa (ayuno, retención de líquidos, preparación intestinal) según el procedimiento agendado.

### Mejora: Bitácora de Conversaciones Integrada en la Cita
**Descripción:** Registro y visualización del historial de mensajes intercambiados con el paciente dentro del módulo de citas para resolver aclaraciones o auditorías de servicio al cliente.

### Mejora: Blindaje de Alta Disponibilidad en el Polling de Notificaciones de Citas
**Descripción:** Fortalecimiento defensivo del canal de notificaciones en tiempo real del sistema de agendamiento (`AgendaCitaService` y `AgendaCitaController`), blindando las consultas periódicas de cabecera contra caídas de sesión, estados nulos o pérdidas momentáneas de conectividad, asegurando estabilidad continua de la interfaz web institucional.

---

## 2. Admisión de Pacientes y Lista de Trabajo Médica (Modality Worklist DICOM)

### Mejora: Modality Worklist (MWL DICOM) conectado a consolas de Rayos X, TAC y Ecografía
**Descripción:** Integración directa entre el sistema de admisión y las consolas de adquisición de los equipos médicos hospitalarios. Al registrar un paciente en ventanilla, sus datos y la orden del examen se envían automáticamente a la consola del tomógrafo o equipo de Rayos X a través del estándar DICOM (`mwl_item`). Esto erradica por completo la necesidad de que el tecnólogo digite manualmente el nombre, cédula o fecha de nacimiento del paciente, eliminando el riesgo de errores en la rotulación de estudios.

### Mejora: Codificador Binario DICOM Nativo (`DicomDatasetBuilder`)
**Descripción:** Motor interno desarrollado para generar paquetes binarios DICOM conformes a la norma internacional (Little Endian Explicit VR). Garantiza compatibilidad universal con equipos de cualquier fabricante (General Electric, Siemens, Philips) y con el servidor central PACS Dcm4chee sin depender de librerías externas o puentes lentos.

### Mejora: Enrutamiento Multisede de Órdenes con Identificador Único
**Descripción:** Direccionamiento inteligente de las órdenes de examen según la sede hospitalaria activa donde se encuentra el paciente, generando números de acceso secuenciales y trazables (`RX-YYYYMMDD-####`).

### Mejora: Cancelación y Retiro Seguro de Órdenes en Espera
**Descripción:** Función en la interfaz de admisión que permite al personal de recepción o enfermería cancelar o retirar de inmediato una orden enviada a la consola en caso de inasistencia o reprogramación del paciente, actualizando la lista de trabajo del equipo médico de manera instantánea.

### Mejora: Módulo de Admisión con Filtros Persistentes y Atajos Clínicos
**Descripción:** Pantalla de admisión (`admisionComponent.vue`) desarrollada en Vue 3 con tecnología de persistencia de filtros en memoria, indicador en tiempo real de *"En espera de Rayos X"*, Skeleton Loaders para tiempos de carga fluidos y soporte para Modo Claro / Modo Oscuro.

---

## 3. Estación de Lectura Radiológica, Transcripción y Diagnóstico Médico

### Mejora: Bloqueo Inteligente de Concurrencia Médica (`Estudio Locks`)
**Descripción:** Sistema de bloqueo exclusivo automático cuando un médico radiólogo abre un estudio para elaborar su informe. Si otro médico o transcriptor intenta ingresar al mismo examen simultáneamente, el sistema notifica de inmediato quién lo tiene abierto y bloquea la edición cruzada (`HTTP 409 Conflict`), erradicando sobreescrituras accidentales de conceptos médicos. Al terminar la lectura o cerrar la estación, el bloqueo se libera automáticamente.

### Mejora: Dictado por Voz Clínico con Inteligencia Artificial (Whisper + FastAPI)
**Descripción:** Integración de reconocimiento de voz de grado médico que permite al radiólogo dictar sus informes directamente en la estación de trabajo. El audio se procesa y comprime en el navegador a 64 kbps (FFmpeg) y se transcribe localmente a gran velocidad mediante el modelo Whisper en un microservicio dedicado, insertando el texto en el informe clínico en segundos sin depender de internet.

### Mejora: Estandarización de Membretes, Firmas Digitales y Registros Médicos en PDF
**Descripción:** Generador automatizado de reportes médicos en PDF que incrusta la firma digitalizada, registro médico, especialidad y membrete institucional de la sede, garantizando validez legal y presentación corporativa homogénea en la entrega de resultados.

### Mejora: Consentimientos Informados Digitales con Firma Biométrica
**Descripción:** Módulo digital para procedimientos especiales y ecografías intervencionistas (biopsias) que permite capturar la firma táctil/biométrica del paciente o acudiente en pantallas táctiles o tabletas, generando el documento legal en PDF cifrado e integrándolo automáticamente al expediente del estudio.

### Mejora: Flexibilidad Operativa en la Asignación de Estudios (Correo Electrónico Opcional)
**Descripción:** Se optimizó el formulario de asignación y enrutamiento de estudios (`AsignarEstudioComponent.vue`), haciendo voluntario el registro de correo electrónico (`email_reportar`). Esto agiliza la admisión y despacho inmediato de pacientes de urgencias, zonas rurales o personas de la tercera edad que no disponen de cuenta de correo propia, manteniendo la validación de formato solo en caso de ser diligenciado.

### Mejora: Conversión Resiliente y Descarga Directa de Informes Médicos en PDF
**Descripción:** Optimización del motor de conversión de documentos Word a PDF (`PdfConverterHelper` y `PlantillaEstudioService`) ampliando la ventana de espera para generación de reportes complejos con imágenes y firmas, integrando saneamiento preventivo de nombres de archivos y protegiendo el frontend contra aperturas de enlaces fallidos o rutas indefinidas en el módulo de notificación de resultados.

---

## 4. Control de Radiación y Dosimetría del Paciente (Resolución 482 de 2018)

### Mejora: Módulo de Dosimetría y Vigilancia Radiológica del Paciente
**Descripción:** Sistema para capturar, calcular y vigilar la dosis de radiación ionizante recibida por cada paciente en exámenes de Rayos X, Tomografía y Fluoroscopia, dando estricto cumplimiento a la Resolución 482 de 2018 del Ministerio de Salud y Protección Social.

### Mejora: Parser de Reportes Estructurados de Dosis DICOM (RDSR)
**Descripción:** Lector automático que extrae la información dosimétrica directa emitida por tomógrafos y angiógrafos modernos a través de objetos DICOM RDSR (Radiation Dose Structured Report), almacenando producto dosis-longitud (DLP), dosis en superficie y tiempo de irradiación.

### Mejora: Estimación Matemática de Dosis (DAP) bajo Metodología IAEA TRS-457
**Descripción:** Algoritmo biofísico que calcula de forma automática el Producto Dosis-Área (DAP en $mGy \cdot cm^2$) para equipos de Rayos X digitales que no poseen cámara de ionización física en el colimador, utilizando los parámetros de exposición del disparo (kVp, mAs, distancia foco-receptor y filtración añadida).

### Mejora: Clasificación Anatómica RadLex y Detección de Deriva de Dosis
**Descripción:** Mapeo de procedimientos radiológicos a la terminología estándar internacional RadLex. El sistema detecta desviaciones o "deriva de dosis" cuando un examen supera los Niveles de Referencia para Diagnóstico (NRD), emitiendo alertas para el Oficial de Protección Radiológica.

### Mejora: Anonimización Criptográfica de Pacientes con HMAC-SHA256
**Descripción:** Protección de la identidad del paciente en la telemetría de radiación mediante generación de identificadores seudonimizados con algoritmo HMAC-SHA256 y semilla criptográfica aleatoria de 32 bytes por sede hospitalaria, impidiendo ataques de tablas arcoíris (*anti-rainbow table*) y garantizando total privacidad médica.

### Mejora: Cola Persistente Edge en SQLite (`cola_dosimetria.db`)
**Descripción:** Base de datos embebida en los servidores locales de cada hospital que almacena temporalmente las mediciones de radiación y las sincroniza con el servidor central. Cuenta con reintentos automáticos y garantiza cero pérdida de datos ante cortes eléctricos o caídas prolongadas del enlace de internet.

### Mejora: Reporte Gerencial y Multi-Hoja en Excel para Secretarías de Salud
**Descripción:** Generador de reportes consolidados en Excel con membrete institucional, gráficos estadísticos de barras y tortas por modalidad/sede, indicadores de cumplimiento NRD y trazabilidad histórica para auditorías del Ministerio de Salud o entes territoriales.

---

## 5. Entrega de Resultados, Medios Diagnósticos (CD/DVD/ZIP) y Portal Web

### Mejora: Quemado Asistido de CD / DVD con Detección Automática de Unidades
**Descripción:** Módulo de grabación de medios físicos que sondea en tiempo real las unidades ópticas conectadas en la red de la clínica. Permite al personal de ventanilla quemar discos para pacientes sin requerir software externo como Nero o ImgBurn.

### Mejora: Visor DICOM Ligero Embebido en cada Disco (MicroDicom Portable)
**Descripción:** Cada disco óptico quemado incluye de forma transparente un visor DICOM portable preconfigurado. El paciente o médico tratante puede insertar el disco en cualquier computadora con Windows y visualizar de inmediato las imágenes radiológicas sin necesidad de instalar programas adicionales.

### Mejora: Grabación Multi-Estudio en un Solo Disco
**Descripción:** Capacidad de consolidar varios estudios de diferentes fechas o modalidades (ej. RX de Tórax y TAC de Cráneo) de un mismo paciente en un único CD o DVD, optimizando el consumo de insumos físicos y reduciendo tiempos de espera en ventanilla.

### Mejora: Descarga Digital Directa de Imágenes en Archivo Comprimido (.ZIP)
**Descripción:** Opción de descarga directa de la serie completa de imágenes DICOM del estudio en formato `.zip` optimizado. Permite entregar los estudios de forma digital inmediata para pacientes ambulatorios o remitidos a otras instituciones, con archivos íntegros compatibles con visores avanzados (Horos, OsiriX, RadiAnt).

### Mejora: Bitácora Forense de Grabación de Medios (`cd_burn_logs`)
**Descripción:** Registro cronológico en base de datos de cada quemado efectuado, almacenando fecha y hora exacta, usuario responsable, paciente, modalidad, unidad de grabación utilizada y resultado del proceso para auditorías de insumos y entrega.

### Mejora: Comando de Transferencia Segura de Informes al Portal (`lidiag:enviar-paciente`)
**Descripción:** Comando automatizado de alta velocidad que convierte los estudios transcritos a PDF mediante LibreOffice Portable aislado y los envía al portal externo de pacientes (`lidiag-reportes`), con telemetría de rendimiento en vivo, cálculo de tiempo estimado (ETA) y barra de avance.

### Mejora: Autenticación Criptográfica HMAC-SHA256 con Claves Dinámicas
**Descripción:** Enlace seguro entre LIDiag y el portal web mediante firmas digitales HMAC-SHA256 en cada petición, impidiendo alteraciones de datos o accesos no autorizados a los resultados de los pacientes en la nube.

### Mejora: Mecanismo de Reintento Masivo y Bitácora de Errores en TXT
**Descripción:** Sistema de resiliencia que detecta estudios fallidos por cortes de conexión con el portal, guardándolos en un archivo `.txt` para ejecución asistida con la bandera `--reintentar`, garantizando que ningún paciente se quede sin su resultado digital.

---

## 6. Interoperabilidad con el Sistema Hospitalario Central (HIS / SAHI)

### Mejora: Transaccionalidad Atómica en el Despacho de Estudios
**Descripción:** Se agrupó la secuencia de 9 operaciones de inserción y actualización de consecutivos en la base de datos de SAHI (`172.16.5.6:1433`) dentro de una transacción atómica estricta (`DB::transaction`). Si ocurre un fallo eléctrico o pérdida de conexión a mitad del despacho, todas las operaciones se revierten por completo, evitando historiales clínicos incompletos, registros huérfanos o contadores desperdiciados.

### Mejora: Timeouts Rápidos de 5 Segundos contra Caídas de Red
**Descripción:** Ajuste de los tiempos límite de conexión y consulta a SQL Server a un máximo de 5 segundos. Cuando se presentan microcortes en la red hospitalaria, el sistema responde de inmediato con una alerta limpia de servicio no disponible (`HTTP 503`) en lugar de congelar los navegadores del personal durante 30 a 60 segundos.

### Mejora: Manejo Defensivo y Centralizado de Errores de Conexión
**Descripción:** Todos los métodos de consulta hacia SAHI (búsqueda de atenciones, productos diagnósticos, contratos de EPS y movimientos) fueron blindados con captura de excepciones y registro de bitácora contextual, evitando errores genéricos de pantalla en blanco para los usuarios.

### Mejora: Comando de Auditoría y Diagnóstico de Red (`php artisan sahi:test`)
**Descripción:** Herramienta técnica en consola que permite al equipo de TI verificar en cualquier momento la salud de la integración con SAHI. Comprueba apertura de socket TCP (puerto 1433), latencia de respuesta, autenticación y acceso de lectura sobre las 5 tablas maestras (`admAtencion` con más de 7 millones de registros, `admCliente`, `proProducto`, `HceEsquema` y `sisTabla`).

### Mejora: Reconciliación Preventiva de Envíos de Ecografías
**Descripción:** El comando automático de reconciliación de ecografías verifica la disponibilidad del enlace antes de operar. Si el servidor central está apagado por mantenimiento preventivo, el comando aborta limpiamente sin marcar falsos estados de error en los exámenes de los pacientes.

---

## 7. Gestión Financiera, Liquidación y Productividad Médica

### Mejora: Auditoría y Detalle Estructurado de Exclusiones Tarifarias
**Descripción:** Identificación precisa de procedimientos que no pudieron liquidarse por inconsistencias contractuales. El sistema muestra un desglose claro con número de documento, nombre del paciente, especialista, código CUPS y el motivo exacto del rechazo de tarifa.

### Mejora: Modal Interactivo de Corrección para Facturación y Contabilidad
**Descripción:** Ventana interactiva con búsqueda en tiempo real por paciente, médico o código CUPS para que el equipo financiero identifique qué tarifas hacen falta en los contratos institucionales antes de cerrar el período.

### Mejora: Exportación Institucional a Excel con Membrete y Fórmulas
**Descripción:** Descarga de reportes de liquidación y productividad médica en formato Excel con membrete oficial de Salud Tolima, formato de moneda `$#,##0`, subtotales automáticos por especialista y consolidado general del total liquidado con conteo de lecturas efectuadas.

---

## 8. Inteligencia de Negocio, Reportes e Indicadores de Gestión Hospitalaria

### Mejora: Rediseño del Centro de Reportes por Categorías Temáticas
**Descripción:** Reorganización de la pantalla de reportes en tarjetas temáticas visuales con iconos modernos (AdminWrap), descripciones claras y modales de parametrización rápida por sede, modalidad y fechas.

### Mejora: Reporte Ejecutivo de Salud y Telemetría de Agentes Hospitalarios
**Descripción:** Módulo con exportador a Excel (`ReporteAgentesTelemetriaExport.php`) que consolida el estado operativo de todas las sedes hospitalarias: fecha y hora de la última imagen DICOM capturada, versión del software local, estado de conexión y espacio disponible en disco.

---

## 9. Monitoreo Gerencial y Supervisión Asistencial vía Telegram (`@salud_tolima_bot`)

### Mejora: Menú Táctil Interactivo para Consulta de Producción
**Descripción:** Asistente interactivo en Telegram para directores y coordinadores que permite consultar en 3 toques la producción de estudios por sede, modalidad y rango de fechas directamente desde el celular.

### Mejora: Conciliación en Vivo: Estudios Facturados vs. Imágenes Tomadas en PACS
**Descripción:** Cruce automático entre los estudios registrados administrativamente en LIDiag y los archivos de imagen DICOM efectivamente adquiridos en los equipos de RX/TAC de cada hospital, detectando discrepancias de facturación o estudios tomados sin orden.

### Mejora: Medición del Tiempo de Respuesta Clínico (TAT)
**Descripción:** Cálculo automático del tiempo transcurrido desde la toma del examen hasta la firma del informe por el radiólogo, clasificado con semáforos de oportunidad clínica (`🟢 Óptimo <12h`, `🟡 Aceptable <24h`, `🔴 Crítico >24h`).

### Mejora: Alerta Temprana de Estudios Represados (`/retrasados`)
**Descripción:** Notificación inmediata que lista los exámenes que superan las 24 horas sin lectura médica, priorizando casos de urgencias y hospitalización.

### Mejora: Resumen Matutino Automático (Daily Digest)
**Descripción:** Reporte programado enviado automáticamente todos los días a las 07:30 AM con el balance de producción del día anterior y la carga de trabajo pendiente para la jornada que inicia.

### Mejora: Monitor Continuo de Discos Duros PACS
**Descripción:** Monitoreo 24/7 del espacio de almacenamiento en los servidores PACS locales, disparando alertas automáticas a Telegram si el disco baja del 15% (advertencia) o del 5% (alerta crítica de saturación).

---

## 10. Continuidad del Negocio, Respaldos y Resiliencia de Sedes Hospitalarias

### Mejora: Dashboard en Tiempo Real de Copias de Seguridad y Red de Sedes
**Descripción:** Panel de supervisión en el módulo de Seguridad que muestra en tiempo real la salud de los respaldos de toda la red institucional: espacio libre y ocupado en el almacenamiento central y en el servidor NAS / QNAP, fecha del último backup de base de datos MySQL y copias de archivos de storage.

### Mejora: Semáforo Asistencial de Sincronización DCM por Sede
**Descripción:** Matriz de control que evalúa la frecuencia de respaldo de imágenes de cada sede hospitalaria con código de colores claro:
* 🟢 **Al día (< 24 horas):** Respaldo de base de datos e imágenes transferido correctamente.
* 🟡 **Advertencia (24 a 48 horas):** Demora detectada para intervención preventiva.
* 🔴 **Crítico (> 48 horas):** Alerta prioritaria para el equipo de sistemas.

### Mejora: Botón de Disparo Inmediato de Respaldo de Base de Datos
**Descripción:** Función en la interfaz gráfica que permite al administrador generar un backup completo de la base de datos MySQL en un clic, con empaquetado ZIP de alta velocidad, cálculo de huella criptográfica SHA-256 de integridad y aplicación automática de políticas de retención.

### Mejora: Agente Autónomo de Sedes Hospitalarias (`agente_backups_dcm`)
**Descripción:** Paquete de software autónomo desplegado en los servidores de cada hospital que combina:
1. Respaldo nocturno programado de la base de datos local con verificación CRC32.
2. Sincronización continua e incremental de imágenes `.dcm` hacia el QNAP central con motor multihilo `robocopy` seguro (no borra archivos en destino).
3. Instalador de 1 clic como servicio desatendido de Windows (`SYSTEM`).

### Mejora: Restauración Cronológica Descendente de Imágenes PACS
**Descripción:** Rutina automatizada para recuperar imágenes históricas desde el almacenamiento secundario o QNAP hacia el servidor PACS local de forma cronológica inversa (desde lo más reciente a lo más antiguo), permitiendo a los médicos visualizar estudios prioritarios mientras se completa la restauración de fondo.

---

## 11. Seguridad, Control de Acceso y Cumplimiento Normativo (ISO 27001 / Habeas Data)

### Mejora: Validación Obligatoria de Confirmación de Contraseña
**Descripción:** En todos los formularios de cambio y actualización de credenciales se implementó la regla estricta de confirmación idéntica (`password_confirmation`). El sistema valida que el usuario digite la nueva contraseña dos veces y que coincidan byte a byte, alertando con mensajes claros en español si existe alguna discrepancia antes de procesar la solicitud, impidiendo que el usuario quede bloqueado por errores tipográficos accidentales.

### Mejora: Política de Contraseñas Robustas de Grado Clínico (`PasswordRules`)
**Descripción:** Motor de validación que exige el cumplimiento estricto de estándares internacionales de ciberseguridad: mínimo 8 caracteres, combinación obligatoria de letras mayúsculas, letras minúsculas, números y caracteres especiales (`!@#$%^&*`). Además, valida obligatoriamente la contraseña actual del usuario (`current_password`) y prohíbe reutilizar la misma clave activa.

### Mejora: Middleware de Forzado de Cambio de Contraseña (`ForcePasswordChange`)
**Descripción:** Mecanismo de seguridad que intercepta automáticamente a cualquier usuario con contraseña por defecto asignada por sistemas, o cuya clave haya expirado según la política institucional (`password_changed_at`). El middleware restringe el acceso a cualquier módulo asistencial o administrativo hasta que el colaborador defina una contraseña nueva y personal.

### Mejora: Redirección Defensiva Post-Cambio de Contraseña
**Descripción:** Blindaje en el flujo de cambio de clave (`PasswordChangeController`) que descarta redirecciones circulares o hacia la pantalla de autenticación (`login`). Una vez completado exitosamente el cambio de contraseña, el usuario es redirigido de manera fluida y determinista al panel principal (`dashboard`) con su sesión renovada, sin ser expulsado del sistema.

### Mejora: Módulo de Perfil de Usuario con Actualización Autónoma de Credenciales
**Descripción:** Interfaz renovada dentro del perfil del usuario (`usuarioPerfil.vue`) que permite a médicos, especialistas y personal asistencial actualizar sus datos personales y renovar su contraseña de forma voluntaria en cualquier momento, con validación de clave actual, nueva clave, confirmación y notificaciones de éxito en pantalla (*Toast*).

### Mejora: Saneamiento y Auditoría Total de Permisos Spatie (`permissions:audit`)
**Descripción:** Normalización completa de la matriz de permisos de seguridad en base de datos. Se eliminaron inconsistencias en las rutas y se aseguró que cada rol (Administrador, Radiólogo, Transcriptor, Facturación, Recepción) tenga acceso exclusivo y verificado a sus funciones correspondientes, alcanzando un estado de **0 permisos faltantes** en toda la plataforma.

### Mejora: Homologación de Catálogos Clínicos y Selectores para Médicos y Asistenciales
**Descripción:** Desbloqueo y apertura estandarizada de catálogos maestros de consulta (diagnósticos CIE-10, productos diagnósticos, prioridades clínicas, EPS/administradoras y listados de médicos especialistas) para todo el personal asistencial y de transcripción debidamente autenticado en la plataforma. Esto garantiza fluidez en la carga de selectores sin presentar bloqueos innecesarios (`HTTP 403 Forbidden`) durante la atención clínica y la lectura diagnóstica, manteniendo protegidas al 100% las funciones administrativas de creación o modificación.

### Mejora: Cifrado en Reposo de Información Sensible de Pacientes (*Encryption at Rest*)
**Descripción:** Implementación de cifrado criptográfico transparente (AES-256) en la base de datos para datos personales, números de contacto y soportes clínicos, garantizando el cumplimiento de la normativa de protección de datos de salud (Habeas Data).

### Mejora: Cierre Determinista y Expiración de Sesiones Inactivas (*Session Timeout*)
**Descripción:** Control automático de expiración de sesiones por inactividad. Si una estación de trabajo en salas de lectura radiológica o módulos de recepción permanece desatendida, el sistema cierra la sesión automáticamente para evitar accesos no autorizados al historial clínico.

### Mejora: Protección contra Ataques de Fuerza Bruta y Limitación de Peticiones (*Rate Limiting*)
**Descripción:** Blindaje en las rutas de inicio de sesión y endpoints críticos que limita el número máximo de intentos fallidos por minuto por dirección IP, bloqueando de manera automática intentos automatizados de hackeo o adivinación de contraseñas.

### Mejora: Protección contra Ejecución Remota de Código y Validación de Archivos
**Descripción:** Implementación de `FileAccessController` y el middleware `ProtectFiles` que validan el tipo MIME real, tamaño y extensión de cualquier archivo subido al servidor (audios, soportes clínicos, documentos de identidad), bloqueando extensiones ejecutables o maliciosas.

### Mejora: Enmascaramiento de Datos Personales en Pantalla (*Data Masking*)
**Descripción:** Función de minimización de datos conforme a ISO 27001 que oculta parcialmente datos sensibles de pacientes (números telefónicos y correos) para usuarios que no tienen permiso explícito de administración.

### Mejora: Cabeceras de Seguridad Avanzadas y Política de Contenido (CSP y HSTS)
**Descripción:** Middleware `SecurityHeaders` que inyecta Content Security Policy (CSP), protección contra clickjacking (`X-Frame-Options`), prevención de sniffing MIME y forzado de HTTPS estricto mediante HSTS en todo el dominio institucional.

### Mejora: Trazabilidad Forense de Acciones en Base de Datos (`LogsActivity`)
**Descripción:** Trait de auditoría automática que registra qué usuario creó, modificó o eliminó cualquier registro clínico o administrativo en el sistema, con captura del valor anterior y el valor nuevo en la tabla `activity_log`.

---

## 12. Ergonomía Asistencial, Atajos de Teclado y Experiencia de Usuario (UI/UX)

### Mejora: Reorganización Estructural del Menú Lateral (Sidebar)
**Descripción:** Rediseño de la barra de navegación lateral reduciendo 14 encabezados dispersos a 5 grupos lógicos de trabajo: *Principal*, *Gestión Clínica*, *Citas y Agenda*, *Gestión y Reportes*, y *Administración*, facilitando la orientación del usuario.

### Mejora: Atajos de Teclado Asistenciales en Todos los Módulos
**Descripción:** Implementación de atajos estándar para agilizar la labor diaria del personal:
* **`F2`:** Refrescar listados o aplicar filtros de búsqueda de inmediato.
* **`Ctrl + S`:** Guardar informe médico, cita o formulario activo sin usar el ratón.
* **`Escape`:** Cerrar paneles laterales, modales de visualización o cancelar formularios.

### Mejora: Paneles Deslizantes Suaves con Desenfoque de Foco (`Backdrop Blur`)
**Descripción:** Transiciones visuales con aceleración natural a 350ms y desenfoque del fondo para que el especialista mantenga el foco en la lectura médica, con opción de cerrar con un solo clic fuera del panel.

### Mejora: Modo Claro y Modo Oscuro
**Descripción:** Soporte completo de temas visuales que permite a los radiólogos y transcriptores trabajar en ambientes de baja iluminación (salas de lectura diagnóstica) reduciendo la fatiga visual.

---

## 13. Modernización Arquitectural: Adopción de Arquitectura Limpia (Clean Architecture)

### Mejora: Transición a Arquitectura por Capas Desacopladas (Services, Requests y Resources)
**Descripción:** Reestructuración integral del núcleo de software de LIDiag migrando de controladores monolíticos sobrecargados hacia un modelo de Arquitectura Limpia modular y desacoplada, estructurada en tres capas de alta responsabilidad:
1. **Capa de Validación y Autorización (Form Requests):** Se crearon clases de validación independientes (`app/Http/Requests/`) que filtran, autorizan y limpian estrictamente los datos de entrada antes de que toquen la lógica médica, impidiendo peticiones incompletas o maliciosas.
2. **Capa de Dominio y Lógica Asistencial (Services):** Se independizó la lógica de negocio en más de 35 servicios especializados (`app/Services/`), separando las reglas de agendamiento, lectura, dosimetría, liquidación y auditoría de la capa web.
3. **Capa de Presentación y Transformación (API Resources & DTOs):** Se estandarizó la serialización de respuestas mediante recursos dedicados (`app/Http/Resources/`), garantizando contratos de datos uniformes, estructurados y optimizados hacia la interfaz de usuario en Vue 3 y sistemas externos.

### Mejora: Beneficios Estratégicos y Retorno de Inversión Tecnológica (Por qué es Recomendable)
**Descripción:** La adopción de esta arquitectura limpia aporta ventajas corporativas determinantes para la operación institucional:
* **Mantenibilidad y Agilidad Evolutiva:** Los cambios en normativas de salud o reglas de facturación se implementan de forma aislada en el servicio correspondiente sin riesgo de alterar otras áreas de la aplicación.
* **Escalabilidad y Rendimiento Hospitalario:** Al separar la validación y transformación de datos, el consumo de memoria del servidor se reduce y la base de datos se protege mediante consultas atómicas y transaccionales bien delimitadas.
* **Trazabilidad y Testabilidad Automatizada:** Facilita la creación de pruebas unitarias y de integración sobre la lógica de negocio pura, garantizando cero regresiones ante futuras actualizaciones del sistema.
* **Seguridad y Blindaje de Datos:** Elimina vulnerabilidades de inyección y sobreescritura accidental, asegurando que solo los parámetros validados por los `Requests` alcancen los registros clínicos de los pacientes.

---

## 14. Rendimiento, Calidad de Código y Estabilidad Técnica

### Mejora: Normalización de Autoload Estricto PSR-4
**Descripción:** Corrección de la estructura de nombres de carpetas y archivos en todo el sistema. El autoload de clases de Composer compila al 100% sin advertencias, mapeando de forma estática 9,416 clases del sistema para máxima velocidad de ejecución.

### Mejora: Suite Automatizada de Pruebas de Integración (37 Tests / 196 Aserciones)
**Descripción:** Batería de pruebas automáticas en PHPUnit que valida continuamente la autenticación, los permisos por rol, los cálculos de liquidación, la conexión con SAHI y la integridad de las APIs clínicas.

### Mejora: Compilación y Optimización de Activos con Vite
**Descripción:** Empaquetado de componentes Vue 3 y estilos en un tiempo récord de ~1.5 segundos, reduciendo el consumo de ancho de banda y acelerando la carga inicial en los navegadores de las sedes.

---

*Documento consolidado semestral para la Dirección y Gerencia General de Salud Tolima — LIDiag v3.2.*

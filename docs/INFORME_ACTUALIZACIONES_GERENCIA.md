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

### Mejora: Tipificación y Alerta Inmediata de Hallazgos Críticos en Urgencias
**Descripción:** Mecanismo integrado en la estación de trabajo radiológica y transcripción (`leerEstudioComponent.vue` y `transcribirEstudioComponent.vue`) que permite al especialista marcar estudios de riesgo vital inminente (`[x] Marcar Crítico`). Al activarlo, el sistema despliega un campo opcional para ingresar un hallazgo puntual (ej. *"Neumotórax a tensión derecho"*, *"Hemorragia subaracnoidea aguda"*). Si el campo se deja en blanco, un algoritmo especializado procesa el informe y extrae de forma automática la sección de `Opinión:`, `Conclusión:` o `Impresión diagnóstica:`, escalando el caso a prioridad máxima en toda la red hospitalaria.

---

## 4. Control de Radiación y Dosimetría del Paciente (Resolución 482 de 2018)

### Mejora: Módulo de Dosimetría y Vigilancia Radiológica del Paciente
**Descripción:** Sistema para capturar, calcular y vigilar la dosis de radiación ionizante recibida por cada paciente en exámenes de Rayos X, Tomografía y Fluoroscopia, dando estricto cumplimiento a la Resolución 482 de 2018 del Ministerio de Salud y Protección Social.

### Mejora: Rediseño del Dashboard de Dosimetría con Estándar Nativo AdminWrap
**Descripción:** Modernización integral de la interfaz visual del panel de control de dosimetría (`/dosimetria/dashboard`), eliminando estilos heterogéneos y adoptando la arquitectura visual oficial de la plataforma: encabezados con migas de pan (`page-titles`), tarjetas de indicadores Info-Box con semáforos cromáticos unificados, ranking de equipos emisores "Projects of the Month", cronología operativa de eventos "Today's Schedule" y selector dinámico de fechas y sedes.

### Mejora: Calibración Clínica del Detector de Deriva Técnica de Tubos Emisores (*Machine Drift*)
**Descripción:** Algoritmo heurístico que supervisa el envejecimiento o descalibración de los tubos de rayos X y tomógrafos comparando la dosis promedio de la primera mitad del periodo frente a la segunda. Se incorporó un filtro de suelo de ruido (*noise floor*) para descartar disparos en vacío, topogramas y *scout views* (< 1.0 mGy), exigiendo un mínimo de disparos válidos ($\ge 3$) y un delta clínico absoluto significativo ($\Delta \ge 1.5\text{ mGy}$ en CTDIvol), erradicando el 100% de falsas alarmas por sesgo de base pequeña en los más de 53.000 estudios evaluados.

### Mejora: Telemetría Continua con "Interruptor de Hombre Muerto" (*Dead Man's Switch*)
**Descripción:** Protocolo de latido periódico (*Heartbeat*) mediante el cual los agentes Edge de cada hospital reportan cada ciclo su estado de conectividad, tamaño de cola local y versión de software a Central (`POST /api/dosimetria/heartbeat`). Si un servidor hospitalario sufre un corte eléctrico o pérdida de red, el Dashboard Central lo detecta y lo marca de inmediato en estado `OFFLINE` para rápida intervención del equipo de soporte.

### Mejora: Parser de Reportes Estructurados de Dosis DICOM (RDSR)
**Descripción:** Lector automático que extrae la información dosimétrica directa emitida por tomógrafos y angiógrafos modernos a través de objetos DICOM RDSR (Radiation Dose Structured Report), almacenando producto dosis-longitud (DLP), dosis en superficie y tiempo de irradiación.

### Mejora: Estimación Matemática de Dosis (DAP) bajo Metodología IAEA TRS-457
**Descripción:** Algoritmo biofísico que calcula de forma automática el Producto Dosis-Área (DAP en $mGy \cdot cm^2$) para equipos de Rayos X digitales que no poseen cámara de ionización física en el colimador, utilizando los parámetros de exposición del disparo (kVp, mAs, distancia foco-receptor y filtración añadida).

### Mejora: Clasificación Anatómica RadLex y Detección de Deriva de Dosis
**Descripción:** Mapeo de procedimientos radiológicos a la terminología estándar internacional RadLex. El sistema clasifica el 100% de los estudios en 8 regiones anatómicas (Tórax, Cráneo, Abdomen, Pelvis, Columna, Extremidades, Mama y General) y detecta desviaciones cuando un examen supera los Niveles de Referencia para Diagnóstico (NRD), emitiendo alertas para el Oficial de Protección Radiológica.

### Mejora: Anonimización Criptográfica de Pacientes con HMAC-SHA256 y Semilla Dinámica (`dosimetria_salt`)
**Descripción:** Protección de la identidad del paciente en la telemetría de radiación mediante generación de identificadores seudonimizados con algoritmo HMAC-SHA256 y semilla criptográfica aleatoria (`dosimetria_salt`) de 64 caracteres hexadecimales única por sede hospitalaria. Este mecanismo cumple los estándares de privacidad clínica (Ley 1581 de 2012 e ISO 27001), impide ataques de tablas arcoíris (*anti-rainbow table*) y permite al sistema central calcular la **Dosis Efectiva Acumulada** de pacientes con múltiples tomografías a lo largo del tiempo sin almacenar jamás sus documentos de identidad en texto plano.

### Mejora: Blindaje de Transmisión Masiva y Tolerancia de Red en Dosimetría Edge
**Descripción:** Optimización del canal de telemetría dosimétrica para soportar hospitales departamentales de alta complejidad con más de 30.000 estudios anuales (como el Hospital Federico Lleras Acosta en Ibagué y el Hospital San Rafael en Espinal). La transmisión se empaqueta en micro-bloques de 50 registros con cadencia controlada (150 ms) y reintentos automáticos con retroceso exponencial, complementado con una cuota ampliada en Central de 1.200 peticiones por minuto y autenticación multi-canal (Bearer Token, cabecera `X-API-TOKEN` y payload) para operar sin bloqueos a través de proxies hospitalarios y servidores web Apache.

### Mejora: Cola Persistente Edge en SQLite (`cola_dosimetria.db`)
**Descripción:** Base de datos embebida en los servidores locales de cada hospital que almacena temporalmente las mediciones de radiación y las sincroniza con el servidor central. Cuenta con reintentos automáticos y garantiza cero pérdida de datos ante cortes eléctricos o caídas prolongadas del enlace de internet.

### Mejora: Reporte Gerencial y Multi-Hoja en Excel para Secretarías de Salud
**Descripción:** Generador de reportes consolidados en Excel con membrete institucional, gráficos estadísticos de barras y tortas por modalidad/sede, indicadores de cumplimiento NRD y trazabilidad histórica para auditorías del Ministerio de Salud o entes territoriales.

---

### 4.1 Balance y Auditoría de Producción Real del Módulo de Dosimetría

Con la restauración y auditoría de la base de datos de producción (noviembre de 2026), se consolidaron las siguientes métricas operativas reales:

* **Volumen Total Monitoreado:** **53.176 mediciones dosimétricas** registradas en la red hospitalaria.
* **Pacientes Únicos Protegidos:** **36.516 pacientes** bajo seguimiento dosimétrico y trazabilidad de identidad protegida.
* **Cobertura Hospitalaria Activa:**
  * **Sede Espinal (Hospital San Rafael):** 31.225 estudios procesados.
  * **Sede Chaparral (Hospital San Juan Bautista):** 21.172 estudios procesados.
  * **Sede Ibagué (Hospital Federico Lleras Acosta):** 779 estudios procesados (incorporación reciente).
* **Parque de Equipos Emisores:** 19 equipos emisores auto-descubiertos e inventariados activamente (Rayos X y Tomografía Computarizada).

---

### 4.2 Hoja de Ruta y Próximas Mejoras Estratégicas en Dosimetría

A partir de la auditoría técnica profunda sobre los datos reales, se priorizaron las siguientes funcionalidades para robustecer el cumplimiento de la Resolución 482 de 2018 y las directrices del Organismo Internacional de Energía Atómica (OIEA):

#### Fase 1: Calibración Física y Regulatoria (Prioridad Inmediata)
1. **Poblamiento Oficial de Umbrales NRD (Resolución 482 / ICRP):**
   * *Objetivo:* Cargar en la base de datos los límites y niveles de advertencia regulatorios colombianos para Tomografía ($CTDI_{vol}$, $DLP$), Radiología Convencional ($DAP$) y Mamografía ($DGM$), activando el semáforo de superaciones reales y el catálogo interactivo para físicos médicos.
2. **Parametrización Multidimensional por Región Anatómica:**
   * *Objetivo:* Extender la tabla de umbrales para que la evaluación de dosis no sea plana por modalidad, sino diferenciada por órgano blanco: Cráneo ($50\text{ mGy}$), Tórax ($15\text{ mGy}$), Abdomen/Pelvis ($25\text{ mGy}$) y Columna, evitando tanto falsas alarmas en estudios de alta atenuación como omisión de sobreexposiciones en órganos sensibles.
3. **Calibración Científica de Dosis Efectiva Media $E$ (ICRP 103):**
   * *Objetivo:* Sustituir el factor multiplicador genérico actual por la matriz de coeficientes $k$ ponderados de la Comisión Internacional de Protección Radiológica según región anatómica y modalidad, reflejando el impacto biológico real en milisieverts ($mSv$).
4. **Extracción y Cálculo de Producto Dosis-Longitud ($DLP$) en Tomografía:**
   * *Objetivo:* Actualizar el agente Edge para capturar o deducir el $DLP$ ($CTDI_{vol} \times L_{scan}$) en tomografía computarizada desde tags de protocolo y cabeceras privadas de Siemens, GE y Philips.

#### Fase 2: Herramientas Clínicas Asistenciales y Pasaporte Radiológico
5. **Pasaporte Dosimétrico del Paciente (Historial de Dosis Acumulada de por Vida):**
   * *Objetivo:* Crear una interfaz clínica interactiva que permita al radiólogo y médico remitente consultar, mediante el identificador criptográfico del paciente, su curva de dosis acumulada total a través de todas las sedes de la red hospitalaria (especialmente crítico para pacientes oncológicos, crónicos o politraumatizados con más de 15 tomografías acumuladas).
6. **Gestor Biomédico de Equipos Emisores (ABM de Salas y Tubos):**
   * *Objetivo:* Panel administrativo que permita al Oficial de Protección Radiológica y al equipo biomédico personalizar el nombre de las salas ("Sala RX 1 - Urgencias"), registrar marca y modelo real, número de serie del tubo emisor, fecha de último mantenimiento preventivo y fecha de prueba de constancia dosimétrica.
7. **Notificaciones Push Proactivas en Telegram ante Superaciones NRD:**
   * *Objetivo:* Envío de alertas instantáneas a través del bot institucional de Telegram dirigidas al físico médico cuando un estudio supere el percentil 95 o el límite normativo NRD para su investigación y optimización inmediata de protocolos.

#### Fase 3: Acreditación Hospitalaria e Inteligencia Avanzada
8. **Estimación de Dosis Específica por Tamaño (SSDE - AAPM Report 204):**
   * *Objetivo:* Incorporar factores de corrección por diámetro efectivo del paciente para ajustar la dosis en pacientes pediátricos y neonatales, garantizando máxima protección al paciente infantil.
9. **Certificado Institucional de Dosimetría con Validación QR para Secretarías de Salud:**
   * *Objetivo:* Generador automático de certificados oficiales en formato PDF con firma electrónica y código QR de autenticidad para presentar como evidencia de cumplimiento estricto de la Resolución 482 en visitas de habilitación e inspección de entes gubernamentales.

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

### Mejora: Módulo de Auditoría y Reportes de Estudios PACS por Sede (`reportes-estudios-pac`)
**Descripción:** Interfaz interactiva de consulta y auditoría radiológica (`reporteEstudiosPACComponent.vue`) desarrollada bajo la estética institucional AdminWrap. Permite a coordinadores médicos y de facturación seleccionar sedes activas y rangos de fechas para auditar con precisión los estudios adquiridos en el PACS Dcm4chee (documento de identidad, paciente, fecha/hora exacta, descripción del examen y modalidades involucradas), integrando descarga directa de archivos consolidados en formato Microsoft Excel (`/descarga-reportes-estudios-pac`).

---

## 9. Monitoreo Gerencial y Supervisión Asistencial vía Telegram (`@salud_tolima_bot`)

### Mejora: Alertas de Hallazgos Críticos en Urgencias con Justificación Clínica Explícita
**Descripción:** Rediseño integral de las alertas push de hallazgos críticos enviadas a los grupos y directores asistenciales por Telegram. El mensaje detalla con precisión médica **por qué se requiere la valoración inmediata del paciente**:
* **Conclusión / Hallazgo Patológico Urgente:** Extraído automáticamente de la conclusión u opinión del informe médico (o ingresado puntualmente por el radiólogo).
* **Motivo del Examen y Sospecha Clínica:** Rescata la justificación clínica de ingreso del paciente (`motivo_examen`).
* **Diagnósticos CIE-10 Asociados:** Lista los códigos y nombres de las patologías identificadas.
* **Instrucción de Choque Asistencial:** Directriz clara para el médico tratante de guardia en Urgencias o UCI.

### Mejora: Medición del Tiempo de Respuesta Clínico Real (TAT) y Desglose Dual de Producción
**Descripción:** Optimización matemática de la oportunidad diagnóstica en los reportes `/red` y `/produccion`. El Turnaround Time (TAT) se calcula de forma real contra la fecha de toma física del examen (`fec_estudio`), eliminando distorsiones por desfases de sincronización en bases de datos. Además, se implementó el **desglose dual** que separa los *Procedimientos/Partes Interpretadas* (`COUNT(estudios_productos)`) del *Total de Pacientes Únicos Atendidos* (`COUNT(DISTINCT estudios.id)`), garantizando total coherencia con los reportes gerenciales y de facturación.

### Mejora: Semáforo Asistencial Escalonado y Detección de Urgencias Críticas (`/retrasados`)
**Descripción:** Evolución del reporte de estudios pendientes hacia un semáforo de riesgo asistencial escalonado en 3 bandas de intervención operativa:
* 🟡 **24h a 48h (Alerta Preventiva):** Casos para balancear en el cambio de turno.
* 🟠 **48h a 72h (Retraso Crítico):** Prioridad alta para evitar quejas de servicio.
* 🔴 **> 72 horas (Retraso Severo):** Casos de máxima alarma con riesgo inminente de glosa por parte de las EPS.
* 🚑 **Detección de Urgencias Críticas:** Identifica y alerta cuántos estudios de Urgencias/UCI cumplen **más de 30 minutos** esperando lectura médica.

### Mejora: Menú Táctil Interactivo para Consulta de Producción
**Descripción:** Asistente interactivo en Telegram para directores y coordinadores que permite consultar en 3 toques la producción de estudios por sede, modalidad y rango de fechas directamente desde el celular, con generación y descarga directa de archivos Excel consolidados.

### Mejora: Monitor de Almacenamiento y Telemetría de Servidores en Vivo (`/dd`)
**Descripción:** El comando `/dd` evolucionó de un mensaje fijo a un informe dinámico en tiempo real que evalúa:
1. **Servidor Central:** Espacio libre, ocupado y porcentaje de uso de la unidad principal y carpetas de storage con barras de progreso visuales.
2. **Sedes Hospitalarias:** Estado de salud y última fecha de respaldo recibida desde los agentes Edge locales de cada hospital (Espinal, Honda, Líbano, Chaparral, etc.).

### Mejora: Supervisión Operativa de Citas y Canales Digitales (`/citas`)
**Descripción:** Módulo de reporte en Telegram orientado a la operación del día: refleja cuántas citas están agendadas para atención **HOY** (confirmadas, asistidas o canceladas) y la tasa de conversión y solicitudes procesadas a través del canal oficial de WhatsApp.

### Mejora: Resumen Matutino Automático (Daily Digest)
**Descripción:** Reporte programado enviado automáticamente todos los días a las 07:30 AM con el balance de producción del día anterior, el semáforo asistencial de la red y el estado de los agentes locales para la jornada que inicia.

### Mejora: Blindaje de Red IPv4 y Diagnóstico de Conectividad (`--diagnose`)
**Descripción:** Fortalecimiento de la capa de comunicación cURL de Telegram (`TelegramService`), forzando resolución IPv4 pura para evitar bloqueos por *IPv6 blackhole* en redes hospitalarias cerradas, con sanitización automática de tokens y diagnóstico integral de sockets en puerto 443.

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

### Mejora: Desacoplamiento Operativo y Documentación Técnica Dedicada por Servicio de Sede
**Descripción:** Reestructuración de la suite de software desplegada en servidores hospitalarios (`agente_backups_dcm`) separando la documentación técnica y operacional en manuales especializados e independientes:
1. **Respaldo de Base de Datos MySQL (`README_BACKUP_DB.md`):** Volcado con `mysqldump`, compresión ZIP de alta velocidad, validación CRC32, cálculo de huella SHA-256 y transferencia SMB directa a la NAS QNAP con purga dual de retención.
2. **Sincronización Continua de Imágenes DICOM (`README_SYNC_IMAGENES.md`):** Motor Robocopy multi-hilo persistente con política de preservación (cero borrados en destino) y monitoreo en vivo mediante `robocopy_scan.log`.
3. **Agente Edge de Dosimetría Radiológica (`README_DOSIMETRIA.md`):** Cumplimiento de la Resolución 482 / ISO 27001, justificación de `dosimetria_salt` para cálculo de dosis acumulada anónima y estrategia escalonada para hospitales de alto volumen (>15.000 estudios/mes).
4. **Recuperación ante Desastres (`README_RESTORE_IMAGENES.md`):** Procedimiento asistido de restauración cronológica descendente (`2026` -> `2025` -> `2024`...) para reactivar el servicio de urgencias en menos de 3 horas.
5. **Portal Maestro de Despliegue (`README.md`):** Catálogo unificado de sedes (`sede_id`), instalador automatizado en 1 clic (`install.bat`) y vinculación desatendida en el Programador de Tareas de Windows bajo `SYSTEM`.

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

### Mejora: Estandarización Canónica de Tarjetas de Indicadores (KPIs) en Dashboards Asistenciales
**Descripción:** Homologación visual de las tarjetas de resumen y métricas en los paneles de Dosimetría, Monitoreo de Backups y Seguridad de Tokens conforme al patrón canónico del tema institucional AdminWrap (`d-flex p-10 no-block` con columna de icono y columna de valor/etiqueta). Se eliminaron estilos heterogéneos y micro-barras de progreso no estándar, proporcionando una lectura homogénea y semáforos cromáticos unificados (`text-success`, `text-info`, `text-warning`, `text-danger`).

### Mejora: Rebalanceo y Optimización Ergonómica de Columnas en Listas de Trabajo Radiológicas
**Descripción:** Optimización milimétrica de las tablas de Asignación y Estudios Asignados (`AsignarEstudioComponent.vue` y `estudioAsignadosComponent.vue`) mediante la fijación proporcional de anchos en píxeles, desactivación del auto-ancho dinámico invasivo de DataTables (`autoWidth: false`) y jerarquización tipográfica del paciente y su documento, erradicando saltos de línea incómodos y agilizando la lectura visual de listas de trabajo con alto flujo asistencial.

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

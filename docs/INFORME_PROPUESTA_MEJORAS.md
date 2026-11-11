# Informe Exhaustivo: Nuevos Módulos, Funcionalidades y Mejoras para LIDiag

**Fecha de Actualización:** Noviembre de 2026  
**Autor:** Antigravity AI — Pair Programming  
**Área:** Arquitectura de Software, Dirección Médica, Operaciones y Radiología  
**Destinatarios:** Gerencia General, Coordinación Médica de Radiología, Dirección de Sistemas y Operaciones

---

## 1. Visión Global de la Evolución de LIDiag

Con una infraestructura consolidada en **PHP 8.4 + Laravel 13**, **Vue 3**, **FastAPI + Whisper**, interoperabilidad bidireccional con **HIS/SAHI (SQL Server)**, **PACS dcm4chee** y los agentes Edge de respaldos y dosimetría en las sedes hospitalarias (Espinal, La Dorada, Chaparral, Ibagué, Líbano, Honda, Rovira), **LIDiag** está preparado para dar el salto de un sistema de captura y transcripción a un **Ecosistema Radiológico Integral (RIS/PACS de Nueva Generación)**.

---

## 2. Catálogo de Nuevos Módulos a Implementar

A continuación se detalla la especificación de **6 nuevos módulos de alto impacto clínico y operativo**:

### MÓDULO 1: Control de Calidad Radiológica y Doble Lectura (Peer Review / RADPEER)
* **Objetivo:** Cumplir con los estándares de acreditación en salud y auditoría de calidad médica mediante la sobrelectura cruzada aleatoria de estudios radiológicos.
* **Componentes y Flujo:**
  - **Muestreo Automatizado:** Algoritmo configurable que selecciona aleatoriamente entre el 2% y 5% de los estudios informados mensualmente por cada especialista.
  - **Bandeja de Doble Lectura Ciega:** El segundo radiólogo revisa las imágenes y el informe previo sin sesgo, emitiendo una calificación bajo la escala internacional **RADPEER**:
    * *1. Concordancia diagnóstica completa.*
    * *2. Discrepancia menor (sin impacto clínico).*
    * *3. Discrepancia significativa (impacto en el tratamiento).*
    * *4. Discrepancia crítica que requiere notificación inmediata.*
  - **Comité de Conciliación y Estadísticas:** Panel para la coordinación médica con métricas de concordancia por modalidad (TAC, RM, RX, Mamografía), curvas de aprendizaje y reportes anónimos para mejora continua.
* **Impacto:** Eleva la seguridad clínica del paciente y posiciona a LIDiag ante auditorías de secretarías de salud y entidades acreditadoras.

---

### MÓDULO 2: Gestión de Medios de Contraste y Farmacovigilancia
* **Objetivo:** Controlar la seguridad farmacológica en estudios contrastados de Tomografía (Yodo) y Resonancia Magnética (Gadolinio).
* **Componentes y Flujo:**
  - **Screening Pre-Procedimiento:** Verificación obligatoria de antecedentes alérgicos, cálculo automático de Tasa de Filtración Glomerular (eGFR / Creatinina sérica mediante fórmulas CKD-EPI o Cockcroft-Gault) con alerta de riesgo de Nefropatía Inducida por Contraste (NIC).
  - **Trazabilidad de Dosis y Lote:** Registro de volumen inyectado (ml), velocidad de flujo, marca comercial, lote y fecha de vencimiento.
  - **Reporte de Reacciones Adversas (RAM):** Formulario estandarizado de eventos adversos (leves: náuseas, urticaria; moderados: broncoespasmo; severos: choque anafiláctico) con notificación instantánea al comité de Farmacovigilancia y generación de formato oficial Invima.
* **Impacto:** Cumplimiento de normatividad farmacéutica y reducción a cero de riesgos médico-legales en procedimientos contrastados.

---

### MÓDULO 3: Mantenimiento Biomédico y Gestión de Equipos Emisores de Radiación
* **Objetivo:** Monitorear el ciclo de vida, disparos, calibraciones y vigencias legales de los equipos radiológicos de la red.
* **Componentes y Flujo:**
  - **Ficha Técnica Digital de Equipos:** Registro de marca, modelo, serie, fecha de instalación, sede y sala de cada Tomógrafo, Ecógrafo, Arco en C y Equipo de RX.
  - **Contador de Disparos y Desgaste del Tubo:** Monitoreo del envejecimiento del tubo de rayos X para proyectar reemplazos antes de fallos catastróficos que detengan el servicio hospitalario.
  - **Control de Mantenimientos y Fantomas:** Cronograma con alertas de mantenimiento preventivo, calibraciones dosimétricas de física médica y pruebas de calidad de imagen con fantomas.
  - **Semáforo de Licencias Radiactivas:** Notificación 60 y 30 días antes del vencimiento de licencias de operación de equipos emisores ante el Ministerio de Minas / Secretaría Departamental.
* **Impacto:** Previene paradas operativas no planificadas y asegura la continuidad del servicio en los hospitales de la red.

---

### MÓDULO 4: Portal Web para Médicos Remitentes y Especialistas Hospitalarios
* **Objetivo:** Brindar a los médicos de urgencias, hospitalización, cirugía y consulta externa acceso instantáneo a los informes y visores de sus pacientes.
* **Componentes y Flujo:**
  - **Autenticación Médica Segura:** Acceso con credenciales institucionales o SSO.
  - **Bandeja de Pacientes a Cargo:** Lista filtrada por piso, cama, servicio (Urgencias, UCI, Cirugía) o médico solicitante.
  - **Visor Clínico Web Liviano:** Apertura en 1 clic del informe firmado y visualización DICOM web sin requerir instalación de visores en las estaciones hospitalarias.
  - **Solicitud de Segunda Opinión / Prioridad:** Canal bidireccional donde el cirujano u ortopedista puede solicitar al radiólogo aclaraciones o priorización de un estudio urgente.
* **Impacto:** Elimina llamadas telefónicas redundantes al servicio de radiología y acelera las decisiones quirúrgicas y de alta médica.

---

### MÓDULO 5: Facturación Electrónica y Generador de RIPS en JSON (Res. 2275 de 2023)
* **Objetivo:** Automatizar la generación de soportes de cobro y archivos RIPS bajo la normativa vigente del Ministerio de Salud.
* **Componentes y Flujo:**
  - **Mapeo Automático CUPS / Tarifarios:** Cruce entre el procedimiento realizado (`cod_cups`), el contrato de la EPS y el manual tarifario (SOAT, ISS + porcentaje o propio).
  - **Generador de RIPS JSON:** Estructuración de datos de consulta, procedimiento, usuario y transacción en el formato JSON exigido por el validador oficial del Ministerio.
  - **Módulo de Trazabilidad de Glosas:** Registro de devoluciones o glosas por parte de las EPS con causales y gestión de respuestas para el equipo de facturación.
* **Impacto:** Flujo de caja garantizado y radicación de cuentas médicas sin rechazos normativos.

---

### MÓDULO 6: Pipeline de Pre-Triage Diagnóstico Asistido por Inteligencia Artificial
* **Objetivo:** Apoyar la detección temprana de patologías críticas mediante modelos de visión artificial locales o en nube privada.
* **Componentes y Flujo:**
  - **Recepción DICOM en Nodo de Inferencia:** Al recibir un estudio de TAC de cráneo o RX de tórax en el PACS, el motor envía las imágenes a un contenedor de inferencia con modelos abiertos (ej. TorchIO, MONAI).
  - **Detección de Hallazgos Positivos:** Análisis de sospecha de:
    * Hemorragia intracraneal (ICH) en Tomografía de cráneo.
    * Neumotórax o neumoperitoneo en Rayos X de tórax/abdomen.
    * Fracturas óseas desplazadas.
  - **Reordenamiento Dinámico de Lista de Trabajo:** Si el modelo detecta probabilidad >85% de patología aguda, el estudio sube automáticamente al tope de la lista de lectura con una insignia `[⚡ Sospecha IA: Crítico]`.
* **Impacto:** Priorización de pacientes en riesgo vital antes de que el radiólogo llegue al estudio por orden cronológico.

---

## 3. Catálogo de Nuevas Funcionalidades en Módulos Existentes

### 3.1 En el Módulo de Transcripción y Lectura de Estudios
* **Autoguardado Local Silencioso (`Drafts` en LocalStorage):** Respalda el texto en el navegador del médico cada 15 segundos. Detecta si el navegador se cerró o se perdió energía y ofrece restaurar el borrador en 1 clic.
* **Sistema de Macros y Snippets de Expansión Rápida:** Escribir `.torax`, `.normal`, `.cerebro` o `.sinpatologia` en el editor WYSIWYG y presionar `Tab` o `Espacio` para autocompletar párrafos diagnósticos estándar.
* **Firma Electrónica Avanzada con Código QR de Verificación:** Incorporar en el PDF un código QR público y seguro (`/validar-informe/{token}`) que permite a aseguradoras, jueces o pacientes validar la autenticidad del informe sin alterar el documento *(Implementado en Fase 2)*.
* **Dictado por Voz Continuo en Tiempo Real (Streaming con Whisper):** Evolucionar el flujo de "grabar audio completo y transcribir" a "dictado continuo con visualización palabra por palabra" mediante streaming WebSockets hacia el transcriptor local FastAPI.
* **Previsualización Instantánea del PDF en el Drawer:** Botón "*Ver PDF*" que compila en memoria la plantilla Word/PDF de la sede para validar membretes, márgenes y firmas antes del guardado final.
* **Visor DICOM Integrado en Pantalla Dividida (Split-Screen):** Modo de pantalla dividida 50/50 o 60/40 en monitores de radiología para redactar el informe viendo las series DICOM sin cambiar de ventana.

### 3.2 En Interoperabilidad HIS (SAHI) y PACS
* **Cola de Reintentos Automáticos de Sincronización SAHI:** Job programado (`php artisan sahi:reintentar-fallidos`) que procesa estudios pendientes o con fallas temporales de enlace VPN cada 10 minutos.
* **Alerta Inmediata de "Hallazgo Crítico" al Médico de Urgencias:** Mecanismo integrado en lectura y transcripción que permite marcar estudios con hallazgos de riesgo vital inminente, escalando alertas inmediatas por Telegram, WhatsApp y correo *(Implementado en Fase 2)*.
* **Sincronización de Imágenes y Reportes por Protocolo HL7 ORU / FHIR:** Capa de comunicación estándar para interoperar con HIS modernos que no permitan conexión directa a base de datos SQL Server.

### 3.3 En Agentes Edge, Red de Sedes y Seguridad
* **Monitor de Sedes Offline y Alertas Proactivas en Telegram:** Alerta automática en el Bot si una sede supera las 12 horas sin emitir latido (`heartbeat`) o si la NAS QNAP tiene menos del 15% de espacio en disco.
* **Historial y Curva de Dosis Radiológica Acumulada del Paciente (Res. 482 de 2018):** Gráfico de dosis acumulada (CTDIvol, DLP, DAP) a lo largo de los estudios realizados en toda la red, con alertas de superación de Niveles de Referencia Diagnóstica (DRLs).
* **Asistente Web de Restauración de Backups DICOM en 1 Clic:** Panel interactivo para restaurar estudios desde el `.zip` de la NAS hacia el dcm4chee local sin usar la consola de comandos.

### 3.4 En Pacientes, Citas y Consentimientos
* **Portal Web de Auto-Consulta de Resultados para Pacientes:** Vista web ligera `/portal-paciente` donde el usuario accede con su cédula y un PIN temporal enviado a su WhatsApp/SMS para descargar su informe firmado y ver sus imágenes.
* **Confirmación Interactiva de Citas por WhatsApp:** Mensajes con botones interactivos de Meta Cloud API: `[Confirmar]` y `[Cancelar]`, reduciendo hasta un 30% el ausentismo *(Implementado en Fase 2)*.
* **Envío Automático de Instrucciones de Preparación Médica:** Envío programado 24 horas antes con las pautas de preparación según el estudio (ayuno de 6 horas, ingesta de líquidos, suspensión de medicamentos).
* **Firma Digital Biométrica en Tablet para Consentimientos Informados de Rayos X:** Captura de firma sobre pantalla táctil para consentimientos de contraste en Tomografía y procedimientos invasivos, archivando el PDF firmado automáticamente en el expediente.

---

## 4. Matriz Maestra de Priorización y Estimación

| Nivel | Iniciativa | Tipo | Esfuerzo | Impacto |
| :---: | :--- | :---: | :---: | :---: |
| **Fase 1** | **Autoguardado de Borradores en LocalStorage** | Mejora | 1-2 días | Alta seguridad |
| **Fase 1** | **Macros y Snippets de Expansión de Texto (`.normal`)** | Nueva Func. | 2 días | Acelera redacción clínica |
| **Fase 1** | **Reintentos Automáticos de Sincronización SAHI** | Mejora | 2-3 días | Blindaje ante caídas de red |
| **Fase 1** | **Alertas en Telegram de Sedes Offline o Sin Backups** | Nueva Func. | 2 días | Detección temprana |
| **Fase 2** | **Alerta Inmediata de Hallazgos Críticos a Urgencias** | Nueva Func. | 3-4 días | Seguridad del paciente *(Completado)* |
| **Fase 2** | **Botones Interactivos de WhatsApp para Citas** | Nueva Func. | 4 días | Disminución de ausentismo *(Completado)* |
| **Fase 2** | **Firma Electrónica con Código QR de Verificación Pública** | Nueva Func. | 3 días | Validez médico-legal *(Completado)* |
| **Fase 2** | **Optimización de Bundles Vite y Balanceo de Tablas** | Optimización | 2 días | Carga ultra rápida en sedes *(Completado)* |
| **Fase 3** | **Portal Web de Pacientes con PIN Temporal WhatsApp** | Nuevo Módulo | 1-2 semanas | Experiencia del usuario |
| **Fase 3** | **Módulo de Control de Calidad y Doble Lectura (Peer Review)** | Nuevo Módulo | 2 semanas | Acreditación en salud |
| **Fase 3** | **Módulo de Gestión de Medios de Contraste y Eventos Adversos** | Nuevo Módulo | 2 semanas | Farmacovigilancia |
| **Fase 4** | **Módulo de Mantenimiento Biomédico y Tubos de RX** | Nuevo Módulo | 2-3 semanas | Continuidad del servicio |
| **Fase 4** | **Módulo de Facturación y RIPS JSON (Res. 2275)** | Nuevo Módulo | 3 semanas | Flujo financiero |
| **Fase 5** | **Pipeline de Pre-Triage Diagnóstico Asistido por IA** | Nuevo Módulo | 3-4 semanas | Innovación médica |

---

*Documento de Propuesta Técnica y Funcional para la Dirección y Gerencia General de Salud Tolima — LIDiag.*

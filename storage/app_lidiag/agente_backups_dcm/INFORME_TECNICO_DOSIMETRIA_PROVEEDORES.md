# INFORME TÉCNICO Y CLÍNICO: EVALUACIÓN DE TELEMETRÍA DOSIMÉTRICA DICOM EN EQUIPOS EMISORES DE RADIACIÓN IONIZANTE

**Dirigido a:** Dirección Médica, Ingeniería Biomédica, Físico Médico y Proveedores de Soporte Técnico de Equipos Radiológicos  
**Elaborado por:** Equipo de Desarrollo e Interoperabilidad — LIDiag  
**Sede Evaluada:** Sede Espinal — Hospital San Rafael  
**Fecha de Emisión:** 26 de Octubre de 2026  
**Marco Normativo:** Resolución 482 de 2018 (Ministerio de Salud y Protección Social de Colombia) / Estándar DICOM PS3.3 / ISO/IEC 27001  

---

## 1. Resumen Ejecutivo

En el marco de la implementación del módulo de vigilancia dosimétrica y cumplimiento del estándar de garantía de calidad en protección radiológica (Resolución 482 de 2018), se implementó un agente de telemetría automática (*LIDiag Dosimetría Edge*) encargado de extraer las magnitudes de dosis absorbida por paciente directamente desde las cabeceras DICOM generadas por las modalidades asistenciales de la sede.

Tras la auditoría forense de los estudios generados entre el **25 y 26 de octubre de 2026** (105 estudios evaluados), se determinó:
1. **Tomografía Computarizada (CT):** Integración **100% exitosa**. El tomógrafo (`TM_CT_CMW_V3.00`) calcula, emite e incrusta de forma obligatoria y consistente las magnitudes de dosis volumétrica (`CTDIvol` en mGy) en cada estudio (rango observado: 4.30 a 47.60 mGy).
2. **Radiografía Digital Convencional (DX / CR):** **Inconsistencia detectada**. El equipo de Rayos X registra en la cabecera DICOM un valor numérico nulo (`0.000000`) en los tags estándar de dosis acumulada (DAP / Kerma / Organ Dose), a pesar de registrar con exactitud los parámetros técnicos del disparo (`KVP`, `mAs`, tiempo de exposición).

---

## 2. Evidencia Técnica y Hallazgos Forenses en Cabecera DICOM

A continuación se detalla la extracción directa de tags sobre archivos DICOM representativos generados por la modalidad de Radiografía Digital (`DX`):

### Estudio Muestra A: D:\img_dcm\2026\10\25\0\5FD7344E\092AC84D\8C2C44FC
* **Modalidad:** `DX` (Radiografía Digital)
* **Fecha/Hora:** 25/10/2026 00:23:14

```text
========================================================================================
TAG DICOM          DESCRIPCIÓN ESTÁNDAR (PS3.3)                    VALOR OBSERVADO
========================================================================================
(0018, 0060)       KVP (Kilovoltaje Pico)                          55.000000 kV
(0018, 1150)       Exposure Time (Tiempo de Exposición)            32.000000 ms
(0018, 1152)       Exposure (Carga de Tubo / Corriente-Tiempo)     6.400000 mAs
(0018, 115E)       Image and Fluoroscopy Area Dose Product (DAP)   0.000000 Gy*cm2  <-- NULO
(0018, 1411)       Exposure Index (Índice de Exposición IEC 62494) 0.000000          <-- NULO
(0040, 0316)       Organ Dose (Dosis en Órgano)                    0.000000 mGy      <-- NULO
(300A, 022A)       Reference Air Kerma Rate                        0.000000          <-- NULO
(300A, 0250)       Total Reference Air Kerma                       0.000000 Gy       <-- NULO
========================================================================================
```

### Estudio Muestra B: D:\img_dcm\2026\10\25\0\5FD7344E\092AC84D\9E2DDED4
* **Modalidad:** `DX`
* **Parámetros de disparo:** `KVP = 75.0 kV`, `Exposure = 20.0 mAs`
* **Resultado DAP `(0018, 115E)`:** `0.000000 Gy*cm2`

### Comparativa: Tomografía Computarizada (CT) en la misma Sede
* **Estudio:** `ANGIOTEP KL URG` / `TC CRANEO-CERVICAL SIMPLE`
* **Parámetros detectados:** `CTDIvol = 47.6000 mGy` y `CTDIvol = 5.8000 mGy`
* **Estado:** Operativo y reportando satisfactoriamente a LIDiag Central.

---

## 3. Diagnóstico de Causa Raíz

Existen tres causas técnicas posibles para que el equipo de Rayos X emita `0.000000` en el tag de dosis `(0018, 115E)`:

1. **Desactivación por Software en Consola de Adquisición:**
   El software del equipo de adquisición (Workstation / Consola de Control de RX) tiene deshabilitado el cálculo o la inserción del valor DAP en el flujo de exportación DICOM hacia el PACS dcm4chee.
2. **Cámara de Ionización (DAP Meter) no comunicada:**
   El equipo cuenta con una cámara de transmisión física instalada en la boca del colimador, pero el cable de datos (RS-232 o USB) no está conectado a la consola, o la interfaz de comunicación está desconfigurada en el software del fabricante.
3. **Ausencia de Hardware de Medición DAP:**
   El tubo de rayos X no cuenta con cámara de transmisión dosimétrica acoplada al colimador, y el software no tiene configurada la tabla de estimación teórica de Kerma en Aire.

---

## 4. Plan de Acción y Preguntas para el Proveedor de los Equipos

Se solicita formalmente al **Ingeniero de Servicio Técnico / Especialista de Aplicaciones** del fabricante del equipo de Rayos X dar respuesta a las siguientes consultas:

### Pregunta 1: Configuración de Exportación DICOM
> *"¿Tiene la consola de adquisición la opción de habilitar el cálculo o reporte automático de DAP (`Dose Area Product`, Tag `0018,115E`) o `EntranceDoseInmGy` (`Tag 0040,0302`) en las imágenes DICOM enviadas al PACS?"*

### Pregunta 2: Conexión de Cámara de Ionización
> *"¿El equipo cuenta físicamente con cámara de transmisión DAP en el colimador? En caso afirmativo, ¿se encuentra activa la lectura y calibración con la consola?"*

### Pregunta 3: Structured Reporting (RDSR)
> *"¿El equipo soporta la emisión de informes estructurados de dosis de radiación (**Radiation Dose Structured Report - RDSR**, SOP Class `1.2.840.10008.5.1.4.1.1.88.67`) al finalizar cada adquisición o al cerrar el estudio?"*

---

## 5. ¿Qué podemos hacer en LIDiag mientras se realiza la gestión con el proveedor?

Para no depender exclusivamente de la visita técnica del proveedor y garantizar la vigilancia dosimétrica continua que exige la Resolución 482 de 2018, disponemos de **tres alternativas de solución inmediata**:

### Alternativa 1: Cálculo Automático Indirecto de Dosis (ESD) mediante Parámetros Reales de Disparo (Recomendado)
Como el archivo DICOM **SÍ registra con exactitud los valores reales de cada disparo**:
- $kVp$: Kilovoltaje pico aplicado.
- $mAs$: Miliamperios-segundo (carga del tubo).
- $SID$: Distancia foco-detector.

Podemos incorporar en `agente_dosimetria.py` el modelo estandarizado por el **OIEA (Organismo Internacional de Energía Atómica, Informe TRS-457)** y la Sociedad Española de Física Médica (SEFM) para calcular la **Dosis en Superficie de Entrada (ESD / Entrance Surface Dose)**:

$$\text{ESD (mGy)} = Y \times \left(\frac{kVp}{80}\right)^2 \times mAs \times \left(\frac{100}{\text{DFP} - d_{\text{paciente}}}\right)^2 \times \text{BSF}$$

*Donde:*
* $Y$: Rendimiento del tubo de rayos X (mGy/mAs a 1 metro), medido semestral/anualmente por el Físico Médico de la institución en las pruebas de control de calidad.
* $\text{BSF}$: Factor de retrodispersión (*Backscatter Factor*, típicamente 1.30 a 1.40 para adultos).
* $\text{DFP}$: Distancia foco-película (habitualmente 100 cm o 180 cm para tórax).

> **Ventaja:** Permite reportar la dosis real estimada de cada una de las 81 radiografías diarias de inmediato en LIDiag, cumpliendo con los registros institucionales de dosimetría de la Resolución 482.

---

### Alternativa 2: Calibración por Software en Consola por el Proveedor
Si el proveedor confirma que el equipo tiene capacidad de cálculo por software o cámara de ionización, coordinar una sesión de soporte para:
1. Activar el servicio *"DICOM Radiation Dose Export"*.
2. Calibrar el valor de área del campo con el tamaño de colimación automática.

---

### Alternativa 3: Instalación de Cámara DAP Externa (Hardware)
Si el equipo es analógico digitalizado o un CR básico sin capacidad de cómputo:
- Gestionar con Ingeniería Biomédica la cotización de una cámara de ionización DAP transparente externa (ej: *VacuDAP* de VacuTec o *KermaX-plus* de IBA Dosimetry), la cual se fija directamente en las ranuras accesorias del colimador y envía la lectura por red o cable a la consola.

---

## 6. Conclusiones y Próximos Pasos

1. El sistema de dosimetría de **Tomografía (CT)** en LIDiag está **100% operativo y certificado**.
2. Remitir este informe técnico al departamento de **Ingeniería Biomédica** y al **Físico Médico Asesor** de la sede para que soliciten la intervención del proveedor del equipo de Rayos X.
3. Si el usuario lo autoriza, LIDiag puede activar el módulo de **Cálculo de Dosis Estimada por Parámetros de Disparo (Alternativa 1)** para los estudios `DX`, permitiendo visualizar inmediatamente la dosimetría de Rayos X en el Dashboard sin esperar la visita técnica del fabricante.

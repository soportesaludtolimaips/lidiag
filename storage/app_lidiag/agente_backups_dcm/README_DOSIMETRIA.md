# LIDiag — Agente Edge de Dosimetría Radiológica (Resolución 482 / ISO 27001)

Script principal: **`agente_dosimetria.py`**  
Lanzador por lotes: **`ejecutar_dosimetria.bat`**  
Tarea programada de Windows: **`LIDiag_Dosimetria_Edge`**  
Frecuencia recomendada: **Horaria (cada 1 hora, ventana últimas 24 horas)**  

---

## 1. Descripción y Propósito Clínico

El Agente Edge de Dosimetría Radiológica es un componente de software clínico que extrae métricas de exposición a radiación ionizante directamente de las cabeceras DICOM de los equipos radiológicos locales (Tomógrafos CT, Mamógrafos MG, Angiógrafos XA y Rayos X Digitales DX/CR), permitiendo a las instituciones prestadoras de salud cumplir rigurosamente con la **Resolución 482 de 2018 del Ministerio de Salud y Protección Social de Colombia**, las directrices del **OIEA (Organismo Internacional de Energía Atómica)** y las normas **ISO 27001** de seguridad de la información.

### Capacidades Principales:
* **Extracción Multi-modalidad:**
  * **Tomografía Computarizada (CT):** Índice de Dosis en TC por Volumen (`CTDIvol` en mGy), Producto Dosis-Longitud (`DLP` en mGy·cm), kVp, mA, tiempo de rotación, pitch y reconstrucción.
  * **Mamografía Digital (MG):** Dosis Glandular Media (`DGM` en mGy), Entrada en Superficie (ESD), espesor de mama comprimida y fuerza de compresión.
  * **Rayos X y Angiografía (DX, CR, XA):** Producto Dosis-Área (`DAP` / `KAP` en Gy·cm² o dGy·cm²), kVp, mAs y filtración.
* **Cálculo de Dosis Efectiva Estimada ($E$ en mSv):** Integra factores de ponderación tisular según la publicación 103 de la **ICRP** ($E = \text{DLP} \times k$).
* **Anonimización Criptográfica HMAC-SHA256:** Protege la privacidad del paciente (PHI / Ley 1581 de 2012) mediante un hash salado irreversible, evitando transferir cédulas o nombres a la nube.
* **Cola Transaccional Persistente (SQLite):** Almacena las dosis en una base de datos local embebida (`logs/cola_dosimetria.db`). Si la conexión a Internet o la VPN del hospital se interrumpe, las dosis se conservan intactas y se retransmiten automáticamente al reanudarse la red.
* **Transmisión Eficiente por Micro-bloques:** Empaqueta las dosis en lotes de 50 registros con pausas de 150 ms para respetar los límites de tasa de la API Central y evitar errores HTTP 429.
* **Autenticación Multi-canal:** Compatible con Laravel Sanctum mediante cabecera HTTP Bearer, encabezado alternativo `X-API-TOKEN` o parámetro de cuerpo.

---

## 2. Privacidad, PHI y el Atributo `dosimetria_salt`

En el archivo `config.json`, el atributo **`dosimetria_salt`** cumple un rol indispensable para la ciberseguridad asistencial y el cumplimiento normativo:

```json
{
  "dosimetria_salt": "64_CARACTERES_HEXADECIMALES_UNICOS_POR_SEDE"
}
```

### ¿Para qué se utiliza exactamente?

1. **Privacidad y Protección de Datos Clínicos (PHI / Ley 1581 de 2012 e ISO 27001):**
   El agente extrae las métricas de radiación (`CTDIvol`, `DLP`, `mSv`, etc.), pero **nunca transmite la cédula ni el nombre real del paciente** a Central. En su lugar, el agente convierte el `PatientID` en un código hash irreversible mediante el algoritmo **HMAC-SHA256**.

2. **Protección contra Ataques de Diccionario (Rainbow Tables):**
   Si solo se usara un SHA-256 plano sobre la cédula, un atacante con una lista de documentos de identidad colombianos podría generar los hashes previamente y descubrir a qué persona corresponde cada estudio. Al agregar el `dosimetria_salt` (una clave criptográfica única de 64 caracteres generada por el agente), el hash resultante es completamente indescifrable desde el exterior.

3. **Cálculo de Dosis Acumulada por Paciente:**
   Al mantenerse el mismo salt en la sede hospitalaria, si el mismo paciente se toma 3 tomografías en diferentes meses, el agente generará exactamente el mismo identificador anónimo. Esto le permite al Dashboard de LIDiag Central calcular la **Dosis Efectiva Acumulada** de ese paciente sin necesidad de almacenar su número de cédula en texto plano.

> [!NOTE]
> **Autogeneración:** Si `config.json` no contiene `dosimetria_salt`, el agente lo genera aleatoriamente en su primera ejecución (`secrets.token_hex(32)`) y lo guarda automáticamente. Una vez generado, debe preservarse para mantener la concordancia histórica del paciente.

---

## 3. Configuración en `config.json`

```json
{
  "sede_id": 1,
  "sede_nombre": "Espinal",

  "db_host": "127.0.0.1",
  "db_port": 3306,
  "db_name": "pacsdb",
  "db_user": "root",
  "db_password": "PASSWORD_MYSQL",

  "local_images_dir": "D:\\img_dcm",

  "api_url": "https://10.35.47.4/lidiag/public/api/dosimetria/registros",
  "api_token": "TOKEN_BEARER_LIDIAG_AQUI",
  "dosimetria_salt": "7b8f9e2d4c5a1b3e6f8d0a2c4e6b8a1d3f5e7c9b1a3d5f7e9c1b3d5f7e9a1b3c"
}
```

---

## 4. Tabla de Argumentos de Línea de Comandos

| Argumento | Tipo | Valor por Defecto | Descripción |
| :--- | :---: | :---: | :--- |
| `--config`, `-c` | `str` | `config.json` | Ruta al archivo de configuración JSON. |
| `--dry-run` | `flag` | `False` | **Modo Simulación:** Extrae y calcula las métricas dosimétricas en pantalla sin transmitirlas a la API central. |
| `--dias` | `int` | `1` | Ventana de días hacia atrás a consultar desde la fecha actual. |
| `--desde` | `str` | `None` | Fecha inicial fija de consulta en formato `YYYY-MM-DD`. |
| `--hasta` | `str` | `None` | Fecha final fija de consulta en formato `YYYY-MM-DD`. |
| `--limit` | `int` | `200` | Límite máximo de estudios a procesar por ciclo (usa `0` o `--sin-limite` para ilimitado). |
| `--sin-limite` | `flag` | `False` | **Sin Límite:** Procesa todos los estudios candidatos del período seleccionado sin tope numérico. |
| `--forzar`, `--force` | `flag` | `False` | **Forzar re-procesamiento:** Ignora la memoria caché local y vuelve a evaluar y transmitir estudios ya leídos. |
| `--limpiar-cache` | `flag` | `False` | Elimina el archivo `logs/dosimetria_cache_procesados.json` y finaliza. |
| `--sede-id` | `int` | Desde config | Sobrescribe el ID de la sede hospitalaria en las métricas transmitidas. |

---

## 5. Comandos de Ejecución y Pruebas

```powershell
# 1. Ejecución estándar (últimas 24 horas, límite 200 estudios)
python agente_dosimetria.py

# O con el lanzador por lotes:
.\ejecutar_dosimetria.bat

# 2. Modo Simulación (audita y muestra métricas clínicas en consola sin transmitirlas)
python agente_dosimetria.py --dry-run --dias 7

# 3. Procesar ventana amplia de días con mayor cupo
python agente_dosimetria.py --dias 7 --limit 500
python agente_dosimetria.py --dias 30 --limit 1000

# 4. Procesar un rango histórico específico (ej: consolidar un mes completo)
python agente_dosimetria.py --desde 2026-10-01 --hasta 2026-10-31 --limit 1000

# 5. Forzar re-transmisión de un período ya procesado (bypasea caché)
python agente_dosimetria.py --desde 2026-10-01 --hasta 2026-10-15 --limit 300 --forzar

# 6. Limpiar la memoria caché local de estudios procesados
python agente_dosimetria.py --limpiar-cache
```

---

## 6. Estrategia para Hospitales de Alto Volumen (>15.000 estudios/mes)

En sedes hospitalarias con alto flujo radiológico, la operación se divide en **dos fases**:

### Fase 1: Carga Masiva Inicial de Datos Históricos
Cuando se instala el agente por primera vez y se requiere extraer meses o un año completo de datos pasados:

* **Opción A (Recomendada — Mes a Mes):** Para evitar que el motor MySQL de dcm4chee colapse o ralentice el PACS con un `SELECT` masivo en tablas con millones de filas, procesa de forma escalonada mes a mes sin límite:
  ```powershell
  python agente_dosimetria.py --desde 2026-08-01 --hasta 2026-08-31 --sin-limite
  python agente_dosimetria.py --desde 2026-09-01 --hasta 2026-09-30 --sin-limite
  python agente_dosimetria.py --desde 2026-10-01 --hasta 2026-10-31 --sin-limite
  ```

* **Opción B (Carga Anual Completa en 1 Solo Paso):**
  ```powershell
  python agente_dosimetria.py --dias 365 --sin-limite --forzar
  ```

* **Opción C (Reiniciar desde Cero Limpiando Caché Local):**
  ```powershell
  python agente_dosimetria.py --limpiar-cache
  python agente_dosimetria.py --dias 365 --sin-limite
  ```

### Fase 2: Operación Continua en Producción (Totalmente Automática)
* En un hospital con 15.000 estudios al mes:
  $$\frac{15.000\text{ estudios}}{30\text{ días}} = 500\text{ estudios/día} \approx \mathbf{20\text{ a }30\text{ estudios/hora}}$$
* La tarea programada `LIDiag_Dosimetria_Edge` se ejecuta automáticamente **cada 1 hora**.
* En cada ciclo horario, el agente solo encuentra y procesa los 20-30 estudios generados en esa hora en **menos de 2 segundos**, garantizando que el servidor del hospital opere con 0% de impacto en CPU, memoria y disco.

---

## 7. Diagnóstico y Monitoreo en Vivo

```powershell
# 1. Monitorear la bitácora de extracción y transmisión de dosimetría:
Get-Content .\logs\dosimetria_agent.log -Wait -Tail 30

# 2. Consultar el estado de la cola persistente SQLite:
# (Verifica cuántos registros están 'PENDIENTE', 'TRANSMITIDO' o 'ERROR')
python -c "import sqlite3; con = sqlite3.connect('logs/cola_dosimetria.db'); print(con.execute('SELECT estado, count(*) FROM cola_dosimetria GROUP BY estado').fetchall())"

# 3. Probar la conectividad HTTPS y la validez del token Bearer con la API Central de LIDiag:
$token = "TU_TOKEN_BEARER_AQUI"
Invoke-RestMethod -Uri "https://10.35.47.4/lidiag/public/api/dosimetria/registros" `
    -Method GET `
    -Headers @{ "Authorization" = "Bearer $token"; "Accept" = "application/json" }

# 4. Consultar el conteo de estudios por modalidad con radiación ionizante en MySQL local:
mysql -u root -p -e "SELECT modality, COUNT(*) AS total FROM pacsdb.series WHERE modality IN ('CT','MG','DX','CR','XA') GROUP BY modality;"
```

---

## 8. Solución de Problemas Frecuentes

* **HTTP 401: `{"message":"Unauthenticated."}`:**
  * El token configurado en `"api_token"` no coincide con el token activo en la base de datos de LIDiag Central.
  * Ve a **Administración → Seguridad → Tokens de Sedes** en la plataforma Central (`https://10.35.47.4/lidiag/public/seguridad-tokens`), genera o rota el token de la sede y actualiza el campo `"api_token"` en `config.json`.
* **HTTP 429: `Too Many Requests`:**
  * Ocurre si se transmiten miles de registros en pocos segundos superando el límite de Laravel.
  * El agente incluye un retardo de 150 ms entre bloques y 5 reintentos con backoff exponencial. Además, la API Central de LIDiag tiene un cupo ampliado de 1.200 peticiones/minuto para los endpoints de dosimetría. Si ocurre un fallo temporal, los registros permanecen en la cola SQLite y se enviarán automáticamente en el siguiente ciclo.
* **Estudio sin dosis (`None`):**
  * Algunos estudios de Rayos X análogos digitalizados o reconstrucciones secundarias no contienen cabeceras DICOM de dosis. El agente los identifica, los registra en la caché para no reevaluarlos y continúa con el siguiente estudio sin detenerse.

---

## 9. Procesamiento Central y Detección de Deriva Técnica (*Machine Drift*)

Una vez que el Agente Edge transmite las dosis al servidor central (`POST /api/dosimetria/registros`), el backend en Laravel (`DosimetriaService.php`) procesa y audita los datos automáticamente:

### 1. Upsert Idempotente y Desduplicación:
Los registros se insertan mediante clave única compuesta (`study_uid` + `tipo_metrica`). Si el agente retransmite estudios pasados o recupera la cola tras una desconexión, el sistema no duplica las mediciones ni altera los históricos.

### 2. Detección Predictiva de Deriva Técnica (*Machine Drift Detection*):
El sistema divide el período de análisis en dos mitades temporales para comparar la media inicial histórica frente a la media reciente de cada equipo emisor:
* **Alerta Predictiva (+20%):** Si un equipo registra un incremento sostenido $\ge +20\%$, se emite una alerta temprana en el Dashboard institucional sugiriendo posible descalibración física, colimación deficiente o fatiga del tubo emisor de rayos X.
* **Calibración y Mitigación de Falsos Positivos (*Noise Floor*):**
  Para prevenir falsas alarmas provocadas por el "sesgo de base pequeña" (por ejemplo, scout views / topogramas de TC con dosis basales de $0.2\text{ mGy}$ que suben a $0.32\text{ mGy}$ representando $+60\%$ relativo pero solo $0.12\text{ mGy}$ de diferencia absoluta):
  * **Filtro de suelo de ruido:** Se descartan del cálculo de drift disparos no diagnósticos (`CTDIvol < 1.0 mGy`, `DLP < 20 mGy·cm`, `DGM < 0.4 mGy`, `DAP < 0.05 Gy·cm²`).
  * **Umbral de incremento neto absoluto ($\Delta$ mínimo):** La alerta requiere superar un incremento neto representativo ($\Delta \ge 1.5\text{ mGy}$ en CT, $\Delta \ge 25\text{ mGy·cm}$ en DLP, $\Delta \ge 0.3\text{ mGy}$ en DGM).
  * **Muestra estadística mínima:** Se exige un mínimo de al menos 3 disparos en cada mitad temporal para que la media sea matemáticamente representativa.

### 3. Visualización en el Dashboard Central (`/dosimetria/dashboard`):
El personal de radioprotección e ingeniería biomédica puede supervisar el estado de todas las sedes en tiempo real, visualizar el ranking de tubos emisores, la línea de tiempo de auditoría de informes RDSR y exportar el informe legal en formato Excel para las Secretarías de Salud.

### 4. Matriz de Riesgo y Mapa de Calor Anatómico (7 Regiones Diana):
El backend clasifica cada estudio en una de las 7 regiones corporales normalizadas según RadLex Playbook y la Resolución 482:
* **Cráneo:** Procedimientos de cabeza, cerebro, senos paranasales, órbitas y macizo facial.
* **Tórax:** RX y TAC de tórax, mediastino, reja costal, pulmones y pleura.
* **Abdomen:** Estudios abdominales superiores e inferiores, hígado, páncreas y vías digestivas.
* **Pelvis:** Pelvis ósea, caderas, articulaciones sacroilíacas y urografía.
* **Columna:** Columna cervical, dorsal, lumbosacra y dinámica.
* **Extremidades:** Hombros, brazos, codos, muñecas, manos, fémur, rodillas, piernas, tobillos y pies.
* **Mama:** Exámenes de mamografía diagnóstica o tamizaje (MG / DGM).

El **Mapa de Calor** calcula el `% de Dosis respecto al NRD` de forma ponderada por cada cohorte técnica y región:
$$\text{pct\_nrd\_region} = \frac{\sum (\text{pct\_nrd}_i \times \text{estudios}_i)}{\sum \text{estudios}_i}$$
Semáforo cromático institucional:
* 🟢 **Seguro (< 80% del NRD):** Dosis optimizada bajo principio ALARA.
* 🟡 **Alerta Preventiva (80% a 99% del NRD):** Procedimientos cercanos al tope que ameritan revisión de protocolos.
* 🔴 **Superación Crítica ($\ge$ 100% del NRD):** Exceso normativo que activa investigación por física médica.

### 5. Arquitectura de Auto-Recuperación (Self-Healing) y Comandos Centrales:
Para garantizar la integridad continua sin depender exclusivamente del agente externo:
* **Auto-Recuperación Transparente:** `DosimetriaService::asegurarTelemetriaSedes()` verifica la presencia de datos dosimétricos al cargar el dashboard o exportar reportes. Si detecta estudios clínicos en la sede sin telemetría, dispara la sincronización automáticamente en segundo plano con caché de 24 horas.
* **Comando de Mamografías:**
  ```bash
  php artisan dosimetria:sync-mamografias
  ```
  Sincroniza y asocia equipos mamográficos con su límite DGM (3.0 mGy) y dosis real por disparo.
* **Comando de Estudios Integrales:**
  ```bash
  php artisan dosimetria:sync-estudios --sede=6
  ```
  Extrae y clasifica estudios de Tomografía, Rayos X Digitales y Digitalizador CR para la sede indicada.

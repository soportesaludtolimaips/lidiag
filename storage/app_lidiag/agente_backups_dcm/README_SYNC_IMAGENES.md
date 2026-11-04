# LIDiag — Agente de Sincronización Continua de Imágenes DICOM a QNAP NAS

Script principal: **`sync_dcm_images.py`**  
Lanzador por lotes: **`ejecutar_sync_imagenes.bat`**  
Tarea programada de Windows: **`LIDiag_Sync_Imagenes_DCM`**  
Frecuencia recomendada: **Continua en segundo plano (escaneo cada 180 segundos / 3 minutos)**  

---

## 1. Descripción y Propósito Clínico

El Agente de Sincronización Continua de Imágenes DICOM replica de forma automática, incremental y transparente todas las imágenes radiológicas (`.dcm`) generadas por las modalidades (Tomógrafos, Resonadores, Mamógrafos, Rayos X) desde el almacenamiento local del PACS dcm4chee (`D:\img_dcm`) hacia el almacenamiento centralizado en la **NAS QNAP** de la sede hospitalaria (`\\<qnap_ip>\<qnap_share_imagenes>`).

### Capacidades Principales:
* **Modelo Dropbox / Google Drive:** Monitoreo persistente en segundo plano. Escanea el árbol de directorios a intervalos regulares y transfiere únicamente los archivos nuevos o no sincronizados.
* **Motor Nativo Robocopy Multi-hilo:** Aprovecha el motor robusto de Windows `robocopy.exe` con soporte multi-hilo (`/MT:8` a `/MT:32`) para máxima tasa de transferencia sin saturar la CPU.
* **Política de Respaldo Seguro (Zero Deletions):** El agente **NUNCA borra** archivos en el servidor de destino (QNAP NAS). Solo replica (`/E /XO /NP`). Si un archivo local se elimina accidentalmente o se purga por falta de espacio, la copia histórica en la NAS permanece intacta.
* **Control de Concurrencia y Lockfile:** Emplea un archivo de bloqueo (`logs/sync_dcm.lock`) para evitar que múltiples instancias simultáneas compitan por recursos o archivos bloqueados.
* **Bitácora Detallada en Vivo (`logs/robocopy_scan.log`):** Permite al administrador observar el avance en tiempo real, archivo por archivo y carpeta por carpeta.
* **Emisión de Telemetría JSON:** Genera `status_sync_{sede}.json` y alimenta `healthcheck_agente.json` para reflejar el estado de la sincronización en LIDiag Central.

---

## 2. Configuración en `config.json`

El agente lee los siguientes parámetros del archivo `config.json`:

```json
{
  "sede_id": 1,
  "sede_nombre": "Espinal",
  "qnap_ip": "10.35.47.21",
  "qnap_user": "sede_espinal",
  "qnap_password": "PASSWORD_QNAP_AQUI",
  "qnap_share_imagenes": "imagenes_espinal",

  "local_images_dir": "D:\\img_dcm",
  "sync_interval_seconds": 180,
  "threads": 8,
  "min_free_space_gb": 10
}
```

### Explicación de Parámetros:
* **`local_images_dir`**: Ruta absoluta al directorio donde el PACS almacena los estudios DICOM locales (típicamente `D:\img_dcm` o `C:\dcm4chee\server\default\archive\data`).
* **`sync_interval_seconds`**: Intervalo de descanso en segundos entre ciclos sucesivos en modo continuo (valor recomendado: 180 s = 3 min).
* **`threads`**: Número de subprocesos paralelos asignados a Robocopy (`8` por defecto, ajustable a `16` o `32` para enlaces 10 GbE).
* **`min_free_space_gb`**: Umbral mínimo de seguridad en disco; si el almacenamiento cae por debajo de este valor, el agente emite una alerta crítica.

---

## 3. Tabla de Argumentos de Línea de Comandos

| Argumento | Tipo | Valor por Defecto | Descripción |
| :--- | :---: | :---: | :--- |
| `--config`, `-c` | `str` | `config.json` | Ruta al archivo de configuración JSON. |
| `--once` | `flag` | `False` | **Pasada Única:** Ejecuta un solo ciclo de sincronización de cambios pendientes y finaliza de inmediato. |
| `--dry-run` | `flag` | `False` | **Modo Simulación:** Utiliza el flag `/L` de Robocopy para listar y cuantificar imágenes pendientes sin copiar ningún archivo. |
| `--threads`, `-t` | `int` | `8` (desde config) | Número de hilos paralelos de Robocopy (`/MT:8` a `/MT:32`). |
| `--interval`, `-i` | `int` | `180` (desde config) | Intervalo de espera en segundos entre ciclos sucesivos en modo continuo. |

---

## 4. Comandos de Ejecución y Pruebas

```powershell
# 1. Servicio continuo persistente (bucle infinito cada sync_interval_seconds)
python sync_dcm_images.py

# O mediante su lanzador por lotes:
.\ejecutar_sync_imagenes.bat

# 2. Pasada única (sincroniza pendientes y finaliza; ideal para pruebas manuales o cron externo)
python sync_dcm_images.py --once

# 3. Modo simulación (escanea el disco y reporta archivos nuevos sin transferirlos)
python sync_dcm_images.py --dry-run

# 4. Acelerar transferencia con 16 o 32 hilos (para redes 10 GbE o cargas masivas iniciales)
python sync_dcm_images.py --threads 16
python sync_dcm_images.py --once --threads 32

# 5. Ajustar intervalo de escaneo continuo a 60 segundos
python sync_dcm_images.py --interval 60

# 6. Ejecutar utilizando configuración alternativa
python sync_dcm_images.py --config C:\ruta\otro_config.json --once
```

---

## 5. Diagnóstico y Monitoreo en Vivo

```powershell
# 1. Monitorear la bitácora principal del ciclo de sincronización:
Get-Content .\logs\sync_imagenes.log -Wait -Tail 30

# 2. Monitorear el escaneo detallado de directorios e imágenes de Robocopy en tiempo real:
Get-Content .\logs\robocopy_scan.log -Wait -Tail 30

# 3. Consultar el estado del último ciclo (archivos copiados, omitidos, fallidos, espacio):
Get-Content .\status_sync_*.json | ConvertFrom-Json | Format-List

# 4. Verificar si hay procesos de Robocopy o Python activos en el sistema:
Get-Process -Name robocopy, python -ErrorAction SilentlyContinue | Format-Table Id, ProcessName, CPU, WorkingSet

# 5. Si el agente quedó bloqueado por un reinicio abrupto, liberar el lockfile:
Remove-Item .\logs\sync_dcm.lock -Force -ErrorAction SilentlyContinue

# 6. Probar conectividad SMB con la NAS QNAP (puertos 139 NetBIOS y 445 SMB):
Test-NetConnection -ComputerName 10.35.47.21 -Port 445

# 7. Probar montaje manual del recurso SMB de imágenes:
net use \\10.35.47.21\imagenes_espinal /user:sede_espinal PASSWORD_QNAP_AQUI
net use \\10.35.47.21\imagenes_espinal /delete /y
```

---

## 6. Integración con el Programador de Tareas (`SYSTEM`)

Para asegurar que la sincronización se inicie automáticamente tras cada reinicio del servidor sin necesidad de iniciar sesión:

```powershell
# Registrar la tarea del sistema (se ejecuta con privilegios de SYSTEM al encender el equipo):
schtasks /create /tn "LIDiag_Sync_Imagenes_DCM" /tr "C:\agente_backups_dcm\ejecutar_sync_imagenes.bat" /sc ONSTART /ru "SYSTEM" /f

# Consultar estado:
schtasks /query /tn "LIDiag_Sync_Imagenes_DCM" /fo TABLE /v

# Iniciar o detener manualmente:
schtasks /run /tn "LIDiag_Sync_Imagenes_DCM"
schtasks /end /tn "LIDiag_Sync_Imagenes_DCM"
```

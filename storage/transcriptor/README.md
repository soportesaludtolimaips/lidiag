# Servicio de Transcripcion de Audio - Lidiag

Servicio HTTP local para transcribir archivos de audio a texto usando **OpenAI Whisper** (modelo `base`) ejecutandose en CPU.

## Requisitos

- Windows Server 2022 o superior
- Python 3.10, 3.11 o 3.12 instalado
- FFmpeg instalado y agregado al PATH del sistema
- Minimo 4GB RAM libre
- NSSM (Non-Sucking Service Manager) para servicio permanente

## Instalacion

### 1. Instalar Python

Descargar desde https://www.python.org/downloads/

> **Importante:** Durante la instalacion marcar **"Add Python to PATH"**.
> Si ya hay otro Python instalado (ej: LibreOffice), se debe usar la ruta completa del Python correcto.

### 2. Instalar FFmpeg

1. Descargar desde https://ffmpeg.org/download.html
2. Extraer los binarios (ej: `C:\ffmpeg\bin\`)
3. Agregar al PATH del sistema:
   ```
   C:\ffmpeg\bin
   ```
4. Verificar: `ffmpeg -version`

### 3. Instalar NSSM (para servicio Windows)

Descargar desde https://nssm.cc/ y agregar al PATH o copiar `nssm.exe` a `C:\Windows\System32\`.

### 4. Instalar dependencias Python

Abrir **cmd como Administrador** y ejecutar:

```cmd
cd /d C:\laragon\www\lidiag\storage\transcriptor
install.bat
```

O manualmente:

```cmd
cd /d C:\laragon\www\lidiag\storage\transcriptor
"C:\ruta\a\python.exe" -m pip install wheel
"C:\ruta\a\python.exe" -m pip install --no-build-isolation -r requirements.txt
```

> **Nota:** Reemplazar `C:\ruta\a\python.exe` con la ruta real de Python en el servidor.
> Ejemplo: `C:\Users\Administrador\AppData\Local\Programs\Python\Python312\python.exe`
>
> Se usa `--no-build-isolation` para que openai-whisper pueda compilarse correctamente con el `setuptools` del sistema.

### 5. Verificar instalacion

```cmd
cd /d C:\laragon\www\lidiag\storage\transcriptor
"C:\ruta\a\python.exe" -c "import fastapi, whisper; print('OK')"
```

## Uso

### Prueba manual

```cmd
cd /d C:\laragon\www\lidiag\storage\transcriptor
start.bat
```

O directamente:

```cmd
cd /d C:\laragon\www\lidiag\storage\transcriptor
"C:\ruta\a\python.exe" main.py
```

El servicio quedara disponible en: `http://localhost:8001`

### Verificar que responde

```cmd
curl http://localhost:8001/health
```

Respuesta esperada:
```json
{"status": "ok", "model_loaded": true}
```

### Instalar como servicio Windows (produccion)

Con NSSM, para que arranque automaticamente con el sistema:

```cmd
nssm install LidiagTranscriptor
nssm set LidiagTranscriptor Application "C:\ruta\a\python.exe"
nssm set LidiagTranscriptor ApplicationParameters main.py
nssm set LidiagTranscriptor AppDirectory "C:\laragon\www\lidiag\storage\transcriptor"
nssm set LidiagTranscriptor DisplayName "Lidiag - Servicio de Transcripcion de Audio"
nssm set LidiagTranscriptor Start SERVICE_AUTO_START
nssm start LidiagTranscriptor
```

Ejemplo con rutas reales:

```cmd
nssm install LidiagTranscriptor
nssm set LidiagTranscriptor Application "C:\Users\Administrador\AppData\Local\Programs\Python\Python312\python.exe"
nssm set LidiagTranscriptor ApplicationParameters main.py
nssm set LidiagTranscriptor AppDirectory "C:\laragon\www\lidiag\storage\transcriptor"
nssm start LidiagTranscriptor
```

### Verificar estado del servicio

```cmd
nssm status LidiagTranscriptor
nssm list
```

### Detener / Reiniciar servicio

```cmd
nssm stop LidiagTranscriptor
nssm restart LidiagTranscriptor
```

### Desinstalar servicio

```cmd
nssm stop LidiagTranscriptor
nssm remove LidiagTranscriptor confirm
```

## Endpoints

### GET /health

Verifica que el servicio esta activo y el modelo cargado.

```json
{
  "status": "ok",
  "model_loaded": true
}
```

### POST /transcribir

Transcribe un archivo de audio a texto.

**Request**: `multipart/form-data` con campo `file`

**Response**:
```json
{
  "texto": "Texto transcrito del audio...",
  "duracion": 45.2,
  "idioma": "es"
}
```

**Formatos soportados**: mp3, wav, m4a, webm, ogg, flac
**Tamano maximo**: 100MB
**Timeout**: 300 segundos (5 minutos)

## Integracion con Laravel

En `.env` del proyecto Laravel:

```
TRANSCRIPTOR_SERVICE_URL=http://localhost:8001
TRANSCRIPTOR_TIMEOUT=300
```

Variables en `config/services.php`:
```php
'transcriptor' => [
    'url' => env('TRANSCRIPTOR_SERVICE_URL', 'http://localhost:8001'),
    'timeout' => env('TRANSCRIPTOR_TIMEOUT', 300),
],
```

## Troubleshooting

### Python no encontrado

```cmd
where python
```

Si apunta a otro Python (ej: LibreOffice), usar la ruta completa del Python correcto.

### FFmpeg no encontrado

```cmd
ffmpeg -version
```

Si no aparece, verificar que la carpeta `bin` de FFmpeg este en el PATH del sistema.

### ModuleNotFoundError: No module named 'fastapi'

Las dependencias se instalaron con un Python distinto al que ejecuta el servicio.

**Solucion:** Usar siempre el mismo ejecutable:
```cmd
"C:\ruta\a\python.exe" -m pip install --no-build-isolation -r requirements.txt
"C:\ruta\a\python.exe" main.py
```

### pkg_resources not found / error compilando openai-whisper

Ocurre con `setuptools` 72+ que eliminaron `pkg_resources`.

**Soluciones:**
1. Usar `pip install --no-build-isolation -r requirements.txt` (recomendado)
2. O instalar `setuptools==69.5.1` antes de instalar dependencias

### El servicio no responde / Puerto ocupado

```cmd
netstat -ano | findstr 8001
```

Si el puerto 8001 esta ocupado por otro proceso, detenerlo o cambiar el puerto en `main.py`.

### "CUDA out of memory"

El servicio usa CPU por defecto. Si tiene GPU NVIDIA, editar `whisper_service.py`:

```python
# Cambiar de:
model = whisper.load_model(self.model_size, device="cpu")
# a:
model = whisper.load_model(self.model_size, device="cuda")
```

### El modelo Whisper consume mucha RAM

El modelo `base` usa ~1GB RAM. Si el servidor tiene recursos limitados, usar el modelo `tiny`:

En `main.py`:
```python
whisper_service = WhisperTranscriptionService(model_size="tiny")
```

### Ver logs del servicio

```cmd
nssm set LidiagTranscriptor AppStdout "C:\laragon\www\lidiag\storage\transcriptor\logs\stdout.log"
nssm set LidiagTranscriptor AppStderr "C:\laragon\www\lidiag\storage\transcriptor\logs\stderr.log"
```

## Archivos del proyecto

| Archivo | Descripcion |
|---------|-------------|
| `main.py` | Servicio FastAPI, endpoints HTTP |
| `whisper_service.py` | Wrapper del modelo Whisper (carga, transcripcion) |
| `requirements.txt` | Dependencias Python |
| `start.bat` | Script para iniciar el servicio manualmente |
| `install.bat` | Script para instalar dependencias |
| `README.md` | Este documento |

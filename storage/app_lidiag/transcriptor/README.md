# Servicio de Transcripcion de Audio - Lidiag

Servicio HTTP local para transcribir archivos de audio a texto usando **OpenAI Whisper** (modelo `base`) ejecutandose en CPU.

---

## Requisitos del servidor

- Windows Server 2022 o superior
- Python 3.10, 3.11 o 3.12 instalado
- FFmpeg instalado (en `C:\ffmpeg\bin` o cualquier ruta accesible)
- Minimo 4GB RAM libre
- Proyecto Lidiag clonado en `C:\laragon\www\lidiag`
- Conexion a internet para descargar dependencias

---

## Paso a paso completo

### Paso 1: Instalar Python

1. Descargar Python 3.12 desde https://www.python.org/downloads/
2. Ejecutar el instalador
3. **IMPORTANTE:** En la primera pantalla marcar **"Add Python to PATH"**
4. Seleccionar **"Install Now"** (instalacion por defecto)
5. Al terminar, abrir **cmd** y verificar:
   ```cmd
   python --version
   ```
   Debe mostrar algo como: `Python 3.12.x`

> **Nota:** Si ya hay otro Python instalado (ej: el que viene con LibreOffice), el comando `python` podria apuntar al equivocado. En ese caso, usar siempre la ruta completa:
> ```
> C:\Users\Administrador\AppData\Local\Programs\Python\Python312\python.exe
> ```

### Paso 2: Instalar FFmpeg

1. Descargar FFmpeg desde https://ffmpeg.org/download.html
   - Elegir la version para Windows (ej: `ffmpeg-release-full.7z`)
2. Extraer el contenido en `C:\ffmpeg`
3. Verificar que existe el ejecutable:
   ```cmd
   dir C:\ffmpeg\bin\ffmpeg.exe
   ```

> **Nota:** No es necesario agregar FFmpeg al PATH del sistema. El servicio lo detecta automaticamente en `C:\ffmpeg\bin\ffmpeg.exe` y lo agrega al PATH del proceso.

### Paso 3: Ubicar el proyecto

El proyecto debe estar en `C:\laragon\www\lidiag`. Si no, clonarlo:

```cmd
cd /d C:\laragon\www\lidiag
```

(Los comandos de los pasos siguientes asumen esta ruta.)

### Paso 4: Configurar la ruta de Python en los scripts

Abrir el archivo `C:\laragon\www\lidiag\storage\transcriptor\iniciar_servicio.bat`

Verificar que la linea `set PYTHON_EXE=` tenga la ruta correcta de Python. Debe verse asi:

```bat
set PYTHON_EXE=C:\Users\Administrador\AppData\Local\Programs\Python\Python312\python.exe
```

Si tu Python esta en otra ruta, ajustarla.

**Para saber donde esta Python:**

```cmd
where python
```

Si muestra `C:\Users\Administrador\AppData\Local\Programs\Python\Python312\python.exe`, esa es la ruta correcta.

Si muestra otra cosa (ej: `C:\Program Files\LibreOffice\program\python.exe`), el Python correcto esta en:

```cmd
dir C:\Users\Administrador\AppData\Local\Programs\Python\Python312\python.exe
```

### Paso 5: Instalar las dependencias Python

Abrir **cmd como Administrador** y ejecutar:

```cmd
cd /d C:\laragon\www\lidiag\storage\transcriptor
install.bat
```

Esto instalara automaticamente:

- `fastapi` - Framework web para el servicio HTTP
- `uvicorn` - Servidor ASGI para ejecutar FastAPI
- `openai-whisper` - Modelo de transcripcion de OpenAI
- `python-multipart` - Soporte para subida de archivos
- `torch` - PyTorch (motor de Whisper)
- `torchaudio` - Audio para PyTorch
- `numpy` - Computacion numerica
- `ffmpeg-python` - Bindings de FFmpeg para Python

**Si install.bat falla**, instalar manualmente:

```cmd
cd /d C:\laragon\www\lidiag\storage\transcriptor
"C:\Users\Administrador\AppData\Local\Programs\Python\Python312\python.exe" -m pip install wheel
"C:\Users\Administrador\AppData\Local\Programs\Python\Python312\python.exe" -m pip install --no-build-isolation -r requirements.txt
```

El flag `--no-build-isolation` es necesario para que openai-whisper se compile usando el `setuptools` del sistema.

### Paso 6: Verificar que las dependencias se instalaron bien

```cmd
"C:\Users\Administrador\AppData\Local\Programs\Python\Python312\python.exe" -c "import fastapi; import whisper; print('Todo OK')"
```

Debe mostrar: `Todo OK`

### Paso 7: Probar el servicio manualmente

```cmd
cd /d C:\laragon\www\lidiag\storage\transcriptor
"C:\Users\Administrador\AppData\Local\Programs\Python\Python312\python.exe" main.py
```

Dejar esta ventana abierta. Abrir **otra ventana de cmd** y verificar:

```cmd
curl http://localhost:8001/health
```

Debe responder:
```json
{"status":"ok","model_loaded":true}
```

Si responde correctamente, volver a la primera ventana y presionar **Ctrl+C** para detener el servicio.

### Paso 8: Configurar el servicio permanente (Programador de Tareas)

Para que el servicio arranque automaticamente al encender el servidor, sin necesidad de que ningun usuario inicie sesion:

**8.1** Verificar que existe el archivo `C:\laragon\www\lidiag\storage\transcriptor\iniciar_servicio.bat`
con el siguiente contenido:

```bat
@echo off
set PYTHON_EXE=C:\Users\Administrador\AppData\Local\Programs\Python\Python312\python.exe
set LOG_FILE=C:\laragon\www\lidiag\storage\transcriptor\servicio.log

echo %date% %time% - Iniciando servicio... >> %LOG_FILE%

cd /d C:\laragon\www\lidiag\storage\transcriptor
if %errorlevel% neq 0 (
    echo %date% %time% - ERROR: No se pudo cambiar al directorio >> %LOG_FILE%
    exit /b 1
)

"%PYTHON_EXE%" main.py >> %LOG_FILE% 2>&1
if %errorlevel% neq 0 (
    echo %date% %time% - ERROR: Codigo %errorlevel% >> %LOG_FILE%
    exit /b 1
)
```

**8.2** En **cmd como Administrador**, registrar la tarea:

```cmd
schtasks /create /tn "LidiagTranscriptor" /tr "C:\laragon\www\lidiag\storage\transcriptor\iniciar_servicio.bat" /sc onstart /ru Administrador /rl highest /f
```

> **IMPORTANTE:** Usar `/ru Administrador` (el usuario que tiene Python instalado).
> NO usar `/ru SYSTEM` porque el usuario SYSTEM no tiene acceso a la carpeta `AppData` del usuario.

**8.3** Verificar que la tarea se creo:

```cmd
schtasks /query /tn "LidiagTranscriptor"
```

Debe mostrar: `Estado: Listo`

### Paso 9: Iniciar el servicio

```cmd
schtasks /run /tn "LidiagTranscriptor"
```

Esperar 5-10 segundos (Whisper tarda en cargar el modelo la primera vez).

### Paso 10: Verificar que el servicio esta corriendo

```cmd
curl http://localhost:8001/health
```

Respuesta esperada:
```json
{"status":"ok","model_loaded":true}
```

Si no responde, revisar el log:

```cmd
type C:\laragon\www\lidiag\storage\transcriptor\servicio.log
```

### Paso 11: Probar la transcripcion desde Laravel

1. Abrir el modulo de **Transcribir Estudios** en Lidiag
2. Seleccionar un estudio pendiente
3. Subir un archivo de audio
4. El sistema debe transcribir el audio a texto automaticamente

---

## Comandos utiles para el dia a dia

### Verificar que el servicio esta activo

```cmd
curl http://localhost:8001/health
```

### Ver logs del servicio

```cmd
type C:\laragon\www\lidiag\storage\transcriptor\servicio.log
```

### Detener el servicio

```cmd
schtasks /end /tn "LidiagTranscriptor"
```

### Iniciar el servicio (despues de detenerlo)

```cmd
schtasks /run /tn "LidiagTranscriptor"
```

### Ver el estado de la tarea programada

```cmd
schtasks /query /tn "LidiagTranscriptor"
```

### Verificar que puerto 8001 esta ocupado

```cmd
netstat -ano | findstr 8001
```

---

## Solucion de problemas

### Error: `python` no encontrado o apunta a LibreOffice

```
where python
```

Si muestra `C:\Program Files\LibreOffice\program\python.exe`, usar la ruta completa del Python 3.12:

```cmd
"C:\Users\Administrador\AppData\Local\Programs\Python\Python312\python.exe"
```

### Error: `No module named 'fastapi'` al ejecutar main.py

Las dependencias se instalaron con un Python distinto al que ejecuta el servicio.

**Solucion:** Usar el mismo ejecutable para instalar y ejecutar:

```cmd
"C:\Users\Administrador\AppData\Local\Programs\Python\Python312\python.exe" -m pip install --no-build-isolation -r requirements.txt
"C:\Users\Administrador\AppData\Local\Programs\Python\Python312\python.exe" main.py
```

### Error: `No module named 'pkg_resources'` al instalar dependencias

Ocurre con versiones modernas de `setuptools` (72+) que eliminaron `pkg_resources`.

**Solucion:** Usar `--no-build-isolation`:

```cmd
pip install --no-build-isolation -r requirements.txt
```

### Error: La tarea programada no inicia el servicio

1. Verificar el log:
   ```cmd
   type C:\laragon\www\lidiag\storage\transcriptor\servicio.log
   ```
2. Verificar que `PYTHON_EXE` en `iniciar_servicio.bat` tenga la ruta correcta
3. Verificar que la tarea se creo con `Administrador` y no con `SYSTEM`:
   ```cmd
   schtasks /query /tn "LidiagTranscriptor" /v | findstr "Usuario"
   ```
4. Si es SYSTEM, eliminar y recrear:
   ```cmd
   schtasks /delete /tn "LidiagTranscriptor" /f
   schtasks /create /tn "LidiagTranscriptor" /tr "C:\laragon\www\lidiag\storage\transcriptor\iniciar_servicio.bat" /sc onstart /ru Administrador /rl highest /f
   ```

### Error: Puerto 8001 ocupado

```cmd
netstat -ano | findstr 8001
```

Si hay otro proceso usando el puerto, cambiar el puerto en `main.py` (linea `uvicorn.run(...)`) y actualizar la variable `TRANSCRIPTOR_SERVICE_URL` en el `.env` de Laravel.

### Error: `[WinError 2] El sistema no puede encontrar el archivo especificado`

Ocurre cuando FFmpeg no es encontrado por el proceso de Python o por Whisper internamente.

**Causas posibles:**

1. FFmpeg no esta instalado en ninguna ruta esperada
2. FFmpeg no esta en `C:\ffmpeg\bin\ffmpeg.exe`

**Solucion:** El servicio busca FFmpeg automaticamente en:
- El PATH del sistema
- `C:\ffmpeg\bin\ffmpeg.exe`
- `C:\ffmpeg\ffmpeg.exe`

Si FFmpeg esta en otra ruta, editar `whisper_service.py` y agregar la ruta en `_find_ffmpeg()`.

Para verificar que el servicio encuentra FFmpeg, revisar el log al iniciar:
```
FFmpeg encontrado en: C:/ffmpeg/bin/ffmpeg.exe
```

### Error: "CUDA out of memory"

El servicio usa CPU por defecto. Si el servidor tiene GPU NVIDIA, editar `whisper_service.py`:

```python
model = whisper.load_model(self.model_size, device="cuda")
```

### El servicio consume mucha RAM

El modelo `base` usa ~1GB RAM. Si el servidor tiene poca memoria, cambiarlo a `tiny`:

En `main.py`:
```python
whisper_service = WhisperTranscriptionService(model_size="tiny")
```

---

## Endpoints del servicio

### GET /health

Verifica que el servicio esta activo.

```json
{"status":"ok","model_loaded":true}
```

### GET /debug

Diagnostico del servicio. Muestra version de Python, ruta de FFmpeg, PATH, etc.

```json
{"python":"3.12.x","ffmpeg_path":"C:/ffmpeg/bin/ffmpeg.exe","ffmpeg_ok":true,"model_loaded":true,...}
```

### POST /transcribir

Transcribe un audio a texto.

- **Request:** `multipart/form-data` con campo `file`
- **Formatos:** mp3, wav, m4a, webm, ogg, flac
- **Tamaño maximo:** 100MB
- **Response:**
  ```json
  {"texto":"texto transcrito","duracion":45.2,"idioma":"es"}
  ```

---

## Integracion con Laravel

En `C:\laragon\www\lidiag\.env`:

```
TRANSCRIPTOR_SERVICE_URL=http://localhost:8001
TRANSCRIPTOR_TIMEOUT=300
```

En `config/services.php`:
```php
'transcriptor' => [
    'url' => env('TRANSCRIPTOR_SERVICE_URL', 'http://localhost:8001'),
    'timeout' => env('TRANSCRIPTOR_TIMEOUT', 300),
],
```

## Archivos del proyecto

| Archivo | Descripcion |
|---------|-------------|
| `main.py` | Servicio FastAPI con endpoints HTTP (`/health`, `/debug`, `/transcribir`) |
| `whisper_service.py` | Carga y ejecuta el modelo Whisper. Detecta FFmpeg automaticamente y lo agrega al PATH |
| `requirements.txt` | Lista de dependencias Python |
| `start.bat` | Inicia el servicio manualmente (cmd interactivo, para pruebas). Muestra mensajes en consola y requiere Ctrl+C para detener |
| `install.bat` | Instala todas las dependencias |
| `iniciar_servicio.bat` | Script para el Programador de Tareas (headless, sin ventana, escribe logs a `servicio.log`). **Este es el que debe usarse para la tarea programada** |
| `servicio.log` | Log de ejecucion del servicio (se crea automaticamente al iniciar) |
| `uploads/` | Directorio temporal para archivos de audio recibidos |
| `test_audio.mp3` | Audio de prueba para verificar la transcripcion |
| `README.md` | Este documento |

# Servicio de Transcripcion de Audio - Lidiag

Servicio HTTP local para transcribir archivos de audio a texto usando **OpenAI Whisper** (modelo `base`) ejecutandose en CPU.

## Requisitos

- Python 3.10 o superior
- FFmpeg instalado en el sistema
- Minimo 4GB RAM libre

## Instalacion

1. Instalar Python 3.10+ desde https://www.python.org/downloads/
2. Instalar FFmpeg:
   - Descargar desde https://ffmpeg.org/download.html
   - Agregar `bin/` al PATH del sistema
3. Ejecutar `install.bat`

## Uso

### Iniciar el servicio

```
start.bat
```

El servicio quedara disponible en: `http://localhost:8001`

### Endpoints

#### GET /health

Verifica que el servicio esta activo.

```json
{
  "status": "ok",
  "model_loaded": true
}
```

#### POST /transcribir

Transcribe un archivo de audio.

**Request**: multipart/form-data con campo `file`

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

## Integracion con Laravel

En `.env` de Laravel:
```
TRANSCRIPTOR_SERVICE_URL=http://localhost:8001
```

## Modelos disponibles

| Modelo   | RAM   | Velocidad | Precision |
|----------|-------|-----------|-----------|
| tiny     | ~1GB  | Muy rapida| Baja      |
| base     | ~1GB  | Rapida    | Media     |
| small    | ~2GB  | Media     | Alta      |
| medium   | ~5GB  | Lenta     | Muy alta  |
| large    | ~10GB | Muy lenta | Maxima    |

Para cambiar el modelo, editar `main.py`:
```python
whisper_service = WhisperTranscriptionService(model_size="small")
```

## Produccion (Windows Server)

Para ejecutar como servicio de Windows:

1. Instalar NSSM: https://nssm.cc/
2. Ejecutar:
   ```
   nssm install LidiagTranscriptor
   nssm set LidiagTranscriptor Application C:\path\to\python.exe
   nssm set LidiagTranscriptor ApplicationParameters main.py
   nssm set LidiagTranscriptor AppDirectory C:\laragon\www\lidiag\storage\transcriptor
   nssm start LidiagTranscriptor
   ```

## Troubleshooting

### "Python no encontrado"
- Verifique que Python esta en el PATH: `python --version`

### "FFmpeg no encontrado"
- Verifique que FFmpeg esta en el PATH: `ffmpeg -version`

### "CUDA out of memory"
- El servicio usa CPU por defecto. Si tiene GPU, edite `whisper_service.py` y cambie `device="cpu"` a `device="cuda"`.

### El servicio no responde
- Verifique logs en la consola
- Verifique que el puerto 8001 no este en uso: `netstat -ano | findstr 8001`

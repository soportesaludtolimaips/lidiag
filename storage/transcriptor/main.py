"""
Servicio de Transcripcion de Audio - Lidiag
FastAPI + OpenAI Whisper (CPU)

Uso:
    python main.py
    o
    uvicorn main:app --host 0.0.0.0 --port 8001

Endpoints:
    POST /transcribir - Transcribe un archivo de audio
    GET /health - Verifica que el servicio esta activo
"""

import os
import logging
import shutil
from pathlib import Path
from fastapi import FastAPI, File, UploadFile, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel

from whisper_service import WhisperTranscriptionService

# Configuracion
UPLOAD_DIR = Path(__file__).parent / "uploads"
UPLOAD_DIR.mkdir(exist_ok=True)

ALLOWED_EXTENSIONS = {".mp3", ".wav", ".m4a", ".webm", ".ogg", ".flac"}
MAX_FILE_SIZE = 100 * 1024 * 1024  # 100MB

# Logging
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s - %(name)s - %(levelname)s - %(message)s",
)
logger = logging.getLogger(__name__)

# FastAPI app
app = FastAPI(
    title="Lidiag Audio Transcription Service",
    description="Servicio de transcripcion de audio usando Whisper (CPU)",
    version="1.0.0",
)

# CORS - permitir peticiones desde Laravel
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Servicio Whisper (singleton)
whisper_service = WhisperTranscriptionService(model_size="base")


class TranscriptionResponse(BaseModel):
    texto: str
    duracion: float
    idioma: str


class HealthResponse(BaseModel):
    status: str
    model_loaded: bool


@app.on_event("startup")
async def startup_event():
    """Precarga el modelo al iniciar el servicio."""
    logger.info("Iniciando servicio de transcripcion...")
    try:
        whisper_service.load_model()
        logger.info("Servicio listo para transcribir.")
    except Exception as e:
        logger.error(f"Error cargando modelo: {e}")


@app.get("/health", response_model=HealthResponse)
async def health_check():
    """Verifica que el servicio esta activo."""
    return HealthResponse(
        status="ok",
        model_loaded=whisper_service.model is not None,
    )


@app.post("/transcribir", response_model=TranscriptionResponse)
async def transcribir_audio(file: UploadFile = File(...)):
    """
    Transcribe un archivo de audio a texto.

    - Formatos soportados: mp3, wav, m4a, webm, ogg, flac
    - Tamano maximo: 100MB
    """
    # Validar extension
    file_ext = Path(file.filename).suffix.lower()
    if file_ext not in ALLOWED_EXTENSIONS:
        raise HTTPException(
            status_code=400,
            detail=f"Formato no soportado: {file_ext}. Permitidos: {', '.join(ALLOWED_EXTENSIONS)}",
        )

    # Leer archivo
    try:
        content = await file.read()
    except Exception as e:
        raise HTTPException(status_code=400, detail=f"Error leyendo archivo: {str(e)}")

    # Validar tamano
    if len(content) == 0:
        raise HTTPException(status_code=400, detail="El archivo esta vacio")

    if len(content) > MAX_FILE_SIZE:
        raise HTTPException(
            status_code=413,
            detail=f"Archivo demasiado grande. Maximo: 100MB",
        )

    # Guardar temporalmente
    temp_path = UPLOAD_DIR / f"temp_{file.filename}"
    try:
        with open(temp_path, "wb") as f:
            f.write(content)

        logger.info(f"Transcribiendo: {file.filename} ({len(content)} bytes)")

        # Transcribir
        result = whisper_service.transcribe(temp_path, language="es")

        logger.info(f"Transcripcion completada: {len(result['texto'])} caracteres")

        return TranscriptionResponse(
            texto=result["texto"],
            duracion=result["duracion"],
            idioma=result["idioma"],
        )

    except Exception as e:
        logger.error(f"Error en transcripcion: {str(e)}")
        raise HTTPException(status_code=500, detail=f"Error transcribiendo audio: {str(e)}")

    finally:
        # Limpiar archivo temporal
        if temp_path.exists():
            try:
                os.unlink(temp_path)
            except Exception:
                pass


if __name__ == "__main__":
    import uvicorn

    uvicorn.run(
        "main:app",
        host="0.0.0.0",
        port=8001,
        reload=False,
        log_level="info",
    )

import whisper
import tempfile
import os
import shutil
import subprocess
import logging

logger = logging.getLogger(__name__)


class WhisperTranscriptionService:
    """Servicio de transcripcion usando OpenAI Whisper local."""

    def __init__(self, model_size: str = "base"):
        """
        Inicializa el modelo Whisper.

        Args:
            model_size: Tamano del modelo ('tiny', 'base', 'small', 'medium', 'large')
                       'base' es buen balance entre precision y velocidad en CPU.
        """
        self.model_size = model_size
        self.model = None
        self.ffmpeg_path = self._find_ffmpeg()
        self._ensure_ffmpeg_in_path()

    def _find_ffmpeg(self) -> str:
        """Busca ffmpeg en el PATH o en rutas comunes de instalacion."""
        ffmpeg = shutil.which("ffmpeg")
        if ffmpeg:
            logger.info(f"FFmpeg encontrado en: {ffmpeg}")
            return ffmpeg

        rutas_comunes = [
            "C:/ffmpeg/bin/ffmpeg.exe",
            "C:/ffmpeg/ffmpeg.exe",
            os.path.expandvars("%USERPROFILE%/ffmpeg/bin/ffmpeg.exe"),
            os.path.expandvars("%LOCALAPPDATA%/ffmpeg/bin/ffmpeg.exe"),
        ]
        for ruta in rutas_comunes:
            if os.path.exists(ruta):
                logger.info(f"FFmpeg encontrado en: {ruta}")
                return ruta

        logger.warning("FFmpeg no encontrado. El servicio fallara al transcribir.")
        return "ffmpeg"

    def _ensure_ffmpeg_in_path(self):
        """Agrega el directorio de FFmpeg al PATH para que Whisper lo encuentre internamente."""
        ffmpeg_dir = os.path.dirname(self.ffmpeg_path)
        if ffmpeg_dir and os.path.isdir(ffmpeg_dir):
            current_path = os.environ.get("PATH", "")
            if ffmpeg_dir not in current_path.split(os.pathsep):
                os.environ["PATH"] = ffmpeg_dir + os.pathsep + current_path
                logger.info(f"FFmpeg agregado al PATH: {ffmpeg_dir}")

    @property
    def ffmpeg_ok(self) -> bool:
        return shutil.which("ffmpeg") is not None or os.path.exists(self.ffmpeg_path)

    def load_model(self):
        """Carga el modelo Whisper en memoria."""
        if self.model is None:
            logger.info(f"Cargando modelo Whisper '{self.model_size}'...")
            self.model = whisper.load_model(self.model_size, device="cpu")
            logger.info(f"Modelo '{self.model_size}' cargado exitosamente.")

    def _convert_to_wav(self, audio_path: str) -> str:
        """
        Convierte cualquier formato de audio a WAV 16kHz mono (optimo para Whisper).

        Args:
            audio_path: Ruta del archivo de audio original.

        Returns:
            Ruta del archivo WAV temporal.
        """
        temp_wav = tempfile.NamedTemporaryFile(suffix=".wav", delete=False)
        temp_wav.close()

        try:
            result = subprocess.run(
                [
                    self.ffmpeg_path,
                    "-y",
                    "-i", audio_path,
                    "-acodec", "pcm_s16le",
                    "-ar", "16000",
                    "-ac", "1",
                    temp_wav.name,
                ],
                capture_output=True,
                text=True,
                timeout=300,
            )

            if result.returncode != 0:
                raise Exception(f"FFmpeg error: {result.stderr}")

            return temp_wav.name

        except subprocess.TimeoutExpired:
            raise Exception("La conversion de audio tardo demasiado (timeout 5min)")
        except Exception as e:
            if os.path.exists(temp_wav.name):
                os.unlink(temp_wav.name)
            raise e

    def transcribe(
        self,
        audio_path: str,
        language: str = "es",
        task: str = "transcribe",
    ) -> dict:
        """
        Transcribe un archivo de audio a texto.

        Args:
            audio_path: Ruta del archivo de audio.
            language: Codigo de idioma ('es' para espanol).
            task: 'transcribe' o 'translate'.

        Returns:
            dict con:
                - texto: Texto transcrito completo
                - duracion: Duracion del audio en segundos
                - idioma: Idioma detectado
        """
        self.load_model()

        wav_path = None
        try:
            wav_path = self._convert_to_wav(audio_path)

            result = self.model.transcribe(
                wav_path,
                language=language,
                task=task,
                fp16=False,
            )

            texto = result.get("text", "").strip()

            return {
                "texto": texto,
                "duracion": result.get("segments", [{}])[-1].get("end", 0) if result.get("segments") else 0,
                "idioma": result.get("language", language),
            }

        except Exception as e:
            logger.error(f"Error transcribiendo {audio_path}: {str(e)}")
            raise
        finally:
            if wav_path and os.path.exists(wav_path):
                os.unlink(wav_path)

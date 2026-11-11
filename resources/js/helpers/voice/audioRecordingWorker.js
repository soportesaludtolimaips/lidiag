/**
 * Audio Recording Web Worker - Lidiag Dragon
 * Web Worker dedicado para grabación de audio continua
 * Funciona en hilo separado para evitar pausas al cambiar de pestaña
 * Uso:
 *   const worker = new AudioRecordingWorker();
 *   worker.start();
 *   worker.pause();
 *   worker.resume();
 *   worker.stop();
 */

const ctx = self;
let mediaRecorder = null;
let stream = null;
let chunks = [];
let isRecording = false;
let isPaused = false;
let chunkCounter = 0;
let sessionId = null;
let resumeTimeoutId = null;

/**
 * Devuelve el tipo MIME de audio soportado por MediaRecorder, en orden de preferencia.
 *
 * @returns {string}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-05-06
 */
function getMimeType() {
    const types = [
        "audio/wav",
        "audio/webm;codecs=opus",
        "audio/webm",
        "audio/ogg;codecs=opus",
        "audio/ogg",
        "audio/mp3",
    ];

    for (const type of types) {
        if (MediaRecorder.isTypeSupported(type)) {
            return type;
        }
    }
    return "audio/webm";
}

/**
 * Inicia la grabacion de audio en el Worker solicitando el microfono y configurando MediaRecorder.
 *
 * @returns {object}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-05-06
 */
async function iniciarGrabacion() {
    if (isRecording) return { success: false, error: "ya_esta_grabando" };

    try {
        const constraints = {
            audio: {
                echoCancellation: true,
                noiseSuppression: true,
                autoGainControl: true,
                sampleRate: 16000,
            },
            video: false,
        };

        stream = await navigator.mediaDevices.getUserMedia(constraints);

        const mimeType = getMimeType();

        try {
            mediaRecorder = new MediaRecorder(stream, {
                mimeType: mimeType,
                audioBitsPerSecond: 64000,
            });
        } catch {
            mediaRecorder = new MediaRecorder(stream);
        }

        chunks = [];

        // Escuchar eventos de estado del MediaRecorder
        mediaRecorder.onpause = () => {
            console.log("[Worker] MediaRecorder pausado - intentanto reanudar");
            // Auto-reanudar después de un delay pequeño
            clearTimeout(resumeTimeoutId);
            resumeTimeoutId = setTimeout(() => {
                if (mediaRecorder && mediaRecorder.state === "paused" && isRecording && !isPaused) {
                    try {
                        mediaRecorder.resume();
                        console.log("[Worker] MediaRecorder auto-reanudado");
                    } catch (e) {
                        console.log("[Worker] Error auto-reanudando:", e);
                    }
                }
            }, 500);
        };

        mediaRecorder.onresume = () => {
            console.log("[Worker] MediaRecorder reanudado");
            clearTimeout(resumeTimeoutId);
        };

        mediaRecorder.onstart = () => {
            console.log("[Worker] MediaRecorder iniciado");
        };

        mediaRecorder.onstop = () => {
            console.log("[Worker] MediaRecorder detenido");
        };

        mediaRecorder.ondataavailable = (event) => {
            if (event.data && event.data.size > 0) {
                chunks.push(event.data);

                const blob = new Blob([event.data], { type: mimeType });

                ctx.postMessage({
                    type: "chunk",
                    chunk: blob,
                    chunkNum: chunkCounter++,
                    timestamp: Date.now(),
                });
            }
        };

        mediaRecorder.start(3000);
        isRecording = true;
        isPaused = false;

        sessionId = `session_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;

        ctx.postMessage({
            type: "started",
            sessionId: sessionId,
            mimeType: mimeType,
        });

        return { success: true, sessionId: sessionId, mimeType: mimeType };
    } catch (error) {
        return { success: false, error: error.message };
    }
}

/**
 * Pausa la grabacion de audio en curso y notifica el cambio de estado.
 *
 * @returns {object}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-05-06
 */
function pausarGrabacion() {
    if (!isRecording || isPaused) return { success: false };

    mediaRecorder?.pause();
    isPaused = true;

    ctx.postMessage({ type: "paused" });
    return { success: true };
}

/**
 * Reanuda la grabacion de audio previamente pausada y notifica el cambio de estado.
 *
 * @returns {object}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-05-06
 */
function reanudarGrabacion() {
    if (!isRecording || !isPaused) return { success: false };

    mediaRecorder?.resume();
    isPaused = false;

    ctx.postMessage({ type: "resumed" });
    return { success: true };
}

/**
 * Detiene la grabacion, libera las pistas del microfono y devuelve el blob de audio resultante.
 *
 * @returns {Promise<object>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-05-06
 */
async function detenerGrabacion() {
    if (!isRecording) return { success: false };

    return new Promise((resolve) => {
        mediaRecorder.onstop = () => {
            const mimeType = getMimeType();
            const blob = new Blob(chunks, { type: mimeType });

            if (stream) {
                stream.getTracks().forEach((track) => track.stop());
            }

            const result = {
                success: true,
                audio: blob,
                sessionId: sessionId,
                duration: blob.size,
                chunksCount: chunks.length,
            };

            chunks = [];
            isRecording = false;
            isPaused = false;
            mediaRecorder = null;
            sessionId = null;

            resolve(result);
        };

        mediaRecorder?.stop();

        ctx.postMessage({ type: "stopped" });
    });
}

/**
 * Devuelve el estado actual de la grabacion (grabando, pausado, sesion y conteo de fragmentos).
 *
 * @returns {object}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-05-06
 */
function obtenerEstado() {
    return {
        isRecording: isRecording,
        isPaused: isPaused,
        sessionId: sessionId,
        chunksCount: chunks.length,
    };
}

/**
 * Maneja los mensajes entrantes del hilo principal y ejecuta la accion de grabacion correspondiente.
 *
 * @param object $event Evento del mensaje con la accion y los datos.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-05-06
 */
ctx.onmessage = async (event) => {
    const { action } = event.data;

    let result;

    switch (action) {
        case "start":
            result = await iniciarGrabacion();
            break;

        case "pause":
            result = pausarGrabacion();
            break;

        case "resume":
            result = reanudarGrabacion();
            break;

        case "stop":
            result = await detenerGrabacion();
            break;

        case "status":
            result = obtenerEstado();
            break;

        default:
            result = { success: false, error: "accion_desconocida" };
    }

    ctx.postMessage({ action: action, result: result });
};

ctx.postMessage({ type: "ready" });

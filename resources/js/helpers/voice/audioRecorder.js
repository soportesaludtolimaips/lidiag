/**
 * Audio recorder helper (sin librerías externas).
 * Reutilizable en cualquier componente Vue.
 *
 * Uso:
 *   const session = await createAudioRecorderSession();
 *   session.start();
 *   const { blob, url } = await session.stop();
 *   session.cleanup();
 */

export function getVoiceAudioConstraints() {
    return {
        audio: {
            echoCancellation: true,
            noiseSuppression: true,
            autoGainControl: true,
        },
    };
}

export function isGetUserMediaSupported() {
    return !!(
        navigator &&
        navigator.mediaDevices &&
        typeof navigator.mediaDevices.getUserMedia === "function"
    );
}

export async function createAudioRecorderSession({
    constraints = getVoiceAudioConstraints(),
    mimeType = "audio/wav",
    audioBitsPerSecond = 64000,
} = {}) {
    if (!isGetUserMediaSupported()) {
        throw new Error("getUserMedia no soportado");
    }

    const stream = await navigator.mediaDevices.getUserMedia(constraints);

    let mediaRecorder;
    try {
        mediaRecorder = new MediaRecorder(stream, { mimeType, audioBitsPerSecond });
    } catch (e) {
        // Fallback (por compatibilidad de mimeType)
        mediaRecorder = new MediaRecorder(stream);
    }

    const chunks = [];
    mediaRecorder.ondataavailable = (event) => {
        if (event && event.data && event.data.size) chunks.push(event.data);
    };

    const start = () => {
        mediaRecorder.start();
    };

    const pause = () => {
        if (mediaRecorder.state === "recording") mediaRecorder.pause();
    };

    const resume = () => {
        if (mediaRecorder.state === "paused") mediaRecorder.resume();
    };

    const stop = () =>
        new Promise((resolve) => {
            if (!mediaRecorder || mediaRecorder.state === "inactive") {
                resolve({ blob: null, url: null, chunks });
                return;
            }
            mediaRecorder.onstop = () => {
                const blob = new Blob(chunks, { type: "audio/webm" });
                const url = URL.createObjectURL(blob);
                resolve({ blob, url, chunks });
            };
            mediaRecorder.stop();
        });

    const cleanup = () => {
        try {
            if (stream && stream.getTracks) stream.getTracks().forEach((t) => t.stop());
        } catch (e) {}
    };

    return { stream, mediaRecorder, chunks, start, pause, resume, stop, cleanup };
}

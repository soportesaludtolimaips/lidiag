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

import { createAudioRecorderWorkerSession, isWorkerSupported } from "./audioRecorderWorkerSession";

export async function createAudioRecorderSessionSmart(opts = {}) {
    if (isWorkerSupported()) {
        try {
            return await createAudioRecorderWorkerSession(opts);
        } catch (e) {
            // fallback a main thread
        }
    }
    return createAudioRecorderSession(opts);
}

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

    let autoResumeTimer = null;
    let isRecording = false;
    mediaRecorder.onpause = () => {
        clearTimeout(autoResumeTimer);
        autoResumeTimer = setTimeout(() => {
            if (mediaRecorder && mediaRecorder.state === "paused" && isRecording) {
                mediaRecorder.resume();
            }
        }, 500);
    };

    const start = () => {
        isRecording = true;
        mediaRecorder.start();
    };

    const pause = () => {
        if (mediaRecorder.state === "recording") mediaRecorder.pause();
    };

    const resume = () => {
        if (mediaRecorder.state === "paused") mediaRecorder.resume();
    };

    let lastUrl = null;

    const stop = () =>
        new Promise((resolve) => {
            isRecording = false;
            clearTimeout(autoResumeTimer);
            if (!mediaRecorder || mediaRecorder.state === "inactive") {
                resolve({ blob: null, url: null, chunks, revoke: () => {} });
                return;
            }
            mediaRecorder.onstop = () => {
                const blob = new Blob(chunks, { type: "audio/webm" });
                const url = URL.createObjectURL(blob);
                lastUrl = url;
                resolve({
                    blob,
                    url,
                    chunks,
                    revoke: () => {
                        if (lastUrl) {
                            URL.revokeObjectURL(lastUrl);
                            lastUrl = null;
                        }
                    },
                });
            };
            mediaRecorder.stop();
        });

    const cleanup = () => {
        isRecording = false;
        clearTimeout(autoResumeTimer);
        try {
            if (stream && stream.getTracks) stream.getTracks().forEach((t) => t.stop());
        } catch (e) {}
        if (lastUrl) {
            URL.revokeObjectURL(lastUrl);
            lastUrl = null;
        }
    };

    return { stream, mediaRecorder, chunks, start, pause, resume, stop, cleanup };
}

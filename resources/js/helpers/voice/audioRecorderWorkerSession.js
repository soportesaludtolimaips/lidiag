import { getVoiceAudioConstraints } from "./audioRecorder";

/**
 * Indica si el entorno soporta Web Workers y MediaRecorder para grabar en un hilo separado.
 *
 * @returns {boolean}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-06-11
 */
export function isWorkerSupported() {
    try {
        if (typeof Worker === "undefined") return false;
        if (typeof MediaRecorder === "undefined") return false;
        return true;
    } catch {
        return false;
    }
}

/**
 * Crea una sesion de grabacion de audio delegada a un Web Worker, con los mismos controles que la sesion local.
 *
 * @param object $opts Opciones (constraints, mimeType, audioBitsPerSecond).
 *
 * @returns {object}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-06-11
 */
export async function createAudioRecorderWorkerSession({
    _constraints = getVoiceAudioConstraints(),
    _mimeType = "audio/wav",
    _audioBitsPerSecond = 64000,
} = {}) {
    const worker = new Worker(new URL("./audioRecordingWorker.js", import.meta.url));

    let recorderState = "inactive";
    let chunkList = [];
    let resolveStop = null;
    let lastUrl = null;

    worker.onmessage = (e) => {
        const msg = e.data;

        if (msg.type === "chunk" && msg.chunk) {
            chunkList.push(msg.chunk);
            return;
        }

        if (msg.type === "started") {
            recorderState = "recording";
            return;
        }

        if (msg.action === "start" && msg.result && !msg.result.success) {
            recorderState = "inactive";
            return;
        }

        if (msg.type === "paused") {
            recorderState = "paused";
            return;
        }

        if (msg.type === "resumed") {
            recorderState = "recording";
            return;
        }

        if (msg.action === "stop" && msg.result) {
            recorderState = "inactive";
            const blob = msg.result.audio || null;
            if (resolveStop) {
                const url = blob ? URL.createObjectURL(blob) : null;
                lastUrl = url;
                resolveStop({
                    blob,
                    url,
                    chunks: [...chunkList],
                    revoke: () => {
                        if (lastUrl) {
                            URL.revokeObjectURL(lastUrl);
                            lastUrl = null;
                        }
                    },
                });
            }
            return;
        }
    };

    const streamMock = {
        getTracks() {
            return [{ stop() {} }];
        },
    };

    const mediaRecorderMock = {
        get state() {
            return recorderState;
        },
        stop() {},
        pause() {},
        resume() {},
    };

    return {
        stream: streamMock,
        mediaRecorder: mediaRecorderMock,
        get chunks() {
            return chunkList;
        },

        start() {
            return new Promise((resolve, reject) => {
                recorderState = "recording";
                const onMsg = (e) => {
                    const msg = e.data;
                    if (msg.action === "start" && msg.result) {
                        worker.removeEventListener("message", onMsg);
                        if (msg.result.success) resolve();
                        else
                            reject(
                                new Error(
                                    msg.result.error || "Error al iniciar grabación en Worker"
                                )
                            );
                    }
                };
                worker.addEventListener("message", onMsg);
                worker.postMessage({ action: "start" });
            });
        },

        pause() {
            recorderState = "paused";
            worker.postMessage({ action: "pause" });
        },

        resume() {
            recorderState = "recording";
            worker.postMessage({ action: "resume" });
        },

        stop() {
            return new Promise((resolve) => {
                resolveStop = resolve;
                const timer = setTimeout(() => {
                    if (resolveStop) {
                        resolveStop({
                            blob: null,
                            url: null,
                            chunks: [...chunkList],
                            revoke: () => {},
                        });
                        resolveStop = null;
                    }
                }, 5000);
                const originalResolve = resolve;
                const wrappedResolve = (val) => {
                    clearTimeout(timer);
                    originalResolve(val);
                };
                resolveStop = wrappedResolve;
                worker.postMessage({ action: "stop" });
            });
        },

        cleanup() {
            try {
                worker.terminate();
            } catch {
                /* ignore */
            }
            if (lastUrl) {
                URL.revokeObjectURL(lastUrl);
                lastUrl = null;
            }
            chunkList = [];
        },
    };
}

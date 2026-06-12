import { getVoiceAudioConstraints } from "./audioRecorder";

export function isWorkerSupported() {
    try {
        if (typeof Worker === "undefined") return false;
        if (typeof MediaRecorder === "undefined") return false;
        return true;
    } catch (e) {
        return false;
    }
}

export async function createAudioRecorderWorkerSession({
    constraints = getVoiceAudioConstraints(),
    mimeType = "audio/wav",
    audioBitsPerSecond = 64000,
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
                resolveStop({ blob, url, chunks: [...chunkList], revoke: () => { if (lastUrl) { URL.revokeObjectURL(lastUrl); lastUrl = null; } } });
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
        get state() { return recorderState; },
    };

    return {
        stream: streamMock,
        mediaRecorder: mediaRecorderMock,
        get chunks() { return chunkList; },

        start() {
            recorderState = "recording";
            worker.postMessage({ action: "start" });
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
                worker.postMessage({ action: "stop" });
            });
        },

        cleanup() {
            try { worker.terminate(); } catch (e) { /* ignore */ }
            if (lastUrl) { URL.revokeObjectURL(lastUrl); lastUrl = null; }
            chunkList = [];
        },
    };
}

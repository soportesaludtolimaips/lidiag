/**
 * Orquestador de voz (sin librerías externas).
 * Combina dictado (SpeechRecognition) + grabación (MediaRecorder) en una sola sesión.
 * Diseñado para ser reutilizable en múltiples componentes sin tocar UI.
 */
import {
    createAudioRecorderSession,
    createAudioRecorderSessionSmart,
    getVoiceAudioConstraints,
} from "./audioRecorder";
import { createSpeechDictation, isSpeechRecognitionSupported } from "./speechDictation";

/**
 * Crea una sesion de voz que combina dictado (SpeechRecognition) y grabacion (MediaRecorder) con controles unificados.
 *
 * @param object $options Opciones de audio, dictado y callbacks.
 *
 * @returns {object}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-02-13
 */
export function createVoiceSession({
    enableAudio = true,
    enableDictation = true,
    // Audio
    constraints = getVoiceAudioConstraints(),
    mimeType = "audio/wav",
    audioBitsPerSecond = 64000,

    // Dictado
    lang = "es-ES",
    grammarCommands = [],
    maxNoSpeechRetries = 6,

    // Callbacks
    onFinalText = () => {},
    onInterimText = () => {},
    onPermissionDenied = () => {},
    onError = () => {},
    onState = () => {}, // ({ recognizing, active, lastError })
} = {}) {
    let audioSession = null;
    let dictation = null;

    /**
     * Crea o retorna la instancia de dictado de voz, lanzando error si no es soportada.
     *
     * @returns {object|null}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-02-13
     */
    const ensureDictation = () => {
        if (!enableDictation) return null;
        if (dictation) return dictation;
        if (!isSpeechRecognitionSupported()) {
            throw new Error("SpeechRecognition no soportado");
        }
        dictation = createSpeechDictation({
            lang,
            continuous: true,
            interimResults: true,
            maxAlternatives: 1,
            maxNoSpeechRetries,
            baseBackoffMs: 500,
            maxBackoffMs: 8000,
            grammarCommands,
            onFinalText,
            onInterimText,
            onPermissionDenied,
            onError,
            onState,
        });
        return dictation;
    };

    /**
     * Crea o retorna la sesion de grabacion de audio, usando Worker con fallback al hilo principal.
     *
     * @returns {Promise<object|null>}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-02-13
     */
    const ensureAudio = async () => {
        if (!enableAudio) return null;
        if (audioSession) return audioSession;
        audioSession = await createAudioRecorderSessionSmart({
            constraints,
            mimeType,
            audioBitsPerSecond,
        });
        return audioSession;
    };

    const start = async () => {
        const d = ensureDictation();
        if (d) d.start();
        const a = await ensureAudio();
        if (a) {
            try {
                await a.start();
            } catch {
                // Worker interno falló → reintenta con main thread
                try {
                    a.cleanup();
                } catch {}
                const fallback = await createAudioRecorderSession({
                    constraints,
                    mimeType,
                    audioBitsPerSecond,
                });
                fallback.start();
                audioSession = fallback;
                return { audioSession: fallback, dictation: d };
            }
        }
        return { audioSession: a, dictation: d };
    };

    const pause = () => {
        try {
            if (audioSession) audioSession.pause();
        } catch {}
        try {
            if (dictation) dictation.pause();
        } catch {}
    };

    const resume = () => {
        try {
            if (audioSession) audioSession.resume();
        } catch {}
        try {
            if (dictation) dictation.resume();
        } catch {}
    };

    const stopAudio = async () => {
        if (!enableAudio || !audioSession) return { blob: null, url: null, chunks: [] };
        return await audioSession.stop();
    };

    const stopDictation = () => {
        try {
            if (dictation) dictation.stop();
        } catch {}
    };

    const cleanup = () => {
        try {
            if (audioSession) audioSession.cleanup();
        } catch {}
    };

    const destroy = () => {
        stopDictation();
        cleanup();
        audioSession = null;
        dictation = null;
    };

    return {
        start,
        pause,
        resume,
        stopAudio,
        stopDictation,
        cleanup,
        destroy,
        getAudioSession: () => audioSession,
        getDictation: () => dictation,
    };
}

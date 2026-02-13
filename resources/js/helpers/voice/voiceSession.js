/**
 * Orquestador de voz (sin librerías externas).
 * Combina dictado (SpeechRecognition) + grabación (MediaRecorder) en una sola sesión.
 *
 * Diseñado para ser reutilizable en múltiples componentes sin tocar UI.
 */
import { createAudioRecorderSession, getVoiceAudioConstraints } from "./audioRecorder";
import { createSpeechDictation, isSpeechRecognitionSupported } from "./speechDictation";

export function createVoiceSession({
    enableAudio = true,
    enableDictation = true,
    // Audio
    constraints = getVoiceAudioConstraints(),
    mimeType = "audio/webm;codecs=opus",
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

    const ensureAudio = async () => {
        if (!enableAudio) return null;
        if (audioSession) return audioSession;
        audioSession = await createAudioRecorderSession({
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
        if (a) a.start();
        return { audioSession: a, dictation: d };
    };

    const pause = () => {
        try {
            if (audioSession) audioSession.pause();
        } catch (e) {}
        try {
            if (dictation) dictation.pause();
        } catch (e) {}
    };

    const resume = () => {
        try {
            if (audioSession) audioSession.resume();
        } catch (e) {}
        try {
            if (dictation) dictation.resume();
        } catch (e) {}
    };

    const stopAudio = async () => {
        if (!enableAudio || !audioSession) return { blob: null, url: null, chunks: [] };
        return await audioSession.stop();
    };

    const stopDictation = () => {
        try {
            if (dictation) dictation.stop();
        } catch (e) {}
    };

    const cleanup = () => {
        try {
            if (audioSession) audioSession.cleanup();
        } catch (e) {}
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

/**
 * Speech dictation helper (sin librerías externas).
 * Envuelve SpeechRecognition con reinicio seguro y backoff por no-speech.
 * Nota: depende de API nativa del navegador (Chrome/Edge).
 */

/**
 * Obtiene el constructor de SpeechRecognition disponible en el navegador o null si no existe.
 *
 * @returns {function|null}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-02-13
 */
function getSpeechRecognitionCtor() {
    return (
        window.SpeechRecognition ||
        window.webkitSpeechRecognition ||
        window.mozSpeechRecognition ||
        null
    );
}

/**
 * Indica si el navegador soporta la API de SpeechRecognition (Chrome/Edge).
 *
 * @returns {boolean}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-02-13
 */
export function isSpeechRecognitionSupported() {
    return !!getSpeechRecognitionCtor();
}

/**
 * Crea una instancia de dictado por voz con reinicio seguro y backoff ante silencio, envuelta sobre SpeechRecognition.
 *
 * @param object $options Opciones (lang, continuous, interimResults, callbacks, etc.).
 *
 * @returns {object}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-02-13
 */
export function createSpeechDictation({
    lang = "es-ES",
    continuous = true,
    interimResults = true,
    maxAlternatives = 1,
    maxNoSpeechRetries = 6,
    baseBackoffMs = 500,
    maxBackoffMs = 8000,
    onFinalText = () => {},
    onInterimText = () => {},
    onPermissionDenied = () => {},
    onError = () => {},
    onState = () => {}, // ({ recognizing, active, lastError })
    grammarCommands = null, // array<string> (best-effort)
} = {}) {
    const Ctor = getSpeechRecognitionCtor();
    if (!Ctor) throw new Error("SpeechRecognition no soportado");

    const recognition = new Ctor();
    recognition.lang = lang;
    recognition.continuous = !!continuous;
    recognition.interimResults = !!interimResults;
    recognition.maxAlternatives = maxAlternatives;

    // Gramática opcional para mejorar comandos (best-effort)
    try {
        const SpeechGrammarListCtor = window.SpeechGrammarList || window.webkitSpeechGrammarList;
        if (SpeechGrammarListCtor && Array.isArray(grammarCommands) && grammarCommands.length) {
            const list = new SpeechGrammarListCtor();
            const cmds = grammarCommands.map((c) => String(c || "").trim()).filter(Boolean);
            if (cmds.length) {
                const grammar = `#JSGF V1.0; grammar commands; public <command> = ${cmds.join(" | ")};`;
                list.addFromString(grammar, 1);
                recognition.grammars = list;
            }
        }
    } catch {
        // ignore
    }

    let recognizing = false;
    let active = false;
    let restartTimer = null;
    let backoffMs = baseBackoffMs;
    let noSpeechCount = 0;
    let lastError = null;

    /**
     * Cancela el temporizador de reinicio pendiente del reconocimiento de voz.
     *
     * @returns {void}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-02-13
     */
    const clearRestart = () => {
        if (restartTimer) {
            try {
                clearTimeout(restartTimer);
            } catch {}
            restartTimer = null;
        }
    };

    /**
     * Intenta iniciar el reconocimiento de voz de forma segura, ignorando errores si ya esta activo.
     *
     * @returns {boolean}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-02-13
     */
    const safeStart = () => {
        if (!recognition) return false;
        if (active) return false;
        try {
            recognition.start();
            return true;
        } catch {
            return false;
        }
    };

    /**
     * Programa un reinicio del reconocimiento de voz usando backoff si sigue activo.
     *
     * @returns {void}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-02-13
     */
    const scheduleRestart = (customDelayMs = null) => {
        clearRestart();
        if (!recognizing) return;
        const delay = customDelayMs !== null ? customDelayMs : Math.max(100, backoffMs || 300);
        restartTimer = setTimeout(() => {
            if (!recognizing) return;
            safeStart();
        }, delay);
    };

    recognition.onstart = () => {
        active = true;
        onState({ recognizing, active, lastError });
    };

    recognition.onresult = (event) => {
        let finalText = "";
        let interimText = "";
        for (let i = event.resultIndex; i < event.results.length; i++) {
            const result = event.results[i];
            const transcript =
                result && result[0] && result[0].transcript ? String(result[0].transcript) : "";
            if (result && result.isFinal) {
                finalText += transcript;
            } else if (transcript) {
                interimText += transcript;
            }
        }
        if (finalText) {
            noSpeechCount = 0;
            backoffMs = baseBackoffMs;
            onFinalText(finalText);
        } else if (interimText) {
            onInterimText(interimText);
        }
    };

    recognition.onend = () => {
        active = false;
        onState({ recognizing, active, lastError });
        if (recognizing) {
            const delay = lastError === "aborted" ? 150 : null;
            scheduleRestart(delay);
        }
    };

    recognition.onerror = (event) => {
        const err = event && event.error ? String(event.error) : "";
        lastError = err || null;
        onState({ recognizing, active, lastError });

        // Permisos denegados definitivamente
        if (err === "not-allowed" || err === "service-not-allowed" || err === "permission-denied") {
            recognizing = false;
            clearRestart();
            try {
                recognition.stop();
            } catch {}
            onPermissionDenied(err);
            return;
        }

        // Si se abortó (por ejemplo, al cambiar de ventana o foco), no cancelar; reanudar rápidamente
        if (err === "aborted") {
            if (recognizing) {
                scheduleRestart(150);
            }
            return;
        }

        if (err === "no-speech") {
            noSpeechCount += 1;
            // Backoff suave para evitar desconexiones bruscas
            backoffMs = Math.min(
                maxBackoffMs,
                Math.max(baseBackoffMs, (backoffMs || baseBackoffMs) * 1.5)
            );
            if (noSpeechCount >= maxNoSpeechRetries) {
                // No detener dictado bruscamente si el médico está leyendo una placa en silencio; reintentar con backoff tope
                noSpeechCount = 0;
                backoffMs = maxBackoffMs;
            }
        }

        if (err === "no-speech" || err === "network") {
            // El reinicio seguro queda programado en onend
            try {
                recognition.stop();
            } catch {}
        } else {
            onError({ error: err });
        }
    };

    const start = () => {
        recognizing = true;
        clearRestart();
        safeStart();
        onState({ recognizing, active, lastError });
    };

    const pause = () => {
        recognizing = false;
        active = false;
        clearRestart();
        try {
            recognition.stop();
        } catch {}
        onState({ recognizing, active, lastError });
    };

    const resume = () => {
        recognizing = true;
        clearRestart();
        safeStart();
        onState({ recognizing, active, lastError });
    };

    const stop = () => {
        recognizing = false;
        active = false;
        clearRestart();
        try {
            recognition.onend = null;
            recognition.onerror = null;
        } catch {}
        try {
            recognition.stop();
        } catch {}
        onState({ recognizing, active, lastError });
    };

    const destroy = () => {
        stop();
    };

    return {
        recognition,
        start,
        pause,
        resume,
        stop,
        destroy,
        getState: () => ({ recognizing, active, lastError, noSpeechCount, backoffMs }),
    };
}

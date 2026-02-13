/**
 * Speech dictation helper (sin librerías externas).
 * Envuelve SpeechRecognition con reinicio seguro y backoff por no-speech.
 *
 * Nota: depende de API nativa del navegador (Chrome/Edge).
 */

function getSpeechRecognitionCtor() {
    return (
        window.SpeechRecognition ||
        window.webkitSpeechRecognition ||
        window.mozSpeechRecognition ||
        null
    );
}

export function isSpeechRecognitionSupported() {
    return !!getSpeechRecognitionCtor();
}

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
    } catch (e) {
        // ignore
    }

    let recognizing = false;
    let active = false;
    let restartTimer = null;
    let backoffMs = baseBackoffMs;
    let noSpeechCount = 0;
    let lastError = null;

    const clearRestart = () => {
        if (restartTimer) {
            try {
                clearTimeout(restartTimer);
            } catch (e) {}
            restartTimer = null;
        }
    };

    const safeStart = () => {
        if (!recognition) return false;
        if (active) return false;
        try {
            recognition.start();
            return true;
        } catch (e) {
            return false;
        }
    };

    const scheduleRestart = () => {
        clearRestart();
        if (!recognizing) return;
        restartTimer = setTimeout(() => {
            if (!recognizing) return;
            safeStart();
        }, backoffMs || 500);
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
        if (recognizing) scheduleRestart();
    };

    recognition.onerror = (event) => {
        const err = event && event.error ? String(event.error) : "";
        lastError = err || null;
        onState({ recognizing, active, lastError });

        // permisos
        if (err === "not-allowed" || err === "service-not-allowed" || err === "permission-denied") {
            recognizing = false;
            clearRestart();
            try {
                recognition.stop();
            } catch (e) {}
            onPermissionDenied(err);
            return;
        }

        if (err === "no-speech") {
            noSpeechCount += 1;
            backoffMs = Math.min(
                maxBackoffMs,
                Math.max(baseBackoffMs, (backoffMs || baseBackoffMs) * 2)
            );
            if (noSpeechCount >= maxNoSpeechRetries) {
                recognizing = false;
                clearRestart();
                try {
                    recognition.stop();
                } catch (e) {}
                onError({ error: err, message: "Silencio repetido" });
                return;
            }
        }

        if (err === "no-speech" || err === "network") {
            // detener: el reinicio queda en onend
            try {
                recognition.stop();
            } catch (e) {}
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
        clearRestart();
        try {
            recognition.stop();
        } catch (e) {}
        onState({ recognizing, active, lastError });
    };

    const resume = () => {
        if (recognizing) return;
        recognizing = true;
        clearRestart();
        safeStart();
        onState({ recognizing, active, lastError });
    };

    const stop = () => {
        recognizing = false;
        clearRestart();
        try {
            recognition.onend = null;
            recognition.onerror = null;
        } catch (e) {}
        try {
            recognition.stop();
        } catch (e) {}
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

export function createSpeechRecognition(options = {}) {
    if (typeof window === "undefined" || !("webkitSpeechRecognition" in window)) {
        return null;
    }

    const recognition = new window.webkitSpeechRecognition();
    recognition.lang = options.lang || "es-CO";
    recognition.continuous = true;
    recognition.interimResults = true;

    return recognition;
}

export default function toggleRecognition(state) {
    // state: { recognition, recognizing, onFinalText }
    if (!state || !state.recognition) return state;

    const { recognition } = state;
    const next = { ...state };

    recognition.onresult = function (event) {
        for (let i = event.resultIndex; i < event.results.length; i++) {
            if (event.results[i].isFinal) {
                const text = event.results[i][0]?.transcript || "";
                if (typeof next.onFinalText === "function") next.onFinalText(text);
            }
        }
    };

    recognition.onend = function () {
        next.recognizing = false;
    };

    if (!next.recognizing) {
        recognition.start();
        next.recognizing = true;
    } else {
        recognition.stop();
        next.recognizing = false;
    }

    return next;
}

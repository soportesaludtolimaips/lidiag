import { createVoiceSession } from "@/helpers/voice/voiceSession";
import { getVoiceAudioConstraints } from "@/helpers/voice/audioRecorder";

export function useVoiceDictation({
    lang = "es-ES",
    audioBitsPerSecond = 64000,
    maxNoSpeechRetries = 6,
    mimeType = "audio/wav",
} = {}) {
    let voiceSession = null;

    const uppercaseCommands = {
        "iniciar mayúscula sostenida": () => ({ type: "uppercase", value: true }),
        "iniciar mayúsculas sostenida": () => ({ type: "uppercase", value: true }),
        "iniciar mayúsculas": () => ({ type: "uppercase", value: true }),
        "iniciar mayúscula": () => ({ type: "uppercase", value: true }),
        "mayúsculas sostenida": () => ({ type: "uppercase", value: true }),
        "mayúscula sostenida": () => ({ type: "uppercase", value: true }),
        "activar mayúsculas": () => ({ type: "uppercase", value: true }),
        "activar mayúscula": () => ({ type: "uppercase", value: true }),
        "habilitar mayúsculas": () => ({ type: "uppercase", value: true }),
        "habilitar mayúscula": () => ({ type: "uppercase", value: true }),
        "activare mayúsculas": () => ({ type: "uppercase", value: true }),
        "activare mayúscula": () => ({ type: "uppercase", value: true }),
        "desactivar mayúsculas": () => ({ type: "uppercase", value: false }),
        "desactivar mayúscula": () => ({ type: "uppercase", value: false }),
        "deshabilitar mayúsculas": () => ({ type: "uppercase", value: false }),
        "deshabilitar mayúscula": () => ({ type: "uppercase", value: false }),
        "terminar mayúsculas": () => ({ type: "uppercase", value: false }),
        "terminar mayúscula": () => ({ type: "uppercase", value: false }),
        "fin mayúsculas": () => ({ type: "uppercase", value: false }),
        "fin mayúscula": () => ({ type: "uppercase", value: false }),
        "finaliza mayúsculas": () => ({ type: "uppercase", value: false }),
        "finaliza mayúscula": () => ({ type: "uppercase", value: false }),
        "finalizar mayúsculas": () => ({ type: "uppercase", value: false }),
        "finalizar mayúscula": () => ({ type: "uppercase", value: false }),
    };

    const lineBreakCommands = {
        "punto aparte": () => ({ type: "lineBreak", value: ".\n\n", capitalizeNext: true }),
        "nuevo párrafo": () => ({ type: "lineBreak", value: "\n\n", capitalizeNext: true }),
        "nueva sección": () => ({ type: "lineBreak", value: "\n\n---\n\n", capitalizeNext: true }),
        separador: () => ({ type: "lineBreak", value: "\n\n---\n\n" }),
        "salto de línea": () => ({ type: "lineBreak", value: "\n" }),
        "doble salto de línea": () => ({ type: "lineBreak", value: "\n\n" }),
        enter: () => ({ type: "lineBreak", value: "\n" }),
        "doble enter": () => ({ type: "lineBreak", value: "\n\n" }),
        "línea en blanco": () => ({ type: "lineBreak", value: "\n\n" }),
        "doble línea en blanco": () => ({ type: "lineBreak", value: "\n\n\n\n" }),
    };

    const punctuationCommands = {
        punto: () => ({ type: "punctuation", value: ".", capitalizeNext: true }),
        "punto y seguido": () => ({ type: "punctuation", value: ". ", capitalizeNext: true }),
        "punto seguido": () => ({ type: "punctuation", value: ". ", capitalizeNext: true }),
        coma: () => ({ type: "punctuation", value: "," }),
        "dos puntos": () => ({ type: "punctuation", value: ":" }),
        "punto y coma": () => ({ type: "punctuation", value: ";" }),
        "dar espacio": () => ({ type: "punctuation", value: " " }),
        "un espacio": () => ({ type: "punctuation", value: " " }),
        "dos espacio": () => ({ type: "punctuation", value: "  " }),
        "doble espacio": () => ({ type: "punctuation", value: "  " }),
        "abrir paréntesis": () => ({ type: "punctuation", value: "(" }),
        "cerrar paréntesis": () => ({ type: "punctuation", value: ")" }),
        "abrir corchetes": () => ({ type: "punctuation", value: "[" }),
        "cerrar corchetes": () => ({ type: "punctuation", value: "]" }),
        "abrir llaves": () => ({ type: "punctuation", value: "{" }),
        "cerrar llaves": () => ({ type: "punctuation", value: "}" }),
        "abrir comillas": () => ({ type: "punctuation", value: '"' }),
        "cerrar comillas": () => ({ type: "punctuation", value: '"' }),
        "abrir comillas simples": () => ({ type: "punctuation", value: "'" }),
        "cerrar comillas simples": () => ({ type: "punctuation", value: "'" }),
        guion: () => ({ type: "punctuation", value: "-" }),
        "guion bajo": () => ({ type: "punctuation", value: "_" }),
        "barra diagonal": () => ({ type: "punctuation", value: "/" }),
        "barra invertida": () => ({ type: "punctuation", value: "\\" }),
        "nueva oración": () => ({ type: "punctuation", value: ". " }),
        "por ciento": () => ({ type: "punctuation", value: "%" }),
        menos: () => ({ type: "punctuation", value: "-" }),
        más: () => ({ type: "punctuation", value: "+" }),
        igual: () => ({ type: "punctuation", value: "=" }),
        multiplicar: () => ({ type: "punctuation", value: "×" }),
        dividir: () => ({ type: "punctuation", value: "÷" }),
        asterisco: () => ({ type: "punctuation", value: "*" }),
        numeral: () => ({ type: "punctuation", value: "#" }),
        ampersand: () => ({ type: "punctuation", value: "&" }),
        arroba: () => ({ type: "punctuation", value: "@" }),
        dólar: () => ({ type: "punctuation", value: "$" }),
        euro: () => ({ type: "punctuation", value: "€" }),
    };

    const formatCommands = {
        negrita: () => ({ type: "format", value: "**" }),
        "cerrar negrita": () => ({ type: "format", value: "**" }),
        cursiva: () => ({ type: "format", value: "_" }),
        "cerrar cursiva": () => ({ type: "format", value: "_" }),
        subray: () => ({ type: "format", value: "<u>" }),
        "cerrar subray": () => ({ type: "format", value: "</u>" }),
    };

    function stripDiacritics(text) {
        try {
            return String(text)
                .normalize("NFD")
                .replace(/[\u0300-\u036f]/g, "");
        } catch (e) {
            return String(text);
        }
    }

    function escapeRegExp(text) {
        return String(text).replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
    }

    function addNormalizedKeys(commandMap) {
        if (!commandMap) return;
        Object.keys(commandMap).forEach((k) => {
            const normalized = stripDiacritics(k).toLowerCase();
            if (!Object.prototype.hasOwnProperty.call(commandMap, normalized)) {
                commandMap[normalized] = commandMap[k];
            }
        });
    }

    function prepareVoiceCommands() {
        addNormalizedKeys(uppercaseCommands);
        addNormalizedKeys(lineBreakCommands);
        addNormalizedKeys(punctuationCommands);
        addNormalizedKeys(formatCommands);
    }

    function applyCommandMap(text, commandMap) {
        if (!commandMap) return text;
        const keys = Object.keys(commandMap).sort((a, b) => b.length - a.length);
        keys.forEach((command) => {
            const escaped = escapeRegExp(command);
            const re = new RegExp(`(^|[^\\p{L}\\p{N}_])(${escaped})(?=$|[^\\p{L}\\p{N}_])`, "giu");
            text = text.replace(re, (match, prefix) => {
                const fn = commandMap[command];
                const result = typeof fn === "function" ? fn() : String(fn);
                if (typeof result === "object" && result.value) {
                    return `${prefix}${result.value}`;
                }
                return `${prefix}${result}`;
            });
        });
        return text;
    }

    function processCommands(text, state = {}) {
        const { uppercase = false, capitalizeNext = false } = state;
        let currentUppercase = uppercase;
        let currentCapitalizeNext = capitalizeNext;

        prepareVoiceCommands();

        text = applyCommandMap(text, uppercaseCommands);
        text = applyCommandMap(text, lineBreakCommands);
        text = applyCommandMap(text, punctuationCommands);
        text = applyCommandMap(text, formatCommands);

        if (currentUppercase) {
            text = text.toUpperCase();
        }

        if (currentCapitalizeNext) {
            text = text.replace(/(^|\n)(\s*)([a-záéíóúüñ])/i, (m, start, spaces, first) => {
                return `${start}${spaces}${first.toUpperCase()}`;
            });
        }

        return text;
    }

    function getAllCommands() {
        prepareVoiceCommands();
        return {
            uppercase: uppercaseCommands,
            lineBreak: lineBreakCommands,
            punctuation: punctuationCommands,
            format: formatCommands,
            all: {
                ...uppercaseCommands,
                ...lineBreakCommands,
                ...punctuationCommands,
                ...formatCommands,
            },
        };
    }

    function startDictation({
        onFinalText = () => {},
        onInterimText = () => {},
        onPermissionDenied = () => {},
        onError = () => {},
        onState = () => {},
    } = {}) {
        prepareVoiceCommands();
        const allCommands = getAllCommands();
        const grammarCommands = Object.keys(allCommands.all);

        voiceSession = createVoiceSession({
            constraints: getVoiceAudioConstraints(),
            mimeType,
            audioBitsPerSecond,
            lang,
            grammarCommands,
            maxNoSpeechRetries,
            onFinalText,
            onInterimText,
            onPermissionDenied,
            onError,
            onState,
        });

        return voiceSession.start();
    }

    function stopDictation() {
        if (voiceSession) {
            return voiceSession.stop();
        }
        return Promise.resolve();
    }

    function createSession(config = {}) {
        return createVoiceSession({
            constraints: getVoiceAudioConstraints(),
            mimeType: config.mimeType || mimeType,
            audioBitsPerSecond: config.audioBitsPerSecond || audioBitsPerSecond,
            lang: config.lang || lang,
            grammarCommands: config.grammarCommands || Object.keys(getAllCommands().all),
            maxNoSpeechRetries: config.maxNoSpeechRetries || maxNoSpeechRetries,
            ...config,
        });
    }

    return {
        uppercaseCommands,
        lineBreakCommands,
        punctuationCommands,
        formatCommands,
        stripDiacritics,
        escapeRegExp,
        addNormalizedKeys,
        prepareVoiceCommands,
        applyCommandMap,
        processCommands,
        getAllCommands,
        startDictation,
        stopDictation,
        createSession,
    };
}

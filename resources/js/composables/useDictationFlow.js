import { ref, onBeforeUnmount } from "vue";
import axios from "axios";
import { createVoiceSession } from "@/helpers/voice/voiceSession";
import {
    createAudioRecorderSession,
    getVoiceAudioConstraints,
} from "@/helpers/voice/audioRecorder";
import { isSpeechRecognitionSupported } from "@/helpers/voice/speechDictation";

export function useDictationFlow() {
    const recognizing = ref(false);
    const recognitionActive = ref(false);
    const recognitionRestartTimer = ref(null);
    const recognitionBackoffMs = ref(500);
    const noSpeechCount = ref(0);
    const maxNoSpeechRetries = ref(6);
    const lastRecognitionError = ref(null);
    const recording = ref(false);
    const microfono = ref("admin-wrap/assets/images/mic.gif");
    const dictation = ref(null);
    const voiceSession = ref(null);
    const recognition = ref(null);
    const audioSession = ref(null);
    const mediaRecorder = ref(null);
    const audioChunks = ref([]);
    const audioBlob = ref(null);
    const audioUrl = ref(null);
    const speechInterim = ref("");
    const uppercase = ref(false);
    const mediaStream = ref(null);
    const capitalizeNext = ref(false);

    let _onText = null;
    let _showToast = null;
    let _onState = null;
    let _onAudioSaved = null;
    let _audioRevoke = null;
    let onKeydownHandler = null;

    function handleVisibilityChange() {
        if (
            !document.hidden &&
            recording.value &&
            mediaRecorder.value &&
            mediaRecorder.value.state === "paused"
        ) {
            mediaRecorder.value.resume();
        }
    }

    function setCallbacks({ onText, showToast, onState, onAudioSaved } = {}) {
        if (typeof onText === "function") _onText = onText;
        if (typeof showToast === "function") _showToast = showToast;
        if (typeof onState === "function") _onState = onState;
        if (typeof onAudioSaved === "function") _onAudioSaved = onAudioSaved;
    }

    const uppercaseCommands = {
        "iniciar mayúscula sostenida": () => {
            uppercase.value = true;
            return "";
        },
        "iniciar mayúsculas sostenida": () => {
            uppercase.value = true;
            return "";
        },
        "iniciar mayúsculas": () => {
            uppercase.value = true;
            return "";
        },
        "iniciar mayúscula": () => {
            uppercase.value = true;
            return "";
        },
        "mayúsculas sostenida": () => {
            uppercase.value = true;
            return "";
        },
        "mayúscula sostenida": () => {
            uppercase.value = true;
            return "";
        },
        "activar mayúsculas": () => {
            uppercase.value = true;
            return "";
        },
        "activar mayúscula": () => {
            uppercase.value = true;
            return "";
        },
        "habilitar mayúsculas": () => {
            uppercase.value = true;
            return "";
        },
        "habilitar mayúscula": () => {
            uppercase.value = true;
            return "";
        },
        "activare mayúsculas": () => {
            uppercase.value = true;
            return "";
        },
        "activare mayúscula": () => {
            uppercase.value = true;
            return "";
        },
        "desactivar mayúsculas": () => {
            uppercase.value = false;
            return "";
        },
        "desactivar mayúscula": () => {
            uppercase.value = false;
            return "";
        },
        "Deshabilitar mayúsculas": () => {
            uppercase.value = false;
            return "";
        },
        "Deshabilitar mayúscula": () => {
            uppercase.value = false;
            return "";
        },
        "terminar mayúsculas": () => {
            uppercase.value = false;
            return "";
        },
        "terminar mayúscula": () => {
            uppercase.value = false;
            return "";
        },
        "fin mayúsculas": () => {
            uppercase.value = false;
            return "";
        },
        "fin mayúscula": () => {
            uppercase.value = false;
            return "";
        },
        "finaliza mayúsculas": () => {
            uppercase.value = false;
            return "";
        },
        "finaliza mayúscula": () => {
            uppercase.value = false;
            return "";
        },
        "finalizar mayúsculas": () => {
            uppercase.value = false;
            return "";
        },
        "finalizar mayúscula": () => {
            uppercase.value = false;
            return "";
        },
    };

    const lineBreakCommands = {
        "punto aparte": () => {
            capitalizeNext.value = true;
            return ".\n\n";
        },
        "punto a parte": () => {
            capitalizeNext.value = true;
            return ".\n\n";
        },
        "punto y aparte": () => {
            capitalizeNext.value = true;
            return ".\n\n";
        },
        "nuevo párrafo": () => {
            capitalizeNext.value = true;
            return "\n\n";
        },
        "nueva sección": () => {
            capitalizeNext.value = true;
            return "\n\n---\n\n";
        },
        separador: () => "\n\n---\n\n",
        "salto de línea": () => "\n",
        "doble salto de línea": () => "\n\n",
        enter: () => "\n",
        "doble enter": () => "\n\n",
        "línea en blanco": () => "\n\n",
        "doble línea en blanco": () => "\n\n\n\n",
    };

    const punctuationCommands = {
        punto: () => {
            capitalizeNext.value = true;
            return ".";
        },
        "punto y seguido": () => {
            capitalizeNext.value = true;
            return ". ";
        },
        "punto seguido": () => {
            capitalizeNext.value = true;
            return ". ";
        },
        coma: () => ",",
        "dos puntos": () => ":",
        "punto y coma": () => ";",
        "dar espacio": () => " ",
        "un espacio": () => " ",
        "dos espacio": () => "  ",
        "doble espacio": () => "  ",
        "abrir paréntesis": () => "(",
        "cerrar paréntesis": () => ")",
        "abrir corchetes": () => "[",
        "cerrar corchetes": () => "]",
        "abrir llaves": () => "{",
        "cerrar llaves": () => "}",
        "abrir comillas": () => '"',
        "cerrar comillas": () => '"',
        "abrir comillas simples": () => "'",
        "cerrar comillas simples": () => "'",
        guion: () => "-",
        "guion bajo": () => "_",
        "barra diagonal": () => "/",
        "barra invertida": () => "\\",
        "nueva oración": () => ". ",
        "por ciento": () => "%",
        menos: () => "-",
        más: () => "+",
        igual: () => "=",
        multiplicar: () => "×",
        dividir: () => "÷",
        asterisco: () => "*",
        numeral: () => "#",
        ampersand: () => "&",
        arroba: () => "@",
        dólar: () => "$",
        euro: () => "€",
    };

    const formatCommands = {
        negrita: () => "**",
        "cerrar negrita": () => "**",
        cursiva: () => "_",
        "cerrar cursiva": () => "_",
        subrayado: () => "<u>",
        "cerrar subrayado": () => "</u>",
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

    function prepareVoiceCommands() {
        const addNormalizedKeys = (obj) => {
            if (!obj) return;
            Object.keys(obj).forEach((k) => {
                const n = stripDiacritics(k).toLowerCase();
                if (!Object.prototype.hasOwnProperty.call(obj, n)) obj[n] = obj[k];
            });
        };
        addNormalizedKeys(uppercaseCommands);
        addNormalizedKeys(lineBreakCommands);
        addNormalizedKeys(punctuationCommands);
        addNormalizedKeys(formatCommands);
    }

    function applyCommandMap(text, commandMap, { onEachMatch } = {}) {
        if (!commandMap) return text;
        const keys = Object.keys(commandMap).sort((a, b) => b.length - a.length);
        keys.forEach((cmd) => {
            const re = new RegExp(
                `(^|[^\\p{L}\\p{N}_])(${escapeRegExp(cmd)})(?=$|[^\\p{L}\\p{N}_])`,
                "giu"
            );
            text = text.replace(re, (match, prefix) => {
                if (typeof onEachMatch === "function") onEachMatch(cmd);
                const fn = commandMap[cmd];
                return `${prefix}${typeof fn === "function" ? fn() : String(fn)}`;
            });
        });
        return text;
    }

    function processCommands(text) {
        if (typeof uppercase.value === "undefined") uppercase.value = false;
        prepareVoiceCommands();
        text = applyCommandMap(text, uppercaseCommands);
        text = applyCommandMap(text, lineBreakCommands, {
            onEachMatch: () => {
                capitalizeNext.value = true;
            },
        });
        text = applyCommandMap(text, punctuationCommands);
        text = applyCommandMap(text, formatCommands);
        text = uppercase.value ? text.toUpperCase() : text;
        if (capitalizeNext.value) {
            text = text.replace(/(^|\n)(\s*)([a-záéíóúüñ])/i, (m, start, spaces, first) => {
                capitalizeNext.value = false;
                return `${start}${spaces}${first.toUpperCase()}`;
            });
        }
        return text;
    }

    function clearRecognitionRestartTimer() {
        if (recognitionRestartTimer.value) {
            try {
                clearTimeout(recognitionRestartTimer.value);
            } catch (e) {}
            recognitionRestartTimer.value = null;
        }
    }

    function safeStartRecognition() {
        if (!recognition.value || recognitionActive.value) return false;
        try {
            recognition.value.start();
            return true;
        } catch (e) {
            return false;
        }
    }

    function scheduleRecognitionRestart(delayMs = 500) {
        clearRecognitionRestartTimer();
        if (!recognition.value || !recognizing.value) return;
        recognitionRestartTimer.value = setTimeout(() => {
            if (!recognizing.value || !recognition.value) return;
            safeStartRecognition();
        }, delayMs);
    }

    function showToast(cfg) {
        if (typeof _showToast === "function") _showToast(cfg);
    }

    async function ensureMicApiAvailable() {
        if (
            !navigator ||
            !navigator.mediaDevices ||
            typeof navigator.mediaDevices.getUserMedia !== "function"
        ) {
            showToast({
                heading: "No soportado",
                text: "Este navegador no soporta acceso al micrófono (getUserMedia). Usa Chrome/Edge actualizado.",
                position: "top-right",
                loaderBg: "#ff6849",
                icon: "warning",
                hideAfter: 7000,
                stack: 6,
            });
            return false;
        }
        return true;
    }

    function getMicErrorToastConfig(error) {
        const name = error && error.name ? String(error.name) : "";
        const msg = error && error.message ? String(error.message) : String(error);
        if (name === "NotAllowedError" || /permission denied/i.test(msg))
            return {
                heading: "Permiso de micrófono",
                text: "El navegador negó el acceso al micrófono. Solución: en el ícono del candado del navegador → Permisos del sitio → Micrófono → Permitir, y recarga. Si estás en Windows, revisa Configuración → Privacidad y seguridad → Micrófono y permite el acceso para tu navegador.",
                icon: "warning",
                hideAfter: 7000,
            };
        if (name === "SecurityError" || /secure origins|only secure origins/i.test(msg))
            return {
                heading: "HTTPS requerido",
                text: "El micrófono solo funciona en contextos seguros (HTTPS o localhost). Abre el sistema en `https://` (por ejemplo `https://lidiag.test`) y acepta el certificado si corresponde, luego recarga.",
                icon: "warning",
                hideAfter: 8000,
            };
        if (name === "NotFoundError" || /notfound/i.test(name))
            return {
                heading: "Sin micrófono",
                text: "No se encontró un dispositivo de micrófono. Conecta/activa un micrófono y vuelve a intentar.",
                icon: "warning",
                hideAfter: 7000,
            };
        if (name === "NotReadableError")
            return {
                heading: "Micrófono ocupado",
                text: "El micrófono está siendo usado por otra aplicación o el navegador no puede acceder a él. Cierra apps que lo estén usando (Meet/Zoom/otra pestaña) y reintenta.",
                icon: "warning",
                hideAfter: 7000,
            };
        return {
            heading: "Upsss!!!",
            text: "Error al acceder al micrófono: " + (msg || name || "desconocido"),
            icon: "danger",
            hideAfter: 4500,
        };
    }

    function pauseRecognition() {
        clearRecognitionRestartTimer();
        if (dictation.value) {
            try {
                dictation.value.pause();
            } catch (e) {}
        } else if (recognition.value) {
            try {
                recognition.value.stop();
            } catch (e) {}
        }
        recognizing.value = false;
    }

    function resumeRecognition() {
        if (dictation.value && !recognizing.value) {
            try {
                dictation.value.resume();
                recognizing.value = true;
            } catch (e) {
                recognizing.value = false;
            }
            return;
        }
        if (recognition.value && !recognizing.value) {
            try {
                recognizing.value = true;
                safeStartRecognition();
            } catch (e) {
                recognizing.value = false;
            }
        }
    }

    function startAudioOnly() {
        ensureMicApiAvailable().then((ok) => {
            if (!ok) return;
            if (mediaStream.value && mediaStream.value.getTracks) {
                try {
                    mediaStream.value.getTracks().forEach((t) => t.stop());
                } catch (e) {}
                mediaStream.value = null;
            }
            createAudioRecorderSession({
                constraints: getVoiceAudioConstraints(),
                mimeType: "audio/webm;codecs=opus",
                audioBitsPerSecond: 64000,
            })
                .then((session) => {
                    audioSession.value = session;
                    mediaStream.value = session.stream;
                    mediaRecorder.value = session.mediaRecorder;
                    audioChunks.value = session.chunks;
                    if (_audioRevoke) {
                        try {
                            _audioRevoke();
                        } catch (e) {}
                        _audioRevoke = null;
                    }
                    audioBlob.value = null;
                    audioUrl.value = null;
                    session.start();
                    recording.value = true;
                    microfono.value = "admin-wrap/assets/images/mic-animate.gif";
                })
                .catch((error) => {
                    recording.value = false;
                    microfono.value = "admin-wrap/assets/images/mic.gif";
                    const cfg = getMicErrorToastConfig(error);
                    showToast({
                        heading: cfg.heading,
                        text: cfg.text,
                        position: "top-right",
                        loaderBg: "#ff6849",
                        icon: cfg.icon,
                        hideAfter: cfg.hideAfter,
                        stack: 6,
                    });
                    console.error("Error al acceder al micrófono:", error);
                });
        });
    }

    function stopRecognition() {
        try {
            if (voiceSession.value) {
                try {
                    voiceSession.value.stopDictation();
                } catch (e) {}
            }
            if (dictation.value) {
                try {
                    dictation.value.stop();
                } catch (e) {}
                dictation.value = null;
            }
            if (recognition.value) {
                try {
                    recognition.value.onend = null;
                    recognition.value.onerror = null;
                } catch (e) {}
                if (mediaRecorder.value && mediaRecorder.value.state === "recording") {
                    try {
                        mediaRecorder.value.pause();
                    } catch (e) {
                        console.warn(
                            "No se pudo pausar la grabación al detener reconocimiento:",
                            e
                        );
                    }
                    microfono.value = "admin-wrap/assets/images/mic.gif";
                }
                try {
                    recognition.value.stop();
                } catch (e) {}
            }
            recognizing.value = false;
            recognitionActive.value = false;
            microfono.value = "admin-wrap/assets/images/mic.gif";
            speechInterim.value = "";
        } catch (e) {
            console.error("Error al detener reconocimiento:", e);
        }
    }

    async function stopRecording() {
        return new Promise(async (resolve) => {
            let resolved = false;
            const doResolve = () => {
                if (!resolved) {
                    resolved = true;
                    resolve();
                }
            };
            let usingMediaRecorder = false;
            try {
                if (audioSession.value && typeof audioSession.value.stop === "function") {
                    const timeoutPromise = new Promise((r) =>
                        setTimeout(
                            () => r({ blob: null, url: null, chunks: [], revoke: () => {} }),
                            3000
                        )
                    );
                    const { blob, url, revoke } = await Promise.race([
                        audioSession.value.stop(),
                        timeoutPromise,
                    ]);
                    _audioRevoke = revoke || null;
                    audioBlob.value = blob instanceof Blob ? blob : null;
                    audioUrl.value = url || null;
                } else if (mediaRecorder.value && mediaRecorder.value.state !== "inactive") {
                    usingMediaRecorder = true;
                    mediaRecorder.value.stop();
                    recording.value = false;
                    microfono.value = "admin-wrap/assets/images/mic.gif";
                    await new Promise((r) => setTimeout(r, 200));
                    if (audioChunks.value && audioChunks.value.length > 0) {
                        audioBlob.value = new Blob(audioChunks.value, { type: "audio/webm" });
                        audioUrl.value = URL.createObjectURL(audioBlob.value);
                    }
                    doResolve();
                    return;
                }
            } catch (e) {
                console.warn("[stopRecording] Error:", e);
            } finally {
                recording.value = false;
                microfono.value = "admin-wrap/assets/images/mic.gif";
                if (!usingMediaRecorder) {
                    doResolve();
                }
            }
        });
    }

    async function stopAll() {
        try {
            document.removeEventListener("visibilitychange", handleVisibilityChange);
            clearRecognitionRestartTimer();
            noSpeechCount.value = 0;
            recognitionBackoffMs.value = 500;
            await stopRecording();
            stopRecognition();
            if (voiceSession.value) {
                try {
                    voiceSession.value.cleanup();
                } catch (e) {}
                voiceSession.value = null;
            }
            if (audioSession.value) {
                try {
                    audioSession.value.cleanup();
                } catch (e) {}
                audioSession.value = null;
            } else if (mediaStream.value && mediaStream.value.getTracks) {
                try {
                    mediaStream.value.getTracks().forEach((t) => t.stop());
                } catch (e) {}
            }
            mediaStream.value = null;
            if (_audioRevoke) {
                try {
                    _audioRevoke();
                } catch (e) {}
                _audioRevoke = null;
            }
            if (audioUrl.value) {
                try {
                    URL.revokeObjectURL(audioUrl.value);
                } catch (e) {}
                audioUrl.value = null;
            }
            recording.value = false;
            microfono.value = "admin-wrap/assets/images/mic.gif";
        } catch (e) {
            console.error("Error en stopAll:", e);
        }
    }

    function clearAudioBuffer() {
        if (_audioRevoke) {
            try {
                _audioRevoke();
            } catch (e) {}
            _audioRevoke = null;
        }
        if (audioUrl.value) {
            try {
                URL.revokeObjectURL(audioUrl.value);
            } catch (e) {}
            audioUrl.value = null;
        }
        audioChunks.value = [];
        audioBlob.value = null;
    }

    function startRecording() {
        ensureMicApiAvailable().then((ok) => {
            if (!ok) return;
            prepareVoiceCommands();
            const allCommands = {
                ...uppercaseCommands,
                ...lineBreakCommands,
                ...punctuationCommands,
                ...formatCommands,
            };
            const grammarCommands = Object.keys(allCommands);
            if (!voiceSession.value) {
                voiceSession.value = createVoiceSession({
                    constraints: getVoiceAudioConstraints(),
                    mimeType: "audio/webm;codecs=opus",
                    audioBitsPerSecond: 64000,
                    lang: "es-ES",
                    grammarCommands,
                    maxNoSpeechRetries: maxNoSpeechRetries.value || 6,
                    onFinalText: (raw) => {
                        let text = String(raw || "").trim();
                        if (!text) return;
                        text = processCommands(text);
                        if (text && String(text).length) {
                            const suffix = /[ \n]$/.test(text) ? "" : " ";
                            if (typeof _onText === "function") _onText(text + suffix);
                            speechInterim.value = "";
                        }
                    },
                    onInterimText: (raw) => {
                        speechInterim.value = String(raw || "").trim();
                    },
                    onPermissionDenied: () => {
                        recognizing.value = false;
                        recognitionActive.value = false;
                        microfono.value = "admin-wrap/assets/images/mic.gif";
                        speechInterim.value = "";
                        showToast({
                            heading: "Permiso requerido",
                            text: "El navegador bloqueó el micrófono. Permite el acceso al micrófono para usar dictado.",
                            position: "top-right",
                            loaderBg: "#ff6849",
                            icon: "warning",
                            hideAfter: 6000,
                            stack: 6,
                        });
                    },
                    onError: (e) => {
                        const err = e && e.error ? String(e.error) : "";
                        lastRecognitionError.value = err || null;
                        console.error("[Voice] Error:", err, e);
                        if (e && e.message === "Silencio repetido") {
                            showToast({
                                heading: "Sin voz detectada",
                                text: "No se detecta voz (silencio repetido). Verifica micrófono/entrada y vuelve a iniciar el dictamen.",
                                position: "top-right",
                                loaderBg: "#ff6849",
                                icon: "warning",
                                hideAfter: 6000,
                                stack: 6,
                            });
                        } else if (err.includes("not-allowed")) {
                            showToast({
                                heading: "Micrófono bloqueado",
                                text: "Permite el acceso al micrófono en la configuración del navegador.",
                                position: "top-right",
                                loaderBg: "#ff6849",
                                icon: "error",
                                hideAfter: 6000,
                                stack: 6,
                            });
                        } else if (err.includes("no-speech")) {
                            showToast({
                                heading: "Sin voz",
                                text: "No se detectó voz. Intenta de nuevo.",
                                position: "top-right",
                                loaderBg: "#ff6849",
                                icon: "info",
                                hideAfter: 4000,
                                stack: 6,
                            });
                        }
                    },
                    onState: ({ recognizing: r, active, lastError }) => {
                        recognizing.value = !!r;
                        recognitionActive.value = !!active;
                        lastRecognitionError.value = lastError || null;
                        if (typeof _onState === "function")
                            _onState({
                                recognizing: !!r,
                                active: !!active,
                                lastError: lastError || null,
                            });
                    },
                });
            }
            microfono.value = "admin-wrap/assets/images/mic-animate.gif";
            voiceSession.value
                .start()
                .then(({ audioSession: as, dictation: d }) => {
                    if (_audioRevoke) {
                        try {
                            _audioRevoke();
                        } catch (e) {}
                        _audioRevoke = null;
                    }
                    audioSession.value = as;
                    dictation.value = d;
                    recognition.value = d ? d.recognition : recognition.value;
                    mediaStream.value = as ? as.stream : mediaStream.value;
                    mediaRecorder.value = as ? as.mediaRecorder : mediaRecorder.value;
                    audioChunks.value = as ? as.chunks : audioChunks.value;
                    audioBlob.value = null;
                    audioUrl.value = null;
                    recording.value = true;
                    document.addEventListener("visibilitychange", handleVisibilityChange);
                })
                .catch((error) => {
                    recording.value = false;
                    microfono.value = "admin-wrap/assets/images/mic.gif";
                    const cfg = getMicErrorToastConfig(error);
                    showToast({
                        heading: cfg.heading,
                        text: cfg.text,
                        position: "top-right",
                        loaderBg: "#ff6849",
                        icon: cfg.icon,
                        hideAfter: cfg.hideAfter,
                        stack: 6,
                    });
                    console.error("Error al iniciar sesión de voz:", error);
                });
        });
    }

    function toggleSpeechRecognition() {
        if (!mediaRecorder.value || !recognition.value) {
            startRecording();
            return;
        }
        const state = mediaRecorder.value.state;
        if (state === "recording") {
            if (voiceSession.value) {
                try {
                    voiceSession.value.pause();
                } catch (e) {}
            } else {
                try {
                    mediaRecorder.value.pause();
                } catch (e) {}
                pauseRecognition();
            }
            microfono.value = "admin-wrap/assets/images/mic.gif";
        } else if (state === "paused") {
            if (voiceSession.value) {
                try {
                    voiceSession.value.resume();
                } catch (e) {}
            } else {
                try {
                    mediaRecorder.value.resume();
                } catch (e) {}
                resumeRecognition();
            }
            microfono.value = "admin-wrap/assets/images/mic-animate.gif";
        } else if (state === "inactive") {
            startRecording();
        }
    }

    async function sendToBackend(estudioId, estudioProductoId) {
        if (!audioBlob.value || !(audioBlob.value instanceof Blob)) {
            await stopRecording();
        }
        if (!audioBlob.value || !(audioBlob.value instanceof Blob)) {
            return;
        }
        const formData = new FormData();
        formData.append("audio", audioBlob.value, "audio.webm");
        formData.append("estudio_id", estudioId);
        formData.append("estudio_producto_id", estudioProductoId);
        try {
            const res = await axios.post("/api/upload-audio", formData);
            if (res && (res.status === 200 || res.status === 201)) {
                showToast({
                    heading: "Ok!!!",
                    text: "Audio enviado correctamente",
                    position: "top-right",
                    loaderBg: "#ff6849",
                    icon: "success",
                    hideAfter: 3500,
                    stack: 6,
                });
                clearAudioBuffer();
                if (typeof _onAudioSaved === "function") _onAudioSaved();
            } else {
                const msg = res && res.data && res.data.message ? String(res.data.message) : "";
                if (
                    msg.includes("El audio debe tener contenido") ||
                    msg.includes("No se proporcionó un archivo de audio")
                ) {
                    clearAudioBuffer();
                    return;
                }
                showToast({
                    heading: "Upsss!!!",
                    text:
                        res && res.data && res.data.message
                            ? res.data.message
                            : "Error al enviar audio",
                    position: "top-right",
                    loaderBg: "#ff6849",
                    icon: "error",
                    hideAfter: 3500,
                    stack: 6,
                });
            }
        } catch (error) {
            const status = error && error.response ? error.response.status : null;
            const msg =
                error && error.response && error.response.data && error.response.data.message
                    ? String(error.response.data.message)
                    : "";
            if (
                status === 400 &&
                (msg.includes("El audio debe tener contenido") ||
                    msg.includes("No se proporcionó un archivo de audio"))
            ) {
                clearAudioBuffer();
                return;
            }
            showToast({
                heading: "Upsss!!!",
                text: "Fallo al enviar audio",
                position: "top-right",
                loaderBg: "#ff6849",
                icon: "error",
                hideAfter: 3500,
                stack: 6,
            });
        }
    }

    function setupKeyboardShortcut() {
        teardownKeyboardShortcut();
        onKeydownHandler = (e) => {
            if (e.key === "F2" && !e.repeat) {
                e.preventDefault();
                if (recording.value || recognizing.value) stopAll();
                else startRecording();
            }
        };
        window.addEventListener("keydown", onKeydownHandler);
    }

    function teardownKeyboardShortcut() {
        if (onKeydownHandler) {
            try {
                window.removeEventListener("keydown", onKeydownHandler);
            } catch (e) {}
            onKeydownHandler = null;
        }
    }

    onBeforeUnmount(() => {
        stopAll();
        teardownKeyboardShortcut();
    });

    return {
        recognizing,
        recognitionActive,
        recognitionRestartTimer,
        recognitionBackoffMs,
        noSpeechCount,
        maxNoSpeechRetries,
        lastRecognitionError,
        recording,
        microfono,
        dictation,
        voiceSession,
        recognition,
        audioSession,
        mediaRecorder,
        audioChunks,
        audioBlob,
        audioUrl,
        speechInterim,
        uppercase,
        mediaStream,
        capitalizeNext,

        setCallbacks,
        toggleSpeechRecognition,
        startAudioOnly,
        stopRecognition,
        stopAll,
        stopRecording,
        clearAudioBuffer,
        startRecording,
        processCommands,
        sendToBackend,
        setupKeyboardShortcut,
        teardownKeyboardShortcut,
        pauseRecognition,
        resumeRecognition,
        ensureMicApiAvailable,
        getMicErrorToastConfig,
        clearRecognitionRestartTimer,
        safeStartRecognition,
        scheduleRecognitionRestart,
        prepareVoiceCommands,
        stripDiacritics,
        escapeRegExp,
        applyCommandMap,
    };
}

import { ref, onBeforeUnmount } from "vue";
import { processVoiceCommands } from "@/helpers/voice/voiceCommands";

/**
 * Composable para el flujo de dictado por voz y captura de audio en LIDIAG con soporte
 * para todos los comandos de voz de forma directa, predecible y sin dependencias de IA.
 *
 * Flujo del proceso:
 * 1. Inicializa referencias reactivas de estado (recognizing, recording, audioBlob, microfono, uppercase, capitalizeNext).
 * 2. Utiliza exclusivamente la API nativa del navegador (window.SpeechRecognition || window.webkitSpeechRecognition)
 *    configurada con continuous=true, interimResults=true y lang="es-CO".
 * 3. En onresult, procesa el texto final con `processVoiceCommands`, ejecutando comandos de estado (mayúsculas),
 *    saltos de línea, puntuación, símbolos y operadores, y enviando el texto resultante directamente a onText.
 * 4. Gestiona la captura de audio en paralelo mediante MediaRecorder estándar para preservar el audioBlob
 *    necesario al guardar el estudio en backend.
 * 5. Expone funciones de control esenciales (toggleSpeechRecognition, startRecording, stopRecording, stopAll).
 *
 * @returns {Object} Referencias reactivas y métodos de control para dictado y grabación.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-11-23
 */
export function useDictationFlow() {
    const recognizing = ref(false);
    const recording = ref(false);
    const microfono = ref("admin-wrap/assets/images/mic.gif");
    const audioBlob = ref(null);
    const audioUrl = ref(null);
    const speechInterim = ref("");
    const uppercase = ref(false);
    const capitalizeNext = ref(false);

    let _onText = null;
    let _showToast = null;
    let _onState = null;
    let _onAudioSaved = null;

    let recognitionInstance = null;
    let mediaStream = null;
    let mediaRecorder = null;
    let audioChunks = [];
    let onKeydownHandler = null;
    let restartTimer = null;

    /**
     * Limpia cualquier temporizador de reinicio pendiente.
     */
    function clearRestartTimer() {
        if (restartTimer) {
            clearTimeout(restartTimer);
            restartTimer = null;
        }
    }

    /**
     * Instancia y configura el motor nativo de SpeechRecognition con callbacks síncronos.
     *
     * @returns {SpeechRecognition|null}
     */
    function initSpeechRecognition() {
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        if (!SpeechRecognition) return null;

        const recognition = new SpeechRecognition();
        recognition.continuous = true;
        recognition.interimResults = true;
        recognition.lang = "es-CO";

        recognition.onresult = (event) => {
            let interim = "";
            for (let i = event.resultIndex; i < event.results.length; ++i) {
                const res = event.results[i];
                const transcript = res[0]?.transcript || "";
                if (res.isFinal) {
                    const raw = transcript.trim();
                    if (raw) {
                        const {
                            text,
                            uppercase: nextUpper,
                            capitalizeNext: nextCap,
                        } = processVoiceCommands(raw, {
                            uppercase: uppercase.value,
                            capitalizeNext: capitalizeNext.value,
                        });
                        uppercase.value = nextUpper;
                        capitalizeNext.value = nextCap;

                        console.log("[DictationFlow] RAW:", raw, "=> OUT:", JSON.stringify(text));

                        if (text && typeof _onText === "function") {
                            _onText(text);
                        }
                    }
                } else {
                    interim += transcript;
                }
            }
            speechInterim.value = interim;
        };

        recognition.onerror = (event) => {
            if (event.error === "no-speech") return;

            console.warn("[useDictationFlow] Evento de error en SpeechRecognition:", event.error);

            if (event.error === "not-allowed" || event.error === "service-not-allowed") {
                recognizing.value = false;
                microfono.value = "admin-wrap/assets/images/mic.gif";
                clearRestartTimer();
                _showToast?.({
                    title: "Permiso denegado",
                    message: "El reconocimiento de voz no tiene permiso para usar el micrófono.",
                    type: "error",
                });
                return;
            }

            if (event.error === "aborted" || event.error === "network") {
                scheduleRecognitionRestart(150);
            }
        };

        recognition.onend = () => {
            // Reanudar con debounce de 150ms si el usuario sigue dictando
            if (recognizing.value) {
                scheduleRecognitionRestart(150);
            }
        };

        return recognition;
    }

    /**
     * Programa un reinicio seguro con debounce para no bloquear el subsistema de audio en Chromium.
     *
     * @param {number} delayMs - Tiempo de espera en milisegundos.
     */
    function scheduleRecognitionRestart(delayMs = 150) {
        clearRestartTimer();
        if (!recognizing.value) return;

        restartTimer = setTimeout(() => {
            if (!recognizing.value) return;
            try {
                recognitionInstance?.start();
            } catch (_) {
                try {
                    recognitionInstance = initSpeechRecognition();
                    recognitionInstance?.start();
                } catch (_) {
                    /* noop */
                }
            }
        }, delayMs);
    }

    /**
     * Configura los callbacks de comunicación con el componente padre.
     *
     * @param {Object} cbs - Objeto con callbacks opcionales (onText, showToast, onState, onAudioSaved).
     * @returns {void}
     */
    function setCallbacks({ onText, showToast, onState, onAudioSaved } = {}) {
        if (typeof onText === "function") _onText = onText;
        if (typeof showToast === "function") _showToast = showToast;
        if (typeof onState === "function") _onState = onState;
        if (typeof onAudioSaved === "function") _onAudioSaved = onAudioSaved;
    }

    /**
     * Inicializa y arranca la sesión nativa de SpeechRecognition y la captura de MediaRecorder.
     *
     * @returns {Promise<void>}
     */
    async function startRecording() {
        if (recognizing.value || recording.value) {
            await stopAll();
        }

        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

        if (!SpeechRecognition) {
            _showToast?.({
                title: "Navegador no soportado",
                message:
                    "Este navegador no cuenta con la API nativa de reconocimiento de voz (SpeechRecognition).",
                type: "warning",
            });
            return;
        }

        try {
            // 1. Obtener stream de audio para la grabación
            mediaStream = await navigator.mediaDevices.getUserMedia({
                audio: {
                    echoCancellation: true,
                    noiseSuppression: true,
                    autoGainControl: true,
                },
            });

            audioChunks = [];
            mediaRecorder = new MediaRecorder(mediaStream);

            mediaRecorder.ondataavailable = (event) => {
                if (event.data && event.data.size > 0) {
                    audioChunks.push(event.data);
                }
            };

            mediaRecorder.onstop = () => {
                if (audioChunks.length > 0) {
                    const mime = mediaRecorder?.mimeType || "audio/webm";
                    audioBlob.value = new Blob(audioChunks, { type: mime });
                    if (audioUrl.value) URL.revokeObjectURL(audioUrl.value);
                    audioUrl.value = URL.createObjectURL(audioBlob.value);
                    _onAudioSaved?.(audioBlob.value);
                }
            };

            mediaRecorder.start(1000);
            recording.value = true;
        } catch (err) {
            console.error("[useDictationFlow] Error al acceder al micrófono para grabación:", err);
            _showToast?.({
                title: "Error de Micrófono",
                message: "No se pudo acceder al micrófono para la grabación de audio.",
                type: "error",
            });
            return;
        }

        try {
            // 2. Inicializar y arrancar SpeechRecognition nativo
            recognitionInstance = initSpeechRecognition();
            if (recognitionInstance) {
                recognitionInstance.start();
                recognizing.value = true;
                microfono.value = "admin-wrap/assets/images/mic-animate.gif";
                _onState?.({ recognizing: true, recording: true });
            }
        } catch (err) {
            console.error("[useDictationFlow] Error al iniciar SpeechRecognition:", err);
            recognizing.value = false;
            microfono.value = "admin-wrap/assets/images/mic.gif";
        }
    }

    /**
     * Detiene la grabación de MediaRecorder y genera el audioBlob.
     *
     * @returns {Promise<Blob|null>}
     */
    async function stopRecording() {
        return new Promise((resolve) => {
            if (mediaRecorder && mediaRecorder.state !== "inactive") {
                const oldOnStop = mediaRecorder.onstop;
                mediaRecorder.onstop = (e) => {
                    oldOnStop?.(e);
                    recording.value = false;
                    resolve(audioBlob.value);
                };
                try {
                    mediaRecorder.stop();
                } catch (_) {
                    recording.value = false;
                    resolve(audioBlob.value);
                }
            } else {
                recording.value = false;
                resolve(audioBlob.value);
            }

            if (mediaStream) {
                mediaStream.getTracks().forEach((track) => track.stop());
                mediaStream = null;
            }
        });
    }

    /**
     * Detiene por completo tanto el reconocimiento de voz como la grabación de audio.
     *
     * @returns {Promise<void>}
     */
    async function stopAll() {
        clearRestartTimer();
        recognizing.value = false;
        microfono.value = "admin-wrap/assets/images/mic.gif";
        speechInterim.value = "";
        capitalizeNext.value = false;

        if (recognitionInstance) {
            try {
                recognitionInstance.onend = null;
                recognitionInstance.onerror = null;
                recognitionInstance.stop();
            } catch (_) {
                /* noop */
            }
            recognitionInstance = null;
        }

        await stopRecording();
        _onState?.({ recognizing: false, recording: false });
    }

    /**
     * Alterna (toggle) el estado de reconocimiento y grabación.
     *
     * @returns {Promise<void>}
     */
    async function toggleSpeechRecognition() {
        if (recognizing.value || recording.value) {
            await stopAll();
        } else {
            await startRecording();
        }
    }

    /**
     * Limpia el búfer de audio y revoca URLs de objetos creadas.
     *
     * @returns {void}
     */
    function clearAudioBuffer() {
        audioBlob.value = null;
        if (audioUrl.value) {
            URL.revokeObjectURL(audioUrl.value);
            audioUrl.value = null;
        }
        audioChunks = [];
    }

    /**
     * Registra el atajo de teclado F2 para iniciar/detener el dictado de forma rápida.
     *
     * @returns {Function} Función para remover el listener del atajo.
     */
    function setupKeyboardShortcut() {
        teardownKeyboardShortcut();
        onKeydownHandler = (e) => {
            if (e.key === "F2" && !e.repeat) {
                e.preventDefault();
                toggleSpeechRecognition();
            }
        };
        window.addEventListener("keydown", onKeydownHandler);
        return teardownKeyboardShortcut;
    }

    /**
     * Remueve el listener del atajo de teclado F2.
     *
     * @returns {void}
     */
    function teardownKeyboardShortcut() {
        if (onKeydownHandler) {
            window.removeEventListener("keydown", onKeydownHandler);
            onKeydownHandler = null;
        }
    }

    onBeforeUnmount(() => {
        stopAll();
        teardownKeyboardShortcut();
    });

    return {
        recognizing,
        recording,
        uppercase,
        capitalizeNext,
        microfono,
        audioBlob,
        audioUrl,
        speechInterim,
        toggleSpeechRecognition,
        startRecording,
        stopRecording,
        stopAll,
        clearAudioBuffer,
        setCallbacks,
        setupKeyboardShortcut,
        teardownKeyboardShortcut,
    };
}

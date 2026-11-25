import { ref, onBeforeUnmount } from "vue";
import { processVoiceCommands, isKnownVoiceCommand } from "@/helpers/voice/voiceCommands";

/**
 * Composable para el flujo de dictado por voz y captura de audio en LIDIAG con soporte
 * para todos los comandos de voz de forma directa, predecible y sin dependencias de IA.
 *
 * Flujo del proceso:
 * 1. Inicializa referencias reactivas de estado (recognizing, recording, isAudioCapturing,
 *    isSpeechActive, confidence, audioBlob, microfono, uppercase, capitalizeNext, speechInterim).
 * 2. Utiliza exclusivamente la API nativa del navegador (window.SpeechRecognition || window.webkitSpeechRecognition)
 *    configurada con continuous=true, interimResults=true, maxAlternatives=3 y lang="es-CO".
 * 3. Se suscribe al ciclo de vida acústico nativo (onstart, onaudiostart, onspeechstart, onspeechend,
 *    onaudioend, onnomatch, onerror, onend) para coordinar el estado del micrófono y la interfaz con precisión física.
 * 4. Deduplica ráfagas de audio con un puntero incremental `lastProcessedFinalIndex` y selecciona la mejor
 *    hipótesis fonética evaluando comandos de voz prioritarios y métricas nativas de confianza (confidence).
 * 5. Procesa el texto final con `processVoiceCommands`, ejecutando comandos de estado (mayúsculas),
 *    saltos de línea, puntuación, símbolos y operadores, y enviando el texto resultante directamente a onText.
 * 6. Gestiona la captura de audio en paralelo mediante MediaRecorder estándar para preservar el audioBlob
 *    necesario al guardar el estudio en backend.
 * 7. Proporciona una secuencia de parada segura (`stopSpeechRecognitionSafely`) que permite procesar el último
 *    enunciado en vuelo antes de desmantelar la sesión de audio.
 *
 * @returns {Object} Referencias reactivas y métodos de control para dictado y grabación.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-11-23
 */
export function useDictationFlow() {
    const recognizing = ref(false);
    const recording = ref(false);
    const isAudioCapturing = ref(false);
    const isSpeechActive = ref(false);
    const confidence = ref(0);
    const microfono = ref("admin-wrap/assets/images/mic.gif");
    const audioBlob = ref(null);
    const audioUrl = ref(null);
    const speechInterim = ref("");
    const uppercase = ref(false);
    const capitalizeNext = ref(false);
    const isReconnecting = ref(false);

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
    let lastProcessedFinalIndex = -1;
    let isStoppingVoluntarily = false;
    let reconnectAttempt = 0;

    const RECONNECT_BACKOFF_MS = [25, 100, 250, 500, 1000, 2000];
    const MAX_RECONNECT_ATTEMPTS = 10;

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
     * Selecciona la mejor alternativa acústica entre las candidatas devueltas por SpeechRecognition.
     * Si alguna alternativa coincide con un comando de voz conocido, se le otorga prioridad para evitar
     * que un comando sea confundido con texto común. De lo contrario, se opta por la alternativa con mayor
     * nivel de confianza reportado.
     *
     * @param {SpeechRecognitionResult} result - Objeto de resultado emitido por SpeechRecognition.
     * @returns {string} Texto de la alternativa seleccionada.
     */
    function selectBestAlternative(result) {
        if (!result || result.length === 0) return "";
        if (result.length === 1) {
            confidence.value = result[0]?.confidence || 0;
            return result[0]?.transcript || "";
        }

        // 1. Evaluar si alguna alternativa coincide exactamente con un comando de voz
        for (let k = 0; k < result.length; k++) {
            const candTranscript = result[k]?.transcript || "";
            if (isKnownVoiceCommand(candTranscript)) {
                confidence.value = result[k]?.confidence || 0;
                return candTranscript;
            }
        }

        // 2. Si no es comando, seleccionar la de mayor confianza válida
        let best = result[0];
        for (let k = 1; k < result.length; k++) {
            const alt = result[k];
            if (alt && alt.confidence > (best?.confidence || 0)) {
                best = alt;
            }
        }

        confidence.value = best?.confidence || 0;
        return best?.transcript || "";
    }

    /**
     * Instancia y configura el motor nativo de SpeechRecognition con eventos de ciclo de vida completos.
     *
     * @returns {SpeechRecognition|null}
     */
    function initSpeechRecognition() {
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        if (!SpeechRecognition) return null;

        const recognition = new SpeechRecognition();
        recognition.continuous = true;
        recognition.interimResults = true;
        recognition.maxAlternatives = 3;
        recognition.lang = "es-CO";

        recognition.onstart = () => {
            lastProcessedFinalIndex = -1;
            console.info("[DictationFlow] SpeechRecognition iniciado (sesión activa).");
        };

        recognition.onaudiostart = () => {
            isAudioCapturing.value = true;
            isReconnecting.value = false;
            reconnectAttempt = 0;
            microfono.value = "admin-wrap/assets/images/mic-animate.gif";
            _onState?.({
                recognizing: recognizing.value,
                recording: recording.value,
                isAudioCapturing: true,
                isSpeechActive: isSpeechActive.value,
            });
        };

        recognition.onspeechstart = () => {
            isSpeechActive.value = true;
            _onState?.({
                recognizing: recognizing.value,
                recording: recording.value,
                isAudioCapturing: isAudioCapturing.value,
                isSpeechActive: true,
            });
        };

        recognition.onspeechend = () => {
            isSpeechActive.value = false;
            _onState?.({
                recognizing: recognizing.value,
                recording: recording.value,
                isAudioCapturing: isAudioCapturing.value,
                isSpeechActive: false,
            });
        };

        recognition.onaudioend = () => {
            isAudioCapturing.value = false;
            isSpeechActive.value = false;
            _onState?.({
                recognizing: recognizing.value,
                recording: recording.value,
                isAudioCapturing: false,
                isSpeechActive: false,
            });
        };

        recognition.onnomatch = (event) => {
            console.info(
                "[DictationFlow] onnomatch: Entrada de audio no reconocida con suficiente confianza.",
                event
            );
        };

        recognition.onresult = (event) => {
            let interim = "";
            for (let i = event.resultIndex; i < event.results.length; ++i) {
                const res = event.results[i];
                if (res.isFinal) {
                    if (i <= lastProcessedFinalIndex) {
                        continue;
                    }
                    lastProcessedFinalIndex = i;

                    const raw = selectBestAlternative(res).trim();
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
                    const interimCand = res[0]?.transcript || "";
                    interim += interimCand;
                }
            }
            speechInterim.value = interim;
        };

        recognition.onerror = (event) => {
            if (event.error === "no-speech") return;

            console.warn("[useDictationFlow] Evento de error en SpeechRecognition:", event.error);

            if (event.error === "not-allowed" || event.error === "service-not-allowed") {
                recognizing.value = false;
                isAudioCapturing.value = false;
                isSpeechActive.value = false;
                microfono.value = "admin-wrap/assets/images/mic.gif";
                clearRestartTimer();
                _showToast?.({
                    title: "Permiso denegado",
                    message: "El reconocimiento de voz no tiene permiso para usar el micrófono.",
                    type: "error",
                });
                return;
            }

            if (event.error === "audio-capture") {
                recognizing.value = false;
                isAudioCapturing.value = false;
                isSpeechActive.value = false;
                microfono.value = "admin-wrap/assets/images/mic.gif";
                clearRestartTimer();
                _showToast?.({
                    title: "Micrófono no disponible",
                    message: "No se detectó un dispositivo de audio para el reconocimiento de voz.",
                    type: "error",
                });
                return;
            }

            if (
                (event.error === "aborted" || event.error === "network") &&
                !isStoppingVoluntarily
            ) {
                reconnectAttempt += 1;
                scheduleRecognitionRestart(reconnectAttempt);
            }
        };

        recognition.onend = () => {
            isAudioCapturing.value = false;
            isSpeechActive.value = false;
            // Reanudar con backoff si el usuario sigue en sesión de dictado y no fue parada voluntaria
            if (recognizing.value && !isStoppingVoluntarily) {
                scheduleRecognitionRestart(reconnectAttempt);
            } else {
                isReconnecting.value = false;
                reconnectAttempt = 0;
            }
        };

        return recognition;
    }

    /**
     * Programa un reinicio casi inmediato y resiliente con backoff progresivo para no perder
     * palabras tras pausas silenciosas o microcortes del canal de audio en Chromium.
     *
     * @param {number} attempt - Número de intento consecutivo (0 inicia en 25ms).
     */
    function scheduleRecognitionRestart(attempt = 0) {
        clearRestartTimer();
        if (!recognizing.value || isStoppingVoluntarily) {
            isReconnecting.value = false;
            reconnectAttempt = 0;
            return;
        }

        if (attempt > MAX_RECONNECT_ATTEMPTS) {
            recognizing.value = false;
            isAudioCapturing.value = false;
            isSpeechActive.value = false;
            isReconnecting.value = false;
            reconnectAttempt = 0;
            microfono.value = "admin-wrap/assets/images/mic.gif";
            _showToast?.({
                title: "Dictado interrumpido",
                message:
                    "No se pudo reanudar el reconocimiento de voz tras varios intentos. Presiona el micrófono o F2 para continuar.",
                type: "warning",
            });
            return;
        }

        reconnectAttempt = attempt;
        const delayMs = RECONNECT_BACKOFF_MS[Math.min(attempt, RECONNECT_BACKOFF_MS.length - 1)];
        isReconnecting.value = true;

        restartTimer = setTimeout(() => {
            if (!recognizing.value || isStoppingVoluntarily) {
                isReconnecting.value = false;
                reconnectAttempt = 0;
                return;
            }
            try {
                recognitionInstance?.start();
            } catch (_) {
                try {
                    recognitionInstance = initSpeechRecognition();
                    recognitionInstance?.start();
                } catch (_retryErr) {
                    // El canal de audio o red aún no está listo: escalar al siguiente nivel de backoff
                    scheduleRecognitionRestart(attempt + 1);
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

        isStoppingVoluntarily = false;

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
                _onState?.({
                    recognizing: true,
                    recording: true,
                    isAudioCapturing: false,
                    isSpeechActive: false,
                });
            }
        } catch (err) {
            console.error("[useDictationFlow] Error al iniciar SpeechRecognition:", err);
            recognizing.value = false;
            microfono.value = "admin-wrap/assets/images/mic.gif";
        }
    }

    /**
     * Detiene la captura de SpeechRecognition de forma limpia permitiendo procesar el último enunciado.
     *
     * @returns {Promise<void>}
     */
    function stopSpeechRecognitionSafely() {
        return new Promise((resolve) => {
            if (!recognitionInstance) {
                recognizing.value = false;
                isAudioCapturing.value = false;
                isSpeechActive.value = false;
                return resolve();
            }

            let resolved = false;
            const finish = () => {
                if (!resolved) {
                    resolved = true;
                    recognizing.value = false;
                    isAudioCapturing.value = false;
                    isSpeechActive.value = false;
                    isReconnecting.value = false;
                    reconnectAttempt = 0;
                    microfono.value = "admin-wrap/assets/images/mic.gif";
                    speechInterim.value = "";
                    clearRestartTimer();

                    if (recognitionInstance) {
                        try {
                            recognitionInstance.onend = null;
                            recognitionInstance.onerror = null;
                        } catch (_) {
                            /* noop */
                        }
                        recognitionInstance = null;
                    }
                    resolve();
                }
            };

            // Esperar hasta 350ms a que Chrome envíe el último onresult final y luego onend
            const safetyTimeout = setTimeout(finish, 350);

            recognitionInstance.onend = () => {
                clearTimeout(safetyTimeout);
                finish();
            };

            try {
                recognitionInstance.stop();
            } catch (_) {
                clearTimeout(safetyTimeout);
                finish();
            }
        });
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
        isStoppingVoluntarily = true;
        clearRestartTimer();
        await stopSpeechRecognitionSafely();
        await stopRecording();
        isStoppingVoluntarily = false;
        _onState?.({
            recognizing: false,
            recording: false,
            isAudioCapturing: false,
            isSpeechActive: false,
        });
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
        isAudioCapturing,
        isSpeechActive,
        isReconnecting,
        confidence,
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

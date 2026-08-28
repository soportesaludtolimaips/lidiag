/**
 * Voice Session Bridge - Mantiene la sesión de voz activa entre pestañas
 * Usa BroadcastChannel API para comunicar el reconocimiento de voz
 * entre la pestaña del formulario y otras pestañas (como el visor DICOM)
 */

const VOICE_CHANNEL_NAME = "lidiag-voice-session";

/**
 * Crea un puente de sesion de voz usando BroadcastChannel para compartir transcripciones y estado entre pestanas.
 *
 * @param object $options Callbacks onTranscript y onStateChange.
 *
 * @returns {object}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-05-06
 */
export function createVoiceSessionBridge({
    onTranscript = () => {},
    onStateChange = () => {},
} = {}) {
    const channel = new BroadcastChannel(VOICE_CHANNEL_NAME);

    let isActive = false;
    let currentTranscript = "";

    // Escuchar mensajes de otras pestañas
    channel.onmessage = (event) => {
        const { type, payload } = event.data;

        switch (type) {
            case "TRANSCRIPT_UPDATE":
                currentTranscript = payload.text;
                onTranscript(payload.text, payload.isFinal);
                break;
            case "SESSION_START":
                isActive = true;
                onStateChange({ active: true, action: "start" });
                break;
            case "SESSION_STOP":
                isActive = false;
                onStateChange({ active: false, action: "stop" });
                break;
            case "SESSION_PAUSE":
                isActive = false;
                onStateChange({ active: false, action: "pause" });
                break;
            case "SESSION_RESUME":
                isActive = true;
                onStateChange({ active: true, action: "resume" });
                break;
        }
    };

    // Métodos para enviar mensajes a otras pestañas
    const broadcast = {
        sendTranscript: (text, isFinal = false) => {
            channel.postMessage({
                type: "TRANSCRIPT_UPDATE",
                payload: { text, isFinal, timestamp: Date.now() },
            });
        },

        sendStart: () => {
            channel.postMessage({ type: "SESSION_START", payload: { timestamp: Date.now() } });
            isActive = true;
        },

        sendStop: () => {
            channel.postMessage({ type: "SESSION_STOP", payload: { timestamp: Date.now() } });
            isActive = false;
        },

        sendPause: () => {
            channel.postMessage({ type: "SESSION_PAUSE", payload: { timestamp: Date.now() } });
        },

        sendResume: () => {
            channel.postMessage({ type: "SESSION_RESUME", payload: { timestamp: Date.now() } });
            isActive = true;
        },

        // Obtener el texto actual
        getCurrentTranscript: () => currentTranscript,

        // Verificar si hay sesión activa
        isSessionActive: () => isActive,

        // Cerrar el canal
        close: () => {
            channel.close();
        },
    };

    return broadcast;
}

/**
 * Hook para usar en el componente Vue
 */
import { ref, onMounted, onUnmounted } from "vue";

/**
 * Hook de Vue que conecta el puente de sesion de voz y expone el estado remoto reactivo.
 *
 * @returns {object}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-05-06
 */
export function useVoiceSessionBridge() {
    const isRemoteActive = ref(false);
    const remoteTranscript = ref("");
    let bridge = null;

    onMounted(() => {
        bridge = createVoiceSessionBridge({
            onTranscript: (text, isFinal) => {
                remoteTranscript.value = text;
            },
            onStateChange: ({ active }) => {
                isRemoteActive.value = active;
            },
        });
    });

    onUnmounted(() => {
        if (bridge) {
            bridge.close();
        }
    });

    return {
        isRemoteActive,
        remoteTranscript,
        bridge,
    };
}

export default createVoiceSessionBridge;

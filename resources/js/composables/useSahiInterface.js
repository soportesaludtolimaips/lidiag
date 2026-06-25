import { ref, onBeforeUnmount } from "vue";
import axios from "axios";

export function useSahiInterface() {
    const isEnviando = ref(false);
    const isConsultando = ref(false);
    const ultimoEstado = ref(null);
    const pollTimer = ref(null);
    const pollCount = ref(0);

    onBeforeUnmount(() => {
        stopPolling();
    });

    const enviarEcografiaProducto = async (ecografiaProductoId) => {
        isEnviando.value = true;
        try {
            const res = await axios.post(
                "/api/interface-sahi-ecografias/enviar/ecografia-producto",
                { ecografia_producto_id: ecografiaProductoId }
            );
            return res;
        } finally {
            isEnviando.value = false;
        }
    };

    const consultarEstado = async (ecografiaProductoId) => {
        isConsultando.value = true;
        try {
            const res = await axios.get(
                `/api/interface-sahi-ecografias/estado/ecografia-producto/${ecografiaProductoId}`
            );
            ultimoEstado.value = res.data?.data || {};
            return ultimoEstado.value;
        } finally {
            isConsultando.value = false;
        }
    };

    const startPolling = (ecografiaProductoId, onUpdate, onError) => {
        if (pollTimer.value) return;
        pollCount.value = 0;
        const maxAttempts = 36;
        pollTimer.value = setInterval(async () => {
            pollCount.value += 1;
            if (pollCount.value > maxAttempts) {
                stopPolling();
                return;
            }
            try {
                const estado = await consultarEstado(ecografiaProductoId);
                if (onUpdate) onUpdate(estado);
                if (estado?.estado_envio_sahi !== "ENVIANDO") {
                    stopPolling();
                }
            } catch (error) {
                if (onError) onError(error);
                stopPolling();
            }
        }, 5000);
    };

    const stopPolling = () => {
        if (pollTimer.value) {
            clearInterval(pollTimer.value);
            pollTimer.value = null;
        }
        pollCount.value = 0;
    };

    return {
        isEnviando,
        isConsultando,
        ultimoEstado,
        pollTimer,
        pollCount,
        enviarEcografiaProducto,
        consultarEstado,
        startPolling,
        stopPolling,
    };
}

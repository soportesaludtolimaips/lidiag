<template>
    <transition name="fade">
        <span
            v-if="count > 0"
            class="label label-rounded label-danger pull-right clinical-notification-badge"
            :class="{ 'badge-pulse': hasNew }"
            :title="
                count +
                ' solicitud' +
                (count > 1 ? 'es' : '') +
                ' pendiente' +
                (count > 1 ? 's' : '')
            "
            aria-live="polite"
            :aria-label="count + ' solicitudes pendientes'"
            style="margin-top: 3px"
        >
            {{ count > 99 ? "99+" : count }}
        </span>
    </transition>
</template>
<script setup>
import { getCurrentInstance, onBeforeUnmount, onMounted, ref } from "vue";
import axios from "axios";

const { proxy } = getCurrentInstance() || {};
const emitter = proxy?.emitter;

const count = ref(0);
const interval = ref(null);
const hasNew = ref(false);

/**
 * Consulta al backend la cantidad de solicitudes de citas pendientes/no leídas.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-19
 */
async function obtenerConteo() {
    try {
        const resp = await axios.get("/api/citas/notificaciones/no-leidas");
        const nuevoConteo = resp.data.data?.count || 0;
        if (nuevoConteo > count.value && count.value > 0) {
            hasNew.value = true;
            setTimeout(() => {
                hasNew.value = false;
            }, 2000);
        }
        count.value = nuevoConteo;
    } catch {
        // Silencioso para no interrumpir la navegación asistencial
    }
}

/**
 * Manejador reactivo cuando ocurre una actualización global de citas.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-19
 */
function onCitasActualizadas() {
    obtenerConteo();
}

/**
 * Hook de ciclo de vida que realiza la consulta inicial, programa el intervalo periódico y registra listeners en el EventBus.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-19
 */
onMounted(() => {
    obtenerConteo();
    interval.value = setInterval(obtenerConteo, 30000);
    if (emitter) {
        emitter.on("citasActualizadas", onCitasActualizadas);
        emitter.on("sedeSeleccionada", onCitasActualizadas);
    }
});

onBeforeUnmount(() => {
    if (interval.value) {
        clearInterval(interval.value);
    }
    if (emitter) {
        emitter.off("citasActualizadas", onCitasActualizadas);
        emitter.off("sedeSeleccionada", onCitasActualizadas);
    }
});
</script>
<style scoped>
.clinical-notification-badge {
    transition:
        transform 0.2s ease,
        background-color 0.2s ease;
    font-weight: 600;
}
.badge-pulse {
    animation: pulse-attention 1.5s ease-in-out infinite;
}
@keyframes pulse-attention {
    0% {
        transform: scale(1);
        box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7);
    }
    50% {
        transform: scale(1.15);
        box-shadow: 0 0 0 6px rgba(239, 68, 68, 0);
    }
    100% {
        transform: scale(1);
        box-shadow: 0 0 0 0 rgba(239, 68, 68, 0);
    }
}
.fade-enter-active,
.fade-leave-active {
    transition:
        opacity 0.3s ease,
        transform 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
    transform: scale(0.8);
}
</style>

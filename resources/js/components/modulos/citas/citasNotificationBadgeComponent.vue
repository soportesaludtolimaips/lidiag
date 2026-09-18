<template>
    <span
        v-if="count > 0"
        class="label label-rounded label-danger pull-right"
        :title="
            count + ' solicitud' + (count > 1 ? 'es' : '') + ' pendiente' + (count > 1 ? 's' : '')
        "
        aria-live="polite"
        :aria-label="count + ' solicitudes pendientes'"
        style="margin-top: 3px"
    >
        {{ count > 99 ? "99+" : count }}
    </span>
</template>
<script setup>
import { onBeforeUnmount, onMounted, ref } from "vue";
import axios from "axios";

const count = ref(0);
const interval = ref(null);

async function obtenerConteo() {
    try {
        const resp = await axios.get("/api/citas/notificaciones/no-leidas");
        count.value = resp.data.data?.count || 0;
    } catch {
        // silent
    }
}

onMounted(() => {
    obtenerConteo();
    interval.value = setInterval(() => obtenerConteo(), 30000);
});

onBeforeUnmount(() => {
    if (interval.value) {
        clearInterval(interval.value);
    }
});
</script>

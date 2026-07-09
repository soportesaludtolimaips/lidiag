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

<script>
export default {
    data() {
        return {
            count: 0,
            interval: null,
        };
    },
    mounted() {
        this.obtenerConteo();
        this.interval = setInterval(() => this.obtenerConteo(), 30000);
    },
    beforeUnmount() {
        if (this.interval) {
            clearInterval(this.interval);
        }
    },
    methods: {
        async obtenerConteo() {
            try {
                const resp = await axios.get("/api/citas/notificaciones/no-leidas");
                this.count = resp.data.count || 0;
            } catch {
                // silent
            }
        },
    },
};
</script>

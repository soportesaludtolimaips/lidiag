<template>
    <div>
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">Helpdesk</h3>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="javascript:void(0)">Inicio</a></li>
                    <li class="breadcrumb-item active">Notificaciones</li>
                </ol>
            </div>
            <div class="col-md-7 align-self-center text-right">
                <button
                    class="btn btn-info btn-sm"
                    type="button"
                    :disabled="loading"
                    @click="marcarTodas()"
                >
                    <i class="fa fa-check"></i> Marcar todas como leídas
                </button>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Notificaciones</h4>

                        <div class="form-group">
                            <label>
                                <input type="checkbox" v-model="soloNoLeidas" />
                                Solo no leídas
                            </label>
                        </div>

                        <div v-if="loading"><i class="fa fa-spinner fa-spin"></i> Cargando...</div>

                        <div v-else>
                            <div v-if="items.length === 0" class="text-muted">
                                Sin notificaciones.
                            </div>
                            <div
                                v-for="n in itemsFiltradas"
                                :key="n.id"
                                class="alert"
                                :class="n.read_at ? 'alert-secondary' : 'alert-info'"
                            >
                                <div class="d-flex justify-content-between">
                                    <strong>{{ n.data?.evento || "EVENTO" }}</strong>
                                    <span class="text-muted">{{
                                        formatDateTime(n.created_at)
                                    }}</span>
                                </div>
                                <div>
                                    <span v-if="n.data?.folio"
                                        ><strong>{{ n.data.folio }}</strong> -
                                    </span>
                                    {{ n.data?.mensaje || "" }}
                                </div>
                                <div class="m-t-5">
                                    <a
                                        v-if="n.data?.ticket_id"
                                        :href="`/helpdesk-tickets/${n.data.ticket_id}`"
                                        @click.prevent="abrir(n)"
                                    >
                                        Abrir ticket
                                    </a>
                                    <button
                                        v-if="!n.read_at"
                                        class="btn btn-sm btn-outline-dark m-l-10"
                                        @click="marcarLeida(n)"
                                    >
                                        Marcar leída
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { handleAxiosErrorToast } from "@/helpers/alerts.js";

export default {
    props: ["usuarioactual"],
    data() {
        return {
            loading: false,
            soloNoLeidas: false,
            items: [],
        };
    },
    computed: {
        itemsFiltradas() {
            if (!this.soloNoLeidas) return this.items;
            return this.items.filter((x) => !x.read_at);
        },
    },
    async mounted() {
        await this.cargar();
    },
    methods: {
        formatDateTime(dt) {
            if (!dt) return "";
            try {
                return window.dayjs(dt).tz("America/Bogota").format("YYYY-MM-DD HH:mm");
            } catch (_) {
                return String(dt);
            }
        },
        async cargar() {
            this.loading = true;
            try {
                const res = await axios.get("/api/helpdesk-notificaciones", {
                    params: { limit: 100 },
                });
                this.items = res.data?.items || [];
            } catch (error) {
                handleAxiosErrorToast(error, "No se pudieron cargar notificaciones.");
            } finally {
                this.loading = false;
            }
        },
        async marcarLeida(n) {
            try {
                await axios.post(`/api/helpdesk-notificaciones/${n.id}/read`);
                n.read_at = new Date().toISOString();
            } catch (error) {
                handleAxiosErrorToast(error, "No se pudo marcar como leída.");
            }
        },
        async marcarTodas() {
            try {
                await axios.post("/api/helpdesk-notificaciones/read-all");
                await this.cargar();
            } catch (error) {
                handleAxiosErrorToast(error, "No se pudieron marcar todas.");
            }
        },
        async abrir(n) {
            if (!n.read_at) await this.marcarLeida(n);
            if (n.data?.ticket_id) window.location.href = `/helpdesk-tickets/${n.data.ticket_id}`;
        },
    },
};
</script>

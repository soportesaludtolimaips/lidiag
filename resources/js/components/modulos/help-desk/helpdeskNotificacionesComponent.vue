<template>
    <div>
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">Helpdesk</h3>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/helpdesk-tickets">Tickets</a></li>
                    <li class="breadcrumb-item active">Notificaciones</li>
                </ol>
            </div>
            <div class="col-md-7 align-self-center text-right">
                <button
                    type="button"
                    class="btn btn-sm btn-outline-secondary btn-action-clinical m-r-5"
                    :disabled="loading"
                    title="Actualizar notificaciones (F2)"
                    @click="cargar()"
                >
                    <i class="fa fa-refresh mr-1" :class="{ 'fa-spin': loading }"></i>
                    Refrescar <span class="badge badge-light border ml-1">F2</span>
                </button>
                <button
                    class="btn btn-info btn-sm btn-action-clinical"
                    type="button"
                    :disabled="loading"
                    title="Marcar todas como leídas"
                    @click="marcarTodas()"
                >
                    <i class="fa fa-check mr-1"></i> Marcar todas como leídas
                </button>
                <a
                    href="/helpdesk-tickets"
                    class="btn btn-inverse btn-sm btn-action-clinical m-l-5"
                    title="Volver a tickets (Esc)"
                >
                    <i class="fa fa-arrow-left mr-1"></i> Volver
                    <span class="badge badge-secondary ml-1">Esc</span>
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Notificaciones</h4>

                        <div class="form-group">
                            <label for="checkSoloNoLeidas">
                                <input
                                    id="checkSoloNoLeidas"
                                    type="checkbox"
                                    v-model="soloNoLeidas"
                                />
                                Solo no leídas
                            </label>
                        </div>

                        <div v-if="loading"><i class="fa fa-spinner fa-spin"></i> Cargando...</div>

                        <div v-else>
                            <div v-if="items.length === 0" class="text-muted">
                                Sin notificaciones.
                            </div>
                            <template v-else>
                                <div
                                    v-for="n in items"
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
                                <div v-if="totalPaginas > 1" class="text-center m-t-15">
                                    <button
                                        class="btn btn-sm btn-outline-dark"
                                        :disabled="paginaActual <= 1"
                                        @click="irPagina(paginaActual - 1)"
                                    >
                                        <i class="fa fa-chevron-left"></i> Anterior
                                    </button>
                                    <span class="m-x-15"
                                        >Página {{ paginaActual }} de {{ totalPaginas }}</span
                                    >
                                    <button
                                        class="btn btn-sm btn-outline-dark"
                                        :disabled="paginaActual >= totalPaginas"
                                        @click="irPagina(paginaActual + 1)"
                                    >
                                        Siguiente <i class="fa fa-chevron-right"></i>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup>
import { onMounted, ref, watch } from "vue";

import { toastMessage } from "@/helpers/alerts.js";
import { formatDateTime } from "@/helpers/dateUtils.js";
import { helpdesk } from "@/helpers/api.js";
import { useKeyboardShortcuts } from "@/composables/useKeyboardShortcuts.js";

const loading = ref(false);
const soloNoLeidas = ref(false);
const items = ref([]);
const paginaActual = ref(1);
const totalPaginas = ref(1);
const porPagina = ref(50);

async function cargar(pagina) {
    if (pagina) paginaActual.value = pagina;
    loading.value = true;
    try {
        const res = await helpdesk.notificaciones({
            limit: porPagina.value,
            page: paginaActual.value,
            unread_only: soloNoLeidas.value ? 1 : 0,
        });
        items.value = res.data?.data?.items || [];
        totalPaginas.value = res.data?.data?.last_page || 1;
    } catch (_error) {
        toastMessage({
            heading: "Error",
            text: "No se pudieron cargar las notificaciones.",
            icon: "error",
        });
    } finally {
        loading.value = false;
    }
}

function irPagina(pagina) {
    if (pagina < 1 || pagina > totalPaginas.value) return;
    cargar(pagina);
}

async function marcarLeida(n) {
    try {
        await helpdesk.marcarLeida(n.id);
        const idx = items.value.findIndex((x) => x.id === n.id);
        if (idx !== -1) {
            items.value[idx] = { ...items.value[idx], read_at: new Date().toISOString() };
        }
        toastMessage({
            heading: "Listo",
            text: "Notificación marcada como leída.",
            icon: "success",
        });
    } catch (_error) {
        toastMessage({
            heading: "Error",
            text: "No se pudo marcar la notificación.",
            icon: "error",
        });
    }
}

async function marcarTodas() {
    try {
        await helpdesk.marcarTodasLeidas();
        await cargar();
    } catch (_error) {
        toastMessage({
            heading: "Error",
            text: "No se pudieron marcar todas como leídas.",
            icon: "error",
        });
    }
}

async function abrir(n) {
    if (!n.read_at) await marcarLeida(n);
    if (n.data?.ticket_id) window.location.href = `/helpdesk-tickets/${n.data.ticket_id}`;
}

watch(soloNoLeidas, () => {
    paginaActual.value = 1;
    cargar();
});

onMounted(async () => {
    await cargar();
});

useKeyboardShortcuts({
    onSearch: () => {
        if (!loading.value) {
            cargar();
        }
    },
    onEscape: () => {
        window.location.href = "/helpdesk-tickets";
    },
});
</script>

<style scoped>
.btn-action-clinical {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}
.btn-action-clinical:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow:
        0 4px 6px -1px rgba(0, 0, 0, 0.1),
        0 2px 4px -1px rgba(0, 0, 0, 0.06);
}
</style>

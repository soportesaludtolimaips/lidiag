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
    } catch (error) {
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
            heading: "Ok",
            text: "Notificación marcada como leída.",
            icon: "success",
        });
    } catch (error) {}
}
async function marcarTodas() {
    try {
        await helpdesk.marcarTodasLeidas();
        await cargar();
    } catch (error) {}
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
</script>

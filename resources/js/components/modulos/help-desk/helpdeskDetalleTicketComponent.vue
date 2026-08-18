<template>
    <div>
        <div class="row page-titles">
            <div class="col-md-7 align-self-center">
                <h3 class="text-themecolor">Helpdesk</h3>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/helpdesk-tickets">Tickets</a></li>
                    <li class="breadcrumb-item active">{{ ticket?.folio || "#" + ticketId }}</li>
                </ol>
            </div>
            <div class="col-md-5 align-self-center text-right">
                <a href="/helpdesk-tickets" class="btn btn-inverse btn-sm" tabindex="0">
                    <i class="fa fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>

        <div class="row" v-if="loading">
            <div class="col-12">
                <div class="card">
                    <div class="card-body"><i class="fa fa-spinner fa-spin"></i> Cargando...</div>
                </div>
            </div>
        </div>

        <div class="row" v-if="ticket && !loading">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Detalle</h4>
                        <p class="m-b-5">
                            <strong>Folio:</strong> {{ ticket.folio || "#" + ticket.id }}
                        </p>
                        <p class="m-b-5"><strong>Título:</strong> {{ ticket.titulo }}</p>
                        <p class="m-b-5"><strong>Estado:</strong> {{ ticket.estado?.nombre }}</p>
                        <p class="m-b-5">
                            <strong>Prioridad:</strong> {{ ticket.prioridad?.nombre }}
                        </p>
                        <p class="m-b-5">
                            <strong>Categoría:</strong> {{ ticket.categoria?.nombre }}
                        </p>
                        <p class="m-b-5">
                            <strong>Pendiente de:</strong>
                            <span
                                class="badge"
                                :class="
                                    ticket.pendiente_de === 'SOPORTE'
                                        ? 'badge-warning'
                                        : 'badge-primary'
                                "
                            >
                                {{ ticket.pendiente_de }}
                            </span>
                        </p>
                        <p class="m-b-5"><strong>Sede:</strong> {{ ticket.sede?.nom_sede }}</p>
                        <p class="m-b-5">
                            <strong>Solicitante:</strong> {{ ticket.solicitante?.name }}
                        </p>
                        <p class="m-b-5"><strong>Asignado:</strong> {{ ticket.asignado?.name }}</p>
                        <p class="m-b-5">
                            <strong>Visto por soporte:</strong>
                            {{
                                ticket.asignado_visto_at
                                    ? formatDateTime(ticket.asignado_visto_at)
                                    : "No"
                            }}
                        </p>
                        <p class="m-b-5">
                            <strong>Creación:</strong> {{ formatDateTime(ticket.created_at) }}
                        </p>
                        <p class="m-b-5">
                            <strong>Actualización:</strong> {{ formatDateTime(ticket.updated_at) }}
                        </p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Acciones</h4>

                        <div class="form-group">
                            <label for="detalleEstado">Cambiar estado</label>
                            <select
                                id="detalleEstado"
                                v-model="accion.estado_id"
                                class="form-control custom-select"
                            >
                                <option value="">Seleccione...</option>
                                <option v-for="e in estados" :key="e.id" :value="e.id">
                                    {{ e.nombre }}
                                </option>
                            </select>
                        </div>
                        <div class="form-group" v-if="accion.estado_id">
                            <label for="detalleComentarioEstado">Comentario (opcional)</label>
                            <WysiwygEditor v-model="accion.comentario" :rows="4" />
                        </div>
                        <div class="form-group" v-if="estadoSeleccionadoNombre === 'Resuelto'">
                            <label for="detalleSolucion">Descripción solución (obligatoria)</label>
                            <WysiwygEditor v-model="accion.descripcion_solucion" :rows="4" />
                        </div>
                        <button
                            class="btn btn-info btn-sm"
                            type="button"
                            :disabled="savingEstado"
                            @click="cambiarEstado()"
                        >
                            <i v-if="savingEstado" class="fa fa-spinner fa-spin"></i>
                            <i v-else class="fa fa-exchange"></i>
                            Aplicar estado
                        </button>

                        <hr />

                        <div class="form-group">
                            <label for="detalleReasignar">Reasignar (solo manager/admin)</label>
                            <select
                                id="detalleReasignar"
                                v-model="reasignacion.asignado_id"
                                class="form-control custom-select"
                            >
                                <option value="">Seleccione...</option>
                                <option v-for="u in asignables" :key="u.id" :value="u.id">
                                    {{ u.name }} - {{ u.email }}
                                </option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="detalleComentarioReasignar">Comentario (obligatorio)</label>
                            <textarea
                                id="detalleComentarioReasignar"
                                v-model="reasignacion.comentario"
                                class="form-control"
                                rows="3"
                            ></textarea>
                        </div>
                        <button
                            class="btn btn-warning btn-sm"
                            type="button"
                            :disabled="savingReasignar"
                            @click="reasignar()"
                        >
                            <i v-if="savingReasignar" class="fa fa-spinner fa-spin"></i>
                            <i v-else class="fa fa-user"></i>
                            Reasignar
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Descripción</h4>
                        <div v-html="sanitize(ticket.descripcion)"></div>

                        <div v-if="ticket.descripcion_solucion" class="m-t-20">
                            <h5 class="text-success">Solución</h5>
                            <div v-html="sanitize(ticket.descripcion_solucion)"></div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Agregar comentario</h4>
                        <WysiwygEditor v-model="nuevoComentario" :rows="4" />
                        <button
                            class="btn btn-success btn-sm m-t-10"
                            type="button"
                            :disabled="savingComentario"
                            @click="comentar()"
                        >
                            <i v-if="savingComentario" class="fa fa-spinner fa-spin"></i>
                            <i v-else class="fa fa-comment"></i>
                            Enviar
                        </button>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Adjuntos</h4>
                        <input
                            id="detalleAdjuntos"
                            ref="files"
                            type="file"
                            class="form-control"
                            multiple
                            accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx"
                        />
                        <button
                            class="btn btn-secondary btn-sm m-t-10"
                            type="button"
                            :disabled="savingAdjuntos"
                            @click="subirAdjuntos()"
                        >
                            <i v-if="savingAdjuntos" class="fa fa-spinner fa-spin"></i>
                            <i v-else class="fa fa-paperclip"></i>
                            Subir
                        </button>
                        <small class="text-muted d-block">Solo descarga (sin vista previa).</small>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Historial</h4>
                        <div aria-live="polite" aria-atomic="true">
                            <div
                                v-if="!ticket.historial || !ticket.historial.length"
                                class="text-muted"
                            >
                                Sin historial.
                            </div>
                            <ul v-else class="list-group">
                                <li
                                    v-for="h in ticket.historial"
                                    :key="h.id"
                                    class="list-group-item"
                                >
                                    <div class="d-flex justify-content-between">
                                        <strong>{{ h.tipo }}</strong>
                                        <span class="text-muted">{{
                                            formatDateTime(h.created_at)
                                        }}</span>
                                    </div>
                                    <div class="text-muted">{{ h.user?.name }}</div>
                                    <div
                                        v-if="h.comentario"
                                        class="m-t-5"
                                        v-html="sanitize(h.comentario)"
                                    ></div>

                                    <div v-if="h.adjuntos && h.adjuntos.length" class="m-t-10">
                                        <div v-for="a in h.adjuntos" :key="a.id">
                                            <a
                                                :href="helpdesk.descargarAdjunto(a.id)"
                                                target="_blank"
                                            >
                                                {{ a.original_name }}
                                            </a>
                                            <span class="text-muted">
                                                - {{ formatDateTime(a.created_at) }}</span
                                            >
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup>
import { computed, onMounted, ref } from "vue";

import DOMPurify from "dompurify";
import { toastMessage, confirmAction } from "@/helpers/alerts.js";
import { formatDateTime } from "@/helpers/dateUtils.js";
import WysiwygEditor from "@/components/shared/WysiwygEditor.vue";
import { helpdesk } from "@/helpers/api.js";

const props = defineProps({
    ticketId: { type: [Number, String], required: true },
});

const loading = ref(false);
const savingComentario = ref(false);
const savingEstado = ref(false);
const savingReasignar = ref(false);
const savingAdjuntos = ref(false);
const ticket = ref(null);
const estados = ref([]);
const asignables = ref([]);
const nuevoComentario = ref("");
const files = ref(null);
const accion = ref({
    estado_id: "",
    comentario: "",
    descripcion_solucion: "",
});
const reasignacion = ref({
    asignado_id: "",
    comentario: "",
});

const estadoSeleccionadoNombre = computed(() => {
    const e = estados.value.find((x) => String(x.id) === String(accion.value.estado_id));
    return e ? e.nombre : "";
});

function sanitize(dirty) {
    return DOMPurify.sanitize(dirty || "");
}
async function recargarTicket() {
    try {
        const ticketRes = await helpdesk.ticket(props.ticketId);
        ticket.value = ticketRes.data;
    } catch (error) {}
}
async function cargar() {
    loading.value = true;
    try {
        const ticketRes = await helpdesk.ticket(props.ticketId);
        ticket.value = ticketRes.data;

        // Estados (puede fallar por permisos; no bloquea el detalle)
        try {
            const estadosRes = await helpdesk.estados();
            estados.value = (estadosRes.data || []).filter((x) => x.activo);
        } catch (e) {
            console.warn("No se pudieron cargar estados", e);
            estados.value = [];
        }

        // Asignables (solo necesario para reasignación; no bloquea el detalle)
        try {
            const asignablesRes = await helpdesk.asignables();
            asignables.value = asignablesRes.data || [];
        } catch (e) {
            console.warn("No se pudieron cargar usuarios asignables", e);
            asignables.value = [];
        }
    } catch (error) {
    } finally {
        loading.value = false;
    }
}
async function comentar() {
    if (!nuevoComentario.value) return;
    savingComentario.value = true;
    try {
        await helpdesk.comentar(props.ticketId, { comentario: nuevoComentario.value });
        nuevoComentario.value = "";
        toastMessage({ heading: "Ok", text: "Comentario agregado.", icon: "success" });
        await recargarTicket();
    } catch (error) {
    } finally {
        savingComentario.value = false;
    }
}
async function cambiarEstado() {
    if (!accion.value.estado_id) return;
    const ok = await confirmAction({
        title: "Cambiar estado",
        text: "¿Estás seguro de aplicar este cambio de estado?",
        icon: "warning",
    });
    if (!ok) return;
    savingEstado.value = true;
    try {
        await helpdesk.cambiarEstado(props.ticketId, {
            estado_id: accion.value.estado_id,
            comentario: accion.value.comentario || null,
            descripcion_solucion: accion.value.descripcion_solucion || null,
        });
        accion.value = { estado_id: "", comentario: "", descripcion_solucion: "" };
        toastMessage({ heading: "Ok", text: "Estado actualizado.", icon: "success" });
        await recargarTicket();
    } catch (error) {
    } finally {
        savingEstado.value = false;
    }
}
async function reasignar() {
    if (!reasignacion.value.asignado_id || !reasignacion.value.comentario) return;
    const ok = await confirmAction({
        title: "Reasignar ticket",
        text: "¿Estás seguro de reasignar este ticket?",
        icon: "warning",
    });
    if (!ok) return;
    savingReasignar.value = true;
    try {
        await helpdesk.reasignar(props.ticketId, {
            asignado_id: reasignacion.value.asignado_id,
            comentario: reasignacion.value.comentario,
        });
        reasignacion.value = { asignado_id: "", comentario: "" };
        toastMessage({ heading: "Ok", text: "Ticket reasignado.", icon: "success" });
        await recargarTicket();
    } catch (error) {
    } finally {
        savingReasignar.value = false;
    }
}
async function subirAdjuntos() {
    const input = files.value;
    const rawFiles = input && input.files ? Array.from(input.files) : [];
    if (!rawFiles.length) return;

    savingAdjuntos.value = true;
    try {
        const fd = new FormData();
        rawFiles.forEach((f) => fd.append("files[]", f));
        fd.append("comentario", "Adjuntos agregados.");
        await helpdesk.subirAdjuntos(props.ticketId, fd);
        if (input) input.value = "";
        toastMessage({ heading: "Ok", text: "Adjuntos subidos.", icon: "success" });
        await recargarTicket();
    } catch (error) {
    } finally {
        savingAdjuntos.value = false;
    }
}

onMounted(async () => {
    await cargar();
});
</script>

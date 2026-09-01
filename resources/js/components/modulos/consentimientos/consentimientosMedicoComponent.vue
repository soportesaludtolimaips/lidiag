<template>
    <div>
        <!-- Banner de bloqueo -->
        <div
            v-if="estado.bloqueado"
            class="alert alert-danger d-flex justify-content-between align-items-center"
        >
            <span>
                <strong>Lecturas bloqueadas.</strong>
                Tiene {{ estado.pendientes }} consentimiento(s) pendiente(s) de su firma.
            </span>
            <button class="btn btn-sm btn-danger" type="button" @click="mostrarModal = true">
                Ver pendientes
            </button>
        </div>

        <div v-else-if="estado.liberado_hasta" class="alert alert-warning">
            Bloqueo liberado temporalmente hasta {{ formatFecha(estado.liberado_hasta) }}.
        </div>

        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">Consentimientos pendientes de firma</h5>
                    <button
                        class="btn btn-success"
                        type="button"
                        :disabled="
                            firmandoTodos ||
                            !documentos.length ||
                            !can('consentimientos.firmar_medico')
                        "
                        @click="firmarTodos"
                    >
                        <span v-if="firmandoTodos">Firmando…</span>
                        <span v-else>Firmar todos</span>
                    </button>
                </div>

                <div v-if="cargando" class="text-muted">Cargando…</div>

                <table v-else class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Paciente</th>
                            <th>Documento</th>
                            <th>Generado</th>
                            <th class="text-end">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="d in documentos" :key="d.id">
                            <td>{{ d.id }}</td>
                            <td>{{ d.nombres }} {{ d.apellidos }}</td>
                            <td>{{ d.num_docu }}</td>
                            <td>{{ formatFecha(d.created_at) }}</td>
                            <td class="text-end">
                                <button
                                    class="btn btn-sm btn-primary"
                                    type="button"
                                    :disabled="
                                        firmandoId === d.id || !can('consentimientos.firmar_medico')
                                    "
                                    @click="firmar(d.id)"
                                >
                                    <span v-if="firmandoId === d.id">Firmando…</span>
                                    <span v-else>Firmar</span>
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!documentos.length">
                            <td colspan="5" class="text-muted text-center">Sin pendientes</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal simple de pendientes -->
        <div v-if="mostrarModal" class="modal-backdrop-custom" @click.self="mostrarModal = false">
            <div class="modal-card card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <h5 class="card-title text-danger">⚠️ Consentimientos pendientes</h5>
                        <button
                            class="btn-close"
                            type="button"
                            @click="mostrarModal = false"
                        ></button>
                    </div>
                    <p class="text-muted">
                        No puede realizar lecturas hasta firmar los siguientes consentimientos:
                    </p>
                    <ul class="list-group mb-3">
                        <li
                            v-for="d in documentos"
                            :key="d.id"
                            class="list-group-item d-flex justify-content-between align-items-center"
                        >
                            <span>{{ d.nombres }} {{ d.apellidos }} · {{ d.num_docu }}</span>
                            <button
                                class="btn btn-sm btn-primary"
                                type="button"
                                :disabled="firmandoId === d.id"
                                @click="firmar(d.id)"
                            >
                                Firmar
                            </button>
                        </li>
                    </ul>
                    <div class="text-end">
                        <button
                            class="btn btn-success"
                            type="button"
                            :disabled="firmandoTodos || !documentos.length"
                            @click="firmarTodos"
                        >
                            Firmar todos
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, reactive, ref } from "vue";

import { consentimientos as api } from "../../../helpers/api.js";
import usePermissions from "../../../helpers/permissions.js";
import { alertSuccess, handleAxiosErrorToast } from "../../../helpers/alerts.js";

const props = defineProps({
    permisos: { type: Array, default: () => [] },
});

const { can } = usePermissions(props.permisos);

const cargando = ref(true);
const estado = reactive({ bloqueado: false, pendientes: 0, liberado_hasta: null });
const documentos = ref([]);
const firmandoId = ref(null);
const firmandoTodos = ref(false);
const mostrarModal = ref(false);

function formatFecha(f) {
    return f ? new Date(f).toLocaleString() : "";
}

async function cargarEstado() {
    cargando.value = true;
    try {
        const res = await api.bloqueoEstado();
        const d = res.data.data;
        estado.bloqueado = d.bloqueado;
        estado.pendientes = d.pendientes;
        estado.liberado_hasta = d.liberado_hasta;
        documentos.value = d.documentos ?? [];
        if (estado.bloqueado) mostrarModal.value = true;
    } catch (e) {
        handleAxiosErrorToast(e, "No fue posible cargar el estado.");
    } finally {
        cargando.value = false;
    }
}

async function firmar(id) {
    firmandoId.value = id;
    try {
        await api.firmarMedico({ documento_id: id });
        alertSuccess("Consentimiento firmado");
        await cargarEstado();
    } catch (e) {
        handleAxiosErrorToast(e, "No fue posible firmar.");
    } finally {
        firmandoId.value = null;
    }
}

async function firmarTodos() {
    firmandoTodos.value = true;
    try {
        const ids = documentos.value.map((d) => d.id);
        for (const id of ids) {
            await api.firmarMedico({ documento_id: id });
        }
        alertSuccess("Consentimientos firmados");
        await cargarEstado();
        if (!estado.bloqueado) mostrarModal.value = false;
    } catch (e) {
        handleAxiosErrorToast(e, "No fue posible firmar todos.");
        await cargarEstado();
    } finally {
        firmandoTodos.value = false;
    }
}

onMounted(cargarEstado);
</script>

<style scoped>
.modal-backdrop-custom {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1050;
}
.modal-card {
    width: min(600px, 92vw);
    max-height: 85vh;
    overflow: auto;
}
</style>

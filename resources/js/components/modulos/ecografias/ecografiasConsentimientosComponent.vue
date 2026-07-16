<template>
    <div>
        <div class="row page-titles">
            <div class="col-md-6 align-self-center">
                <h3 class="text-themecolor">EcografÃ­as</h3>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <button type="button" class="btn btn-link p-0 breadcrumb-btn">
                            Inicio
                        </button>
                    </li>
                    <li class="breadcrumb-item">
                        <button type="button" class="btn btn-link p-0 breadcrumb-btn">
                            EcografÃ­as
                        </button>
                    </li>
                    <li class="breadcrumb-item active">Consentimientos</li>
                </ol>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Buscar consentimientos</h4>
                <div class="row">
                    <div class="col-md-4">
                        <label for="filtros_q">Paciente / Documento</label>
                        <input
                            id="filtros_q"
                            class="form-control"
                            v-model="filtros.q"
                            placeholder="Nombre o documento"
                        />
                    </div>
                    <div class="col-md-3">
                        <label for="filtros_desde">Fecha inicio</label>
                        <input
                            id="filtros_desde"
                            type="date"
                            class="form-control"
                            v-model="filtros.desde"
                        />
                    </div>
                    <div class="col-md-3">
                        <label for="filtros_hasta">Fecha fin</label>
                        <input
                            id="filtros_hasta"
                            type="date"
                            class="form-control"
                            v-model="filtros.hasta"
                        />
                    </div>
                </div>

                <div class="mt-3 d-flex gap-2">
                    <button
                        class="btn btn-success"
                        type="button"
                        @click="buscar"
                        :disabled="loading"
                    >
                        <i class="fa" :class="loading ? 'fa-spinner fa-spin' : 'fa-search'"></i>
                        Buscar
                    </button>

                    <button
                        v-if="can('ecografias.consentimientos.pdf.generar')"
                        class="btn btn-info"
                        type="button"
                        @click="generarPdfSeleccionado"
                        :disabled="loading || selectedIds.length !== 1"
                    >
                        Generar PDF
                    </button>

                    <button
                        v-if="can('ecografias.consentimientos.pdf.descargar')"
                        class="btn btn-primary"
                        type="button"
                        @click="descargarPdfSeleccionado"
                        :disabled="
                            loading || selectedIds.length !== 1 || !selectedConsentimiento?.pdf_path
                        "
                    >
                        Descargar PDF
                    </button>

                    <button
                        v-if="can('ecografias.consentimientos.zip.generar')"
                        class="btn btn-warning"
                        type="button"
                        @click="generarZip"
                        :disabled="loading || selectedIds.length < 2"
                    >
                        Descargar ZIP
                    </button>

                    <button
                        v-if="can('ecografias.consentimientos.anular')"
                        class="btn btn-danger"
                        type="button"
                        @click="anularSeleccionado"
                        :disabled="loading || selectedIds.length !== 1"
                    >
                        Anular
                    </button>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Resultados</h4>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th width="40">
                                    <input
                                        type="checkbox"
                                        :checked="allSelectableSelected"
                                        @change="toggleSelectAll"
                                        aria-label="Seleccionar todos"
                                    />
                                </th>
                                <th>Paciente</th>
                                <th>Documento</th>
                                <th>Examen</th>
                                <th>Estado</th>
                                <th>PDF</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="c in registros" :key="c.id">
                                <td>
                                    <input
                                        type="checkbox"
                                        :disabled="!esSeleccionable(c)"
                                        :checked="selectedMap[c.id] === true"
                                        @change="toggleSelect(c)"
                                        aria-label="Seleccionar consentimiento"
                                    />
                                </td>
                                <td>{{ pacienteNombre(c) }}</td>
                                <td>
                                    {{
                                        c.ecografia_producto?.ecografia?.paciente?.num_docu || "â€”"
                                    }}
                                </td>
                                <td>{{ c.producto?.nom_produc || "â€”" }}</td>
                                <td>
                                    <span v-if="c.estado === 'COMPLETO'" class="text-success"
                                        >COMPLETO</span
                                    >
                                    <span v-else class="text-danger">{{ c.estado }}</span>
                                </td>
                                <td>
                                    <span v-if="c.pdf_path" class="text-success">Generado</span>
                                    <span v-else class="text-muted">â€”</span>
                                </td>
                            </tr>
                            <tr v-if="registros.length === 0">
                                <td colspan="6" class="text-center text-muted">Sin resultados.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup>
import { computed, onMounted, ref } from "vue";

import { handleAxiosErrorToast } from "@/helpers/alerts";
import usePermissions from "@/helpers/permissions.js";
import axios from "axios";

const props = defineProps({ usuarioactual: Object, permisos: Array });

const filtros = ref({
    q: "",
    desde: "",
    hasta: "",
    sede_id: sessionStorage.getItem("ST-sede") || "",
});
const registros = ref([]);
const loading = ref(false);
const selectedMap = ref({});

const selectedIds = computed(() => {
    return Object.keys(selectedMap.value)
        .filter((k) => selectedMap.value[k])
        .map((k) => Number(k));
});
const selectedConsentimiento = computed(() => {
    if (selectedIds.value.length !== 1) return null;
    const id = selectedIds.value[0];
    return registros.value.find((r) => Number(r.id) === Number(id)) || null;
});
const allSelectableSelected = computed(() => {
    const selectable = registros.value.filter(esSeleccionable);
    if (selectable.length === 0) return false;
    return selectable.every((r) => selectedMap.value[r.id] === true);
});

function pacienteNombre(c) {
    const p = c?.ecografia_producto?.ecografia?.paciente;
    if (!p) return "â€”";
    return String(p.nombres || "").trim();
}
function esSeleccionable(c) {
    return c?.estado === "COMPLETO";
}
function toggleSelect(c) {
    const id = Number(c.id);
    if (!esSeleccionable(c)) return;
    selectedMap.value[id] = !selectedMap.value[id];
}
function toggleSelectAll(e) {
    const checked = !!e.target.checked;
    const next = { ...selectedMap.value };
    registros.value.forEach((r) => {
        if (!esSeleccionable(r)) return;
        next[r.id] = checked;
    });
    selectedMap.value = next;
}
async function buscar() {
    loading.value = true;
    selectedMap.value = {};
    try {
        const res = await axios.get("/api/ecografias-consentimientos", {
            params: filtros.value,
        });
        registros.value = Array.isArray(res.data?.data) ? res.data.data : [];
    } catch (error) {
        handleAxiosErrorToast(error, "No fue posible listar consentimientos.");
        registros.value = [];
    } finally {
        loading.value = false;
    }
}
async function generarPdfSeleccionado() {
    if (selectedIds.value.length !== 1) return;
    const id = selectedIds.value[0];
    loading.value = true;
    try {
        await axios.post(`/api/ecografias-consentimientos/${id}/pdf`);
        await buscar();
    } catch (error) {
        handleAxiosErrorToast(error, "No fue posible generar el PDF.");
    } finally {
        loading.value = false;
    }
}
function descargarPdfSeleccionado() {
    const c = selectedConsentimiento.value;
    if (!c?.id || !c?.pdf_path) return;
    window.open(
        `/api/ecografias-consentimientos/${c.id}/pdf/download`,
        "_blank",
        "noopener,noreferrer"
    );
}
async function generarZip() {
    if (selectedIds.value.length < 2) return;
    loading.value = true;
    try {
        const res = await axios.post(
            "/api/ecografias-consentimientos/zip",
            { ids: selectedIds.value },
            { responseType: "blob" }
        );
        const blob = new Blob([res.data], { type: "application/zip" });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement("a");
        a.href = url;
        a.download = "consentimientos.zip";
        document.body.appendChild(a);
        a.click();
        a.remove();
        window.URL.revokeObjectURL(url);
    } catch (error) {
        handleAxiosErrorToast(error, "No fue posible generar el ZIP.");
    } finally {
        loading.value = false;
    }
}
async function anularSeleccionado() {
    if (selectedIds.value.length !== 1) return;
    const id = selectedIds.value[0];
    const Swal = getSwal();
    if (!Swal) {
        const obs = window.prompt("ObservaciÃ³n de anulaciÃ³n (obligatoria):");
        if (!obs || !String(obs).trim()) return;
        await _ejecutarAnulacion(id, obs);
        return;
    }
    const result = await Swal.fire({
        title: "Anular consentimiento",
        text: "Indique la observaciÃ³n de anulaciÃ³n (obligatoria)",
        icon: "warning",
        input: "textarea",
        inputPlaceholder: "Motivo de anulaciÃ³n...",
        showCancelButton: true,
        confirmButtonText: "SÃ­, anular",
        cancelButtonText: "Cancelar",
        confirmButtonColor: "#DD6B55",
        preConfirm: (obs) => {
            if (!obs || !String(obs).trim()) {
                Swal.showValidationMessage("Debe indicar una observaciÃ³n");
            }
            return obs;
        },
    });
    if (result.isConfirmed) {
        await _ejecutarAnulacion(id, result.value);
    }
}
async function _ejecutarAnulacion(id, obs) {
    loading.value = true;
    try {
        await axios.post(`/api/ecografias-consentimientos/${id}/anular`, {
            observacion: String(obs).trim(),
        });
        await buscar();
    } catch (error) {
        handleAxiosErrorToast(error, "No fue posible anular el consentimiento.");
    } finally {
        loading.value = false;
    }
}

onMounted(() => {
    buscar();
});
</script>
<style scoped>
.breadcrumb-btn {
    vertical-align: baseline;
}
</style>

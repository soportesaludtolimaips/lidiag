<template>
    <div>
        <div class="row page-titles">
            <div class="col-md-6 align-self-center">
                <h3 class="text-themecolor">Ecografías</h3>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <button type="button" class="btn btn-link p-0 breadcrumb-btn">
                            Inicio
                        </button>
                    </li>
                    <li class="breadcrumb-item">
                        <button type="button" class="btn btn-link p-0 breadcrumb-btn">
                            Ecografías
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
                                    {{ c.ecografia_producto?.ecografia?.paciente?.num_docu || "—" }}
                                </td>
                                <td>{{ c.producto?.nom_produc || "—" }}</td>
                                <td>
                                    <span v-if="c.estado === 'COMPLETO'" class="text-success"
                                        >COMPLETO</span
                                    >
                                    <span v-else class="text-danger">{{ c.estado }}</span>
                                </td>
                                <td>
                                    <span v-if="c.pdf_path" class="text-success">Generado</span>
                                    <span v-else class="text-muted">—</span>
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

/**
 * Obtiene la lista de identificadores de consentimientos actualmente seleccionados.
 *
 * @returns {Array<number>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-02-13
 */
const selectedIds = computed(() => {
    return Object.keys(selectedMap.value)
        .filter((k) => selectedMap.value[k])
        .map((k) => Number(k));
});
/**
 * Devuelve el consentimiento seleccionado cuando exactamente uno está marcado, o null en caso contrario.
 *
 * @returns {Object|null}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-02-13
 */
const selectedConsentimiento = computed(() => {
    if (selectedIds.value.length !== 1) return null;
    const id = selectedIds.value[0];
    return registros.value.find((r) => Number(r.id) === Number(id)) || null;
});
/**
 * Indica si todos los consentimientos seleccionables (estado COMPLETO) se encuentran marcados.
 *
 * @returns {boolean}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-02-13
 */
const allSelectableSelected = computed(() => {
    const selectable = registros.value.filter(esSeleccionable);
    if (selectable.length === 0) return false;
    return selectable.every((r) => selectedMap.value[r.id] === true);
});

/**
 * Extrae y retorna el nombre del paciente asociado a un consentimiento.
 *
 * @param {Object} c Registro de consentimiento del cual se obtiene el paciente.
 *
 * @returns {string}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-02-13
 */
function pacienteNombre(c) {
    const p = c?.ecografia_producto?.ecografia?.paciente;
    if (!p) return "—";
    return String(p.nombres || "").trim();
}
/**
 * Determina si un consentimiento puede ser seleccionado (solo aquellos en estado COMPLETO).
 *
 * @param {Object} c Registro de consentimiento a evaluar.
 *
 * @returns {boolean}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-02-13
 */
function esSeleccionable(c) {
    return c?.estado === "COMPLETO";
}
/**
 * Alterna la selección de un consentimiento individual si este es seleccionable.
 *
 * @param {Object} c Registro de consentimiento a seleccionar o deseleccionar.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-02-13
 */
function toggleSelect(c) {
    const id = Number(c.id);
    if (!esSeleccionable(c)) return;
    selectedMap.value[id] = !selectedMap.value[id];
}
/**
 * Marca o desmarca todos los consentimientos seleccionables según el estado del checkbox de cabecera.
 *
 * @param {Event} e Evento de cambio del checkbox de selección total.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-02-13
 */
function toggleSelectAll(e) {
    const checked = !!e.target.checked;
    const next = { ...selectedMap.value };
    registros.value.forEach((r) => {
        if (!esSeleccionable(r)) return;
        next[r.id] = checked;
    });
    selectedMap.value = next;
}
/**
 * Consulta al backend el listado de consentimientos aplicando los filtros de búsqueda.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-02-13
 */
async function buscar() {
    loading.value = true;
    selectedMap.value = {};
    try {
        const res = await axios.get("/api/ecografias-consentimientos", {
            params: filtros.value,
        });
        registros.value = Array.isArray(res.data?.data) ? res.data.data : [];
    } catch (error) {
        registros.value = [];
    } finally {
        loading.value = false;
    }
}
/**
 * Genera el PDF del consentimiento seleccionado (cuando hay exactamente uno) y refresca la lista.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-02-13
 */
async function generarPdfSeleccionado() {
    if (selectedIds.value.length !== 1) return;
    const id = selectedIds.value[0];
    loading.value = true;
    try {
        await axios.post(`/api/ecografias-consentimientos/${id}/pdf`);
        await buscar();
    } catch (error) {
    } finally {
        loading.value = false;
    }
}
/**
 * Abre en una nueva pestaña la descarga del PDF del consentimiento seleccionado.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-02-13
 */
function descargarPdfSeleccionado() {
    const c = selectedConsentimiento.value;
    if (!c?.id || !c?.pdf_path) return;
    window.open(
        `/api/ecografias-consentimientos/${c.id}/pdf/download`,
        "_blank",
        "noopener,noreferrer"
    );
}
/**
 * Genera y descarga un archivo ZIP con los PDF de los consentimientos seleccionados (mínimo dos).
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-02-13
 */
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
    } finally {
        loading.value = false;
    }
}
/**
 * Solicita la observación de anulación (vía SweetAlert o prompt) y anula el consentimiento seleccionado.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-02-13
 */
async function anularSeleccionado() {
    if (selectedIds.value.length !== 1) return;
    const id = selectedIds.value[0];
    const Swal = getSwal();
    if (!Swal) {
        const obs = window.prompt("Observación de anulación (obligatoria):");
        if (!obs || !String(obs).trim()) return;
        await _ejecutarAnulacion(id, obs);
        return;
    }
    const result = await Swal.fire({
        title: "Anular consentimiento",
        text: "Indique la observación de anulación (obligatoria)",
        icon: "warning",
        input: "textarea",
        inputPlaceholder: "Motivo de anulación...",
        showCancelButton: true,
        confirmButtonText: "Sí, anular",
        cancelButtonText: "Cancelar",
        confirmButtonColor: "#DD6B55",
        preConfirm: (obs) => {
            if (!obs || !String(obs).trim()) {
                Swal.showValidationMessage("Debe indicar una observación");
            }
            return obs;
        },
    });
    if (result.isConfirmed) {
        await _ejecutarAnulacion(id, result.value);
    }
}
/**
 * Envía la petición de anulación de un consentimiento con su observación y refresca la lista.
 *
 * @param {number} id Identificador del consentimiento a anular.
 * @param {string} obs Observación obligatoria de la anulación.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-02-13
 */
async function _ejecutarAnulacion(id, obs) {
    loading.value = true;
    try {
        await axios.post(`/api/ecografias-consentimientos/${id}/anular`, {
            observacion: String(obs).trim(),
        });
        await buscar();
    } catch (error) {
    } finally {
        loading.value = false;
    }
}

/**
 * Hook de ciclo de vida que ejecuta la búsqueda inicial de consentimientos al montar el componente.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-02-13
 */
onMounted(() => {
    buscar();
});
</script>
<style scoped>
.breadcrumb-btn {
    vertical-align: baseline;
}
</style>

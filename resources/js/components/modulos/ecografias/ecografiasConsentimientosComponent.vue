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
            <div class="col-md-6 align-self-center text-right d-none d-md-block">
                <button
                    type="button"
                    class="btn btn-sm btn-outline-secondary"
                    :disabled="loading"
                    title="Actualizar listado de consentimientos (F2)"
                    @click="buscar"
                >
                    <i class="fa fa-refresh mr-1" :class="{ 'fa-spin': loading }"></i>
                    Refrescar <span class="badge badge-light border ml-1">F2</span>
                </button>
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
                            @keyup.enter="buscar"
                        />
                    </div>
                    <div class="col-md-3">
                        <label for="filtros_desde">Fecha inicio</label>
                        <input
                            id="filtros_desde"
                            type="date"
                            class="form-control"
                            v-model="filtros.desde"
                            @change="buscar"
                        />
                    </div>
                    <div class="col-md-3">
                        <label for="filtros_hasta">Fecha fin</label>
                        <input
                            id="filtros_hasta"
                            type="date"
                            class="form-control"
                            v-model="filtros.hasta"
                            @change="buscar"
                        />
                    </div>
                </div>

                <div class="mt-3 d-flex flex-wrap gap-2">
                    <button
                        class="btn btn-success btn-action-clinical"
                        type="button"
                        @click="buscar"
                        :disabled="loading"
                    >
                        <i
                            class="fa mr-1"
                            :class="loading ? 'fa-spinner fa-spin' : 'fa-search'"
                        ></i>
                        Buscar <span class="badge badge-light border ml-1">F2</span>
                    </button>

                    <button
                        v-if="can('ecografias.consentimientos.pdf.generar')"
                        class="btn btn-info btn-action-clinical"
                        type="button"
                        @click="generarPdfSeleccionado"
                        :disabled="loading || selectedIds.length !== 1"
                    >
                        <i class="fa fa-file-pdf-o mr-1"></i>
                        Generar PDF
                    </button>

                    <button
                        v-if="can('ecografias.consentimientos.pdf.descargar')"
                        class="btn btn-primary btn-action-clinical"
                        type="button"
                        @click="descargarPdfSeleccionado"
                        :disabled="
                            loading || selectedIds.length !== 1 || !selectedConsentimiento?.pdf_path
                        "
                    >
                        <i class="fa fa-download mr-1"></i>
                        Descargar PDF
                    </button>

                    <button
                        v-if="can('ecografias.consentimientos.zip.generar')"
                        class="btn btn-warning btn-action-clinical"
                        type="button"
                        @click="generarZip"
                        :disabled="loading || selectedIds.length < 2"
                    >
                        <i class="fa fa-file-archive-o mr-1"></i>
                        Descargar ZIP ({{ selectedIds.length }})
                    </button>

                    <button
                        v-if="can('ecografias.consentimientos.anular')"
                        class="btn btn-danger btn-action-clinical"
                        type="button"
                        @click="abrirModalAnular"
                        :disabled="loading || selectedIds.length !== 1"
                    >
                        <i class="fa fa-ban mr-1"></i>
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
                                <th width="40" class="text-center">
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
                                <th width="130" class="text-center">Estado</th>
                                <th width="120" class="text-center">PDF</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="loading">
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i
                                        class="fa fa-circle-o-notch fa-spin fa-2x mb-2 text-primary"
                                    ></i>
                                    <div>Cargando consentimientos...</div>
                                </td>
                            </tr>
                            <template v-else>
                                <tr
                                    v-for="c in registros"
                                    :key="c.id"
                                    :class="{ 'table-active': selectedMap[c.id] }"
                                >
                                    <td class="text-center">
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
                                            c.ecografia_producto?.ecografia?.paciente?.num_docu ||
                                            "—"
                                        }}
                                    </td>
                                    <td>{{ c.producto?.nom_produc || "—" }}</td>
                                    <td class="text-center">
                                        <span
                                            v-if="c.estado === 'COMPLETO'"
                                            class="badge badge-success"
                                        >
                                            <i class="fa fa-check mr-1"></i>COMPLETO
                                        </span>
                                        <span
                                            v-else-if="c.estado === 'ANULADO'"
                                            class="badge badge-danger"
                                        >
                                            <i class="fa fa-ban mr-1"></i>ANULADO
                                        </span>
                                        <span v-else class="badge badge-secondary">
                                            {{ c.estado }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span v-if="c.pdf_path" class="badge badge-info">
                                            <i class="fa fa-file-pdf-o mr-1"></i>Generado
                                        </span>
                                        <span v-else class="text-muted">—</span>
                                    </td>
                                </tr>
                                <tr v-if="registros.length === 0">
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="fa fa-info-circle mr-1"></i>Sin resultados
                                        encontrados.
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal Reactivo de Anulación -->
        <div
            v-if="modalAnularVisible"
            class="modal fade show d-block"
            tabindex="-1"
            role="dialog"
            aria-labelledby="modalAnularTitle"
            aria-modal="true"
            style="
                background: rgba(15, 23, 42, 0.45);
                backdrop-filter: blur(2px);
                -webkit-backdrop-filter: blur(2px);
            "
            @click.self="cerrarModalAnular"
        >
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content shadow-lg border-0">
                    <div class="modal-header bg-danger text-white py-2">
                        <h5 class="modal-title font-weight-bold" id="modalAnularTitle">
                            <i class="fa fa-ban mr-1"></i> Anular Consentimiento
                        </h5>
                        <button
                            type="button"
                            class="close text-white"
                            aria-label="Cerrar modal"
                            @click="cerrarModalAnular"
                        >
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div v-if="selectedConsentimiento" class="alert alert-warning py-2 mb-3">
                            <div class="font-weight-bold">
                                {{ pacienteNombre(selectedConsentimiento) }}
                            </div>
                            <small class="text-muted d-block">
                                Documento:
                                {{
                                    selectedConsentimiento.ecografia_producto?.ecografia?.paciente
                                        ?.num_docu || "—"
                                }}
                            </small>
                            <small class="text-muted d-block">
                                Examen:
                                {{ selectedConsentimiento.producto?.nom_produc || "—" }}
                            </small>
                        </div>
                        <div class="form-group mb-0">
                            <label for="anulacion_observacion" class="font-weight-bold">
                                Observación de anulación <span class="text-danger">*</span>
                            </label>
                            <textarea
                                id="anulacion_observacion"
                                ref="motivoAnulaRef"
                                class="form-control"
                                rows="3"
                                v-model="anulacionObservacion"
                                placeholder="Especifique detalladamente el motivo de la anulación..."
                            ></textarea>
                            <small class="form-text text-muted">
                                Esta acción es irreversible y quedará registrada en la auditoría
                                clínica.
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer py-2 bg-light">
                        <button
                            type="button"
                            class="btn btn-secondary btn-action-clinical"
                            @click="cerrarModalAnular"
                            :disabled="anulando"
                        >
                            <i class="fa fa-times mr-1"></i> Cancelar
                            <span class="badge badge-light border ml-1">Esc</span>
                        </button>
                        <button
                            type="button"
                            class="btn btn-danger btn-action-clinical"
                            @click="confirmarAnulacion"
                            :disabled="anulando || !anulacionObservacion.trim()"
                        >
                            <i
                                class="fa mr-1"
                                :class="anulando ? 'fa-spinner fa-spin' : 'fa-check'"
                            ></i>
                            {{ anulando ? "Anulando..." : "Confirmar Anulación" }}
                            <span class="badge badge-light border ml-1">Ctrl+S</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, getCurrentInstance } from "vue";

import usePermissions from "@/helpers/permissions.js";
import { toastMessage } from "@/helpers/alerts";
import { useKeyboardShortcuts } from "@/composables/useKeyboardShortcuts.js";
import axios from "axios";

const { proxy } = getCurrentInstance();
const emitter = proxy.emitter;

const props = defineProps({
    usuarioactual: { type: Object, default: () => ({}) },
    permisos: { type: Array, default: () => [] },
});
const { can } = usePermissions(props.permisos);

const filtros = ref({
    q: "",
    desde: "",
    hasta: "",
    sede_id: sessionStorage.getItem("ST-sede") || "",
});
const registros = ref([]);
const loading = ref(false);
const selectedMap = ref({});

const modalAnularVisible = ref(false);
const anulacionObservacion = ref("");
const anulando = ref(false);
const motivoAnulaRef = ref(null);

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
    } catch {
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
        toastMessage({
            heading: "Error",
            text: error.response?.data?.message || "No fue posible generar el PDF.",
            icon: "error",
        });
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
        toastMessage({
            heading: "Error",
            text: error.response?.data?.message || "No fue posible descargar el ZIP.",
            icon: "error",
        });
    } finally {
        loading.value = false;
    }
}

/**
 * Abre el modal clínico de anulación para el consentimiento seleccionado.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-19
 */
function abrirModalAnular() {
    if (selectedIds.value.length !== 1) return;
    anulacionObservacion.value = "";
    modalAnularVisible.value = true;
    nextTick(() => {
        if (motivoAnulaRef.value) {
            motivoAnulaRef.value.focus();
        }
    });
}

/**
 * Cierra el modal reactivo de anulación.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-19
 */
function cerrarModalAnular() {
    if (anulando.value) return;
    modalAnularVisible.value = false;
    anulacionObservacion.value = "";
}

/**
 * Confirma y procesa la anulación del consentimiento seleccionado.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-19
 */
async function confirmarAnulacion() {
    if (selectedIds.value.length !== 1 || !anulacionObservacion.value.trim()) return;
    const id = selectedIds.value[0];
    anulando.value = true;
    try {
        await axios.post(`/api/ecografias-consentimientos/${id}/anular`, {
            observacion: anulacionObservacion.value.trim(),
        });
        toastMessage({
            heading: "Éxito",
            text: "Consentimiento anulado correctamente.",
            icon: "success",
        });
        cerrarModalAnular();
        await buscar();
    } catch (error) {
        toastMessage({
            heading: "Error",
            text: error.response?.data?.message || "No fue posible anular el consentimiento.",
            icon: "error",
        });
    } finally {
        anulando.value = false;
    }
}

function onSedeSeleccionada(data) {
    if (data?.idSede) {
        filtros.value.sede_id = String(data.idSede);
    } else {
        filtros.value.sede_id = sessionStorage.getItem("ST-sede") || "";
    }
    buscar();
}

useKeyboardShortcuts({
    onSave: () => {
        if (modalAnularVisible.value) {
            confirmarAnulacion();
        }
    },
    onEscape: () => {
        if (modalAnularVisible.value) {
            cerrarModalAnular();
        } else if (selectedIds.value.length > 0) {
            selectedMap.value = {};
        }
    },
    onSearch: () => {
        if (!modalAnularVisible.value) {
            buscar();
        }
    },
});

/**
 * Hook de ciclo de vida que ejecuta la búsqueda inicial de consentimientos y escucha cambios de sede.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-02-13
 */
onMounted(() => {
    buscar();
    emitter.on("sedeSeleccionada", onSedeSeleccionada);
});

onBeforeUnmount(() => {
    emitter.off("sedeSeleccionada", onSedeSeleccionada);
});
</script>
<style scoped>
.breadcrumb-btn {
    vertical-align: baseline;
}
.btn-action-clinical {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease-in-out;
}
.btn-action-clinical:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow:
        0 4px 6px -1px rgba(0, 0, 0, 0.1),
        0 2px 4px -1px rgba(0, 0, 0, 0.06);
}
</style>

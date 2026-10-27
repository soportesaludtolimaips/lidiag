<template>
    <div>
        <!-- ============================================================== -->
        <!-- Bread crumb and right sidebar toggle -->
        <!-- ============================================================== -->
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">Ecografías</h3>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0)">Inicio</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0)">Ecografías</a>
                    </li>
                    <li class="breadcrumb-item active">Consentimientos</li>
                </ol>
            </div>
            <div class="col-md-7 align-self-center text-right d-none d-md-block">
                <button
                    type="button"
                    class="btn btn-info waves-effect waves-light"
                    :disabled="loading"
                    title="Actualizar listado de consentimientos"
                    @click="buscar"
                >
                    <i class="fa" :class="loading ? 'fa-spinner fa-spin' : 'fa-refresh'"></i>
                    Refrescar
                </button>
            </div>
        </div>
        <!-- ============================================================== -->
        <!-- End Bread crumb and right sidebar toggle -->
        <!-- ============================================================== -->

        <!-- ============================================================== -->
        <!-- Start Page Content -->
        <!-- ============================================================== -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title text-themecolor">
                            <i class="fa fa-filter"></i> Filtros para búsqueda de consentimientos
                        </h4>
                        <form action="#" @submit.prevent="buscar">
                            <div class="form-body">
                                <div class="row p-t-2">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="control-label" for="filtros_q">
                                                Paciente / Documento
                                            </label>
                                            <input
                                                id="filtros_q"
                                                type="text"
                                                class="form-control"
                                                v-model="filtros.q"
                                                placeholder="Nombre o documento de paciente"
                                                @keyup.enter="buscar"
                                            />
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="control-label" for="filtros_desde">
                                                Fecha inicio
                                            </label>
                                            <input
                                                id="filtros_desde"
                                                type="date"
                                                class="form-control"
                                                v-model="filtros.desde"
                                                @change="buscar"
                                            />
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="control-label" for="filtros_hasta">
                                                Fecha fin
                                            </label>
                                            <input
                                                id="filtros_hasta"
                                                type="date"
                                                class="form-control"
                                                v-model="filtros.hasta"
                                                @change="buscar"
                                            />
                                        </div>
                                    </div>
                                </div>
                                <div class="form-actions">
                                    <button
                                        type="button"
                                        class="btn btn-success waves-effect waves-light m-r-5"
                                        @click="buscar"
                                        :disabled="loading"
                                    >
                                        <i
                                            class="fa"
                                            :class="loading ? 'fa-spinner fa-spin' : 'fa-search'"
                                        ></i>
                                        {{ loading ? "Buscando..." : "Buscar" }}
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-inverse waves-effect waves-light"
                                        @click="limpiarFiltros"
                                    >
                                        <i class="fa fa-refresh"></i> Limpiar
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div
                            class="d-flex justify-content-between align-items-center m-b-20 flex-wrap"
                        >
                            <div>
                                <h4 class="card-title">Listado de consentimientos</h4>
                                <h6 class="card-subtitle text-info">
                                    Gestión y descarga de consentimientos informados de ecografías.
                                </h6>
                            </div>
                            <div class="ml-auto">
                                <button
                                    v-if="
                                        can('ecografias.consentimientos.zip.generar') ||
                                        can('ecografias.consentimientos.index')
                                    "
                                    class="btn btn-warning waves-effect waves-light"
                                    type="button"
                                    @click="generarZip"
                                    :disabled="loading || selectedIds.length < 2"
                                    title="Descargar archivo ZIP con los consentimientos seleccionados (mínimo 2)"
                                >
                                    <i class="fa fa-file-archive-o m-r-5"></i> Descargar ZIP
                                    <span
                                        v-if="selectedIds.length >= 2"
                                        class="badge badge-light text-dark m-l-5 font-weight-bold"
                                    >
                                        {{ selectedIds.length }}
                                    </span>
                                </button>
                            </div>
                        </div>

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
                                        <th width="140" class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="loading">
                                        <td colspan="7" class="text-center py-4 text-muted">
                                            <i
                                                class="fa fa-spinner fa-spin fa-2x mb-2 text-primary"
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
                                                    c.ecografia_producto?.ecografia?.paciente
                                                        ?.num_docu || "—"
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
                                            <td class="text-nowrap text-center">
                                                <button
                                                    v-if="
                                                        can(
                                                            'ecografias.consentimientos.pdf.generar'
                                                        ) && c.estado === 'COMPLETO'
                                                    "
                                                    type="button"
                                                    class="btn waves-effect waves-light btn-rounded btn-outline-info btn-sm m-r-5"
                                                    @click="generarPdf(c)"
                                                    :disabled="loadingPdfId === c.id"
                                                    title="Generar PDF"
                                                    aria-label="Generar PDF"
                                                >
                                                    <i
                                                        class="fa"
                                                        :class="
                                                            loadingPdfId === c.id
                                                                ? 'fa-spinner fa-spin'
                                                                : 'fa-file-pdf-o'
                                                        "
                                                    ></i>
                                                </button>

                                                <button
                                                    v-if="
                                                        can(
                                                            'ecografias.consentimientos.pdf.descargar'
                                                        ) && c.pdf_path
                                                    "
                                                    type="button"
                                                    class="btn waves-effect waves-light btn-rounded btn-outline-primary btn-sm m-r-5"
                                                    @click="descargarPdf(c)"
                                                    title="Descargar PDF"
                                                    aria-label="Descargar PDF"
                                                >
                                                    <i class="fa fa-download"></i>
                                                </button>

                                                <button
                                                    v-if="
                                                        can('ecografias.consentimientos.anular') &&
                                                        c.estado === 'COMPLETO'
                                                    "
                                                    type="button"
                                                    class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm"
                                                    @click="abrirModalAnular(c)"
                                                    title="Anular consentimiento"
                                                    aria-label="Anular consentimiento"
                                                >
                                                    <i class="fa fa-ban"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <tr v-if="registros.length === 0">
                                            <td colspan="7" class="text-center text-muted py-4">
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
            </div>
        </div>

        <!-- Modal de Anulación -->
        <div
            class="modal fade"
            :class="{ show: modalAnularVisible }"
            :style="{ display: modalAnularVisible ? 'block' : 'none' }"
            tabindex="-1"
            role="dialog"
            aria-labelledby="modalAnularTitle"
            aria-hidden="true"
            @click.self="cerrarModalAnular"
        >
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title" id="modalAnularTitle">Anular Consentimiento</h4>
                        <button
                            type="button"
                            class="close"
                            aria-label="Cerrar modal"
                            @click="cerrarModalAnular"
                        >
                            ×
                        </button>
                    </div>
                    <div class="modal-body">
                        <div v-if="consentimientoParaAnular" class="alert alert-warning">
                            <div>
                                <strong>{{ pacienteNombre(consentimientoParaAnular) }}</strong>
                            </div>
                            <small class="text-muted d-block">
                                Documento:
                                {{
                                    consentimientoParaAnular.ecografia_producto?.ecografia?.paciente
                                        ?.num_docu || "—"
                                }}
                            </small>
                            <small class="text-muted d-block">
                                Examen:
                                {{ consentimientoParaAnular.producto?.nom_produc || "—" }}
                            </small>
                        </div>
                        <div class="form-group">
                            <label class="control-label" for="anulacion_observacion">
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
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-default waves-effect"
                            @click="cerrarModalAnular"
                            :disabled="anulando"
                        >
                            Cancelar
                        </button>
                        <button
                            type="button"
                            class="btn btn-danger waves-effect waves-light"
                            @click="confirmarAnulacion"
                            :disabled="anulando || !anulacionObservacion.trim()"
                        >
                            <i v-if="anulando" class="fa fa-spinner fa-spin"></i>
                            <i v-else class="fa fa-ban"></i>
                            {{ anulando ? "Anulando..." : "Confirmar anulación" }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="modalAnularVisible" class="modal-backdrop fade show"></div>
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
const consentimientoParaAnular = ref(null);
const loadingPdfId = ref(null);

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
 * Limpia los filtros de búsqueda y restablece el listado de consentimientos.
 *
 * Flujo del proceso:
 * 1. Restablece los campos de búsqueda de paciente y rango de fechas a vacío.
 * 2. Invoca el método buscar para actualizar los registros en pantalla.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-27
 */
function limpiarFiltros() {
    filtros.value.q = "";
    filtros.value.desde = "";
    filtros.value.hasta = "";
    buscar();
}

/**
 * Genera el PDF del consentimiento individual y actualiza la lista.
 *
 * Flujo del proceso:
 * 1. Asigna el id del registro al estado reactivo de carga para mostrar el spinner.
 * 2. Emite la solicitud POST al endpoint de generación de PDF de consentimientos.
 * 3. Muestra alerta de éxito y refresca la lista de consentimientos.
 *
 * @param {Object} c Registro de consentimiento a generar.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-27
 */
async function generarPdf(c) {
    if (!c?.id) return;
    loadingPdfId.value = c.id;
    try {
        await axios.post(`/api/ecografias-consentimientos/${c.id}/pdf`);
        toastMessage({
            heading: "Éxito",
            text: "PDF generado correctamente.",
            icon: "success",
        });
        await buscar();
    } catch (error) {
        toastMessage({
            heading: "Error",
            text: error.response?.data?.message || "No fue posible generar el PDF.",
            icon: "error",
        });
    } finally {
        loadingPdfId.value = null;
    }
}

/**
 * Abre en una nueva pestaña la descarga del PDF del consentimiento individual.
 *
 * @param {Object} c Registro de consentimiento a descargar.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-27
 */
function descargarPdf(c) {
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
 * Abre el modal clínico de anulación para el consentimiento indicado.
 *
 * @param {Object} c Registro de consentimiento a anular.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-27
 */
function abrirModalAnular(c) {
    consentimientoParaAnular.value = c;
    anulacionObservacion.value = "";
    modalAnularVisible.value = true;
    nextTick(() => {
        if (motivoAnulaRef.value) {
            motivoAnulaRef.value.focus();
        }
    });
}

/**
 * Cierra el modal reactivo de anulación y reinicia el consentimiento seleccionado.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-27
 */
function cerrarModalAnular() {
    if (anulando.value) return;
    modalAnularVisible.value = false;
    anulacionObservacion.value = "";
    consentimientoParaAnular.value = null;
}

/**
 * Confirma y procesa la anulación del consentimiento seleccionado.
 *
 * Flujo del proceso:
 * 1. Valida existencia de consentimiento seleccionado y observación obligatoria.
 * 2. Emite la solicitud POST de anulación clínica al backend.
 * 3. Muestra toast de confirmación, cierra el modal y refresca el listado.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-27
 */
async function confirmarAnulacion() {
    if (!consentimientoParaAnular.value?.id || !anulacionObservacion.value.trim()) return;
    const id = consentimientoParaAnular.value.id;
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

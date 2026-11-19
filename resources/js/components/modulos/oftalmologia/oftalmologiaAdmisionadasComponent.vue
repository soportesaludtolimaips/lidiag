<template>
    <div>
        <!-- ============================================================== -->
        <!-- Bread crumb and right sidebar toggle -->
        <!-- ============================================================== -->
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">Oftalmología</h3>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <button type="button" class="btn btn-link p-0 breadcrumb-btn">
                            Inicio
                        </button>
                    </li>
                    <li class="breadcrumb-item">
                        <button type="button" class="btn btn-link p-0 breadcrumb-btn">
                            Oftalmología
                        </button>
                    </li>
                    <li class="breadcrumb-item active">Pacientes admisionados</li>
                </ol>
            </div>
            <div class="col-md-7 align-self-center text-right d-none d-md-block">
                <button
                    type="button"
                    class="btn btn-info waves-effect waves-light"
                    @click="listarAtenciones()"
                    :disabled="isBuscandoAdmisionadas"
                    title="Actualizar listado de pacientes admisionados"
                >
                    <i
                        class="fa mr-1"
                        :class="isBuscandoAdmisionadas ? 'fa-spinner fa-spin' : 'fa-refresh'"
                    ></i>
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
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h4 class="card-title text-themecolor mb-0">
                                <i class="fa fa-filter"></i> Filtros para búsqueda de admisiones de
                                oftalmología
                                <span
                                    v-if="filtrosActivosCount > 0"
                                    class="badge badge-pill badge-info ml-2"
                                >
                                    {{ filtrosActivosCount }}
                                    {{
                                        filtrosActivosCount === 1
                                            ? "filtro activo"
                                            : "filtros activos"
                                    }}
                                </span>
                            </h4>
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-secondary"
                                @click="toggleFiltros()"
                                :title="filtrosVisible ? 'Ocultar filtros' : 'Mostrar filtros'"
                            >
                                <i
                                    :class="
                                        filtrosVisible ? 'fa fa-chevron-up' : 'fa fa-chevron-down'
                                    "
                                ></i>
                                {{ filtrosVisible ? "Ocultar filtros" : "Mostrar filtros" }}
                            </button>
                        </div>
                        <transition name="fade-collapse">
                            <form
                                v-show="filtrosVisible"
                                action="#"
                                autocomplete="off"
                                @submit.prevent="listarAtenciones()"
                            >
                                <div class="form-body">
                                    <div class="row p-t-2">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label class="control-label" for="bus_nom_num_docu">
                                                    # de atención, documento o paciente
                                                </label>
                                                <input
                                                    type="text"
                                                    id="bus_nom_num_docu"
                                                    name="bus_nom_num_docu"
                                                    v-model="busqueda.bus_nom_num_docu"
                                                    class="form-control"
                                                    placeholder="Atención, documento o nombre"
                                                    autocomplete="off"
                                                    @keyup.enter="listarAtenciones()"
                                                />
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label class="control-label" for="bus_estado"
                                                    >Estado</label
                                                >
                                                <select
                                                    id="bus_estado"
                                                    v-model="busqueda.estado"
                                                    class="form-control"
                                                    @change="listarAtenciones()"
                                                >
                                                    <option value="">Todos los estados</option>
                                                    <option value="PENDIENTE">PENDIENTE</option>
                                                    <option value="EN_CONSULTA">EN CONSULTA</option>
                                                    <option value="ATENDIDO">ATENDIDO</option>
                                                    <option value="ANULADO">ANULADO</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label class="control-label" for="bus_fech_ini"
                                                    >Fecha Inicio</label
                                                >
                                                <input
                                                    type="date"
                                                    id="bus_fech_ini"
                                                    name="bus_fech_ini"
                                                    v-model="busqueda.bus_fech_ini"
                                                    class="form-control"
                                                    autocomplete="off"
                                                    @keyup.enter="listarAtenciones()"
                                                />
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label class="control-label" for="bus_fecha_fin"
                                                    >Fecha Fin</label
                                                >
                                                <input
                                                    type="date"
                                                    id="bus_fecha_fin"
                                                    name="bus_fecha_fin"
                                                    v-model="busqueda.bus_fecha_fin"
                                                    class="form-control"
                                                    autocomplete="off"
                                                    @keyup.enter="listarAtenciones()"
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-actions mt-3">
                                        <button
                                            type="button"
                                            class="btn btn-success waves-effect waves-light m-r-5"
                                            @click="listarAtenciones()"
                                            :disabled="isBuscandoAdmisionadas"
                                        >
                                            <i
                                                class="fa mr-1"
                                                :class="
                                                    isBuscandoAdmisionadas
                                                        ? 'fa-spinner fa-spin'
                                                        : 'fa-search'
                                                "
                                            ></i>
                                            {{ isBuscandoAdmisionadas ? "Buscando..." : "Buscar" }}
                                        </button>

                                        <button
                                            type="button"
                                            class="btn btn-inverse waves-effect waves-light"
                                            @click="btnLimpiar()"
                                            :disabled="isBuscandoAdmisionadas"
                                        >
                                            <i class="fa fa-refresh mr-1"></i> Limpiar
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </transition>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12 table-responsive">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            Listado de pacientes admisionados de Oftalmología.
                        </h4>
                        <h6 class="card-subtitle text-info">
                            Atenciones en sala de espera o atendidas por el especialista.
                        </h6>
                        <table-skeleton-loader
                            :visible="isBuscandoAdmisionadas"
                            :rows="6"
                            :columns="9"
                        />
                        <empty-state-component
                            v-if="!isBuscandoAdmisionadas && registros.length === 0"
                            icon="sl-icon-eye"
                            title="No hay admisiones de oftalmología para mostrar"
                            description="No se encontraron pacientes admisionados que coincidan con los filtros ingresados."
                            action-text="Limpiar filtros"
                            action-icon="fa fa-refresh"
                            @action="btnLimpiar()"
                        />
                        <div
                            class="table-responsive"
                            v-show="!isBuscandoAdmisionadas && registros.length > 0"
                        >
                            <table
                                id="tablaOftalmologiaAdmisionadas"
                                ref="dt"
                                class="table table-hover table-bordered table-custom-compact"
                                cellspacing="0"
                                width="100%"
                            >
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width: 10%">Fecha / Hora</th>
                                        <th style="width: 16%">Paciente</th>
                                        <th style="width: 12%"># Atención</th>
                                        <th style="width: 10%" class="text-center">Recaudo</th>
                                        <th style="width: 16%">Aseguramiento / EPS</th>
                                        <th style="width: 14%">Especialista</th>
                                        <th style="width: 8%" class="text-center">Estado</th>
                                        <th style="width: 6%" class="text-center">SAHI</th>
                                        <th style="width: 8%" class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item, index) in registros" :key="item.id || index">
                                        <!-- 1. Fecha / Hora -->
                                        <td class="align-middle">
                                            <div
                                                class="font-weight-bold text-dark"
                                                style="font-size: 13px"
                                            >
                                                <i class="fa fa-calendar-o text-muted mr-1"></i>
                                                {{ formatearFecha(item.created_at) }}
                                            </div>
                                            <small
                                                class="text-muted d-block"
                                                style="font-size: 11px"
                                            >
                                                <i class="fa fa-clock-o mr-1"></i
                                                >{{ formatearHora(item.created_at) }}
                                            </small>
                                        </td>

                                        <!-- 2. Paciente -->
                                        <td class="align-middle">
                                            <div class="paciente-nombre font-weight-bold text-dark">
                                                {{ item.paciente?.nombres || "-" }}
                                                {{ item.paciente?.pri_ape || "" }}
                                                {{ item.paciente?.seg_ape || "" }}
                                            </div>
                                            <div
                                                class="text-primary mt-1"
                                                style="font-size: 12px; font-weight: 600"
                                            >
                                                <i class="fa fa-id-card-o mr-1"></i>
                                                {{ item.paciente?.num_docu || "-" }}
                                            </div>
                                        </td>

                                        <!-- 3. # Atención -->
                                        <td class="align-middle font-weight-bold">
                                            <span class="badge badge-light border text-dark">
                                                {{ item.atencion_id }}
                                            </span>
                                            <small
                                                v-if="item.ubicacion"
                                                class="text-muted d-block mt-1"
                                            >
                                                {{ item.ubicacion }}
                                            </small>
                                        </td>

                                        <!-- 4. Recaudo -->
                                        <td class="text-center align-middle">
                                            <span
                                                class="badge"
                                                :class="
                                                    item.tipo_recaudo?.codigo === 'PARTICULAR'
                                                        ? 'badge-primary'
                                                        : item.tipo_recaudo?.codigo === 'CORTESIA'
                                                          ? 'badge-warning text-white'
                                                          : 'badge-info'
                                                "
                                            >
                                                {{ item.tipo_recaudo?.nombre || "Atención" }}
                                            </span>
                                        </td>

                                        <!-- 5. EPS / Contrato -->
                                        <td class="align-middle">
                                            <div
                                                class="font-weight-bold text-dark text-truncate"
                                                style="max-width: 200px"
                                                :title="
                                                    item.admin_salud?.nom_admin ||
                                                    item.contrato ||
                                                    'Particular'
                                                "
                                            >
                                                {{
                                                    item.admin_salud?.nom_admin ||
                                                    "Particular / Sin EPS"
                                                }}
                                            </div>
                                            <div
                                                v-if="item.contrato"
                                                class="small text-muted mt-1 text-truncate"
                                                style="max-width: 200px"
                                            >
                                                <strong>C:</strong> {{ item.contrato }}
                                            </div>
                                        </td>

                                        <!-- 6. Especialista -->
                                        <td class="align-middle">
                                            <div
                                                class="font-weight-bold text-dark text-truncate"
                                                style="max-width: 160px"
                                            >
                                                {{ item.medico?.name || "Sin asignar" }}
                                            </div>
                                        </td>

                                        <!-- 7. Estado -->
                                        <td class="text-center align-middle">
                                            <span
                                                class="badge"
                                                :class="
                                                    item.estado === 'ATENDIDO'
                                                        ? 'badge-success'
                                                        : item.estado === 'EN_CONSULTA'
                                                          ? 'badge-warning text-white'
                                                          : item.estado === 'ANULADO'
                                                            ? 'badge-danger'
                                                            : 'badge-info'
                                                "
                                            >
                                                {{ item.estado }}
                                            </span>
                                        </td>

                                        <!-- 8. SAHI -->
                                        <td class="text-center align-middle">
                                            <span
                                                v-if="item.estado_sahi === 'ENVIADO'"
                                                class="badge badge-success"
                                                title="Sincronizado con SAHI"
                                            >
                                                <i class="fa fa-check"></i>
                                            </span>
                                            <span
                                                v-else-if="item.estado_sahi === 'ERROR'"
                                                class="badge badge-danger"
                                                title="Error en transmisión a SAHI"
                                            >
                                                <i class="fa fa-times"></i>
                                            </span>
                                            <span
                                                v-else
                                                class="badge badge-secondary"
                                                title="No aplica o pendiente"
                                            >
                                                {{ item.estado_sahi || "LOCAL" }}
                                            </span>
                                        </td>

                                        <!-- 9. Acciones -->
                                        <td class="text-center align-middle text-nowrap">
                                            <router-link
                                                v-if="item.estado !== 'ANULADO'"
                                                :to="`/oftalmologia.consulta/${item.id}`"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-primary btn-sm mr-1"
                                                title="Realizar / Ver Consulta Clínica"
                                            >
                                                <i class="fa fa-stethoscope"></i>
                                            </router-link>

                                            <button
                                                v-if="
                                                    item.estado !== 'ANULADO' &&
                                                    can('oftalmologia.anular')
                                                "
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm"
                                                @click="prepararAnulacion(item)"
                                                title="Anular atención"
                                            >
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Anular Atención -->
        <div
            class="modal fade"
            id="modalAnularOftalmologia"
            tabindex="-1"
            role="dialog"
            aria-labelledby="modalAnularLabel"
            aria-hidden="true"
        >
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title font-weight-bold" id="modalAnularLabel">
                            <i class="fa fa-exclamation-triangle text-danger mr-1"></i> Anular
                            Atención de Oftalmología
                        </h4>
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
                            ×
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="control-label font-weight-bold" for="motivoAnulacion"
                                >Motivo de Anulación <span class="text-danger">*</span></label
                            >
                            <textarea
                                id="motivoAnulacion"
                                v-model="motivoAnulacion"
                                class="form-control"
                                rows="3"
                                placeholder="Indique claramente la razón por la cual se anula la atención..."
                            ></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-inverse waves-effect"
                            data-dismiss="modal"
                        >
                            Cancelar
                        </button>
                        <button
                            type="button"
                            class="btn btn-danger waves-effect waves-light"
                            @click="ejecutarAnulacion()"
                            :disabled="isAnulando"
                        >
                            <i
                                class="fa mr-1"
                                :class="isAnulando ? 'fa-spinner fa-spin' : 'fa-trash'"
                            ></i>
                            {{ isAnulando ? "Anulando..." : "Confirmar Anulación" }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, getCurrentInstance } from "vue";
const { appContext } = getCurrentInstance();
const emitter = appContext?.config?.globalProperties?.emitter;

import usePermissions from "@/helpers/permissions.js";
import { toastMessage } from "@/helpers/alerts.js";
import { useSede } from "@/composables/useSede.js";
import axios from "axios";

const props = defineProps({
    usuarioactual: { type: Object, default: () => ({}) },
    permisos: { type: Array, default: () => [] },
});

const { can } = usePermissions(props.permisos);
const { sedeActual, establecerSede: establecerSedeComposable } = useSede();

const dt = ref(null);
const registros = ref([]);
const isBuscandoAdmisionadas = ref(false);
const isAnulando = ref(false);
const filtrosVisible = ref(true);

const itemParaAnular = ref(null);
const motivoAnulacion = ref("");

const busqueda = ref({
    sede_id: sedeActual,
    bus_nom_num_docu: "",
    estado: "",
    bus_fech_ini: "",
    bus_fecha_fin: "",
});

const filtrosActivosCount = computed(() => {
    let count = 0;
    if (busqueda.value.bus_nom_num_docu) count++;
    if (busqueda.value.estado) count++;
    if (busqueda.value.bus_fech_ini) count++;
    if (busqueda.value.bus_fecha_fin) count++;
    return count;
});

function toggleFiltros() {
    filtrosVisible.value = !filtrosVisible.value;
}

function safeDestroyDataTable(selector = "#tablaOftalmologiaAdmisionadas") {
    if (typeof window === "undefined" || !window.$ || !window.$.fn || !window.$.fn.DataTable)
        return;
    const $t = window.$(selector);
    if (!$t.length) return;
    if (window.$.fn.DataTable.isDataTable($t)) {
        try {
            $t.DataTable().destroy();
        } catch {
            // Noop
        }
    }
}

function safeInitDataTable(selector = "#tablaOftalmologiaAdmisionadas") {
    if (typeof window === "undefined" || !window.$ || !window.$.fn || !window.$.fn.DataTable)
        return;
    nextTick(() => {
        const $t = window.$(selector);
        if (!$t.length) return;
        try {
            $t.DataTable({
                pageLength: 15,
                language: {
                    search: "Buscar en tabla:",
                    lengthMenu: "Mostrar _MENU_ registros",
                    info: "Mostrando _START_ a _END_ de _TOTAL_ atenciones",
                    infoEmpty: "Sin registros disponibles",
                    zeroRecords: "No se encontraron resultados",
                    paginate: {
                        first: "Primero",
                        last: "Último",
                        next: "Siguiente",
                        previous: "Anterior",
                    },
                },
                order: [[0, "desc"]],
            });
        } catch {
            // Noop
        }
    });
}

function formatearFecha(fecha) {
    if (!fecha) return "—";
    try {
        const d = new Date(fecha);
        if (isNaN(d.getTime())) return fecha;
        const dia = String(d.getDate()).padStart(2, "0");
        const mes = String(d.getMonth() + 1).padStart(2, "0");
        const anio = d.getFullYear();
        return `${dia}/${mes}/${anio}`;
    } catch {
        return fecha;
    }
}

function formatearHora(fecha) {
    if (!fecha) return "";
    try {
        const d = new Date(fecha);
        if (isNaN(d.getTime())) return "";
        let horas = d.getHours();
        const minutos = String(d.getMinutes()).padStart(2, "0");
        const ampm = horas >= 12 ? "PM" : "AM";
        horas = horas % 12;
        horas = horas ? horas : 12;
        return `${String(horas).padStart(2, "0")}:${minutos} ${ampm}`;
    } catch {
        return "";
    }
}

function btnLimpiar() {
    busqueda.value.bus_nom_num_docu = "";
    busqueda.value.estado = "";
    busqueda.value.bus_fech_ini = "";
    busqueda.value.bus_fecha_fin = "";
    listarAtenciones();
}

async function listarAtenciones() {
    try {
        isBuscandoAdmisionadas.value = true;
        const sede = sessionStorage.getItem("ST-sede") || localStorage.getItem("ST-sede");
        busqueda.value.sede_id = sede;

        const res = await axios.post("/api/oftalmologia/atenciones", {
            sede_id: busqueda.value.sede_id,
            busqueda: busqueda.value.bus_nom_num_docu,
            estado: busqueda.value.estado,
            fecha_ini: busqueda.value.bus_fech_ini,
            fecha_fin: busqueda.value.bus_fecha_fin,
            per_page: 50,
        });

        if (res.status === 200) {
            safeDestroyDataTable(dt.value || "#tablaOftalmologiaAdmisionadas");
            registros.value = res.data?.data?.data || res.data?.data || [];
            safeInitDataTable(dt.value || "#tablaOftalmologiaAdmisionadas");
        }
    } catch (error) {
        toastMessage({
            heading: "Error",
            text: error?.response?.data?.message || "No fue posible consultar las atenciones.",
            icon: "error",
        });
    } finally {
        isBuscandoAdmisionadas.value = false;
    }
}

function prepararAnulacion(item) {
    itemParaAnular.value = item;
    motivoAnulacion.value = "";
    if (window.$) {
        window.$("#modalAnularOftalmologia").modal("show");
    }
}

async function ejecutarAnulacion() {
    if (!motivoAnulacion.value || motivoAnulacion.value.trim().length < 4) {
        toastMessage({
            heading: "¡Atención!",
            text: "Debe ingresar un motivo de anulación válido.",
            icon: "warning",
        });
        return;
    }

    try {
        isAnulando.value = true;
        const res = await axios.post(
            `/api/oftalmologia/atenciones/${itemParaAnular.value.id}/anular`,
            {
                motivo: motivoAnulacion.value,
            }
        );

        if (res.status === 200) {
            toastMessage({
                heading: "¡Éxito!",
                text: "Atención anulada correctamente.",
                icon: "success",
            });
            if (window.$) {
                window.$("#modalAnularOftalmologia").modal("hide");
            }
            listarAtenciones();
        }
    } catch (error) {
        toastMessage({
            heading: "Error",
            text: error?.response?.data?.message || "No fue posible anular la atención.",
            icon: "error",
        });
    } finally {
        isAnulando.value = false;
    }
}

function onSedeSeleccionada(nuevaSede) {
    busqueda.value.sede_id = nuevaSede?.id || nuevaSede;
    listarAtenciones();
}

onMounted(() => {
    if (emitter) {
        emitter.on("sedeSeleccionada", onSedeSeleccionada);
    }
    const sede = sessionStorage.getItem("ST-sede") || localStorage.getItem("ST-sede");
    if (sede) {
        busqueda.value.sede_id = sede;
        establecerSedeComposable();
    }
    listarAtenciones();
});

onBeforeUnmount(() => {
    if (emitter) {
        emitter.off("sedeSeleccionada", onSedeSeleccionada);
    }
});
</script>

<style scoped>
.breadcrumb-btn {
    vertical-align: baseline;
}

.table-custom-compact {
    font-size: 13px;
    margin-bottom: 0;
}

.table-custom-compact thead th {
    background-color: #f8fafc;
    color: #495057;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 10px 8px !important;
    vertical-align: middle !important;
    border-bottom: 2px solid #e9ecef;
}

.table-custom-compact tbody td {
    padding: 8px 8px !important;
    vertical-align: middle !important;
}

.paciente-nombre {
    font-size: 13px;
    line-height: 1.25;
    color: #2b3035;
}

.fade-collapse-enter-active,
.fade-collapse-leave-active {
    transition: opacity 0.2s ease;
}

.fade-collapse-enter-from,
.fade-collapse-leave-to {
    opacity: 0;
}
</style>

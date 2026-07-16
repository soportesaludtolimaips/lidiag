<template>
    <div>
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">Revisión de Solicitudes</h3>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0)">Inicio</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0)">Citas</a>
                    </li>
                    <li class="breadcrumb-item active">Solicitudes Pendientes</li>
                </ol>
            </div>
            <div class="col-md-7 align-self-center text-right d-none d-md-block">
                <button
                    type="button"
                    class="btn btn-info"
                    @click="listarRegistros()"
                    :disabled="loadingBuscar"
                >
                    <i class="fa" :class="loadingBuscar ? 'fa-spinner fa-spin' : 'fa-refresh'"></i>
                    Refrescar
                </button>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title text-themecolor">
                            <i class="fa fa-filter"></i> Filtros
                        </h4>
                        <form action="#">
                            <div class="form-body">
                                <div class="row p-t-2">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="control-label">Servicio</label>
                                            <select
                                                v-model="filtro.servicio_cita_id"
                                                class="form-control"
                                                @change="listarRegistros()"
                                            >
                                                <option value="">Todos</option>
                                                <option
                                                    v-for="s in servicios"
                                                    :key="s.id"
                                                    :value="s.id"
                                                >
                                                    {{ s.nombre }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="control-label">Sede</label>
                                            <select
                                                v-model="filtro.sede_id"
                                                class="form-control"
                                                @change="listarRegistros()"
                                            >
                                                <option value="">Todas</option>
                                                <option
                                                    v-for="s in sedes"
                                                    :key="s.id"
                                                    :value="s.id"
                                                >
                                                    {{ s.nom_sede }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="control-label">Búsqueda</label>
                                            <input
                                                type="text"
                                                v-model="filtro.busqueda"
                                                class="form-control"
                                                placeholder="Documento o nombre"
                                                @keyup.enter="listarRegistros()"
                                            />
                                        </div>
                                    </div>
                                    <div class="col-md-3 d-flex align-items-end">
                                        <button
                                            type="button"
                                            class="btn btn-success m-r-5"
                                            @click="listarRegistros()"
                                            :disabled="loadingBuscar"
                                        >
                                            <i
                                                v-if="loadingBuscar"
                                                class="fa fa-spinner fa-spin"
                                            ></i>
                                            <i v-else class="fa fa-search"></i>
                                            Buscar
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-inverse"
                                            @click="limpiarFiltros()"
                                        >
                                            <i class="fa fa-refresh"></i> Limpiar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12 table-responsive">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h4 class="card-title">Solicitudes Pendientes</h4>
                                <h6 class="card-subtitle text-info">
                                    Revise las solicitudes de citas y apruebe o rechace cada una
                                </h6>
                            </div>
                            <div class="text-right">
                                <span class="badge badge-primary" v-if="contadorRegistros > 0"
                                    >{{ contadorRegistros }} registro(s)</span
                                >
                                <span class="badge badge-secondary" v-else>Sin resultados</span>
                            </div>
                        </div>

                        <loading-spinner :visible="loadingBuscar" />

                        <div class="table-responsive" :class="{ 'd-none': loadingBuscar }">
                            <table
                                id="example23"
                                ref="dt"
                                class="table table-bordered table-striped"
                                cellspacing="0"
                                width="100%"
                            >
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Fecha</th>
                                        <th>Paciente</th>
                                        <th>Documento</th>
                                        <th>Servicio</th>
                                        <th>Sede</th>
                                        <th>Cédula Frente</th>
                                        <th>Cédula Dorso</th>
                                        <th>Orden</th>
                                        <th>Autorización</th>
                                        <th class="text-nowrap">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="!registros.length">
                                        <td colspan="11" class="text-center text-muted py-4">
                                            <i class="fa fa-inbox fa-2x d-block mb-2"></i>
                                            No hay solicitudes pendientes.
                                        </td>
                                    </tr>
                                    <tr
                                        v-for="item in registros"
                                        :key="item.id"
                                        :id="`cita-${item.id}`"
                                    >
                                        <td>{{ item.id }}</td>
                                        <td>{{ formatFecha(item.created_at) }}</td>
                                        <td>{{ item.paciente?.nombres || "—" }}</td>
                                        <td>{{ item.paciente?.num_docu || "—" }}</td>
                                        <td>{{ item.servicio?.nombre || "—" }}</td>
                                        <td>{{ item.sede?.nom_sede || "—" }}</td>
                                        <td>
                                            <a
                                                v-if="item.ruta_cedula_frente_media"
                                                href="javascript:void(0)"
                                                @click="verArchivo(item.ruta_cedula_frente_media)"
                                                title="Ver cédula frente"
                                            >
                                                <img
                                                    v-if="esImagen(item.ruta_cedula_frente_media)"
                                                    :src="
                                                        getArchivoUrl(item.ruta_cedula_frente_media)
                                                    "
                                                    class="img-thumbnail miniatura-archivo"
                                                    alt="Cédula frente"
                                                />
                                                <i
                                                    v-else
                                                    class="fa fa-file-pdf-o text-danger icono-archivo"
                                                ></i>
                                            </a>
                                            <span v-else>—</span>
                                        </td>
                                        <td>
                                            <a
                                                v-if="item.ruta_cedula_dorso_media"
                                                href="javascript:void(0)"
                                                @click="verArchivo(item.ruta_cedula_dorso_media)"
                                                title="Ver cédula dorso"
                                            >
                                                <img
                                                    v-if="esImagen(item.ruta_cedula_dorso_media)"
                                                    :src="
                                                        getArchivoUrl(item.ruta_cedula_dorso_media)
                                                    "
                                                    class="img-thumbnail miniatura-archivo"
                                                    alt="Cédula dorso"
                                                />
                                                <i
                                                    v-else
                                                    class="fa fa-file-pdf-o text-danger icono-archivo"
                                                ></i>
                                            </a>
                                            <span v-else>—</span>
                                        </td>
                                        <td>
                                            <a
                                                v-if="item.ruta_orden_media"
                                                href="javascript:void(0)"
                                                @click="verArchivo(item.ruta_orden_media)"
                                                title="Ver archivo de orden"
                                            >
                                                <img
                                                    v-if="esImagen(item.ruta_orden_media)"
                                                    :src="getArchivoUrl(item.ruta_orden_media)"
                                                    class="img-thumbnail miniatura-archivo"
                                                    alt="Orden médica"
                                                />
                                                <i
                                                    v-else
                                                    class="fa fa-file-pdf-o text-danger icono-archivo"
                                                ></i>
                                            </a>
                                            <span v-else>—</span>
                                        </td>
                                        <td>
                                            <a
                                                v-if="item.ruta_autorizacion_media"
                                                href="javascript:void(0)"
                                                @click="verArchivo(item.ruta_autorizacion_media)"
                                                title="Ver archivo de autorización"
                                            >
                                                <img
                                                    v-if="esImagen(item.ruta_autorizacion_media)"
                                                    :src="
                                                        getArchivoUrl(item.ruta_autorizacion_media)
                                                    "
                                                    class="img-thumbnail miniatura-archivo"
                                                    alt="Autorización"
                                                />
                                                <i
                                                    v-else
                                                    class="fa fa-file-pdf-o text-danger icono-archivo"
                                                ></i>
                                            </a>
                                            <span v-else>—</span>
                                        </td>
                                        <td class="text-nowrap">
                                            <button
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-success btn-sm m-r-5"
                                                @click="abrirAprobar(item)"
                                                title="Aprobar cita"
                                                aria-label="Aprobar cita"
                                            >
                                                <i class="fa fa-check"></i>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm"
                                                @click="abrirRechazar(item)"
                                                title="Rechazar cita"
                                                aria-label="Rechazar cita"
                                            >
                                                <i class="fa fa-times"></i>
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

        <div id="modalAprobar" class="modal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Aprobar Cita</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
                            ×
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <strong>Paciente:</strong> {{ citaSeleccionada.paciente?.nombres
                            }}<br />
                            <strong>Servicio:</strong> {{ citaSeleccionada.servicio?.nombre }}
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group" id="producto-parent">
                                    <label class="control-label">Producto *</label>
                                    <Select2
                                        :value="formAprobar.producto_id"
                                        :options="productoOptions"
                                        width="100%"
                                        :allow-clear="true"
                                        @input="onProductoSelect"
                                    />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Sede *</label>
                                    <select
                                        v-model="formAprobar.sede_id"
                                        class="form-control"
                                        @change="
                                            cargarProductosPorServicio();
                                            cargarDetallesDisponibles();
                                        "
                                    >
                                        <option value="">Seleccione...</option>
                                        <option v-for="s in sedes" :key="s.id" :value="s.id">
                                            {{ s.nom_sede }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Fecha *</label>
                                    <input
                                        type="date"
                                        v-model="formAprobar.fecha"
                                        class="form-control"
                                        @change="cargarDetallesDisponibles"
                                    />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Horarios disponibles *</label>
                                    <select
                                        v-model="formAprobar.agenda_disponibilidad_detalle_id"
                                        class="form-control"
                                        :disabled="
                                            loadingDetalles || detallesDisponibles.length === 0
                                        "
                                    >
                                        <option value="">
                                            {{
                                                loadingDetalles
                                                    ? "Cargando..."
                                                    : detallesDisponibles.length === 0
                                                      ? "Sin horarios disponibles"
                                                      : "Seleccione un horario..."
                                            }}
                                        </option>
                                        <option
                                            v-for="detalle in detallesDisponibles"
                                            :key="detalle.id"
                                            :value="detalle.id"
                                        >
                                            {{ formatearHoraDetalle(detalle.inicio) }} -
                                            {{ formatearHoraDetalle(detalle.fin) }}
                                        </option>
                                    </select>
                                    <small
                                        class="text-muted"
                                        v-if="
                                            formAprobar.sede_id &&
                                            formAprobar.fecha &&
                                            detallesDisponibles.length === 0 &&
                                            !loadingDetalles
                                        "
                                    >
                                        No hay bloques de agenda configurados para esta sede +
                                        servicio en esta fecha.
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div
                            v-if="recomendacionActual"
                            class="alert alert-warning"
                            style="white-space: pre-wrap"
                        >
                            <strong
                                ><i class="fa fa-info-circle"></i> Recomendación para el
                                paciente:</strong
                            ><br />
                            {{ recomendacionActual }}
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="control-label">Observaciones</label>
                                    <textarea
                                        v-model="formAprobar.observaciones"
                                        class="form-control"
                                        rows="2"
                                    ></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-inverse" data-dismiss="modal">
                            Cancelar
                        </button>
                        <button
                            type="button"
                            class="btn btn-success"
                            @click="aprobarCita()"
                            :disabled="loadingGuardar"
                        >
                            <i
                                class="fa"
                                :class="loadingGuardar ? 'fa-spinner fa-spin' : 'fa-check'"
                            ></i>
                            {{ loadingGuardar ? "Guardando..." : "Aprobar y Asignar" }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div id="modalRechazar" class="modal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Rechazar Cita</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
                            ×
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <strong>Paciente:</strong> {{ citaSeleccionada.paciente?.nombres
                            }}<br />
                            <strong>Documento:</strong> {{ citaSeleccionada.paciente?.num_docu }}
                        </div>
                        <div class="form-group">
                            <label class="control-label">Motivo de rechazo *</label>
                            <select v-model="formRechazar.rechazo_motivo_id" class="form-control">
                                <option value="">Seleccione un motivo...</option>
                                <option v-for="m in motivosRechazo" :key="m.id" :value="m.id">
                                    {{ m.motivo }}
                                </option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="control-label">Observación</label>
                            <textarea
                                v-model="formRechazar.rechazo_observacion"
                                class="form-control"
                                rows="3"
                                placeholder="Observación adicional (opcional)"
                            ></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-inverse" data-dismiss="modal">
                            Cancelar
                        </button>
                        <button
                            type="button"
                            class="btn btn-danger"
                            @click="rechazarCita()"
                            :disabled="loadingGuardar"
                        >
                            <i
                                class="fa"
                                :class="loadingGuardar ? 'fa-spinner fa-spin' : 'fa-times'"
                            ></i>
                            {{ loadingGuardar ? "Guardando..." : "Rechazar" }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, getCurrentInstance } from "vue";

const { proxy } = getCurrentInstance();
const dt = ref(null);

import LoadingSpinner from "@/components/comunes/LoadingSpinner.vue";
import { safeDestroyDataTable, safeInitDataTable } from "@/helpers/datatables.js";
import { handleAxiosErrorToast, alertSuccess, alertError } from "@/helpers/alerts";
import { formatFecha } from "@/composables/useCitasUtils.js";
import axios from "axios";

const registros = ref([]);
const servicios = ref([]);
const sedes = ref([]);
const productos = ref([]);
const motivosRechazo = ref([]);
const recomendaciones = ref([]);
const detallesDisponibles = ref([]);
const loadingBuscar = ref(false);
const loadingGuardar = ref(false);
const loadingDetalles = ref(false);
const filtro = ref({
    servicio_cita_id: "",
    sede_id: "",
    busqueda: "",
});
const citaSeleccionada = ref({
    id: null,
    paciente: null,
    servicio: null,
    servicio_cita_id: null,
});
const formAprobar = ref({
    producto_id: "",
    sede_id: "",
    fecha: "",
    agenda_disponibilidad_detalle_id: "",
    duracion_minutos: 30,
    observaciones: "",
});
const formRechazar = ref({
    rechazo_motivo_id: "",
    rechazo_observacion: "",
});
const pollingInterval = ref(null);

const contadorRegistros = computed(() => {
    return registros.value.length;
});
const recomendacionActual = computed(() => {
    if (!formAprobar.value.producto_id) return "";
    const rec = recomendaciones.value.find((r) => r.producto_id == formAprobar.value.producto_id);
    return rec && rec.estado ? rec.recomendacion || "" : "";
});
const productoOptions = computed(() => {
    return (productos.value || []).map((p) => ({
        id: String(p.id),
        cod_cups: p.cod_cups,
        nom_produc: p.nom_produc,
    }));
});

function destroyDataTable() {
    safeDestroyDataTable(dt.value || "#example23");
}
function cargarListas() {
    const self = proxy;

    axios
        .get("/api/citas/servicios")
        .then((r) => {
            self.servicios = r.data;
        })
        .catch((e) => {
            console.error("Error servicios:", e);
        });

    axios
        .get("/api/config-sedes/listar/sedes/activas")
        .then((r) => {
            self.sedes = r.data;
        })
        .catch((e) => {
            console.error("Error sedes:", e);
        });

    // Los productos se cargan al abrir el modal con cargarProductosPorServicio

    axios
        .get("/api/citas-rechazo-motivos")
        .then((r) => {
            self.motivosRechazo = r.data;
        })
        .catch((e) => {
            console.error("Error motivos rechazo:", e);
        });

    axios
        .get("/api/config-recomendacion-productos")
        .then((r) => {
            self.recomendaciones = r.data || [];
        })
        .catch((e) => {
            console.error("Error recomendaciones:", e);
        });
}
async function listarRegistros() {
    destroyDataTable();

    if (loadingBuscar.value) return;
    loadingBuscar.value = true;

    try {
        const params = new URLSearchParams();
        if (filtro.value.servicio_cita_id)
            params.append("servicio_cita_id", filtro.value.servicio_cita_id);
        if (filtro.value.sede_id) params.append("sede_id", filtro.value.sede_id);
        if (filtro.value.busqueda) params.append("busqueda", filtro.value.busqueda);

        const res = await axios.get("/api/citas/pendientes?" + params.toString());

        if (res.status === 200) {
            destroyDataTable();

            const data = res.data.data;
            registros.value = Array.isArray(data) ? data : [];

            nextTick(() => {
                safeInitDataTable(dt.value || "#example23", {
                    dom: "Bfrtip",
                    buttons: ["copy", "csv", "excel", "pdf", "print"],
                });
            });
        }
    } catch (error) {
        handleAxiosErrorToast(error, "No fue posible cargar las solicitudes.");
    } finally {
        loadingBuscar.value = false;
    }
}
function abrirAprobar(cita) {
    citaSeleccionada.value = {
        ...cita,
        servicio_cita_id: cita.servicio_cita_id || cita.servicio?.id,
    };
    formAprobar.value = {
        producto_id: "",
        sede_id: cita.sede_id || "",
        fecha: "",
        agenda_disponibilidad_detalle_id: "",
        duracion_minutos: 30,
        observaciones: "",
    };
    detallesDisponibles.value = [];
    jQuery("#modalAprobar").modal("show");
    cargarProductosPorServicio();
}
function cargarProductosPorServicio() {
    const servicioId = citaSeleccionada.value.servicio_cita_id;
    const sedeId = formAprobar.value.sede_id;

    formAprobar.value.producto_id = "";
    formAprobar.value.agenda_disponibilidad_detalle_id = "";
    detallesDisponibles.value = [];
    productos.value = [];

    if (!servicioId) return;

    var url = "/api/citas/productos/" + servicioId;
    if (sedeId) url += "?sede_id=" + sedeId;

    axios.get(url).then((r) => {
        productos.value = r.data;
    });
}
function onProductoSelect(value) {
    formAprobar.value.producto_id = value || "";
    cargarDetallesDisponibles();
}
async function cargarDetallesDisponibles() {
    if (
        !formAprobar.value.sede_id ||
        !formAprobar.value.fecha ||
        !citaSeleccionada.value.servicio_cita_id
    ) {
        detallesDisponibles.value = [];
        return;
    }
    loadingDetalles.value = true;
    formAprobar.value.agenda_disponibilidad_detalle_id = "";
    try {
        const res = await axios.get("/api/citas/disponibilidad", {
            params: {
                producto_id: formAprobar.value.producto_id || productos.value[0]?.id,
                sede_id: formAprobar.value.sede_id,
                servicio_cita_id: citaSeleccionada.value.servicio_cita_id,
                fecha: formAprobar.value.fecha,
            },
        });
        detallesDisponibles.value = res.data?.data?.detalles || [];
    } catch (error) {
        detallesDisponibles.value = [];
    } finally {
        loadingDetalles.value = false;
    }
}
function formatearHoraDetalle(inicio) {
    if (!inicio) return "";
    const parts = inicio.split(" ");
    return parts[1] ? parts[1].substring(0, 5) : inicio;
}
async function aprobarCita() {
    if (loadingGuardar.value) return;

    if (
        !formAprobar.value.producto_id ||
        !formAprobar.value.sede_id ||
        !formAprobar.value.fecha ||
        !formAprobar.value.agenda_disponibilidad_detalle_id
    ) {
        alertError("Advertencia", "Debe seleccionar producto, sede, fecha y un horario disponible");
        return;
    }

    loadingGuardar.value = true;

    try {
        const detalle = detallesDisponibles.value.find(
            (d) => d.id == formAprobar.value.agenda_disponibilidad_detalle_id
        );
        await axios.post("/api/citas/" + citaSeleccionada.value.id + "/asignar", {
            producto_id: formAprobar.value.producto_id,
            sede_id: formAprobar.value.sede_id,
            agenda_disponibilidad_detalle_id: formAprobar.value.agenda_disponibilidad_detalle_id,
            fecha_hora_inicio: detalle.inicio,
            duracion_minutos: formAprobar.value.duracion_minutos,
            observaciones: formAprobar.value.observaciones,
        });

        alertSuccess("Éxito", "Cita aprobada y asignada correctamente");
        jQuery("#modalAprobar").modal("hide");
        listarRegistros();
    } catch (error) {
        const msg =
            error.response?.data?.error || error.response?.data?.message || "Error al aprobar";
        alertError("Error", msg);
    } finally {
        loadingGuardar.value = false;
    }
}
function abrirRechazar(cita) {
    citaSeleccionada.value = cita;
    formRechazar.value = {
        rechazo_motivo_id: "",
        rechazo_observacion: "",
    };
    jQuery("#modalRechazar").modal("show");
}
async function rechazarCita() {
    if (loadingGuardar.value) return;

    if (!formRechazar.value.rechazo_motivo_id) {
        alertError("Advertencia", "Debe seleccionar un motivo de rechazo");
        return;
    }

    loadingGuardar.value = true;

    try {
        await axios.post("/api/citas/" + citaSeleccionada.value.id + "/rechazar", {
            rechazo_motivo_id: formRechazar.value.rechazo_motivo_id,
            rechazo_observacion: formRechazar.value.rechazo_observacion,
        });

        alertSuccess("Éxito", "Cita rechazada correctamente");
        jQuery("#modalRechazar").modal("hide");
        listarRegistros();
    } catch (error) {
        const msg =
            error.response?.data?.error || error.response?.data?.message || "Error al rechazar";
        alertError("Error", msg);
    } finally {
        loadingGuardar.value = false;
    }
}
function verArchivo(ruta) {
    if (!ruta) return;
    window.open(getArchivoUrl(ruta), "_blank", "noopener,noreferrer");
}
function getArchivoUrl(ruta) {
    if (!ruta) return "";
    const filename = ruta.replace("citas_soportes/", "");
    return "/citas/media/" + filename;
}
function esImagen(ruta) {
    if (!ruta) return false;
    const ext = ruta.split(".").pop().toLowerCase();
    return ["jpg", "jpeg", "png", "gif", "webp"].includes(ext);
}
function limpiarFiltros() {
    filtro.value = { servicio_cita_id: "", sede_id: "", busqueda: "" };
    listarRegistros();
}

onMounted(() => {
    cargarListas();
    listarRegistros();
    axios.post("/api/citas/notificaciones/todas-leidas").catch(() => {});
    pollingInterval.value = setInterval(() => {
        listarRegistros();
    }, 30000);
});

onBeforeUnmount(() => {
    destroyDataTable();
    if (pollingInterval.value) {
        clearInterval(pollingInterval.value);
    }
});
</script>
<style scoped>
.miniatura-archivo {
    max-width: 90px;
    max-height: 90px;
    object-fit: cover;
    cursor: pointer;
    padding: 2px;
}
.icono-archivo {
    font-size: 3em;
    cursor: pointer;
}
</style>

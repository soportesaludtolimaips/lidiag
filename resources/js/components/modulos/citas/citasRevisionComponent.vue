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
                                        <th>Orden</th>
                                        <th>Autorización</th>
                                        <th class="text-nowrap">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="item in registros"
                                        :key="item.id"
                                        :id="`cita-${item.id}`"
                                    >
                                        <td>{{ item.id }}</td>
                                        <td>{{ item.created_at }}</td>
                                        <td>{{ item.paciente?.nombres || "—" }}</td>
                                        <td>{{ item.paciente?.num_docu || "—" }}</td>
                                        <td>{{ item.servicio?.nombre || "—" }}</td>
                                        <td>{{ item.sede?.nom_sede || "—" }}</td>
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
                                            >
                                                <i class="fa fa-check"></i>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm"
                                                @click="abrirRechazar(item)"
                                                title="Rechazar cita"
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

<script>
import LoadingSpinner from "@/components/comunes/LoadingSpinner.vue";
import { safeDestroyDataTable, safeInitDataTable } from "@/helpers/datatables.js";
import { handleAxiosErrorToast, alertSuccess, alertError } from "@/helpers/alerts";

export default {
    name: "CitasRevisionComponent",
    components: { LoadingSpinner },
    computed: {
        contadorRegistros() {
            return this.registros.length;
        },
        recomendacionActual() {
            if (!this.formAprobar.producto_id) return "";
            const rec = this.recomendaciones.find(
                (r) => r.producto_id == this.formAprobar.producto_id
            );
            return rec && rec.estado ? rec.recomendacion || "" : "";
        },
        productoOptions() {
            return (this.productos || []).map((p) => ({
                id: String(p.id),
                cod_cups: p.cod_cups,
                nom_produc: p.nom_produc,
            }));
        },
    },
    mounted() {
        this.cargarListas();
        this.listarRegistros();
        this.pollingInterval = setInterval(() => {
            this.listarRegistros();
            axios.post("/api/citas/notificaciones/todas-leidas").catch(() => {});
        }, 30000);
    },
    beforeUnmount() {
        this.destroyDataTable();
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval);
        }
    },
    data() {
        return {
            registros: [],
            servicios: [],
            sedes: [],
            productos: [],
            motivosRechazo: [],
            recomendaciones: [],
            detallesDisponibles: [],
            loadingBuscar: false,
            loadingGuardar: false,
            loadingDetalles: false,
            filtro: {
                servicio_cita_id: "",
                sede_id: "",
                busqueda: "",
            },
            citaSeleccionada: {
                id: null,
                paciente: null,
                servicio: null,
                servicio_cita_id: null,
            },
            formAprobar: {
                producto_id: "",
                sede_id: "",
                fecha: "",
                agenda_disponibilidad_detalle_id: "",
                duracion_minutos: 30,
                observaciones: "",
            },
            formRechazar: {
                rechazo_motivo_id: "",
                rechazo_observacion: "",
            },
            pollingInterval: null,
        };
    },
    methods: {
        destroyDataTable() {
            safeDestroyDataTable(this.$refs.dt || "#example23");
        },
        cargarListas() {
            const self = this;

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
        },
        async listarRegistros() {
            this.destroyDataTable();

            if (this.loadingBuscar) return;
            this.loadingBuscar = true;

            try {
                const params = new URLSearchParams();
                if (this.filtro.servicio_cita_id)
                    params.append("servicio_cita_id", this.filtro.servicio_cita_id);
                if (this.filtro.sede_id) params.append("sede_id", this.filtro.sede_id);
                if (this.filtro.busqueda) params.append("busqueda", this.filtro.busqueda);

                const res = await axios.get("/api/citas/pendientes?" + params.toString());

                if (res.status === 200) {
                    this.destroyDataTable();

                    const data = typeof res.data === "string" ? JSON.parse(res.data) : res.data;
                    this.registros = Array.isArray(data) ? data : [];

                    this.$nextTick(() => {
                        safeInitDataTable(this.$refs.dt || "#example23", {
                            dom: "Bfrtip",
                            buttons: ["copy", "csv", "excel", "pdf", "print"],
                        });
                    });
                }
            } catch (error) {
                handleAxiosErrorToast(error, "No fue posible cargar las solicitudes.");
            } finally {
                this.loadingBuscar = false;
            }
        },
        abrirAprobar(cita) {
            this.citaSeleccionada = {
                ...cita,
                servicio_cita_id: cita.servicio_cita_id || cita.servicio?.id,
            };
            this.formAprobar = {
                producto_id: "",
                sede_id: cita.sede_id || "",
                fecha: "",
                agenda_disponibilidad_detalle_id: "",
                duracion_minutos: 30,
                observaciones: "",
            };
            this.detallesDisponibles = [];
            jQuery("#modalAprobar").modal("show");
            this.cargarProductosPorServicio();
        },
        cargarProductosPorServicio() {
            const servicioId = this.citaSeleccionada.servicio_cita_id;
            const sedeId = this.formAprobar.sede_id;

            this.formAprobar.producto_id = "";
            this.formAprobar.agenda_disponibilidad_detalle_id = "";
            this.detallesDisponibles = [];
            this.productos = [];

            if (!servicioId) return;

            var url = "/api/citas/productos/" + servicioId;
            if (sedeId) url += "?sede_id=" + sedeId;

            axios.get(url).then((r) => {
                this.productos = r.data;
            });
        },
        onProductoSelect(value) {
            this.formAprobar.producto_id = value || "";
            this.cargarDetallesDisponibles();
        },
        async cargarDetallesDisponibles() {
            if (
                !this.formAprobar.sede_id ||
                !this.formAprobar.fecha ||
                !this.citaSeleccionada.servicio_cita_id
            ) {
                this.detallesDisponibles = [];
                return;
            }
            this.loadingDetalles = true;
            this.formAprobar.agenda_disponibilidad_detalle_id = "";
            try {
                const res = await axios.get("/api/citas/disponibilidad", {
                    params: {
                        producto_id: this.formAprobar.producto_id || this.productos[0]?.id,
                        sede_id: this.formAprobar.sede_id,
                        servicio_cita_id: this.citaSeleccionada.servicio_cita_id,
                        fecha: this.formAprobar.fecha,
                    },
                });
                this.detallesDisponibles = res.data?.detalles || [];
            } catch (error) {
                this.detallesDisponibles = [];
            } finally {
                this.loadingDetalles = false;
            }
        },
        cargarRecomendacion() {},
        formatearHoraDetalle(inicio) {
            if (!inicio) return "";
            const parts = inicio.split(" ");
            return parts[1] ? parts[1].substring(0, 5) : inicio;
        },
        async aprobarCita() {
            if (this.loadingGuardar) return;

            if (
                !this.formAprobar.producto_id ||
                !this.formAprobar.sede_id ||
                !this.formAprobar.fecha ||
                !this.formAprobar.agenda_disponibilidad_detalle_id
            ) {
                alertError(
                    "Advertencia",
                    "Debe seleccionar producto, sede, fecha y un horario disponible"
                );
                return;
            }

            this.loadingGuardar = true;

            try {
                const detalle = this.detallesDisponibles.find(
                    (d) => d.id == this.formAprobar.agenda_disponibilidad_detalle_id
                );
                await axios.post("/api/citas/" + this.citaSeleccionada.id + "/asignar", {
                    producto_id: this.formAprobar.producto_id,
                    sede_id: this.formAprobar.sede_id,
                    agenda_disponibilidad_detalle_id:
                        this.formAprobar.agenda_disponibilidad_detalle_id,
                    fecha_hora_inicio: detalle.inicio,
                    duracion_minutos: this.formAprobar.duracion_minutos,
                    observaciones: this.formAprobar.observaciones,
                });

                alertSuccess("Éxito", "Cita aprobada y asignada correctamente");
                jQuery("#modalAprobar").modal("hide");
                this.listarRegistros();
            } catch (error) {
                const msg =
                    error.response?.data?.error ||
                    error.response?.data?.message ||
                    "Error al aprobar";
                alertError("Error", msg);
            } finally {
                this.loadingGuardar = false;
            }
        },
        abrirRechazar(cita) {
            this.citaSeleccionada = cita;
            this.formRechazar = {
                rechazo_motivo_id: "",
                rechazo_observacion: "",
            };
            jQuery("#modalRechazar").modal("show");
        },
        async rechazarCita() {
            if (this.loadingGuardar) return;

            if (!this.formRechazar.rechazo_motivo_id) {
                alertError("Advertencia", "Debe seleccionar un motivo de rechazo");
                return;
            }

            this.loadingGuardar = true;

            try {
                await axios.post("/api/citas/" + this.citaSeleccionada.id + "/rechazar", {
                    rechazo_motivo_id: this.formRechazar.rechazo_motivo_id,
                    rechazo_observacion: this.formRechazar.rechazo_observacion,
                });

                alertSuccess("Éxito", "Cita rechazada correctamente");
                jQuery("#modalRechazar").modal("hide");
                this.listarRegistros();
            } catch (error) {
                const msg =
                    error.response?.data?.error ||
                    error.response?.data?.message ||
                    "Error al rechazar";
                alertError("Error", msg);
            } finally {
                this.loadingGuardar = false;
            }
        },
        verArchivo(ruta) {
            if (!ruta) return;
            window.open(this.getArchivoUrl(ruta), "_blank", "noopener,noreferrer");
        },
        getArchivoUrl(ruta) {
            if (!ruta) return "";
            const filename = ruta.replace("citas_soportes/", "");
            return "/citas/media/" + filename;
        },
        esImagen(ruta) {
            if (!ruta) return false;
            const ext = ruta.split(".").pop().toLowerCase();
            return ["jpg", "jpeg", "png", "gif", "webp"].includes(ext);
        },
        limpiarFiltros() {
            this.filtro = { servicio_cita_id: "", sede_id: "", busqueda: "" };
            this.listarRegistros();
        },
        formatFecha(fecha) {
            return fecha || "";
        },
    },
};
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

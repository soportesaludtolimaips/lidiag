<template>
    <div>
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">Gestión de Citas</h3>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0)">Inicio</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0)">Citas</a>
                    </li>
                    <li class="breadcrumb-item active">Gestión de citas</li>
                </ol>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title text-themecolor">
                            <i class="fa fa-filter"></i> Filtros de búsqueda
                        </h4>
                        <form action="#">
                            <div class="form-body">
                                <div class="row p-t-2">
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label class="control-label">Estado</label>
                                            <select v-model="busqueda.estado" class="form-control">
                                                <option value="">Todos</option>
                                                <option value="solicitada">Solicitada</option>
                                                <option value="asignada">Asignada</option>
                                                <option value="confirmada">Confirmada</option>
                                                <option value="cancelada">Cancelada</option>
                                                <option value="cumplida">Cumplida</option>
                                                <option value="no_asistio">No asistió</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label class="control-label">Fecha Inicio</label>
                                            <input type="date" v-model="busqueda.fecha_inicio" class="form-control" />
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label class="control-label">Fecha Fin</label>
                                            <input type="date" v-model="busqueda.fecha_fin" class="form-control" />
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="control-label">Búsqueda</label>
                                            <input type="text" v-model="busqueda.busqueda" placeholder="Documento o nombre"
                                                class="form-control" />
                                        </div>
                                    </div>
                                </div>

                                <div class="form-actions mt-3">
                                    <button type="button" class="btn btn-success m-r-5" @click="listarRegistros()"
                                        :disabled="loadingBuscar">
                                        <i class="fa" :class="loadingBuscar ? 'fa-spinner fa-spin' : 'fa-search'"></i>
                                        {{ loadingBuscar ? 'Buscando...' : 'Buscar' }}
                                    </button>
                                    <button type="button" class="btn btn-inverse" @click="limpiarFiltros()">
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
            <div class="col-12 table-responsive">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Listado de Citas</h4>
                        <h6 class="card-subtitle text-info">
                            Gestione las solicitudes de citas y asígneles fecha y hora
                        </h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Fecha Solicitud</th>
                                        <th>Paciente</th>
                                        <th>Documento</th>
                                        <th>Teléfono</th>
                                        <th>Servicio</th>
                                        <th>Orden</th>
                                        <th>Autorización</th>
                                        <th>Estado</th>
                                        <th>Fecha Cita</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in registros" :key="item.id">
                                        <td>{{ formatFecha(item.created_at) }}</td>
                                        <td>{{ item.paciente?.nombres }} {{ item.paciente?.pri_ape }}</td>
                                        <td>{{ item.paciente?.num_docu }}</td>
                                        <td>{{ item.paciente?.cel }}</td>
                                        <td>{{ item.servicio?.nombre }}</td>
                                        <td>{{ item.numero_orden }}</td>
                                        <td>{{ item.numero_autorizacion }}</td>
                                        <td>
                                            <span :class="getEstadoClase(item.estado)"
                                                class="badge badge-pill font-weight-bold">
                                                {{ getEstadoLabel(item.estado) }}
                                            </span>
                                        </td>
                                        <td>{{ item.fecha_hora_inicio ? formatFecha(item.fecha_hora_inicio) : '-' }}</td>
                                        <td class="text-nowrap">
                                            <button type="button"
                                                class="btn btn-outline-primary btn-sm btn-rounded m-1"
                                                @click="actualizar = true; mostrarModalAsignar(item)">
                                                <i class="fa fa-calendar-plus"></i>
                                            </button>
                                            <button v-if="item.estado === 'asignada'" type="button"
                                                class="btn btn-outline-success btn-sm btn-rounded m-1"
                                                @click="confirmarCita(item)">
                                                <i class="fa fa-check"></i>
                                            </button>
                                            <button v-if="item.estado === 'asignada' || item.estado === 'confirmada'"
                                                type="button" class="btn btn-outline-danger btn-sm btn-rounded m-1"
                                                @click="cancelarCita(item)">
                                                <i class="fa fa-times"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-12 text-right">
                                <pagination :data="datosPaginacion" @pagination-change-page="listarRegistros">
                                </pagination>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="modalAsignar" class="modal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Asignar Cita</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Producto *</label>
                                    <select v-model="citaSeleccionada.producto_id" class="form-control"
                                        @change="cargarDisponibilidad()">
                                        <option value="">Seleccione...</option>
                                        <option v-for="prod in productos" :key="prod.id" :value="prod.id">
                                            {{ prod.nom_producto }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Sede *</label>
                                    <select v-model="citaSeleccionada.sede_id" class="form-control">
                                        <option value="">Seleccione...</option>
                                        <option v-for="sede in sedes" :key="sede.id" :value="sede.id">
                                            {{ sede.nom_sede }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Fecha *</label>
                                    <input type="date" v-model="citaSeleccionada.fecha" class="form-control"
                                        @change="cargarDisponibilidad()" />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Hora *</label>
                                    <select v-model="citaSeleccionada.hora" class="form-control">
                                        <option value="">Seleccione...</option>
                                        <option v-for="horario in horarios" :key="horario.inicio" :value="horario.inicio"
                                            :disabled="!horario.disponible">
                                            {{ horario.inicio.substring(11, 16) }}
                                            {{ !horario.disponible ? '(Ocupado)' : '' }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="control-label">Observaciones</label>
                                    <textarea v-model="citaSeleccionada.observaciones" class="form-control" rows="3">
                                    </textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-inverse" data-dismiss="modal">Cerrar</button>
                        <button type="button" class="btn btn-success" @click="asignarCita()"
                            :disabled="loadingGuardar">
                            <i class="fa" :class="loadingGuardar ? 'fa-spinner fa-spin' : 'fa-save'"></i>
                            {{ loadingGuardar ? 'Guardando...' : 'Guardar' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    data: function () {
        return {
            registros: [],
            productos: [],
            sedes: [],
            servicios: [],
            horarios: [],
            datosPaginacion: {},
            busqueda: {
                estado: '',
                fecha_inicio: '',
                fecha_fin: '',
                busqueda: '',
            },
            citaSeleccionada: {
                id: null,
                producto_id: '',
                sede_id: '',
                fecha: '',
                hora: '',
                observaciones: '',
            },
            loadingBuscar: false,
            loadingGuardar: false,
            actualizar: false,
        };
    },

    mounted: function () {
        this.inicializar();
    },

    methods: {
        inicializar: function () {
            this.listarRegistros();
            this.cargarListas();
        },

        listarRegistros: function () {
            var thisVue = this;
            thisVue.loadingBuscar = true;

            var params = new URLSearchParams();
            if (thisVue.busqueda.estado) params.append('estado', thisVue.busqueda.estado);
            if (thisVue.busqueda.fecha_inicio) params.append('fecha_inicio', thisVue.busqueda.fecha_inicio);
            if (thisVue.busqueda.fecha_fin) params.append('fecha_fin', thisVue.busqueda.fecha_fin);
            if (thisVue.busqueda.busqueda) params.append('busqueda', thisVue.busqueda.busqueda);

            axios.get('/api/citas?' + params.toString())
                .then(function (response) {
                    thisVue.registros = response.data.data;
                    thisVue.datosPaginacion = response.data;
                })
                .catch(function (error) {
                    thisVue.mostrarError(error);
                })
                .then(function () {
                    thisVue.loadingBuscar = false;
                });
        },

        cargarListas: function () {
            var thisVue = this;

            axios.get('/api/citas/servicios')
                .then(function (response) {
                    thisVue.servicios = response.data;
                });

            axios.get('/api/config/productos')
                .then(function (response) {
                    thisVue.productos = response.data;
                });

            axios.get('/api/config/sedes')
                .then(function (response) {
                    thisVue.sedes = response.data;
                });
        },

        cargarDisponibilidad: function () {
            var thisVue = this;

            if (!thisVue.citaSeleccionada.producto_id || !thisVue.citaSeleccionada.fecha) {
                return;
            }

            axios.get('/api/citas/disponibilidad', {
                    params: {
                        producto_id: thisVue.citaSeleccionada.producto_id,
                        fecha: thisVue.citaSeleccionada.fecha,
                    },
                })
                .then(function (response) {
                    thisVue.horarios = response.data.horarios;
                })
                .catch(function (error) {
                    thisVue.mostrarError(error);
                });
        },

        mostrarModalAsignar: function (cita) {
            this.citaSeleccionada = {
                id: cita.id,
                producto_id: '',
                sede_id: '',
                fecha: '',
                hora: '',
                observaciones: '',
            };
            this.horarios = [];
            this.actualizar = true;

            jQuery('#modalAsignar').modal('show');
        },

        asignarCita: function () {
            var thisVue = this;

            if (!thisVue.citaSeleccionada.producto_id || !thisVue.citaSeleccionada.sede_id ||
                !thisVue.citaSeleccionada.fecha || !thisVue.citaSeleccionada.hora) {
                thisVue.mostrarAdvertencia('Todos los campos obligatorios deben ser llenados');
                return;
            }

            thisVue.loadingGuardar = true;

            axios.post('/api/citas/' + thisVue.citaSeleccionada.id + '/asignar', {
                    producto_id: thisVue.citaSeleccionada.producto_id,
                    sede_id: thisVue.citaSeleccionada.sede_id,
                    fecha_hora_inicio: thisVue.citaSeleccionada.hora,
                    observaciones: thisVue.citaSeleccionada.observaciones,
                })
                .then(function (response) {
                    thisVue.mostrarExito('Cita asignada correctamente');
                    jQuery('#modalAsignar').modal('hide');
                    thisVue.listarRegistros();
                })
                .catch(function (error) {
                    thisVue.mostrarError(error);
                })
                .then(function () {
                    thisVue.loadingGuardar = false;
                });
        },

        confirmarCita: function (cita) {
            var thisVue = this;

            thisVue.$swal({
                title: 'Confirmar Cita',
                text: '¿Está seguro de confirmar esta cita?',
                type: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, confirmar',
                cancelButtonText: 'No',
            }).then(function (result) {
                if (result.value) {
                    axios.post('/api/citas/' + cita.id + '/confirmar')
                        .then(function (response) {
                            thisVue.mostrarExito('Cita confirmada');
                            thisVue.listarRegistros();
                        })
                        .catch(function (error) {
                            thisVue.mostrarError(error);
                        });
                }
            });
        },

        cancelarCita: function (cita) {
            var thisVue = this;

            thisVue.$swal({
                title: 'Cancelar Cita',
                input: 'text',
                text: 'Ingrese el motivo de cancelación (opcional)',
                type: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, cancelar',
                cancelButtonText: 'No',
            }).then(function (result) {
                if (result.value || result.dismiss === thisVue.$swal.DismissReason.cancel) {
                    var motivo = result.value || '';
                    axios.post('/api/citas/' + cita.id + '/cancelar', {
                            motivo: motivo,
                        })
                        .then(function (response) {
                            thisVue.mostrarExito('Cita cancelada');
                            thisVue.listarRegistros();
                        })
                        .catch(function (error) {
                            thisVue.mostrarError(error);
                        });
                }
            });
        },

        limpiarFiltros: function () {
            this.busqueda = {
                estado: '',
                fecha_inicio: '',
                fecha_fin: '',
                busqueda: '',
            };
            this.listarRegistros();
        },

        formatFecha: function (fecha) {
            if (!fecha) return '';
            return moment(fecha).format('DD/MM/YYYY HH:mm');
        },

        getEstadoLabel: function (estado) {
            var estados = {
                solicitada: 'Solicitada',
                asignada: 'Asignada',
                confirmada: 'Confirmada',
                cancelada: 'Cancelada',
                cumplida: 'Cumplida',
                no_asistio: 'No asistió',
            };
            return estados[estado] || estado;
        },

        getEstadoClase: function (estado) {
            var clases = {
                solicitada: 'badge-warning',
                asignada: 'badge-info',
                confirmada: 'badge-success',
                cancelada: 'badge-danger',
                cumplida: 'badge-primary',
                no_asistio: 'badge-secondary',
            };
            return clases[estado] || 'badge-default';
        },

        mostrarError: function (error) {
            var mensaje = error.response?.data?.error || error.message || 'Error desconocido';
            this.mostrarAdvertencia(mensaje);
        },

        mostrarAdvertencia: function (mensaje) {
            this.$swal('Advertencia', mensaje, 'warning');
        },

        mostrarExito: function (mensaje) {
            this.$swal('Éxito', mensaje, 'success');
        },
    },
};
</script>
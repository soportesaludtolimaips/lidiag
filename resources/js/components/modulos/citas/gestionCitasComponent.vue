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
                    <li class="breadcrumb-item active">Gestión de Citas</li>
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
                                            <select v-model="filtros.estado" class="form-control">
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
                                            <input type="date" v-model="filtros.fecha_inicio" class="form-control" />
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label class="control-label">Fecha Fin</label>
                                            <input type="date" v-model="filtros.fecha_fin" class="form-control" />
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label class="control-label">Servicio</label>
                                            <select v-model="filtros.servicio_cita_id" class="form-control">
                                                <option value="">Todos</option>
                                                <option v-for="serv in servicios" :key="serv.id" :value="serv.id">
                                                    {{ serv.nombre }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="control-label">Búsqueda</label>
                                            <input type="text" v-model="filtros.busqueda" placeholder="Documento o nombre"
                                                class="form-control" />
                                        </div>
                                    </div>
                                </div>

                                <div class="form-actions mt-3">
                                    <button type="button" class="btn btn-success m-r-5" @click="listarCitas()"
                                        :disabled="loadingListar">
                                        <i class="fa" :class="loadingListar ? 'fa-spinner fa-spin' : 'fa-search'"></i>
                                        {{ loadingListar ? 'Buscando...' : 'Buscar' }}
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
                                        <th>#</th>
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
                                    <tr v-for="(item, index) in citas" :key="item.id">
                                        <td>{{ index + 1 }}</td>
                                        <td>{{ formatFecha(item.created_at) }}</td>
                                        <td>{{ item.paciente?.nombres }} {{ item.paciente?.pri_ape }}</td>
                                        <td>{{ item.paciente?.num_docu }}</td>
                                        <td>{{ item.paciente?.cel }}</td>
                                        <td>{{ item.servicio?.nombre }}</td>
                                        <td>{{ item.numero_orden || '-' }}</td>
                                        <td>{{ item.numero_autorizacion || '-' }}</td>
                                        <td>
                                            <span :class="getEstadoClase(item.estado)" class="badge badge-pill font-weight-bold">
                                                {{ getEstadoLabel(item.estado) }}
                                            </span>
                                        </td>
                                        <td>{{ item.fecha_hora_inicio ? formatFecha(item.fecha_hora_inicio) : '-' }}</td>
                                        <td class="text-nowrap">
                                            <button type="button"
                                                class="btn btn-outline-primary btn-sm btn-rounded m-1"
                                                @click="abrirModalAsignar(item)"
                                                v-if="item.estado === 'solicitada'"
                                                title="Asignar Cita">
                                                <i class="fa fa-calendar-plus"></i>
                                            </button>
                                            <button type="button"
                                                class="btn btn-outline-success btn-sm btn-rounded m-1"
                                                @click="confirmarCita(item)"
                                                v-if="item.estado === 'asignada'"
                                                title="Confirmar">
                                                <i class="fa fa-check"></i>
                                            </button>
                                            <button type="button"
                                                class="btn btn-outline-danger btn-sm btn-rounded m-1"
                                                @click="cancelarCita(item)"
                                                v-if="item.estado === 'asignada' || item.estado === 'confirmada'"
                                                title="Cancelar">
                                                <i class="fa fa-times"></i>
                                            </button>
                                            <button type="button"
                                                class="btn btn-outline-info btn-sm btn-rounded m-1"
                                                @click="verDetalle(item)"
                                                title="Ver Detalle">
                                                <i class="fa fa-eye"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="citas.length === 0">
                                        <td colspan="11" class="text-center text-muted">
                                            No hay citas registradas.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-12 text-right">
                                <pagination :data="datosPaginacion" @pagination-change-page="listarCitas">
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
                        <h4 class="modal-title">
                            <i class="fa fa-calendar-plus"></i> Asignar Cita
                        </h4>
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                    </div>
                    <div class="modal-body" v-if="citaSeleccionada">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="alert alert-info">
                                    <strong>Paciente:</strong> {{ citaSeleccionada.paciente?.nombres }}
                                    {{ citaSeleccionada.paciente?.pri_ape }}<br>
                                    <strong>Documento:</strong> {{ citaSeleccionada.paciente?.num_docu }}<br>
                                    <strong>Teléfono:</strong> {{ citaSeleccionada.paciente?.cel }}<br>
                                    <strong>Servicio:</strong> {{ citaSeleccionada.servicio?.nombre }}<br>
                                    <strong>Orden:</strong> {{ citaSeleccionada.numero_orden || '-' }}<br>
                                    <strong>Autorización:</strong> {{ citaSeleccionada.numero_autorizacion || '-' }}
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Producto *</label>
                                    <select v-model="formAsignar.producto_id" class="form-control"
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
                                    <select v-model="formAsignar.sede_id" class="form-control">
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
                                    <input type="date" v-model="formAsignar.fecha" class="form-control"
                                        @change="cargarDisponibilidad()" />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Hora *</label>
                                    <select v-model="formAsignar.hora" class="form-control">
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
                                    <textarea v-model="formAsignar.observaciones" class="form-control" rows="3">
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

        <div id="modalDetalle" class="modal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">
                            <i class="fa fa-eye"></i> Detalle de Cita
                        </h4>
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                    </div>
                    <div class="modal-body" v-if="citaSeleccionada">
                        <div class="row">
                            <div class="col-md-6">
                                <h5><strong>Datos del Paciente</strong></h5>
                                <p>
                                    <strong>Nombre:</strong> {{ citaSeleccionada.paciente?.nombres }}
                                    {{ citaSeleccionada.paciente?.pri_ape }}<br>
                                    <strong>Documento:</strong> {{ citaSeleccionada.paciente?.num_docu }}<br>
                                    <strong>Teléfono:</strong> {{ citaSeleccionada.paciente?.cel }}<br>
                                    <strong>Dirección:</strong> {{ citaSeleccionada.paciente?.direccion || '-' }}
                                </p>
                            </div>
                            <div class="col-md-6">
                                <h5><strong>Datos de la Cita</strong></h5>
                                <p>
                                    <strong>Servicio:</strong> {{ citaSeleccionada.servicio?.nombre }}<br>
                                    <strong>Producto:</strong> {{ citaSeleccionada.producto?.nom_produc || '-' }}<br>
                                    <strong>Sede:</strong> {{ citaSeleccionada.sede?.nom_sede || '-' }}<br>
                                    <strong>Estado:</strong> {{ getEstadoLabel(citaSeleccionada.estado) }}<br>
                                    <strong>Fecha Cita:</strong> {{ citaSeleccionada.fecha_hora_inicio ? formatFecha(citaSeleccionada.fecha_hora_inicio) : '-' }}
                                </p>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-12">
                                <h5><strong>Documentos</strong></h5>
                                <p>
                                    <strong>Orden:</strong> {{ citaSeleccionada.numero_orden || '-' }}<br>
                                    <strong>Autorización:</strong> {{ citaSeleccionada.numero_autorizacion || '-' }}
                                </p>
                            </div>
                        </div>
                        <div class="row mt-2" v-if="citaSeleccionada.observaciones">
                            <div class="col-md-12">
                                <h5><strong>Observaciones</strong></h5>
                                <p>{{ citaSeleccionada.observaciones }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-inverse" data-dismiss="modal">Cerrar</button>
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
            citas: [],
            servicios: [],
            productos: [],
            sedes: [],
            horarios: [],
            datosPaginacion: {},
            filtros: {
                estado: '',
                fecha_inicio: '',
                fecha_fin: '',
                servicio_cita_id: '',
                busqueda: '',
            },
            citaSeleccionada: null,
            formAsignar: {
                producto_id: '',
                sede_id: '',
                fecha: '',
                hora: '',
                observaciones: '',
            },
            loadingListar: false,
            loadingGuardar: false,
        };
    },

    mounted: function () {
        this.inicializar();
    },

    methods: {
        inicializar: function () {
            this.listarCitas();
            this.cargarServicios();
            this.cargarSedes();
        },

        listarCitas: function (page = 1) {
            var thisVue = this;
            thisVue.loadingListar = true;

            var params = new URLSearchParams();
            params.append('page', page);
            if (thisVue.filtros.estado) params.append('estado', thisVue.filtros.estado);
            if (thisVue.filtros.fecha_inicio) params.append('fecha_inicio', thisVue.filtros.fecha_inicio);
            if (thisVue.filtros.fecha_fin) params.append('fecha_fin', thisVue.filtros.fecha_fin);
            if (thisVue.filtros.servicio_cita_id) params.append('servicio_cita_id', thisVue.filtros.servicio_cita_id);
            if (thisVue.filtros.busqueda) params.append('busqueda', thisVue.filtros.busqueda);

            axios.get('/api/citas?' + params.toString())
                .then(function (response) {
                    thisVue.citas = response.data.data;
                    thisVue.datosPaginacion = response.data;
                })
                .catch(function (error) {
                    thisVue.mostrarError(error);
                })
                .then(function () {
                    thisVue.loadingListar = false;
                });
        },

        cargarServicios: function () {
            var thisVue = this;

            axios.get('/api/citas/servicios')
                .then(function (response) {
                    thisVue.servicios = response.data;
                })
                .catch(function (error) {
                    console.error('Error:', error);
                });
        },

        cargarSedes: function () {
            var thisVue = this;

            axios.get('/api/config-sedes')
                .then(function (response) {
                    thisVue.sedes = response.data;
                })
                .catch(function (error) {
                    console.error('Error:', error);
                });
        },

        cargarProductosPorServicio: function (servicioId) {
            var thisVue = this;

            if (!servicioId) {
                thisVue.productos = [];
                return;
            }

            axios.get('/api/citas/productos/' + servicioId)
                .then(function (response) {
                    thisVue.productos = response.data;
                })
                .catch(function (error) {
                    console.error('Error:', error);
                });
        },

        cargarDisponibilidad: function () {
            var thisVue = this;

            if (!thisVue.formAsignar.producto_id || !thisVue.formAsignar.fecha) {
                thisVue.horarios = [];
                return;
            }

            axios.get('/api/citas/disponibilidad', {
                    params: {
                        producto_id: thisVue.formAsignar.producto_id,
                        fecha: thisVue.formAsignar.fecha,
                    },
                })
                .then(function (response) {
                    thisVue.horarios = response.data.horarios;
                })
                .catch(function (error) {
                    thisVue.mostrarError(error);
                });
        },

        abrirModalAsignar: function (cita) {
            this.citaSeleccionada = cita;
            this.formAsignar = {
                producto_id: '',
                sede_id: '',
                fecha: '',
                hora: '',
                observaciones: '',
            };
            this.horarios = [];

            this.cargarProductosPorServicio(cita.servicio_cita_id);
            jQuery('#modalAsignar').modal('show');
        },

        asignarCita: function () {
            var thisVue = this;

            if (!thisVue.formAsignar.producto_id || !thisVue.formAsignar.sede_id ||
                !thisVue.formAsignar.fecha || !thisVue.formAsignar.hora) {
                thisVue.mostrarAdvertencia('Todos los campos obligatorios deben ser llenados');
                return;
            }

            thisVue.loadingGuardar = true;

            var fechaHora = thisVue.formAsignar.fecha + ' ' + thisVue.formAsignar.hora.substring(11, 16) + ':00';

            axios.post('/api/citas/' + thisVue.citaSeleccionada.id + '/asignar', {
                    producto_id: thisVue.formAsignar.producto_id,
                    sede_id: thisVue.formAsignar.sede_id,
                    fecha_hora_inicio: fechaHora,
                    observaciones: thisVue.formAsignar.observaciones,
                })
                .then(function (response) {
                    thisVue.mostrarExito('Cita asignada correctamente');
                    jQuery('#modalAsignar').modal('hide');
                    thisVue.listarCitas();
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
                            thisVue.listarCitas();
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
                            thisVue.listarCitas();
                        })
                        .catch(function (error) {
                            thisVue.mostrarError(error);
                        });
                }
            });
        },

        verDetalle: function (cita) {
            this.citaSeleccionada = cita;
            jQuery('#modalDetalle').modal('show');
        },

        limpiarFiltros: function () {
            this.filtros = {
                estado: '',
                fecha_inicio: '',
                fecha_fin: '',
                servicio_cita_id: '',
                busqueda: '',
            };
            this.listarCitas();
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
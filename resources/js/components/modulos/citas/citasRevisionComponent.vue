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
                <button type="button" class="btn btn-info" @click="listarRegistros()" :disabled="loading">
                    <i class="fa" :class="loading ? 'fa-spinner fa-spin' : 'fa-refresh'"></i>
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
                        <div class="row p-t-2">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Servicio</label>
                                    <select v-model="filtro.servicio_cita_id" class="form-control" @change="listarRegistros()">
                                        <option value="">Todos</option>
                                        <option v-for="s in servicios" :key="s.id" :value="s.id">{{ s.nombre }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Sede</label>
                                    <select v-model="filtro.sede_id" class="form-control" @change="listarRegistros()">
                                        <option value="">Todas</option>
                                        <option v-for="s in sedes" :key="s.id" :value="s.id">{{ s.nom_sede }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Búsqueda</label>
                                    <input type="text" v-model="filtro.busqueda" class="form-control"
                                        placeholder="Documento o nombre" @keyup.enter="listarRegistros()" />
                                </div>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="button" class="btn btn-success m-r-5" @click="listarRegistros()">
                                    <i class="fa fa-search"></i> Buscar
                                </button>
                                <button type="button" class="btn btn-inverse" @click="limpiarFiltros()">
                                    <i class="fa fa-refresh"></i> Limpiar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Solicitudes Pendientes</h4>
                        <h6 class="card-subtitle text-info">
                            Revise las solicitudes de citas y apruebe o rechace cada una
                        </h6>

                        <div v-if="loading" class="text-center p-4">
                            <i class="fa fa-spinner fa-spin fa-2x"></i>
                            <p class="mt-2">Cargando solicitudes...</p>
                        </div>

                        <div v-else-if="registros.length === 0" class="text-center p-4">
                            <i class="fa fa-inbox fa-3x text-muted"></i>
                            <p class="mt-2 text-muted">No hay solicitudes pendientes de revisión</p>
                        </div>

                        <div v-else class="row">
                            <div class="col-md-6 col-lg-4" v-for="item in registros" :key="item.id">
                                <div class="card border shadow-sm mb-3">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <strong>#{{ item.id }}</strong>
                                        <span class="badge badge-warning">{{ item.created_at ? formatFecha(item.created_at) : '' }}</span>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-2">
                                            <strong>Paciente:</strong>
                                            {{ item.paciente?.nombres }} {{ item.paciente?.pri_ape }} {{ item.paciente?.seg_ape || '' }}
                                        </div>
                                        <div class="mb-2">
                                            <strong>Documento:</strong> {{ item.paciente?.num_docu || '—' }}
                                        </div>
                                        <div class="mb-2">
                                            <strong>Teléfono:</strong> {{ item.paciente?.cel || '—' }}
                                        </div>
                                        <div class="mb-2">
                                            <strong>Servicio:</strong> {{ item.servicio?.nombre || '—' }}
                                        </div>
                                        <div class="mb-2">
                                            <strong>Orden:</strong> {{ item.numero_orden || '—' }}
                                            <a v-if="item.ruta_orden_media" href="javascript:void(0)" class="ml-2 text-info"
                                                @click="verArchivo(item.ruta_orden_media)" title="Ver archivo de orden">
                                                <i class="fa fa-paperclip"></i>
                                            </a>
                                        </div>
                                        <div class="mb-2">
                                            <strong>Autorización:</strong> {{ item.numero_autorizacion || '—' }}
                                            <a v-if="item.ruta_autorizacion_media" href="javascript:void(0)" class="ml-2 text-info"
                                                @click="verArchivo(item.ruta_autorizacion_media)" title="Ver archivo de autorización">
                                                <i class="fa fa-paperclip"></i>
                                            </a>
                                        </div>
                                    </div>
                                    <div class="card-footer d-flex justify-content-between">
                                        <button type="button" class="btn btn-success btn-sm"
                                            @click="abrirAprobar(item)">
                                            <i class="fa fa-check"></i> Aprobar
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm"
                                            @click="abrirRechazar(item)">
                                            <i class="fa fa-times"></i> Rechazar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-12 text-right">
                                <pagination :data="datosPaginacion" @pagination-change-page="cambiarPagina">
                                </pagination>
                            </div>
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
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <strong>Paciente:</strong> {{ citaSeleccionada.paciente?.nombres }} {{ citaSeleccionada.paciente?.pri_ape }}<br />
                            <strong>Servicio:</strong> {{ citaSeleccionada.servicio?.nombre }}
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Producto *</label>
                                    <select v-model="formAprobar.producto_id" class="form-control">
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
                                    <select v-model="formAprobar.sede_id" class="form-control">
                                        <option value="">Seleccione...</option>
                                        <option v-for="s in sedes" :key="s.id" :value="s.id">{{ s.nom_sede }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Fecha *</label>
                                    <input type="date" v-model="formAprobar.fecha" class="form-control" />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Hora *</label>
                                    <input type="time" v-model="formAprobar.hora" class="form-control" />
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Duración (min)</label>
                                    <input type="number" v-model="formAprobar.duracion_minutos" class="form-control" min="15" step="5" />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Observaciones</label>
                                    <textarea v-model="formAprobar.observaciones" class="form-control" rows="2"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-inverse" data-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-success" @click="aprobarCita()" :disabled="loadingGuardar">
                            <i class="fa" :class="loadingGuardar ? 'fa-spinner fa-spin' : 'fa-check'"></i>
                            {{ loadingGuardar ? 'Guardando...' : 'Aprobar y Asignar' }}
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
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <strong>Paciente:</strong> {{ citaSeleccionada.paciente?.nombres }} {{ citaSeleccionada.paciente?.pri_ape }}<br />
                            <strong>Documento:</strong> {{ citaSeleccionada.paciente?.num_docu }}
                        </div>
                        <div class="form-group">
                            <label class="control-label">Motivo de rechazo *</label>
                            <select v-model="formRechazar.rechazo_motivo_id" class="form-control">
                                <option value="">Seleccione un motivo...</option>
                                <option v-for="m in motivosRechazo" :key="m.id" :value="m.id">{{ m.motivo }}</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="control-label">Observación</label>
                            <textarea v-model="formRechazar.rechazo_observacion" class="form-control" rows="3"
                                placeholder="Observación adicional (opcional)"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-inverse" data-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-danger" @click="rechazarCita()" :disabled="loadingGuardar">
                            <i class="fa" :class="loadingGuardar ? 'fa-spinner fa-spin' : 'fa-times'"></i>
                            {{ loadingGuardar ? 'Guardando...' : 'Rechazar' }}
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
            servicios: [],
            sedes: [],
            productos: [],
            motivosRechazo: [],
            datosPaginacion: {},
            loading: false,
            loadingGuardar: false,
            filtro: {
                servicio_cita_id: '',
                sede_id: '',
                busqueda: '',
            },
            citaSeleccionada: {
                id: null,
                paciente: null,
                servicio: null,
            },
            formAprobar: {
                producto_id: '',
                sede_id: '',
                fecha: '',
                hora: '',
                duracion_minutos: 30,
                observaciones: '',
            },
            formRechazar: {
                rechazo_motivo_id: '',
                rechazo_observacion: '',
            },
        };
    },
    mounted: function () {
        this.cargarListas();
        this.listarRegistros();
    },
    methods: {
        cargarListas: function () {
            var thisVue = this;

            axios.get('/api/citas/servicios')
                .then(function (r) { thisVue.servicios = r.data; });

            axios.get('/api/config/sedes')
                .then(function (r) { thisVue.sedes = r.data; });

            axios.get('/api/config/productos')
                .then(function (r) { thisVue.productos = r.data; });

            axios.get('/api/citas-rechazo-motivos')
                .then(function (r) { thisVue.motivosRechazo = r.data; });
        },
        listarRegistros: function () {
            var thisVue = this;
            thisVue.loading = true;

            var params = new URLSearchParams();
            if (thisVue.filtro.servicio_cita_id) params.append('servicio_cita_id', thisVue.filtro.servicio_cita_id);
            if (thisVue.filtro.sede_id) params.append('sede_id', thisVue.filtro.sede_id);
            if (thisVue.filtro.busqueda) params.append('busqueda', thisVue.filtro.busqueda);

            axios.get('/api/citas/pendientes?' + params.toString())
                .then(function (response) {
                    thisVue.registros = response.data.data;
                    thisVue.datosPaginacion = response.data;
                })
                .catch(function (error) {
                    thisVue.$swal('Error', 'No fue posible cargar las solicitudes', 'error');
                })
                .then(function () {
                    thisVue.loading = false;
                });
        },
        cambiarPagina: function (page) {
            var thisVue = this;
            thisVue.loading = true;

            var params = new URLSearchParams();
            if (thisVue.filtro.servicio_cita_id) params.append('servicio_cita_id', thisVue.filtro.servicio_cita_id);
            if (thisVue.filtro.sede_id) params.append('sede_id', thisVue.filtro.sede_id);
            if (thisVue.filtro.busqueda) params.append('busqueda', thisVue.filtro.busqueda);
            if (page) params.append('page', page);

            axios.get('/api/citas/pendientes?' + params.toString())
                .then(function (response) {
                    thisVue.registros = response.data.data;
                    thisVue.datosPaginacion = response.data;
                })
                .catch(function (error) {
                    thisVue.$swal('Error', 'No fue posible cargar las solicitudes', 'error');
                })
                .then(function () {
                    thisVue.loading = false;
                });
        },
        abrirAprobar: function (cita) {
            this.citaSeleccionada = cita;
            this.formAprobar = {
                producto_id: '',
                sede_id: '',
                fecha: '',
                hora: '',
                duracion_minutos: 30,
                observaciones: '',
            };
            jQuery('#modalAprobar').modal('show');
        },
        aprobarCita: function () {
            var thisVue = this;

            if (!thisVue.formAprobar.producto_id || !thisVue.formAprobar.sede_id ||
                !thisVue.formAprobar.fecha || !thisVue.formAprobar.hora) {
                thisVue.$swal('Advertencia', 'Todos los campos obligatorios deben ser llenados', 'warning');
                return;
            }

            thisVue.loadingGuardar = true;

            var fechaHora = thisVue.formAprobar.fecha + ' ' + thisVue.formAprobar.hora + ':00';

            axios.post('/api/citas/' + thisVue.citaSeleccionada.id + '/asignar', {
                producto_id: thisVue.formAprobar.producto_id,
                sede_id: thisVue.formAprobar.sede_id,
                fecha_hora_inicio: fechaHora,
                duracion_minutos: thisVue.formAprobar.duracion_minutos,
                observaciones: thisVue.formAprobar.observaciones,
            })
                .then(function () {
                    thisVue.$swal('Éxito', 'Cita aprobada y asignada correctamente', 'success');
                    jQuery('#modalAprobar').modal('hide');
                    thisVue.listarRegistros();
                })
                .catch(function (error) {
                    var msg = error.response?.data?.error || error.response?.data?.message || 'Error al aprobar';
                    thisVue.$swal('Error', msg, 'error');
                })
                .then(function () {
                    thisVue.loadingGuardar = false;
                });
        },
        abrirRechazar: function (cita) {
            this.citaSeleccionada = cita;
            this.formRechazar = {
                rechazo_motivo_id: '',
                rechazo_observacion: '',
            };
            jQuery('#modalRechazar').modal('show');
        },
        rechazarCita: function () {
            var thisVue = this;

            if (!thisVue.formRechazar.rechazo_motivo_id) {
                thisVue.$swal('Advertencia', 'Debe seleccionar un motivo de rechazo', 'warning');
                return;
            }

            thisVue.loadingGuardar = true;

            axios.post('/api/citas/' + thisVue.citaSeleccionada.id + '/rechazar', {
                rechazo_motivo_id: thisVue.formRechazar.rechazo_motivo_id,
                rechazo_observacion: thisVue.formRechazar.rechazo_observacion,
            })
                .then(function () {
                    thisVue.$swal('Éxito', 'Cita rechazada correctamente', 'success');
                    jQuery('#modalRechazar').modal('hide');
                    thisVue.listarRegistros();
                })
                .catch(function (error) {
                    var msg = error.response?.data?.error || error.response?.data?.message || 'Error al rechazar';
                    thisVue.$swal('Error', msg, 'error');
                })
                .then(function () {
                    thisVue.loadingGuardar = false;
                });
        },
        verArchivo: function (ruta) {
            if (!ruta) return;
            var filename = ruta.replace('citas_soportes/', '');
            var url = '/citas/media/' + filename;
            window.open(url, '_blank');
        },
        limpiarFiltros: function () {
            this.filtro = { servicio_cita_id: '', sede_id: '', busqueda: '' };
            this.listarRegistros();
        },
        formatFecha: function (fecha) {
            if (!fecha) return '';
            return moment(fecha).format('DD/MM/YYYY HH:mm');
        },
    },
};
</script>

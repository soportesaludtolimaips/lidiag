<template>
    <div>
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">Configuración</h3>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0)">Inicio</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0)">Configuración</a>
                    </li>
                    <li class="breadcrumb-item active">Servicios de Citas</li>
                </ol>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Servicios</h4>
                        <h6 class="card-subtitle text-info">Seleccione un servicio para administrar sus productos.</h6>
                        <div class="list-group">
                            <button v-for="servicio in servicios" :key="servicio.id" type="button"
                                class="list-group-item list-group-item-action"
                                :class="servicioSeleccionado === servicio.id ? 'active' : ''"
                                @click="seleccionarServicio(servicio)">
                                {{ servicio.nombre }}
                                <span class="badge badge-danger badge-pill float-right"
                                    v-if="servicio.productos_count">
                                    {{ servicio.productos_count }}
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card" v-if="servicioSeleccionado">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="card-title">{{ servicioActual?.nombre }}</h4>
                                <h6 class="card-subtitle text-info">Productos vinculados a este servicio</h6>
                            </div>
                            <button type="button" class="btn btn-info" @click="mostrarModalAgregar()">
                                <i class="fa fa-plus-circle m-r-5"></i> Agregar Producto
                            </button>
                        </div>

                        <div class="table-responsive m-t-20">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Producto</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in productosVinculados" :key="item.id">
                                        <td>{{ item.producto?.cod_cups }}</td>
                                        <td>{{ item.producto?.nom_produc }}</td>
                                        <td>
                                            <span class="label label-success" v-if="item.estado">Activo</span>
                                            <span class="label label-danger" v-else>Inactivo</span>
                                        </td>
                                        <td class="text-nowrap">
                                            <button type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm"
                                                @click="eliminarVinculo(item.id)">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="productosVinculados.length === 0">
                                        <td colspan="4" class="text-center text-muted">
                                            No hay productos vinculados a este servicio.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card" v-else>
                    <div class="card-body text-center">
                        <p class="text-muted">Seleccione un servicio de la lista para ver sus productos.</p>
                    </div>
                </div>
            </div>
        </div>

        <div id="modalAgregar" class="modal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Agregar Producto</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="control-label">Producto *</label>
                            <select v-model="productoSeleccionado" class="form-control">
                                <option value="">Seleccione...</option>
                                <option v-for="prod in productosDisponibles" :key="prod.id" :value="prod.id">
                                    {{ prod.cod_cups }} - {{ prod.nom_produc }}
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-inverse" data-dismiss="modal">Cerrar</button>
                        <button type="button" class="btn btn-success" @click="agregarProducto()"
                            :disabled="loadingGuardar || !productoSeleccionado">
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
            servicios: [],
            servicioSeleccionado: null,
            servicioActual: null,
            productosVinculados: [],
            productosDisponibles: [],
            productoSeleccionado: '',
           loadingGuardar: false,
            loadingListar: false,
        };
    },

    mounted: function () {
        this.cargarServicios();
    },

    methods: {
        cargarServicios: function () {
            var thisVue = this;

            axios.get('/api/config-servicio-citas')
                .then(function (response) {
                    thisVue.servicios = response.data;

                    if (thisVue.servicios.length > 0) {
                        thisVue.seleccionarServicio(thisVue.servicios[0]);
                    }
                })
                .catch(function (error) {
                    console.error('Error:', error);
                });
        },

        seleccionarServicio: function (servicio) {
            this.servicioSeleccionado = servicio.id;
            this.servicioActual = servicio;
            this.cargarProductosVinculados();
        },

        cargarProductosVinculados: function () {
            var thisVue = this;

            if (!thisVue.servicioSeleccionado) {
                return;
            }

            axios.get('/api/config/servicio-producto', {
                    params: { servicio_cita_id: thisVue.servicioSeleccionado },
                })
                .then(function (response) {
                    thisVue.productosVinculados = response.data;
                })
                .catch(function (error) {
                    console.error('Error:', error);
                });
        },

        mostrarModalAgregar: function () {
            var thisVue = this;

            thisVue.productoSeleccionado = '';
            thisVue.loadingGuardar = false;

            axios.get('/api/config/servicio-producto/productos-no-vinculados', {
                    params: { servicio_cita_id: thisVue.servicioSeleccionado },
                })
                .then(function (response) {
                    thisVue.productosDisponibles = response.data;
                    jQuery('#modalAgregar').modal('show');
                })
                .catch(function (error) {
                    console.error('Error:', error);
                });
        },

        agregarProducto: function () {
            var thisVue = this;

            if (!thisVue.productoSeleccionado) {
                return;
            }

            thisVue.loadingGuardar = true;

            axios.post('/api/config/servicio-producto', {
                    servicio_cita_id: thisVue.servicioSeleccionado,
                    producto_id: thisVue.productoSeleccionado,
                })
                .then(function (response) {
                    thisVue.mostrarExito('Producto agregado correctamente');
                    jQuery('#modalAgregar').modal('hide');
                    thisVue.cargarProductosVinculados();
                })
                .catch(function (error) {
                    thisVue.mostrarError(error);
                })
                .then(function () {
                    thisVue.loadingGuardar = false;
                });
        },

        eliminarVinculo: function (id) {
            var thisVue = this;

            thisVue.$swal({
                title: '¿Está seguro?',
                text: 'El producto se desvinculará del servicio.',
                type: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
            }).then(function (result) {
                if (result.value) {
                    axios.delete('/api/config/servicio-producto/' + id)
                        .then(function () {
                            thisVue.mostrarExito('Producto desvinculado');
                            thisVue.cargarProductosVinculados();
                        })
                        .catch(function (error) {
                            thisVue.mostrarError(error);
                        });
                }
            });
        },

        mostrarError: function (error) {
            var mensaje = error.response?.data?.message || error.message || 'Error desconocido';
            this.$swal('Error', mensaje, 'error');
        },

        mostrarExito: function (mensaje) {
            this.$swal('Éxito', mensaje, 'success');
        },
    },
};
</script>
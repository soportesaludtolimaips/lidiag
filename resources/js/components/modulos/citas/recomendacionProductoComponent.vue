<template>
    <div>
        <!-- ============================================================== -->
        <!-- Bread crumb and right sidebar toggle -->
        <!-- ============================================================== -->
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">Producto</h3>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0)">Inicio</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0)">Citas</a>
                    </li>
                    <li class="breadcrumb-item active">Recomendaciones por Producto</li>
                </ol>
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
                        <h4 class="card-title text-themecolor"><i class="fa fa-filter"></i> Filtros de búsqueda</h4>
                        <form action="#">
                            <div class="form-body">
                                <div class="row p-t-2">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="control-label">Sede</label>
                                            <select v-model="filtro.sede_id" class="form-control"
                                                @change="filtro.servicio_cita_id = ''; filtrarServicios(); cargarProductos(); buscar()">
                                                <option value="">Todas</option>
                                                <option v-for="s in sedes" :key="s.id" :value="s.id">{{ s.nom_sede }}</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="control-label">Servicio</label>
                                            <select v-model="filtro.servicio_cita_id" class="form-control"
                                                @change="cargarProductos(); buscar()">
                                                <option value="">Todos</option>
                                                <option v-for="s in serviciosFiltrados" :key="s.id" :value="s.id">{{ s.nombre }}</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group" id="filter-producto-parent">
                                            <label class="control-label">Producto</label>
                                            <Select2 :value="filtro.producto_id" :options="filterProductoOptions"
                                                width="100%" :allow-clear="true"
                                                :dropdown-parent-selector="'#filter-producto-parent'"
                                                @input="onFilterProductoSelect" />
                                        </div>
                                    </div>
                                    <div class="col-md-3 d-flex align-items-end">
                                        <button type="button" class="btn btn-success m-r-5" @click="buscar()" :disabled="loadingBuscar">
                                            <i class="fa" :class="loadingBuscar ? 'fa-spinner fa-spin' : 'fa-search'"></i>
                                            Buscar
                                        </button>
                                        <button type="button" class="btn btn-inverse" @click="btnLimpiar()">
                                            <i class="fa fa-refresh"></i> Limpiar
                                        </button>
                                        <button type="button" class="btn btn-primary m-l-5" @click="mostrarRegistro()">
                                            <i class="fa fa-plus"></i> Nuevo
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
                        <h4 class="card-title">Listado de recomendaciones</h4>
                        <h6 class="card-subtitle text-info">
                            Texto enviado al paciente junto con la confirmación de su cita.
                        </h6>
                        <div class="table-responsive">
                            <table id="example23" ref="dt" class="table table-bordered table-striped" cellspacing="0" width="100%">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th>Recomendación</th>
                                        <th>Estado</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in registros" :key="item.id">
                                        <td>
                                            <strong>{{ item.producto?.nom_produc || '—' }}</strong><br>
                                            <small class="text-muted">{{ item.producto?.cod_cups }}</small>
                                        </td>
                                        <td>
                                            <div style="max-width: 500px; white-space: pre-wrap;">{{ item.recomendacion || '—' }}</div>
                                        </td>
                                        <td>
                                            <span class="badge" :class="item.estado ? 'badge-success' : 'badge-secondary'">
                                                {{ item.estado ? 'Activo' : 'Inactivo' }}
                                            </span>
                                        </td>
                                        <td class="text-nowrap">
                                            <button type="button" class="btn waves-effect waves-light btn-rounded btn-outline-warning btn-sm m-r-5" @click="mostrarRegistro(item)" title="Editar">
                                                <i class="fa fa-pencil"></i>
                                            </button>
                                            <button type="button" class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm" @click="eliminarRegistro(item.id)" :disabled="loadingEliminarId === item.id" title="Eliminar">
                                                <i class="fa" :class="loadingEliminarId === item.id ? 'fa-spinner fa-spin' : 'fa-trash'"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="registros.length === 0">
                                        <td colspan="4" class="text-center text-muted">No hay recomendaciones registradas</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- ============================================================== -->
        <!-- End Page Content -->
        <!-- ============================================================== -->

        <!-- ============================================================== -->
        <!-- Right sidebar -->
        <!-- ============================================================== -->
        <div class="right-sidebar" id="right-sidebar"
            style="overflow-x: hidden; overflow-y: scroll; height: 100%;">
            <div class="slimscrollright">
                <div class="rpanel-title">
                    {{ editando ? 'Editar recomendación' : 'Nueva recomendación' }}
                    <span>
                        <i class="ti-close right-side-toggle" id="btnCerralModalForm" @click="cerrarModal()"></i>
                    </span>
                </div>
                <div class="r-panel-body">
                    <form action="#">
                        <div class="form-body">
                            <div class="form-group" id="form-producto-parent">
                                <label class="control-label">Producto *</label>
                                <Select2 :value="form.producto_id" :options="formProductoOptions"
                                    width="100%" :allow-clear="false" :disabled="editando"
                                    :dropdown-parent-selector="'#form-producto-parent'"
                                    @input="onFormProductoSelect" />
                                <span class="text-danger" v-if="errores.producto_id">{{ errores.producto_id[0] }}</span>
                            </div>
                            <div class="form-group">
                                <label class="control-label">Recomendación</label>
                                <textarea v-model="form.recomendacion" class="form-control" rows="8"
                                    placeholder="Texto de recomendación para el paciente. Use saltos de línea si es necesario."></textarea>
                                <small class="text-muted">Variables disponibles: <code>${num_docu_paciente}</code> <code>${nom_paciente}</code> <code>${cod_cups}</code> <code>${nom_produc}</code></small>
                            </div>
                            <div class="form-group">
                                <label class="control-label">Estado</label>
                                <select v-model="form.estado" class="form-control">
                                    <option :value="true">Activo</option>
                                    <option :value="false">Inactivo</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-actions mt-3">
                            <button type="button" class="btn btn-inverse" @click="cerrarModal()">Cancelar</button>
                            <button type="button" class="btn btn-success m-l-5" @click="guardar()" :disabled="loadingGuardar">
                                <i class="fa" :class="loadingGuardar ? 'fa-spinner fa-spin' : 'fa-check'"></i>
                                {{ loadingGuardar ? 'Guardando...' : 'Guardar' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { safeDestroyDataTable, safeInitDataTable } from "@/helpers/datatables.js";
import { handleAxiosErrorToast, alertSuccess, alertError } from "@/helpers/alerts";

export default {
    name: "RecomendacionProductoComponent",
    computed: {
        filterProductoOptions() {
            return (this.productos || []).map(p => ({
                id: String(p.id),
                cod_cups: p.cod_cups,
                nom_produc: p.nom_produc,
            }));
        },
        formProductoOptions() {
            return (this.productos || []).map(p => ({
                id: String(p.id),
                cod_cups: p.cod_cups,
                nom_produc: p.nom_produc,
            }));
        },
    },
    mounted() {
        this.cargarSedes();
        this.cargarServicios();
        this.cargarProductos();
        this.buscar();
    },
    beforeUnmount() {
        safeDestroyDataTable(this.$refs.dt || "#example23");
    },
    data() {
        return {
            registros: [],
            productos: [],
            sedes: [],
            servicios: [],
            serviciosFiltrados: [],
            filtro: {
                sede_id: '',
                servicio_cita_id: '',
                producto_id: '',
            },
            form: {
                id: null,
                producto_id: '',
                recomendacion: '',
                estado: true,
            },
            errores: {},
            editando: false,
            loadingBuscar: false,
            loadingGuardar: false,
            loadingEliminarId: null,
        };
    },
    methods: {
        async cargarSedes() {
            try {
                const res = await axios.get('/api/config-sedes/listar/sedes/activas');
                this.sedes = res.data || [];
            } catch (error) {
                handleAxiosErrorToast(error, "No fue posible cargar las sedes.");
            }
        },
        async cargarServicios() {
            try {
                const res = await axios.get('/api/citas/servicios');
                this.servicios = res.data || [];
                this.filtrarServicios();
            } catch (error) {
                handleAxiosErrorToast(error, "No fue posible cargar los servicios.");
            }
        },
        filtrarServicios() {
            if (this.filtro.sede_id) {
                this.serviciosFiltrados = this.servicios.filter(s => s.sede_id == this.filtro.sede_id);
            } else {
                this.serviciosFiltrados = this.servicios;
            }
        },
        async cargarProductos() {
            try {
                const servicioId = this.filtro.servicio_cita_id;
                const sedeId = this.filtro.sede_id;

                if (!servicioId) {
                    this.productos = [];
                    return;
                }

                var url = '/api/citas/productos/' + servicioId;
                if (sedeId) url += '?sede_id=' + sedeId;

                const res = await axios.get(url);
                this.productos = res.data || [];
            } catch (error) {
                this.productos = [];
                handleAxiosErrorToast(error, "No fue posible cargar los productos.");
            }
        },
        onFilterProductoSelect(value) {
            var id = Array.isArray(value) && value.length > 0 ? (value[0] && value[0].id) : (value && value.id);
            this.filtro.producto_id = id || '';
            this.buscar();
        },
        onFormProductoSelect(value) {
            var id = Array.isArray(value) && value.length > 0 ? (value[0] && value[0].id) : (value && value.id);
            this.form.producto_id = id || '';
        },
        async buscar() {
            if (this.loadingBuscar) return;
            this.loadingBuscar = true;
            try {
                const params = {};
                if (this.filtro.producto_id) params.producto_id = this.filtro.producto_id;
                const res = await axios.get('/api/config-recomendacion-productos', { params });
                safeDestroyDataTable(this.$refs.dt || "#example23");
                this.registros = res.data || [];
                this.$nextTick(() => {
                    safeInitDataTable(this.$refs.dt || "#example23", {
                        dom: "Bfrtip",
                        buttons: ["copy", "csv", "excel", "pdf", "print"],
                    });
                });
            } catch (error) {
                handleAxiosErrorToast(error, "No fue posible listar las recomendaciones.");
                this.registros = [];
            } finally {
                this.loadingBuscar = false;
            }
        },
        async guardar() {
            if (this.loadingGuardar) return;
            this.loadingGuardar = true;
            this.errores = {};
            try {
                if (!this.form.producto_id) {
                    alertError('Advertencia', 'Debe seleccionar un producto.');
                    return;
                }
                let res;
                if (this.editando && this.form.id) {
                    res = await axios.put('/api/config-recomendacion-productos/' + this.form.id, {
                        recomendacion: this.form.recomendacion,
                        estado: this.form.estado,
                    });
                } else {
                    res = await axios.post('/api/config-recomendacion-productos', {
                        producto_id: this.form.producto_id,
                        recomendacion: this.form.recomendacion,
                        estado: this.form.estado,
                    });
                }
                alertSuccess('Éxito', res?.data?.message || 'Recomendación guardada.');
                this.cerrarModal();
                await this.buscar();
            } catch (error) {
                this.errores = error?.response?.data?.errors || {};
                handleAxiosErrorToast(error, "No fue posible guardar la recomendación.");
            } finally {
                this.loadingGuardar = false;
            }
        },
        async eliminarRegistro(id) {
            if (!confirm('¿Está seguro de eliminar esta recomendación?')) return;
            if (this.loadingEliminarId) return;
            this.loadingEliminarId = id;
            try {
                const res = await axios.delete('/api/config-recomendacion-productos/' + id);
                alertSuccess('Éxito', res?.data?.message || 'Recomendación eliminada.');
                await this.buscar();
            } catch (error) {
                handleAxiosErrorToast(error, "No fue posible eliminar la recomendación.");
            } finally {
                this.loadingEliminarId = null;
            }
        },
        mostrarRegistro(item) {
            this.errores = {};
            if (item) {
                this.editando = true;
                this.form = {
                    id: item.id,
                    producto_id: item.producto_id,
                    recomendacion: item.recomendacion || '',
                    estado: item.estado,
                };
            } else {
                this.editando = false;
                this.form = {
                    id: null,
                    producto_id: '',
                    recomendacion: '',
                    estado: true,
                };
            }
            this.abrirModal();
        },
        abrirModal() {
            $(".right-sidebar").addClass("shw-rside");
        },
        cerrarModal() {
            $(".right-sidebar").removeClass("shw-rside");
        },
        btnLimpiar() {
            this.filtro = { sede_id: '', servicio_cita_id: '', producto_id: '' };
            this.serviciosFiltrados = this.servicios;
            this.cargarProductos();
            this.buscar();
        },
    },
};
</script>
<style scoped>
.right-sidebar {
    position: fixed;
    right: -80%;
    width: 80%;
    display: none;
    z-index: 180;
    background: #ffffff;
    top: 0px;
    padding-bottom: 20px;
    height: 80%;
    -webkit-box-shadow: 5px 1px 40px rgba(0, 0, 0, 0.1);
    box-shadow: 5px 1px 40px rgba(0, 0, 0, 0.1);
    -webkit-transition: all 0.3s ease;
    -o-transition: all 0.3s ease;
    transition: all 0.3s ease;
}

.shw-rside {
    right: 0px;
    width: 80%;
    display: block;
}
</style>

<template>
    <div>
        <!-- ============================================================== -->
        <!-- Bread crumb and right sidebar toggle -->
        <!-- ============================================================== -->
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
                    <li class="breadcrumb-item active">Consentimiento informado</li>
                </ol>
            </div>
            <div class="col-md-7 align-self-center text-right d-none d-md-block">
                <button type="button" class="btn btn-info btnDesplegarRigthSidebar"
                    @click="actualizar = false; mostrarRegistro()">
                    <i class="fa fa-plus-circle m-r-5"></i> Nuevo registro
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
                        <h4 class="card-title">Listado de consentimiento informado</h4>
                        <h6 class="card-subtitle">Administración de los consentimiento informado.</h6>
                        <div class="table-responsive m-t-40">
                            <table id="example23" ref="dt"
                                class="display nowrap table table-hover table-striped table-bordered" cellspacing="0"
                                width="100%">
                                <thead>
                                    <tr>
                                        <th width="150px">Producto</th>
                                        <th>Sede</th>
                                        <th>Nombre</th>
                                        <th>Archivo</th>
                                        <th>Fecha creación</th>
                                        <th>Fecha modificación</th>
                                        <th>Estado</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in registros" :key="item.id">
                                        <td>{{ item.producto?.nom_produc || '—' }}</td>
                                        <td>{{ item.sede.nom_sede }}</td>
                                        <td>{{ item.nombre }}</td>
                                        <td>
                                            <a v-if="item.archivo" :href="archivoUrl(item)" target="_blank">Ver PDF</a>
                                            <span v-else class="text-muted">—</span>
                                        </td>
                                        <td>{{ item.created_at }}</td>
                                        <td>{{ item.updated_at }}</td>
                                        <td>
                                            <span class="label label-success" v-if="item.estado">Activo</span>
                                            <span class="label label-danger" v-if="!item.estado">Inactivo</span>
                                        </td>
                                        <td class="text-nowrap">
                                            <button type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-warning btn-sm m-r-5"
                                                @click="actualizar = true; mostrarRegistro(item)">
                                                <i class="fa fa-pencil"></i>
                                            </button>
                                            <button type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm"
                                                @click="elimnarRegistro(item.id, item.nombre)">
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
        <!-- ============================================================== -->
        <!-- End PAge Content -->
        <!-- ============================================================== -->

        <!-- ============================================================== -->
        <!-- Right sidebar -->
        <!-- ============================================================== -->
        <!-- .right-sidebar -->
        <div class="right-sidebar">
            <div class="slimscrollright">
                <div class="rpanel-title">
                    {{ tituloModal }}
                    <span>
                        <i class="ti-close right-side-toggle" id="btnCerralModalForm"></i>
                    </span>
                </div>
                <div class="r-panel-body">
                    <!-- ============================================================== -->
                    <!-- Aqui va el contenido de los formularios -->
                    <!-- ============================================================== -->
                    <form action="#">
                        <div class="form-body">
                            <div class="row p-t-20">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="control-label">Producto</label>
                                        <Select2 :value="productoSelecciondo" :options="productoOptions" width="100%"
                                            :allow-clear="true" :dropdown-parent-selector="'#productos-parent'"
                                            @input="onProductoSelect" />
                                        <span class="text-danger" v-if="errores.producto_id">{{
                                            errores.producto_id[0]
                                            }}</span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="control-label">Nombre de consentimiento informado</label>
                                        <input type="text" id="nombre" name="nombre" v-model="registro.nombre"
                                            class="form-control" />
                                        <span class="text-danger" v-if="errores.nombre">
                                            {{ errores.nombre[0] }}
                                        </span>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <input type="file" class="form-control" id="exampleInputFile1"
                                        aria-describedby="fileHelp" @change='obtenerArchivo' />
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <div class="checkbox checkbox-success">
                                            <input type="checkbox" id="estado" name="estado" v-model="registro.estado"
                                                :checked="registro.estado" value="1">
                                            <label for="estado"> Activo </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-actions mt-3">
                                <button type="button" class="btn btn-success m-r-5" @click="guardarRegistro()"
                                    v-if="!actualizar">
                                    <i class="fa fa-check"></i> Guardar
                                </button>

                                <button type="button" class="btn btn-warning m-r-5" v-if="actualizar"
                                    @click="guardarRegistro()">
                                    <i class="fa fa-pencil"></i> Actualizar
                                </button>

                                <button type="button" class="btn btn-inverse"
                                    @click="btnCerralModalForm()">Cancel</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- ============================================================== -->
        <!-- End Right sidebar -->
        <!-- ============================================================== -->
    </div>
</template>

<script>
import { safeDestroyDataTable, safeInitDataTable } from "@/helpers/datatables.js";
import { alertError, alertSuccess, confirmAction, handleAxiosErrorToast } from "@/helpers/alerts.js";

export default {
    mounted() {
        this.ListarDatos();
        this.listarProductos();
    },
    beforeUnmount() {
        safeDestroyDataTable(this.$refs.dt || "#example23");
    },
    data() {
        return {
            id: 0,
            registros: [],
            registro: { producto_id: null, sede_id: sessionStorage.getItem('ST-sede'), nombre: '', estado: true },
            productoSelecciondo: null,
            archivoFile: null,
            tituloModal: 'Nuevo registro',
            actualizar: false,
            errores: {},
            productos: [],
            loadingListar: false,
            loadingGuardar: false,
            loadingEliminarId: null,
        };
    },
    computed: {
        productoOptions() {
            // Opciones para el Select2 local de productos.
            return (this.productos || []).map(p => ({ id: String(p.id), cod_cups: p.cod_cups, nom_produc: p.nom_produc }));
        },
    },
    methods: {
        async ListarDatos() {
            if (this.loadingListar) return;
            this.loadingListar = true;
            try {
                const res = await axios.get("/api/config-consentimiento-informado");
                safeDestroyDataTable(this.$refs.dt || "#example23");
                this.registros = res?.data?.data || res?.data || [];
                this.$nextTick(() => {
                    safeInitDataTable(this.$refs.dt || "#example23", {
                        dom: 'Bfrtip',
                        buttons: ['copy', 'csv', 'excel', 'pdf', 'print']
                    });
                });
            } catch (error) {
                handleAxiosErrorToast(error, "No fue posible listar consentimientos informados.");
                this.registros = [];
            } finally {
                this.loadingListar = false;
            }
        },
        async guardarRegistro() {
            if (this.loadingGuardar) return;
            this.loadingGuardar = true;
            try {
                const sedeId = sessionStorage.getItem('ST-sede');
                const formData = new FormData();
                formData.append('producto_id', String(this.registro.producto_id ?? ''));
                formData.append('sede_id', String(this.registro.sede_id ?? sedeId ?? ''));
                formData.append('nombre', String(this.registro.nombre ?? ''));
                formData.append('estado', this.registro.estado ? '1' : '0');
                if (this.archivoFile) formData.append('archivo', this.archivoFile);

                let res;
                if (!this.actualizar) {
                    res = await axios.post('/api/config-consentimiento-informado', formData, {
                        headers: { "Content-Type": "multipart/form-data" }
                    });
                } else {
                    formData.append('_method', 'PUT');
                    res = await axios.post('/api/config-consentimiento-informado/' + this.id, formData, {
                        headers: { "Content-Type": "multipart/form-data" }
                    });
                }

                if (res.status === 200 || res.status === 201) {
                    $.toast({
                        heading: 'Ok!!!',
                        text: res.data.message || 'Guardado correctamente.',
                        position: 'top-right',
                        loaderBg: '#ff6849',
                        icon: 'success',
                        hideAfter: 3500,
                        stack: 6
                    });
                    this.ListarDatos();
                    this.limpiarFormulario();
                    this.btnCerralModalForm();
                }
            } catch (error) {
                this.errores = error?.response?.data?.errors || {};
                handleAxiosErrorToast(error, "No fue posible guardar el consentimiento informado.");
            } finally {
                this.loadingGuardar = false;
            }
        },
        async elimnarRegistro(id, nom) {
            const ok = await confirmAction({
                title: `Estas seguro de eliminar el consentimiento informado ${nom}?`,
                text: "No podrás recuperar este registro!",
                icon: "warning",
            });
            if (!ok) return;

            try {
                if (this.loadingEliminarId) return;
                this.loadingEliminarId = id;
                const response = await axios.delete('/api/config-consentimiento-informado/' + id);
                alertSuccess("Ok!", response?.data?.message || "Registro eliminado.", "success");
                this.ListarDatos();
            } catch (error) {
                handleAxiosErrorToast(error, "No fue posible eliminar el registro.");
                alertError("Error", "No fue posible eliminar el registro.", "error");
            } finally {
                this.loadingEliminarId = null;
            }
        },
        async listarProductos() {
            try {
                const res = await axios.get("/api/config-productos");
                this.productos = res.data;
            } catch (error) {
                handleAxiosErrorToast(error, "No fue posible listar productos.");
            }
        },
        obtenerArchivo(e) {
            this.archivoFile = e.target.files?.[0] || null;
        },
        onProductoSelect(value) {
            let selectedId = null;
            if (Array.isArray(value) && value.length > 0) {
                const v = value[0];
                selectedId = String(typeof v === 'object' && v && v.id != null ? v.id : v);
            } else if (value && typeof value === 'object' && value.id != null) {
                selectedId = String(value.id);
            } else if (value != null) {
                selectedId = String(value);
            }
            this.registro.producto_id = selectedId ? Number(selectedId) : null;
            this.productoSelecciondo = selectedId;
        },
        archivoUrl(item) {
            if (!item?.archivo) return null;
            return `/storage/${item.archivo}`;
        },
        mostrarRegistro(data = {}) {
            if (this.actualizar == true) {
                this.tituloModal = "Actualizar el registro: " + (data.nombre || "");
                this.id = data.id;
                this.registro.producto_id = data.producto_id;
                this.productoSelecciondo = String(data.producto_id ?? '');
                this.registro.sede_id = data.sede_id;
                this.registro.nombre = data.nombre;
                this.registro.estado = !!data.estado;
                this.archivoFile = null;
            } else {
                this.actualizar = false;
                this.tituloModal = "Nuevo registro";
                this.id = 0;
                this.limpiarFormulario();
            }

            this.btnCerralModalForm();
        },
        limpiarFormulario() {
            this.actualizar = false;
            this.tituloModal = "Nuevo registro";
            this.id = 0;
            this.errores = {};
            this.registro.producto_id = null;
            this.registro.sede_id = sessionStorage.getItem('ST-sede');
            this.registro.nombre = "";
            this.registro.estado = true;
            this.productoSelecciondo = null;
            this.archivoFile = null;
        },
        btnCerralModalForm() {
            $(".right-sidebar").slideDown(50);
            $(".right-sidebar").toggleClass("shw-rside");
        }
    },
};
</script>

<style scoped>
.right-sidebar {
    position: fixed;
    right: -25%;
    width: 25%;
    display: none;
    z-index: 1100;
    background: #ffffff;
    top: 0px;
    padding-bottom: 20px;
    height: 100%;
    -webkit-box-shadow: 5px 1px 40px rgba(0, 0, 0, 0.1);
    box-shadow: 5px 1px 40px rgba(0, 0, 0, 0.1);
    -webkit-transition: all 0.3s ease;
    -o-transition: all 0.3s ease;
    transition: all 0.3s ease;
}

.shw-rside {
    right: 0px;
    width: 25%;
    display: block;
}
</style>
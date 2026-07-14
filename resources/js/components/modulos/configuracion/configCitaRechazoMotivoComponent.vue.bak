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
                    <li class="breadcrumb-item active">Motivos de Rechazo (Citas)</li>
                </ol>
            </div>
            <div class="col-md-7 align-self-center text-right d-none d-md-block">
                <button type="button" class="btn btn-info btnDesplegarRigthSidebar"
                    @click="actualizar = false; mostrarRegistro()">
                    <i class="fa fa-plus-circle m-r-5"></i> Nuevo registro
                </button>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Motivos de Rechazo</h4>
                        <h6 class="card-subtitle">Administración de motivos de rechazo para las citas agendadas.</h6>

                        <div v-if="loadingListar" class="text-center m-t-40">
                            <i class="fa fa-spinner fa-spin fa-2x"></i>
                            <p class="text-muted m-t-10">Cargando motivos...</p>
                        </div>

                        <div v-else-if="errorMotivos" class="alert alert-danger m-t-20">
                            <i class="fa fa-exclamation-triangle"></i>
                            {{ errorMotivos }}
                        </div>

                        <div v-else-if="registros.length === 0" class="text-center m-t-40">
                            <p class="text-muted">No hay motivos de rechazo configurados. Cree un nuevo registro para comenzar.</p>
                        </div>

                        <div class="table-responsive m-t-40" v-else>
                            <table id="tblRechazoMotivos" ref="dt"
                                class="display nowrap table table-hover table-striped table-bordered" cellspacing="0"
                                width="100%">
                                <thead>
                                    <tr>
                                        <th>Motivo</th>
                                        <th>Estado</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in registros" :key="item.id">
                                        <td>{{ item.motivo }}</td>
                                        <td>
                                            <span class="label label-success" v-if="item.activo">Activo</span>
                                            <span class="label label-danger" v-if="!item.activo">Inactivo</span>
                                        </td>
                                        <td class="text-nowrap">
                                            <button type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-warning btn-sm m-r-5"
                                                @click="actualizar = true; mostrarRegistro(item)">
                                                <i class="fa fa-pencil"></i>
                                            </button>
                                            <button type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm"
                                                @click="eliminarRegistro(item.id)"
                                                :disabled="loadingEliminarId === item.id">
                                                <i class="fa"
                                                    :class="loadingEliminarId === item.id ? 'fa-spinner fa-spin' : 'fa-trash'"></i>
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

        <div class="right-sidebar" style="overflow-x: hidden; overflow-y: scroll; height: 100%;">
            <div class="slimscrollright">
                <div class="rpanel-title">
                    {{ tituloModal }}
                    <span>
                        <i class="ti-close right-side-toggle" id="btnCerrarModalForm"></i>
                    </span>
                </div>
                <div class="r-panel-body">
                    <form action="#">
                        <div class="form-body">
                            <div class="row p-t-20">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="control-label">Motivo <span class="text-danger">*</span></label>
                                        <input type="text" v-model="registro.motivo" class="form-control"
                                            :class="{ 'is-invalid': errores.motivo }"
                                            placeholder="Ingrese el motivo de rechazo">
                                        <span class="text-danger" v-if="errores.motivo">{{ errores.motivo[0] }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <div class="checkbox checkbox-success">
                                            <input type="checkbox" id="activo" v-model="registro.activo"
                                                :true-value="1" :false-value="0">
                                            <label for="activo"> Activo </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-actions mt-3">
                                <button type="button" class="btn btn-success m-r-5" @click="guardarRegistro()"
                                    v-if="!actualizar" :disabled="loadingGuardar">
                                    <i class="fa" :class="loadingGuardar ? 'fa-spinner fa-spin' : 'fa-check'"></i>
                                    Guardar
                                </button>
                                <button type="button" class="btn btn-warning m-r-5" v-if="actualizar"
                                    @click="guardarRegistro()" :disabled="loadingGuardar">
                                    <i class="fa" :class="loadingGuardar ? 'fa-spinner fa-spin' : 'fa-pencil'"></i>
                                    Actualizar
                                </button>
                                <button type="button" class="btn btn-inverse" @click="cerrarFormSidebar()">Cancel</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { safeDestroyDataTable, safeInitDataTable } from "@/helpers/datatables.js";
import { alertError, alertSuccess, confirmAction, handleAxiosErrorToast } from "@/helpers/alerts.js";

export default {
    mounted() {
        this.listarMotivos();
    },
    beforeUnmount() {
        safeDestroyDataTable(this.$refs.dt || "#tblRechazoMotivos");
    },
    data() {
        return {
            id: 0,
            registros: [],
            registro: { motivo: '', activo: 1 },
            tituloModal: 'Nuevo registro',
            actualizar: false,
            errores: {},
            loadingListar: false,
            errorMotivos: '',
            loadingGuardar: false,
            loadingEliminarId: null,
        };
    },
    methods: {
        async listarMotivos() {
            if (this.loadingListar) return;
            this.loadingListar = true;
            this.errorMotivos = '';
            try {
                const res = await axios.get("/api/citas-rechazo-motivos");
                safeDestroyDataTable(this.$refs.dt || "#tblRechazoMotivos");
                this.registros = res.data;
                this.$nextTick(() => {
                    safeInitDataTable(this.$refs.dt || "#tblRechazoMotivos", {
                        dom: 'Bfrtip',
                        buttons: ['copy', 'csv', 'excel', 'pdf', 'print']
                    });
                });
            } catch (error) {
                this.errorMotivos = error?.response?.data?.message || "No fue posible cargar los motivos de rechazo.";
                handleAxiosErrorToast(error, "No fue posible listar motivos.");
                this.registros = [];
            } finally {
                this.loadingListar = false;
            }
        },
        async guardarRegistro() {
            if (this.loadingGuardar) return;
            this.errores = {};
            if (!this.registro.motivo || !this.registro.motivo.trim()) {
                this.errores = { motivo: ['El motivo es obligatorio.'] };
                return;
            }
            this.loadingGuardar = true;
            try {
                const payload = { motivo: this.registro.motivo, activo: this.registro.activo === 1 || this.registro.activo === true };
                if (this.actualizar === false) {
                    const res = await axios.post('/api/citas-rechazo-motivos', payload);
                    if (res.status == 201 || res.status == 200) {
                        this.listarMotivos();
                        $.toast({
                            heading: 'Ok!!!',
                            text: 'Motivo guardado correctamente.',
                            position: 'top-right',
                            loaderBg: '#ff6849',
                            icon: 'success',
                            hideAfter: 3500,
                            stack: 6
                        });
                        this.cerrarFormSidebar();
                        this.limpiar();
                    }
                } else {
                    const res = await axios.put('/api/citas-rechazo-motivos/' + this.id, payload);
                    if (res.status == 200) {
                        this.listarMotivos();
                        $.toast({
                            heading: 'Ok!!!',
                            text: 'Motivo actualizado correctamente.',
                            position: 'top-right',
                            loaderBg: '#ff6849',
                            icon: 'success',
                            hideAfter: 3500,
                            stack: 6
                        });
                        this.cerrarFormSidebar();
                        this.limpiar();
                    }
                }
            } catch (error) {
                this.errores = error?.response?.data?.errors || {};
                handleAxiosErrorToast(error, "No fue posible guardar el motivo.");
            } finally {
                this.loadingGuardar = false;
            }
        },
        async eliminarRegistro(id) {
            const ok = await confirmAction({
                title: "¿Estas seguro?",
                text: "No podrás recuperar este registro!",
                icon: "warning",
            });
            if (!ok) return;
            try {
                if (this.loadingEliminarId) return;
                this.loadingEliminarId = id;
                const response = await axios.delete('/api/citas-rechazo-motivos/' + id);
                alertSuccess("Ok!", response?.data?.message || "Registro eliminado.", "success");
                this.listarMotivos();
            } catch (error) {
                handleAxiosErrorToast(error, "No fue posible eliminar el registro.");
                alertError("Error", "No fue posible eliminar el registro.", "error");
            } finally {
                this.loadingEliminarId = null;
            }
        },
        mostrarRegistro(data = {}) {
            if (this.actualizar) {
                this.tituloModal = "Actualizar motivo: " + data.motivo;
                this.id = data.id;
                this.registro.motivo = data.motivo;
                this.registro.activo = data.activo ? 1 : 0;
            } else {
                this.tituloModal = "Nuevo motivo de rechazo";
                this.id = 0;
                this.registro.motivo = '';
                this.registro.activo = 1;
            }
            this.btnCerrarModalForm();
        },
        limpiar() {
            this.actualizar = false;
            this.tituloModal = "Nuevo motivo de rechazo";
            this.id = 0;
            this.registro.motivo = '';
            this.registro.activo = 1;
        },
        btnCerrarModalForm() {
            $(".right-sidebar").slideDown(50);
            $(".right-sidebar").addClass("shw-rside");
        },
        cerrarFormSidebar() {
            $(".right-sidebar").slideUp(50);
            $(".right-sidebar").removeClass("shw-rside");
        },
    },
};
</script>
<style scoped>
.right-sidebar {
    position: fixed;
    right: -50%;
    width: 50%;
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
    width: 50%;
    display: block;
}
</style>

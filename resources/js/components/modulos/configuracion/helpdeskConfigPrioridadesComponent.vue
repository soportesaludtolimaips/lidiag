<template>
    <div>
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">Configuración</h3>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="javascript:void(0)">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="javascript:void(0)">Configuración</a></li>
                    <li class="breadcrumb-item active">Helpdesk - Prioridades</li>
                </ol>
            </div>
            <div class="col-md-7 align-self-center text-right d-none d-md-block">
                <button
                    type="button"
                    class="btn btn-info btnDesplegarRigthSidebar"
                    @click="
                        actualizar = false;
                        mostrarRegistro();
                    "
                >
                    <i class="fa fa-plus-circle m-r-5"></i> Nuevo registro
                </button>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Listado de prioridades</h4>
                        <h6 class="card-subtitle">Catálogo de Helpdesk.</h6>
                        <div class="table-responsive m-t-40">
                            <table
                                id="example23"
                                ref="dt"
                                class="display nowrap table table-hover table-striped table-bordered"
                                cellspacing="0"
                                width="100%"
                            >
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Orden</th>
                                        <th>Estado</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in registros" :key="item.id">
                                        <td>{{ item.nombre }}</td>
                                        <td>{{ item.orden }}</td>
                                        <td>
                                            <span class="label label-success" v-if="item.activo"
                                                >Activo</span
                                            >
                                            <span class="label label-danger" v-else>Inactivo</span>
                                        </td>
                                        <td class="text-nowrap">
                                            <button
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-warning btn-sm m-r-5"
                                                @click="
                                                    actualizar = true;
                                                    mostrarRegistro(item);
                                                "
                                            >
                                                <i class="fa fa-pencil"></i>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm"
                                                @click="desactivarRegistro(item)"
                                                :disabled="loadingEliminarId === item.id"
                                            >
                                                <i
                                                    class="fa"
                                                    :class="
                                                        loadingEliminarId === item.id
                                                            ? 'fa-spinner fa-spin'
                                                            : 'fa-trash'
                                                    "
                                                ></i>
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

        <div class="right-sidebar" style="width: 400px">
            <div class="slimscrollright">
                <div class="rpanel-title">
                    {{ tituloModal }}
                    <span><i class="ti-close right-side-toggle" id="btnCerralModalForm"></i></span>
                </div>
                <div class="r-panel-body">
                    <form action="#" @submit.prevent>
                        <div class="form-body">
                            <div class="row p-t-20">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="control-label">Nombre</label>
                                        <input
                                            type="text"
                                            v-model="registro.nombre"
                                            class="form-control"
                                            maxlength="100"
                                        />
                                        <span class="text-danger" v-if="errores.nombre">{{
                                            errores.nombre[0]
                                        }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="control-label">Orden</label>
                                        <input
                                            type="number"
                                            v-model="registro.orden"
                                            class="form-control"
                                        />
                                        <span class="text-danger" v-if="errores.orden">{{
                                            errores.orden[0]
                                        }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <div class="checkbox checkbox-success">
                                            <input
                                                type="checkbox"
                                                id="activo"
                                                v-model="registro.activo"
                                                :checked="registro.activo"
                                                value="1"
                                            />
                                            <label for="activo"> Activo </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-actions mt-3">
                                <button
                                    type="button"
                                    class="btn btn-success m-r-5"
                                    v-if="!actualizar"
                                    @click="guardarRegistro()"
                                    :disabled="loadingGuardar"
                                >
                                    <i
                                        class="fa"
                                        :class="loadingGuardar ? 'fa-spinner fa-spin' : 'fa-check'"
                                    ></i>
                                    Guardar
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-warning m-r-5"
                                    v-if="actualizar"
                                    @click="guardarRegistro()"
                                    :disabled="loadingGuardar"
                                >
                                    <i
                                        class="fa"
                                        :class="loadingGuardar ? 'fa-spinner fa-spin' : 'fa-pencil'"
                                    ></i>
                                    Actualizar
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-inverse"
                                    @click="btnCerralModalForm()"
                                >
                                    Cancel
                                </button>
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
import { toastMessage, confirmAction, handleAxiosErrorToast } from "@/helpers/alerts.js";

export default {
    mounted() {
        this.listar();
    },
    beforeUnmount() {
        safeDestroyDataTable(this.$refs.dt || "#example23");
    },
    data() {
        return {
            registros: [],
            registro: { id: 0, nombre: "", orden: 0, activo: true },
            tituloModal: "Nuevo registro",
            actualizar: false,
            errores: {},
            loadingListar: false,
            loadingGuardar: false,
            loadingEliminarId: null,
        };
    },
    methods: {
        async listar() {
            if (this.loadingListar) return;
            this.loadingListar = true;
            try {
                const res = await axios.get("/api/helpdesk-prioridades");
                safeDestroyDataTable(this.$refs.dt || "#example23");
                this.registros = res.data || [];
                this.$nextTick(() => {
                    safeInitDataTable(this.$refs.dt || "#example23", {
                        dom: "Bfrtip",
                        buttons: ["copy", "csv", "excel", "pdf", "print"],
                    });
                });
            } catch (error) {
                handleAxiosErrorToast(error, "No se pudo listar.");
            } finally {
                this.loadingListar = false;
            }
        },
        mostrarRegistro(item = null) {
            this.errores = {};
            if (item) {
                this.tituloModal = "Actualizar registro";
                this.registro = {
                    id: item.id,
                    nombre: item.nombre,
                    orden: item.orden,
                    activo: !!item.activo,
                };
            } else {
                this.tituloModal = "Nuevo registro";
                this.registro = { id: 0, nombre: "", orden: 0, activo: true };
            }
            $(".right-side-toggle").click();
        },
        btnCerralModalForm() {
            $("#btnCerralModalForm").click();
        },
        async guardarRegistro() {
            if (this.loadingGuardar) return;
            this.loadingGuardar = true;
            this.errores = {};
            try {
                const payload = {
                    nombre: this.registro.nombre,
                    orden: this.registro.orden,
                    activo: this.registro.activo ? 1 : 0,
                };
                if (!this.actualizar) {
                    await axios.post("/api/helpdesk-prioridades", payload);
                    toastMessage({ heading: "Ok", text: "Prioridad creada.", icon: "success" });
                } else {
                    await axios.put(`/api/helpdesk-prioridades/${this.registro.id}`, payload);
                    toastMessage({
                        heading: "Ok",
                        text: "Prioridad actualizada.",
                        icon: "success",
                    });
                }
                this.btnCerralModalForm();
                await this.listar();
            } catch (error) {
                if (error?.response?.status === 422) {
                    this.errores = error.response.data.errors || {};
                } else {
                    handleAxiosErrorToast(error, "No se pudo guardar.");
                }
            } finally {
                this.loadingGuardar = false;
            }
        },
        async desactivarRegistro(item) {
            const ok = await confirmAction({
                title: "¿Desactivar?",
                text: "El registro quedará inactivo.",
            });
            if (!ok) return;
            this.loadingEliminarId = item.id;
            try {
                await axios.delete(`/api/helpdesk-prioridades/${item.id}`);
                toastMessage({ heading: "Ok", text: "Prioridad desactivada.", icon: "success" });
                await this.listar();
            } catch (error) {
                handleAxiosErrorToast(error, "No se pudo desactivar.");
            } finally {
                this.loadingEliminarId = null;
            }
        },
    },
};
</script>

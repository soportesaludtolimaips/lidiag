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
                    <li class="breadcrumb-item active">Productos</li>
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
                        <h4 class="card-title">Listado de productos</h4>
                        <h6 class="card-subtitle">
                            Administración de productos para la prestación del servicio de imagenes
                            diagnosticas.
                        </h6>
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
                                        <th>Cups</th>
                                        <th>Producto</th>
                                        <th>%</th>
                                        <th>Val. Produc</th>
                                        <th>Lectura</th>
                                        <th>Oral</th>
                                        <th>Venoso</th>
                                        <th>Estado</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in registros" :key="item.id">
                                        <td>{{ item.cod_cups }}</td>
                                        <td>{{ item.nom_produc }}</td>
                                        <td>{{ item.porcen }}</td>
                                        <td>{{ item.val_produc }}</td>
                                        <td>{{ item.requi_lectura }}</td>
                                        <td>{{ item.tipo_oral }}</td>
                                        <td>{{ item.tipo_venoso }}</td>
                                        <td>
                                            <span class="label label-success" v-if="item.estado"
                                                >Activo</span
                                            >
                                            <span class="label label-danger" v-if="!item.estado"
                                                >Inactivo</span
                                            >
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
                                                @click="elimnarRegistro(item.id)"
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
        <!-- ============================================================== -->
        <!-- End PAge Content -->
        <!-- ============================================================== -->

        <!-- ============================================================== -->
        <!-- Right sidebar -->
        <!-- ============================================================== -->
        <!-- .right-sidebar -->
        <div
            class="right-sidebar"
            style="overflow-x: hidden; overflow-y: scroll; overflow-x: hidden; height: 100%"
        >
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
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label">Cups</label>
                                        <input
                                            type="text"
                                            id="cod_cups"
                                            name="cod_cups"
                                            v-model="registro.cod_cups"
                                            class="form-control"
                                            placeholder="Código cups"
                                        />
                                        <span class="text-danger" v-if="errores.cod_cups">{{
                                            errores.cod_cups[0]
                                        }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="control-label">Producto</label>
                                        <input
                                            type="text"
                                            id="nom_produc"
                                            name="nom_produc"
                                            v-model="registro.nom_produc"
                                            class="form-control"
                                            placeholder="Ingrese aquí el nombre del producto"
                                        />
                                        <span class="text-danger" v-if="errores.nom_produc">{{
                                            errores.nom_produc[0]
                                        }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label">%</label>
                                        <input
                                            type="text"
                                            id="porcen"
                                            name="porcen"
                                            v-model="registro.porcen"
                                            class="form-control"
                                            placeholder="% de ganancia"
                                        />
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label">Precio</label>
                                        <input
                                            type="text"
                                            id="val_produc"
                                            name="val_produc"
                                            v-model="registro.val_produc"
                                            class="form-control"
                                            placeholder="Ingrese aquí el precio del producto"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <div class="checkbox checkbox-success">
                                            <input
                                                type="checkbox"
                                                id="requi_lectura"
                                                name="requi_lectura"
                                                v-model="registro.requi_lectura"
                                                :true-value="1"
                                                :false-value="0"
                                            />
                                            <label for="requi_lectura"> Requiere Lectura </label>
                                        </div>
                                        <div class="checkbox checkbox-success">
                                            <input
                                                type="checkbox"
                                                id="tipo_oral"
                                                name="tipo_oral"
                                                v-model="registro.tipo_oral"
                                                :true-value="1"
                                                :false-value="0"
                                            />
                                            <label for="tipo_oral"> Tipo Oral </label>
                                        </div>
                                        <div class="checkbox checkbox-success">
                                            <input
                                                type="checkbox"
                                                id="tipo_venoso"
                                                name="tipo_venoso"
                                                v-model="registro.tipo_venoso"
                                                :true-value="1"
                                                :false-value="0"
                                            />
                                            <label for="tipo_venoso"> Tipo Venoso </label>
                                        </div>
                                        <div class="checkbox checkbox-success">
                                            <input
                                                type="checkbox"
                                                id="factura"
                                                name="factura"
                                                v-model="registro.factura"
                                                :true-value="1"
                                                :false-value="0"
                                            />
                                            <label for="factura"> Genera factura </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="checkbox checkbox-success">
                                <input
                                    type="checkbox"
                                    id="estado"
                                    name="estado"
                                    v-model="registro.estado"
                                    :true-value="1"
                                    :false-value="0"
                                />
                                <label for="estado"> Activo </label>
                            </div>

                            <div class="form-actions mt-3">
                                <button
                                    type="button"
                                    class="btn btn-success m-r-5"
                                    @click="guardarRegistro()"
                                    v-if="!actualizar"
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
        <!-- ============================================================== -->
        <!-- End Right sidebar -->
        <!-- ============================================================== -->
    </div>
</template>
<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref } from "vue";

const dt = ref(null);

import { safeDestroyDataTable, safeInitDataTable } from "@/helpers/datatables.js";
import { alertError, alertSuccess, confirmAction } from "@/helpers/alerts.js";
import axios from "axios";

const id = ref(0);
const registros = ref([]);
const registro = ref({
    cod_cups: "",
    nom_produc: "",
    porcen: 0,
    val_produc: 0,
    requi_lectura: false,
    tipo_oral: false,
    tipo_venoso: false,
    factura: false,
    estado: true,
});
const tituloModal = ref("Nuevo registro");
const actualizar = ref(false);
const errores = ref({});
const loadingListar = ref(false);
const loadingGuardar = ref(false);
const loadingEliminarId = ref(null);

async function ListarDatos() {
    if (loadingListar.value) return;
    loadingListar.value = true;
    try {
        const res = await axios.get("/api/config-productos");
        safeDestroyDataTable(dt.value || "#example23");
        registros.value = res.data.data;
        nextTick(() => {
            safeInitDataTable(dt.value || "#example23", {
                dom: "Bfrtip",
                buttons: ["copy", "csv", "excel", "pdf", "print"],
            });
        });
    } catch (error) {
        registros.value = [];
    } finally {
        loadingListar.value = false;
    }
}
async function guardarRegistro() {
    if (loadingGuardar.value) return;
    loadingGuardar.value = true;
    try {
        if (actualizar.value === false) {
            const res = await axios.post("/api/config-productos", registro.value);

            if (res.status == 200) {
                ListarDatos();

                $.toast({
                    heading: "Ok!!!",
                    text: res.data.data.message,
                    position: "top-right",
                    loaderBg: "#ff6849",
                    icon: "success",
                    hideAfter: 3500,
                    stack: 6,
                });

                btnCerralModalForm();
                limpiar();
            }
        } else {
            const res = await axios.put("/api/config-productos/" + id.value, registro.value);
            if (res.status == 200) {
                ListarDatos();

                $.toast({
                    heading: "Ok!!!",
                    text: res.data.data.message,
                    position: "top-right",
                    loaderBg: "#ff6849",
                    icon: "success",
                    hideAfter: 3500,
                    stack: 6,
                });

                btnCerralModalForm();
                limpiar();
            }
        }
    } catch (error) {
        errores.value = error?.response?.data?.errors || {};
    } finally {
        loadingGuardar.value = false;
    }
}
async function elimnarRegistro(id) {
    const ok = await confirmAction({
        title: "¿Estas seguro?",
        text: "No podrás recuperar este registro!",
        icon: "warning",
    });
    if (!ok) return;

    try {
        if (loadingEliminarId.value) return;
        loadingEliminarId.value = id;
        const response = await axios.delete("/api/config-productos/" + id);
        alertSuccess("Ok!", response?.data?.message || "Registro eliminado.", "success");
        ListarDatos();
    } catch (error) {
        alertError("Error", "No fue posible eliminar el registro.", "error");
    } finally {
        loadingEliminarId.value = null;
    }
}
function mostrarRegistro(data = {}) {
    if (actualizar.value) {
        tituloModal.value = "Actualizar el registro: " + data.nom_produc;
        id.value = data.id;
        registro.value.cod_cups = data.cod_cups;
        registro.value.nom_produc = data.nom_produc;
        registro.value.porcen = data.porcen;
        registro.value.val_produc = data.val_produc;
        registro.value.requi_lectura = Boolean(data.requi_lectura);
        registro.value.tipo_oral = Boolean(data.tipo_oral);
        registro.value.tipo_venoso = Boolean(data.tipo_venoso);
        registro.value.factura = Boolean(data.factura);
    } else {
        actualizar.value = false;
        tituloModal.value = "Nuevo registro";
        id.value = 0;
        registro.value.cod_cups = "";
        registro.value.nom_produc = "";
        registro.value.porcen = 0;
        registro.value.val_produc = 0;
        registro.value.requi_lectura = false;
        registro.value.tipo_oral = false;
        registro.value.tipo_venoso = false;
        registro.value.factura = false;
    }

    btnCerralModalForm();
}
function limpiar() {
    actualizar.value = false;
    tituloModal.value = "Nuevo registro";
    id.value = 0;
    registro.value.cod_cups = "";
    registro.value.nom_produc = "";
    registro.value.porcen = 0;
    registro.value.val_produc = 0;
    registro.value.requi_lectura = false;
    registro.value.tipo_oral = false;
    registro.value.tipo_venoso = false;
    registro.value.factura = false;
}
function btnCerralModalForm() {
    $(".right-sidebar").slideDown(50);
    $(".right-sidebar").toggleClass("shw-rside");
}

onMounted(() => {
    ListarDatos();
});

onBeforeUnmount(() => {
    safeDestroyDataTable(dt.value || "#example23");
});
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

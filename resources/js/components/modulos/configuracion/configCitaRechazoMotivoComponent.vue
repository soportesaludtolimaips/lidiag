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
                        <h4 class="card-title">Motivos de Rechazo</h4>
                        <h6 class="card-subtitle">
                            Administración de motivos de rechazo para las citas agendadas.
                        </h6>

                        <div v-if="loadingListar" class="text-center m-t-40">
                            <i class="fa fa-spinner fa-spin fa-2x"></i>
                            <p class="text-muted m-t-10">Cargando motivos...</p>
                        </div>

                        <div v-else-if="errorMotivos" class="alert alert-danger m-t-20">
                            <i class="fa fa-exclamation-triangle"></i>
                            {{ errorMotivos }}
                        </div>

                        <div v-else-if="registros.length === 0" class="text-center m-t-40">
                            <p class="text-muted">
                                No hay motivos de rechazo configurados. Cree un nuevo registro para
                                comenzar.
                            </p>
                        </div>

                        <div class="table-responsive m-t-40" v-else>
                            <table
                                id="tblRechazoMotivos"
                                ref="dt"
                                class="display nowrap table table-hover table-striped table-bordered"
                                cellspacing="0"
                                width="100%"
                            >
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
                                            <span class="label label-success" v-if="item.activo"
                                                >Activo</span
                                            >
                                            <span class="label label-danger" v-if="!item.activo"
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
                                                @click="eliminarRegistro(item.id)"
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

        <div class="right-sidebar" style="overflow-x: hidden; overflow-y: scroll; height: 100%">
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
                                        <label class="control-label"
                                            >Motivo <span class="text-danger">*</span></label
                                        >
                                        <input
                                            type="text"
                                            v-model="registro.motivo"
                                            class="form-control"
                                            :class="{ 'is-invalid': errores.motivo }"
                                            placeholder="Ingrese el motivo de rechazo"
                                        />
                                        <span class="text-danger" v-if="errores.motivo">{{
                                            errores.motivo[0]
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
                                                :true-value="1"
                                                :false-value="0"
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
                                    @click="cerrarFormSidebar()"
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
<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref } from "vue";

const dt = ref(null);

import { safeDestroyDataTable, safeInitDataTable } from "@/helpers/datatables.js";
import { alertError, alertSuccess, confirmAction } from "@/helpers/alerts.js";
import axios from "axios";

const id = ref(0);
const registros = ref([]);
const registro = ref({ motivo: "", activo: 1 });
const tituloModal = ref("Nuevo registro");
const actualizar = ref(false);
const errores = ref({});
const loadingListar = ref(false);
const errorMotivos = ref("");
const loadingGuardar = ref(false);
const loadingEliminarId = ref(null);

/**
 * Obtiene la lista de motivos de rechazo de citas desde el servidor y la carga en la tabla.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
async function listarMotivos() {
    if (loadingListar.value) return;
    loadingListar.value = true;
    errorMotivos.value = "";
    try {
        const res = await axios.get("/api/citas-rechazo-motivos");
        safeDestroyDataTable(dt.value || "#tblRechazoMotivos");
        registros.value = res.data.data;
        nextTick(() => {
            safeInitDataTable(dt.value || "#tblRechazoMotivos", {
                dom: "Bfrtip",
                buttons: ["copy", "csv", "excel", "pdf", "print"],
            });
        });
    } catch (error) {
        errorMotivos.value =
            error?.response?.data?.message || "No fue posible cargar los motivos de rechazo.";
        registros.value = [];
    } finally {
        loadingListar.value = false;
    }
}
/**
 * Crea o actualiza un motivo de rechazo de citas según el estado de edición del formulario.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
async function guardarRegistro() {
    if (loadingGuardar.value) return;
    errores.value = {};
    if (!registro.value.motivo || !registro.value.motivo.trim()) {
        errores.value = { motivo: ["El motivo es obligatorio."] };
        return;
    }
    loadingGuardar.value = true;
    try {
        const payload = {
            motivo: registro.value.motivo,
            activo: registro.value.activo === 1 || registro.value.activo === true,
        };
        if (actualizar.value === false) {
            const res = await axios.post("/api/citas-rechazo-motivos", payload);
            if (res.status == 201 || res.status == 200) {
                listarMotivos();
                $.toast({
                    heading: "Ok!!!",
                    text: "Motivo guardado correctamente.",
                    position: "top-right",
                    loaderBg: "#ff6849",
                    icon: "success",
                    hideAfter: 3500,
                    stack: 6,
                });
                cerrarFormSidebar();
                limpiar();
            }
        } else {
            const res = await axios.put("/api/citas-rechazo-motivos/" + id.value, payload);
            if (res.status == 200) {
                listarMotivos();
                $.toast({
                    heading: "Ok!!!",
                    text: "Motivo actualizado correctamente.",
                    position: "top-right",
                    loaderBg: "#ff6849",
                    icon: "success",
                    hideAfter: 3500,
                    stack: 6,
                });
                cerrarFormSidebar();
                limpiar();
            }
        }
    } catch (error) {
        errores.value = error?.response?.data?.errors || {};
    } finally {
        loadingGuardar.value = false;
    }
}
/**
 * Elimina un motivo de rechazo de citas previa confirmación del usuario.
 *
 * @param {number} id Identificador del motivo de rechazo a eliminar.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
async function eliminarRegistro(id) {
    const ok = await confirmAction({
        title: "¿Estas seguro?",
        text: "No podrás recuperar este registro!",
        icon: "warning",
    });
    if (!ok) return;
    try {
        if (loadingEliminarId.value) return;
        loadingEliminarId.value = id;
        const response = await axios.delete("/api/citas-rechazo-motivos/" + id);
        alertSuccess("Ok!", response?.data?.message || "Registro eliminado.", "success");
        listarMotivos();
    } catch (error) {
        alertError("Error", "No fue posible eliminar el registro.", "error");
    } finally {
        loadingEliminarId.value = null;
    }
}
/**
 * Prepara el formulario lateral para crear o editar un motivo de rechazo de citas.
 *
 * @param {Object} data Datos del motivo de rechazo a editar (vacío para un nuevo registro).
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
function mostrarRegistro(data = {}) {
    if (actualizar.value) {
        tituloModal.value = "Actualizar motivo: " + data.motivo;
        id.value = data.id;
        registro.value.motivo = data.motivo;
        registro.value.activo = data.activo ? 1 : 0;
    } else {
        tituloModal.value = "Nuevo motivo de rechazo";
        id.value = 0;
        registro.value.motivo = "";
        registro.value.activo = 1;
    }
    btnCerrarModalForm();
}
/**
 * Reinicia los valores del formulario y el estado de edición a los valores iniciales.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
function limpiar() {
    actualizar.value = false;
    tituloModal.value = "Nuevo motivo de rechazo";
    id.value = 0;
    registro.value.motivo = "";
    registro.value.activo = 1;
}
/**
 * Muestra el panel lateral del formulario agregando la clase correspondiente.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
function btnCerrarModalForm() {
    $(".right-sidebar").slideDown(50);
    $(".right-sidebar").addClass("shw-rside");
}
/**
 * Oculta el panel lateral del formulario removiendo la clase correspondiente.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
function cerrarFormSidebar() {
    $(".right-sidebar").slideUp(50);
    $(".right-sidebar").removeClass("shw-rside");
}

/**
 * Hook de ciclo de vida que carga los motivos al montar el componente.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
onMounted(() => {
    listarMotivos();
});

/**
 * Hook de ciclo de vida que destruye la tabla de datos al desmontar el componente.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
onBeforeUnmount(() => {
    safeDestroyDataTable(dt.value || "#tblRechazoMotivos");
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

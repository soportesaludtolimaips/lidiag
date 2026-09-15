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
                        <h4 class="card-title">Listado de consentimiento informado</h4>
                        <h6 class="card-subtitle">
                            Administración de los consentimiento informado.
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
                                        <td>{{ item.producto?.nom_produc || "—" }}</td>
                                        <td>{{ item.sede.nom_sede }}</td>
                                        <td>{{ item.nombre }}</td>
                                        <td>
                                            <a
                                                v-if="item.archivo"
                                                :href="archivoUrl(item)"
                                                target="_blank"
                                                >Ver PDF</a
                                            >
                                            <span v-else class="text-muted">—</span>
                                        </td>
                                        <td>{{ item.created_at }}</td>
                                        <td>{{ item.updated_at }}</td>
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
                                                @click="elimnarRegistro(item.id, item.nombre)"
                                            >
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
                                        <Select2
                                            :value="productoSelecciondo"
                                            :options="productoOptions"
                                            width="100%"
                                            :allow-clear="true"
                                            @input="onProductoSelect"
                                        />
                                        <span class="text-danger" v-if="errores.producto_id">{{
                                            errores.producto_id[0]
                                        }}</span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="control-label"
                                            >Nombre de consentimiento informado</label
                                        >
                                        <input
                                            type="text"
                                            id="nombre"
                                            name="nombre"
                                            v-model="registro.nombre"
                                            class="form-control"
                                        />
                                        <span class="text-danger" v-if="errores.nombre">
                                            {{ errores.nombre[0] }}
                                        </span>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <input
                                        type="file"
                                        class="form-control"
                                        id="exampleInputFile1"
                                        aria-describedby="fileHelp"
                                        @change="obtenerArchivo"
                                    />
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
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
                                    </div>
                                </div>
                            </div>

                            <div class="form-actions mt-3">
                                <button
                                    type="button"
                                    class="btn btn-success m-r-5"
                                    @click="guardarRegistro()"
                                    v-if="!actualizar"
                                >
                                    <i class="fa fa-check"></i> Guardar
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-warning m-r-5"
                                    v-if="actualizar"
                                    @click="guardarRegistro()"
                                >
                                    <i class="fa fa-pencil"></i> Actualizar
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
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from "vue";

const dt = ref(null);

import { safeDestroyDataTable, safeInitDataTable } from "@/helpers/datatables.js";
import { confirmAction, alertSuccess, alertError, toastMessage } from "@/helpers/alerts.js";
import Select2 from "@/components/shared/Select2.vue";
import axios from "axios";

const id = ref(0);
const registros = ref([]);
const registro = ref({
    producto_id: null,
    sede_id: sessionStorage.getItem("ST-sede"),
    nombre: "",
    estado: true,
});
const productoSelecciondo = ref(null);
const archivoFile = ref(null);
const tituloModal = ref("Nuevo registro");
const actualizar = ref(false);
const errores = ref({});
const productos = ref([]);
const loadingListar = ref(false);
const loadingGuardar = ref(false);
const loadingEliminarId = ref(null);

/**
 * Genera las opciones para el selector de productos usado en el formulario.
 *
 * @returns {Array<Object>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
const productoOptions = computed(() => {
    // Opciones para el Select2 local de productos.
    return (productos.value || []).map((p) => ({
        id: String(p.id),
        cod_cups: p.cod_cups,
        nom_produc: p.nom_produc,
    }));
});

/**
 * Obtiene la lista de consentimientos informados desde el servidor y la carga en la tabla.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
async function ListarDatos() {
    if (loadingListar.value) return;
    loadingListar.value = true;
    try {
        const res = await axios.get("/api/config-consentimiento-informado");
        safeDestroyDataTable(dt.value || "#example23");
        registros.value = res?.data?.data || res?.data || [];
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
/**
 * Crea o actualiza un consentimiento informado enviando los datos y el archivo al servidor.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
async function guardarRegistro() {
    if (loadingGuardar.value) return;
    loadingGuardar.value = true;
    try {
        const sedeId = sessionStorage.getItem("ST-sede");
        const formData = new FormData();
        formData.append("producto_id", String(registro.value.producto_id ?? ""));
        formData.append("sede_id", String(registro.value.sede_id ?? sedeId ?? ""));
        formData.append("nombre", String(registro.value.nombre ?? ""));
        formData.append("estado", registro.value.estado ? "1" : "0");
        if (archivoFile.value) formData.append("archivo", archivoFile.value);

        let res;
        if (!actualizar.value) {
            res = await axios.post("/api/config-consentimiento-informado", formData, {
                headers: { "Content-Type": "multipart/form-data" },
            });
        } else {
            formData.append("_method", "PUT");
            res = await axios.post("/api/config-consentimiento-informado/" + id.value, formData, {
                headers: { "Content-Type": "multipart/form-data" },
            });
        }

        if (res.status === 200 || res.status === 201) {
            toastMessage({
                heading: "Ok!!!",
                text: res.data.data.message || "Guardado correctamente.",
                icon: "success",
            });
            ListarDatos();
            limpiarFormulario();
            btnCerralModalForm();
        }
    } catch (error) {
        errores.value = error?.response?.data?.errors || {};
    } finally {
        loadingGuardar.value = false;
    }
}
/**
 * Elimina un consentimiento informado previa confirmación del usuario.
 *
 * @param {number} id Identificador del consentimiento informado a eliminar.
 * @param {string} nom Nombre del consentimiento informado para el mensaje de confirmación.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
async function elimnarRegistro(id, nom) {
    const ok = await confirmAction({
        title: `Estas seguro de eliminar el consentimiento informado ${nom}?`,
        text: "No podrás recuperar este registro!",
        icon: "warning",
    });
    if (!ok) return;

    try {
        if (loadingEliminarId.value) return;
        loadingEliminarId.value = id;
        const response = await axios.delete("/api/config-consentimiento-informado/" + id);
        alertSuccess("Ok!", response?.data?.message || "Registro eliminado.", "success");
        ListarDatos();
    } catch (error) {
        alertError("Error", "No fue posible eliminar el registro.", "error");
    } finally {
        loadingEliminarId.value = null;
    }
}
/**
 * Obtiene la lista de productos disponibles para vincularlos al consentimiento informado.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
async function listarProductos() {
    try {
        const res = await axios.get("/api/config-productos");
        productos.value = res.data.data;
    } catch (error) {
        toastMessage({
            heading: "Error",
            text: "No se pudieron cargar los productos.",
            icon: "error",
        });
    }
}
/**
 * Captura el archivo seleccionado en el input de tipo file del formulario.
 *
 * @param {Event} e Evento de cambio del input de archivo.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
function obtenerArchivo(e) {
    archivoFile.value = e.target.files?.[0] || null;
}
/**
 * Actualiza el producto seleccionado a partir del valor retornado por el selector.
 *
 * @param {*} value Valor seleccionado en el componente Select2.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
function onProductoSelect(value) {
    let selectedId = null;
    if (Array.isArray(value) && value.length > 0) {
        const v = value[0];
        selectedId = String(typeof v === "object" && v && v.id != null ? v.id : v);
    } else if (value && typeof value === "object" && value.id != null) {
        selectedId = String(value.id);
    } else if (value != null) {
        selectedId = String(value);
    }
    registro.value.producto_id = selectedId ? Number(selectedId) : null;
    productoSelecciondo.value = selectedId;
}
/**
 * Devuelve la ruta pública del archivo PDF de un consentimiento informado.
 *
 * @param {Object} item Registro del consentimiento informado que contiene el archivo.
 *
 * @returns {string|null}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
function archivoUrl(item) {
    if (!item?.archivo) return null;
    return `/storage/${item.archivo}`;
}
/**
 * Prepara el formulario lateral para crear o editar un consentimiento informado.
 *
 * @param {Object} data Datos del consentimiento informado a editar (vacío para un nuevo registro).
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
function mostrarRegistro(data = {}) {
    if (actualizar.value == true) {
        tituloModal.value = "Actualizar el registro: " + (data.nombre || "");
        id.value = data.id;
        registro.value.producto_id = data.producto_id;
        productoSelecciondo.value = String(data.producto_id ?? "");
        registro.value.sede_id = data.sede_id;
        registro.value.nombre = data.nombre;
        registro.value.estado = !!data.estado;
        archivoFile.value = null;
    } else {
        actualizar.value = false;
        tituloModal.value = "Nuevo registro";
        id.value = 0;
        limpiarFormulario();
    }

    btnCerralModalForm();
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
function limpiarFormulario() {
    actualizar.value = false;
    tituloModal.value = "Nuevo registro";
    id.value = 0;
    errores.value = {};
    registro.value.producto_id = null;
    registro.value.sede_id = sessionStorage.getItem("ST-sede");
    registro.value.nombre = "";
    registro.value.estado = true;
    productoSelecciondo.value = null;
    archivoFile.value = null;
}
/**
 * Muestra u oculta el panel lateral del formulario mediante la clase correspondiente.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
function btnCerralModalForm() {
    $(".right-sidebar").slideDown(50);
    $(".right-sidebar").toggleClass("shw-rside");
}

/**
 * Hook de ciclo de vida que carga los consentimientos y los productos al montar el componente.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
onMounted(() => {
    ListarDatos();
    listarProductos();
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
    safeDestroyDataTable(dt.value || "#example23");
});
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

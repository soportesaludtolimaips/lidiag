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
                    <li class="breadcrumb-item active">sedes</li>
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
                        <h4 class="card-title">Listado de sedes</h4>
                        <h6 class="card-subtitle">
                            Administración de sedes para la distribucion y asignacion de estudios y
                            agenda de lecturas de imagenes díagnosticas.
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
                                        <th>Sede</th>
                                        <th>Ip Pacs</th>
                                        <th>BD Pacs</th>
                                        <th>Usuario Pacs</th>
                                        <th>Usuario Oviyam</th>
                                        <th>Password Oviyam</th>
                                        <th>Estado</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in registros" :key="item.id">
                                        <td>{{ item.nom_sede }}</td>
                                        <td>{{ item.ip_dcm }}</td>
                                        <td>{{ item.bd_dcm }}</td>
                                        <td>{{ item.usuario_dcm }}</td>
                                        <td>{{ item.usuario_oviyam }}</td>
                                        <td>{{ item.url_oviyam }}</td>
                                        <td>
                                            <span class="label label-success" v-if="item.estado"
                                                >Activo</span
                                            >
                                            <span class="label label-info" v-if="!item.estado"
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
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label">Sede</label>
                                        <input
                                            type="text"
                                            id="nom_sede"
                                            name="nom_sede"
                                            v-model="registro.nom_sede"
                                            class="form-control"
                                            placeholder="Ingrese el nombre de la sede"
                                        />
                                        <span class="text-danger" v-if="errores.nom_sede">{{
                                            errores.nom_sede[0]
                                        }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label">Email Para Reportar</label>
                                        <input
                                            type="text"
                                            id="email_repor"
                                            name="email_repor"
                                            v-model="registro.email_repor"
                                            class="form-control"
                                            placeholder="Ingrese el email para reportar las lecturas"
                                        />
                                        <span class="text-danger" v-if="errores.email_repor">{{
                                            errores.email_repor[0]
                                        }}</span>
                                    </div>
                                </div>
                            </div>

                            <h5 class="card-title text-info">
                                <i class="fa fa-paperclip"></i> Datos de configuración de DCMCHEE
                            </h5>
                            <hr />

                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="control-label">Ip BD Dcm4chee</label>
                                        <input
                                            type="text"
                                            id="ip_dcm"
                                            name="ip_dcm"
                                            v-model="registro.ip_dcm"
                                            class="form-control"
                                            placeholder="Ip del Dcm4chee de la sede"
                                        />
                                        <span class="text-danger" v-if="errores.ip_dcm">{{
                                            errores.ip_dcm[0]
                                        }}</span>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="control-label">BD DCM4CHEE</label>
                                        <input
                                            type="text"
                                            id="bd_dcm"
                                            name="bd_dcm"
                                            v-model="registro.bd_dcm"
                                            class="form-control"
                                            placeholder="Ingrese aquí el nombre de la BD"
                                        />
                                        <span class="text-danger" v-if="errores.bd_dcm">{{
                                            errores.bd_dcm[0]
                                        }}</span>
                                    </div>
                                </div>

                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label class="control-label">Usuario Bd</label>
                                        <input
                                            type="text"
                                            id="usuario_dcm"
                                            name="usuario_dcm"
                                            v-model="registro.usuario_dcm"
                                            class="form-control"
                                            placeholder="Ingrese aquí la URL del Ovyam de la sede"
                                        />
                                        <span class="text-danger" v-if="errores.usuario_dcm">{{
                                            errores.usuario_dcm[0]
                                        }}</span>
                                    </div>
                                </div>

                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label class="control-label">Password</label>
                                        <input
                                            type="text"
                                            id="password_dcm"
                                            name="password_dcm"
                                            v-model="registro.password_dcm"
                                            class="form-control"
                                            placeholder="Ingrese aquí la URL del Ovyam de la sede"
                                        />
                                        <span class="text-danger" v-if="errores.password_dcm">{{
                                            errores.password_dcm[0]
                                        }}</span>
                                    </div>
                                </div>

                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label class="control-label">Puerto</label>
                                        <input
                                            type="number"
                                            id="puerto_dcm"
                                            name="puerto_dcm"
                                            v-model="registro.puerto_dcm"
                                            class="form-control"
                                            placeholder="Ingrese aquí la URL del Ovyam de la sede"
                                        />
                                        <span class="text-danger" v-if="errores.puerto_dcm">{{
                                            errores.puerto_dcm[0]
                                        }}</span>
                                    </div>
                                </div>
                            </div>

                            <h5 class="card-title text-info">
                                <i class="fa fa-paperclip"></i> Datos de configuración de OVIYAM
                            </h5>
                            <hr />

                            <div class="row">
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label class="control-label">Usuario Oviyam</label>
                                        <input
                                            type="text"
                                            id="usuario_oviyam"
                                            name="usuario_oviyam"
                                            v-model="registro.usuario_oviyam"
                                            class="form-control"
                                            placeholder="Ingrese aquí la URL del Ovyam de la sede"
                                        />
                                        <span class="text-danger" v-if="errores.usuario_oviyam">{{
                                            errores.usuario_oviyam[0]
                                        }}</span>
                                    </div>
                                </div>

                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label class="control-label">Password Oviyam</label>
                                        <input
                                            type="text"
                                            id="password_oviyam"
                                            name="password_oviyam"
                                            v-model="registro.password_oviyam"
                                            class="form-control"
                                            placeholder="Ingrese aquí la URL del Ovyam de la sede"
                                        />
                                        <span class="text-danger" v-if="errores.password_oviyam">{{
                                            errores.password_oviyam[0]
                                        }}</span>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label">Url Oviyam</label>
                                        <input
                                            type="text"
                                            id="url_oviyam"
                                            name="url_oviyam"
                                            v-model="registro.url_oviyam"
                                            class="form-control"
                                            placeholder="Ingrese aquí la URL del Ovyam de la sede"
                                        />
                                        <span class="text-danger" v-if="errores.url_oviyam">{{
                                            errores.url_oviyam[0]
                                        }}</span>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label class="control-label">Tab Oviyam</label>
                                        <input
                                            type="text"
                                            id="tap_oviyam"
                                            name="tap_oviyam"
                                            v-model="registro.tap_oviyam"
                                            class="form-control"
                                            placeholder="Ingrese aquí la URL del Ovyam de la sede"
                                        />
                                        <span class="text-danger" v-if="errores.tap_oviyam">{{
                                            errores.tap_oviyam[0]
                                        }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <h5 class="card-title text-info">
                                        <i class="fa fa-paperclip"></i> Datos de configuración de la
                                        plantilla
                                    </h5>
                                    <hr />
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label class="control-label"
                                                    >Plantilla para Rx, Tomografía y
                                                    resonancia</label
                                                >
                                                <input
                                                    type="file"
                                                    class="form-control"
                                                    id="exampleInputFileRx"
                                                    aria-describedby="fileHelp"
                                                    @change="obtenerPlantillaRx"
                                                    accept=".doc, .docx"
                                                />
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label"
                                                    >Plantilla para ecografias</label
                                                >
                                                <input
                                                    type="file"
                                                    class="form-control"
                                                    id="exampleInputFileEcografia"
                                                    aria-describedby="fileHelp"
                                                    @change="obtenerPlantillaEcografia"
                                                    accept=".doc, .docx"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <h5 class="card-title text-info">
                                        <i class="fa fa-file-o"></i> Variables disponibles para la
                                        plantilla
                                    </h5>
                                    <hr />
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div class="form-group">
                                                        <label class="text-info">Paciente</label>:
                                                        <code>${nombre_paciente}</code> Nombres del
                                                        paciente, <code>${num_docu}</code> Número de
                                                        documento del paciente,
                                                        <code>${fec_naci}</code> Fecha de nacimiento
                                                        del paciente, <code>${sexo}</code> Sexo del
                                                        paciente, <code>${edad}</code> Edad del
                                                        paciente, <code>${dir}</code> Dirección de
                                                        recidencia, <code>${tel}</code> Télefono de
                                                        contacto, <code>${cel}</code> Celular de
                                                        contacto, <br /><br />

                                                        <label class="text-info">Estudio</label>:
                                                        <code>${fechor_estudio}</code> Fecha y Hora
                                                        del estudio,
                                                        <code>${fechor_lectura}</code> Fecha y Hora
                                                        de lectura,
                                                        <code>${fechor_transcipcion}</code> Fecha y
                                                        Hora de transcripción,
                                                        <code>${atencion}</code> Atención o número
                                                        de folio,
                                                        <code>${cod_producto}</code> Código del
                                                        producto,
                                                        <code>${nom_producto}</code> Nombre del
                                                        producto, <code>${lectura}</code>,
                                                        <code>${nom_sede} Sede de la tóma</code>.
                                                        <br /><br />

                                                        <label class="text-info">Ecografias</label>:
                                                        <code>${tipo_estudio}</code> Tipo de
                                                        estudio, <code>${opinion}</code> Opinión des
                                                        estudio, <code>${productos}</code> Productos
                                                        de la lectura, <br /><br />

                                                        <label class="text-info"
                                                            >Administradora de salud</label
                                                        >: <code>${eps}</code> Eps del paciente,
                                                        <code>${contrato}</code> Contrato de
                                                        vinculación con la Eps.
                                                        <code>${nom_sede}</code> Nombre de la sede
                                                        <br /><br />

                                                        <label class="text-info">Medico</label>:
                                                        <code>${nombres_medico}</code>,
                                                        <code>${reg_medico}</code>,
                                                        <code>${num_docu_medico}</code>,
                                                        <code>${firma_medico}</code>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
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
    id: null,
    nom_sede: "",
    email_repor: "",
    ip_dcm: "",
    bd_dcm: "",
    puerto_dcm: "",
    usuario_dcm: "",
    password_dcm: "",
    usuario_oviyam: "",
    password_oviyam: "",
    url_oviyam: "",
    tap_oviyam: "",
    estado: true,
});
const tituloModal = ref("Nuevo registro");
const actualizar = ref(false);
const plantillaRx = ref(null);
const plantillaEcografia = ref(null);
const errores = ref({});
const loadingListar = ref(false);
const loadingGuardar = ref(false);
const loadingEliminarId = ref(null);

/**
 * Obtiene la lista de sedes desde el servidor y la carga en la tabla.
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
        const res = await axios.get("/api/config-sedes");
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
/**
 * Crea o actualiza una sede enviando los datos y las plantillas de documentos al servidor.
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
        const formData = new FormData();
        formData.append("registro", JSON.stringify(registro.value));
        formData.append("plantilla_rx", plantillaRx.value);
        formData.append("plantilla_ecografia", plantillaEcografia.value);

        if (actualizar.value === false) {
            const res = await axios.post("/api/config-sedes", formData);

            if (res.status == 200) {
                $.toast({
                    heading: "Ok!!!",
                    text: res.data.data.message,
                    position: "top-right",
                    loaderBg: "#ff6849",
                    icon: "success",
                    hideAfter: 3500,
                    stack: 6,
                });

                ListarDatos();
                btnCerralModalForm();
                limpiar();
            }
        } else {
            const res = await axios.post("/config/sedes/updateWitchPlantillas", formData, {
                headers: { "Content-Type": "multipart/form-data" },
            });

            if (res.status == 200) {
                $.toast({
                    heading: "Ok!!!",
                    text: res.data.data.message,
                    position: "top-right",
                    loaderBg: "#ff6849",
                    icon: "success",
                    hideAfter: 3500,
                    stack: 6,
                });

                ListarDatos();
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
/**
 * Elimina una sede previa confirmación del usuario.
 *
 * @param {number} id Identificador de la sede a eliminar.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
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
        const response = await axios.delete("/api/config-sedes/" + id);
        alertSuccess("Ok!", response?.data?.message || "Registro eliminado.", "success");
        ListarDatos();
    } catch (error) {
        alertError("Error", "No fue posible eliminar el registro.", "error");
    } finally {
        loadingEliminarId.value = null;
    }
}
/**
 * Captura el archivo de plantilla Rx seleccionado en el input de tipo file.
 *
 * @param {Event} e Evento de cambio del input de archivo.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
function obtenerPlantillaRx(e) {
    plantillaRx.value = e.target.files[0];
}
/**
 * Captura el archivo de plantilla de ecografía seleccionado en el input de tipo file.
 *
 * @param {Event} e Evento de cambio del input de archivo.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
function obtenerPlantillaEcografia(e) {
    plantillaEcografia.value = e.target.files[0];
}
/**
 * Prepara el formulario lateral para crear o editar una sede.
 *
 * @param {Object} data Datos de la sede a editar (vacío para un nuevo registro).
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
function mostrarRegistro(data = {}) {
    if (actualizar.value) {
        tituloModal.value = "Actualizar el registro: " + data.nom_sede;
        registro.value.id = data.id;
        registro.value.nom_sede = data.nom_sede;
        registro.value.email_repor = data.email_repor;
        registro.value.ip_dcm = data.ip_dcm;
        registro.value.bd_dcm = data.bd_dcm;
        registro.value.puerto_dcm = data.puerto_dcm;
        registro.value.usuario_dcm = data.usuario_dcm;
        registro.value.password_dcm = data.password_dcm;
        registro.value.usuario_oviyam = data.usuario_oviyam;
        registro.value.password_oviyam = data.password_oviyam;
        registro.value.url_oviyam = data.url_oviyam;
        registro.value.tap_oviyam = data.tap_oviyam;
        registro.value.plantilla = data.plantilla_rx;
        registro.value.estado = Boolean(data.estado);

        $("#btnCerralModalForm").click();
        errores.value = [];
    } else {
        actualizar.value = false;
        tituloModal.value = "Nuevo registro";
        registro.value.id = 0;
        registro.value.nom_sede = "";
        registro.value.email_repor = "";
        registro.value.ip_dcm = "";
        registro.value.bd_dcm = "";
        registro.value.puerto_dcm = "";
        registro.value.usuario_dcm = "";
        registro.value.password_dcm = "";
        registro.value.usuario_oviyam = "";
        registro.value.password_oviyam = "";
        registro.value.url_oviyam = "";
        registro.value.tap_oviyam = "";
        registro.value.plantilla = "";
        registro.value.estado = true;

        $("#btnCerralModalForm").click();
        errores.value = [];
    }
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
    tituloModal.value = "Nuevo registro";
    registro.value.id = 0;
    registro.value.nom_sede = "";
    registro.value.email_repor = "";
    registro.value.ip_dcm = "";
    registro.value.bd_dcm = "";
    registro.value.puerto_dcm = "";
    registro.value.usuario_dcm = "";
    registro.value.password_dcm = "";
    registro.value.usuario_oviyam = "";
    registro.value.password_oviyam = "";
    registro.value.url_oviyam = "";
    registro.value.plantilla = "";
    registro.value.estado = true;

    $("#btnCerralModalForm").click();
    errores.value = [];
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
 * Hook de ciclo de vida que carga los datos al montar el componente.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
onMounted(() => {
    ListarDatos();
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

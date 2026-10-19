<template>
    <div>
        <!-- ============================================================== -->
        <!-- Bread crumb and right sidebar toggle -->
        <!-- ============================================================== -->
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">Seguridad</h3>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a role="button" tabindex="0">Inicio</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a role="button" tabindex="0">Seguridad</a>
                    </li>
                    <li class="breadcrumb-item active">Gestión de Usuarios</li>
                </ol>
            </div>
            <div class="col-md-7 align-self-center text-right d-none d-md-block">
                <button
                    type="button"
                    class="btn btn-sm btn-outline-secondary btn-action-clinical m-r-5"
                    :disabled="loadingListar"
                    title="Actualizar listado de usuarios (F2)"
                    @click="ListarDatos()"
                >
                    <i class="fa fa-refresh mr-1" :class="{ 'fa-spin': loadingListar }"></i>
                    Refrescar <span class="badge badge-light border ml-1">F2</span>
                </button>
                <button
                    type="button"
                    class="btn btn-info btn-action-clinical btnDesplegarRigthSidebar"
                    title="Crear nuevo usuario"
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
                        <h4 class="card-title">Listado de usuarios</h4>
                        <div v-if="loadingListar" class="text-center p-5">
                            <i class="fa fa-spinner fa-spin fa-3x"></i>
                            <p class="m-t-10">Cargando usuarios...</p>
                        </div>
                        <div class="table-responsive m-t-40" v-else>
                            <table
                                id="example23"
                                ref="dt"
                                class="display nowrap table table-hover table-striped table-bordered"
                                cellspacing="0"
                                width="100%"
                            >
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th># Documento</th>
                                        <th>Registro Medico</th>
                                        <th>Nombres</th>
                                        <th>E-Mail</th>
                                        <th>Firma</th>
                                        <th>Modulos</th>
                                        <th>Permisos</th>
                                        <th>Estado</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="!registros.length">
                                        <td colspan="12" class="text-center text-muted">
                                            No se encontraron usuarios registrados.
                                        </td>
                                    </tr>
                                    <tr v-for="item in registros" :key="item.id">
                                        <td>
                                            <div class="user-img">
                                                <img
                                                    v-if="!item.imagen_perfil"
                                                    :src="'/admin-wrap/assets/images/users/avatar_default.png'"
                                                    :alt="item.name"
                                                    class="img-circle"
                                                    height="40"
                                                    width="40"
                                                />
                                                <img
                                                    v-else
                                                    :src="'/files/' + item.imagen_perfil"
                                                    :alt="item.name"
                                                    class="img-circle"
                                                    height="40"
                                                    width="40"
                                                />
                                            </div>
                                        </td>
                                        <td>{{ item.num_docu }}</td>
                                        <td>{{ item.reg_med }}</td>
                                        <td>{{ item.name }}</td>
                                        <td>{{ item.email }}</td>
                                        <td>
                                            <img
                                                v-if="item.firma && !firmaError[item.id]"
                                                :src="'/files/' + item.firma"
                                                alt="Firma"
                                                :title="'Firma de ' + item.name"
                                                height="30"
                                                style="
                                                    cursor: pointer;
                                                    max-width: 100px;
                                                    object-fit: contain;
                                                "
                                                @click="ampliarFirma(item.firma, item.name)"
                                                @error="firmaError[item.id] = true"
                                            />
                                            <span
                                                v-else-if="item.firma && firmaError[item.id]"
                                                class="badge badge-warning"
                                                title="Archivo de firma no encontrado en el servidor"
                                            >
                                                <i class="fa fa-exclamation-triangle"></i> Firma no
                                                disponible
                                            </span>
                                            <span class="label label-danger" v-else>No</span>
                                        </td>
                                        <td>
                                            <span
                                                class="badge badge-info m-1"
                                                v-for="mod in item.modulos"
                                                :key="mod.id"
                                            >
                                                {{ mod.modulo }}
                                            </span>
                                        </td>
                                        <td>
                                            <span
                                                class="badge badge-info m-1"
                                                v-for="rol in item.roles"
                                                :key="rol.id"
                                            >
                                                {{ rol.name }}
                                            </span>
                                        </td>
                                        <td>
                                            <span
                                                class="label label-success"
                                                v-if="item.estado == 1"
                                                >Activo</span
                                            >
                                            <span class="label label-danger" v-if="item.estado == 0"
                                                >Inactivo</span
                                            >
                                        </td>
                                        <td class="text-nowrap">
                                            <!-- <router-link
                                                class="btn waves-effect waves-light btn-rounded btn-outline-info btn-sm m-r-5"
                                                :to="{ name: 'seguridad.usuario.perfil', params: { id: item.id } }">
                                                <i class="fa fa-eye"></i>
                                            </router-link> -->

                                            <button
                                                type="button"
                                                title="Editar usuario"
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
                                                title="Eliminar usuario"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm m-r-5"
                                                :disabled="loadingEliminarId === item.id"
                                                @click="eliminarRegistro(item.id)"
                                            >
                                                <i
                                                    v-if="loadingEliminarId === item.id"
                                                    class="fa fa-spinner fa-spin"
                                                ></i>
                                                <i v-else class="fa fa-trash"></i>
                                            </button>
                                            <button
                                                type="button"
                                                title="Desactivar usuario"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-success btn-sm"
                                                :disabled="loadingEstadoId === item.id"
                                                @click="cambiarEstado(1, item)"
                                                v-if="item.estado == 1"
                                            >
                                                <i
                                                    v-if="loadingEstadoId === item.id"
                                                    class="fa fa-spinner fa-spin"
                                                ></i>
                                                <i v-else class="fa fa-unlock"></i>
                                            </button>
                                            <button
                                                type="button"
                                                title="Activar usuario"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-info btn-sm"
                                                :disabled="loadingEstadoId === item.id"
                                                @click="cambiarEstado(0, item)"
                                                v-if="item.estado == 0"
                                            >
                                                <i
                                                    v-if="loadingEstadoId === item.id"
                                                    class="fa fa-spinner fa-spin"
                                                ></i>
                                                <i v-else class="fa fa-lock"></i>
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
        <div v-if="sidebarOpen" class="sidebar-backdrop" @click="btnCerrarModalForm()"></div>
        <!-- .right-sidebar -->
        <div
            class="right-sidebar"
            :class="{ 'shw-rside': sidebarOpen }"
            style="overflow-y: auto; height: 100%"
        >
            <div class="slimscrollright">
                <div class="rpanel-title">
                    {{ tituloModal }}
                    <button
                        type="button"
                        class="right-side-toggle text-white border-0 bg-transparent"
                        title="Cerrar panel (Esc)"
                        aria-label="Cerrar panel"
                        @click="btnCerrarModalForm()"
                    >
                        <i class="ti-close"></i>
                    </button>
                </div>
                <div class="r-panel-body">
                    <!-- ============================================================== -->
                    <!-- Aqui va el contenido de los formularios -->
                    <!-- ============================================================== -->
                    <form action="#">
                        <div class="form-body">
                            <div class="row p-t-5">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="control-label"># Documento</label>
                                        <input
                                            type="text"
                                            id="num_docu"
                                            v-model="registro.num_docu"
                                            class="form-control"
                                            placeholder="Número de documento del usuario"
                                        />
                                        <span class="text-danger" v-if="errores.num_docu">{{
                                            errores.num_docu[0]
                                        }}</span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="control-label">Registro médico</label>
                                        <input
                                            type="text"
                                            id="reg_med"
                                            v-model="registro.reg_med"
                                            class="form-control"
                                            placeholder="Registro medico"
                                        />
                                        <span class="text-danger" v-if="errores.reg_med">{{
                                            errores.reg_med[0]
                                        }}</span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="control-label">Id Usuario SAHI</label>
                                        <input
                                            type="text"
                                            id="interface_id"
                                            v-model="registro.interface_id"
                                            class="form-control"
                                            placeholder="Id del usuario de SAHI"
                                        />
                                        <span class="text-danger" v-if="errores.interface_id">{{
                                            errores.interface_id[0]
                                        }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="control-label">Nombres</label>
                                        <input
                                            type="text"
                                            id="name"
                                            v-model="registro.name"
                                            class="form-control"
                                            placeholder="Ingrese aquí los nombres del usuario"
                                        />
                                        <span class="text-danger" v-if="errores.name">{{
                                            errores.name[0]
                                        }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="control-label">E - Mail</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">
                                                    <i class="ti-email"></i>
                                                </span>
                                            </div>
                                            <input
                                                type="email"
                                                id="email"
                                                name="email"
                                                v-model="registro.email"
                                                class="form-control"
                                                placeholder="Ingrese aquí E - Mail del usuario"
                                            />
                                        </div>

                                        <span class="text-danger" v-if="errores.email">{{
                                            errores.email[0]
                                        }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label">Contraseña</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">
                                                    <i class="ti-lock"></i>
                                                </span>
                                            </div>
                                            <input
                                                type="password"
                                                id="password"
                                                name="password"
                                                v-model="registro.password"
                                                class="form-control"
                                                placeholder="Contraseña"
                                            />
                                        </div>

                                        <small class="text-muted d-block"
                                            >Dejar en blanco si no desea cambiar la
                                            contraseña</small
                                        >
                                        <span class="text-danger" v-if="errores.password">{{
                                            errores.password[0]
                                        }}</span>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label">Confirmar contraseña</label>

                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">
                                                    <i class="ti-lock"></i
                                                ></span>
                                            </div>
                                            <input
                                                type="password"
                                                id="confirmarPassword"
                                                name="confirmarPassword"
                                                v-model="registro.confirmarPassword"
                                                class="form-control"
                                                placeholder="Confirmar contraseña"
                                            />
                                        </div>

                                        <span
                                            class="text-danger"
                                            v-if="errores.confirmarPassword"
                                            >{{ errores.confirmarPassword[0] }}</span
                                        >
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <p class="text-info"><i class="fa fa-unlock"></i> Roles</p>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <div class="controls">
                                                    <div
                                                        v-for="itemRol in registro.rolesUsuario"
                                                        :key="itemRol.id"
                                                    >
                                                        <div class="custom-control custom-checkbox">
                                                            <input
                                                                type="checkbox"
                                                                class="custom-control-input"
                                                                :id="itemRol.name"
                                                                v-model="itemRol.checked"
                                                            />
                                                            <label
                                                                class="custom-control-label"
                                                                :for="itemRol.name"
                                                            >
                                                                {{ itemRol.name }}
                                                            </label>
                                                        </div>
                                                    </div>

                                                    <div class="custom-control custom-checkbox">
                                                        <input
                                                            type="checkbox"
                                                            class="custom-control-input"
                                                            id="transcrip_remoto"
                                                            v-model="registro.transcrip_remoto"
                                                        />
                                                        <label
                                                            class="custom-control-label"
                                                            for="transcrip_remoto"
                                                        >
                                                            Transcripcción remota
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <p class="text-info"><i class="fa fa-unlock"></i> Modulos</p>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <div class="controls">
                                                    <div
                                                        v-for="itemModulo in registro.modulos"
                                                        :key="itemModulo.id"
                                                    >
                                                        <div class="custom-control custom-checkbox">
                                                            <input
                                                                type="checkbox"
                                                                class="custom-control-input"
                                                                :id="itemModulo.modulo"
                                                                v-model="itemModulo.checked"
                                                            />
                                                            <label
                                                                class="custom-control-label"
                                                                :for="itemModulo.modulo"
                                                            >
                                                                {{ itemModulo.modulo }}
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <p class="text-info">
                                        <i class="fa fa-building"></i> Sedes Asignadas
                                    </p>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <div
                                                    class="controls"
                                                    style="max-height: 200px; overflow-y: auto"
                                                >
                                                    <div
                                                        v-for="itemSede in registro.sedes"
                                                        :key="itemSede.id"
                                                    >
                                                        <div class="custom-control custom-checkbox">
                                                            <input
                                                                type="checkbox"
                                                                class="custom-control-input"
                                                                :id="'sede_' + itemSede.id"
                                                                v-model="itemSede.checked"
                                                            />
                                                            <label
                                                                class="custom-control-label"
                                                                :for="'sede_' + itemSede.id"
                                                            >
                                                                {{ itemSede.nom_sede }}
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <p class="text-info">
                                        <i class="fa fa-stethoscope"></i> Servicios de Cita
                                    </p>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <div
                                                    class="controls"
                                                    style="max-height: 200px; overflow-y: auto"
                                                >
                                                    <div
                                                        v-for="itemSC in registro.serviciosCitas"
                                                        :key="itemSC.id"
                                                    >
                                                        <div class="custom-control custom-checkbox">
                                                            <input
                                                                type="checkbox"
                                                                class="custom-control-input"
                                                                :id="'sc_' + itemSC.id"
                                                                v-model="itemSC.checked"
                                                            />
                                                            <label
                                                                class="custom-control-label"
                                                                :for="'sc_' + itemSC.id"
                                                            >
                                                                {{ itemSC.nombre }}
                                                                <small class="text-muted">
                                                                    ({{
                                                                        itemSC.sede?.nom_sede
                                                                    }})</small
                                                                >
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-12 m-b-20">
                                <div
                                    class="fileupload btn btn-danger btn-rounded waves-effect waves-light"
                                >
                                    <span> <i class="ion-upload m-r-5"></i>Subir Firma </span>
                                    <input type="file" class="upload" @change="obtenerFirma" />
                                </div>
                                <div
                                    class="fileupload btn btn-danger btn-rounded waves-effect waves-light"
                                >
                                    <span> <i class="ion-upload m-r-5"></i>Subir Avatar </span>
                                    <input type="file" class="upload" @change="obtenerAvatar" />
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
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
                                    class="btn btn-success btn-action-clinical m-r-5"
                                    @click="guardarRegistro()"
                                    v-if="!actualizar"
                                    :disabled="loadingGuardar"
                                    title="Guardar usuario (Ctrl+S)"
                                >
                                    <i v-if="loadingGuardar" class="fa fa-spinner fa-spin mr-1"></i>
                                    <i v-else class="fa fa-check mr-1"></i> Guardar
                                    <span class="badge badge-light text-dark ml-1">Ctrl+S</span>
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-warning btn-action-clinical m-r-5"
                                    v-if="actualizar"
                                    @click="guardarRegistro()"
                                    :disabled="loadingGuardar"
                                    title="Actualizar usuario (Ctrl+S)"
                                >
                                    <i v-if="loadingGuardar" class="fa fa-spinner fa-spin mr-1"></i>
                                    <i v-else class="fa fa-pencil mr-1"></i> Actualizar
                                    <span class="badge badge-light text-dark ml-1">Ctrl+S</span>
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-inverse btn-action-clinical"
                                    title="Cancelar (Esc)"
                                    @click="btnCerrarModalForm()"
                                >
                                    Cancelar
                                    <span class="badge badge-secondary ml-1">Esc</span>
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

        <!-- Modal ampliar firma -->
        <div
            v-if="modalFirmaVisible"
            class="modal fade show d-block"
            id="modalAmpliarFirma"
            tabindex="-1"
            role="dialog"
            aria-modal="true"
            aria-hidden="false"
            style="background: rgba(15, 23, 42, 0.5); backdrop-filter: blur(2px); z-index: 1060"
            @click.self="cerrarModalFirma"
        >
            <div class="modal-dialog modal-sm">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Firma — {{ firmaAmpliadaNombre }}</h4>
                        <button
                            type="button"
                            class="close"
                            @click="cerrarModalFirma"
                            aria-label="Cerrar modal"
                            title="Cerrar (Esc)"
                        >
                            &times;
                        </button>
                    </div>
                    <div class="modal-body text-center">
                        <img
                            v-if="firmaAmpliadaSrc && !firmaAmpliadaError"
                            :src="firmaAmpliadaSrc"
                            alt="Firma"
                            style="max-width: 100%; max-height: 300px"
                            @error="firmaAmpliadaError = true"
                        />
                        <p v-else-if="firmaAmpliadaSrc && firmaAmpliadaError" class="text-warning">
                            <i class="fa fa-exclamation-triangle fa-2x m-b-10 d-block"></i>
                            Firma no disponible
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-inverse btn-action-clinical"
                            title="Cerrar (Esc)"
                            @click="cerrarModalFirma"
                        >
                            Cerrar
                            <span class="badge badge-secondary ml-1">Esc</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref } from "vue";
import { confirmAction, alertError, alertSuccess, toastMessage } from "@/helpers/alerts";
import { safeDestroyDataTable, safeInitDataTable } from "@/helpers/datatables.js";
import { useKeyboardShortcuts } from "@/composables/useKeyboardShortcuts.js";
import axios from "axios";

defineProps({ usuarioactual: { type: Object, default: () => ({}) } });

const dt = ref(null);
const modalFirmaVisible = ref(false);

const registros = ref([]);
const roles = ref([]);
const id = ref(null);
const registro = ref({
    num_docu: "",
    reg_med: "",
    name: "",
    email: "",
    password: "",
    confirmarPassword: "",
    estado: false,
    imagen_perfil: "",
    rolesUsuario: [],
    modulos: [],
    sedes: [],
    serviciosCitas: [],
    interface_id: null,
    transcrip_remoto: 0,
});
const tituloModal = ref("Nuevo registro");
const actualizar = ref(false);
const avatar = ref(null);
const firma = ref(null);
const errores = ref({});
const loadingListar = ref(false);
const loadingGuardar = ref(false);
const loadingEstadoId = ref(null);
const loadingEliminarId = ref(null);
const firmaAmpliadaSrc = ref("");
const firmaAmpliadaNombre = ref("");
const firmaAmpliadaError = ref(false);
const firmaError = ref({});
const sidebarOpen = ref(false);

function initDataTable() {
    safeInitDataTable(dt.value || "#example23", {
        dom: "Bfrtip",
        buttons: ["copy", "csv", "excel", "pdf", "print"],
        retrieve: true,
    });
}
function destroyDataTable() {
    safeDestroyDataTable(dt.value || "#example23");
}
async function ListarDatos() {
    if (loadingListar.value) return;
    loadingListar.value = true;
    try {
        const res = await axios.get("/api/seguridad-usuarios");
        const resRoles = await axios.get("/api/seguridad-roles");

        destroyDataTable();

        registros.value = [];
        roles.value = [];
        registro.value.rolesUsuario = [];

        registros.value = res.data.data;
        roles.value = resRoles.data.data.roles;
        listarRoles();

        nextTick(() => {
            initDataTable();
        });
    } catch (_error) {
        toastMessage({
            heading: "Error",
            text: "No se pudieron cargar los usuarios.",
            icon: "error",
        });
    } finally {
        loadingListar.value = false;
    }
}
function listarRoles() {
    registro.value.rolesUsuario = roles.value.map((x) => ({
        id: x.id,
        name: x.name,
        checked: false,
    }));
}
async function guardarRegistro() {
    if (loadingGuardar.value) return;
    loadingGuardar.value = true;
    try {
        const formData = new FormData();
        formData.append("registro", JSON.stringify(registro.value));
        formData.append("id", id.value);
        formData.append("firma", firma.value);
        formData.append("avatar", avatar.value);

        if (actualizar.value === false) {
            const res = await axios.post("/api/seguridad-usuarios", formData, {
                headers: { "Content-Type": "multipart/form-data" },
            });

            toastMessage({
                heading: "Ok!!!",
                text: res.data.data.message,
                position: "top-right",
                loaderBg: "#ff6849",
                icon: "success",
                hideAfter: 3500,
                stack: 6,
            });

            ListarDatos();
            limpiarFormulario();
            btnCerrarModalForm();
        } else {
            const res = await axios.post("/api/seguridad/usuarios/update", formData, {
                headers: { "Content-Type": "multipart/form-data" },
            });

            toastMessage({
                heading: "Ok!!!",
                text: res.data.data.message,
                position: "top-right",
                loaderBg: "#ff6849",
                icon: "success",
                hideAfter: 3500,
                stack: 6,
            });

            ListarDatos();
            limpiarFormulario();
            btnCerrarModalForm();
        }
    } catch (_error) {
        errores.value = _error?.response?.data?.errors || {};

        if (_error?.response?.status !== 422) {
            toastMessage({
                heading: "Error",
                text: _error?.response?.data?.message || "No se pudo guardar el usuario.",
                icon: "error",
            });
        }
    } finally {
        loadingGuardar.value = false;
    }
}
async function cambiarEstado(estado, data = {}) {
    const ok = await confirmAction({
        title: "Confirmación",
        text:
            "Estas seguro de que desea " +
            (estado == 0 ? " activar" : "desactivar") +
            " el usuario " +
            data.name +
            "?",
        icon: "warning",
    });
    if (!ok) return;
    if (loadingEstadoId.value) return;

    loadingEstadoId.value = data.id;
    try {
        await axios.put("/api/seguridad-usuarios/estado/" + data.id, {
            id: data.id,
            estado: estado == 0 ? 1 : 0,
        });
        alertSuccess(
            "Ok!",
            "El usuario " +
                data.name +
                " se " +
                (estado == 0 ? " activó" : "desactivó") +
                " correctamente",
            "success"
        );
        ListarDatos();
    } catch (error) {
        alertError(
            "Error",
            error?.response?.data?.message || "No fue posible actualizar el estado.",
            "error"
        );
    } finally {
        loadingEstadoId.value = null;
    }
}
async function eliminarRegistro(userId) {
    const ok = await confirmAction({
        title: "Estas seguro de eliminar este usuario?",
        text: "No podrás recuperar este registro!",
        icon: "warning",
    });
    if (!ok) return;
    if (loadingEliminarId.value) return;

    loadingEliminarId.value = userId;
    try {
        const res = await axios.delete("/api/seguridad-usuarios/" + userId);
        alertSuccess("Eliminado!", res?.data?.message || "Registro eliminado.", "success");
        ListarDatos();
    } catch (err) {
        alertError("Error", err?.response?.data?.message || "No fue posible eliminar.", "error");
    } finally {
        loadingEliminarId.value = null;
    }
}
async function ListarModulos() {
    try {
        const res = await axios.get("/api/config-modulos-listar");
        const data = res.data?.data || res.data || [];
        registro.value.modulos = data.map((modulo) => ({
            ...modulo,
            checked: false,
        }));
    } catch (_error) {
        toastMessage({
            heading: "Error",
            text: "No fue posible cargar los módulos.",
            icon: "error",
        });
    }
}
async function ListarSedes() {
    try {
        const res = await axios.get("/api/config-sedes");
        const data = res.data?.data || res.data || [];
        registro.value.sedes = data.map((sede) => ({
            ...sede,
            checked: false,
        }));
    } catch (_error) {
        toastMessage({
            heading: "Error",
            text: "No fue posible cargar las sedes.",
            icon: "error",
        });
    }
}
async function ListarServiciosCitas() {
    try {
        const res = await axios.get("/api/config-servicio-citas");
        registro.value.serviciosCitas = (res.data?.data || res.data || []).map((sc) => ({
            ...sc,
            checked: false,
        }));
    } catch (_error) {
        toastMessage({
            heading: "Error",
            text: "No fue posible cargar los servicios de citas.",
            icon: "error",
        });
    }
}
function obtenerFirma(event) {
    firma.value = event.target.files[0];
}
function obtenerAvatar(event) {
    avatar.value = event.target.files[0];
}
function mostrarRegistro(data = {}) {
    errores.value = {};
    registro.value.password = "";
    registro.value.confirmarPassword = "";

    if (actualizar.value) {
        tituloModal.value = "Actualizar el registro: " + data.name;
        id.value = data.id;
        registro.value.num_docu = data.num_docu;
        registro.value.reg_med = data.reg_med;
        registro.value.name = data.name;
        registro.value.email = data.email;
        registro.value.interface_id = data.interface_id;
        registro.value.estado = Boolean(data.estado);

        // Inactavar los modulos
        registro.value.modulos.forEach((modulo) => {
            modulo.checked = false;
        });

        // Asigna el valor de `checked` a cada módulo
        (data.modulos || []).forEach((modulo) => {
            let moduloActivo = registro.value.modulos.findIndex((x) => x.id == modulo.id);
            if (moduloActivo !== -1) {
                registro.value.modulos[moduloActivo].checked = true;
            }
        });

        // Inactivar sedes y marcar las del usuario
        registro.value.sedes.forEach((sede) => {
            sede.checked = false;
        });
        (data.sedes || []).forEach((sede) => {
            let idx = registro.value.sedes.findIndex((x) => x.id == sede.id);
            if (idx !== -1) {
                registro.value.sedes[idx].checked = true;
            }
        });

        // Inactivar servicios de cita y marcar los del usuario
        registro.value.serviciosCitas.forEach((sc) => {
            sc.checked = false;
        });
        (data.serviciosCita || []).forEach((sc) => {
            let idx = registro.value.serviciosCitas.findIndex((x) => x.id == sc.id);
            if (idx !== -1) {
                registro.value.serviciosCitas[idx].checked = true;
            }
        });

        /**
         * Listo los datos del usuario con sus respectivos roles
         * Recorro los roles que estan en el Frond y los desactivo para
         * marcar los roles del usuario a editar
         */
        axios
            .get("/api/seguridad-usuarios/" + id.value)
            .then((res) => {
                const RolesDelUsuario = res.data?.data?.RolesDeUsuario || [];

                (registro.value.rolesUsuario || []).forEach(function (rol, index) {
                    registro.value.rolesUsuario[index].checked = false;
                });

                (RolesDelUsuario || []).forEach(function (rolId) {
                    let rolActivo = roles.value.findIndex((x) => x.id == rolId);
                    if (rolActivo !== -1) {
                        registro.value.rolesUsuario[rolActivo].checked = true;
                    }
                });
            })
            .catch((_error) => {
                toastMessage({
                    heading: "Error",
                    text:
                        _error?.response?.data?.message ||
                        "No fue posible cargar los roles del usuario.",
                    icon: "error",
                });
            });
    } else {
        actualizar.value = false;
        tituloModal.value = "Nuevo registro";
        id.value = 0;
        registro.value.num_docu = "";
        registro.value.reg_med = "";
        registro.value.name = "";
        registro.value.email = "";
        registro.value.password = "";
        registro.value.estado = true;
        registro.value.interface_id = "";
        // Reset sedes y servicios de cita
        registro.value.sedes.forEach((sede) => (sede.checked = false));
        registro.value.serviciosCitas.forEach((sc) => (sc.checked = false));
    }

    sidebarOpen.value = true;
    nextTick(() => {
        const el = document.getElementById("num_docu");
        if (el) el.focus();
    });
}
function ampliarFirma(firmaPath, nombre) {
    firmaAmpliadaError.value = false;
    firmaAmpliadaSrc.value = "/" + firmaPath;
    firmaAmpliadaNombre.value = nombre || "Firma";
    modalFirmaVisible.value = true;
}
function cerrarModalFirma() {
    modalFirmaVisible.value = false;
}
function btnCerrarModalForm() {
    sidebarOpen.value = false;
}
function limpiarFormulario() {
    actualizar.value = false;
    tituloModal.value = "Nuevo registro";
    id.value = 0;
    registro.value.num_docu = "";
    registro.value.reg_med = "";
    registro.value.name = "";
    registro.value.email = "";
    registro.value.password = "";
    registro.value.confirmarPassword = "";
    registro.value.estado = false;
    errores.value = {};
}

onMounted(() => {
    ListarDatos();
    ListarModulos();
    ListarSedes();
    ListarServiciosCitas();
});

onBeforeUnmount(() => {
    destroyDataTable();
});

useKeyboardShortcuts({
    onSearch: () => {
        if (!sidebarOpen.value && !modalFirmaVisible.value && !loadingListar.value) {
            ListarDatos();
        }
    },
    onSave: () => {
        if (sidebarOpen.value && !loadingGuardar.value) {
            guardarRegistro();
        }
    },
    onEscape: () => {
        if (modalFirmaVisible.value) {
            cerrarModalFirma();
        } else if (sidebarOpen.value) {
            btnCerrarModalForm();
        }
    },
});
</script>
<style scoped>
.right-sidebar {
    position: fixed;
    right: -55%;
    width: 55%;
    visibility: hidden;
    pointer-events: none;
    z-index: 1100;
    background: #ffffff;
    top: 0px;
    padding-bottom: 20px;
    height: 100%;
    box-shadow: -5px 0 25px rgba(0, 0, 0, 0.15);
    transition:
        right 0.35s cubic-bezier(0.16, 1, 0.3, 1),
        visibility 0.35s;
}

.shw-rside {
    right: 0px;
    visibility: visible;
    pointer-events: auto;
}

.sidebar-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(15, 23, 42, 0.45);
    backdrop-filter: blur(2px);
    z-index: 1050;
    transition: opacity 0.25s ease;
}

.btn-action-clinical {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}
.btn-action-clinical:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow:
        0 4px 6px -1px rgba(0, 0, 0, 0.1),
        0 2px 4px -1px rgba(0, 0, 0, 0.06);
}
</style>

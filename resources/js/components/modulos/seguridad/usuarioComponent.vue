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
                        <a href="javascript:void(0)">Inicio</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0)">Seguridad</a>
                    </li>
                    <li class="breadcrumb-item active">Gestión de Usuarios</li>
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
                        <h4 class="card-title">Listado de usuarios</h4>
                        <div class="table-responsive m-t-40">
                            <table id="example23" ref="dt"
                                class="display nowrap table table-hover table-striped table-bordered" cellspacing="0"
                                width="100%">
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
                                        <th>Sedes</th>
                                        <th>Servicios de Cita</th>
                                        <th>Estado</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in registros" :key="item.id">
                                        <td>
                                            <div class="user-img">
                                                <img v-if="!item.imagen_perfil"
                                                    src="@/admin-wrap/assets/images/users/avatar_default.png"
                                                    :alt="item.name" class="img-circle" height="40" width="40">
                                                <img v-else :src="item.imagen_perfil" :alt="item.name"
                                                    class="img-circle" height="40" width="40">
                                            </div>
                                        </td>
                                        <td>{{ item.num_docu }}</td>
                                        <td>{{ item.reg_med }}</td>
                                        <td>{{ item.name }}</td>
                                        <td>{{ item.email }}</td>
                                        <td>
                                            <span class="label label-success" v-if="item.firma != null">Si</span>
                                            <span class="label label-danger" v-if="item.firma === null">No</span>
                                        </td>
                                        <td>
                                            <span class="badge badge-info m-1" v-for="mod in item.modulos"
                                                :key="mod.id">
                                                {{ mod.modulo }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-info m-1" v-for="rol in item.roles" :key="rol.id">
                                                {{ rol.name }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-warning m-1" v-for="sede in item.sedes" :key="sede.id">
                                                {{ sede.nom_sede }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-success m-1" v-for="sc in item.serviciosCita" :key="sc.id" style="white-space: normal;">
                                                {{ sc.nombre }}
                                                <br><small style="font-size: 10px; opacity: 0.9;">{{ sc.sede?.nom_sede || '' }}</small>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="label label-success" v-if="item.estado == 1">Activo</span>
                                            <span class="label label-danger" v-if="item.estado == 0">Inactivo</span>
                                        </td>
                                        <td class="text-nowrap">
                                            <!-- <router-link
                                                class="btn waves-effect waves-light btn-rounded btn-outline-info btn-sm m-r-5"
                                                :to="{ name: 'seguridad.usuario.perfil', params: { id: item.id } }">
                                                <i class="fa fa-eye"></i>
                                            </router-link> -->

                                            <button type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-warning btn-sm m-r-5"
                                                @click="actualizar = true; mostrarRegistro(item)">
                                                <i class="fa fa-pencil"></i>
                                            </button>
                                            <button type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm m-r-5"
                                                :disabled="loadingEliminarId === item.id"
                                                @click="elimnarRegistro(item.id)">
                                                <i v-if="loadingEliminarId === item.id"
                                                    class="fa fa-spinner fa-spin"></i>
                                                <i v-else class="fa fa-trash"></i>
                                            </button>
                                            <button type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-success btn-sm"
                                                :disabled="loadingEstadoId === item.id" @click="cambiarEstado(1, item)"
                                                v-if="item.estado == 1">
                                                <i v-if="loadingEstadoId === item.id" class="fa fa-spinner fa-spin"></i>
                                                <i v-else class="fa fa-unlock"></i>
                                            </button>
                                            <button type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-info btn-sm"
                                                :disabled="loadingEstadoId === item.id" @click="cambiarEstado(0, item)"
                                                v-if="item.estado == 0">
                                                <i v-if="loadingEstadoId === item.id" class="fa fa-spinner fa-spin"></i>
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
        <!-- .right-sidebar -->
        <div class="right-sidebar" style="overflow-x: hidden; overflow-y: scroll; overflow-x: hidden; height: 100%;">
            <div class="slimscrollright">
                <div class="rpanel-title">
                    {{ tituloModal }}
                    <span @click="btnCerralModalForm()">
                        <i class="ti-close right-side-toggle"></i>
                    </span>
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
                                        <input type="text" id="num_docu" v-model="registro.num_docu"
                                            class="form-control" placeholder="Número de documeto del usuario">
                                        <span class="text-danger" v-if="errores.num_docu">{{
                                            errores.num_docu[0]
                                        }}</span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="control-label">Registro medidco</label>
                                        <input type="text" id="reg_med" v-model="registro.reg_med" class="form-control"
                                            placeholder="Registro medico">
                                        <span class="text-danger" v-if="errores.reg_med">{{
                                            errores.reg_med[0]
                                        }}</span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="control-label">Id Usuario SAHI</label>
                                        <input type="text" id="interface_id" v-model="registro.interface_id"
                                            class="form-control" placeholder="Id del usuario de SAHI">
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
                                        <input type="text" id="name" v-model="registro.name" class="form-control"
                                            placeholder="Ingrese aquí el los nombres del usaurio">
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
                                            <input type="email" id="email" name="email" v-model="registro.email"
                                                class="form-control" placeholder="Ingrese aquí E - Mail del usuario">
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
                                            <input type="password" id="password" name="password"
                                                v-model="registro.password" class="form-control"
                                                placeholder="Contraseña">
                                        </div>

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
                                                    <i class="ti-lock"></i></span>
                                            </div>
                                            <input type="password" id="confirmarPassword" name="confirmarPassword"
                                                v-model="registro.confirmarPassword" class="form-control"
                                                placeholder="Confirmar contraseña">
                                        </div>

                                        <span class="text-danger" v-if="errores.confirmarPassword">{{
                                            errores.confirmarPassword[0]
                                        }}</span>
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
                                                    <div v-for="itemRol in registro.rolesUsuario" :key="itemRol.id">
                                                        <div class="custom-control custom-checkbox">
                                                            <input type="checkbox" class="custom-control-input"
                                                                :id="itemRol.name" v-model="itemRol.checked">
                                                            <label class="custom-control-label" :for="itemRol.name">
                                                                {{ itemRol.name }}
                                                            </label>
                                                        </div>
                                                    </div>

                                                    <div class="custom-control custom-checkbox">
                                                        <input type="checkbox" class="custom-control-input"
                                                            id="transcrip_remoto" v-model="registro.transcrip_remoto">
                                                        <label class="custom-control-label" for="transcrip_remoto">
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
                                                    <div v-for="itemModulo in registro.modulos" :key="itemModulo.id">
                                                        <div class="custom-control custom-checkbox">
                                                            <input type="checkbox" class="custom-control-input"
                                                                :id="itemModulo.modulo" v-model="itemModulo.checked">
                                                            <label class="custom-control-label"
                                                                :for="itemModulo.modulo">
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
                                    <p class="text-info"><i class="fa fa-building"></i> Sedes Asignadas</p>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <div class="controls" style="max-height: 200px; overflow-y: auto;">
                                                    <div v-for="itemSede in registro.sedes" :key="itemSede.id">
                                                        <div class="custom-control custom-checkbox">
                                                            <input type="checkbox" class="custom-control-input"
                                                                :id="'sede_' + itemSede.id"
                                                                v-model="itemSede.checked">
                                                            <label class="custom-control-label"
                                                                :for="'sede_' + itemSede.id">
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
                                    <p class="text-info"><i class="fa fa-stethoscope"></i> Servicios de Cita</p>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <div class="controls" style="max-height: 200px; overflow-y: auto;">
                                                    <div v-for="itemSC in registro.serviciosCitas" :key="itemSC.id">
                                                        <div class="custom-control custom-checkbox">
                                                            <input type="checkbox" class="custom-control-input"
                                                                :id="'sc_' + itemSC.id"
                                                                v-model="itemSC.checked">
                                                            <label class="custom-control-label"
                                                                :for="'sc_' + itemSC.id">
                                                                {{ itemSC.nombre }}
                                                                <small class="text-muted"> ({{ itemSC.sede?.nom_sede }})</small>
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
                                <div class="fileupload btn btn-danger btn-rounded waves-effect waves-light">
                                    <span>
                                        <i class="ion-upload m-r-5"></i>Subir Firma
                                    </span>
                                    <input type="file" class="upload" @change="obtenerFirma">
                                </div>
                                <div class="fileupload btn btn-danger btn-rounded waves-effect waves-light">
                                    <span>
                                        <i class="ion-upload m-r-5"></i>Subir Avatar
                                    </span>
                                    <input type="file" class="upload" @change="obtenerAvatar">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <div class="checkbox checkbox-success">
                                            <input type="checkbox" id="estado" name="estado" v-model="registro.estado"
                                                :true-value="1" :false-value="0">
                                            <label for="estado"> Activo </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-actions mt-3">
                                <button type="button" class="btn btn-success m-r-5" @click="guardarRegistro()"
                                    v-if="!actualizar" :disabled="loadingGuardar">
                                    <i v-if="loadingGuardar" class="fa fa-spinner fa-spin"></i>
                                    <i v-else class="fa fa-check"></i> Guardar
                                </button>

                                <button type="button" class="btn btn-warning m-r-5" v-if="actualizar"
                                    @click="guardarRegistro()" :disabled="loadingGuardar">
                                    <i v-if="loadingGuardar" class="fa fa-spinner fa-spin"></i>
                                    <i v-else class="fa fa-pencil"></i> Actualizar
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
import { handleAxiosErrorToast, confirmAction, alertError, alertSuccess } from "@/helpers/alerts";
import { safeDestroyDataTable, safeInitDataTable } from "@/helpers/datatables.js";


export default {
    props: ['usuarioactual'],
    mounted() {
        this.ListarDatos();
        this.ListarModulos();
        this.ListarSedes();
        this.ListarServiciosCitas();
    },
    beforeUnmount() {
        this.destroyDataTable();
    },
    data() {
        return {
            registros: [],
            roles: [],
            id: null,
            registro: { num_docu: '', reg_med: '', name: '', email: '', password: '', estado: false, imagen_perfil: '', rolesUsuario: [], modulos: [], sedes: [], serviciosCitas: [], interface_id: null, transcrip_remoto: 0 },
            tituloModal: 'Nuevo registro',
            actualizar: false,
            avatar: null,
            firma: null,
            errores: {},
            dependencias: [], //Listo las dependencias
            modulos: [],
            loadingListar: false,
            loadingGuardar: false,
            loadingEstadoId: null,
            loadingEliminarId: null,
        };
    },
    methods: {
        initDataTable() {
            safeInitDataTable(this.$refs.dt || "#example23", {
                dom: "Bfrtip",
                buttons: ["copy", "csv", "excel", "pdf", "print"],
                retrieve: true,
            });
        },
        destroyDataTable() {
            safeDestroyDataTable(this.$refs.dt || "#example23");
        },
        async ListarDatos() {
            if (this.loadingListar) return;
            this.loadingListar = true;
            try {
                const res = await axios.get("/api/seguridad-usuarios");
                const resRoles = await axios.get("/api/seguridad-roles");

                this.destroyDataTable();

                this.registros = [];
                this.roles = [];
                this.registro.rolesUsuario = [];

                this.registros = res.data;
                this.roles = resRoles.data[0];
                this.listarRoles();

                this.$nextTick(() => {
                    this.initDataTable();
                });
            } catch (error) {
                handleAxiosErrorToast(error, "No fue posible listar usuarios.");
            } finally {
                this.loadingListar = false;
            }
        },
        listarRoles() {
            let me = this;

            me.roles.map(function (x, y) {
                me.registro.rolesUsuario.push({
                    "id": x.id,
                    "name": x.name,
                    "checked": false,
                });
            });
        },
        async guardarRegistro() {
            if (this.loadingGuardar) return;
            this.loadingGuardar = true;
            try {

                const formData = new FormData();
                formData.append('registro', JSON.stringify(this.registro));
                formData.append('id', this.id);
                formData.append('firma', this.firma);
                formData.append('avatar', this.avatar);

                if (this.actualizar === false) {

                    const res = await axios.post('/api/seguridad-usuarios', formData, {
                        headers: { "Content-Type": "multipart/form-data" }
                    });

                    if (res.status == 200) {

                        $.toast({
                            heading: 'Ok!!!',
                            text: res.data.message,
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
                } else {

                    const res = await axios.post('/api/seguridad/usuarios/update', formData, {
                        headers: { "Content-Type": "multipart/form-data" }
                    });

                    if (res.status == 200) {

                        $.toast({
                            heading: 'Ok!!!',
                            text: res.data.message,
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
                }
            } catch (error) {
                this.errores = error?.response?.data || {};
                handleAxiosErrorToast(error, "No fue posible guardar el usuario.");
            } finally {
                this.loadingGuardar = false;
            }
        },
        async cambiarEstado(estado, data = {}) {
            const ok = await confirmAction({
                title: "Confirmación",
                text: "Estas seguro de que desea " + ((estado == 0) ? ' activar' : 'desactivar') + ' el usaurio ' + data.name + '?',
                icon: "warning",
            });
            if (!ok) return;
            if (this.loadingEstadoId) return;

            this.loadingEstadoId = data.id;
            try {
                await axios.put('/api/seguridad-usuarios/estado/' + data.id, { id: data.id, estado: ((estado == 0) ? 1 : 0) });
                alertSuccess("Ok!", 'el usaurio ' + data.name + ' se ' + ((estado == 0) ? ' activo' : 'desactivo') + ' correctamente', "success");
                this.ListarDatos();
            } catch (error) {
                handleAxiosErrorToast(error, "No fue posible actualizar el estado.");
                alertError("Error", error?.response?.data?.message || "No fue posible actualizar el estado.", "error");
            } finally {
                this.loadingEstadoId = null;
            }
        },
        async elimnarRegistro(id) {
            const ok = await confirmAction({
                title: "Estas seguro de eliminar este usuario?",
                text: "No podrás recuperar este registro!",
                icon: "warning",
            });
            if (!ok) return;
            if (this.loadingEliminarId) return;

            this.loadingEliminarId = id;
            try {
                const res = await axios.delete('/api/seguridad-usuarios/' + id);
                alertSuccess("Eliminado!", res?.data?.message || "Registro eliminado.", "success");
                this.ListarDatos();
            } catch (err) {
                handleAxiosErrorToast(err, "No fue posible eliminar.");
                alertError("Error", err?.response?.data?.message || "No fue posible eliminar.", "error");
            } finally {
                this.loadingEliminarId = null;
            }
        },
        async ListarModulos() {
            try {
                const res = await axios.get("/api/config-modulos-listar");
                this.registro.modulos = res.data.map(modulo => ({
                    ...modulo,
                    checked: false,
                }));
            } catch (error) {
                handleAxiosErrorToast(error, "No fue posible listar módulos.");
            }
        },
        async ListarSedes() {
            try {
                const res = await axios.get("/api/config-sedes");
                const data = res.data?.data || res.data || [];
                this.registro.sedes = data.map(sede => ({
                    ...sede,
                    checked: false,
                }));
            } catch (error) {
                handleAxiosErrorToast(error, "No fue posible listar sedes.");
            }
        },
        async ListarServiciosCitas() {
            try {
                const res = await axios.get("/api/config-servicio-citas");
                this.registro.serviciosCitas = (res.data || []).map(sc => ({
                    ...sc,
                    checked: false,
                }));
            } catch (error) {
                handleAxiosErrorToast(error, "No fue posible listar servicios de cita.");
            }
        },
        obtenerFirma(event) {
            this.firma = event.target.files[0];
        },
        obtenerAvatar(event) {
            this.avatar = event.target.files[0];
        },
        mostrarRegistro(data = {}) {
            if (this.actualizar) {
                this.tituloModal = "Actualizar el registro: " + data.name;
                this.id = data.id;
                this.registro.num_docu = data.num_docu;
                this.registro.reg_med = data.reg_med;
                this.registro.name = data.name;
                this.registro.email = data.email;
                this.registro.interface_id = data.interface_id;
                this.registro.estado = Boolean(data.estado);


                // Inactavar los modulos
                this.registro.modulos.forEach(modulo => {
                    modulo.checked = false;
                });

                // Asigna el valor de `checked` a cada módulo
                data.modulos.forEach(modulo => {
                    let moduloActivo = this.registro.modulos.findIndex((x) => x.id == modulo.id);
                    this.registro.modulos[moduloActivo].checked = true;
                });

                // Inactivar sedes y marcar las del usuario
                this.registro.sedes.forEach(sede => {
                    sede.checked = false;
                });
                (data.sedes || []).forEach(sede => {
                    let idx = this.registro.sedes.findIndex((x) => x.id == sede.id);
                    if (idx !== -1) {
                        this.registro.sedes[idx].checked = true;
                    }
                });

                // Inactivar servicios de cita y marcar los del usuario
                this.registro.serviciosCitas.forEach(sc => {
                    sc.checked = false;
                });
                (data.serviciosCita || []).forEach(sc => {
                    let idx = this.registro.serviciosCitas.findIndex((x) => x.id == sc.id);
                    if (idx !== -1) {
                        this.registro.serviciosCitas[idx].checked = true;
                    }
                });

                /**
                 * Listo los los datos del usuario con sus respectivos roles
                 * Recorro los roles que estan en el Frond y los desactivo para
                 * marcar los roles del usuario a editar
                 */
                axios.get('/api/seguridad-usuarios/' + this.id).then(res => {

                    const RolesDelUsuario = res.data.RolesDeUsuario;
                    let me = this;

                    me.roles.forEach(function (roles, index) {
                        me.registro.rolesUsuario[index].checked = false;
                    });

                    RolesDelUsuario.forEach(function (RolesDelUsuario) {
                        let rolActivo = me.roles.findIndex((x) => x.id == RolesDelUsuario);
                        me.registro.rolesUsuario[rolActivo].checked = true;
                    });
                });
            } else {
                this.actualizar = false;
                this.tituloModal = "Nuevo registro";
                this.id = 0;
                this.registro.num_docu = "";
                this.registro.reg_med = "";
                this.registro.name = "";
                this.registro.email = "";
                this.registro.password = "";
                this.registro.estado = true;
                this.registro.interface_id = '';
                // Reset sedes y servicios de cita
                this.registro.sedes.forEach(sede => sede.checked = false);
                this.registro.serviciosCitas.forEach(sc => sc.checked = false);
            }

            this.btnCerralModalForm();
        },
        btnCerralModalForm() {
            $(".right-sidebar").slideDown(50);
            $(".right-sidebar").toggleClass("shw-rside");
        },
        limpiarFormulario() {
            this.actualizar = false;
            this.tituloModal = "Nuevo registro";
            this.id = 0;
            this.registro.num_docu = "";
            this.registro.reg_med = 0;
            this.registro.name = "";
            this.registro.email = "";
            this.registro.password = "";
            this.registro.estado = true;
        }
    }
}
</script>

<style scoped>
.right-sidebar {
    position: fixed;
    right: -55%;
    width: 55%;
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
    width: 55%;
    display: block;
}
</style>

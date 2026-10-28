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
                <button
                    type="button"
                    class="btn btn-sm btn-outline-secondary waves-effect waves-light m-r-5"
                    :disabled="loadingListar"
                    title="Actualizar listado de usuarios"
                    @click="ListarDatos()"
                >
                    <i class="fa fa-refresh mr-1" :class="{ 'fa-spin': loadingListar }"></i>
                    Refrescar
                </button>
                <button
                    type="button"
                    class="btn btn-info waves-effect waves-light btnDesplegarRigthSidebar"
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
                                                v-if="
                                                    item.firma &&
                                                    item.firma_disponible !== false &&
                                                    !firmaError[item.id]
                                                "
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
                                                v-else-if="
                                                    item.firma &&
                                                    (item.firma_disponible === false ||
                                                        firmaError[item.id])
                                                "
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
                                            <span
                                                v-if="!item.password_changed_at"
                                                class="badge badge-warning m-t-5 d-block"
                                                title="El usuario debe cambiar la contraseña en su próximo inicio de sesión"
                                            >
                                                <i class="fa fa-clock-o"></i> Cambio pendiente
                                            </span>
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
                                                title="Restablecer contraseña (obliga cambio al iniciar sesión)"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-info btn-sm m-r-5"
                                                @click="abrirModalReset(item)"
                                            >
                                                <i class="fa fa-key"></i>
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

                            <div class="row" v-if="actualizar && registro.password">
                                <div class="col-md-12">
                                    <div class="custom-control custom-checkbox mt-1 mb-2">
                                        <input
                                            type="checkbox"
                                            class="custom-control-input"
                                            id="forzar_cambio_password"
                                            v-model="registro.forzar_cambio_password"
                                        />
                                        <label
                                            class="custom-control-label"
                                            for="forzar_cambio_password"
                                        >
                                            Obligar al usuario a cambiar la contraseña en el próximo
                                            inicio de sesión
                                        </label>
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
                                    class="btn btn-success waves-effect waves-light m-r-5"
                                    @click="guardarRegistro()"
                                    v-if="!actualizar"
                                    :disabled="loadingGuardar"
                                    title="Guardar usuario"
                                >
                                    <i v-if="loadingGuardar" class="fa fa-spinner fa-spin mr-1"></i>
                                    <i v-else class="fa fa-check mr-1"></i> Guardar
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-warning waves-effect waves-light m-r-5"
                                    v-if="actualizar"
                                    @click="guardarRegistro()"
                                    :disabled="loadingGuardar"
                                    title="Actualizar usuario"
                                >
                                    <i v-if="loadingGuardar" class="fa fa-spinner fa-spin mr-1"></i>
                                    <i v-else class="fa fa-pencil mr-1"></i> Actualizar
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-inverse waves-effect waves-light"
                                    title="Cancelar"
                                    @click="btnCerrarModalForm()"
                                >
                                    Cancelar
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
            class="modal fade"
            :class="{ show: modalFirmaVisible }"
            :style="{ display: modalFirmaVisible ? 'block' : 'none' }"
            id="modalAmpliarFirma"
            tabindex="-1"
            role="dialog"
            aria-labelledby="modalAmpliarFirmaTitle"
            :aria-hidden="!modalFirmaVisible"
            @click.self="cerrarModalFirma"
        >
            <div class="modal-dialog modal-sm" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title" id="modalAmpliarFirmaTitle">
                            Firma — {{ firmaAmpliadaNombre }}
                        </h4>
                        <button
                            type="button"
                            class="close"
                            @click="cerrarModalFirma"
                            aria-label="Cerrar modal"
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
                            class="btn btn-default waves-effect"
                            @click="cerrarModalFirma"
                        >
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="modalFirmaVisible" class="modal-backdrop fade show"></div>

        <!-- ============================================================== -->
        <!-- Modal Restablecer Contraseña -->
        <!-- ============================================================== -->
        <div
            class="modal fade"
            :class="{ show: modalResetVisible }"
            :style="{ display: modalResetVisible ? 'block' : 'none' }"
            tabindex="-1"
            role="dialog"
            aria-labelledby="modalResetTitle"
            aria-hidden="true"
            @click.self="cerrarModalReset"
        >
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title" id="modalResetTitle">
                            <i class="fa fa-key text-info m-r-5"></i> Restablecer Contraseña
                        </h4>
                        <button
                            type="button"
                            class="close"
                            aria-label="Cerrar modal"
                            title="Cerrar (Esc)"
                            @click="cerrarModalReset"
                        >
                            &times;
                        </button>
                    </div>
                    <div class="modal-body" v-if="usuarioParaReset">
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle mr-1"></i>
                            Se asignará una contraseña temporal para
                            <strong>{{ usuarioParaReset.name }}</strong> ({{
                                usuarioParaReset.email
                            }}). Al iniciar sesión con esta contraseña, el sistema
                            <strong>obligará al usuario a cambiarla</strong> por su contraseña
                            personal.
                        </div>

                        <div class="form-group">
                            <label class="control-label"
                                >Nueva contraseña temporal <span class="text-danger">*</span></label
                            >
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="ti-lock"></i></span>
                                </div>
                                <input
                                    :type="mostrarPasswordReset ? 'text' : 'password'"
                                    class="form-control"
                                    placeholder="Ingrese o genere la contraseña temporal"
                                    v-model="resetForm.password"
                                    autocomplete="new-password"
                                />
                                <div class="input-group-append">
                                    <button
                                        type="button"
                                        class="btn btn-secondary waves-effect"
                                        title="Mostrar u ocultar contraseña"
                                        @click="mostrarPasswordReset = !mostrarPasswordReset"
                                    >
                                        <i
                                            :class="
                                                mostrarPasswordReset
                                                    ? 'fa fa-eye-slash'
                                                    : 'fa fa-eye'
                                            "
                                        ></i>
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-info waves-effect waves-light"
                                        title="Generar automáticamente contraseña segura que cumpla las políticas"
                                        @click="generarPasswordTemporal()"
                                    >
                                        <i class="fa fa-magic mr-1"></i> Generar
                                    </button>
                                </div>
                            </div>
                            <small class="text-muted d-block mt-1">
                                Mínimo 8 caracteres, con mayúscula, minúscula, número y símbolo.
                            </small>
                            <span class="text-danger" v-if="erroresReset.password">
                                {{ erroresReset.password[0] }}
                            </span>
                        </div>

                        <div class="form-group">
                            <label class="control-label"
                                >Confirmar contraseña temporal
                                <span class="text-danger">*</span></label
                            >
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="ti-lock"></i></span>
                                </div>
                                <input
                                    :type="mostrarPasswordReset ? 'text' : 'password'"
                                    class="form-control"
                                    placeholder="Confirme la contraseña temporal"
                                    v-model="resetForm.confirmarPassword"
                                    autocomplete="new-password"
                                />
                            </div>
                            <span class="text-danger" v-if="erroresReset.confirmarPassword">
                                {{ erroresReset.confirmarPassword[0] }}
                            </span>
                        </div>

                        <div class="custom-control custom-checkbox mt-2">
                            <input
                                type="checkbox"
                                class="custom-control-input"
                                id="chk_forzar_cambio"
                                v-model="resetForm.forzar_cambio"
                            />
                            <label class="custom-control-label" for="chk_forzar_cambio">
                                Obligar al usuario a cambiar la contraseña en su próximo inicio de
                                sesión
                            </label>
                        </div>

                        <div v-if="passwordCopiada" class="alert alert-success mt-3 mb-0">
                            <i class="fa fa-check mr-1"></i> ¡Contraseña copiada al portapapeles!
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-outline-secondary waves-effect"
                            v-if="resetForm.password"
                            @click="copiarPasswordAlPortapapeles()"
                            title="Copiar contraseña generada al portapapeles"
                        >
                            <i class="fa fa-copy mr-1"></i> Copiar contraseña
                        </button>
                        <button
                            type="button"
                            class="btn btn-default waves-effect"
                            @click="cerrarModalReset"
                            :disabled="loadingReset"
                        >
                            Cancelar
                        </button>
                        <button
                            type="button"
                            class="btn btn-info waves-effect waves-light"
                            @click="ejecutarResetPassword()"
                            :disabled="loadingReset"
                            title="Restablecer contraseña (Ctrl+S)"
                        >
                            <i v-if="loadingReset" class="fa fa-spinner fa-spin mr-1"></i>
                            <i v-else class="fa fa-check mr-1"></i> Restablecer
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="modalResetVisible" class="modal-backdrop fade show"></div>
    </div>
</template>
<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref } from "vue";
import {
    confirmAction,
    alertError,
    alertSuccess,
    swalSuccess,
    toastMessage,
} from "@/helpers/alerts";
import { safeDestroyDataTable, safeInitDataTable } from "@/helpers/datatables.js";
import { useKeyboardShortcuts } from "@/composables/useKeyboardShortcuts.js";
import axios from "axios";

defineProps({ usuarioactual: { type: Object, default: () => ({}) } });

const dt = ref(null);
const modalFirmaVisible = ref(false);
const modalResetVisible = ref(false);
const usuarioParaReset = ref(null);
const loadingReset = ref(false);
const mostrarPasswordReset = ref(false);
const passwordCopiada = ref(false);
const erroresReset = ref({});
const resetForm = ref({
    password: "",
    confirmarPassword: "",
    forzar_cambio: true,
});

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
    forzar_cambio_password: true,
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
    registro.value.forzar_cambio_password = true;

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

/**
 * Abre el modal para restablecer la contraseña del usuario seleccionado
 * generando una contraseña temporal inicial y preparando el estado reactivo.
 *
 * @param {Object} item Datos del usuario
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-27
 */
function abrirModalReset(item) {
    usuarioParaReset.value = item;
    resetForm.value = {
        password: "",
        confirmarPassword: "",
        forzar_cambio: true,
    };
    erroresReset.value = {};
    mostrarPasswordReset.value = false;
    passwordCopiada.value = false;
    modalResetVisible.value = true;
    generarPasswordTemporal();
}

/**
 * Cierra el modal de restablecimiento de contraseña.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-27
 */
function cerrarModalReset() {
    if (loadingReset.value) return;
    modalResetVisible.value = false;
    usuarioParaReset.value = null;
    erroresReset.value = {};
    passwordCopiada.value = false;
}

/**
 * Genera de forma pseudoaleatoria una contraseña temporal segura que cumple
 * estrictamente las políticas institucionales (longitud 10+, mayúscula, minúscula,
 * número y símbolo) excluyendo caracteres ambiguos.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-27
 */
function generarPasswordTemporal() {
    const mayusculas = "ABCDEFGHJKLMNPQRSTUVWXYZ";
    const minusculas = "abcdefghijkmnpqrstuvwxyz";
    const numeros = "23456789";
    const simbolos = "!@#$%&*_-";

    let pwd = "";
    pwd += mayusculas.charAt(Math.floor(Math.random() * mayusculas.length));
    pwd += minusculas.charAt(Math.floor(Math.random() * minusculas.length));
    pwd += numeros.charAt(Math.floor(Math.random() * numeros.length));
    pwd += simbolos.charAt(Math.floor(Math.random() * simbolos.length));

    const todo = mayusculas + minusculas + numeros + simbolos;
    for (let i = 0; i < 6; i++) {
        pwd += todo.charAt(Math.floor(Math.random() * todo.length));
    }

    const arr = pwd.split("");
    for (let i = arr.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [arr[i], arr[j]] = [arr[j], arr[i]];
    }
    const finalPwd = arr.join("");

    resetForm.value.password = finalPwd;
    resetForm.value.confirmarPassword = finalPwd;
    mostrarPasswordReset.value = true;
    erroresReset.value = {};
}

/**
 * Copia la contraseña temporal al portapapeles del navegador para que el
 * administrador pueda suministrársela de inmediato al usuario solicitante.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-27
 */
async function copiarPasswordAlPortapapeles() {
    if (!resetForm.value.password) return;
    try {
        if (navigator?.clipboard?.writeText) {
            await navigator.clipboard.writeText(resetForm.value.password);
        } else {
            const ta = document.createElement("textarea");
            ta.value = resetForm.value.password;
            document.body.appendChild(ta);
            ta.select();
            document.execCommand("copy");
            document.body.removeChild(ta);
        }
        passwordCopiada.value = true;
        setTimeout(() => {
            passwordCopiada.value = false;
        }, 3000);
        toastMessage({
            heading: "Copiado",
            text: "Contraseña temporal copiada al portapapeles.",
            icon: "info",
            position: "top-right",
            hideAfter: 2500,
        });
    } catch (_err) {
        // Fallback sin interrupción
    }
}

/**
 * Envía la petición POST al backend para restablecer la contraseña del usuario,
 * revocando sesiones previas y forzando el cambio en el próximo inicio de sesión.
 *
 * Flujo del proceso:
 * 1. Valida presencia y coincidencia de contraseña temporal.
 * 2. Emite la petición a /api/seguridad-usuarios/{id}/restablecer-password.
 * 3. En caso de éxito, cierra el modal, refresca la tabla y notifica mediante SweetAlert modal.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-27
 */
async function ejecutarResetPassword() {
    if (!usuarioParaReset.value || loadingReset.value) return;
    erroresReset.value = {};

    if (!resetForm.value.password) {
        erroresReset.value = { password: ["La contraseña temporal es obligatoria."] };
        return;
    }
    if (resetForm.value.password !== resetForm.value.confirmarPassword) {
        erroresReset.value = {
            confirmarPassword: ["La confirmación de la contraseña no coincide."],
        };
        return;
    }

    loadingReset.value = true;
    try {
        const payload = {
            password: resetForm.value.password,
            confirmarPassword: resetForm.value.confirmarPassword,
            forzar_cambio: resetForm.value.forzar_cambio,
        };

        await axios.post(
            `/api/seguridad-usuarios/${usuarioParaReset.value.id}/restablecer-password`,
            payload
        );

        const passwordUsada = resetForm.value.password;
        const nombreUsuario = usuarioParaReset.value.name;

        cerrarModalReset();
        await ListarDatos();

        swalSuccess(
            "¡Contraseña Restablecida!",
            `Se ha asignado la contraseña temporal (${passwordUsada}) a ${nombreUsuario}. Al iniciar sesión, el sistema le obligará a crear una nueva contraseña.`
        );
    } catch (err) {
        erroresReset.value = err?.response?.data?.errors || {};
        if (err?.response?.status !== 422) {
            alertError(
                "Error",
                err?.response?.data?.message || "No fue posible restablecer la contraseña.",
                "error"
            );
        }
    } finally {
        loadingReset.value = false;
    }
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
    registro.value.forzar_cambio_password = true;
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
        if (
            !sidebarOpen.value &&
            !modalFirmaVisible.value &&
            !modalResetVisible.value &&
            !loadingListar.value
        ) {
            ListarDatos();
        }
    },
    onSave: () => {
        if (modalResetVisible.value && !loadingReset.value) {
            ejecutarResetPassword();
        } else if (sidebarOpen.value && !loadingGuardar.value) {
            guardarRegistro();
        }
    },
    onEscape: () => {
        if (modalResetVisible.value) {
            cerrarModalReset();
        } else if (modalFirmaVisible.value) {
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
    background: rgba(0, 0, 0, 0.5);
    z-index: 1050;
    transition: opacity 0.25s ease;
}
</style>

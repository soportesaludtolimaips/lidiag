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
                    <li class="breadcrumb-item active">WhatsApp Bot</li>
                </ol>
            </div>
            <div class="col-md-7 align-self-center text-right d-none d-md-block">
                <button type="button" class="btn btn-info btnDesplegarRigthSidebar" v-if="tabActivo !== 'productos'"
                    @click="actualizar = false; mostrarRegistro()">
                    <i class="fa fa-plus-circle m-r-5"></i> Nuevo registro
                </button>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <ul class="nav nav-tabs mb-3" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link" :class="{ active: tabActivo === 'numeros' }" href="#"
                                    @click.prevent="cambiarTab('numeros')">
                                    <i class="fa fa-phone"></i> Números WhatsApp
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" :class="{ active: tabActivo === 'saludos' }" href="#"
                                    @click.prevent="cambiarTab('saludos')">
                                    <i class="fa fa-comment"></i> Saludos del Bot
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" :class="{ active: tabActivo === 'productos' }" href="#"
                                    @click.prevent="cambiarTab('productos')">
                                    <i class="fa fa-cubes"></i> Productos por Servicio
                                </a>
                            </li>
                        </ul>

                        <div v-if="tabActivo === 'numeros'">
                            <h4 class="card-title">Números WhatsApp por sede</h4>
                            <h6 class="card-subtitle">Administración de los números de teléfono asociados a cada sede
                                para el envío de mensajes.</h6>

                            <div v-if="loadingNumeros" class="text-center m-t-40">
                                <i class="fa fa-spinner fa-spin fa-2x"></i>
                                <p class="text-muted m-t-10">Cargando números...</p>
                            </div>

                            <div v-else-if="errorNumeros" class="alert alert-danger m-t-20">
                                <i class="fa fa-exclamation-triangle"></i>
                                {{ errorNumeros }}
                            </div>

                            <div v-else-if="registrosNumeros.length === 0" class="text-center m-t-40">
                                <p class="text-muted">No hay números WhatsApp configurados. Cree un nuevo registro para
                                    comenzar.</p>
                            </div>

                            <div class="table-responsive m-t-40" v-else>
                                <table id="tblWhatsappNumbers" ref="dtNumeros"
                                    class="display nowrap table table-hover table-striped table-bordered"
                                    cellspacing="0" width="100%">
                                    <thead>
                                        <tr>
                                            <th>Sede</th>
                                            <th>Phone Number ID</th>
                                            <th>Número</th>
                                            <th>Descripción</th>
                                            <th>Activo</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="item in registrosNumeros" :key="item.id">
                                            <td>{{ item.sede ? item.sede.nom_sede : '—' }}</td>
                                            <td>{{ item.phone_number_id }}</td>
                                            <td>{{ item.numero }}</td>
                                            <td>{{ item.descripcion || '—' }}</td>
                                            <td>
                                                <span class="label"
                                                    :class="item.activo ? 'label-success' : 'label-danger'">
                                                    {{ item.activo ? 'Activo' : 'Inactivo' }}
                                                </span>
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

                        <div v-if="tabActivo === 'saludos'">
                            <h4 class="card-title">Saludos del Bot</h4>
                            <h6 class="card-subtitle">Mensajes de bienvenida personalizados por sede y servicio para el
                                bot de WhatsApp.</h6>

                            <div v-if="loadingSaludos" class="text-center m-t-40">
                                <i class="fa fa-spinner fa-spin fa-2x"></i>
                                <p class="text-muted m-t-10">Cargando saludos...</p>
                            </div>

                            <div v-else-if="errorSaludos" class="alert alert-danger m-t-20">
                                <i class="fa fa-exclamation-triangle"></i>
                                {{ errorSaludos }}
                            </div>

                            <div v-else-if="registrosSaludos.length === 0" class="text-center m-t-40">
                                <p class="text-muted">No hay saludos configurados. Cree un nuevo registro para comenzar.
                                </p>
                            </div>

                            <div class="table-responsive m-t-40" v-else>
                                <table id="tblWhatsappSaludos" ref="dtSaludos"
                                    class="display nowrap table table-hover table-striped table-bordered"
                                    cellspacing="0" width="100%">
                                    <thead>
                                        <tr>
                                            <th>Sede</th>
                                            <th>Servicio</th>
                                            <th>Mensaje de bienvenida</th>
                                            <th>Activo</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="item in registrosSaludos" :key="item.id">
                                            <td>{{ item.sede ? item.sede.nom_sede : 'Todas las sedes' }}</td>
                                            <td>{{ item.servicio ? item.servicio.nombre : 'Todos los servicios' }}</td>
                                            <td class="text-wrap" style="max-width: 400px">
                                                {{ item.mensaje_bienvenida.substring(0, 50) }}{{
                                                    item.mensaje_bienvenida.length > 50 ? '...' : '' }}
                                            </td>
                                            <td>
                                                <span class="label"
                                                    :class="item.activo ? 'label-success' : 'label-danger'">
                                                    {{ item.activo ? 'Activo' : 'Inactivo' }}
                                                </span>
                                            </td>
                                            <td class="text-nowrap">
                                                <button type="button"
                                                    class="btn waves-effect waves-light btn-rounded btn-outline-warning btn-sm m-r-5"
                                                    @click="actualizar = true; mostrarRegistro(item)">
                                                    <i class="fa fa-pencil"></i>
                                                </button>
                                                <button type="button"
                                                    class="btn waves-effect waves-light btn-rounded btn-outline-info btn-sm m-r-5"
                                                    @click="abrirProbarEnvio()">
                                                    <i class="fa fa-send"></i>
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

                        <div v-if="tabActivo === 'productos'" class="m-t-20">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-body">
                                            <h4 class="card-title">Servicios</h4>
                                            <h6 class="card-subtitle text-info">Seleccione un servicio para administrar
                                                sus productos.</h6>

                                            <div v-if="loadingServiciosProductos" class="text-center m-t-20">
                                                <i class="fa fa-spinner fa-spin fa-2x"></i>
                                                <p class="text-muted m-t-10">Cargando servicios...</p>
                                            </div>

                                            <div v-else-if="errorServiciosProductos" class="alert alert-danger m-t-20">
                                                <i class="fa fa-exclamation-triangle"></i>
                                                {{ errorServiciosProductos }}
                                            </div>

                                            <div v-else-if="serviciosProductos.length === 0" class="text-center m-t-20">
                                                <p class="text-muted">No hay servicios disponibles. Contacte al
                                                    administrador para asignarle servicios.</p>
                                            </div>

                                            <div class="list-group m-t-10" v-else>
                                                <button v-for="servicio in serviciosProductos" :key="servicio.id"
                                                    type="button" class="list-group-item list-group-item-action"
                                                    :class="servicioSeleccionado === servicio.id ? 'active' : ''"
                                                    @click="seleccionarServicio(servicio)">
                                                    <div
                                                        class="d-flex justify-content-between align-items-center w-100">
                                                        <div>
                                                            <div>{{ servicio.nombre }}</div>
                                                            <small class="d-block"
                                                                style="font-size: 11px; opacity: 0.75;">
                                                                <i class="fa fa-map-marker"></i> {{
                                                                    servicio.sede?.nom_sede || 'Sin sede' }}
                                                            </small>
                                                        </div>
                                                        <span class="badge badge-danger badge-pill"
                                                            v-if="servicio.productos_count">
                                                            {{ servicio.productos_count }}
                                                        </span>
                                                    </div>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-8">
                                    <div class="card" v-if="servicioSeleccionado">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h4 class="card-title">{{ servicioActual?.nombre }}</h4>
                                                    <h6 class="card-subtitle text-info">Productos vinculados a este
                                                        servicio</h6>
                                                </div>
                                                <button type="button" class="btn btn-info"
                                                    @click="mostrarModalAgregar()">
                                                    <i class="fa fa-plus-circle m-r-5"></i> Agregar Producto
                                                </button>
                                            </div>
                                            <div class="table-responsive m-t-20">
                                                <table class="table table-bordered table-striped">
                                                    <thead>
                                                        <tr>
                                                            <th>Código</th>
                                                            <th>Producto</th>
                                                            <th>Estado</th>
                                                            <th>Acciones</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr v-for="item in productosVinculados" :key="item.id">
                                                            <td>{{ item.producto?.cod_cups }}</td>
                                                            <td>{{ item.producto?.nom_produc }}</td>
                                                            <td>
                                                                <span class="label label-success"
                                                                    v-if="item.estado">Activo</span>
                                                                <span class="label label-danger" v-else>Inactivo</span>
                                                            </td>
                                                            <td class="text-nowrap">
                                                                <button type="button"
                                                                    class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm"
                                                                    @click="eliminarVinculo(item.id)">
                                                                    <i class="fa fa-trash"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                        <tr v-if="productosVinculados.length === 0">
                                                            <td colspan="4" class="text-center text-muted">
                                                                No hay productos vinculados a este servicio.
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card" v-else>
                                        <div class="card-body text-center">
                                            <p class="text-muted">Seleccione un servicio de la lista para ver sus
                                                productos.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
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
                            <template v-if="tabActivo === 'numeros'">
                                <div class="row p-t-20">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Sede <span class="text-danger">*</span></label>
                                            <select v-model="form.sede_id" class="form-control"
                                                :class="{ 'is-invalid': errores.sede_id }">
                                                <option value="">Seleccione una sede</option>
                                                <option v-for="s in sedes" :key="s.id" :value="s.id">
                                                    {{ s.nom_sede }}
                                                </option>
                                            </select>
                                            <span class="text-danger" v-if="errores.sede_id">{{ errores.sede_id[0]
                                            }}</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Phone Number ID <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" v-model="form.phone_number_id" class="form-control"
                                                :class="{ 'is-invalid': errores.phone_number_id }"
                                                placeholder="ID de Meta">
                                            <span class="text-danger" v-if="errores.phone_number_id">{{
                                                errores.phone_number_id[0] }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="control-label">Número <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" v-model="form.numero" class="form-control"
                                                :class="{ 'is-invalid': errores.numero }" placeholder="573001234567">
                                            <span class="text-danger" v-if="errores.numero">{{ errores.numero[0]
                                            }}</span>
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="form-group">
                                            <label class="control-label">Descripción</label>
                                            <input type="text" v-model="form.descripcion" class="form-control"
                                                placeholder="Ej: Ecografías">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <div class="checkbox checkbox-success m-t-25">
                                                <input type="checkbox" id="form_activo_numero" v-model="form.activo"
                                                    :true-value="1" :false-value="0">
                                                <label for="form_activo_numero"> Activo </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <template v-if="tabActivo === 'saludos'">
                                <div class="row p-t-20">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Sede <small
                                                    class="text-muted">(opcional)</small></label>
                                            <select v-model="form.sede_id" class="form-control"
                                                :class="{ 'is-invalid': errores.sede_id }">
                                                <option value="">Todas las sedes</option>
                                                <option v-for="s in sedes" :key="s.id" :value="s.id">
                                                    {{ s.nom_sede }}
                                                </option>
                                            </select>
                                            <span class="text-danger" v-if="errores.sede_id">{{ errores.sede_id[0]
                                            }}</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Servicio <small
                                                    class="text-muted">(opcional)</small></label>
                                            <select v-model="form.servicio_cita_id" class="form-control"
                                                :class="{ 'is-invalid': errores.servicio_cita_id }">
                                                <option value="">Todos los servicios</option>
                                                <option v-for="s in servicios" :key="s.id" :value="s.id">
                                                    {{ s.nombre + (s.sede ? ' — ' + s.sede.nom_sede : '') }}
                                                </option>
                                            </select>
                                            <span class="text-danger" v-if="errores.servicio_cita_id">{{
                                                errores.servicio_cita_id[0] }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="control-label">
                                                Mensaje de bienvenida
                                                <button type="button" class="btn btn-sm btn-outline-info m-l-10"
                                                    @click="mostrarPreviewSaludo" :disabled="!form.mensaje_bienvenida">
                                                    <i class="fa fa-eye"></i> Vista previa
                                                </button>
                                            </label>
                                            <textarea v-model="form.mensaje_bienvenida" class="form-control" rows="5"
                                                :class="{ 'is-invalid': errores.mensaje_bienvenida }"
                                                placeholder="Escriba el mensaje de bienvenida que verá el paciente al iniciar el chat..."></textarea>
                                            <span class="text-danger" v-if="errores.mensaje_bienvenida">{{
                                                errores.mensaje_bienvenida[0] }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <div class="checkbox checkbox-success">
                                                <input type="checkbox" id="form_activo_saludo" v-model="form.activo"
                                                    :true-value="1" :false-value="0">
                                                <label for="form_activo_saludo"> Activo </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <template v-if="tabActivo === 'productos'">
                                <div class="row p-t-20">
                                    <div class="col-md-12">
                                        <div class="form-group" id="producto-parent">
                                            <label class="control-label">Producto <span
                                                    class="text-danger">*</span></label>
                                            <Select2 :value="productoSeleccionado"
                                                :options="productosDisponiblesOptions" width="100%" :allow-clear="false"
                                                :dropdown-parent-selector="'#producto-parent'"
                                                @input="onProductoSelect" />
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="control-label">Recomendación</label>
                                            <textarea v-model="recomendacion" class="form-control" rows="8"
                                                placeholder="Texto de recomendación para el paciente. Use saltos de línea si es necesario."></textarea>
                                            <small class="text-muted">
                                                Variables disponibles:
                                                <code>${num_docu_paciente}</code>
                                                <code>${nom_paciente}</code>
                                                <code>${cod_cups}</code>
                                                <code>${nom_produc}</code>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <div class="form-actions mt-3" v-if="tabActivo !== 'productos'">
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

                                <button type="button" class="btn btn-inverse"
                                    @click="cerrarFormSidebar()">Cancel</button>
                            </div>

                            <div class="form-actions mt-3" v-if="tabActivo === 'productos'">
                                <button type="button" class="btn btn-inverse" @click="cerrarModal()">Cerrar</button>
                                <button type="button" class="btn btn-success m-l-5" @click="agregarProducto()"
                                    :disabled="loadingGuardar || !productoSeleccionado">
                                    <i class="fa" :class="loadingGuardar ? 'fa-spinner fa-spin' : 'fa-save'"></i>
                                    {{ loadingGuardar ? 'Guardando...' : 'Guardar' }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- Vista previa del saludo -->
        <div class="modal fade" id="modalPreviewSaludo" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Vista previa del mensaje</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="card card-body bg-light">
                            <p style="white-space: pre-wrap;">{{ previewMensaje }}</p>
                        </div>
                        <small class="text-muted">
                            Variables:
                            <code>${num_docu_paciente}</code> → <strong>1234567890</strong>,
                            <code>${nom_paciente}</code> → <strong>Paciente</strong>,
                            <code>${cod_cups}</code> → <strong>CUPS001</strong>,
                            <code>${nom_produc}</code> → <strong>Producto</strong>
                        </small>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-inverse" data-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal probar envío WhatsApp -->
        <div class="modal fade" id="modalProbarEnvio" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Probar envío de WhatsApp</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="control-label">Número de teléfono <span class="text-danger">*</span></label>
                            <input type="text" v-model="testPhoneNumber" class="form-control"
                                placeholder="573001234567" />
                            <small class="text-muted">Ingrese el número con código de país (Ej: 573001234567)</small>
                        </div>
                        <div v-if="testResultado" class="m-t-10">
                            <div class="alert" :class="testResultado.ok ? 'alert-success' : 'alert-danger'">
                                <i class="fa"
                                    :class="testResultado.ok ? 'fa-check-circle' : 'fa-exclamation-triangle'"></i>
                                {{ testResultado.mensaje }}
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-inverse" data-dismiss="modal"
                            @click="testResultado = null">Cerrar</button>
                        <button type="button" class="btn btn-info" @click="probarEnvio()"
                            :disabled="!testPhoneNumber || loadingTestEnvio">
                            <i class="fa" :class="loadingTestEnvio ? 'fa-spinner fa-spin' : 'fa-send'"></i>
                            {{ loadingTestEnvio ? 'Enviando...' : 'Enviar' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { safeDestroyDataTable, safeInitDataTable } from "@/helpers/datatables.js";
import { alertError, alertSuccess, confirmAction, handleAxiosErrorToast } from "@/helpers/alerts.js";
import Select2 from "@/components/shared/Select2.vue";

const INIT_FORM_NUMEROS = {
    sede_id: '',
    phone_number_id: '',
    numero: '',
    descripcion: '',
    activo: 1,
};

const INIT_FORM_SALUDOS = {
    sede_id: '',
    servicio_cita_id: '',
    mensaje_bienvenida: '',
    activo: 1,
};

export default {
    components: { Select2 },
    mounted() {
        this.listarNumeros();
        this.listarSaludos();
        this.listarSedes();
        this.listarServicios();
        this.cargarServiciosProductos();
    },
    beforeUnmount() {
        safeDestroyDataTable(this.$refs.dtNumeros || "#tblWhatsappNumbers");
        safeDestroyDataTable(this.$refs.dtSaludos || "#tblWhatsappSaludos");
    },
    data() {
        return {
            tabActivo: 'numeros',
            id: 0,
            registrosNumeros: [],
            registrosSaludos: [],
            sedes: [],
            servicios: [],
            form: { ...INIT_FORM_NUMEROS },
            tituloModal: 'Nuevo registro',
            actualizar: false,
            errores: {},
            loadingNumeros: false,
            loadingSaludos: false,
            errorNumeros: '',
            errorSaludos: '',
            loadingGuardar: false,
            loadingEliminarId: null,

            serviciosProductos: [],
            loadingServiciosProductos: false,
            errorServiciosProductos: '',
            servicioSeleccionado: null,
            servicioActual: null,
            productosVinculados: [],
            productosDisponibles: [],
            productoSeleccionado: '',
            recomendacion: '',
            loadingAgregarProducto: false,
            previewMensaje: '',
            testPhoneNumber: '',
            testResultado: null,
            loadingTestEnvio: false,
        };
    },
    computed: {
        productosDisponiblesOptions() {
            return (this.productosDisponibles || []).map(function (p) {
                return {
                    id: String(p.id),
                    cod_cups: p.cod_cups,
                    nom_produc: p.nom_produc,
                    recomendacion_producto: p.recomendacion_producto,
                };
            });
        },
    },
    methods: {
        cambiarTab(tab) {
            this.tabActivo = tab;
            this.errores = {};
            if (tab === 'productos') {
                this.cargarServiciosProductos();
            }
            this.$nextTick(() => {
                if (tab === 'numeros') {
                    safeInitDataTable(this.$refs.dtNumeros || "#tblWhatsappNumbers", {
                        dom: 'Bfrtip',
                        buttons: ['copy', 'csv', 'excel', 'pdf', 'print']
                    });
                } else if (tab === 'saludos') {
                    safeInitDataTable(this.$refs.dtSaludos || "#tblWhatsappSaludos", {
                        dom: 'Bfrtip',
                        buttons: ['copy', 'csv', 'excel', 'pdf', 'print']
                    });
                }
            });
        },
        async listarNumeros() {
            if (this.loadingNumeros) return;
            this.loadingNumeros = true;
            this.errorNumeros = '';
            try {
                const res = await axios.get("/api/config-sede-whatsapp-numbers");
                safeDestroyDataTable(this.$refs.dtNumeros || "#tblWhatsappNumbers");
                this.registrosNumeros = res.data;
                this.$nextTick(() => {
                    if (this.tabActivo === 'numeros') {
                        safeInitDataTable(this.$refs.dtNumeros || "#tblWhatsappNumbers", {
                            dom: 'Bfrtip',
                            buttons: ['copy', 'csv', 'excel', 'pdf', 'print']
                        });
                    }
                });
            } catch (error) {
                this.errorNumeros = error?.response?.data?.message || "No fue posible cargar los números WhatsApp.";
                handleAxiosErrorToast(error, "No fue posible listar números WhatsApp.");
                this.registrosNumeros = [];
            } finally {
                this.loadingNumeros = false;
            }
        },
        async listarSaludos() {
            if (this.loadingSaludos) return;
            this.loadingSaludos = true;
            this.errorSaludos = '';
            try {
                const res = await axios.get("/api/config-whatsapp");
                safeDestroyDataTable(this.$refs.dtSaludos || "#tblWhatsappSaludos");
                this.registrosSaludos = res.data;
                this.$nextTick(() => {
                    if (this.tabActivo === 'saludos') {
                        safeInitDataTable(this.$refs.dtSaludos || "#tblWhatsappSaludos", {
                            dom: 'Bfrtip',
                            buttons: ['copy', 'csv', 'excel', 'pdf', 'print']
                        });
                    }
                });
            } catch (error) {
                this.errorSaludos = error?.response?.data?.message || "No fue posible cargar los saludos.";
                handleAxiosErrorToast(error, "No fue posible listar saludos.");
                this.registrosSaludos = [];
            } finally {
                this.loadingSaludos = false;
            }
        },
        async listarSedes() {
            try {
                const res = await axios.get("/api/config-sedes/listar/sedes/activas");
                this.sedes = res.data;
            } catch (error) {
                console.error(error);
            }
        },
        async listarServicios() {
            try {
                const res = await axios.get("/api/config-servicio-citas");
                this.servicios = res.data;
            } catch (error) {
                console.error(error);
            }
        },
        async guardarRegistro() {
            if (this.loadingGuardar) return;
            this.errores = {};

            if (this.tabActivo === 'numeros') {
                const errs = {};
                if (!this.form.sede_id) errs.sede_id = ['La sede es obligatoria.'];
                if (!this.form.phone_number_id) errs.phone_number_id = ['El Phone Number ID es obligatorio.'];
                if (!this.form.numero) errs.numero = ['El número es obligatorio.'];
                if (Object.keys(errs).length > 0) {
                    this.errores = errs;
                    return;
                }
            } else if (this.tabActivo === 'saludos') {
                const errs = {};
                if (!this.form.mensaje_bienvenida || !this.form.mensaje_bienvenida.trim()) {
                    errs.mensaje_bienvenida = ['El mensaje de bienvenida es obligatorio.'];
                }
                if (Object.keys(errs).length > 0) {
                    this.errores = errs;
                    return;
                }
            }

            this.loadingGuardar = true;
            try {
                const payload = {
                    ...this.form,
                    activo: this.form.activo === 1 || this.form.activo === true,
                };

                const esNumero = this.tabActivo === 'numeros';
                const baseUrl = esNumero ? '/api/config-sede-whatsapp-numbers' : '/api/config-whatsapp';

                if (this.actualizar === false) {
                    const res = await axios.post(baseUrl, payload);
                    if (res.status == 201 || res.status == 200) {
                        this.recargarTabActivo();
                        $.toast({
                            heading: 'Ok!!!',
                            text: 'Registro guardado correctamente.',
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
                    const res = await axios.put(`${baseUrl}/${this.id}`, payload);
                    if (res.status == 200) {
                        this.recargarTabActivo();
                        $.toast({
                            heading: 'Ok!!!',
                            text: 'Registro actualizado correctamente.',
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
                handleAxiosErrorToast(error, "No fue posible guardar el registro.");
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
                const baseUrl = this.tabActivo === 'numeros'
                    ? '/api/config-sede-whatsapp-numbers'
                    : '/api/config-whatsapp';
                const response = await axios.delete(`${baseUrl}/${id}`);
                alertSuccess("Ok!", response?.data?.message || "Registro eliminado.", "success");
                this.recargarTabActivo();
            } catch (error) {
                handleAxiosErrorToast(error, "No fue posible eliminar el registro.");
                alertError("Error", "No fue posible eliminar el registro.", "error");
            } finally {
                this.loadingEliminarId = null;
            }
        },
        mostrarRegistro(data = {}) {
            if (this.tabActivo === 'productos') return;
            if (this.tabActivo === 'numeros') {
                if (this.actualizar) {
                    this.tituloModal = "Actualizar número WhatsApp";
                    this.id = data.id;
                    this.form = {
                        sede_id: data.sede_id || '',
                        phone_number_id: data.phone_number_id || '',
                        numero: data.numero || '',
                        descripcion: data.descripcion || '',
                        activo: data.activo ? 1 : 0,
                    };
                } else {
                    this.tituloModal = "Nuevo número WhatsApp";
                    this.id = 0;
                    this.form = { ...INIT_FORM_NUMEROS };
                }
            } else {
                if (this.actualizar) {
                    this.tituloModal = "Actualizar saludo: " + (data.sede?.nom_sede || 'Todas las sedes');
                    this.id = data.id;
                    this.form = {
                        sede_id: data.sede_id || '',
                        servicio_cita_id: data.servicio_cita_id || '',
                        mensaje_bienvenida: data.mensaje_bienvenida || '',
                        activo: data.activo ? 1 : 0,
                    };
                } else {
                    this.tituloModal = "Nuevo saludo";
                    this.id = 0;
                    this.form = { ...INIT_FORM_SALUDOS };
                }
            }
            this.btnCerrarModalForm();
        },
        recargarTabActivo() {
            if (this.tabActivo === 'numeros') {
                this.listarNumeros();
            } else {
                this.listarSaludos();
            }
        },
        limpiar() {
            this.actualizar = false;
            this.tituloModal = "Nuevo registro";
            this.id = 0;
            if (this.tabActivo === 'numeros') {
                this.form = { ...INIT_FORM_NUMEROS };
            } else {
                this.form = { ...INIT_FORM_SALUDOS };
            }
        },
        btnCerrarModalForm() {
            $(".right-sidebar").slideDown(50);
            $(".right-sidebar").addClass("shw-rside");
        },
        cerrarFormSidebar() {
            $(".right-sidebar").slideUp(50);
            $(".right-sidebar").removeClass("shw-rside");
        },

        async cargarServiciosProductos() {
            this.loadingServiciosProductos = true;
            this.errorServiciosProductos = '';
            try {
                const res = await axios.get('/api/config-servicio-citas');
                this.serviciosProductos = res.data;
                if (this.serviciosProductos.length > 0) {
                    this.seleccionarServicio(this.serviciosProductos[0]);
                }
            } catch (error) {
                const msg = error?.response?.data?.message || error.message || 'Error al cargar servicios';
                this.errorServiciosProductos = msg;
                this.serviciosProductos = [];
            } finally {
                this.loadingServiciosProductos = false;
            }
        },

        seleccionarServicio(servicio) {
            this.servicioSeleccionado = servicio.id;
            this.servicioActual = servicio;
            this.cargarProductosVinculados();
        },

        async cargarProductosVinculados() {
            if (!this.servicioSeleccionado) return;
            try {
                const res = await axios.get('/api/servicio-producto', {
                    params: { servicio_cita_id: this.servicioSeleccionado },
                });
                this.productosVinculados = res.data;
            } catch (error) {
                console.error('Error al cargar productos vinculados:', error);
            }
        },

        async mostrarModalAgregar() {
            this.tituloModal = 'Agregar producto a: ' + (this.servicioActual?.nombre || '');
            this.productoSeleccionado = '';
            this.recomendacion = '';
            this.loadingAgregarProducto = false;
            try {
                const res = await axios.get('/api/servicio-producto/productos-no-vinculados', {
                    params: { servicio_cita_id: this.servicioSeleccionado },
                });
                this.productosDisponibles = res.data;
                this.abrirModal();
            } catch (error) {
                console.error('Error al cargar productos disponibles:', error);
            }
        },

        onProductoSelect(value) {
            const id = Array.isArray(value) && value.length > 0 ? (value[0] && value[0].id) : (value && value.id);
            this.productoSeleccionado = id || '';
            const prod = Array.isArray(value) && value.length > 0 ? value[0] : value;
            if (prod && prod.recomendacion_producto) {
                this.recomendacion = prod.recomendacion_producto.recomendacion || '';
            } else {
                const encontrado = this.productosDisponibles.find(function (p) { return String(p.id) === String(id); });
                this.recomendacion = (encontrado && encontrado.recomendacion_producto && encontrado.recomendacion_producto.recomendacion) || '';
            }
        },

        abrirModal() {
            $(".right-sidebar").slideDown(50);
            $(".right-sidebar").addClass("shw-rside");
        },

        cerrarModal() {
            $(".right-sidebar").removeClass("shw-rside");
        },

        async agregarProducto() {
            if (!this.productoSeleccionado) return;
            this.loadingGuardar = true;
            try {
                await axios.post('/api/servicio-producto', {
                    servicio_cita_id: this.servicioSeleccionado,
                    producto_id: this.productoSeleccionado,
                });
                if (this.recomendacion && this.recomendacion.trim()) {
                    await axios.post('/api/config-recomendacion-productos', {
                        producto_id: this.productoSeleccionado,
                        recomendacion: this.recomendacion,
                    });
                }
                alertSuccess('Éxito', 'Producto agregado correctamente');
                this.cerrarModal();
                this.cargarProductosVinculados();
                this.cargarServiciosProductos();
            } catch (error) {
                handleAxiosErrorToast(error, 'No fue posible agregar el producto.');
            } finally {
                this.loadingGuardar = false;
            }
        },

        async eliminarVinculo(id) {
            const ok = await confirmAction({
                title: '¿Está seguro?',
                text: 'El producto se desvinculará del servicio.',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
            });
            if (!ok) return;
            try {
                await axios.delete('/api/servicio-producto/' + id);
                alertSuccess('Éxito', 'Producto desvinculado');
                this.cargarProductosVinculados();
                this.cargarServiciosProductos();
            } catch (error) {
                handleAxiosErrorToast(error, 'No fue posible eliminar el producto.');
            }
        },

        abrirProbarEnvio() {
            this.testPhoneNumber = '';
            this.testResultado = null;
            $('#modalProbarEnvio').modal('show');
        },

        mostrarPreviewSaludo() {
            if (!this.form.mensaje_bienvenida) return;
            this.previewMensaje = this.form.mensaje_bienvenida
                .replace(/\$\{num_docu_paciente\}/g, '1234567890')
                .replace(/\$\{nom_paciente\}/g, 'Paciente')
                .replace(/\$\{cod_cups\}/g, 'CUPS001')
                .replace(/\$\{nom_produc\}/g, 'Nombre del producto');
            $('#modalPreviewSaludo').modal('show');
        },

        async probarEnvio() {
            if (!this.testPhoneNumber || this.loadingTestEnvio) return;
            this.loadingTestEnvio = true;
            this.testResultado = null;
            try {
                const res = await axios.post('/api/citas/test/enviar', {
                    phone: this.testPhoneNumber,
                });
                this.testResultado = {
                    ok: true,
                    mensaje: res.data?.message || 'Mensaje enviado correctamente.',
                };
            } catch (error) {
                const msg = error?.response?.data?.message || error.message || 'Error al enviar el mensaje.';
                this.testResultado = {
                    ok: false,
                    mensaje: msg,
                };
                handleAxiosErrorToast(error, 'No fue posible enviar el mensaje de prueba.');
            } finally {
                this.loadingTestEnvio = false;
            }
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

<template>
    <div>
        <!-- ============================================================== -->
        <!-- Bread crumb and right sidebar toggle -->
        <!-- ============================================================== -->
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">Estudios</h3>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <button
                            type="button"
                            class="btn btn-link p-0"
                            style="vertical-align: baseline; text-decoration: none; color: inherit"
                        >
                            Inicio
                        </button>
                    </li>
                    <li class="breadcrumb-item">
                        <button
                            type="button"
                            class="btn btn-link p-0"
                            style="vertical-align: baseline; text-decoration: none; color: inherit"
                        >
                            Estudios
                        </button>
                    </li>
                    <li class="breadcrumb-item active">Transcribir Estudios</li>
                </ol>
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
                        <h4 class="card-title text-themecolor">
                            <i class="fa fa-filter"></i> Filtros para busqueda de mis pendientes por
                            transcribir
                        </h4>
                        <form action="#">
                            <div class="form-body">
                                <div class="row p-t-2">
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label for="quien_asigno_id">Quien asigno</label>
                                            <select
                                                v-model="busqueda.quien_asigno_id"
                                                name="quien_asigno_id"
                                                id="quien_asigno_id"
                                                class="form-control custom-select"
                                                @change="listarPendientesTrascribir()"
                                            >
                                                <option value="">
                                                    ...::: Todos los usuarios :::...
                                                </option>
                                                <option
                                                    v-for="ItemUsuario in quienesAsignaronActivos"
                                                    :key="ItemUsuario.id"
                                                    :value="ItemUsuario.id"
                                                >
                                                    {{ ItemUsuario.name }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label for="medico_id">Medicos</label>
                                            <select
                                                v-model="busqueda.medico_id"
                                                name="medico_id"
                                                id="medico_id"
                                                class="form-control custom-select"
                                                @change="listarPendientesTrascribir()"
                                            >
                                                <option value="" selected>
                                                    ...::: Todos los medicos :::...
                                                </option>
                                                <option
                                                    v-for="ItemUsuario in medicosActivos"
                                                    :key="ItemUsuario.id"
                                                    :value="ItemUsuario.id"
                                                >
                                                    {{ ItemUsuario.name }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="control-label" for="bus_nom_num_docu"
                                                >Nombres o # documento</label
                                            >
                                            <input
                                                type="text"
                                                id="bus_nom_num_docu"
                                                name="bus_nom_num_docu"
                                                v-model="busqueda.bus_nom_num_docu"
                                                class="form-control"
                                                placeholder="Nombres de paciente o # de documento"
                                                @keyup.enter="listarPendientesTrascribir()"
                                            />
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label class="control-label" for="bus_fech_ini"
                                                >Fecha Inicio</label
                                            >
                                            <input
                                                type="date"
                                                id="bus_fech_ini"
                                                name="bus_fech_ini"
                                                v-model="busqueda.bus_fech_ini"
                                                class="form-control"
                                            />
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label class="control-label" for="fecha_fin"
                                                >Fecha Fin</label
                                            >
                                            <input
                                                type="date"
                                                id="fecha_fin"
                                                name="fecha_fin"
                                                v-model="busqueda.bus_fecha_fin"
                                                class="form-control"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <div class="form-actions mt-3">
                                    <button
                                        type="button"
                                        class="btn btn-success m-r-5"
                                        :disabled="loadingBuscar"
                                        @click="listarPendientesTrascribir()"
                                    >
                                        <i v-if="loadingBuscar" class="fa fa-spinner fa-spin"></i>
                                        <i v-else class="fa fa-search"></i>
                                        Buscar
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-inverse"
                                        @click="btnLimpiar()"
                                    >
                                        <i class="fa fa-refresh"></i> Limpiar
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-outline-primary ml-2"
                                        @click="listarPendientesTrascribir()"
                                        :disabled="loadingBuscar"
                                    >
                                        <i class="fa fa-sync"></i> Actualizar
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12 table-responsive">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h4 class="card-title">Listado de estudios disponibles</h4>
                                <h6 class="card-subtitle text-info">
                                    Asigne los estudios a los médicos para su respectiva lectura.
                                </h6>
                            </div>
                            <div class="text-right">
                                <span class="badge badge-primary" v-if="contadorRegistros > 0"
                                    >{{ contadorRegistros }} registro(s)</span
                                >
                                <span class="badge badge-secondary" v-else>Sin resultados</span>
                            </div>
                        </div>
                        <table-skeleton-loader :visible="loadingBuscar" :rows="6" :columns="11" />
                        <empty-state-component
                            v-if="!loadingBuscar && registros.length === 0"
                            icon="fa fa-folder-open-o"
                            title="No hay estudios pendientes por transcribir"
                            description="No se encontraron estudios pendientes para transcripción con los filtros seleccionados o use los filtros para buscar."
                            action-text="Limpiar filtros"
                            action-icon="fa fa-refresh"
                            @action="btnLimpiar()"
                        />
                        <div
                            class="table-responsive"
                            v-show="!loadingBuscar && registros.length > 0"
                        >
                            <table
                                id="example23"
                                ref="dt"
                                class="table table-bordered table-striped"
                                cellspacing="0"
                                width="100%"
                            >
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th>Sede</th>
                                        <th>Toma</th>
                                        <th width="50%">Lectura</th>
                                        <th>Asignación</th>
                                        <th>Quien Asigno</th>
                                        <th width="50%">Medico</th>
                                        <th width="50%">Paciente</th>
                                        <th>Detalle</th>
                                        <th>Producto</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="item in registros"
                                        :key="item.estudio_producto_id"
                                        :id="'row-' + item.estudio_producto_id"
                                    >
                                        <td>
                                            <button
                                                type="button"
                                                v-if="item.priori_id == 1"
                                                class="btn waves-effect waves-light btn-rounded btn-sm btn-danger"
                                                title="Alta"
                                            >
                                                A
                                            </button>
                                            <button
                                                type="button"
                                                v-else-if="item.priori_id == 2"
                                                class="btn waves-effect waves-light btn-rounded btn-sm btn-warning"
                                                title="Media"
                                            >
                                                M
                                            </button>
                                            <button
                                                type="button"
                                                v-else-if="item.priori_id == 3"
                                                class="btn waves-effect waves-light btn-rounded btn-sm btn-info"
                                                title="Baja"
                                            >
                                                B
                                            </button>
                                            <button
                                                type="button"
                                                v-else-if="item.priori_id == 4"
                                                class="btn waves-effect waves-light btn-rounded btn-sm"
                                                title="Sin prioridad"
                                            >
                                                S
                                            </button>
                                        </td>
                                        <td>{{ item.nom_sede }}</td>
                                        <td>{{ item.fec_estudio }}</td>
                                        <td>{{ item.fechor_lectura }}</td>
                                        <td>{{ formatDate(item.created_at) }}</td>
                                        <td>{{ item.quien_registro }}</td>
                                        <td>{{ item.nombres_medico || item.medico }}</td>
                                        <td>
                                            # Docu: {{ item.num_docu }} <br />
                                            Nombres: {{ item.nom_pacien }}
                                        </td>
                                        <td>
                                            Atención: {{ item.atencion }} <br />
                                            Descrip: {{ item.study_desc }} <br />
                                            {{ item.accession_no }}
                                        </td>
                                        <td>{{ item.nom_produc }}</td>
                                        <td class="text-nowrap">
                                            <button
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-warning btn-sm m-r-5"
                                                :disabled="
                                                    loadingAbrirRegistroId ===
                                                    item.estudio_producto_id
                                                "
                                                @click="mostrarRegistro(item)"
                                            >
                                                <i
                                                    v-if="
                                                        loadingAbrirRegistroId ===
                                                        item.estudio_producto_id
                                                    "
                                                    class="fa fa-spinner fa-spin"
                                                ></i>
                                                <i v-else class="fa fa-pencil"></i>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm m-r-5"
                                                :disabled="
                                                    loadingAnularId === item.estudio_producto_id
                                                "
                                                @click="
                                                    anularAsignacion(
                                                        item.estudio_producto_id,
                                                        item.nom_pacien,
                                                        item.nom_produc
                                                    )
                                                "
                                            >
                                                <i
                                                    v-if="
                                                        loadingAnularId === item.estudio_producto_id
                                                    "
                                                    class="fa fa-spinner fa-spin"
                                                ></i>
                                                <i v-else class="fa fa-eraser"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <div
                                v-if="registros.length === 0 && !loadingBuscar"
                                class="text-center py-4 text-muted"
                            >
                                <i class="fa fa-inbox fa-3x mb-3"></i>
                                <p>No hay estudios para transcribir</p>
                            </div>
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
        <!-- Backdrop para cerrar al hacer clic y atenuar fondo -->
        <transition name="fade">
            <div
                v-if="sidebarVisible"
                class="sidebar-backdrop"
                @click="btnCerrarModalForm()"
                @mousedown.prevent="btnCerrarModalForm()"
                aria-hidden="true"
            ></div>
        </transition>

        <!-- .right-sidebar -->
        <div class="right-sidebar sidebar-scroll" :class="{ 'shw-rside': sidebarVisible }">
            <div class="slimscrollright">
                <div class="rpanel-title">
                    {{ tituloModal }}
                    <button
                        type="button"
                        class="right-side-toggle"
                        @click="btnCerrarModalForm()"
                        @mousedown.prevent="btnCerrarModalForm()"
                        aria-label="Cerrar panel"
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
                            <div class="row">
                                <div class="col-md-6">
                                    <form-paciente :registro="registro" :errores="errores" />

                                    <h5 class="card-title text-info">
                                        <i class="fa fa-user"></i> Datos del Estudio
                                    </h5>
                                    <hr />

                                    <input
                                        type="hidden"
                                        id="estudio_producto_id"
                                        name="estudio_producto_id"
                                        v-model="registro.estudio_producto_id"
                                    />
                                    <input
                                        type="hidden"
                                        id="email"
                                        name="email"
                                        v-model="registro.email"
                                    />

                                    <div class="row">
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <dl>
                                                    <dt>Descripción:</dt>
                                                    <dd>{{ registro.study_desc }}</dd>
                                                </dl>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <dl>
                                                    <dt>Detalle</dt>
                                                    <dd>{{ registro.accession_no }}</dd>
                                                </dl>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <dl>
                                                    <dt>Fecha del estudio</dt>
                                                    <dd>{{ registro.fec_estudio }}</dd>
                                                </dl>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label class="control-label" for="email_reportar"
                                                    >Reportar al Email
                                                    <small class="text-muted"
                                                        >(Opcional)</small
                                                    ></label
                                                >
                                                <input
                                                    type="email"
                                                    id="email_reportar"
                                                    name="email_reportar"
                                                    v-model="registro.email_reportar"
                                                    class="form-control"
                                                    :class="getInputClass(statusEmailReportar)"
                                                    placeholder="Ingrese aquí el email a reportar"
                                                    ref="primerInput"
                                                    @blur="touch('email_reportar')"
                                                />
                                                <div
                                                    class="invalid-feedback d-block"
                                                    v-if="statusEmailReportar.isInvalid"
                                                >
                                                    {{ statusEmailReportar.message }}
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <dl>
                                                    <dt>Observaciones</dt>
                                                    <dd>{{ registro.observaciones }}</dd>
                                                </dl>
                                            </div>
                                        </div>
                                    </div>

                                    <h5 class="card-title text-info">
                                        <i class="fa fa-user"></i> Producto
                                    </h5>
                                    <hr />

                                    <div class="row table-responsive">
                                        <div class="col-md-12">
                                            <dl>
                                                <dd>{{ registro.nom_produc }}</dd>
                                            </dl>
                                        </div>
                                    </div>
                                    <br />
                                </div>
                                <div class="col-md-6">
                                    <div class="row">
                                        <div class="col-9">
                                            <h5 class="card-title text-info">
                                                <i class="fa fa-edit"></i> Lectura
                                            </h5>
                                            <hr />
                                        </div>
                                        <div class="col-3">
                                            <div
                                                class="col-md-7 align-self-center text-right d-none d-md-block"
                                            >
                                                <button
                                                    type="button"
                                                    class="btn btn-secondary btn-xs"
                                                    @click="
                                                        verImagen(
                                                            datosImagen.urlOviyam,
                                                            datosImagen.patientId,
                                                            datosImagen.studyUID,
                                                            datosImagen.serverName
                                                        )
                                                    "
                                                    :disabled="loadingVerImagen"
                                                >
                                                    <i
                                                        v-if="loadingVerImagen"
                                                        class="fa fa-spinner fa-spin"
                                                    ></i>
                                                    <i v-else class="fa fa-file-image-o"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <span class="text-danger" v-if="errores.lectura">{{
                                        errores.lectura[0]
                                    }}</span>
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="form-group">
                                                <WysiwygEditor
                                                    v-model="registro.lectura"
                                                    :rows="20"
                                                    placeholder="Ingrese aquí la lectura del estudio ..."
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-12">
                                            <!-- Nav tabs -->
                                            <ul class="nav nav-tabs" role="tablist">
                                                <li class="nav-item">
                                                    <a
                                                        class="nav-link active"
                                                        data-toggle="tab"
                                                        href="#home8"
                                                        role="tab"
                                                    >
                                                        <h5 class="card-title text-info">
                                                            <i class="fa fa-paperclip"></i> Adjuntos
                                                        </h5>
                                                    </a>
                                                </li>
                                                <li class="nav-item">
                                                    <a
                                                        class="nav-link"
                                                        data-toggle="tab"
                                                        href="#profile8"
                                                        role="tab"
                                                    >
                                                        <span>
                                                            <h5 class="card-title text-info">
                                                                <i class="fa fa-file-audio-o"></i>
                                                                Audios
                                                            </h5>
                                                        </span>
                                                    </a>
                                                </li>
                                            </ul>
                                            <!-- Tab panes -->
                                            <div class="tab-content tabcontent-border">
                                                <div
                                                    class="tab-pane active p-20"
                                                    id="home8"
                                                    role="tabpanel"
                                                >
                                                    <div
                                                        class="col-md-8 col-lg-9"
                                                        v-for="itemArchivo in soportesHC"
                                                        :key="itemArchivo.archivo_encrip"
                                                    >
                                                        <a
                                                            href="javascript:void(0)"
                                                            :style="{
                                                                pointerEvents:
                                                                    loadingDescarga ===
                                                                    itemArchivo.archivo_encrip
                                                                        ? 'none'
                                                                        : 'auto',
                                                            }"
                                                            @click="
                                                                descargaSoporte(
                                                                    itemArchivo.archivo_encrip
                                                                )
                                                            "
                                                        >
                                                            <i
                                                                v-if="
                                                                    loadingDescarga ===
                                                                    itemArchivo.archivo_encrip
                                                                "
                                                                class="fa fa-spinner fa-spin"
                                                            ></i>
                                                            {{ itemArchivo.archivo_original }}
                                                        </a>
                                                    </div>
                                                </div>
                                                <div
                                                    class="tab-pane p-20"
                                                    id="profile8"
                                                    role="tabpanel"
                                                >
                                                    <div v-if="loadingAudios" class="text-muted">
                                                        <i class="fa fa-spinner fa-spin"></i>
                                                        Cargando audios...
                                                    </div>
                                                    <template v-if="!loadingAudios">
                                                        <div
                                                            v-for="(item, index) in audiosDeEstudio"
                                                            :key="item.id || index"
                                                            class="card mb-2"
                                                        >
                                                            <div class="card-body p-2">
                                                                <div
                                                                    class="d-flex align-items-center mb-1 flex-wrap"
                                                                >
                                                                    <i
                                                                        class="fa fa-file-audio-o text-muted mr-1"
                                                                    ></i>
                                                                    <strong
                                                                        class="mr-2 text-truncate"
                                                                        :title="
                                                                            item.nombre_original
                                                                        "
                                                                        style="max-width: 200px"
                                                                    >
                                                                        {{
                                                                            item.nombre_original ||
                                                                            "Audio " + (index + 1)
                                                                        }}
                                                                    </strong>
                                                                    <span
                                                                        class="badge mr-1"
                                                                        :class="
                                                                            item.tipo === 'cargado'
                                                                                ? 'badge-info'
                                                                                : 'badge-success'
                                                                        "
                                                                    >
                                                                        <i
                                                                            :class="
                                                                                item.tipo ===
                                                                                'cargado'
                                                                                    ? 'fa fa-upload'
                                                                                    : 'fa fa-microphone'
                                                                            "
                                                                        ></i>
                                                                        {{
                                                                            item.tipo === "cargado"
                                                                                ? "Cargado"
                                                                                : "Grabado"
                                                                        }}
                                                                    </span>
                                                                    <small
                                                                        class="text-muted"
                                                                        v-if="item.duracion"
                                                                    >
                                                                        <i
                                                                            class="fa fa-clock-o"
                                                                        ></i>
                                                                        {{
                                                                            formatDuracion(
                                                                                item.duracion
                                                                            )
                                                                        }}
                                                                    </small>
                                                                </div>
                                                                <audio
                                                                    :src="audioUrl(item)"
                                                                    controls
                                                                    style="
                                                                        height: 30px;
                                                                        width: 100%;
                                                                    "
                                                                ></audio>
                                                                <div
                                                                    v-if="item.transcripcion"
                                                                    class="bg-light rounded p-1 mt-1"
                                                                    style="
                                                                        font-size: 0.8rem;
                                                                        max-height: 40px;
                                                                        overflow: hidden;
                                                                    "
                                                                >
                                                                    {{
                                                                        previewTranscripcion(
                                                                            item.transcripcion,
                                                                            120
                                                                        )
                                                                    }}
                                                                    <span
                                                                        v-if="
                                                                            item.transcripcion
                                                                                .length > 120
                                                                        "
                                                                        class="text-primary small"
                                                                        >...</span
                                                                    >
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div
                                                            v-if="audiosDeEstudio.length === 0"
                                                            class="text-muted small"
                                                        >
                                                            No hay audios para este estudio.
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-actions mt-3">
                                <button
                                    type="button"
                                    class="btn btn-success m-r-5"
                                    :disabled="loadingGuardar"
                                    @click="guardarRegistro()"
                                    title="Guardar transcripción (Ctrl + S)"
                                >
                                    <i v-if="loadingGuardar" class="fa fa-spinner fa-spin"></i>
                                    <i v-else class="fa fa-check"></i>
                                    Guardar
                                    <span
                                        class="badge badge-light text-dark ml-1 d-none d-md-inline"
                                        style="font-size: 10px"
                                        >Ctrl+S</span
                                    >
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-inverse"
                                    ref="btnCerrarForm"
                                    @click="btnCerrarModalForm()"
                                    @mousedown.prevent="btnCerrarModalForm()"
                                    title="Cerrar panel (Esc)"
                                >
                                    Cancelar
                                    <span
                                        class="badge badge-secondary ml-1 d-none d-md-inline"
                                        style="font-size: 10px"
                                        >Esc</span
                                    >
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
import { computed, nextTick, onBeforeUnmount, onMounted, ref, getCurrentInstance } from "vue";
import FormPaciente from "@/components/comunes/formPacienteComponent.vue";
import { listarUsuariosYSedes } from "@/helpers/usuarios";
import { safeDestroyDataTable, safeInitDataTable } from "@/helpers/datatables.js";
import { confirmAction, getUserSafeError, toastMessage } from "@/helpers/alerts";
import WysiwygEditor from "@/components/shared/WysiwygEditor.vue";
import dayjs from "dayjs";
import { useEstudioHelpers } from "@/composables/useEstudioHelpers";
import { useFormValidation } from "@/composables/useFormValidation";
import { useKeyboardShortcuts } from "@/composables/useKeyboardShortcuts";
import axios from "axios";

const { proxy } = getCurrentInstance();
const emitter = proxy.emitter;

defineProps({ usuarioactual: { type: Object, default: () => ({}) } });

const {
    loadingDescarga,
    loadingAudios,
    loadingVerImagen,
    audiosDeEstudio,
    formatDate,
    descargaSoporte,
    listarAudios,
    verImagen,
} = useEstudioHelpers();

const dt = ref(null);
const primerInput = ref(null);

const id = ref(0);
const diagnosticosEstudio = ref([]);
const soportesHC = ref([]);
const registros = ref([]);
const tituloModal = ref("Transcribir estudio");
const registro = ref({
    id_estudio: 0,
    estudio_producto_id: 0,
    lectura: "",
    fec_lectura: "",
    fec_estudio: "",
    accession_no: "",
    study_desc: "",
    observaciones: "",
    email_reportar: "",
    num_docu: "",
    nom_pacien: "",
    nombres: "",
    sexo: "",
    fec_naci: "",
    email: "",
    diagnosticosEstudio: [],
    sede_id: "",
    atencion: null,
    servicio: null,
    eps: null,
    contrato: null,
    medico_id: null,
    cod_cups: "",
    nom_produc: "",
    num_docu_medico: "",
    reg_medico: "",
    nombres_medico: "",
    firma_medico: "",
    url_oviyam: null,
    tap_oviyam: null,
    study_iuid: null,
});
const fechaDefectoIni = dayjs().startOf("month").format("YYYY-MM-DD");
const fechaDefectoFin = dayjs().endOf("month").format("YYYY-MM-DD");
const busqueda = ref({
    estudioParaTranscribir: "si",
    quien_asigno_id: "",
    medico_id: "",
    sede_id: "",
    bus_nom_num_docu: "",
    bus_fech_ini: fechaDefectoIni,
    bus_fecha_fin: fechaDefectoFin,
    destino_estudio: "Salud Tolima",
});
const datosImagen = ref({ urlOviyam: "", patientId: "", studyUID: "", serverName: "" });
const quienes_asignaron = ref([]);
const quienesAsignaronActivos = computed(() => {
    return (quienes_asignaron.value || [])
        .filter(
            (u) =>
                u &&
                (u.estado === undefined || u.estado === 1 || u.estado === true || u.estado === "1")
        )
        .map((u) => ({
            ...u,
            name: (u.name || "").trim(),
        }))
        .sort((a, b) => a.name.localeCompare(b.name, "es", { sensitivity: "base" }));
});
const medicos = ref([]);
const medicosActivos = computed(() => {
    return (medicos.value || [])
        .filter(
            (u) =>
                u &&
                (u.estado === undefined || u.estado === 1 || u.estado === true || u.estado === "1")
        )
        .map((u) => ({
            ...u,
            name: (u.name || "").trim(),
        }))
        .sort((a, b) => a.name.localeCompare(b.name, "es", { sensitivity: "base" }));
});
const errores = ref({});
const idRow = ref(null);
const loadingBuscar = ref(false);
const loadingGuardar = ref(false);
const loadingAbrirRegistroId = ref(null);
const loadingAnularId = ref(null);
const sidebarVisible = ref(false);

const contadorRegistros = computed(() => {
    return registros.value.length;
});

const { touch, getFieldStatus, getInputClass, validators, resetTouched } = useFormValidation();

const statusEmailReportar = computed(() => {
    const val = registro.value.email_reportar;
    if (!val || val === "null" || val === "undefined" || String(val).trim() === "") {
        return { isInvalid: false, isValid: false, message: "" };
    }
    return getFieldStatus("email_reportar", String(val).trim(), [validators.email], errores.value);
});

useKeyboardShortcuts({
    onSave: guardarRegistro,
    onEscape: btnCerrarModalForm,
    isActive: () => sidebarVisible.value,
    isLoading: loadingGuardar,
});

/**
 * Aplica un prellenado del dashboard si existe un accession almacenado en sessionStorage. Si se encuentra un accession válido, se busca el estudio correspondiente y se muestra en la interfaz. Si no hay un accession válido, simplemente se listan los estudios pendientes.
 * @returns {void}
 * @author: Jhon Javer Lozano Arce
 */
function applyDashboardPrefill() {
    try {
        const accession = sessionStorage.getItem("ST-accession");
        if (accession && String(accession).trim()) {
            busqueda.value.bus_nom_num_docu = String(accession).trim();
            // Consumir para no pegar el filtro en siguientes visitas
            sessionStorage.removeItem("ST-accession");
        }
        if (sessionStorage.getItem("ST-sede") != null) {
            destroyDataTable();
            listarPendientesTrascribir();
        }
    } catch {
        toastMessage({
            heading: "Error",
            text: "No fue posible cargar el prellenado del dashboard.",
            icon: "error",
        });
        listarPendientesTrascribir();
    }
}

function destroyDataTable() {
    safeDestroyDataTable(dt.value || "#example23");
}

function btnLimpiar() {
    const hoy = new Date();
    const yearActual = hoy.getFullYear();
    busqueda.value.quien_asigno_id = "";
    busqueda.value.medico_id = "";
    busqueda.value.bus_nom_num_docu = "";
    busqueda.value.bus_fech_ini = yearActual + "-01-01";
    busqueda.value.bus_fecha_fin = hoy.toISOString().slice(0, 10);
    errores.value = {};
    destroyDataTable();
    registros.value = [];
    limpiar();
}

/**
 * Lista los estudios pendientes de transcribir según los filtros de búsqueda. Si no se ha establecido una sede en sessionStorage, muestra un mensaje de advertencia. De lo contrario, realiza una solicitud al backend para obtener los estudios y los muestra en la tabla.
 * @returns {Promise<void>}
 * @author: Jhon Javer Lozano Arce
 */
async function listarPendientesTrascribir() {
    if (loadingBuscar.value) return;
    loadingBuscar.value = true;
    try {
        const sedeActual = sessionStorage.getItem("ST-sede");
        if (sedeActual == null) {
            toastMessage({
                heading: "Upsss!!!",
                text: "Debes establece la sede en la cual vas a trabajar.",
                icon: "warning",
            });
        } else {
            busqueda.value.sede_id = sedeActual;
            destroyDataTable();

            const payload = {
                ...busqueda.value,
                sede_id: sedeActual,
                quien_asigno_id: busqueda.value.quien_asigno_id || null,
                medico_id: busqueda.value.medico_id || null,
            };
            const res = await axios.post("/api/estudio-listarPendientesTrascribir", payload);
            registros.value = Array.isArray(res?.data?.data) ? res.data.data : [];

            nextTick(() => {
                if (registros.value.length > 0) {
                    safeInitDataTable(dt.value || "#example23", {
                        dom: "Bfrtip",
                        buttons: ["copy", "csv", "excel", "pdf", "print"],
                    });
                }
            });
        }
    } catch (error) {
        const msg =
            error?.response?.data?.message ||
            error?.response?.data?.errors?.sede_id?.[0] ||
            "No fue posible listar los estudios pendientes de transcribir.";
        toastMessage({
            heading: "Error",
            text: getUserSafeError(error, msg),
            icon: "error",
        });
    } finally {
        loadingBuscar.value = false;
    }
}

/**
 * Guarda la transcripción del estudio en el backend. Si ya se está guardando, la función retorna inmediatamente. Muestra mensajes de estado durante el proceso y maneja errores de validación y otros errores del servidor.
 * @returns {Promise<void>}
 * @author: Jhon Javer Lozano Arce
 */
async function guardarRegistro() {
    if (loadingGuardar.value) return;

    if (registro.value.email_reportar && String(registro.value.email_reportar).trim() !== "") {
        touch("email_reportar");
        if (statusEmailReportar.value.isInvalid) {
            toastMessage({
                heading: "Email inválido",
                text:
                    statusEmailReportar.value.message ||
                    "Por favor ingrese un email válido para reportar.",
                icon: "warning",
            });
            return;
        }
    }

    loadingGuardar.value = true;
    try {
        toastMessage({
            heading: "Procesando...",
            text: "Enviando lectura, por favor espere",
            icon: "info",
        });

        const cleanEmail =
            registro.value.email_reportar &&
            registro.value.email_reportar !== "null" &&
            registro.value.email_reportar !== "undefined" &&
            String(registro.value.email_reportar).trim() !== ""
                ? String(registro.value.email_reportar).trim()
                : null;

        const payload = {
            estudio_producto_id: registro.value.estudio_producto_id,
            id_estudio: registro.value.id_estudio,
            atencion: registro.value.atencion ?? null,
            atencion_id: registro.value.atencion ? parseInt(registro.value.atencion, 10) : null,
            destino_estudio: registro.value.destino_estudio ?? null,
            lectura: registro.value.lectura,
            url_oviyam: registro.value.url_oviyam,
            study_iuid: registro.value.study_iuid,
            tap_oviyam: registro.value.tap_oviyam,
            nom_sede: registro.value.nom_sede,
            sede_id: registro.value.sede_id,
            medico_id: registro.value.medico_id,
            cod_cups: registro.value.cod_cups,
            nom_produc: registro.value.nom_produc,
            num_docu: registro.value.num_docu,
            nom_pacien: registro.value.nom_pacien,
            email: registro.value.email,
            email_reportar: cleanEmail,
            fec_naci: registro.value.fec_naci,
            fec_estudio: registro.value.fec_estudio,
            fec_lectura: registro.value.fec_lectura,
            nombres_medico: registro.value.nombres_medico ?? null,
            reg_medico: registro.value.reg_medico ?? null,
            num_docu_medico: registro.value.num_docu_medico ?? null,
            firma_medico: registro.value.firma_medico ?? null,
        };
        const res = await axios.post("/api/estudio-guardarTranscripcion", payload);

        if (res.status == 200) {
            if (idRow.value != null) {
                const estudioEliminar = registros.value.findIndex(
                    (x) => x.estudio_producto_id == idRow.value
                );
                if (estudioEliminar >= 0) {
                    destroyDataTable();
                    registros.value.splice(estudioEliminar, 1);
                    nextTick(() => {
                        if (registros.value.length > 0) {
                            safeInitDataTable(dt.value || "#example23", {
                                dom: "Bfrtip",
                                buttons: ["copy", "csv", "excel", "pdf", "print"],
                            });
                        }
                    });
                }
            }

            toastMessage({
                heading: "Completado",
                text: res.data.message,
                icon: "success",
            });

            await nextTick();
            btnCerrarModalForm();
            limpiar();
        } else {
            toastMessage({
                heading: "Upss!!!",
                text: res.data.message,
                icon: "danger",
            });
        }
    } catch (error) {
        const status = error?.response?.status;
        // Errores de validación (400) vienen como objeto con campos
        if (
            (status == 400 || status == 422) &&
            error?.response?.data &&
            typeof error.response.data === "object"
        ) {
            const data = error.response.data;
            errores.value = data.errors && typeof data.errors === "object" ? data.errors : data;
        }
        if (status == 402) {
            toastMessage({
                heading: "Upss!!!",
                text: getUserSafeError(error, "La plantilla no está configurada para esta sede."),
                icon: "error",
            });
        } else {
            toastMessage({
                heading: "Upss!!!",
                text: getUserSafeError(error, "No fue posible guardar la transcripción."),
                icon: "error",
            });
        }
    } finally {
        loadingGuardar.value = false;
    }
}

/**
 * Anula la asignación de un estudio para un paciente y producto específico. Solicita confirmación al usuario antes de proceder. Si la acción es confirmada, realiza una solicitud al backend para anular la asignación y actualiza la lista de registros en la interfaz. Maneja errores y muestra mensajes de estado durante el proceso.
 * @param {number} idProductoEstudio - ID del producto de estudio a anular.
 * @param {string} paciente - Nombre del paciente.
 * @param {string} producto - Nombre del producto.
 * @returns {Promise<void>}
 * @author: Jhon Javer Lozano Arce
 */
async function anularAsignacion(idProductoEstudio, paciente, producto) {
    const ok = await confirmAction({
        title: `Estas seguro de anular la lectura del paciente ${paciente} con el producto ${producto} ?`,
        text: "No podrás recuperar este registro!",
        icon: "warning",
    });
    if (!ok) return;
    if (loadingAnularId.value) return;

    loadingAnularId.value = idProductoEstudio;
    try {
        const res = await axios.post("/api/estudio-anular-asignacion", {
            id: idProductoEstudio,
        });
        if (res.status === 200) {
            const idx = registros.value.findIndex(
                (x) => x.estudio_producto_id == idProductoEstudio
            );
            if (idx >= 0) {
                destroyDataTable();
                registros.value.splice(idx, 1);
                nextTick(() => {
                    if (registros.value.length > 0) {
                        safeInitDataTable(dt.value || "#example23", {
                            dom: "Bfrtip",
                            buttons: ["copy", "csv", "excel", "pdf", "print"],
                        });
                    }
                });
            }
            toastMessage({
                heading: "Anulado!",
                text: "El producto se anulo correctamente.",
                icon: "success",
            });
        } else {
            toastMessage({
                heading: "Ups!",
                text: "No fue posible anular el producto.",
                icon: "error",
            });
        }
    } catch {
        toastMessage({
            heading: "Error",
            text: "No fue posible anular el producto.",
            icon: "error",
        });
    } finally {
        loadingAnularId.value = null;
    }
}

function audioUrl(item) {
    if (!item || !item.audio) return null;
    return "/audios/" + item.audio;
}

function formatDuracion(segundos) {
    if (!segundos) return "0:00";
    const mins = Math.floor(segundos / 60);
    const secs = Math.floor(segundos % 60);
    return mins + ":" + (secs < 10 ? "0" : "") + secs;
}

function previewTranscripcion(texto, maxLen) {
    if (!texto) return "";
    if (texto.length <= maxLen) return texto;
    return texto.substring(0, maxLen);
}

/**
 * Muestra el registro de un estudio específico en el modal de transcripción. Si ya se está cargando un registro, la función retorna inmediatamente. Realiza una solicitud al backend para obtener los detalles del estudio y los muestra en la interfaz. Maneja errores y muestra mensajes de estado si no es posible cargar el registro.
 * @param {Object} data - Objeto que contiene los datos del estudio a mostrar.
 * @returns {Promise<void>}
 * @author: Jhon Javer Lozano Arce
 */
async function mostrarRegistro(data = {}) {
    if (loadingAbrirRegistroId.value !== null) return;
    loadingAbrirRegistroId.value = data.estudio_producto_id;

    try {
        const date = new Date();
        const fechaActual =
            ("0" + date.getDate()).slice(-2) +
            "-" +
            ("0" + (date.getMonth() + 1)).slice(-2) +
            "-" +
            date.getFullYear() +
            " " +
            ("0" + date.getHours()).slice(-2) +
            ":" +
            ("0" + date.getMinutes()).slice(-2) +
            ":" +
            ("0" + date.getSeconds()).slice(-2);

        tituloModal.value = "Transcribir la lectura del paciente: " + data.nom_pacien;
        id.value = data.id;
        idRow.value = data.estudio_producto_id;
        registro.value.estudio_producto_id = data.estudio_producto_id;
        registro.value.fec_estudio = data.fec_estudio;
        registro.value.fec_lectura = data.fechor_lectura;
        registro.value.accession_no = data.accession_no;
        registro.value.study_desc = data.study_desc;
        registro.value.email_reportar = data.email_reportar;
        registro.value.atencion = data.atencion;
        registro.value.medico_id = data.medico_id;
        registro.value.num_docu = data.num_docu;
        registro.value.nom_pacien = data.nom_pacien;
        registro.value.nombres = data.nom_pacien;
        registro.value.direccion = data.direccion;
        registro.value.email = data.email;
        // Unificar campo teléfono: el backend espera `tel`
        registro.value.tel = data.tel;
        // El backend devuelve `pacien.sexo` y `pacien.fec_naci`
        registro.value.sexo = data.sexo;
        registro.value.fec_naci = data.fec_naci;
        registro.value.cod_cups = data.cod_cups;
        registro.value.nom_produc = data.nom_produc;
        registro.value.num_docu_medico = data.num_docu_medico;
        registro.value.reg_medico = data.reg_medico;
        registro.value.nombres_medico = data.nombres_medico;
        registro.value.firma_medico = data.firma_medico;
        registro.value.lectura = `Atención: ${data.atencion}\nFecha y Hora del estudio: ${data.fec_estudio}\nFecha y Hora de transcripción: ${fechaActual}\n\n${data.nom_produc}\n\n${data.lectura || ""}`;
        datosImagen.value.urlOviyam = data.url_oviyam;
        datosImagen.value.patientId = data.num_docu;
        datosImagen.value.studyUID = data.study_iuid;
        datosImagen.value.serverName = data.tap_oviyam;

        registro.value.sede_id = data.sede_id;
        registro.value.nom_sede = data.nom_sede;

        registro.value.url_oviyam = data.url_oviyam;
        registro.value.study_iuid = data.study_iuid;
        registro.value.tap_oviyam = data.tap_oviyam;
        // Fix: id_estudio y destino_estudio son necesarios para Tele-Radiología
        registro.value.id_estudio = data.id;
        registro.value.destino_estudio = data.destino_estudio ?? null;

        const res = await axios.get("/api/study-listar-diagnostico-por-estudio?id=" + id.value);
        diagnosticosEstudio.value = res.data.data;

        const resSoportesHC = await axios.get("/api/listar-soportes-hc-estudio?id=" + id.value);
        soportesHC.value = resSoportesHC.data.data;

        audiosDeEstudio.value = [];
        abrirModal();

        await listarAudios(registro.value.estudio_producto_id);
        errores.value = {};
    } catch (error) {
        toastMessage({
            heading: "Atención",
            text: getUserSafeError(
                error,
                "No fue posible cargar diagnósticos/soportes del estudio."
            ),
            icon: "warning",
        });
    } finally {
        loadingAbrirRegistroId.value = null;
    }
}
function limpiar() {
    tituloModal.value = "Nuevo registro";
    registro.value.email_reportar = "";
    registro.value.email = "";
    registro.value.nom_pacien = "";
    registro.value.nombres = "";

    btnCerrarModalForm();
    errores.value = {};
}
function abrirModal() {
    sidebarVisible.value = true;
    nextTick(() => {
        primerInput.value?.focus();
    });
}
function btnCerrarModalForm() {
    sidebarVisible.value = false;
    resetTouched();
    errores.value = {};
}

/**
 * Carga o recarga los usuarios asignadores y médicos filtrando por la sede activa.
 *
 * Flujo del proceso:
 * 1. Resuelve el ID de la sede objetivo a partir del argumento, de busqueda.sede_id o de sessionStorage.
 * 2. Invoca listarUsuariosYSedes pasando options.sedeId para restringir el listado de asignadores.
 * 3. Captura cualquier fallo de red de forma silenciosa para no interrumpir el montaje.
 *
 * @param {string|number|null} [sedeId=null] - ID opcional de la sede a filtrar.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-29
 */
const cargarAsignadoresYSedes = async (sedeId = null) => {
    const targetSede = sedeId ?? busqueda.value.sede_id ?? sessionStorage.getItem("ST-sede");
    try {
        await listarUsuariosYSedes(
            {
                get quienes_asignaron() {
                    return quienes_asignaron.value;
                },
                set quienes_asignaron(v) {
                    quienes_asignaron.value = Array.isArray(v) ? v : [];
                },
                get medicos() {
                    return medicos.value;
                },
                set medicos(v) {
                    medicos.value = Array.isArray(v) ? v : [];
                },
            },
            { moduloId: "1", sedeId: targetSede }
        );
    } catch (e) {
        console.error("Error al inicializar usuarios y médicos:", e);
    }
};

onMounted(async () => {
    busqueda.value.sede_id = sessionStorage.getItem("ST-sede");

    await cargarAsignadoresYSedes(busqueda.value.sede_id);

    applyDashboardPrefill();

    emitter.on("sedeSeleccionada", onSedeSeleccionada);
});

/**
 * Maneja el evento "sedeSeleccionada" emitido por el selector global de sede: actualiza
 * busqueda.sede_id, limpia los filtros de asignador y médico, recarga los asignadores y médicos
 * para la nueva sede, destruye la tabla y vuelve a listar los pendientes de transcribir.
 *
 * @param {Object} data - Payload del evento, contiene data.idSede con el ID de la sede elegida.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-29
 */
function onSedeSeleccionada(data) {
    busqueda.value.sede_id = data.idSede;
    busqueda.value.quien_asigno_id = "";
    busqueda.value.medico_id = "";
    cargarAsignadoresYSedes(data.idSede);
    destroyDataTable();
    listarPendientesTrascribir();
}

onBeforeUnmount(() => {
    emitter.off("sedeSeleccionada", onSedeSeleccionada);
    destroyDataTable();
    registros.value = [];
    registro.value = { productosEstudio: [], diagnosticosEstudio: [], soportesHC: [] };
    soportesHC.value = [];
    audiosDeEstudio.value = [];
});
</script>
<style scoped>
.sidebar-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(15, 23, 42, 0.45);
    backdrop-filter: blur(2px);
    -webkit-backdrop-filter: blur(2px);
    z-index: 1090;
    cursor: pointer;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.25s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.right-sidebar {
    position: fixed;
    right: -80%;
    width: 80%;
    z-index: 1100;
    background: #ffffff;
    top: 0px;
    padding-bottom: 20px;
    height: 100%;
    box-shadow: -5px 0 35px rgba(0, 0, 0, 0.15);
    transition:
        right 0.35s cubic-bezier(0.16, 1, 0.3, 1),
        visibility 0.35s;
    visibility: hidden;
    pointer-events: none;
}

@media (max-width: 767px) {
    .right-sidebar {
        right: -100%;
        width: 100%;
    }
}

.shw-rside {
    right: 0px !important;
    width: 80%;
    visibility: visible;
    pointer-events: auto;
}

@media (max-width: 767px) {
    .shw-rside {
        width: 100%;
    }
}

.sidebar-scroll {
    overflow-x: hidden;
    overflow-y: scroll;
    height: 100%;
}
</style>

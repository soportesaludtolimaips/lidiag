<template>
    <!-- ============================================================== -->
    <!-- Bread crumb and right sidebar toggle -->
    <!-- ============================================================== -->
    <div class="row page-titles">
        <div class="col-md-5 align-self-center">
            <h3 class="text-themecolor">Estudios</h3>
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="javascript:void(0)">Inicio</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="javascript:void(0)">Estudios</a>
                </li>
                <li class="breadcrumb-item active">Leer Estudios</li>
            </ol>
        </div>
        <div class="col-md-7 align-self-center text-right d-none d-md-block">
            <i class=" fa fa-life-ring model_img img-responsive" data-toggle="modal"
                data-target=".bs-example-modal-lg"></i>
        </div>

        <!-- sample modal content -->
        <div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel"
            aria-hidden="true" style="display: none;">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title" id="myLargeModalLabel">Comando de voz</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <ul>
                                    <li>iniciar mayúscula sostenida</li>
                                    <li>iniciar mayúsculas sostenida</li>
                                    <li>iniciar mayúsculas</li>
                                    <li>iniciar mayúscula</li>
                                    <li>mayúsculas sostenida</li>
                                    <li>mayúscula sostenida</li>
                                    <li>activar mayúsculas</li>
                                    <li>activar mayúscula</li>
                                    <li>habilitar mayúsculas</li>
                                    <li>habilitar mayúscula</li>
                                    <li>activare mayúsculas</li>
                                    <li>activare mayúscula</li>
                                    <li>desactivar mayúsculas</li>
                                    <li>desactivar mayúscula</li>
                                    <li>Deshabilitar mayúsculas</li>
                                    <li>Deshabilitar mayúscula</li>
                                    <li>terminar mayúsculas</li>
                                    <li>terminar mayúscula</li>
                                    <li>fin mayúsculas</li>
                                    <li>fin mayúscula</li>
                                    <li>finaliza mayúsculas</li>
                                    <li>finaliza mayúscula</li>
                                    <li>finalizar mayúsculas</li>
                                    <li>finalizar mayúscula</li>
                                    <li>punto aparte</li>
                                    <li>salto de línea</li>
                                    <li>doble salto de línea</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <ul>
                                    <li>enter</li>
                                    <li>doble enter</li>
                                    <li>línea en blanco</li>
                                    <li>dar espacio</li>
                                    <li>un espacio</li>
                                    <li>dos espacio</li>
                                    <li>doble espacio</li>
                                    <li>punto</li>
                                    <li>coma</li>
                                    <li>dos puntos</li>
                                    <li>punto y coma</li>
                                    <li>abrir paréntesis</li>
                                    <li>cerrar paréntesis</li>
                                    <li>abrir corchetes</li>
                                    <li>cerrar corchetes</li>
                                    <li>abrir llaves</li>
                                    <li>cerrar llaves</li>
                                    <li>abrir comillas</li>
                                    <li>cerrar comillas</li>
                                    <li>abrir comillas simples</li>
                                    <li>cerrar comillas simples</li>
                                    <li>guion</li>
                                    <li>guion bajo</li>
                                    <li>barra diagonal</li>
                                    <li>barra invertida</li>
                                    <li>nueva oración</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger waves-effect text-left"
                            data-dismiss="modal">Close</button>
                    </div>
                </div>
                <!-- /.modal-content -->
            </div>
            <!-- /.modal-dialog -->
        </div>
        <!-- /.modal -->

    </div>
    <!-- ============================================================== -->
    <!-- End Bread crumb and right sidebar toggle -->
    <!-- ============================================================== -->

    <!-- ============================================================== -->
    <!-- Start Page Content -->
    <!-- ============================================================== -->
    <div class="row">
        <div class="col-12 table-responsive">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Listado de mis pendientes por leer</h4>
                    <div class="table-responsive">
                        <table id="example23" ref="dt" class="table table-bordered table-striped" cellspacing="0"
                            width="100%">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>Sede</th>
                                    <th>Fecha del Toma</th>
                                    <th>Fecha del Asignación</th>
                                    <th>Quien Asigno</th>
                                    <th>Paciente</th>
                                    <th>Detalle</th>
                                    <th>Producto</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in registros" :key="item.estudio_producto_id"
                                    :class="{ 'text-info': item.visto === 1 }" :id="'row-' + item.estudio_producto_id">
                                    <td width="2%">
                                        <button type="button" v-if="item.priori_id == 1"
                                            class="btn waves-effect waves-light btn-rounded btn-sm btn-danger">A</button>
                                        <button type="button" v-else-if="item.priori_id == 2"
                                            class="btn waves-effect waves-light btn-rounded btn-sm btn-warning">M</button>
                                        <button type="button" v-else-if="item.priori_id == 3"
                                            class="btn waves-effect waves-light btn-rounded btn-sm btn-info">B</button>
                                        <button type="button" v-else-if="item.priori_id == 4"
                                            class="btn waves-effect waves-light btn-rounded btn-sm">S</button>
                                    </td>
                                    <td>{{ item.nom_sede }}</td>
                                    <td>{{ item.fec_estudio }}</td>
                                    <td>{{ formatDate(item.created_at) }}</td>
                                    <td>{{ item.quien_registro }}</td>
                                    <td>
                                        {{ item.num_docu }} <br />
                                        {{ item.nom_pacien }}
                                    </td>
                                    <td>
                                        {{ item.study_desc }} <br />
                                        {{ item.accession_no }}
                                    </td>
                                    <td>
                                        {{ item.nom_produc }}
                                    </td>
                                    <td class="text-nowrap">
                                        <button type="button"
                                            class="btn waves-effect waves-light btn-rounded btn-outline-warning btn-sm m-r-5"
                                            :disabled="loadingAbrirId === item.estudio_producto_id"
                                            @click="actualizar = true; mostrarRegistro(item);">
                                            <i v-if="loadingAbrirId === item.estudio_producto_id"
                                                class="fa fa-spinner fa-spin"></i>
                                            <i v-else class="fa fa-pencil"></i>
                                        </button>
                                        <button type="button"
                                            class="btn waves-effect waves-light btn-rounded btn-outline-primary btn-sm"
                                            :disabled="loadingImagenId === item.estudio_producto_id"
                                            @click="verImagen(item.url_oviyam, item.num_docu, item.study_iuid, item.tap_oviyam, item.estudio_producto_id)"
                                            title="Ver imagenes">
                                            <i v-if="loadingImagenId === item.estudio_producto_id"
                                                class="fa fa-spinner fa-spin"></i>
                                            <i v-else class="fa fa-file-photo-o"></i>
                                        </button>
                                        <button type="button"
                                            class="btn waves-effect waves-light btn-rounded btn-outline-info btn-sm m-r-5"
                                            :disabled="loadingVistoId === item.estudio_producto_id"
                                            @click="actualizar = true; visto(item.estudio_producto_id);">
                                            <i v-if="loadingVistoId === item.estudio_producto_id"
                                                class="fa fa-spinner fa-spin"></i>
                                            <i v-else class="fa fa-eye"></i>
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
                    <i class="ti-close right-side-toggle" id="btnCerralModalForm" @click="btnCerralModalForm()"></i>
                </span>
            </div>
            <div class="r-panel-body">
                <!-- ============================================================== -->
                <!-- Aqui va el contenido de los formularios -->
                <!-- ============================================================== -->
                <form action="#">
                    <div class="form-body">
                        <div class="row">
                            <div class="col-md-6">

                                <h5 class="card-title text-info">
                                    <i class="fa fa-user"></i> Datos del Paciente
                                </h5>
                                <hr>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">

                                            <dl>
                                                <dt>Paciente:</dt>
                                                <dd>{{ registro.nom_pacien }}</dd>
                                                <dt># Documento:</dt>
                                                <dd>{{ registro.num_docu }}</dd>
                                            </dl>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <dl>
                                                <dt>Fecha de nacimiento:</dt>
                                                <dd>{{ registro.fec_naci }}</dd>
                                                <dt>Sexo:</dt>
                                                <dd>{{ registro.sexo }}</dd>
                                            </dl>
                                        </div>
                                    </div>
                                </div>

                                <h5 class="card-title text-info">
                                    <i class="fa fa-user"></i> Datos del Estudio
                                </h5>
                                <hr>

                                <input type="hidden" id="estudio_producto_id" name="estudio_producto_id"
                                    v-model="registro.estudio_producto_id" />
                                <input type="hidden" id="email" name="email" v-model="registro.email" />

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
                                <hr>

                                <div class="row table-responsive">
                                    <div class="col-md-12">
                                        <dl>
                                            <dd>{{ productoEstudio.nom_produc }}</dd>
                                        </dl>
                                    </div>
                                </div>
                                <br />
                                <h5 class="card-title text-info">
                                    <i class="fa fa-user"></i> Diagnosticos
                                </h5>
                                <hr>

                                <div class="row table-responsive" style="height:150px; overflow-y: scroll;">
                                    <div class="col-md-12">
                                        <ul v-for="(item, index) in diagnosticosEstudio" :key="index">
                                            <li>{{ item.nom_diagnos }}</li>

                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="row">
                                    <div class="col-9">
                                        <h5 class="card-title text-info">
                                            <i class="fa fa-edit"></i> Lectura
                                        </h5>
                                        <hr>
                                    </div>

                                    <div class="ml-auto">
                                        <ul class="list-inline text-center">
                                            <li>
                                                <button type="button" class="btn btn-secondary btn-xs"
                                                    :disabled="loadingImagenSidebar"
                                                    @click="verImagen(this.registro.urlOviyam, this.registro.patientId, this.registro.studyUID, this.registro.serverName)">
                                                    <i v-if="loadingImagenSidebar" class="fa fa-spinner fa-spin"></i>
                                                    <i v-else class="fa fa-file-image-o"></i>
                                                </button>
                                            </li>
                                            <li>
                                                <a class="" @click="toggleSpeechRecognition"
                                                    style="width: 30px; height: 30px;">
                                                    <img title="Grabadora" :src="microfono">
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>

                                <span class="text-danger" v-if="errores.lectura">{{ errores.lectura[0] }}</span>
                                <div class="row">
                                    <div class="col-12">
                                        <div class="form-group">
                                            <textarea v-model="registro.lectura" class="form-control" rows="15"
                                                id="lectura" style="height: 100%;"
                                                placeholder="Ingrese aquí la lectura del estudio ..."></textarea>
                                            <small v-if="recognizing && speechInterim" class="form-text text-muted">
                                                Dictando: <i>{{ speechInterim }}</i>
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">

                                        <!-- Nav tabs -->
                                        <ul class="nav nav-tabs" role="tablist">
                                            <li class="nav-item">
                                                <a class="nav-link active" data-toggle="tab" href="#home8" role="tab">
                                                    <h5 class=" card-title text-info">
                                                        <i class="fa fa-paperclip"></i> Adjuntos
                                                    </h5>
                                                </a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link" data-toggle="tab" href="#profile8" role="tab"
                                                    @click="listarAudios()">
                                                    <span>
                                                        <h5 class="card-title text-info">
                                                            <i class="fa fa-file-audio-o"></i> Audios
                                                        </h5>
                                                    </span>
                                                </a>
                                            </li>
                                            <!-- <li class="nav-item">
                                                <a class="nav-link" data-toggle="tab" href="#profile8" role="tab">
                                                    <span>
                                                        <h5 class="card-title text-info">
                                                            <i class="fa fa-file-audio-o"></i> Hisotial
                                                        </h5>
                                                    </span>
                                                </a>
                                            </li> -->
                                        </ul>
                                        <!-- Tab panes -->
                                        <div class="tab-content tabcontent-border">
                                            <div class="tab-pane active p-20" id="home8" role="tabpanel">
                                                <div class="col-md-8 col-lg-9"
                                                    v-for="(itemArchivo, index) in soportesHC" :key="index">
                                                    <a href="javascript:void(0)"
                                                        :style="{ pointerEvents: (loadingDescargaSoporte === itemArchivo.archivo_encrip) ? 'none' : 'auto' }"
                                                        @click="descargaSoportte(itemArchivo.archivo_original, itemArchivo.archivo_encrip)">
                                                        <i v-if="loadingDescargaSoporte === itemArchivo.archivo_encrip"
                                                            class="fa fa-spinner fa-spin"></i>
                                                        {{ itemArchivo.archivo_original }}
                                                    </a>
                                                </div>
                                            </div>
                                            <div class="tab-pane p-20" id="profile8" role="tabpanel">
                                                <div v-if="loadingAudios" class="text-muted">
                                                    <i class="fa fa-spinner fa-spin"></i> Cargando audios...
                                                </div>
                                                <template v-for="(item, index) in audiosDeEstudio" :key="index">
                                                    <audio :src="item.url" controls
                                                        style="height: 15px; width: 100%;"></audio>
                                                </template>
                                                <div v-if="!loadingAudios && (!audiosDeEstudio || audiosDeEstudio.length === 0)"
                                                    class="text-muted">
                                                    No hay audios para este estudio.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-actions mt-3">
                            <button type="button" class="btn btn-success m-r-5" @click="guardarRegistro()"
                                :disabled="loadingGuardar">
                                <i v-if="loadingGuardar" class="fa fa-spinner fa-spin"></i>
                                <i v-else class="fa fa-check"></i>
                                Guardar
                            </button>

                            <button type="button" class="btn btn-inverse" @click="btnCerralModalForm()">
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
</template>

<script>
import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc';
import timezone from 'dayjs/plugin/timezone';
import { safeDestroyDataTable, safeInitDataTable, safeRemoveDataTableRow } from "@/helpers/datatables.js";
import { handleAxiosErrorToast } from "@/helpers/alerts";

dayjs.extend(utc);
dayjs.extend(timezone);

export default {
    props: ['usuarioactual', 'permisos'],
    mounted() {
        console.log('Usuario actual:', this.usuarioactual);
        console.log('Permisos:', this.permisos);

        this.establecerSede();
        this.sede_id = sessionStorage.getItem('ST-sede');
        this.listarMisPendientes();

        // Prepara sinónimos (sin tildes) para comandos de voz
        this.prepareVoiceCommands();

        this.emitter.on("sedeSeleccionada", (data) => {
            this.sede_id = data.idSede;
            this.destroyDataTable();
            this.listarMisPendientes();
        });
    },
    beforeUnmount() {
        this.destroyDataTable();
    },
    data() {
        return {
            id: 0,
            registros: [],
            productoEstudio: { cod_produc: '', nom_produc: '' },
            diagnosticosEstudio: [],
            soportesHC: [],
            tituloModal: "Nuevo registro",
            registro: {
                estudio_producto_id: 0, lectura: "", fec_estudio: "", accession_no: "", study_desc: "", observaciones: "", num_docu: "", nom_pacien: "", sexo: "", fec_naci: "", email: "",
                diagnosticosEstudio: [], urlOviyam: '', patientId: '', studyUID: '', serverName: ''
            },
            errores: {},
            tipoPrioridad: 0,
            idRow: null,
            sede_id: null,
            loadingListar: false,
            loadingGuardar: false,
            loadingAbrirId: null,
            loadingVistoId: null,
            loadingDescargaSoporte: null,
            loadingImagenId: null,
            loadingImagenSidebar: false,
            loadingAudios: false,
            audiosDeEstudio: [],

            /**
             * Variables para convertir de voz a texto y grabacion de audio
             */
            recognizing: false,
            recognitionActive: false,
            recognitionRestartTimer: null,
            recording: false,
            microfono: 'admin-wrap/assets/images/mic.gif',
            recognition: null,
            mediaRecorder: null,
            audioChunks: [],
            audioBlob: null,
            audioUrl: null,
            speechInterim: "",
            uppercase: false,
            mediaStream: null,
            capitalizeNext: false,

            // Comandos de mayúsculas
            uppercaseCommands: {
                "iniciar mayúscula sostenida": () => { this.uppercase = true; return ""; },
                "iniciar mayúsculas sostenida": () => { this.uppercase = true; return ""; },
                "iniciar mayúsculas": () => { this.uppercase = true; return ""; },
                "iniciar mayúscula": () => { this.uppercase = true; return ""; },
                "mayúsculas sostenida": () => { this.uppercase = true; return ""; },
                "mayúscula sostenida": () => { this.uppercase = true; return ""; },
                "activar mayúsculas": () => { this.uppercase = true; return ""; },
                "activar mayúscula": () => { this.uppercase = true; return ""; },
                "habilitar mayúsculas": () => { this.uppercase = true; return ""; },
                "habilitar mayúscula": () => { this.uppercase = true; return ""; },
                "activare mayúsculas": () => { this.uppercase = true; return ""; },
                "activare mayúscula": () => { this.uppercase = true; return ""; },
                "desactivar mayúsculas": () => { this.uppercase = false; return ""; },
                "desactivar mayúscula": () => { this.uppercase = false; return ""; },
                "Deshabilitar mayúsculas": () => { this.uppercase = false; return ""; },
                "Deshabilitar mayúscula": () => { this.uppercase = false; return ""; },
                "terminar mayúsculas": () => { this.uppercase = false; return ""; },
                "terminar mayúscula": () => { this.uppercase = false; return ""; },
                "fin mayúsculas": () => { this.uppercase = false; return ""; },
                "fin mayúscula": () => { this.uppercase = false; return ""; },
                "finaliza mayúsculas": () => { this.uppercase = false; return ""; },
                "finaliza mayúscula": () => { this.uppercase = false; return ""; },
                "finalizar mayúsculas": () => { this.uppercase = false; return ""; },
                "finalizar mayúscula": () => { this.uppercase = false; return ""; },
            },

            // Comandos de saltos de línea y puntuación
            lineBreakCommands: {
                "punto aparte": () => ".\n\n",
                "salto de línea": () => "\n",
                "doble salto de línea": () => "\n\n",
                "enter": () => "\n",
                "doble enter": () => "\n\n",
                "línea en blanco": () => "\n\n",
                "doble línea en blanco": () => "\n\n\n\n",
            },

            // Otros comandos
            punctuationCommands: {
                "punto": () => { this.capitalizeNext = true; return "."; },
                "punto y seguido": () => { this.capitalizeNext = true; return ". "; },
                "punto seguido": () => { this.capitalizeNext = true; return ". "; },
                "coma": () => ",",
                "dos puntos": () => ":",
                "punto y coma": () => ";",
                "dar espacio": () => " ",
                "un espacio": () => " ",
                "dos espacio": () => "  ",
                "doble espacio": () => "  ",
                "abrir paréntesis": () => "(",
                "cerrar paréntesis": () => ")",
                "abrir corchetes": () => "[",
                "cerrar corchetes": () => "]",
                "abrir llaves": () => "{",
                "cerrar llaves": () => "}",
                "abrir comillas": () => "\"",
                "cerrar comillas": () => "\"",
                "abrir comillas simples": () => "'",
                "cerrar comillas simples": () => "'",
                "guion": () => "-",
                "guion bajo": () => "_",
                "barra diagonal": () => "/",
                "barra invertida": () => "\\",
                "nueva oración": () => ". "
            },
        };
    },
    methods: {
        stripDiacritics(text) {
            // Elimina tildes/diacríticos para comparar comandos (no modifica el texto final)
            try {
                return String(text).normalize('NFD').replace(/[\u0300-\u036f]/g, '');
            } catch (e) {
                return String(text);
            }
        },
        escapeRegExp(text) {
            return String(text).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        },
        prepareVoiceCommands() {
            // Agrega claves sin tildes a los diccionarios de comandos (ej: "mayuscula" -> "mayúscula")
            const addNormalizedKeys = (obj) => {
                if (!obj) return;
                const keys = Object.keys(obj);
                keys.forEach((k) => {
                    const normalized = this.stripDiacritics(k).toLowerCase();
                    if (!Object.prototype.hasOwnProperty.call(obj, normalized)) {
                        obj[normalized] = obj[k];
                    }
                });
            };

            addNormalizedKeys(this.uppercaseCommands);
            addNormalizedKeys(this.lineBreakCommands);
            addNormalizedKeys(this.punctuationCommands);
        },
        clearAudioBuffer() {
            // Limpia buffers/urls sin intentar detener nada (para cambios de estudio / reset UI)
            try {
                if (this.audioUrl) {
                    try { URL.revokeObjectURL(this.audioUrl); } catch (e) { }
                }
            } catch (e) { }
            this.audioChunks = [];
            this.audioBlob = null;
            this.audioUrl = null;
        },
        applyCommandMap(text, commandMap, { onEachMatch } = {}) {
            // Reemplaza comandos respetando letras Unicode (á/é/í/ó/ú/ñ) y priorizando frases largas
            if (!commandMap) return text;

            // Importante: primero comandos más largos para evitar colisiones (ej: "punto y coma" vs "punto")
            const keys = Object.keys(commandMap).sort((a, b) => b.length - a.length);
            keys.forEach((command) => {
                const escaped = this.escapeRegExp(command);
                // "Bordes" con Unicode: evita el problema de \b con caracteres acentuados
                const re = new RegExp(`(^|[^\\p{L}\\p{N}_])(${escaped})(?=$|[^\\p{L}\\p{N}_])`, 'giu');

                text = text.replace(re, (match, prefix) => {
                    if (typeof onEachMatch === 'function') {
                        try { onEachMatch(command); } catch (e) { }
                    }
                    const fn = commandMap[command];
                    const replacement = (typeof fn === 'function') ? fn() : String(fn);
                    return `${prefix}${replacement}`;
                });
            });

            return text;
        },
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
        getMicErrorToastConfig(error) {
            const name = error && error.name ? String(error.name) : "";
            const message = error && error.message ? String(error.message) : String(error);

            // Casos típicos: permisos denegados / contexto inseguro / sin mic / mic ocupado
            if (name === "NotAllowedError" || /permission denied/i.test(message)) {
                return {
                    heading: "Permiso de micrófono",
                    text:
                        "El navegador negó el acceso al micrófono. " +
                        "Solución: en el ícono del candado del navegador → Permisos del sitio → Micrófono → Permitir, y recarga. " +
                        "Si estás en Windows, revisa Configuración → Privacidad y seguridad → Micrófono y permite el acceso para tu navegador.",
                    icon: "warning",
                    hideAfter: 7000,
                };
            }

            if (name === "SecurityError" || /secure origins|only secure origins/i.test(message)) {
                return {
                    heading: "HTTPS requerido",
                    text:
                        "El micrófono solo funciona en contextos seguros (HTTPS o localhost). " +
                        "Abre el sistema en `https://` (por ejemplo `https://lidiag.test`) y acepta el certificado si corresponde, luego recarga.",
                    icon: "warning",
                    hideAfter: 8000,
                };
            }

            if (name === "NotFoundError" || /notfound/i.test(name)) {
                return {
                    heading: "Sin micrófono",
                    text: "No se encontró un dispositivo de micrófono. Conecta/activa un micrófono y vuelve a intentar.",
                    icon: "warning",
                    hideAfter: 7000,
                };
            }

            if (name === "NotReadableError") {
                return {
                    heading: "Micrófono ocupado",
                    text:
                        "El micrófono está siendo usado por otra aplicación o el navegador no puede acceder a él. " +
                        "Cierra apps que lo estén usando (Meet/Zoom/otra pestaña) y reintenta.",
                    icon: "warning",
                    hideAfter: 7000,
                };
            }

            return {
                heading: "Upsss!!!",
                text: "Error al acceder al micrófono: " + (message || name || "desconocido"),
                icon: "danger",
                hideAfter: 4500,
            };
        },
        async ensureMicApiAvailable() {
            if (!navigator || !navigator.mediaDevices || typeof navigator.mediaDevices.getUserMedia !== "function") {
                $.toast({
                    heading: "No soportado",
                    text: "Este navegador no soporta acceso al micrófono (getUserMedia). Usa Chrome/Edge actualizado.",
                    position: "top-right",
                    loaderBg: "#ff6849",
                    icon: "warning",
                    hideAfter: 7000,
                    stack: 6,
                });
                return false;
            }
            return true;
        },
        clearRecognitionRestartTimer() {
            if (this.recognitionRestartTimer) {
                try { clearTimeout(this.recognitionRestartTimer); } catch (e) { }
                this.recognitionRestartTimer = null;
            }
        },
        safeStartRecognition() {
            if (!this.recognition) return false;
            // Evita InvalidStateError (start() cuando ya está activo)
            if (this.recognitionActive) return false;

            try {
                this.recognition.start();
                return true;
            } catch (e) {
                // InvalidStateError: ya inició (race). No lo tratamos como fatal.
                return false;
            }
        },
        scheduleRecognitionRestart(delayMs = 500) {
            this.clearRecognitionRestartTimer();
            if (!this.recognition || !this.recognizing) return;

            this.recognitionRestartTimer = setTimeout(() => {
                // Si sigue en modo "reconociendo", intenta arrancar.
                if (!this.recognizing || !this.recognition) return;
                this.safeStartRecognition();
            }, delayMs);
        },
        pauseRecognition() {
            // SpeechRecognition no tiene "pause": se simula con stop() y una bandera
            if (this.recognition && this.recognizing) {
                try {
                    this.recognizing = false; // evita auto-restart en onend
                    this.clearRecognitionRestartTimer();
                    this.recognition.stop();
                } catch (e) {
                    // best-effort
                    this.recognizing = false;
                }
            } else {
                this.recognizing = false;
            }
        },
        resumeRecognition() {
            if (this.recognition && !this.recognizing) {
                try {
                    this.recognizing = true;
                    this.safeStartRecognition();
                } catch (e) {
                    // Algunos navegadores lanzan InvalidStateError si ya está arrancado
                    // best-effort: mantener bandera consistente
                    this.recognizing = false;
                }
            }
        },
        toggleSpeechRecognition() {
            // Comportamiento acordado:
            // - Primer clic: inicia dictado + grabación
            // - Siguientes clics: pausa/reanuda AMBOS (audio + dictado)
            if (!this.mediaRecorder || !this.recognition) {
                this.startRecording();
                return;
            }

            const state = this.mediaRecorder.state;
            if (state === "recording") {
                try { this.mediaRecorder.pause(); } catch (e) { }
                this.pauseRecognition();
                this.microfono = 'admin-wrap/assets/images/mic.gif';
            } else if (state === "paused") {
                try { this.mediaRecorder.resume(); } catch (e) { }
                this.resumeRecognition();
                this.microfono = 'admin-wrap/assets/images/mic-animate.gif';
            } else if (state === "inactive") {
                this.startRecording();
            }
        },
        // Inicia una nueva grabación de audio sin tocar el reconocimiento de voz
        startAudioOnly() {
            // Debe ejecutarse por un gesto del usuario (click/touch) o el navegador lo bloqueará.
            this.ensureMicApiAvailable().then((ok) => {
                if (!ok) return;

                // Asegura que no queden streams previos abiertos
                if (this.mediaStream && this.mediaStream.getTracks) {
                    try { this.mediaStream.getTracks().forEach(t => t.stop()); } catch (e) { }
                    this.mediaStream = null;
                }
                navigator.mediaDevices.getUserMedia({ audio: true }).then(stream => {
                    try {
                        this.mediaRecorder = new MediaRecorder(stream, {
                            mimeType: "audio/webm;codecs=opus",
                            audioBitsPerSecond: 64000,
                        });
                    } catch (e) {
                        // Fallback for browsers that don't support the mimeType
                        this.mediaRecorder = new MediaRecorder(stream);
                    }
                    this.mediaStream = stream;
                    this.audioChunks = [];
                    this.audioBlob = null;

                    this.mediaRecorder.ondataavailable = (event) => {
                        this.audioChunks.push(event.data);
                    };

                    this.mediaRecorder.onstop = () => {
                        this.audioBlob = new Blob(this.audioChunks, { type: 'audio/webm' });
                        this.audioUrl = URL.createObjectURL(this.audioBlob);
                    };

                    this.mediaRecorder.start();
                    this.recording = true;
                    this.microfono = 'admin-wrap/assets/images/mic-animate.gif';
                }).catch(error => {
                    this.recording = false;
                    this.microfono = 'admin-wrap/assets/images/mic.gif';
                    const cfg = this.getMicErrorToastConfig(error);
                    $.toast({
                        heading: cfg.heading,
                        text: cfg.text,
                        position: "top-right",
                        loaderBg: "#ff6849",
                        icon: cfg.icon,
                        hideAfter: cfg.hideAfter,
                        stack: 6,
                    });
                    console.error('Error al acceder al micrófono:', error);
                });
            });
        },
        // Permite detener explícitamente el reconocimiento de voz
        stopRecognition() {
            try {
                if (this.recognition) {
                    // Evita reinicios automáticos mientras se detiene
                    this.recognition.onend = null;
                    this.recognition.onerror = null;
                    // Si se detiene el reconocimiento, pausar también la grabación de audio
                    if (this.mediaRecorder && this.mediaRecorder.state === 'recording') {
                        try { this.mediaRecorder.pause(); } catch (e) { console.warn('No se pudo pausar la grabación al detener reconocimiento:', e); }
                        this.microfono = 'admin-wrap/assets/images/mic.gif';
                    }
                    this.recognition.stop();
                }
                this.recognizing = false;
                this.microfono = 'admin-wrap/assets/images/mic.gif';
                this.speechInterim = "";
            } catch (e) {
                console.error('Error al detener reconocimiento:', e);
            }
        },
        // Detiene todo: reconocimiento y grabación, y limpia recursos
        async stopAll() {
            try {
                // Detener grabación y generar blob si corresponde
                await this.stopRecording();
                // Detener reconocimiento de voz
                this.stopRecognition();
                // Liberar media stream
                if (this.mediaStream && this.mediaStream.getTracks) {
                    try { this.mediaStream.getTracks().forEach(t => t.stop()); } catch (e) { }
                    this.mediaStream = null;
                }
                // Revocar URL de audio si existe
                if (this.audioUrl) {
                    try { URL.revokeObjectURL(this.audioUrl); } catch (e) { }
                    this.audioUrl = null;
                }
                // Indicadores de estado
                this.recording = false;
                this.microfono = 'admin-wrap/assets/images/mic.gif';
            } catch (e) {
                console.error('Error en stopAll:', e);
            }
        },
        startRecording() {
            // Debe ejecutarse por un gesto del usuario (click/touch) o el navegador lo bloqueará.
            // Preflight: asegurar API
            // Nota: SpeechRecognition también puede solicitar micrófono; por eso manejamos bien NotAllowedError.
            // (no hacemos return si SpeechRecognition existe; solo verificamos getUserMedia)
            // eslint-disable-next-line no-unused-vars
            const _micReadyPromise = this.ensureMicApiAvailable();
            if (!this.recognition) {
                // Asegura que existan sinónimos antes de crear gramática
                this.prepareVoiceCommands();

                this.recognition = new (window.SpeechRecognition || window.webkitSpeechRecognition || window.mozSpeechRecognition)();
                if (!this.recognition) {
                    $.toast({
                        heading: "Error",
                        text: 'Reconocimiento de voz no soportado en este navegador.',
                        position: "top-right",
                        loaderBg: "#ff6849",
                        icon: "error",
                        hideAfter: 5000,
                    });
                    return;
                }
                this.recognition.lang = 'es-ES';
                this.recognition.continuous = true;
                this.recognition.interimResults = true;
                this.recognition.maxAlternatives = 1; // número de alternativas

                // Combinar todos los comandos en un solo objeto
                const allCommands = {
                    ...this.uppercaseCommands,
                    ...this.lineBreakCommands,
                    ...this.punctuationCommands
                };

                // Implementación de SpeechGrammarList para optimizar los comandos (best-effort)
                // Nota: en algunos navegadores puede fallar por longitud/charset de la gramática.
                try {
                    const SpeechGrammarListCtor = (window.SpeechGrammarList || window.webkitSpeechGrammarList);
                    if (SpeechGrammarListCtor) {
                        const speechRecognitionList = new SpeechGrammarListCtor();
                        const commandsList = Object.keys(allCommands);
                        const grammar = `#JSGF V1.0; grammar commands; public <command> = ${commandsList.join(" | ")};`;
                        speechRecognitionList.addFromString(grammar, 1);
                        this.recognition.grammars = speechRecognitionList;
                    }
                } catch (e) {
                    console.warn("No se pudo configurar SpeechGrammarList:", e);
                }

                this.recognition.onresult = (event) => {
                    let finalText = '';
                    let interimText = '';

                    for (let i = event.resultIndex; i < event.results.length; i++) {
                        const result = event.results[i];
                        const transcript = result && result[0] && result[0].transcript ? String(result[0].transcript) : '';

                        if (result && result.isFinal) {
                            let text = transcript.trim();
                            text = this.processCommands(text);

                            // Si el resultado final es un comando "vacío" (ej: activar mayúsculas), no agregues espacios.
                            if (text && String(text).length) {
                                finalText += text;
                                // Agregar espacio SOLO si el texto no termina en espacio/salto de línea
                                if (!/[ \n]$/.test(finalText)) finalText += ' ';
                            }
                        } else if (transcript) {
                            // Mostrar dictado parcial sin tocar el textarea (evita duplicación).
                            interimText += transcript;
                        }
                    }

                    if (finalText) {
                        this.registro.lectura += finalText;
                        this.speechInterim = "";
                    } else {
                        this.speechInterim = interimText.trim();
                    }
                };

                // Flags para evitar dobles starts (Chrome lanza InvalidStateError si arrancas 2 veces)
                this.recognition.onstart = () => {
                    this.recognitionActive = true;
                };

                this.recognition.onend = () => {
                    this.recognitionActive = false;
                    if (this.recognizing) {
                        this.scheduleRecognitionRestart(500);
                    } else {
                        // Reconocimiento fue detenido/pausado: pausar también la grabación de audio
                        if (this.mediaRecorder && this.mediaRecorder.state === 'recording') {
                            try { this.mediaRecorder.pause(); } catch (e) { console.warn('No se pudo pausar la grabación en onend:', e); }
                            this.microfono = 'admin-wrap/assets/images/mic.gif';
                        }
                    }
                };

                this.recognition.onerror = (event) => {
                    console.warn('Error en reconocimiento:', event && event.error ? event.error : event);
                    const err = event && event.error ? String(event.error) : '';

                    // Si el usuario/bloqueo del navegador niega permisos, NO reintentar (evita loop infinito).
                    if (err === 'not-allowed' || err === 'service-not-allowed' || err === 'permission-denied') {
                        this.recognizing = false;
                        this.clearRecognitionRestartTimer();
                        this.microfono = 'admin-wrap/assets/images/mic.gif';
                        this.speechInterim = "";
                        try { this.recognition.onend = null; } catch (e) { }
                        try { this.recognition.stop(); } catch (e) { }

                        $.toast({
                            heading: "Permiso requerido",
                            text: "El navegador bloqueó el micrófono. Permite el acceso al micrófono para usar dictado.",
                            position: "top-right",
                            loaderBg: "#ff6849",
                            icon: "warning",
                            hideAfter: 6000,
                            stack: 6,
                        });
                        return;
                    }

                    // En errores comunes (silencio o red), detenemos.
                    // El reinicio queda centralizado en onend() para evitar doble start().
                    if (err === 'no-speech' || err === 'network') {
                        try { this.recognition.stop(); } catch (e) { }
                    }
                };
            }

            try {
                this.recognizing = true;
                this.safeStartRecognition();
            } catch (e) {
                this.recognizing = false;
            }
            this.microfono = 'admin-wrap/assets/images/mic-animate.gif';

            // Asegura que no queden streams previos abiertos
            if (this.mediaStream && this.mediaStream.getTracks) {
                try { this.mediaStream.getTracks().forEach(t => t.stop()); } catch (e) { }
                this.mediaStream = null;
            }
            navigator.mediaDevices.getUserMedia({ audio: true }).then(stream => {
                try {
                    this.mediaRecorder = new MediaRecorder(stream, {
                        mimeType: "audio/webm;codecs=opus",
                        audioBitsPerSecond: 64000, // Reduce la calidad (valores entre 16kbps y 128kbps)
                    });
                } catch (e) {
                    // Fallback for browsers that don't support the mimeType
                    this.mediaRecorder = new MediaRecorder(stream);
                }
                this.mediaStream = stream;
                this.audioChunks = [];
                this.audioBlob = null;

                this.mediaRecorder.ondataavailable = (event) => {
                    this.audioChunks.push(event.data);
                };

                this.mediaRecorder.onstop = () => {
                    this.audioBlob = new Blob(this.audioChunks, { type: 'audio/webm' });
                    this.audioUrl = URL.createObjectURL(this.audioBlob);
                };

                this.mediaRecorder.start();
                this.recording = true;
            }).catch(error => {
                this.recording = false;
                this.microfono = 'admin-wrap/assets/images/mic.gif';
                const cfg = this.getMicErrorToastConfig(error);
                $.toast({
                    heading: cfg.heading,
                    text: cfg.text,
                    position: "top-right",
                    loaderBg: "#ff6849",
                    icon: cfg.icon,
                    hideAfter: cfg.hideAfter,
                    stack: 6,
                });
                console.error('Error al acceder al micrófono:', error);
            });
        },
        stopRecording() {
            return new Promise((resolve) => {
                if (this.mediaRecorder && this.recording) {
                    this.mediaRecorder.onstop = () => {
                        this.audioBlob = new Blob(this.audioChunks, { type: "audio/webm" });
                        this.audioUrl = URL.createObjectURL(this.audioBlob);
                        resolve(); // Resuelve la promesa después de generar el Blob
                    };
                    this.mediaRecorder.stop();
                    this.recording = false;
                    this.microfono = 'admin-wrap/assets/images/mic.gif';
                } else {
                    resolve(); // Si ya está detenida, simplemente resuelve la promesa
                }
            });
        },
        processCommands(text) {
            if (typeof this.uppercase === "undefined") {
                this.uppercase = false;
            }

            // Asegura sinónimos sin tildes por si el dictado viene sin acentos
            this.prepareVoiceCommands();

            // Procesar comandos de mayúsculas
            text = this.applyCommandMap(text, this.uppercaseCommands);

            // Procesar comandos de saltos de línea
            text = this.applyCommandMap(text, this.lineBreakCommands, {
                onEachMatch: () => { this.capitalizeNext = true; }
            });

            // Procesar comandos de puntuación y espacios
            text = this.applyCommandMap(text, this.punctuationCommands);

            // Convertir texto a mayúsculas si está activado
            text = this.uppercase ? text.toUpperCase() : text;

            // Aplicar mayúscula en la primera letra del nuevo párrafo
            // solo cuando un comando de salto de línea haya sido reconocido
            if (this.capitalizeNext) {
                text = text.replace(/(^|\n)(\s*)([a-záéíóúüñ])/i, (m, start, spaces, first) => {
                    this.capitalizeNext = false; // Resetear capitalización tras usarla
                    return `${start}${spaces}${first.toUpperCase()}`;
                });
            }

            return text;
        },
        async sendToBackend() {
            await this.stopRecording(); // Esperar a que el blob esté listo

            // Acordado: si no hay audio, no hacer nada (sin toasts)
            if (!this.audioBlob || !(this.audioBlob instanceof Blob)) return;

            const formData = new FormData();
            formData.append('audio', this.audioBlob, 'audio.webm');
            formData.append('estudio_id', this.id);
            formData.append('estudio_producto_id', this.registro.estudio_producto_id);

            try {
                const res = await axios.post('/api/upload-audio', formData);

                if (res && res.status === 200) {
                    $.toast({
                        heading: "Ok!!!",
                        text: 'Audio enviado correctamente',
                        position: "top-right",
                        loaderBg: "#ff6849",
                        icon: "success",
                        hideAfter: 3500,
                        stack: 6,
                    });
                    // Limpieza de estado de audio tras envío exitoso
                    this.clearAudioBuffer();
                    // Refrescar listado de audios del estudio (para reproducirlo de inmediato)
                    await this.listarAudios();
                } else {
                    // Acordado: si el backend dice "sin contenido", no hacer nada (sin toasts)
                    const msg = (res && res.data && res.data.message) ? String(res.data.message) : '';
                    if (msg.includes('El audio debe tener contenido') || msg.includes('No se proporcionó un archivo de audio')) {
                        this.clearAudioBuffer();
                        return;
                    }
                    $.toast({
                        heading: "Upsss!!!",
                        text: (res && res.data && res.data.message) ? res.data.message : 'Error al enviar audio',
                        position: "top-right",
                        loaderBg: "#ff6849",
                        icon: "error",
                        hideAfter: 3500,
                        stack: 6,
                    });
                }

            } catch (error) {
                const status = error && error.response ? error.response.status : null;
                const msg = error && error.response && error.response.data && error.response.data.message
                    ? String(error.response.data.message)
                    : '';

                // Acordado: si es "audio sin contenido", no hacer nada (sin toasts)
                if (status === 400 && (msg.includes('El audio debe tener contenido') || msg.includes('No se proporcionó un archivo de audio'))) {
                    this.clearAudioBuffer();
                    return;
                }

                console.error('Error al enviar audio:', error);
                $.toast({
                    heading: "Upsss!!!",
                    text: 'Fallo al enviar audio',
                    position: "top-right",
                    loaderBg: "#ff6849",
                    icon: "error",
                    hideAfter: 3500,
                    stack: 6,
                });
            }
        },
        async listarAudios() {
            if (this.loadingAudios) return;
            this.loadingAudios = true;
            try {
                if (!this.id) {
                    this.audiosDeEstudio = [];
                    return;
                }
                const res = await axios.get('/api/listar-audios-estudio/' + this.id);
                this.audiosDeEstudio = res.data;
            } catch (e) {
                handleAxiosErrorToast(e, "No fue posible listar audios.");
                this.audiosDeEstudio = [];
            } finally {
                this.loadingAudios = false;
            }
        },
        async listarMisPendientes() {
            if (sessionStorage.getItem('ST-sede') == null) {
                $.toast({
                    heading: "Upsss!!!",
                    text: 'Debes establece la sede en la cual vas a trabajar.',
                    position: "top-right",
                    loaderBg: "#ff6849",
                    icon: "warning",
                    hideAfter: 3500,
                    stack: 6,
                });
            } else {
                if (this.loadingListar) return;
                this.loadingListar = true;
                try {
                    const res = await axios.post('/api/estudio-listarPendientesMedico', { id: this.usuarioactual.id, sede_id: this.sede_id });
                    this.destroyDataTable();

                    this.registros = res.data;

                    this.$nextTick(() => {
                        this.initDataTable();
                    });
                } catch (error) {
                    handleAxiosErrorToast(error, "No fue posible listar los pendientes.");
                } finally {
                    this.loadingListar = false;
                }
            }
        },
        async guardarRegistro() {
            if (this.loadingGuardar) return;
            this.loadingGuardar = true;
            try {
                // Acordado: al Guardar, detener automáticamente dictado+grabación
                await this.stopAll();

                const res = await axios.post('/api/estudio-leerEstudio', this.registro);

                if (res.status == 200) {

                    /**
                     * Envio el audio al backend
                     */
                    await this.sendToBackend();

                    $.toast({
                        heading: "Ok!!!",
                        text: res.data.message,
                        position: "top-right",
                        loaderBg: "#ff6849",
                        icon: "success",
                        hideAfter: 3500,
                        stack: 6,
                    });

                    const estudioEliminar = this.registros.findIndex((x) => x.estudio_producto_id == this.idRow);
                    if (estudioEliminar >= 0) {
                        // En guardado NO destruimos/reinicializamos la tabla:
                        // solo removemos la fila correspondiente y mantenemos el resto de pendientes visible.
                        try {
                            safeRemoveDataTableRow(this.$refs.dt || "#example23", "#row-" + this.idRow);
                        } catch (e) {
                            // best-effort: si DataTables no está listo, seguimos con el splice (Vue actualizará el DOM)
                        }
                        this.registros.splice(estudioEliminar, 1);
                    }

                    this.btnCerralModalForm();
                    this.limpiar();
                }
            } catch (error) {
                this.errores = error.response && error.response.data ? error.response.data.errors : {};
                handleAxiosErrorToast(error, "No fue posible guardar la lectura.");
            } finally {
                this.loadingGuardar = false;
            }
        },
        async visto(id) {
            if (this.loadingVistoId) return;
            this.loadingVistoId = id;

            try {
                await axios.post('/api/marcar-visto/' + id);
                await this.listarMisPendientes();
            } catch (error) {
                handleAxiosErrorToast(error, "No fue posible marcar como visto.");
            } finally {
                this.loadingVistoId = null;
            }
        },
        async mostrarRegistro(data = {}) {
            if (this.loadingAbrirId !== null) return;
            this.loadingAbrirId = data.estudio_producto_id;

            // Acordado: al abrir un nuevo estudio, limpiar cualquier audio/dictado previo
            await this.stopAll();
            this.clearAudioBuffer();
            this.uppercase = false;
            this.capitalizeNext = false;
            this.audiosDeEstudio = [];

            this.tituloModal = "Lectura de paciente: " + data.nom_pacien + "Producto: \n" + data.nom_produc;
            this.id = data.id;
            this.idRow = data.estudio_producto_id;
            this.registro.estudio_producto_id = data.estudio_producto_id;
            this.registro.fec_estudio = data.fec_estudio;
            this.registro.accession_no = data.accession_no;
            this.registro.study_desc = data.study_desc;

            this.registro.num_docu = data.num_docu;
            this.registro.nom_pacien = data.nom_pacien;
            this.registro.sexo = data.pat_sex;
            this.registro.fec_naci = data.pat_birthdate;

            this.registro.urlOviyam = data.url_oviyam;
            this.registro.patientId = data.num_docu;
            this.registro.studyUID = data.study_iuid;
            this.registro.serverName = data.tap_oviyam;

            this.productoEstudio.cod_produc = data.cod_cups;
            this.productoEstudio.nom_produc = data.nom_produc;

            try {
                const res = await axios.get('/api/study-listar-diagnostico-por-estudio?id=' + this.id);
                this.diagnosticosEstudio = res.data;

                const resSoportesHC = await axios.get('/api/listar-soportes-hc-estudio?id=' + this.id);
                this.soportesHC = resSoportesHC.data;

                await this.listarAudios();

                this.btnCerralModalForm();
                this.errores = {};
            } catch (error) {
                handleAxiosErrorToast(error, "No fue posible cargar el estudio seleccionado.");
            } finally {
                this.loadingAbrirId = null;
            }
        },
        descargaSoportte(nomArchivoOriginal, nomArchivoEncriptado) {
            if (this.loadingDescargaSoporte) return;
            this.loadingDescargaSoporte = nomArchivoEncriptado;
            const url = `/api/descarga-soportes-hc/${nomArchivoEncriptado}`;

            axios.get(url, { responseType: 'blob' })
                .then(response => {
                    const blobUrl = window.URL.createObjectURL(new Blob([response.data]));
                    const link = document.createElement('a');
                    link.href = blobUrl;
                    link.setAttribute('download', nomArchivoOriginal);
                    document.body.appendChild(link);
                    link.click();
                    setTimeout(() => window.URL.revokeObjectURL(blobUrl), 2000);
                })
                .catch(error => {
                    handleAxiosErrorToast(error, "No fue posible descargar el soporte.");
                })
                .finally(() => {
                    this.loadingDescargaSoporte = null;
                });
        },
        verImagen(url, pat_id, study_iuid, servername, estudio_producto_id = null) {
            // No hace axios, pero evitamos doble click y mostramos spinner breve
            if (estudio_producto_id !== null) {
                if (this.loadingImagenId) return;
                this.loadingImagenId = estudio_producto_id;
            } else {
                if (this.loadingImagenSidebar) return;
                this.loadingImagenSidebar = true;
            }

            try {
                const Url = `${url}?patientID=${encodeURIComponent(pat_id)}&studyUID=${encodeURIComponent(study_iuid)}&serverName=${encodeURIComponent(servername)}`;
                window.open(Url, '_blank', 'noopener,noreferrer');
            } finally {
                setTimeout(() => {
                    this.loadingImagenId = null;
                    this.loadingImagenSidebar = false;
                }, 500);
            }
        },
        establecerSede() {
            this.sede_id = sessionStorage.getItem('ST-sede');
            this.registros = [];
        },
        formatDate(timestamp) {
            // Convertir el timestamp UTC a la zona horaria local y formatear
            return dayjs.utc(timestamp).local().format('YYYY-MM-DD HH:mm:ss');
        },
        limpiar() {
            this.tituloModal = "";
            this.id = 0;
            this.registro.estudio_producto_id = "";
            this.registro.fec_estudio = "";
            this.registro.accession_no = "";
            this.registro.study_desc = "";

            this.registro.num_docu = "";
            this.registro.nom_pacien = "";
            this.registro.sexo = "";
            this.registro.fec_naci = "";
            this.registro.lectura = "";

            // Limpieza de audio/dictado para evitar envíos cruzados
            this.clearAudioBuffer();
            this.uppercase = false;
            this.capitalizeNext = false;
            this.speechInterim = "";
            this.audiosDeEstudio = [];
        },
        btnCerralModalForm() {
            $(".right-sidebar").slideDown(50);
            $(".right-sidebar").toggleClass("shw-rside");
        },
    },
    beforeDestroy() {
        try {
            if (this.recognition) {
                this.recognizing = false;
                this.recognition.onresult = null;
                this.recognition.onend = null;
                this.recognition.onerror = null;
                this.recognition.stop();
                this.recognition = null;
            }
            if (this.mediaRecorder) {
                if (this.mediaRecorder.state && this.mediaRecorder.state !== 'inactive') {
                    try { this.mediaRecorder.stop(); } catch (e) { }
                }
                this.mediaRecorder = null;
            }
            if (this.mediaStream && this.mediaStream.getTracks) {
                this.mediaStream.getTracks().forEach(t => t.stop());
                this.mediaStream = null;
            }
            if (this.audioUrl) {
                try { URL.revokeObjectURL(this.audioUrl); } catch (e) { }
                this.audioUrl = null;
            }
            if (this.emitter && this.emitter.off) {
                try { this.emitter.off('sedeSeleccionada'); } catch (e) { }
            }
        } catch (e) {
            // best-effort cleanup
        }
    }
}
</script>

<style scoped>
.right-sidebar {
    position: fixed;
    right: -100%;
    width: 100%;
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
    width: 100%;
    display: block;
}

.audio {
    width: 50px;
    height: 5px;
}
</style>

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
                        <table id="example23" class="table table-bordered table-striped" cellspacing="0" width="100%">
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
                                <tr v-for="(item, index) in registros" :key="index"
                                    :class="{ 'text-info': item.visto === 1 }" :id="item.estudio_producto_id">
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
                                            @click="actualizar = true; mostrarRegistro(item);">
                                            <i class="fa fa-pencil"></i>
                                        </button>
                                        <button type="button"
                                            class="btn waves-effect waves-light btn-rounded btn-outline-primary btn-sm"
                                            @click="verImagen(item.url_oviyam, item.num_docu, item.study_iuid, item.tap_oviyam)"
                                            title="Ver imagenes">
                                            <i class="fa fa-file-photo-o"></i>
                                        </button>
                                        <button type="button"
                                            class="btn waves-effect waves-light btn-rounded btn-outline-info btn-sm m-r-5"
                                            @click="actualizar = true; visto(item.estudio_producto_id);">
                                            <i class="fa fa-eye"></i>
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
                                    <div class="col-3">
                                        <div class="col-md-7 align-self-center text-right d-none d-md-block">
                                            <button type="button" class="btn btn-secondary btn-xs"
                                                @click="verImagen(this.registro.urlOviyam, this.registro.patientId, this.registro.studyUID, this.registro.serverName)">
                                                <i class="fa fa-file-image-o"></i>
                                            </button>
                                             <a class="" @click="toggleSpeechRecognition"
                                                 style="width: 30px; height: 30px;">
                                                 <img title="Grabadora" :src="microfono">
                                             </a>
                                        </div>
                                    </div>
                                </div>

                                <span class="text-danger" v-if="errores.lectura">{{ errores.lectura[0] }}</span>
                                <div class="row">
                                    <div class="col-12">
                                        <div class="form-group">
                                            <textarea v-model="registro.lectura" class="textarea_editor form-control"
                                                rows="15" id="lectura" style="height: 100%;"
                                                placeholder="Ingrese aquí la lectura del estudio ..."></textarea>
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
                                                <a class="nav-link" data-toggle="tab" href="#profile8" role="tab">
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
                                                        @click="descargaSoportte(itemArchivo.archivo_original, itemArchivo.archivo_encrip)">
                                                        {{ itemArchivo.archivo_original }}
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-actions mt-3">
                            <button type="button" class="btn btn-success m-r-5" @click="guardarRegistro()">
                                <i class="fa fa-check"></i> Guardar
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

dayjs.extend(utc);
dayjs.extend(timezone);

export default {
    props: ['usuarioactual'],
    mounted() {
        this.establecerSede();
        this.sede_id = sessionStorage.getItem('ST-sede');
        this.listarMisPendientes();

        this.emitter.on("sedeSeleccionada", (data) => {
            this.sede_id = data.idSede;
            this.destroyDataTable();
            this.listarMisPendientes();
        });
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

            /**
             * Variables para convertir de voz a texto y grabacion de audio
             */
            recognizing: false,
            recording: false,
            microfono: 'admin-wrap/assets/images/mic.gif',
            recognition: null,
            mediaRecorder: null,
            audioChunks: [],
            audioUrl: null,
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
        initDataTable() {
            if ($ && $.fn && $.fn.DataTable) {
                $("#example23").DataTable({
                    dom: "Bfrtip",
                    buttons: ["copy", "csv", "excel", "pdf", "print"],
                    retrieve: true,
                });
            }
        },
        destroyDataTable() {
            try {
                if ($ && $.fn && $.fn.DataTable && $.fn.dataTable.isDataTable('#example23')) {
                    $("#example23").DataTable().destroy();
                }
            } catch (e) {
                // No-op
            }
        },
        toggleSpeechRecognition() {
            if (!this.recognizing) {
                this.startRecording();
            } else if (this.mediaRecorder) {
                console.log('antes mediaRecorder.state: ', this.mediaRecorder.state);
                if (this.mediaRecorder.state === "recording") {
                    console.log("⏸️ Pausando grabación...");
                    this.mediaRecorder.pause();
                    this.microfono = 'admin-wrap/assets/images/mic.gif';
                } else if (this.mediaRecorder.state === "paused") {
                    console.log("▶️ Reanudando grabación...");
                    this.mediaRecorder.resume();
                    this.microfono = 'admin-wrap/assets/images/mic-animate.gif';
                } else if (this.mediaRecorder.state === "inactive") {
                    console.log("⏺️ Iniciando nueva grabación para la siguiente lectura...");
                    this.startAudioOnly();
                    this.microfono = 'admin-wrap/assets/images/mic-animate.gif';
                }
                console.log('despues mediaRecorder.state: ', this.mediaRecorder.state);
            }
        },
        // Inicia una nueva grabación de audio sin tocar el reconocimiento de voz
        startAudioOnly() {
            navigator.mediaDevices.getUserMedia({ audio: true }).then(stream => {
                this.mediaRecorder = new MediaRecorder(stream, {
                    mimeType: "audio/webm;codecs=opus",
                    audioBitsPerSecond: 64000,
                });
                this.mediaStream = stream;
                this.audioChunks = [];

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
                $.toast({
                    heading: "Upsss!!!",
                    text: 'Error al acceder al micrófono: ' + (error && error.message ? error.message : String(error)),
                    position: "top-right",
                    loaderBg: "#ff6849",
                    icon: "danger",
                    hideAfter: 3500,
                    stack: 6,
                });
                console.error('Error al acceder al micrófono:', error);
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
            if (!this.recognition) {
                this.recognition = new (window.SpeechRecognition || window.webkitSpeechRecognition)();
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

                // Implementación de SpeechGrammarList para optimizar los comandos
                const speechRecognitionList = new (window.SpeechGrammarList || window.webkitSpeechGrammarList)();
                const commandsList = Object.keys(allCommands);
                const grammar = `#JSGF V1.0; grammar commands; public <command> = ${commandsList.join(" | ")};`;
                speechRecognitionList.addFromString(grammar, 1);
                this.recognition.grammars = speechRecognitionList;

                this.recognition.onresult = (event) => {
                    let finalText = '';
                    for (let i = event.resultIndex; i < event.results.length; i++) {
                        if (event.results[i].isFinal) {
                            let text = event.results[i][0].transcript.trim();
                            console.log("Texto reconocido antes de procesar:", text); // Verifica lo que se reconoce antes

                            text = this.processCommands(text); // Aplica los comandos correctamente

                            console.log("Texto después de procesar comandos:", text); // Verifica después de aplicar comandos

                            finalText += text + ' ';
                        }
                    }
                    this.registro.lectura += finalText;
                };

                this.recognition.onend = () => {
                    if (this.recognizing) {
                        setTimeout(() => this.recognition.start(), 500);
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
                    // En errores comunes (silencio o red), reiniciar automáticamente para mantener fluidez
                    if (event.error === 'no-speech' || event.error === 'network') {
                        try { this.recognition.stop(); } catch (e) { }
                        if (this.recognizing) {
                            setTimeout(() => {
                                try { this.recognition.start(); } catch (e) { console.warn('Fallo reintento de reconocimiento:', e); }
                            }, 500);
                        }
                    }
                };
            }

            this.recognition.start();
            this.recognizing = true;
            this.microfono = 'admin-wrap/assets/images/mic-animate.gif';

            navigator.mediaDevices.getUserMedia({ audio: true }).then(stream => {
                this.mediaRecorder = new MediaRecorder(stream, {
                    mimeType: "audio/webm;codecs=opus",
                    audioBitsPerSecond: 64000, // Reduce la calidad (valores entre 16kbps y 128kbps)
                });
                this.mediaStream = stream;
                this.audioChunks = [];

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
                $.toast({
                    heading: "Upsss!!!",
                    text: 'Error al acceder al micrófono: ' + (error && error.message ? error.message : String(error)),
                    position: "top-right",
                    loaderBg: "#ff6849",
                    icon: "danger",
                    hideAfter: 3500,
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

            // Procesar comandos de mayúsculas
            Object.keys(this.uppercaseCommands).forEach(command => {
                text = text.replace(new RegExp(`\\b${command}\\b`, "gi"), this.uppercaseCommands[command]);
            });

            // Procesar comandos de saltos de línea
            Object.keys(this.lineBreakCommands).forEach(command => {
                text = text.replace(new RegExp(`\\b${command}\\b`, "gi"), (match) => {
                    this.capitalizeNext = true; // Activa mayúscula para la siguiente palabra
                    return this.lineBreakCommands[command]();
                });
            });

            // Procesar comandos de puntuación y espacios
            Object.keys(this.punctuationCommands).forEach(command => {
                text = text.replace(new RegExp(`\\b${command}\\b`, "gi"), this.punctuationCommands[command]);
            });

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

            if (!this.audioBlob || !(this.audioBlob instanceof Blob)) {
                $.toast({
                    heading: "Upsss!!!",
                    text: 'No hay audio para enviar',
                    position: "top-right",
                    loaderBg: "#ff6849",
                    icon: "error",
                    hideAfter: 3500,
                    stack: 6,
                });
                console.error("Error: this.audioBlob no es un Blob válido.");
                return;
            }
            const formData = new FormData();
            formData.append('audio', this.audioBlob, 'audio.webm');
            formData.append('estudio_id', this.id);
            formData.append('estudio_producto_id', this.registro.estudio_producto_id);

            try {
                const res = await axios.post('/upload-audio', formData);

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
                    this.audioChunks = [];
                    this.audioBlob = null;
                    this.audioUrl = null;
                } else {
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
                try {
                    const res = await axios.post('/estudio-listarPendientesMedico', { id: this.usuarioactual.id, sede_id: this.sede_id });
                    this.destroyDataTable();

                    this.registros = res.data;

                    this.$nextTick(() => {
                        this.initDataTable();
                    });
                } catch (error) {
                    console.log(error);
                }
            }
        },
        async guardarRegistro() {
            try {
                const res = await axios.post('/estudio-leerEstudio', this.registro);

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
                        this.registros.splice(estudioEliminar, 1);
                        this.destroyDataTable();
                        this.$nextTick(() => {
                            this.initDataTable();
                        });
                    }

                    this.btnCerralModalForm();
                    this.limpiar();
                }
            } catch (error) {
                this.errores = error.response && error.response.data ? error.response.data.errors : {};
            }
        },
        async visto(id) {
            const res = await axios.post('/marcar-visto/' + id);
            this.listarMisPendientes();
        },
        async mostrarRegistro(data = {}) {

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

            const res = await axios.get('/study-listar-diagnostico-por-estudio?id=' + this.id);
            this.diagnosticosEstudio = res.data;

            const resSoportesHC = await axios.get('/listar-soportes-hc-estudio?id=' + this.id);
            this.soportesHC = resSoportesHC.data;

            this.btnCerralModalForm();
            this.errores = [];
            console.log('data: ', data)
        },
        descargaSoportte(nomArchivoOriginal, nomArchivoEncriptado) {
            const url = `/descarga-soportes-hc/${nomArchivoEncriptado}`;

            axios.get(url, { responseType: 'blob' })
                .then(response => {
                    const url = window.URL.createObjectURL(new Blob([response.data]));
                    const link = document.createElement('a');
                    link.href = url;
                    link.setAttribute('download', nomArchivoOriginal);
                    document.body.appendChild(link);
                    link.click();
                })
                .catch(error => {
                    console.error(error);
                });
        },
        verImagen(url, pat_id, study_iuid, servername) {
            const Url = `${url}?patientID=${encodeURIComponent(pat_id)}&studyUID=${encodeURIComponent(study_iuid)}&serverName=${encodeURIComponent(servername)}`;
            window.open(Url, '_blank', 'noopener,noreferrer');
        },
        establecerSede() {
            this.sede_id = sessionStorage.getItem('ST-sede');

            if (this.sede_id == 1) {
                this.sedeInterface = true;
            } else {
                this.sedeInterface = false;
            }
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

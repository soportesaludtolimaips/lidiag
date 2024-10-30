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
                                <tr v-for="(item, index) in registros " :key="index"
                                    :class="{ 'text-info': item.visto === 1 }" :id="item.id_producto_lectura">
                                    <td width="2%">
                                        <!-- {{ item.id_producto_lectura }} -->
                                        <button type="button" v-if="item.priori_id == 1"
                                            class="btn waves-effect waves-light btn-rounded btn-sm btn-danger">A</button>
                                        <button type="button" v-else-if="item.priori_id == 2"
                                            class="btn waves-effect waves-light btn-rounded btn-sm btn-warning">M</button>
                                        <button type="button" v-else-if="item.priori_id == 3"
                                            class="btn waves-effect waves-light btn-rounded btn-sm btn-info">B</button>
                                        <button type="button" v-else-if="item.priori_id == 4"
                                            class="btn waves-effect waves-light btn-rounded btn-sm">S</button>
                                    </td>
                                    <td>{{ item.id }} - {{ item.nom_sede }}</td>
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
                                            @click="actualizar = true; visto(item.id_producto_lectura);">
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

                                <input type="hidden" id="id_producto_lectura" name="id_producto_lectura"
                                    v-model="registro.id_producto_lectura" />
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
                                        <ul v-for="(item, index) in diagnosticosEstudio " :key="index">
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
                                            <a class="" @click="startSpeechRecognition"
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
                                            <div class="tab-pane  p-20" id="profile8" role="tabpanel">
                                                <template v-for="( item, index ) in audiosDeEstudio " :key="index">
                                                    <audio :src="item.audio" controls
                                                        style="height: 15px; width: 100%;"></audio>
                                                </template>
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

        if ('webkitSpeechRecognition' in window) {
            this.recognition = new webkitSpeechRecognition();
            this.recognizing = false;
            this.recognition.lang = 'es-CO';
            this.recognition.continuous = true;
            this.recognition.interimResults = false;
            this.setupCommandRecognition();
        } else {
            console.log('La API de reconocimiento de voz no es compatible con este navegador.');
        }

        this.emitter.on("sedeSeleccionada", (data) => {
            this.sede_id = data.idSede;
            $("#example23").DataTable().destroy();
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
                id_producto_lectura: 0, lectura: "", fec_estudio: "", accession_no: "", study_desc: "", observaciones: "", num_docu: "", nom_pacien: "", sexo: "", fec_naci: "", email: "",
                diagnosticosEstudio: [], urlOviyam: '', patientId: '', studyUID: '', serverName: '',
            },
            errores: {},
            tipoPrioridad: 0,
            microfono: 'admin-wrap/assets/images/mic.gif',
            recognizing: false,
            recognition: null,
            mediaRecorder: null,
            chunks: [],
            recording: false,
            recorder: null,
            audiosDeEstudio: [],
            audioActual: null,
            idRow: null,
            sede_id: null,
            mayusculaSostenida: false, // Estado para mayúscula sostenida
        };
    },
    methods: {
        setupCommandRecognition() {
            this.recognition.onresult = (event) => {
                let interimTranscript = ''; // Transcripción provisional
                let finalTranscript = ''; // Transcripción final

                for (let i = event.resultIndex; i < event.results.length; i++) {
                    if (event.results[i].isFinal) {
                        finalTranscript += event.results[i][0].transcript;
                    } else {
                        interimTranscript += event.results[i][0].transcript;
                    }
                }

                // Procesar el resultado final si existe
                if (finalTranscript) {
                    this.processCommand(finalTranscript.trim().toLowerCase());
                }
            };
        },
        processCommand(command) {
            let isCommand = false;

            // Reconocer comandos de voz para acciones y signos de puntuación
            if (command.includes('iniciar grabación')) {
                this.startRecording();
                isCommand = true;
            } else if (command.includes('detener grabación')) {
                this.stopRecording();
                isCommand = true;
            } else if (command.includes('guardar lectura')) {
                this.guardarRegistro();
                isCommand = true;
            } else if (command.includes('mayúscula sostenida')) {
                this.mayusculaSostenida = true;
                isCommand = true;
            } else if (command.includes('terminar mayúscula sostenida')) {
                this.mayusculaSostenida = false;
                isCommand = true;
            } else if (command.includes('dar espacio')) {
                this.registro.lectura += ' ';
                isCommand = true;
            } else if (command.includes('doble espacio')) {
                this.registro.lectura += '\n\n'; // Doble salto de línea para nuevo párrafo
                isCommand = true;
            } else if (command.includes('un espacio')) {
                this.registro.lectura += '\n'; // Un salto de línea
                isCommand = true;
            } else if (command.includes('punto aparte')) {
                this.registro.lectura += '.\n\n'; // Punto aparte (punto + dos saltos de línea)
                isCommand = true;
            } else if (command.includes('punto')) {
                this.registro.lectura += '.'; // Punto
                isCommand = true;
            }

            // Si no es un comando, procesamos el texto
            if (!isCommand) {
                // Aplicar mayúscula sostenida si está activada
                if (this.mayusculaSostenida) {
                    command = command.toUpperCase();
                }

                // Procesar otros signos de puntuación
                command = command
                    .replace(/\bcoma\b/g, ',')
                    .replace(/\bpunto y coma\b/g, ';')
                    .replace(/\bdos puntos\b/g, ':');

                // Añadir el texto al contenido existente sin agregar saltos de línea extra
                this.registro.lectura += command;
            }
        },
        startSpeechRecognition() {
            if (!this.recognizing) {
                this.recognition.start();
                this.recognizing = true;
                this.microfono = 'admin-wrap/assets/images/mic-animate.gif';
            } else {
                this.recognition.stop();
                this.recognizing = false;
                this.microfono = 'admin-wrap/assets/images/mic.gif';
            }
        },
        async startRecording() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                this.mediaRecorder = new MediaRecorder(stream);

                this.mediaRecorder.ondataavailable = (event) => {
                    if (event.data.size > 0) {
                        this.chunks.push(event.data);
                    }
                };

                this.mediaRecorder.onstop = () => {
                    const audioBlob = new Blob(this.chunks, { type: 'audio/wav' });
                    this.chunks = [];
                    this.saveRecording(audioBlob);
                };

                this.mediaRecorder.start();
                this.recording = true;
                console.log('Grabación iniciada');
            } catch (error) {
                console.error('Error al acceder al micrófono:', error);
            }
        },
        stopRecording() {
            if (this.mediaRecorder && this.recording) {
                this.mediaRecorder.stop();
                this.recording = false;
                console.log('Grabación detenida');
            }
        },
        async saveRecording(audioBlob) {
            const formData = new FormData();
            formData.append('audio', audioBlob, 'audio.wav');
            formData.append('estudio_id', this.id);
            formData.append('estudio_producto_id', this.registro.id_producto_lectura);

            try {
                const res = await axios.post('/upload-audio', formData);
                console.log('Audio enviado con éxito:', res.data);
                this.listarAudios();
            } catch (error) {
                console.error('Error al enviar audio:', error);
            }
        },
        async listarMisPendientes() {
            if (!this.sede_id) {
                $.toast({
                    heading: "Upsss!!!",
                    text: 'Debes establecer la sede en la cual vas a trabajar.',
                    position: "top-right",
                    loaderBg: "#ff6849",
                    icon: "warning",
                    hideAfter: 3500,
                    stack: 6,
                });
                return;
            }

            try {
                const res = await axios.post('/estudio-listarPendientesMedico', { id: this.usuarioactual.id, sede_id: this.sede_id });
                $("#example23").DataTable().destroy();

                this.registros = res.data;

                this.$nextTick(() => {
                    $("#example23").DataTable({
                        dom: "Bfrtip",
                        buttons: ["copy", "csv", "excel", "pdf", "print"],
                    });
                });
            } catch (error) {
                console.log(error);
            }
        },
        async guardarRegistro() {
            try {
                const res = await axios.post('/estudio-leerEstudio', this.registro);

                if (res.status === 200) {
                    $.toast({
                        heading: "Ok!!!",
                        text: res.data.message,
                        position: "top-right",
                        loaderBg: "#ff6849",
                        icon: "success",
                        hideAfter: 3500,
                        stack: 6,
                    });

                    const row = document.getElementById(this.idRow);
                    if (row) {
                        row.remove();
                        const estudioEliminar = this.registros.findIndex(x => x.id_producto_lectura === this.idRow);
                        this.registros.splice(estudioEliminar, 1);

                        $("#example23").DataTable().destroy();
                        this.$nextTick(() => {
                            $("#example23").DataTable({
                                dom: "Bfrtip",
                                buttons: ["copy", "csv", "excel", "pdf", "print"],
                            });
                        });
                    }

                    this.btnCerralModalForm();
                    this.limpiar();
                }
            } catch (error) {
                if (error.response && error.response.data && error.response.status === 422) {
                    this.errores = error.response.data.errors;
                } else {
                    console.error('Error al guardar registro:', error);
                }
            }
        },
        async visto(id) {
            const res = await axios.post('/marcar-visto/' + id);
            this.listarMisPendientes();
        },
        async mostrarRegistro(data = {}) {
            this.tituloModal = "Lectura de paciente: " + data.nom_pacien + "Producto: \n" + data.nom_produc;
            this.id = data.id;
            this.idRow = data.id_producto_lectura;
            this.registro.id_producto_lectura = data.id_producto_lectura;
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

            this.listarAudios();
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
        async listarAudios() {
            const res = await axios.get('/listar-audios-estudio/' + this.id);
            this.audiosDeEstudio = res.data;
        },
        verImagen(url, pat_id, study_iuid, servername) {
            let Url = url + '?patientID =' + pat_id + '&studyUID=' + study_iuid + '&serverName=' + servername
            window.open(Url, '_blank');
        },
        changeTrack(index) {
            this.audioActual = this.audiosDeEstudio[index].audio;
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
            this.registro.id_producto_lectura = "";
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
    }
};
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

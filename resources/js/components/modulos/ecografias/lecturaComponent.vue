<template>
    <div>
        <!-- ============================================================== -->
        <!-- Bread crumb and right sidebar toggle -->
        <!-- ============================================================== -->
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">Ecografias</h3>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0)">Inicio</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0)">Ecografias</a>
                    </li>
                    <li class="breadcrumb-item active">Lectura de ecografias</li>
                </ol>
            </div>
            <div class="col-md-7 align-self-center text-right d-none d-md-block">
                <i class="fa fa-life-ring model_img img-responsive text-info" data-toggle="modal"
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
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title text-themecolor">
                            <i class="fa fa-filter"></i> Filtros para busqueda de atención
                        </h4>
                        <form action="#">
                            <div class="form-body">
                                <div class="row p-t-2">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="control-label">
                                                # de atención, # de documento ó Nombres de paciente
                                            </label>
                                            <input type="text" id="bus_nom_num_docu" name="bus_nom_num_docu"
                                                v-model="busqueda.bus_nom_num_docu" class="form-control"
                                                placeholder=" Atención, Nombres de paciente o # de documento"
                                                @keyup.enter="buscarStudy()" />
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label class="control-label">Fecha Inicio</label>
                                            <input type="date" id="bus_fehc_ini" name="bus_fehc_ini"
                                                v-model="busqueda.bus_fehc_ini" class="form-control" />
                                            <!-- <span class="text-danger" v-if="errores.bus_fehc_ini">
                                                {{ erroresBusqueda.bus_fehc_ini[0] }}
                                            </span> -->
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label class="control-label">Fecha Fin</label>
                                            <input type="date" id="bus_fecha_fin" name="bus_fecha_fin"
                                                v-model="busqueda.bus_fecha_fin" class="form-control" />
                                            <!-- <span class="text-danger" v-if="errores.bus_fecha_fin">
                                                {{ erroresBusqueda.bus_fecha_fin[0] }}
                                            </span> -->
                                        </div>
                                    </div>
                                </div>

                                <div class="form-actions mt-3">
                                    <button type="button" class="btn btn-success m-r-5"
                                        @click="listarPendiestePorLectura()">
                                        <i class="fa fa-search"></i> Buscar
                                    </button>

                                    <button type="button" class="btn btn-inverse" @click="btnLimpiar()">
                                        <i class="fa fa-refresh"></i> Limpiar
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
                        <h4 class="card-title">Listado de atenciones disponibles.</h4>
                        <h6 class="card-subtitle text-info">Asigne los médicos para su respectiva lectura.
                        </h6>
                        <div class="table-responsive">
                            <table id="example23" class="table table-bordered table-striped" cellspacing="0"
                                width="100%">
                                <thead>
                                    <tr>
                                        <th with='5%'>Atención</th>
                                        <th with='5%'># Documento</th>
                                        <th with='30%'>Paciente</th>
                                        <th>Examen</th>
                                        <th>Val. Examen</th>
                                        <th>Administradora</th>
                                        <th>Contrato</th>
                                        <th>Plan</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item, index) in registros" :key="index">
                                        <td>{{ item.atencion_id }}</td>
                                        <td>{{ item.paciente.num_docu }}</td>
                                        <td>{{ item.paciente.nombres }}</td>
                                        <td>{{ item.nom_produc }}</td>
                                        <td>{{ item.ValProduc }}</td>
                                        <td>{{ item.admin_salud.nom_admin_salud }}</td>
                                        <td>{{ item.contrato }}</td>
                                        <td>{{ item.plan }}</td>
                                        <td class="text-nowrap">
                                            <button type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-primary btn-sm m-r-5"
                                                @click="actualizar = true;
                                                mostrarRegistro(item, index);" title="ARealizar lectura">
                                                <i class=" fa fa-check"></i>
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
        <!-- End Page Content -->
        <!-- ============================================================== -->

        <!-- ============================================================== -->
        <!-- Right sidebar -->
        <!-- ============================================================== -->
        <!-- .right-sidebar -->
        <div class="right-sidebar" style="overflow-x: hidden; overflow-y: scroll; overflow-x: hidden; height: 100%;">
            <div class="slimscrollright">
                <div class="rpanel-title">
                    {{ tituloAtencion }}
                    <br />
                    {{ tituloModal }}
                    <br />
                    {{ subTituloModal }}
                    <span @click="btnCerralModalForm()">
                        <i class="ti-close right-side-toggle" id="btnCerralModalForm"></i>
                    </span>
                </div>
                <div class="r-panel-body">
                    <!-- ============================================================== -->
                    <!-- Aqui va el contenido de los formularios -->
                    <!-- ============================================================== -->
                    <form action="#">
                        <div class="form-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="row">
                                        <div class="col-md-10">
                                            <h5 class="card-title text-info">
                                                <i class="fa fa-edit"></i> Lectura
                                            </h5>
                                        </div>
                                        <div class="col-md-2">

                                            <a class="" @click="toggleSpeechRecognition"
                                                style="width: 30px; height: 30px;">
                                                <img title="Grabadora" :src="microfono">
                                            </a>

                                            <i class="fa fa-file-pdf-o text-danger fa-2x model_img img-responsive"
                                                @click="generarReporte" data-toggle="modal"
                                                data-target=".bs-example-modal-lg-impri-repor" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Tipos de plantillas</label>
                                    <Select2 :options="tiposPlantillasOptions" width="100%" :allow-clear="true"
                                        :label-fields="['nom_tipo']"
                                        :dropdown-parent-selector="'#tipos-plantillas-parent'"
                                        @input="onTipoPlantillaSelect" />

                                    <span class="text-danger" v-if="errores.medico_id">
                                        {{ errores.medico_id[0] }}
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label>Plantilla</label>
                                    <Select2 width="100%" :allow-clear="true" :options="plantillasOptions"
                                        :label-fields="['nom_plantilla']"
                                        :dropdown-parent-selector="'#plantillas-parent'" @input="onPlantillaSelect" />

                                    <span class="text-danger" v-if="errores.medico_id">
                                        {{ errores.medico_id[0] }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-md-8">
                                <h5 class="card-title text-info">
                                    <i class="fa fa-pencil"></i> Lectura
                                </h5>
                                <div class="form-group">
                                    <textarea id="plantilla" name="plantilla" v-model="registro.lectura"
                                        class="form-control" placeholder="Ingrese aquí la lectura del procedimiento"
                                        rows="20"></textarea>
                                    <span class="text-danger" v-if="errores.plantilla">{{
                                        errores.plantilla[0]
                                    }}</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <h5 class="card-title text-info">
                                        <i class="fa fa-info"></i> Opinión
                                    </h5>
                                    <textarea id="opinion" name="opinion" v-model="registro.opinion"
                                        class="form-control" placeholder="Ingrese aquí la opinión del procedimiento"
                                        rows="20"></textarea>
                                    <span class="text-danger" v-if="errores.opinion">{{
                                        errores.opinion[0]
                                    }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="form-actions mt-3">
                            <button type="button" class="btn btn-success m-r-5" @click="guardarRegistro()">
                                <i class="fa fa-check"></i> Guardar
                            </button>

                            <button type="button" class="btn btn-inverse" @click="btnCerralModalForm()">Cancel</button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
        <!-- ============================================================== -->
        <!-- End Right sidebar -->
        <!-- ============================================================== -->
    </div>

    <!-- sample modal content -->
    <div class="modal fade bs-example-modal-lg-impri-repor" tabindex="-1" role="dialog"
        aria-labelledby="myLargeModalLabel-impri-repor" aria-hidden="true" style="display: none;">
        <div class="modal-dialog modal-xl" style="max-width: 70%;">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="myLargeModalLabel">Reporte de lectura</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                </div>
                <div class="modal-body">
                    <iframe width="100%" height="600px" id="ifrReporteEcografia" frameborder="0"
                        style="border:0; outline:none;" scrolling="no"></iframe>
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
</template>
<script>
export default {
    props: ['usuarioactual'],
    mounted() {
        this.establecerSede();
        this.listarTiposPlantillas();

        this.emitter.on("sedeSeleccionada", (data) => {
            this.registro.sede_id = data.idSede;
            this.establecerSede();
        });

        this.emitter.on("medicoSeleccionado", (data) => {
            console.log(data)
            this.registro.medico_id = data.idMedico;
            this.establecerMedico();
            this.listarPendiestePorLectura();
        });
    },
    data() {
        return {
            tituloModal: 'Nueva adminsión',
            subTituloModal: '',
            tituloAtencion: '',
            busqueda: { sede_id: this.sedeActual, medico_id: this.medicoActual, bus_nom_num_docu: "", bus_fehc_ini: "", bus_fecha_fin: "" },
            registros: [],
            registro: {
                ecografia_id: "", producto_id: "", sede_id: this.sedeActual, medico_id: this.medicoActual, quien_registro_id: this.usuarioactual.id, atencion_id: null,
                lectura: null, opinion: null, tipo_estudio: null, nom_pacien: null
            },
            errores: {},
            erroresBusqueda: {},
            medicos: [],
            accion: null,
            sedeActual: sessionStorage.getItem('ST-sede'),
            medicoActual: sessionStorage.getItem('ST-medico'),
            productosAgenda: [],
            tiposPlantillas: [],
            plantillas: [],
            sedeInterface: false,

            microfono: 'admin-wrap/assets/images/mic.gif',
        }
    },
    computed: {
        tiposPlantillasOptions() {
            // Opciones para el Select2 local de medicos.
            return (this.tiposPlantillas || []).map(p => ({ id: String(p.id), nom_tipo: p.nom_tipo }));
        },
        plantillasOptions() {
            // Opciones para el Select2 local de medicos.
            return (this.plantillas || []).map(p => ({ id: String(p.id), nom_plantilla: p.nom_plantilla, plantilla: p.plantilla, opinion: p.opinion }));
        },
    },
    methods: {
        async listarPendiestePorLectura() {

            const res = await axios.post('/ecografia-listarPendientesPorLectura/' + this.medicoActual);

            if (res.status == 200) {
                $("#example23").DataTable().destroy();

                this.registros = res.data.ecografias;

                this.$nextTick(() => {
                    $("#example23").DataTable({
                        dom: "Bfrtip",
                        buttons: ["copy", "csv", "excel", "pdf", "print"],
                    });
                });
            }
        },
        async generarLectura() {
            try {
                // Enviar solicitud para guardar la ecografía
                const res = await axios.put('/ecografia-lectura/' + this.registro.ecografia_id, { accion: this.accion, registro: this.registro });

                if (res.status === 201) {

                    // Mostrar notificación de éxito
                    $.toast({
                        heading: '¡Éxito!',
                        text: 'Agenda guardada correctamente.',
                        position: 'top-right',
                        loaderBg: '#ff6849',
                        icon: 'success',
                        hideAfter: 3500,
                        stack: 6,
                    });

                    // Actualizar la lista de datos y limpiar el formulario
                    this.limpiarFormulario();
                    this.btnCerralModalForm();

                } else {
                    $.toast({
                        heading: "Upss!!!",
                        text: res.data.message,
                        position: "top-right",
                        loaderBg: "#ff6849",
                        icon: "danger",
                        hideAfter: 3500,
                        stack: 6,
                    });
                }
            } catch (error) {
                // Manejar errores y mostrar notificación
                console.error('Error al guardar la agenda:', error);

                $.toast({
                    heading: 'Error',
                    text: error.response?.data?.message || 'Ocurrió un error al guardar la agenda.',
                    position: 'top-right',
                    loaderBg: '#ff6849',
                    icon: 'error',
                    hideAfter: 3500,
                    stack: 6,
                });

                // Mostrar errores específicos en el formulario
                if (error.response?.data?.errors) {
                    this.errores = error.response.data.errors;
                }
            }
        },
        async guardarRegistro() {
            this.accion = 'TERMINAR_LECTURA';
            this.generarLectura();
            this.listarPendiestePorLectura();
            this.accion = null;
        },
        async generarReporte() {
            this.accion = 'IMPRIMIR_LECTURA';
            this.generarLectura();
            this.accion = null;

            const iframe = document.getElementById('ifrReporteEcografia');
            iframe.src = '';

            const res = await axios.post('/ecografia-generar-reporte/' + this.registro.ecografia_id, this.registro);

            const pathReporte = res.data.url;

            // Mostrar el reporte en el iframe
            iframe.src = pathReporte;
        },
        establecerSede() {
            this.registro.sede_id = sessionStorage.getItem('ST-sede');
            this.busqueda.sede_id = sessionStorage.getItem('ST-sede');
            this.sedeActual = sessionStorage.getItem('ST-sede');

            if (this.registro.sede_id == 1) {
                this.sedeInterface = true;
            } else {
                this.sedeInterface = false;
            }
            this.registros = [];
        },
        establecerMedico() {
            this.registro.sede_id = sessionStorage.getItem('ST-sede');
            this.busqueda.sede_id = sessionStorage.getItem('ST-sede');
            this.medicoActual = sessionStorage.getItem('ST-medico');

            if (this.registro.sede_id == 1) {
                this.sedeInterface = true;
            } else {
                this.sedeInterface = false;
            }
            this.registros = [];
        },
        async listarTiposPlantillas() {
            try {
                const res = await axios.get("/config-tipos-plantillas/listar/activos");
                this.tiposPlantillas = res.data;
            } catch (error) {
                console.log(error);
            }
        },
        async onTipoPlantillaSelect(value) {

            let selectedId = null;
            if (Array.isArray(value) && value.length > 0) {
                const v = value[0];
                selectedId = String(typeof v === 'object' && v && v.id != null ? v.id : v);
            } else if (value && typeof value === 'object' && value.id != null) {
                selectedId = String(value.id);
            } else if (value != null) {
                selectedId = String(value);
            }
            //this.registro.medico_id = selectedId || "";

            // Traigos las plantillas dependiendo del tipo de plantilla seleccionado
            try {
                const res = await axios.get(`/config-plantillas/listar/activos/${selectedId}`);

                // Cargo el compo de las plantillas
                this.plantillas = res.data;
                this.plantillasOptions;
                this.registro.tipo_estudio = value[0].nom_tipo;
            } catch (error) {
                console.log(error);
            }
        },
        onPlantillaSelect(value) {
            this.registro.lectura = value[0].plantilla || null;
            this.registro.opinion = value[0].opinion || null;
        },
        btnCerralModalForm() {
            $(".right-sidebar").slideDown(50);
            $(".right-sidebar").toggleClass("shw-rside");
        },
        mostrarRegistro(data = {}) {
            console.log('data', data)
            this.tituloAtencion = `Atención N°: ${data.atencion_id}`;
            this.tituloModal = `Paciente: ${data.paciente.num_docu} - Nombres: ${data.paciente.nombres} ${data.paciente.seg_nom} ${data.paciente.pri_ape} ${data.paciente.seg_ape} `;
            this.subTituloModal = `Examen: ${data.nom_produc} - Valor: ${data.ValProduc}`;

            this.registro.ecografia_id = data.id;
            this.registro.atencion_id = data.atencion_id;
            this.registro.paciente_id = data.paciente_id;
            this.registro.num_docu = data.paciente.num_docu;
            this.registro.nom_pacien = data.paciente.nombres;
            this.registro.seg_nom = data.paciente.seg_nom;
            this.registro.pri_ape = data.paciente.pri_ape;
            this.registro.seg_ape = data.paciente.seg_ape;
            this.registro.sexo = data.paciente.sexo;
            this.registro.fec_naci = data.paciente.fec_naci;
            this.registro.direccion = data.paciente.direccion;
            this.registro.tel = data.paciente.tel;
            this.registro.cel = data.paciente.cel;
            this.registro.atencion_id = data.atencion_id;
            this.registro.contrato = data.contrato;
            this.registro.plan = data.plan;
            this.registro.admin_salud = data.admin_salud.nom_admin_salud;

            this.registro.producto_id = data.ecografia_producto_id;

            this.btnCerralModalForm();
        },
        limpiarFormulario() {
            this.tituloModal = '';

            this.registro.paciente_id = '';
            this.registro.num_docu = '';
            this.registro.nombres = '';
            this.registro.seg_nom = '';
            this.registro.pri_ape = '';
            this.registro.seg_ape = '';
            this.registro.sexo = '';
            this.registro.fec_naci = '';
            this.registro.direccion = '';
            this.registro.tel = '';
            this.registro.cel = '';
            this.registro.atencion_id = '';
            this.registro.contrato = '';
            this.registro.plan = '';
            this.registro.admin_salud = '';
            this.registro.quien_ordeno = '';
            this.registro.lectura = '';
            this.registro.opinion = '';
            this.errores = {};
        },
        btnCerralModalForm() {
            $(".right-sidebar").slideDown(50);
            $(".right-sidebar").toggleClass("shw-rside");
        },
    }
}
</script>
<style scoped>
.right-sidebar {
    position: fixed;
    right: -96%;
    width: 96%;
    display: none;
    z-index: 96;
    background: #ffffff;
    top: 0px;
    padding-bottom: 20px;
    height: 96%;
    -webkit-box-shadow: 5px 1px 40px rgba(0, 0, 0, 0.1);
    box-shadow: 5px 1px 40px rgba(0, 0, 0, 0.1);
    -webkit-transition: all 0.3s ease;
    -o-transition: all 0.3s ease;
    transition: all 0.3s ease;
}

.shw-rside {
    right: 0px;
    width: 96%;
    display: block;
}

.audio {
    width: 50px;
    height: 5px;
}
</style>
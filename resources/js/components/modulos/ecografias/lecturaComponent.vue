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
                        <button type="button" class="btn btn-link p-0 breadcrumb-btn">
                            Inicio
                        </button>
                    </li>
                    <li class="breadcrumb-item">
                        <button type="button" class="btn btn-link p-0 breadcrumb-btn">
                            Ecografias
                        </button>
                    </li>
                    <li class="breadcrumb-item active">Lectura de ecografias</li>
                </ol>
            </div>
            <div class="col-md-7 align-self-center text-right d-none d-md-block">
                <i
                    class="fa fa-life-ring model_img img-responsive text-info"
                    data-toggle="modal"
                    data-target=".bs-example-modal-lg"
                ></i>
            </div>
            <!-- sample modal content -->
            <div
                class="modal fade bs-example-modal-lg"
                tabindex="-1"
                role="dialog"
                aria-labelledby="myLargeModalLabel"
                aria-hidden="true"
                style="display: none"
            >
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title" id="myLargeModalLabel">Comando de voz</h4>
                            <button
                                type="button"
                                class="close"
                                data-dismiss="modal"
                                aria-hidden="true"
                            >
                                ×
                            </button>
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
                            <button
                                type="button"
                                class="btn btn-danger waves-effect text-left"
                                data-dismiss="modal"
                            >
                                Close
                            </button>
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
                            <i class="fa fa-filter"></i> Filtros para búsqueda de atención
                        </h4>
                        <form action="#">
                            <div class="form-body">
                                <div class="row p-t-2">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="control-label">
                                                # de atención, # de documento ó Nombres de paciente
                                            </label>
                                            <input
                                                type="text"
                                                id="bus_nom_num_docu"
                                                name="bus_nom_num_docu"
                                                v-model="busqueda.bus_nom_num_docu"
                                                class="form-control"
                                                placeholder=" Atención, Nombres de paciente o # de documento"
                                                @keyup.enter="buscarStudy()"
                                            />
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label class="control-label">Fecha Inicio</label>
                                            <input
                                                type="date"
                                                id="bus_fehc_ini"
                                                name="bus_fehc_ini"
                                                v-model="busqueda.bus_fehc_ini"
                                                class="form-control"
                                            />
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label class="control-label">Fecha Fin</label>
                                            <input
                                                type="date"
                                                id="bus_fecha_fin"
                                                name="bus_fecha_fin"
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
                                        @click="listarPendiestePorLectura()"
                                        :disabled="isBuscandoPendientes"
                                    >
                                        <i
                                            class="fa"
                                            :class="
                                                isBuscandoPendientes
                                                    ? 'fa-spinner fa-spin'
                                                    : 'fa-search'
                                            "
                                        ></i>
                                        {{ isBuscandoPendientes ? "Buscando..." : "Buscar" }}
                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn-inverse"
                                        @click="btnLimpiar()"
                                    >
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
                        <h6 class="card-subtitle text-info">
                            Asigne los médicos para su respectiva lectura.
                        </h6>
                        <div class="table-responsive">
                            <table
                                id="example23"
                                ref="dt"
                                class="table table-bordered table-striped"
                                cellspacing="0"
                                width="100%"
                            >
                                <thead>
                                    <tr>
                                        <th with="5%">Atención</th>
                                        <th with="5%"># Documento</th>
                                        <th with="30%">Paciente</th>
                                        <th>Examen</th>
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
                                        <td>
                                            <div>{{ item.nom_produc }}</div>
                                            <small
                                                v-if="
                                                    item.consentimiento_estado ===
                                                    'PENDIENTE_PACIENTE'
                                                "
                                                class="text-danger"
                                            >
                                                Pendiente firma paciente/acompañante
                                            </small>
                                            <small
                                                v-else-if="
                                                    item.consentimiento_estado ===
                                                    'PENDIENTE_MEDICO'
                                                "
                                                class="text-warning"
                                            >
                                                Pendiente firma médico
                                            </small>
                                            <small
                                                v-else-if="
                                                    item.consentimiento_estado === 'COMPLETO'
                                                "
                                                class="text-success"
                                            >
                                                Consentimiento completo
                                            </small>
                                        </td>
                                        <td>{{ item.admin_salud.nom_admin_salud }}</td>
                                        <td>{{ item.contrato }}</td>
                                        <td>{{ item.plan }}</td>
                                        <td class="text-nowrap">
                                            <button
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-primary btn-sm m-r-5"
                                                :disabled="
                                                    bloqueoLectura ||
                                                    (item.tiene_plantilla_consentimiento &&
                                                        (!item.consentimiento_estado ||
                                                            item.consentimiento_estado ===
                                                                'PENDIENTE_PACIENTE'))
                                                "
                                                @click="
                                                    actualizar = true;
                                                    mostrarRegistro(item, index);
                                                "
                                                title="Realizar lectura"
                                                aria-label="Realizar lectura"
                                            >
                                                <i class="fa fa-check"></i>
                                            </button>

                                            <button
                                                v-if="
                                                    can(
                                                        'ecografias.consentimientos.firmar_medico'
                                                    ) &&
                                                    item.consentimiento_id &&
                                                    item.consentimiento_estado ===
                                                        'PENDIENTE_MEDICO'
                                                "
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-success btn-sm"
                                                :disabled="
                                                    isFirmandoConsentimiento[item.consentimiento_id]
                                                "
                                                @click="firmarConsentimientoMedico(item)"
                                                title="Firmar consentimiento"
                                                aria-label="Firmar consentimiento"
                                            >
                                                <i
                                                    class="fa"
                                                    :class="
                                                        isFirmandoConsentimiento[
                                                            item.consentimiento_id
                                                        ]
                                                            ? 'fa-spinner fa-spin'
                                                            : 'fa-pencil'
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
        <!-- End Page Content -->
        <!-- ============================================================== -->

        <!-- ============================================================== -->
        <!-- Right sidebar -->
        <!-- ============================================================== -->
        <!-- .right-sidebar -->
        <div class="right-sidebar sidebar-scroll">
            <div class="slimscrollright">
                <div class="rpanel-title">
                    {{ tituloAtencion }}
                    <br />
                    {{ tituloModal }}
                    <br />
                    {{ subTituloModal }}
                    <span @click="btnCerralModalForm()">
                        <i
                            class="ti-close right-side-toggle"
                            id="btnCerralModalForm"
                            @click="btnCerralModalForm()"
                        ></i>
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
                                        <div class="col-md-8">
                                            <h5 class="card-title text-info">
                                                <i class="fa fa-edit"></i> Lectura
                                            </h5>
                                        </div>
                                        <div class="ml-auto">
                                            <ul class="list-inline text-center">
                                                <li>
                                                    <a
                                                        class="mic-link"
                                                        @click="toggleSpeechRecognition"
                                                        role="button"
                                                        aria-label="Grabadora"
                                                    >
                                                        <img title="Grabadora" :src="microfono" />
                                                    </a>
                                                </li>
                                                <li>
                                                    <i
                                                        class="fa fa-file-pdf-o text-danger fa-2x model_img img-responsive"
                                                        @click="generarReporte"
                                                        data-toggle="modal"
                                                        data-target=".bs-example-modal-lg-impri-repor"
                                                    />
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <hr />
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Tipos de plantillas</label>
                                    <Select2
                                        :options="tiposPlantillasOptions"
                                        width="100%"
                                        :allow-clear="true"
                                        :label-fields="['nom_tipo']"
                                        @input="onTipoPlantillaSelect"
                                    />
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label>Plantilla</label>
                                    <Select2
                                        width="100%"
                                        :allow-clear="true"
                                        :options="plantillasOptions"
                                        :label-fields="['nom_plantilla']"
                                        @input="onPlantillaSelect"
                                    />
                                </div>
                            </div>
                        </div>
                        <hr />
                        <div class="row">
                            <div class="col-md-8">
                                <h5 class="card-title text-info">
                                    <i class="fa fa-pencil"></i> Lectura
                                </h5>
                                <div class="form-group">
                                    <textarea
                                        id="plantilla"
                                        name="plantilla"
                                        v-model="registro.lectura"
                                        class="form-control"
                                        placeholder="Ingrese aquí la lectura del procedimiento"
                                        rows="17"
                                    ></textarea>
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
                                    <textarea
                                        id="opinion"
                                        name="opinion"
                                        v-model="registro.opinion"
                                        class="form-control"
                                        placeholder="Ingrese aquí la opinión del procedimiento"
                                        rows="17"
                                    ></textarea>
                                    <span class="text-danger" v-if="errores.opinion">{{
                                        errores.opinion[0]
                                    }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="form-actions mt-3">
                            <button
                                type="button"
                                class="btn btn-success m-r-5"
                                @click="guardarRegistro()"
                                :disabled="
                                    isGuardandoLectura ||
                                    (sedeInterface && registro.estado_envio_sahi === 'ENVIANDO')
                                "
                            >
                                <i class="fa fa-check"></i> Guardar
                            </button>

                            <button
                                v-if="sedeInterface && registro.ecografia_producto_id"
                                type="button"
                                class="btn btn-info m-r-5"
                                @click="consultarEstadoSahi(false)"
                                :disabled="isConsultando"
                            >
                                <i
                                    class="fa"
                                    :class="isConsultando ? 'fa-spinner fa-spin' : 'fa-refresh'"
                                ></i>
                                {{ isConsultando ? "Consultando..." : "Actualizar SAHI" }}
                            </button>

                            <button
                                v-if="sedeInterface && registro.estado_envio_sahi === 'ERROR'"
                                type="button"
                                class="btn btn-warning m-r-5"
                                @click="enviarASahiEcografiaProducto()"
                                :disabled="isEnviando || registro.estado_envio_sahi === 'ENVIANDO'"
                            >
                                <i class="fa fa-refresh"></i> Reintentar SAHI
                            </button>

                            <span
                                v-if="sedeInterface && registro.estado_envio_sahi"
                                class="label m-r-5"
                                :class="{
                                    'label-success': registro.estado_envio_sahi === 'ENVIADO',
                                    'label-warning':
                                        registro.estado_envio_sahi === 'ENVIANDO' ||
                                        registro.estado_envio_sahi === 'PENDIENTE',
                                    'label-danger': registro.estado_envio_sahi === 'ERROR',
                                }"
                            >
                                SAHI: {{ registro.estado_envio_sahi }}
                            </span>
                            <small
                                v-if="
                                    sedeInterface &&
                                    registro.estado_envio_sahi === 'ERROR' &&
                                    registro.error_envio_sahi
                                "
                                class="text-danger d-block"
                            >
                                {{ registro.error_envio_sahi }}
                            </small>

                            <button
                                type="button"
                                class="btn btn-inverse"
                                @click="btnCerralModalForm()"
                            >
                                Cancel
                            </button>
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
    <div
        class="modal fade bs-example-modal-lg-impri-repor"
        tabindex="-1"
        role="dialog"
        aria-labelledby="myLargeModalLabel-impri-repor"
        aria-hidden="true"
        style="display: none"
    >
        <div class="modal-dialog modal-xl" style="max-width: 70%">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="myLargeModalLabel-impri-repor">
                        Reporte de lectura
                    </h4>
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
                        ×
                    </button>
                </div>
                <div class="modal-body">
                    <iframe
                        width="100%"
                        height="600px"
                        id="ifrReporteEcografia"
                        frameborder="0"
                        style="border: 0; outline: none"
                        scrolling="no"
                    ></iframe>
                </div>
                <div class="modal-footer">
                    <button
                        type="button"
                        class="btn btn-danger waves-effect text-left"
                        data-dismiss="modal"
                    >
                        Close
                    </button>
                </div>
            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div>
    <!-- /.modal -->

    <div
        id="modal-pendientes-consentimientos"
        class="modal fade"
        tabindex="-1"
        role="dialog"
        aria-labelledby="modal-pendientes-consentimientos-title"
        aria-hidden="true"
        style="display: none"
    >
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="modal-pendientes-consentimientos-title">
                        Consentimientos pendientes por firmar (día anterior)
                    </h4>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        Debe firmar los consentimientos pendientes del día anterior para poder
                        realizar lecturas.
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Paciente</th>
                                    <th>Documento</th>
                                    <th>Examen</th>
                                    <th>Firma paciente</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="row in pendientesDiaAnterior"
                                    :key="row.consentimiento_id"
                                >
                                    <td>{{ row.paciente?.nombres || "—" }}</td>
                                    <td>{{ row.paciente?.num_docu || "—" }}</td>
                                    <td>{{ row.nom_produc }}</td>
                                    <td>{{ row.consentimiento_firma_paciente_at || "—" }}</td>
                                    <td class="text-nowrap">
                                        <button
                                            type="button"
                                            class="btn btn-success btn-sm"
                                            :disabled="
                                                !row.consentimiento_id ||
                                                isFirmandoConsentimiento[row.consentimiento_id]
                                            "
                                            @click="firmarConsentimientoMedico(row)"
                                        >
                                            <i
                                                class="fa"
                                                :class="
                                                    isFirmandoConsentimiento[row.consentimiento_id]
                                                        ? 'fa-spinner fa-spin'
                                                        : 'fa-pencil'
                                                "
                                            ></i>
                                            Firmar
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="pendientesDiaAnterior.length === 0">
                                    <td colspan="5" class="text-center text-muted">
                                        Sin pendientes.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button
                        type="button"
                        class="btn btn-default waves-effect"
                        data-dismiss="modal"
                        :disabled="pendientesDiaAnterior.length > 0"
                    >
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import usePermissions from "@/helpers/permissions.js";
import { handleAxiosErrorToast } from "@/helpers/alerts";
import { createVoiceSession } from "@/helpers/voice/voiceSession";
import medicalReplacements from "@/data/medical_replacements.json";
import medicalTermsRaw from "@/data/terminos_medicos.txt?raw";
import { useSede } from "@/composables/useSede";
import { useSahiInterface } from "@/composables/useSahiInterface";

const MEDICAL_REPLACEMENTS_FROM_JSON = Array.isArray(medicalReplacements)
    ? medicalReplacements
    : [];
const MEDICAL_TERMS_FROM_TXT = String(medicalTermsRaw || "")
    .split(/\r?\n/)
    .map((l) => String(l).trim())
    .filter((l) => l && !l.startsWith("#"));

export default {
    props: ["usuarioactual", "permisos"],
    setup(props) {
        const { can } = usePermissions(props.permisos);
        const { sedeInterface, establecerSede } = useSede();
        const {
            isEnviando,
            isConsultando,
            enviarEcografiaProducto,
            consultarEstado,
            startPolling,
            stopPolling,
        } = useSahiInterface();
        return {
            can,
            sedeInterface,
            establecerSede,
            isEnviando,
            isConsultando,
            enviarEcografiaProducto,
            consultarEstado,
            startPolling,
            stopPolling,
        };
    },
    mounted() {
        this.establecerSede();
        this.listarTiposPlantillas();
        this.establecerMedico();
        this.rebuildMedicalVocabulary();

        this.emitter.on("sedeSeleccionada", (data) => {
            this.registro.sede_id = data.idSede;
            this.establecerSede();
        });

        this.emitter.on("medicoSeleccionado", (data) => {
            this.registro.medico_id = data.idMedico;
            this.establecerMedico();
            this.listarPendiestePorLectura();
        });

        this.url = window.location.pathname;
        this.establecerMedico();
    },
    beforeUnmount() {
        this.stopSahiPolling();
        try {
            this.stopVoice();
        } catch (e) {
            /* noop */
        }
    },
    beforeDestroy() {
        // compat Vue 2/3 (según build)
        this.stopSahiPolling();
        try {
            this.stopVoice();
        } catch (e) {
            /* noop */
        }
    },
    data() {
        return {
            tituloModal: "Nueva admisión",
            subTituloModal: "",
            tituloAtencion: "",
            busqueda: {
                sede_id: this.sedeActual,
                medico_id: "",
                bus_nom_num_docu: "",
                bus_fehc_ini: "",
                bus_fecha_fin: "",
            },
            registros: [],
            registro: {
                ecografia_id: "",
                ecografia_producto_id: "",
                producto_id: "",
                sede_id: this.sedeActual,
                medico_id: "",
                quien_registro_id: this.usuarioactual.id,
                atencion_id: null,
                lectura: null,
                opinion: null,
                tipo_estudio: null,
                nom_pacien: null,
                estado_envio_sahi: null,
                fechor_envio_sahi: null,
                error_envio_sahi: null,
                sahi_esquema_149: null,
                sahi_esquema_150: null,
            },
            errores: {},
            erroresBusqueda: {},
            medicos: [],
            accion: null,
            productosAgenda: [],
            tiposPlantillas: [],
            plantillas: [],
            url: "",

            microfono: "admin-wrap/assets/images/mic.gif",
            voiceSession: null,
            voiceState: "inactive", // inactive | active | paused
            uppercase: false,
            capitalizeNext: false,
            medicalReplCompiled: null,
            medicalVocabularyMap: {}, // normalized -> canonical
            medicalVocabularyKeys: [],
            medicalVocabularyIndex: {}, // firstLetter -> length -> array<normalizedKey>

            isGuardandoLectura: false,
            isBuscandoPendientes: false,

            // Consentimientos
            bloqueoLectura: false,
            pendientesDiaAnterior: [],
            isFirmandoConsentimiento: {},
        };
    },
    computed: {
        tiposPlantillasOptions() {
            // Opciones para el Select2 local de medicos.
            return (this.tiposPlantillas || []).map((p) => ({
                id: String(p.id),
                nom_tipo: p.nom_tipo,
            }));
        },
        plantillasOptions() {
            // Opciones para el Select2 local de medicos.
            return (this.plantillas || []).map((p) => ({
                id: String(p.id),
                nom_plantilla: p.nom_plantilla,
                plantilla: p.plantilla,
                opinion: p.opinion,
            }));
        },
    },
    methods: {
        buscarStudy() {
            // Soporte para Enter en el input de búsqueda
            return this.listarPendiestePorLectura();
        },
        btnLimpiar() {
            // Limpia criterios de búsqueda (mantiene sede/medico del contexto)
            this.busqueda.bus_nom_num_docu = "";
            this.busqueda.bus_fehc_ini = "";
            this.busqueda.bus_fecha_fin = "";
            this.erroresBusqueda = {};

            this.safeDestroyDataTable(this.$refs.dt || "#example23");
            this.registros = [];
            this.pendientesDiaAnterior = [];
            this.bloqueoLectura = false;
        },
        setMapValue(mapName, key, value) {
            if (!key) return;
            if (typeof this.$set === "function") {
                this.$set(this[mapName], key, value);
            } else {
                this[mapName][key] = value;
            }
        },
        getMedicalReplacementsCompiled() {
            if (this.medicalReplCompiled) return this.medicalReplCompiled;
            const compiled = [];
            MEDICAL_REPLACEMENTS_FROM_JSON.forEach((rule) => {
                if (!Array.isArray(rule) || rule.length < 3) return;
                const [regexString, flags, replacement] = rule;
                try {
                    compiled.push([
                        new RegExp(String(regexString), String(flags || "")),
                        String(replacement),
                    ]);
                } catch (e) {
                    // ignore regla inválida
                }
            });
            this.medicalReplCompiled = compiled;
            return compiled;
        },
        applyMedicalReplacements(text) {
            let out = String(text ?? "");
            const compiled = this.getMedicalReplacementsCompiled();
            compiled.forEach(([re, rep]) => {
                out = out.replace(re, rep);
            });
            return out;
        },
        stripDiacritics(text) {
            try {
                return String(text)
                    .normalize("NFD")
                    .replace(/[\u0300-\u036f]/g, "");
            } catch (e) {
                return String(text);
            }
        },
        escapeRegExp(text) {
            return String(text).replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
        },
        normalizeWord(text) {
            return this.stripDiacritics(String(text))
                .toLowerCase()
                .replace(/[^a-z0-9ñ]+/gi, "");
        },
        levenshteinMax(a, b, maxDist = 2) {
            if (a === b) return 0;
            if (!a || !b) return Math.max(a ? a.length : 0, b ? b.length : 0);
            const la = a.length;
            const lb = b.length;
            if (Math.abs(la - lb) > maxDist) return maxDist + 1;

            let prev = new Array(lb + 1);
            let curr = new Array(lb + 1);
            for (let j = 0; j <= lb; j++) prev[j] = j;

            for (let i = 1; i <= la; i++) {
                curr[0] = i;
                let minInRow = curr[0];
                const ca = a.charCodeAt(i - 1);
                for (let j = 1; j <= lb; j++) {
                    const cost = ca === b.charCodeAt(j - 1) ? 0 : 1;
                    const v = Math.min(prev[j] + 1, curr[j - 1] + 1, prev[j - 1] + cost);
                    curr[j] = v;
                    if (v < minInRow) minInRow = v;
                }
                if (minInRow > maxDist) return maxDist + 1;
                const tmp = prev;
                prev = curr;
                curr = tmp;
            }
            return prev[lb];
        },
        rebuildMedicalVocabulary() {
            const map = {};
            const addPhrase = (phrase) => {
                if (!phrase) return;
                const s = String(phrase).trim();
                if (!s) return;

                const nFull = this.normalizeWord(s);
                if (nFull) map[nFull] = s;

                const tokens = s.split(/[\s/,-]+/g).filter(Boolean);
                tokens.forEach((t) => {
                    const nt = this.normalizeWord(t);
                    if (nt && nt.length >= 4 && !map[nt]) map[nt] = t;
                });
            };

            // Fuente principal: archivo grande de términos
            MEDICAL_TERMS_FROM_TXT.forEach(addPhrase);

            this.medicalVocabularyMap = map;
            const keys = Object.keys(map);
            this.medicalVocabularyKeys = keys;

            const index = {};
            keys.forEach((k) => {
                if (!k) return;
                const first = k[0];
                const len = k.length;
                if (!index[first]) index[first] = {};
                if (!index[first][len]) index[first][len] = [];
                index[first][len].push(k);
            });
            this.medicalVocabularyIndex = index;
        },
        applyMedicalCorrections(text) {
            // 1) Normalizaciones (regex)
            let out = this.applyMedicalReplacements(text);

            // 2) Corrección por vocabulario (exact + fuzzy indexado)
            const vocabMap = this.medicalVocabularyMap || {};
            const vocabIndex = this.medicalVocabularyIndex || {};
            if (!vocabMap || !Object.keys(vocabMap).length) return out;

            out = String(out).replace(/[\p{L}\p{N}]+/gu, (token) => {
                if (/^\d+$/u.test(token)) return token;
                if (token.length < 4) return token;
                const n = this.normalizeWord(token);
                if (!n) return token;

                if (vocabMap[n]) return String(vocabMap[n]);

                const first = n[0];
                const bucketByLen = vocabIndex[first] || null;
                if (!bucketByLen) return token;

                let best = null;
                let bestDist = 3;
                for (let L = n.length - 2; L <= n.length + 2; L++) {
                    const arr = bucketByLen[L];
                    if (!arr || !arr.length) continue;
                    for (let i = 0; i < arr.length; i++) {
                        const k = arr[i];
                        const d = this.levenshteinMax(n, k, 2);
                        if (d < bestDist) {
                            bestDist = d;
                            best = k;
                            if (bestDist === 1) break;
                        }
                    }
                    if (bestDist === 1) break;
                }
                if (best && bestDist <= 2 && vocabMap[best]) return String(vocabMap[best]);
                return token;
            });

            return out;
        },
        applyCommandMap(text, commandMap, { onEachMatch } = {}) {
            if (!commandMap) return text;
            const keys = Object.keys(commandMap).sort((a, b) => b.length - a.length);
            keys.forEach((command) => {
                const escaped = this.escapeRegExp(command);
                const re = new RegExp(
                    `(^|[^\\p{L}\\p{N}_])(${escaped})(?=$|[^\\p{L}\\p{N}_])`,
                    "giu"
                );
                text = text.replace(re, (match, prefix) => {
                    if (typeof onEachMatch === "function") {
                        try {
                            onEachMatch(command);
                        } catch (e) {
                            /* noop */
                        }
                    }
                    const fn = commandMap[command];
                    const replacement = typeof fn === "function" ? fn() : String(fn);
                    return `${prefix}${replacement}`;
                });
            });
            return text;
        },
        getUppercaseCommands() {
            return {
                "iniciar mayúscula sostenida": () => {
                    this.uppercase = true;
                    return "";
                },
                "iniciar mayúsculas sostenida": () => {
                    this.uppercase = true;
                    return "";
                },
                "iniciar mayúsculas": () => {
                    this.uppercase = true;
                    return "";
                },
                "iniciar mayúscula": () => {
                    this.uppercase = true;
                    return "";
                },
                "mayúsculas sostenida": () => {
                    this.uppercase = true;
                    return "";
                },
                "mayúscula sostenida": () => {
                    this.uppercase = true;
                    return "";
                },
                "activar mayúsculas": () => {
                    this.uppercase = true;
                    return "";
                },
                "activar mayúscula": () => {
                    this.uppercase = true;
                    return "";
                },
                "habilitar mayúsculas": () => {
                    this.uppercase = true;
                    return "";
                },
                "habilitar mayúscula": () => {
                    this.uppercase = true;
                    return "";
                },
                "desactivar mayúsculas": () => {
                    this.uppercase = false;
                    return "";
                },
                "desactivar mayúscula": () => {
                    this.uppercase = false;
                    return "";
                },
                "terminar mayúsculas": () => {
                    this.uppercase = false;
                    return "";
                },
                "terminar mayúscula": () => {
                    this.uppercase = false;
                    return "";
                },
                "fin mayúsculas": () => {
                    this.uppercase = false;
                    return "";
                },
                "fin mayúscula": () => {
                    this.uppercase = false;
                    return "";
                },
                "finaliza mayúsculas": () => {
                    this.uppercase = false;
                    return "";
                },
                "finaliza mayúscula": () => {
                    this.uppercase = false;
                    return "";
                },
                "finalizar mayúsculas": () => {
                    this.uppercase = false;
                    return "";
                },
                "finalizar mayúscula": () => {
                    this.uppercase = false;
                    return "";
                },
            };
        },
        getLineBreakCommands() {
            return {
                "punto aparte": () => ".\n\n",
                "salto de línea": () => "\n",
                "doble salto de línea": () => "\n\n",
                enter: () => "\n",
                "doble enter": () => "\n\n",
                "línea en blanco": () => "\n\n",
            };
        },
        getPunctuationCommands() {
            return {
                punto: () => {
                    this.capitalizeNext = true;
                    return ".";
                },
                coma: () => ",",
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
                "abrir comillas": () => '"',
                "cerrar comillas": () => '"',
                "abrir comillas simples": () => "'",
                "cerrar comillas simples": () => "'",
                guion: () => "-",
                "guion bajo": () => "_",
                "barra diagonal": () => "/",
                "barra invertida": () => "\\",
                "nueva oración": () => ". ",
            };
        },
        getAllVoiceCommands() {
            // Agrega claves sin tildes para tolerar dictado sin acentos
            const uppercaseCommands = this.getUppercaseCommands();
            const lineBreakCommands = this.getLineBreakCommands();
            const punctuationCommands = this.getPunctuationCommands();

            const addNormalizedKeys = (obj) => {
                const keys = Object.keys(obj);
                keys.forEach((k) => {
                    const normalized = this.stripDiacritics(k).toLowerCase();
                    if (!Object.prototype.hasOwnProperty.call(obj, normalized)) {
                        obj[normalized] = obj[k];
                    }
                });
            };
            addNormalizedKeys(uppercaseCommands);
            addNormalizedKeys(lineBreakCommands);
            addNormalizedKeys(punctuationCommands);
            return { uppercaseCommands, lineBreakCommands, punctuationCommands };
        },
        processCommands(text) {
            const { uppercaseCommands, lineBreakCommands, punctuationCommands } =
                this.getAllVoiceCommands();
            let out = String(text ?? "");
            out = this.applyCommandMap(out, uppercaseCommands);
            out = this.applyCommandMap(out, lineBreakCommands, {
                onEachMatch: () => {
                    this.capitalizeNext = true;
                },
            });
            out = this.applyCommandMap(out, punctuationCommands);
            out = this.uppercase ? out.toUpperCase() : out;
            if (this.capitalizeNext) {
                out = out.replace(/(^|\n)(\s*)([a-záéíóúüñ])/i, (m, start, spaces, first) => {
                    this.capitalizeNext = false;
                    return `${start}${spaces}${first.toUpperCase()}`;
                });
            }
            return out;
        },
        ensureVoiceSession() {
            if (this.voiceSession) return this.voiceSession;
            const { uppercaseCommands, lineBreakCommands, punctuationCommands } =
                this.getAllVoiceCommands();
            const grammarCommands = [
                ...Object.keys(uppercaseCommands),
                ...Object.keys(lineBreakCommands),
                ...Object.keys(punctuationCommands),
            ];

            this.voiceSession = createVoiceSession({
                enableAudio: false, // Ecografías: solo dictado (no grabación)
                enableDictation: true,
                lang: "es-ES",
                grammarCommands,
                maxNoSpeechRetries: 6,
                onFinalText: (raw) => {
                    let text = String(raw || "").trim();
                    if (!text) return;
                    text = this.processCommands(text);
                    text = this.applyMedicalCorrections(text);
                    if (!text) return;

                    // Añadir a lectura (mantener comportamiento: espacio si no termina en espacio/salto)
                    const suffix = /[ \n]$/.test(text) ? "" : " ";
                    this.registro.lectura = String(this.registro.lectura || "") + text + suffix;
                },
                onPermissionDenied: () => {
                    this.voiceState = "inactive";
                    this.microfono = "admin-wrap/assets/images/mic.gif";
                    $.toast({
                        heading: "Permiso requerido",
                        text: "El navegador bloqueó el micrófono. Permite el acceso al micrófono para usar dictado.",
                        position: "top-right",
                        loaderBg: "#ff6849",
                        icon: "warning",
                        hideAfter: 6000,
                        stack: 6,
                    });
                },
                onError: (e) => {
                    if (e && e.message === "Silencio repetido") {
                        this.voiceState = "inactive";
                        this.microfono = "admin-wrap/assets/images/mic.gif";
                        $.toast({
                            heading: "Sin voz detectada",
                            text: "No se detecta voz (silencio repetido). Verifica micrófono/entrada y vuelve a iniciar el dictado.",
                            position: "top-right",
                            loaderBg: "#ff6849",
                            icon: "warning",
                            hideAfter: 6000,
                            stack: 6,
                        });
                    }
                },
            });
            return this.voiceSession;
        },
        async toggleSpeechRecognition() {
            // Mantener el ícono/animación actual, sin tocar template.
            try {
                const vs = this.ensureVoiceSession();
                if (this.voiceState === "inactive") {
                    await vs.start();
                    this.voiceState = "active";
                    this.microfono = "admin-wrap/assets/images/mic-animate.gif";
                } else if (this.voiceState === "active") {
                    vs.pause();
                    this.voiceState = "paused";
                    this.microfono = "admin-wrap/assets/images/mic.gif";
                } else {
                    // paused -> resume
                    vs.resume();
                    this.voiceState = "active";
                    this.microfono = "admin-wrap/assets/images/mic-animate.gif";
                }
            } catch (e) {
                this.voiceState = "inactive";
                this.microfono = "admin-wrap/assets/images/mic.gif";
                $.toast({
                    heading: "Error",
                    text: e && e.message ? e.message : "No fue posible iniciar el dictado.",
                    position: "top-right",
                    loaderBg: "#ff6849",
                    icon: "error",
                    hideAfter: 5000,
                    stack: 6,
                });
            }
        },
        stopVoice() {
            if (this.voiceSession) {
                try {
                    this.voiceSession.destroy();
                } catch (e) {
                    /* noop */
                }
                this.voiceSession = null;
            }
            this.voiceState = "inactive";
            this.microfono = "admin-wrap/assets/images/mic.gif";
        },
        startSahiPolling() {
            if (!this.sedeInterface) return;
            if (!this.registro?.ecografia_producto_id) return;

            const id = this.registro.ecografia_producto_id;
            const onUpdate = (estado) => {
                if (estado.estado_envio_sahi != null)
                    this.registro.estado_envio_sahi = estado.estado_envio_sahi;
                this.registro.fechor_envio_sahi =
                    estado.fechor_envio_sahi ?? this.registro.fechor_envio_sahi;
                this.registro.error_envio_sahi = estado.error_envio_sahi ?? null;
                this.registro.sahi_esquema_149 =
                    estado.sahi_esquema_149 ?? this.registro.sahi_esquema_149;
                this.registro.sahi_esquema_150 =
                    estado.sahi_esquema_150 ?? this.registro.sahi_esquema_150;
            };
            const onError = (error) => {
                this.registro.estado_envio_sahi = "ERROR";
                this.registro.error_envio_sahi =
                    error.response?.data?.message || "Error consultando SAHI.";
            };
            this.startPolling(id, onUpdate, onError);
        },
        stopSahiPolling() {
            this.stopPolling();
        },
        async consultarEstadoSahi(silent = false) {
            if (!this.sedeInterface) return;
            const id = this.registro.ecografia_producto_id;
            if (!id) return;

            try {
                const estado = await this.consultarEstado(id);
                if (estado.estado_envio_sahi != null)
                    this.registro.estado_envio_sahi = estado.estado_envio_sahi;
                this.registro.fechor_envio_sahi =
                    estado.fechor_envio_sahi ?? this.registro.fechor_envio_sahi;
                this.registro.error_envio_sahi = estado.error_envio_sahi ?? null;
                this.registro.sahi_esquema_149 =
                    estado.sahi_esquema_149 ?? this.registro.sahi_esquema_149;
                this.registro.sahi_esquema_150 =
                    estado.sahi_esquema_150 ?? this.registro.sahi_esquema_150;

                if (this.registro.estado_envio_sahi !== "ENVIANDO") {
                    this.stopPolling();
                }

                if (!silent) {
                    $.toast({
                        heading: "SAHI",
                        text: "Estado actualizado.",
                        position: "top-right",
                        loaderBg: "#ff6849",
                        icon: "info",
                        hideAfter: 2500,
                    });
                }
            } catch (error) {
                if (!silent) {
                    $.toast({
                        heading: "SAHI",
                        text:
                            error.response?.data?.message ||
                            "No se pudo consultar el estado en SAHI.",
                        position: "top-right",
                        loaderBg: "#ff6849",
                        icon: "warning",
                        hideAfter: 3500,
                        stack: 6,
                    });
                }
            }
        },
        safeDestroyDataTable(selector = "#example23") {
            if (
                typeof window === "undefined" ||
                !window.$ ||
                !window.$.fn ||
                !window.$.fn.DataTable
            )
                return;
            const $t = window.$(selector);
            if (!$t.length) return;
            if (window.$.fn.DataTable.isDataTable($t)) {
                $t.DataTable().clear().destroy();
            }
        },
        safeInitDataTable(selector = "#example23") {
            if (
                typeof window === "undefined" ||
                !window.$ ||
                !window.$.fn ||
                !window.$.fn.DataTable
            )
                return;
            this.$nextTick(() => {
                const $t = window.$(selector);
                if (!$t.length) return;
                if (window.$.fn.DataTable.isDataTable($t)) return;
                $t.DataTable({
                    dom: "Bfrtip",
                    buttons: ["copy", "csv", "excel", "pdf", "print"],
                    language: {
                        emptyTable: "No se encontraron registros",
                        zeroRecords: "No se encontraron registros",
                    },
                });
            });
        },
        async listarPendiestePorLectura() {
            // Evita llamar al backend con "null"/vacío (causa 404 "El medico no se encontro")
            const medicoId = String(this.busqueda?.medico_id ?? "").trim();
            if (!medicoId || medicoId === "null" || medicoId === "undefined") {
                this.registros = [];
                handleAxiosErrorToast(
                    {
                        response: {
                            data: { message: "No hay médico seleccionado para listar pendientes." },
                        },
                    },
                    "No fue posible listar pendientes por lectura."
                );
                return;
            }
            try {
                this.isBuscandoPendientes = true;

                const res = await axios.post(
                    "/api/ecografia-listarPendientesPorLectura/" + medicoId
                );
                if (res.status == 200) {
                    this.safeDestroyDataTable(this.$refs.dt || "#example23");

                    this.registros = res.data.ecografias;

                    this.safeInitDataTable(this.$refs.dt || "#example23");

                    this.evaluarPendientesConsentimientosDiaAnterior();
                }
            } catch (error) {
                handleAxiosErrorToast(error, "No fue posible listar pendientes por lectura.");
                this.registros = [];
            } finally {
                this.isBuscandoPendientes = false;
            }
        },

        evaluarPendientesConsentimientosDiaAnterior() {
            const todayStart = new Date();
            todayStart.setHours(0, 0, 0, 0);

            const pendientes = (this.registros || []).filter((r) => {
                if (r?.consentimiento_estado !== "PENDIENTE_MEDICO") return false;
                if (!r?.consentimiento_firma_paciente_at) return false;
                const ts = new Date(r.consentimiento_firma_paciente_at).getTime();
                if (!Number.isFinite(ts)) return false;
                return ts < todayStart.getTime();
            });

            this.pendientesDiaAnterior = pendientes;
            this.bloqueoLectura = pendientes.length > 0;

            if (this.bloqueoLectura && window.$) {
                window
                    .$("#modal-pendientes-consentimientos")
                    .modal({ backdrop: "static", keyboard: false });
            } else if (!this.bloqueoLectura && window.$) {
                window.$("#modal-pendientes-consentimientos").modal("hide");
            }
        },

        async firmarConsentimientoMedico(item) {
            const id = item?.consentimiento_id;
            if (!id) return;
            if (this.isFirmandoConsentimiento[id]) return;
            try {
                this.setMapValue("isFirmandoConsentimiento", id, true);
                await axios.post("/api/ecografias-consentimientos/firmar-medico", {
                    consentimiento_id: id,
                });

                item.consentimiento_estado = "COMPLETO";
                item.consentimiento_firma_medico_at = new Date()
                    .toISOString()
                    .slice(0, 19)
                    .replace("T", " ");

                this.evaluarPendientesConsentimientosDiaAnterior();
            } catch (error) {
                handleAxiosErrorToast(error, "No fue posible firmar el consentimiento.");
            } finally {
                this.setMapValue("isFirmandoConsentimiento", id, false);
            }
        },
        async generarLectura() {
            try {
                // Enviar solicitud para guardar la ecografía
                const res = await axios.put(
                    "/api/ecografia-lectura/" + this.registro.ecografia_id,
                    { accion: this.accion, registro: this.registro }
                );

                if (res.status === 201) {
                    // Mostrar notificación de éxito
                    $.toast({
                        heading: "¡Éxito!",
                        text: "Lectura guardada correctamente.",
                        position: "top-right",
                        loaderBg: "#ff6849",
                        icon: "success",
                        hideAfter: 3500,
                        stack: 6,
                    });

                    return true;
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

                    return false;
                }
            } catch (error) {
                handleAxiosErrorToast(error, "Ocurrió un error al guardar la lectura.");

                // Mostrar errores específicos en el formulario
                if (error.response?.data?.errors) {
                    this.errores = error.response.data.errors;
                }

                return false;
            }
        },
        async enviarASahiEcografiaProducto() {
            if (!this.sedeInterface) return true;

            const id = this.registro.ecografia_producto_id;
            if (!id) {
                $.toast({
                    heading: "Advertencia",
                    text: "No se encontró ecografia_producto_id para enviar a SAHI.",
                    position: "top-right",
                    loaderBg: "#ff6849",
                    icon: "warning",
                    hideAfter: 3500,
                    stack: 6,
                });
                return false;
            }

            try {
                this.registro.estado_envio_sahi = "ENVIANDO";
                const res = await this.enviarEcografiaProducto(id);

                const encolado = res.status === 202;
                $.toast({
                    heading: "SAHI",
                    text:
                        res.data?.message ||
                        (encolado ? "Envío a SAHI encolado." : "Enviado a SAHI correctamente."),
                    position: "top-right",
                    loaderBg: "#ff6849",
                    icon: encolado ? "info" : "success",
                    hideAfter: 3500,
                    stack: 6,
                });

                this.registro.estado_envio_sahi = encolado ? "ENVIANDO" : "ENVIADO";
                this.registro.fechor_envio_sahi = new Date().toISOString();
                this.registro.error_envio_sahi = null;
                if (!encolado && res.data?.data) {
                    this.registro.sahi_esquema_149 =
                        res.data.data.IdEsquemaEncabezado149 ?? this.registro.sahi_esquema_149;
                    this.registro.sahi_esquema_150 =
                        res.data.data.IdEsquemaEncabezado150 ?? this.registro.sahi_esquema_150;
                }

                if (encolado) {
                    const onUpdate = (estado) => {
                        if (estado.estado_envio_sahi != null)
                            this.registro.estado_envio_sahi = estado.estado_envio_sahi;
                        this.registro.fechor_envio_sahi =
                            estado.fechor_envio_sahi ?? this.registro.fechor_envio_sahi;
                        this.registro.error_envio_sahi = estado.error_envio_sahi ?? null;
                        this.registro.sahi_esquema_149 =
                            estado.sahi_esquema_149 ?? this.registro.sahi_esquema_149;
                        this.registro.sahi_esquema_150 =
                            estado.sahi_esquema_150 ?? this.registro.sahi_esquema_150;
                    };
                    const onError = (error) => {
                        this.registro.estado_envio_sahi = "ERROR";
                        this.registro.error_envio_sahi =
                            error.response?.data?.message || "Error consultando SAHI.";
                    };
                    this.startPolling(id, onUpdate, onError);
                }

                return true;
            } catch (error) {
                $.toast({
                    heading: "SAHI",
                    text: error.response?.data?.message || "No se pudo enviar a SAHI.",
                    position: "top-right",
                    loaderBg: "#ff6849",
                    icon: "warning",
                    hideAfter: 4500,
                    stack: 6,
                });
                this.registro.estado_envio_sahi = "ERROR";
                this.registro.error_envio_sahi =
                    error.response?.data?.message || "No se pudo enviar a SAHI.";
                return false;
            }
        },
        async guardarRegistro() {
            const accionActual = "TERMINAR_LECTURA";
            this.accion = accionActual;

            this.isGuardandoLectura = true;
            const guardadoOk = await this.generarLectura();
            this.accion = null;
            this.isGuardandoLectura = false;

            if (!guardadoOk) return;

            // Enviar a SAHI solo si la sede tiene interface y la acción fue TERMINAR_LECTURA
            if (this.sedeInterface && accionActual === "TERMINAR_LECTURA") {
                await this.enviarASahiEcografiaProducto();
            }

            this.listarPendiestePorLectura();
            this.limpiarFormulario();
            this.btnCerralModalForm();
        },
        async generarReporte() {
            try {
                this.accion = "IMPRIMIR_LECTURA";
                this.generarLectura();
                this.accion = null;

                const iframe = document.getElementById("ifrReporteEcografia");
                iframe.src = "";

                const res = await axios.post(
                    "/api/ecografia-generar-reporte/" + this.registro.ecografia_id,
                    this.registro
                );
                const pathReporte = res.data.plantilla;

                // Mostrar el reporte en el iframe
                iframe.src = pathReporte;

                this.listarPendiestePorLectura();

                return true;
            } catch (error) {
                handleAxiosErrorToast(error, "Ocurrió un error al generar el reporte.");

                // Mostrar errores específicos en el formulario
                if (error.response?.data?.errors) {
                    this.errores = error.response.data.errors;
                }
            }
        },
        async establecerSede() {
            this.registro.sede_id = sessionStorage.getItem("ST-sede");
            this.busqueda.sede_id = sessionStorage.getItem("ST-sede");
            this.sedeActual = sessionStorage.getItem("ST-sede");
            await this.establecerSede(); // composable call
            this.registros = [];
        },
        establecerMedico() {
            // Siempre usar el usuario logueado como médico (evita null y evita depender de sessionStorage)
            const medicoId = this.usuarioactual?.id != null ? String(this.usuarioactual.id) : "";

            this.busqueda.medico_id = medicoId;
            this.registro.medico_id = medicoId;

            // Opcional: sincronizamos para otras pantallas que lean ST-medico
            if (medicoId) sessionStorage.setItem("ST-medico", medicoId);

            this.listarPendiestePorLectura();
        },
        async listarTiposPlantillas() {
            try {
                const res = await axios.get("/api/config-tipos-plantillas/listar/activos");
                this.tiposPlantillas = res.data;
            } catch (error) {
                handleAxiosErrorToast(error, "No fue posible listar tipos de plantillas.");
                this.tiposPlantillas = [];
            }
        },
        async onTipoPlantillaSelect(value) {
            let selectedId = value || null;
            if (selectedId == null) return;

            try {
                const res = await axios.get(`/api/config-plantillas/listar/activos/${selectedId}`);

                this.plantillas = res.data;
                this.plantillasOptions;
                var tipo = this.tiposPlantillasOptions.find(function (t) {
                    return String(t.id) === String(selectedId);
                });
                this.registro.tipo_estudio = (tipo && tipo.nom_tipo) || "";
            } catch (error) {
                handleAxiosErrorToast(error, "No fue posible listar plantillas.");
            }
        },
        onPlantillaSelect(value) {
            var plantilla = this.plantillasOptions.find(function (p) {
                return String(p.id) === String(value);
            });
            this.registro.lectura = (plantilla && plantilla.plantilla) || null;
            this.registro.opinion = (plantilla && plantilla.opinion) || null;
        },
        btnCerralModalForm() {
            $(".right-sidebar").slideDown(50);
            $(".right-sidebar").toggleClass("shw-rside");
            // al cerrar, detenemos polling para evitar fugas
            this.stopSahiPolling();
        },
        mostrarRegistro(data = {}) {
            this.tituloAtencion = `Atención N°: ${data.atencion_id}`;
            this.tituloModal = `Paciente: ${data.paciente.num_docu} - Nombres: ${data.paciente.nombres} ${data.paciente.seg_nom} ${data.paciente.pri_ape} ${data.paciente.seg_ape} `;
            this.subTituloModal = `Examen: ${data.nom_produc} - Valor: ${data.ValProduc}`;

            this.registro.ecografia_id = data.id;
            this.registro.ecografia_producto_id = data.ecografia_producto_id;
            this.registro.producto_id = data.producto_id;
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

            this.registro.lectura = data.lectura;
            this.registro.opinion = data.opinion;

            // Estado de envío a SAHI (si viene en el select)
            this.registro.estado_envio_sahi = data.estado_envio_sahi || null;
            this.registro.fechor_envio_sahi = data.fechor_envio_sahi || null;
            this.registro.error_envio_sahi = data.error_envio_sahi || null;
            this.registro.sahi_esquema_149 = data.sahi_esquema_149 || null;
            this.registro.sahi_esquema_150 = data.sahi_esquema_150 || null;

            if (this.sedeInterface && this.registro.estado_envio_sahi === "ENVIANDO") {
                this.startSahiPolling();
            } else {
                this.stopSahiPolling();
            }

            this.btnCerralModalForm();
        },
        limpiarFormulario() {
            this.tituloModal = "";
            this.stopSahiPolling();

            this.registro.paciente_id = "";
            this.registro.num_docu = "";
            this.registro.nombres = "";
            this.registro.seg_nom = "";
            this.registro.pri_ape = "";
            this.registro.seg_ape = "";
            this.registro.sexo = "";
            this.registro.fec_naci = "";
            this.registro.direccion = "";
            this.registro.tel = "";
            this.registro.cel = "";
            this.registro.atencion_id = "";
            this.registro.contrato = "";
            this.registro.plan = "";
            this.registro.admin_salud = "";
            this.registro.quien_ordeno = "";
            this.registro.lectura = "";
            this.registro.opinion = "";
            this.errores = {};
        },
    },
};
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

.sidebar-scroll {
    overflow-x: hidden;
    overflow-y: scroll;
    height: 100%;
}

.breadcrumb-btn {
    vertical-align: baseline;
}

.mic-link {
    width: 30px;
    height: 30px;
    display: inline-block;
}
</style>

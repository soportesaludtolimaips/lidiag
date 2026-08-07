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
                                                id="bus_fech_ini"
                                                name="bus_fech_ini"
                                                v-model="busqueda.bus_fech_ini"
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
<script setup>
const { proxy } = getCurrentInstance();
const emitter = proxy.emitter;
import { computed, nextTick, onBeforeUnmount, onMounted, ref, getCurrentInstance } from "vue";

import usePermissions from "@/helpers/permissions.js";
import { toastMessage } from "@/helpers/alerts";
import { setMapValue } from "@/helpers/sahi";
import { createVoiceSession } from "@/helpers/voice/voiceSession";
import medicalReplacements from "@/data/medical_replacements.json";
import medicalTermsRaw from "@/data/terminos_medicos.txt?raw";
import axios from "axios";

const props = defineProps({ usuarioactual: Object, permisos: Array });

const dt = ref(null);
const actualizar = ref(false);

const tituloModal = ref("Nueva admisión");
const subTituloModal = ref("");
const tituloAtencion = ref("");
const busqueda = ref({
    sede_id: sedeActual,
    medico_id: "",
    bus_nom_num_docu: "",
    bus_fech_ini: "",
    bus_fecha_fin: "",
});
const registros = ref([]);
const registro = ref({
    ecografia_id: "",
    ecografia_producto_id: "",
    producto_id: "",
    sede_id: sedeActual,
    medico_id: "",
    quien_registro_id: props.usuarioactual.id,
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
});
const errores = ref({});
const erroresBusqueda = ref({});
const medicos = ref([]);
const accion = ref(null);
const productosAgenda = ref([]);
const tiposPlantillas = ref([]);
const plantillas = ref([]);
const url = ref("");
const microfono = ref("admin-wrap/assets/images/mic.gif");
const voiceSession = ref(null);
const voiceState = ref("inactive");
const uppercase = ref(false);
const capitalizeNext = ref(false);
const medicalReplCompiled = ref(null);
const medicalVocabularyMap = ref({});
const medicalVocabularyKeys = ref([]);
const medicalVocabularyIndex = ref({});
const isGuardandoLectura = ref(false);
const isBuscandoPendientes = ref(false);
const bloqueoLectura = ref(false);
const pendientesDiaAnterior = ref([]);
const isFirmandoConsentimiento = ref({});

const tiposPlantillasOptions = computed(() => {
    // Opciones para el Select2 local de medicos.
    return (tiposPlantillas.value || []).map((p) => ({
        id: String(p.id),
        nom_tipo: p.nom_tipo,
    }));
});
const plantillasOptions = computed(() => {
    // Opciones para el Select2 local de medicos.
    return (plantillas.value || []).map((p) => ({
        id: String(p.id),
        nom_plantilla: p.nom_plantilla,
        plantilla: p.plantilla,
        opinion: p.opinion,
    }));
});

function buscarStudy() {
    // Soporte para Enter en el input de búsqueda
    return listarPendiestePorLectura();
}
function btnLimpiar() {
    // Limpia criterios de búsqueda (mantiene sede/medico del contexto)
    busqueda.value.bus_nom_num_docu = "";
    busqueda.value.bus_fech_ini = "";
    busqueda.value.bus_fecha_fin = "";
    erroresBusqueda.value = {};

    safeDestroyDataTable(dt.value || "#example23");
    registros.value = [];
    pendientesDiaAnterior.value = [];
    bloqueoLectura.value = false;
    limpiarFormulario();
}
function getMedicalReplacementsCompiled() {
    if (medicalReplCompiled.value) return medicalReplCompiled.value;
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
    medicalReplCompiled.value = compiled;
    return compiled;
}
function applyMedicalReplacements(text) {
    let out = String(text ?? "");
    const compiled = getMedicalReplacementsCompiled();
    compiled.forEach(([re, rep]) => {
        out = out.replace(re, rep);
    });
    return out;
}
function stripDiacritics(text) {
    try {
        return String(text)
            .normalize("NFD")
            .replace(/[u0300-u036f]/g, "");
    } catch (e) {
        return String(text);
    }
}
function escapeRegExp(text) {
    return String(text).replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
}
function normalizeWord(text) {
    return stripDiacritics(String(text))
        .toLowerCase()
        .replace(/[^a-z0-9ñ]+/gi, "");
}
function levenshteinMax(a, b, maxDist = 2) {
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
}
function rebuildMedicalVocabulary() {
    const map = {};
    const addPhrase = (phrase) => {
        if (!phrase) return;
        const s = String(phrase).trim();
        if (!s) return;

        const nFull = normalizeWord(s);
        if (nFull) map[nFull] = s;

        const tokens = s.split(/[s/,-]+/g).filter(Boolean);
        tokens.forEach((t) => {
            const nt = normalizeWord(t);
            if (nt && nt.length >= 4 && !map[nt]) map[nt] = t;
        });
    };

    // Fuente principal: archivo grande de términos
    MEDICAL_TERMS_FROM_TXT.forEach(addPhrase);

    medicalVocabularyMap.value = map;
    const keys = Object.keys(map);
    medicalVocabularyKeys.value = keys;

    const index = {};
    keys.forEach((k) => {
        if (!k) return;
        const first = k[0];
        const len = k.length;
        if (!index[first]) index[first] = {};
        if (!index[first][len]) index[first][len] = [];
        index[first][len].push(k);
    });
    medicalVocabularyIndex.value = index;
}
function applyMedicalCorrections(text) {
    // 1) Normalizaciones (regex)
    let out = applyMedicalReplacements(text);

    // 2) Corrección por vocabulario (exact + fuzzy indexado)
    const vocabMap = medicalVocabularyMap.value || {};
    const vocabIndex = medicalVocabularyIndex.value || {};
    if (!vocabMap || !Object.keys(vocabMap).length) return out;

    out = String(out).replace(/[p{L}p{N}]+/gu, (token) => {
        if (/^d+$/u.test(token)) return token;
        if (token.length < 4) return token;
        const n = normalizeWord(token);
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
                const d = levenshteinMax(n, k, 2);
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
}
function applyCommandMap(text, commandMap, { onEachMatch } = {}) {
    if (!commandMap) return text;
    const keys = Object.keys(commandMap).sort((a, b) => b.length - a.length);
    keys.forEach((command) => {
        const escaped = escapeRegExp(command);
        const re = new RegExp(`(^|[^\\p{L}\\p{N}_])(${escaped})(?=$|[^\\p{L}\\p{N}_])`, "giu");
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
}
function getUppercaseCommands() {
    return {
        "iniciar mayúscula sostenida": () => {
            uppercase.value = true;
            return "";
        },
        "iniciar mayúsculas sostenida": () => {
            uppercase.value = true;
            return "";
        },
        "iniciar mayúsculas": () => {
            uppercase.value = true;
            return "";
        },
        "iniciar mayúscula": () => {
            uppercase.value = true;
            return "";
        },
        "mayúsculas sostenida": () => {
            uppercase.value = true;
            return "";
        },
        "mayúscula sostenida": () => {
            uppercase.value = true;
            return "";
        },
        "activar mayúsculas": () => {
            uppercase.value = true;
            return "";
        },
        "activar mayúscula": () => {
            uppercase.value = true;
            return "";
        },
        "habilitar mayúsculas": () => {
            uppercase.value = true;
            return "";
        },
        "habilitar mayúscula": () => {
            uppercase.value = true;
            return "";
        },
        "desactivar mayúsculas": () => {
            uppercase.value = false;
            return "";
        },
        "desactivar mayúscula": () => {
            uppercase.value = false;
            return "";
        },
        "terminar mayúsculas": () => {
            uppercase.value = false;
            return "";
        },
        "terminar mayúscula": () => {
            uppercase.value = false;
            return "";
        },
        "fin mayúsculas": () => {
            uppercase.value = false;
            return "";
        },
        "fin mayúscula": () => {
            uppercase.value = false;
            return "";
        },
        "finaliza mayúsculas": () => {
            uppercase.value = false;
            return "";
        },
        "finaliza mayúscula": () => {
            uppercase.value = false;
            return "";
        },
        "finalizar mayúsculas": () => {
            uppercase.value = false;
            return "";
        },
        "finalizar mayúscula": () => {
            uppercase.value = false;
            return "";
        },
    };
}
function getLineBreakCommands() {
    return {
        "punto aparte": () => ".nn",
        "salto de línea": () => "n",
        "doble salto de línea": () => "nn",
        enter: () => "n",
        "doble enter": () => "nn",
        "línea en blanco": () => "nn",
    };
}
function getPunctuationCommands() {
    return {
        punto: () => {
            capitalizeNext.value = true;
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
}
function getAllVoiceCommands() {
    // Agrega claves sin tildes para tolerar dictado sin acentos
    const uppercaseCommands = getUppercaseCommands();
    const lineBreakCommands = getLineBreakCommands();
    const punctuationCommands = getPunctuationCommands();

    const addNormalizedKeys = (obj) => {
        const keys = Object.keys(obj);
        keys.forEach((k) => {
            const normalized = stripDiacritics(k).toLowerCase();
            if (!Object.prototype.hasOwnProperty.call(obj, normalized)) {
                obj[normalized] = obj[k];
            }
        });
    };
    addNormalizedKeys(uppercaseCommands);
    addNormalizedKeys(lineBreakCommands);
    addNormalizedKeys(punctuationCommands);
    return { uppercaseCommands, lineBreakCommands, punctuationCommands };
}
function processCommands(text) {
    const { uppercaseCommands, lineBreakCommands, punctuationCommands } = getAllVoiceCommands();
    let out = String(text ?? "");
    out = applyCommandMap(out, uppercaseCommands);
    out = applyCommandMap(out, lineBreakCommands, {
        onEachMatch: () => {
            capitalizeNext.value = true;
        },
    });
    out = applyCommandMap(out, punctuationCommands);
    out = uppercase.value ? out.toUpperCase() : out;
    if (capitalizeNext.value) {
        out = out.replace(/(^|n)(s*)([a-záéíóúüñ])/i, (m, start, spaces, first) => {
            capitalizeNext.value = false;
            return `${start}${spaces}${first.toUpperCase()}`;
        });
    }
    return out;
}
function ensureVoiceSession() {
    if (voiceSession.value) return voiceSession.value;
    const { uppercaseCommands, lineBreakCommands, punctuationCommands } = getAllVoiceCommands();
    const grammarCommands = [
        ...Object.keys(uppercaseCommands),
        ...Object.keys(lineBreakCommands),
        ...Object.keys(punctuationCommands),
    ];

    voiceSession.value = createVoiceSession({
        enableAudio: false, // Ecografías: solo dictado (no grabación)
        enableDictation: true,
        lang: "es-ES",
        grammarCommands,
        maxNoSpeechRetries: 6,
        onFinalText: (raw) => {
            let text = String(raw || "").trim();
            if (!text) return;
            text = processCommands(text);
            text = applyMedicalCorrections(text);
            if (!text) return;

            // Añadir a lectura (mantener comportamiento: espacio si no termina en espacio/salto)
            const suffix = /[ n]$/.test(text) ? "" : " ";
            registro.value.lectura = String(registro.value.lectura || "") + text + suffix;
        },
        onPermissionDenied: () => {
            voiceState.value = "inactive";
            microfono.value = "admin-wrap/assets/images/mic.gif";
            toastMessage({
                heading: "Permiso requerido",
                text: "El navegador bloqueó el micrófono. Permite el acceso al micrófono para usar dictado.",
                icon: "warning",
                hideAfter: 6000,
            });
        },
        onError: (e) => {
            if (e && e.message === "Silencio repetido") {
                voiceState.value = "inactive";
                microfono.value = "admin-wrap/assets/images/mic.gif";
                toastMessage({
                    heading: "Sin voz detectada",
                    text: "No se detecta voz (silencio repetido). Verifica micrófono/entrada y vuelve a iniciar el dictado.",
                    icon: "warning",
                    hideAfter: 6000,
                });
            }
        },
    });
    return voiceSession.value;
}
async function toggleSpeechRecognition() {
    // Mantener el ícono/animación actual, sin tocar template.
    try {
        const vs = ensureVoiceSession();
        if (voiceState.value === "inactive") {
            await vs.start();
            voiceState.value = "active";
            microfono.value = "admin-wrap/assets/images/mic-animate.gif";
        } else if (voiceState.value === "active") {
            vs.pause();
            voiceState.value = "paused";
            microfono.value = "admin-wrap/assets/images/mic.gif";
        } else {
            // paused -> resume
            vs.resume();
            voiceState.value = "active";
            microfono.value = "admin-wrap/assets/images/mic-animate.gif";
        }
    } catch (e) {
        voiceState.value = "inactive";
        microfono.value = "admin-wrap/assets/images/mic.gif";
        toastMessage({
            heading: "Error",
            text: e && e.message ? e.message : "No fue posible iniciar el dictado.",
            icon: "error",
            hideAfter: 5000,
        });
    }
}
function stopVoice() {
    if (voiceSession.value) {
        try {
            voiceSession.value.destroy();
        } catch (e) {
            /* noop */
        }
        voiceSession.value = null;
    }
    voiceState.value = "inactive";
    microfono.value = "admin-wrap/assets/images/mic.gif";
}
function startSahiPolling() {
    if (!sedeInterface) return;
    if (!registro.value?.ecografia_producto_id) return;

    const id = registro.value.ecografia_producto_id;
    const onUpdate = (estado) => {
        if (estado.estado_envio_sahi != null)
            registro.value.estado_envio_sahi = estado.estado_envio_sahi;
        registro.value.fechor_envio_sahi =
            estado.fechor_envio_sahi ?? registro.value.fechor_envio_sahi;
        registro.value.error_envio_sahi = estado.error_envio_sahi ?? null;
        registro.value.sahi_esquema_149 =
            estado.sahi_esquema_149 ?? registro.value.sahi_esquema_149;
        registro.value.sahi_esquema_150 =
            estado.sahi_esquema_150 ?? registro.value.sahi_esquema_150;
    };
    const onError = (error) => {
        registro.value.estado_envio_sahi = "ERROR";
        registro.value.error_envio_sahi =
            error.response?.data?.message || "Error consultando SAHI.";
    };
    startPolling(id, onUpdate, onError);
}
function stopSahiPolling() {
    stopPolling();
}
async function consultarEstadoSahi(silent = false) {
    if (!sedeInterface) return;
    const id = registro.value.ecografia_producto_id;
    if (!id) return;

    try {
        const estado = await consultarEstado(id);
        if (estado.estado_envio_sahi != null)
            registro.value.estado_envio_sahi = estado.estado_envio_sahi;
        registro.value.fechor_envio_sahi =
            estado.fechor_envio_sahi ?? registro.value.fechor_envio_sahi;
        registro.value.error_envio_sahi = estado.error_envio_sahi ?? null;
        registro.value.sahi_esquema_149 =
            estado.sahi_esquema_149 ?? registro.value.sahi_esquema_149;
        registro.value.sahi_esquema_150 =
            estado.sahi_esquema_150 ?? registro.value.sahi_esquema_150;

        if (registro.value.estado_envio_sahi !== "ENVIANDO") {
            stopPolling();
        }

        if (!silent) {
            toastMessage({
                heading: "SAHI",
                text: "Estado actualizado.",
                icon: "info",
                hideAfter: 2500,
            });
        }
    } catch (error) {
        if (!silent) {
            toastMessage({
                heading: "SAHI",
                text: error.response?.data?.message || "No se pudo consultar el estado en SAHI.",
                icon: "warning",
            });
        }
    }
}
function safeDestroyDataTable(selector = "#example23") {
    if (typeof window === "undefined" || !window.$ || !window.$.fn || !window.$.fn.DataTable)
        return;
    const $t = window.$(selector);
    if (!$t.length) return;
    if (window.$.fn.DataTable.isDataTable($t)) {
        $t.DataTable().clear().destroy();
    }
}
function safeInitDataTable(selector = "#example23") {
    if (typeof window === "undefined" || !window.$ || !window.$.fn || !window.$.fn.DataTable)
        return;
    nextTick(() => {
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
}
async function listarPendiestePorLectura() {
    // Evita llamar al backend con "null"/vacío (causa 404 "El medico no se encontro")
    const medicoId = String(busqueda.value?.medico_id ?? "").trim();
    if (!medicoId || medicoId === "null" || medicoId === "undefined") {
        registros.value = [];
        return;
    }
    try {
        isBuscandoPendientes.value = true;

        const res = await axios.post("/api/ecografia-listarPendientesPorLectura/" + medicoId);
        if (res.status == 200) {
            safeDestroyDataTable(dt.value || "#example23");

            registros.value = res.data.data.ecografias;

            safeInitDataTable(dt.value || "#example23");

            evaluarPendientesConsentimientosDiaAnterior();
        }
    } catch (error) {
        registros.value = [];
    } finally {
        isBuscandoPendientes.value = false;
    }
}
function evaluarPendientesConsentimientosDiaAnterior() {
    const todayStart = new Date();
    todayStart.setHours(0, 0, 0, 0);

    const pendientes = (registros.value || []).filter((r) => {
        if (r?.consentimiento_estado !== "PENDIENTE_MEDICO") return false;
        if (!r?.consentimiento_firma_paciente_at) return false;
        const ts = new Date(r.consentimiento_firma_paciente_at).getTime();
        if (!Number.isFinite(ts)) return false;
        return ts < todayStart.getTime();
    });

    pendientesDiaAnterior.value = pendientes;
    bloqueoLectura.value = pendientes.length > 0;

    if (bloqueoLectura.value && window.$) {
        window
            .$("#modal-pendientes-consentimientos")
            .modal({ backdrop: "static", keyboard: false });
    } else if (!bloqueoLectura.value && window.$) {
        window.$("#modal-pendientes-consentimientos").modal("hide");
    }
}
async function firmarConsentimientoMedico(item) {
    const id = item?.consentimiento_id;
    if (!id) return;
    if (isFirmandoConsentimiento.value[id]) return;
    try {
        setMapValue(proxy, "isFirmandoConsentimiento", id, true);
        await axios.post("/api/ecografias-consentimientos/firmar-medico", {
            consentimiento_id: id,
        });

        item.consentimiento_estado = "COMPLETO";
        item.consentimiento_firma_medico_at = new Date()
            .toISOString()
            .slice(0, 19)
            .replace("T", " ");

        evaluarPendientesConsentimientosDiaAnterior();
    } catch (error) {
    } finally {
        setMapValue(proxy, "isFirmandoConsentimiento", id, false);
    }
}
async function generarLectura() {
    try {
        // Enviar solicitud para guardar la ecografía
        const res = await axios.put("/api/ecografia-lectura/" + registro.value.ecografia_id, {
            accion: accion.value,
            registro: registro.value,
        });

        if (res.status === 201) {
            // Mostrar notificación de éxito
            toastMessage({
                heading: "¡Éxito!",
                text: "Lectura guardada correctamente.",
                icon: "success",
            });

            return true;
        } else {
            toastMessage({ heading: "Upss!!!", text: res.data.data.message, icon: "error" });

            return false;
        }
    } catch (error) {
        // Mostrar errores específicos en el formulario
        if (error.response?.data?.errors) {
            errores.value = error.response.data.errors;
        }

        return false;
    }
}
async function enviarASahiEcografiaProducto() {
    if (!sedeInterface) return true;

    const id = registro.value.ecografia_producto_id;
    if (!id) {
        toastMessage({
            heading: "Advertencia",
            text: "No se encontró ecografia_producto_id para enviar a SAHI.",
            icon: "warning",
        });
        return false;
    }

    try {
        registro.value.estado_envio_sahi = "ENVIANDO";
        const res = await enviarEcografiaProducto(id);

        const encolado = res.status === 202;
        toastMessage({
            heading: "SAHI",
            text:
                res.data?.message ||
                (encolado ? "Envío a SAHI encolado." : "Enviado a SAHI correctamente."),
            icon: encolado ? "info" : "success",
        });

        registro.value.estado_envio_sahi = encolado ? "ENVIANDO" : "ENVIADO";
        registro.value.fechor_envio_sahi = new Date().toISOString();
        registro.value.error_envio_sahi = null;
        if (!encolado && res.data?.data) {
            registro.value.sahi_esquema_149 =
                res.data.data.IdEsquemaEncabezado149 ?? registro.value.sahi_esquema_149;
            registro.value.sahi_esquema_150 =
                res.data.data.IdEsquemaEncabezado150 ?? registro.value.sahi_esquema_150;
        }

        if (encolado) {
            const onUpdate = (estado) => {
                if (estado.estado_envio_sahi != null)
                    registro.value.estado_envio_sahi = estado.estado_envio_sahi;
                registro.value.fechor_envio_sahi =
                    estado.fechor_envio_sahi ?? registro.value.fechor_envio_sahi;
                registro.value.error_envio_sahi = estado.error_envio_sahi ?? null;
                registro.value.sahi_esquema_149 =
                    estado.sahi_esquema_149 ?? registro.value.sahi_esquema_149;
                registro.value.sahi_esquema_150 =
                    estado.sahi_esquema_150 ?? registro.value.sahi_esquema_150;
            };
            const onError = (error) => {
                registro.value.estado_envio_sahi = "ERROR";
                registro.value.error_envio_sahi =
                    error.response?.data?.message || "Error consultando SAHI.";
            };
            startPolling(id, onUpdate, onError);
        }

        return true;
    } catch (error) {
        toastMessage({
            heading: "SAHI",
            text: error.response?.data?.message || "No se pudo enviar a SAHI.",
            icon: "warning",
            hideAfter: 4500,
        });
        registro.value.estado_envio_sahi = "ERROR";
        registro.value.error_envio_sahi =
            error.response?.data?.message || "No se pudo enviar a SAHI.";
        return false;
    }
}
async function guardarRegistro() {
    const accionActual = "TERMINAR_LECTURA";
    accion.value = accionActual;

    isGuardandoLectura.value = true;
    const guardadoOk = await generarLectura();
    accion.value = null;
    isGuardandoLectura.value = false;

    if (!guardadoOk) return;

    // Enviar a SAHI solo si la sede tiene interface y la acción fue TERMINAR_LECTURA
    if (sedeInterface && accionActual === "TERMINAR_LECTURA") {
        await enviarASahiEcografiaProducto();
    }

    listarPendiestePorLectura();
    limpiarFormulario();
    btnCerralModalForm();
}
async function generarReporte() {
    try {
        accion.value = "IMPRIMIR_LECTURA";
        generarLectura();
        accion.value = null;

        const iframe = document.getElementById("ifrReporteEcografia");
        iframe.src = "";

        const res = await axios.post(
            "/api/ecografia-generar-reporte/" + registro.value.ecografia_id,
            registro.value
        );
        const pathReporte = res.data.data.plantilla;

        // Mostrar el reporte en el iframe
        iframe.src = pathReporte;

        listarPendiestePorLectura();

        return true;
    } catch (error) {
        // Mostrar errores específicos en el formulario
        if (error.response?.data?.errors) {
            errores.value = error.response.data.errors;
        }
    }
}
async function establecerSede() {
    registro.value.sede_id = sessionStorage.getItem("ST-sede");
    busqueda.value.sede_id = sessionStorage.getItem("ST-sede");
    sedeActual = sessionStorage.getItem("ST-sede");
    await establecerSede(); // composable call
    registros.value = [];
}
function establecerMedico() {
    // Siempre usar el usuario logueado como médico (evita null y evita depender de sessionStorage)
    const medicoId = usuarioactual?.id != null ? String(usuarioactual.id) : "";

    busqueda.value.medico_id = medicoId;
    registro.value.medico_id = medicoId;

    // Opcional: sincronizamos para otras pantallas que lean ST-medico
    if (medicoId) sessionStorage.setItem("ST-medico", medicoId);

    listarPendiestePorLectura();
}
async function listarTiposPlantillas() {
    try {
        const res = await axios.get("/api/config-tipos-plantillas/listar/activos");
        tiposPlantillas.value = res.data.data;
    } catch (error) {
        tiposPlantillas.value = [];
    }
}
async function onTipoPlantillaSelect(value) {
    let selectedId = value || null;
    if (selectedId == null) return;

    try {
        const res = await axios.get(
            `/api/config-plantillas/listar/activos/${selectedId}/${medico.value}`
        );

        plantillas.value = res.data.data;
        plantillasOptions.value;
        var tipo = tiposPlantillasOptions.value.find(function (t) {
            return String(t.id) === String(selectedId);
        });
        registro.value.tipo_estudio = (tipo && tipo.nom_tipo) || "";
    } catch (error) {}
}
function onPlantillaSelect(value) {
    var plantilla = plantillasOptions.value.find(function (p) {
        return String(p.id) === String(value);
    });
    registro.value.lectura = (plantilla && plantilla.plantilla) || null;
    registro.value.opinion = (plantilla && plantilla.opinion) || null;
}
function btnCerralModalForm() {
    $(".right-sidebar").slideDown(50);
    $(".right-sidebar").toggleClass("shw-rside");
    // al cerrar, detenemos polling para evitar fugas
    stopSahiPolling();
}
function mostrarRegistro(data = {}) {
    tituloAtencion.value = `Atención N°: ${data.atencion_id}`;
    tituloModal.value = `Paciente: ${data.paciente.num_docu} - Nombres: ${data.paciente.nombres} ${data.paciente.seg_nom} ${data.paciente.pri_ape} ${data.paciente.seg_ape} `;
    subTituloModal.value = `Examen: ${data.nom_produc} - Valor: ${data.ValProduc}`;

    registro.value.ecografia_id = data.id;
    registro.value.ecografia_producto_id = data.ecografia_producto_id;
    registro.value.producto_id = data.producto_id;
    registro.value.atencion_id = data.atencion_id;
    registro.value.paciente_id = data.paciente_id;
    registro.value.num_docu = data.paciente.num_docu;
    registro.value.nom_pacien = data.paciente.nombres;
    registro.value.seg_nom = data.paciente.seg_nom;
    registro.value.pri_ape = data.paciente.pri_ape;
    registro.value.seg_ape = data.paciente.seg_ape;
    registro.value.sexo = data.paciente.sexo;
    registro.value.fec_naci = data.paciente.fec_naci;
    registro.value.direccion = data.paciente.direccion;
    registro.value.tel = data.paciente.tel;
    registro.value.cel = data.paciente.cel;
    registro.value.atencion_id = data.atencion_id;
    registro.value.contrato = data.contrato;
    registro.value.plan = data.plan;
    registro.value.admin_salud = data.admin_salud.nom_admin_salud;

    registro.value.lectura = data.lectura;
    registro.value.opinion = data.opinion;

    // Estado de envío a SAHI (si viene en el select)
    registro.value.estado_envio_sahi = data.estado_envio_sahi || null;
    registro.value.fechor_envio_sahi = data.fechor_envio_sahi || null;
    registro.value.error_envio_sahi = data.error_envio_sahi || null;
    registro.value.sahi_esquema_149 = data.sahi_esquema_149 || null;
    registro.value.sahi_esquema_150 = data.sahi_esquema_150 || null;

    if (sedeInterface && registro.value.estado_envio_sahi === "ENVIANDO") {
        startSahiPolling();
    } else {
        stopSahiPolling();
    }

    btnCerralModalForm();
}
function limpiarFormulario() {
    tituloModal.value = "";
    stopSahiPolling();

    registro.value.paciente_id = "";
    registro.value.num_docu = "";
    registro.value.nombres = "";
    registro.value.seg_nom = "";
    registro.value.pri_ape = "";
    registro.value.seg_ape = "";
    registro.value.sexo = "";
    registro.value.fec_naci = "";
    registro.value.direccion = "";
    registro.value.tel = "";
    registro.value.cel = "";
    registro.value.atencion_id = "";
    registro.value.contrato = "";
    registro.value.plan = "";
    registro.value.admin_salud = "";
    registro.value.quien_ordeno = "";
    registro.value.lectura = "";
    registro.value.opinion = "";
    errores.value = {};
}

onMounted(() => {
    establecerSede();
    listarTiposPlantillas();
    establecerMedico();
    rebuildMedicalVocabulary();

    emitter.on("sedeSeleccionada", (data) => {
        registro.value.sede_id = data.idSede;
        establecerSede();
    });

    emitter.on("medicoSeleccionado", (data) => {
        registro.value.medico_id = data.idMedico;
        establecerMedico();
        listarPendiestePorLectura();
    });

    url.value = window.location.pathname;
    establecerMedico();
});

onBeforeUnmount(() => {
    stopSahiPolling();
    try {
        stopVoice();
    } catch (e) {
        /* noop */
    }
});
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

<template>
    <div>
        <h4 class="card-title">Saludos del Bot</h4>
        <h6 class="card-subtitle">
            Mensajes de bienvenida personalizados por sede y servicio para el bot de WhatsApp.
        </h6>

        <div v-if="!sedeActual" class="alert alert-warning m-t-20">
            <i class="fa fa-exclamation-triangle"></i>
            Seleccione una sede en el menú superior para administrar sus saludos.
        </div>

        <div v-else-if="loadingSaludos" class="text-center m-t-40">
            <i class="fa fa-spinner fa-spin fa-2x"></i>
            <p class="text-muted m-t-10">Cargando saludos...</p>
        </div>

        <div v-else-if="errorSaludos" class="alert alert-danger m-t-20">
            <i class="fa fa-exclamation-triangle"></i>
            {{ errorSaludos }}
        </div>

        <div v-else-if="registrosSaludos.length === 0" class="text-center m-t-40">
            <p class="text-muted">
                No hay saludos configurados. Cree un nuevo registro para comenzar.
            </p>
        </div>

        <div class="table-responsive m-t-40" v-else>
            <table
                id="tblWhatsappSaludos"
                ref="dtSaludos"
                class="display nowrap table table-hover table-striped table-bordered"
                cellspacing="0"
                width="100%"
            >
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
                        <td>
                            {{ item.sede ? item.sede.nom_sede : "Todas las sedes" }}
                        </td>
                        <td>
                            {{ item.servicio ? item.servicio.nombre : "Todos los servicios" }}
                        </td>
                        <td class="text-wrap" style="max-width: 400px">
                            {{ item.mensaje_bienvenida.substring(0, 50)
                            }}{{ item.mensaje_bienvenida.length > 50 ? "..." : "" }}
                        </td>
                        <td>
                            <span
                                class="label"
                                :class="item.activo ? 'label-success' : 'label-danger'"
                            >
                                {{ item.activo ? "Activo" : "Inactivo" }}
                            </span>
                        </td>
                        <td class="text-nowrap">
                            <button
                                type="button"
                                class="btn waves-effect waves-light btn-rounded btn-outline-warning btn-sm m-r-5"
                                @click="editar(item)"
                            >
                                <i class="fa fa-pencil"></i>
                            </button>
                            <button
                                type="button"
                                class="btn waves-effect waves-light btn-rounded btn-outline-info btn-sm m-r-5"
                                @click="abrirProbarEnvio()"
                            >
                                <i class="fa fa-send"></i>
                            </button>
                            <button
                                type="button"
                                class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm"
                                @click="eliminarRegistro(item.id)"
                                :disabled="loadingEliminarId === item.id"
                            >
                                <i
                                    class="fa"
                                    :class="
                                        loadingEliminarId === item.id
                                            ? 'fa-spinner fa-spin'
                                            : 'fa-trash'
                                    "
                                ></i>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div
            class="right-sidebar"
            :class="{ 'shw-rside': sidebarVisible }"
            style="overflow-x: hidden; overflow-y: scroll; height: 100%"
        >
            <div class="slimscrollright">
                <div class="rpanel-title">
                    {{ tituloModal }}
                    <span>
                        <i class="ti-close right-side-toggle" @click="cerrarFormSidebar"></i>
                    </span>
                </div>
                <div class="r-panel-body">
                    <form action="#">
                        <div class="form-body">
                            <div class="row p-t-20">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label"
                                            >Sede <span class="text-danger">*</span></label
                                        >
                                        <input
                                            type="text"
                                            class="form-control"
                                            :value="nomSede || 'Sede seleccionada'"
                                            readonly
                                            disabled
                                        />
                                        <span class="text-danger" v-if="errores.sede_id">{{
                                            errores.sede_id[0]
                                        }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label"
                                            >Servicio
                                            <small class="text-muted">(opcional)</small>
                                        </label>
                                        <select
                                            v-model="form.servicio_id"
                                            class="form-control"
                                            :class="{ 'is-invalid': errores.servicio_id }"
                                        >
                                            <option value="">Todos los servicios</option>
                                            <option
                                                v-for="s in servicios"
                                                :key="s.id"
                                                :value="s.id"
                                            >
                                                {{
                                                    s.nombre +
                                                    (s.sede ? " — " + s.sede.nom_sede : "")
                                                }}
                                            </option>
                                        </select>
                                        <span class="text-danger" v-if="errores.servicio_id">{{
                                            errores.servicio_id[0]
                                        }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="control-label">
                                            Mensaje de bienvenida
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-info m-l-10"
                                                @click="mostrarPreviewSaludo"
                                                :disabled="!form.mensaje_bienvenida"
                                            >
                                                <i class="fa fa-eye"></i> Vista previa
                                            </button>
                                        </label>
                                        <div class="whatsapp-editor">
                                            <div class="whatsapp-toolbar">
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-light"
                                                    title="Negrita"
                                                    @click="insertFormat('*', '*')"
                                                >
                                                    <b>B</b>
                                                </button>
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-light"
                                                    title="Cursiva"
                                                    @click="insertFormat('_', '_')"
                                                >
                                                    <i>I</i>
                                                </button>
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-light"
                                                    title="Tachado"
                                                    @click="insertFormat('~', '~')"
                                                >
                                                    <s>S</s>
                                                </button>
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-light"
                                                    title="Código"
                                                    @click="insertFormat('```', '```')"
                                                >
                                                    <code>{"</code>
                                                </button>
                                                <span class="whatsapp-separator">|</span>
                                                <EmojiPicker @select="insertEmoji" />
                                            </div>
                                            <textarea
                                                ref="mensajeTextarea"
                                                v-model="form.mensaje_bienvenida"
                                                class="form-control"
                                                rows="18"
                                                :class="{
                                                    'is-invalid': errores.mensaje_bienvenida,
                                                }"
                                                placeholder="Escriba el mensaje de bienvenida que verá el paciente al iniciar el chat..."
                                            ></textarea>
                                        </div>
                                        <span
                                            class="text-danger"
                                            v-if="errores.mensaje_bienvenida"
                                            >{{ errores.mensaje_bienvenida[0] }}</span
                                        >
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <div class="checkbox checkbox-success">
                                            <input
                                                type="checkbox"
                                                id="form_activo_saludo"
                                                v-model="form.activo"
                                                :true-value="1"
                                                :false-value="0"
                                            />
                                            <label for="form_activo_saludo"> Activo </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-actions mt-3">
                                <button
                                    type="button"
                                    class="btn btn-success m-r-5"
                                    @click="guardarRegistro()"
                                    v-if="!actualizar"
                                    :disabled="loadingGuardar"
                                >
                                    <i
                                        class="fa"
                                        :class="loadingGuardar ? 'fa-spinner fa-spin' : 'fa-check'"
                                    ></i>
                                    Guardar
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-warning m-r-5"
                                    v-if="actualizar"
                                    @click="guardarRegistro()"
                                    :disabled="loadingGuardar"
                                >
                                    <i
                                        class="fa"
                                        :class="loadingGuardar ? 'fa-spinner fa-spin' : 'fa-pencil'"
                                    ></i>
                                    Actualizar
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-inverse"
                                    @click="cerrarFormSidebar()"
                                >
                                    Cancelar
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- Vista previa del saludo -->
        <div
            class="modal fade"
            id="modalPreviewSaludo"
            tabindex="-1"
            role="dialog"
            aria-hidden="true"
            v-if="modalPreviewVisible"
        >
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Vista previa del mensaje</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
                            &times;
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="card card-body bg-light">
                            <p style="white-space: pre-wrap">{{ previewMensaje }}</p>
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
                        <button type="button" class="btn btn-inverse" data-dismiss="modal">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal probar envío WhatsApp -->
        <div
            class="modal fade"
            id="modalProbarEnvio"
            tabindex="-1"
            role="dialog"
            aria-hidden="true"
            v-if="modalProbarVisible"
        >
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Probar envío de WhatsApp</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
                            &times;
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="control-label"
                                >Número de teléfono <span class="text-danger">*</span></label
                            >
                            <input
                                type="text"
                                v-model="testPhoneNumber"
                                class="form-control"
                                placeholder="573001234567"
                            />
                            <small class="text-muted"
                                >Ingrese el número con código de país (Ej: 573001234567)</small
                            >
                        </div>
                        <div v-if="testResultado" class="m-t-10">
                            <div
                                class="alert"
                                :class="testResultado.ok ? 'alert-success' : 'alert-danger'"
                            >
                                <i
                                    class="fa"
                                    :class="
                                        testResultado.ok
                                            ? 'fa-check-circle'
                                            : 'fa-exclamation-triangle'
                                    "
                                ></i>
                                {{ testResultado.mensaje }}
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-inverse"
                            data-dismiss="modal"
                            @click="testResultado = null"
                        >
                            Cerrar
                        </button>
                        <button
                            type="button"
                            class="btn btn-info"
                            @click="probarEnvio()"
                            :disabled="!testPhoneNumber || loadingTestEnvio"
                        >
                            <i
                                class="fa"
                                :class="loadingTestEnvio ? 'fa-spinner fa-spin' : 'fa-send'"
                            ></i>
                            {{ loadingTestEnvio ? "Enviando..." : "Enviar" }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from "vue";
import axios from "axios";

import { useConfigCrud } from "@/composables/useConfigCrud.js";
import { useConfigForm } from "@/composables/useConfigForm.js";
import { useDataTable } from "@/composables/useDataTable.js";
import { toastMessage } from "@/helpers/alerts.js";
import EmojiPicker from "@/components/shared/EmojiPicker.vue";

const props = defineProps({
    sedeId: {
        type: [Number, String],
        default: "",
    },
});

const sedeActual = computed(() => {
    return props.sedeId || sessionStorage.getItem("ST-sede") || "";
});
const nomSede = computed(() => sessionStorage.getItem("ST-nomsede") || "");
const servicios = ref([]);

const INIT_FORM_SALUDOS = {
    sede_id: "",
    servicio_id: "",
    mensaje_bienvenida: "",
    activo: 1,
};

const {
    form,
    errores,
    tituloModal,
    actualizar,
    id,
    setErrors,
    resetForm,
    prepararNuevo,
    prepararEdicion,
} = useConfigForm({ saludos: INIT_FORM_SALUDOS });
const { loadingGuardar, loadingEliminarId, guardar, eliminar } = useConfigCrud({
    resolveUrl: () => "/api/config-whatsapp",
    validate: validarSaludos,
});
const registrosSaludos = ref([]);
const loadingSaludos = ref(false);
const errorSaludos = ref("");
const dtSaludos = ref(null);
const dtSaludosApi = useDataTable(() => dtSaludos.value || "#tblWhatsappSaludos");
const sidebarVisible = ref(false);
const mensajeTextarea = ref(null);
const previewMensaje = ref("");
const testPhoneNumber = ref("");
const testResultado = ref(null);
const loadingTestEnvio = ref(false);
const modalPreviewVisible = ref(false);
const modalProbarVisible = ref(false);

function validarSaludos(payload) {
    const errs = {};
    if (!payload.sede_id) {
        errs.sede_id = ["La sede es obligatoria. Seleccione una sede en el menú superior."];
    }
    if (!payload.mensaje_bienvenida || !String(payload.mensaje_bienvenida).trim()) {
        errs.mensaje_bienvenida = ["El mensaje de bienvenida es obligatorio."];
    }
    return errs;
}
async function cargarServicios() {
    if (!sedeActual.value) {
        servicios.value = [];
        return;
    }
    try {
        const res = await axios.get("/api/config-servicio-citas", {
            params: { sede_id: sedeActual.value },
        });
        servicios.value = res?.data?.data || res?.data || [];
    } catch {
        servicios.value = [];
    }
}
async function listarSaludos() {
    if (loadingSaludos.value) return;
    if (!sedeActual.value) {
        registrosSaludos.value = [];
        return;
    }
    loadingSaludos.value = true;
    errorSaludos.value = "";
    try {
        const res = await axios.get("/api/config-whatsapp", {
            params: { sede_id: sedeActual.value },
        });
        registrosSaludos.value = res?.data?.data || res?.data || [];
        dtSaludosApi.refresh(true);
    } catch (error) {
        errorSaludos.value = error?.response?.data?.message || "No fue posible cargar los saludos.";
        registrosSaludos.value = [];
    } finally {
        loadingSaludos.value = false;
    }
}
function recargar() {
    cargarServicios();
    listarSaludos();
}
async function guardarRegistro() {
    if (loadingGuardar.value) return;
    setErrors({});
    const payload = {
        ...form.value,
        activo: form.value.activo === 1 || form.value.activo === true,
    };
    const result = await guardar(payload, { actualizar: actualizar.value, id: id.value });
    if (result.errores) {
        setErrors(result.errores);
        return;
    }
    if (result.ok) {
        listarSaludos();
        cerrarFormSidebar();
        limpiar();
    }
}
async function eliminarRegistro(registroId) {
    const deleted = await eliminar(registroId);
    if (deleted) {
        listarSaludos();
    }
}
function mostrarRegistro(data = {}) {
    if (actualizar.value) {
        prepararEdicion(
            "Actualizar saludo: " + (data.sede?.nom_sede || "Todas las sedes"),
            data,
            (d) => ({
                sede_id: d.sede_id || sedeActual.value || "",
                servicio_id: d.servicio_id || "",
                mensaje_bienvenida: d.mensaje_bienvenida || "",
                activo: d.activo ? 1 : 0,
            })
        );
    } else {
        prepararNuevo("saludos", "Nuevo saludo");
        form.value.sede_id = sedeActual.value || "";
    }
    abrirFormSidebar();
}
function editar(item) {
    actualizar.value = true;
    mostrarRegistro(item);
}
function nuevo() {
    if (!sedeActual.value) {
        toastMessage({
            heading: "Seleccione una sede",
            text: "Primero seleccione una sede en el menú superior.",
            icon: "warning",
        });
        return;
    }
    actualizar.value = false;
    mostrarRegistro();
}
function limpiar() {
    resetForm("saludos");
}
function abrirFormSidebar() {
    sidebarVisible.value = true;
}
function cerrarFormSidebar() {
    sidebarVisible.value = false;
}
function abrirProbarEnvio() {
    testPhoneNumber.value = "";
    testResultado.value = null;
    modalProbarVisible.value = true;
    nextTick(() => {
        const el = document.getElementById("modalProbarEnvio");
        if (el) {
            $(el).modal("show");
        }
    });
}
function mostrarPreviewSaludo() {
    if (!form.value.mensaje_bienvenida) return;
    previewMensaje.value = form.value.mensaje_bienvenida
        .replace(/\$\{num_docu_paciente\}/g, "1234567890")
        .replace(/\$\{nom_paciente\}/g, "Paciente")
        .replace(/\$\{cod_cups\}/g, "CUPS001")
        .replace(/\$\{nom_produc\}/g, "Nombre del producto");
    modalPreviewVisible.value = true;
    nextTick(() => {
        const el = document.getElementById("modalPreviewSaludo");
        if (el) {
            $(el).modal("show");
        }
    });
}
function insertFormat(before, after) {
    const ta = mensajeTextarea.value;
    if (!ta) return;
    const start = ta.selectionStart;
    const end = ta.selectionEnd;
    const text = form.value.mensaje_bienvenida || "";
    const selected = text.substring(start, end);
    const replacement = before + selected + after;
    form.value.mensaje_bienvenida = text.substring(0, start) + replacement + text.substring(end);
    nextTick(() => {
        ta.focus();
        ta.selectionStart = start + before.length;
        ta.selectionEnd = start + before.length + selected.length;
    });
}
function insertEmoji(emoji) {
    const ta = mensajeTextarea.value;
    if (!ta) return;
    const start = ta.selectionStart;
    const end = ta.selectionEnd;
    const text = form.value.mensaje_bienvenida || "";
    form.value.mensaje_bienvenida = text.substring(0, start) + emoji + text.substring(end);
    nextTick(() => {
        ta.focus();
        const pos = start + emoji.length;
        ta.selectionStart = pos;
        ta.selectionEnd = pos;
    });
}
async function probarEnvio() {
    if (!testPhoneNumber.value || loadingTestEnvio.value) return;
    loadingTestEnvio.value = true;
    testResultado.value = null;
    try {
        const res = await axios.post("/api/citas/test/enviar", {
            phone: testPhoneNumber.value,
        });
        testResultado.value = {
            ok: true,
            mensaje: res.data?.message || "Mensaje enviado correctamente.",
        };
    } catch (error) {
        const msg =
            error?.response?.data?.message || error.message || "Error al enviar el mensaje.";
        testResultado.value = {
            ok: false,
            mensaje: msg,
        };
    } finally {
        loadingTestEnvio.value = false;
    }
}

defineExpose({ nuevo, listar: recargar });

watch(
    () => props.sedeId,
    () => {
        recargar();
    }
);

onMounted(() => {
    recargar();

    $(document).on("hidden.bs.modal", "#modalPreviewSaludo", function () {
        modalPreviewVisible.value = false;
    });
    $(document).on("hidden.bs.modal", "#modalProbarEnvio", function () {
        modalProbarVisible.value = false;
    });
});

onBeforeUnmount(() => {
    $(document).off("hidden.bs.modal", "#modalPreviewSaludo");
    $(document).off("hidden.bs.modal", "#modalProbarEnvio");
    $("#modalPreviewSaludo").modal("hide");
    $("#modalProbarEnvio").modal("hide");
    $(".modal-backdrop").remove();
    $("body").removeClass("modal-open").css("padding-right", "");
    dtSaludosApi.destroy();
});
</script>
<style scoped>
.right-sidebar {
    position: fixed;
    right: -70%;
    width: 70%;
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
    width: 70%;
    display: block;
    overflow-y: auto;
}

.whatsapp-editor {
    position: relative;
}

.whatsapp-toolbar {
    display: flex;
    align-items: center;
    gap: 2px;
    padding: 6px 8px;
    border: 1px solid #ced4da;
    border-bottom: none;
    border-radius: 4px 4px 0 0;
    background: #f8f9fa;
    flex-wrap: wrap;
    position: relative;
}

.whatsapp-toolbar .btn-light {
    border: 1px solid transparent;
    padding: 2px 10px;
    font-size: 14px;
    line-height: 1.5;
    min-width: 32px;
}

.whatsapp-toolbar .btn-light:hover {
    border-color: #ced4da;
    background: #e9ecef;
}

.whatsapp-toolbar .btn-light b {
    font-weight: 700;
}

.whatsapp-separator {
    color: #ced4da;
    padding: 0 4px;
    user-select: none;
}

.whatsapp-editor textarea {
    border-top-left-radius: 0;
    border-top-right-radius: 0;
}
</style>

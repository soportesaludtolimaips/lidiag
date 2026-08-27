<template>
    <div class="row g-3">
        <!-- Buscar paciente -->
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Generar consentimiento</h5>

                    <div class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">Número de documento</label>
                            <input
                                v-model.trim="numDocu"
                                type="text"
                                class="form-control"
                                placeholder="Documento del paciente"
                                @keyup.enter="buscar"
                            />
                        </div>
                        <div class="col-md-2">
                            <button
                                class="btn btn-primary w-100"
                                type="button"
                                :disabled="buscando || !numDocu"
                                @click="buscar"
                            >
                                <span v-if="buscando">Buscando…</span>
                                <span v-else>Buscar</span>
                            </button>
                        </div>
                    </div>

                    <div v-if="paciente" class="alert alert-light border mt-3 mb-0">
                        <strong>{{ paciente.nombres }} {{ paciente.apellidos }}</strong>
                        · CC {{ paciente.num_docu }}
                        <span v-if="paciente.sexo"> · {{ paciente.sexo }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formulario de generación -->
        <div v-if="paciente" class="col-12">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Datos del consentimiento</h6>

                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label">Nombres</label>
                            <input v-model.trim="form.nombres" type="text" class="form-control" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Apellidos</label>
                            <input v-model.trim="form.apellidos" type="text" class="form-control" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Producto</label>
                            <select v-model="form.producto_id" class="form-select">
                                <option :value="null" disabled>Seleccione…</option>
                                <option v-for="p in productos" :key="p.id" :value="p.id">
                                    {{ p.nom_produc }}
                                </option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Sede</label>
                            <select v-model="form.sede_id" class="form-select">
                                <option :value="null">— (sin sede) —</option>
                                <option v-for="s in sedes" :key="s.id" :value="s.id">
                                    {{ s.nom_sede }}
                                </option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Médico (firma)</label>
                            <select v-model="form.medico_id" class="form-select">
                                <option :value="null">— (sin asignar) —</option>
                                <option v-for="m in medicos" :key="m.id" :value="m.id">
                                    {{ m.name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <button
                        class="btn btn-success mt-3"
                        type="button"
                        :disabled="generando || !form.producto_id || !can('consentimientos.generar')"
                        @click="generar"
                    >
                        <span v-if="generando">Generando…</span>
                        <span v-else>Generar consentimiento</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Documento generado: token + adjuntos -->
        <div v-if="documento" class="col-12">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">
                        Consentimiento #{{ documento.consentimiento_id }}
                        <span class="badge bg-warning text-dark">{{ documento.estado }}</span>
                    </h6>
                    <p class="text-muted mb-3" v-if="documento.fecha_expiracion">
                        Expira: {{ formatFecha(documento.fecha_expiracion) }}
                    </p>

                    <!-- Generar enlace de firma -->
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">Firma</label>
                            <select v-model="firmanteTipo" class="form-select">
                                <option value="PACIENTE">Paciente</option>
                                <option value="ACOMPANANTE">Acompañante/Acudiente</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button
                                class="btn btn-primary w-100"
                                type="button"
                                :disabled="creandoToken"
                                @click="crearToken"
                            >
                                <span v-if="creandoToken">Generando…</span>
                                <span v-else>Generar enlace de firma</span>
                            </button>
                        </div>
                    </div>

                    <div v-if="firmanteTipo === 'ACOMPANANTE'" class="row g-2 mt-1">
                        <div class="col-md-3">
                            <input v-model.trim="acompanante.cedula" class="form-control" placeholder="Cédula acompañante" />
                        </div>
                        <div class="col-md-3">
                            <input v-model.trim="acompanante.nombres" class="form-control" placeholder="Nombres acompañante" />
                        </div>
                        <div class="col-md-3">
                            <input v-model.trim="acompanante.telefono" class="form-control" placeholder="Teléfono" />
                        </div>
                    </div>

                    <!-- Enlace + QR -->
                    <div v-if="tokenUrl" class="mt-3">
                        <div class="input-group mb-2">
                            <input :value="tokenUrl" type="text" class="form-control" readonly />
                            <button class="btn btn-outline-secondary" type="button" @click="copiar">
                                Copiar
                            </button>
                        </div>
                        <img v-if="qrDataUrl" :src="qrDataUrl" alt="QR firma" width="180" height="180" />
                    </div>

                    <hr />

                    <!-- Marcar zonas en imágenes de la plantilla -->
                    <consentimiento-marcas-canvas :documento-id="documento.consentimiento_id" />

                    <!-- Adjuntos -->
                    <h6>Adjuntos</h6>
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <input ref="fileInput" type="file" class="form-control" @change="onFile" />
                        </div>
                        <div class="col-md-3">
                            <select v-model="categoria" class="form-select">
                                <option value="documento_identidad">Documento identidad</option>
                                <option value="orden_medica">Orden médica</option>
                                <option value="historia_clinica">Historia clínica</option>
                                <option value="laboratorio">Laboratorio</option>
                                <option value="imagenes">Imágenes</option>
                                <option value="otro">Otro</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button
                                class="btn btn-primary w-100"
                                type="button"
                                :disabled="subiendo || !archivo"
                                @click="subirAdjunto"
                            >
                                <span v-if="subiendo">Subiendo…</span>
                                <span v-else>Subir</span>
                            </button>
                        </div>
                    </div>

                    <ul class="list-group mt-2">
                        <li
                            v-for="a in adjuntos"
                            :key="a.id"
                            class="list-group-item d-flex justify-content-between align-items-center"
                        >
                            <span>{{ a.nombre_original }} <small class="text-muted">({{ a.categoria }})</small></span>
                            <span class="d-flex gap-2">
                                <a class="btn btn-sm btn-outline-secondary" :href="descargarAdjuntoUrl(a.id)">
                                    Descargar
                                </a>
                                <button class="btn btn-sm btn-outline-danger" type="button" @click="eliminarAdjunto(a.id)">
                                    Eliminar
                                </button>
                            </span>
                        </li>
                        <li v-if="!adjuntos.length" class="list-group-item text-muted">Sin adjuntos</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, reactive, ref } from "vue";

import QRCode from "qrcode";

import { consentimientos as api } from "../../../helpers/api.js";
import usePermissions from "../../../helpers/permissions.js";
import {
    alertError,
    alertSuccess,
    confirmAction,
    handleAxiosErrorToast,
} from "../../../helpers/alerts.js";

const props = defineProps({
    permisos: { type: Array, default: () => [] },
});

const { can } = usePermissions(props.permisos);

const numDocu = ref("");
const buscando = ref(false);
const paciente = ref(null);

const productos = ref([]);
const sedes = ref([]);
const medicos = ref([]);
const form = reactive({ nombres: "", apellidos: "", producto_id: null, sede_id: null, medico_id: null });
const generando = ref(false);

const documento = ref(null);

const firmanteTipo = ref("PACIENTE");
const acompanante = reactive({ cedula: "", nombres: "", telefono: "" });
const creandoToken = ref(false);
const tokenUrl = ref("");
const qrDataUrl = ref("");

const fileInput = ref(null);
const archivo = ref(null);
const categoria = ref("orden_medica");
const subiendo = ref(false);
const adjuntos = ref([]);

const descargarAdjuntoUrl = api.descargarAdjuntoUrl;

function formatFecha(f) {
    return f ? new Date(f).toLocaleString() : "";
}

async function cargarCombos() {
    try {
        const [rp, rs, rm] = await Promise.all([api.productos(), api.sedesActivas(), api.medicos()]);
        productos.value = rp.data?.data ?? rp.data ?? [];
        sedes.value = rs.data?.data ?? rs.data ?? [];
        medicos.value = rm.data?.data ?? rm.data ?? [];
    } catch (e) {
        handleAxiosErrorToast(e);
    }
}

async function buscar() {
    if (!numDocu.value) return;
    buscando.value = true;
    try {
        const res = await api.buscarPaciente(numDocu.value);
        paciente.value = res.data.data;
        form.nombres = paciente.value.nombres || "";
        form.apellidos = paciente.value.apellidos || "";
        documento.value = null;
        tokenUrl.value = "";
        qrDataUrl.value = "";
    } catch (e) {
        paciente.value = null;
        if (e?.response?.status === 404) {
            alertError("Paciente no encontrado. Puede capturar los datos manualmente.");
            paciente.value = { num_docu: numDocu.value, nombres: "", apellidos: "" };
            form.nombres = "";
            form.apellidos = "";
        } else {
            handleAxiosErrorToast(e);
        }
    } finally {
        buscando.value = false;
    }
}

async function generar() {
    if (!form.producto_id) return;
    generando.value = true;
    try {
        const res = await api.generar({
            producto_id: form.producto_id,
            sede_id: form.sede_id,
            medico_id: form.medico_id,
            origen_tipo: "MANUAL",
            num_docu: paciente.value.num_docu,
            nombres: form.nombres,
            apellidos: form.apellidos,
        });
        documento.value = res.data.data;
        adjuntos.value = [];
        alertSuccess("Consentimiento generado");
    } catch (e) {
        handleAxiosErrorToast(e);
    } finally {
        generando.value = false;
    }
}

async function crearToken() {
    creandoToken.value = true;
    try {
        const payload = {
            documento_id: documento.value.consentimiento_id,
            firmante_tipo: firmanteTipo.value,
        };
        if (firmanteTipo.value === "ACOMPANANTE") {
            payload.acompanante = { ...acompanante };
        }
        const res = await api.crearToken(payload);
        tokenUrl.value = res.data.data.url;
        qrDataUrl.value = await QRCode.toDataURL(tokenUrl.value, { width: 180 });
        documento.value.estado = "PENDIENTE_PACIENTE";
        alertSuccess("Enlace de firma generado");
    } catch (e) {
        handleAxiosErrorToast(e);
    } finally {
        creandoToken.value = false;
    }
}

async function copiar() {
    try {
        await navigator.clipboard.writeText(tokenUrl.value);
        alertSuccess("Enlace copiado");
    } catch {
        alertError("No fue posible copiar");
    }
}

function onFile(e) {
    archivo.value = e.target.files?.[0] ?? null;
}

async function cargarAdjuntos() {
    try {
        const res = await api.adjuntos(documento.value.consentimiento_id);
        adjuntos.value = res.data?.data ?? [];
    } catch (e) {
        handleAxiosErrorToast(e);
    }
}

async function subirAdjunto() {
    if (!archivo.value) return;
    subiendo.value = true;
    try {
        const fd = new FormData();
        fd.append("archivo", archivo.value);
        fd.append("categoria", categoria.value);
        await api.subirAdjunto(documento.value.consentimiento_id, fd);
        archivo.value = null;
        if (fileInput.value) fileInput.value.value = "";
        await cargarAdjuntos();
        alertSuccess("Adjunto subido");
    } catch (e) {
        handleAxiosErrorToast(e);
    } finally {
        subiendo.value = false;
    }
}

async function eliminarAdjunto(id) {
    const ok = await confirmAction({ title: "Eliminar adjunto", text: "¿Eliminar este adjunto?" });
    if (!ok) return;
    try {
        await api.eliminarAdjunto(id);
        await cargarAdjuntos();
        alertSuccess("Adjunto eliminado");
    } catch (e) {
        handleAxiosErrorToast(e);
    }
}

onMounted(cargarCombos);
</script>

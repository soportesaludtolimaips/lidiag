<template>
    <div class="row g-3">
        <!-- Asignación de plantilla a productos/sedes -->
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Asignación de plantillas a productos</h5>

                    <div class="row g-2 align-items-end mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Módulo</label>
                            <select v-model="moduloId" class="form-select" @change="listarPlantillas">
                                <option :value="''" disabled>Seleccione…</option>
                                <option v-for="m in modulos" :key="m.id" :value="m.id">
                                    {{ m.nom_modulo || m.nombre || ("Módulo " + m.id) }}
                                </option>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Plantilla</label>
                            <select v-model="plantillaId" class="form-select" @change="listarAsignaciones">
                                <option :value="null" disabled>Seleccione…</option>
                                <option v-for="p in plantillas" :key="p.id" :value="p.id">
                                    {{ p.nombre }} (v{{ p.version }})
                                </option>
                            </select>
                        </div>
                    </div>

                    <template v-if="plantillaId">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label">Producto</label>
                                <select v-model="nuevaAsignacion.producto_id" class="form-select">
                                    <option :value="null" disabled>Seleccione…</option>
                                    <option v-for="p in productos" :key="p.id" :value="p.id">
                                        {{ p.nom_produc }}
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Sede</label>
                                <select v-model="nuevaAsignacion.sede_id" class="form-select">
                                    <option :value="null">— Todas —</option>
                                    <option v-for="s in sedes" :key="s.id" :value="s.id">
                                        {{ s.nom_sede }}
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check mt-4">
                                    <input
                                        id="rfm"
                                        v-model="nuevaAsignacion.requiere_firma_medico"
                                        class="form-check-input"
                                        type="checkbox"
                                    />
                                    <label class="form-check-label" for="rfm">Requiere firma médico</label>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <button
                                    class="btn btn-primary w-100"
                                    type="button"
                                    :disabled="asignando || !nuevaAsignacion.producto_id || !can('consentimientos.configurar')"
                                    @click="asignar"
                                >
                                    Asignar
                                </button>
                            </div>
                        </div>

                        <table class="table table-sm mt-3">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Sede</th>
                                    <th>Firma médico</th>
                                    <th class="text-end">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="a in asignaciones" :key="a.id">
                                    <td>{{ a.producto?.nom_produc }}</td>
                                    <td>{{ a.sede?.nom_sede || "Todas" }}</td>
                                    <td>{{ a.requiere_firma_medico ? "Sí" : "No" }}</td>
                                    <td class="text-end">
                                        <button
                                            class="btn btn-sm btn-outline-danger"
                                            type="button"
                                            @click="desasignar(a.id)"
                                        >
                                            Quitar
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="!asignaciones.length">
                                    <td colspan="4" class="text-muted text-center">Sin asignaciones</td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- Imágenes de la plantilla (decorativas o marcables) -->
                        <hr />
                        <h6>Imágenes {{ "" }}<small class="text-muted">(JPG/PNG, máx 5MB)</small></h6>
                        <div class="row g-2 align-items-end">
                            <div class="col-md-4">
                                <input ref="imgInput" type="file" accept="image/jpeg,image/png" class="form-control" @change="onImg" />
                            </div>
                            <div class="col-md-3">
                                <input v-model.trim="nuevaImagen.nombre" class="form-control" placeholder="Nombre/descripción" />
                            </div>
                            <div class="col-md-3">
                                <div class="form-check mt-2">
                                    <input id="epm" v-model="nuevaImagen.es_para_marcar" class="form-check-input" type="checkbox" />
                                    <label class="form-check-label" for="epm">Es para marcar (zonas)</label>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <button
                                    class="btn btn-primary w-100"
                                    type="button"
                                    :disabled="subiendoImg || !imgFile || !can('consentimientos.configurar')"
                                    @click="subirImagen"
                                >
                                    Subir
                                </button>
                            </div>
                        </div>

                        <div class="row g-2 mt-2">
                            <div v-for="img in imagenes" :key="img.id" class="col-md-3">
                                <div class="card">
                                    <img :src="verImagenUrl(img.id)" class="card-img-top" style="max-height:140px;object-fit:contain;" :alt="img.nombre" />
                                    <div class="card-body p-2">
                                        <small class="d-block text-truncate">{{ img.nombre }}</small>
                                        <span v-if="img.es_para_marcar" class="badge bg-info">Marcable</span>
                                        <button class="btn btn-sm btn-outline-danger float-end" type="button" @click="eliminarImagen(img.id)">
                                            Quitar
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div v-if="!imagenes.length" class="col-12 text-muted">Sin imágenes</div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Comparar versiones -->
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Comparar versiones</h5>

                    <div class="row g-2 align-items-end mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Producto</label>
                            <select v-model="cmpProductoId" class="form-select" @change="cargarVersiones">
                                <option :value="null" disabled>Seleccione…</option>
                                <option v-for="p in productos" :key="p.id" :value="p.id">
                                    {{ p.nom_produc }}
                                </option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Versión A</label>
                            <select v-model="v1" class="form-select">
                                <option :value="null" disabled>—</option>
                                <option v-for="v in versiones" :key="v.id" :value="v.id">v{{ v.version }}</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Versión B</label>
                            <select v-model="v2" class="form-select">
                                <option :value="null" disabled>—</option>
                                <option v-for="v in versiones" :key="v.id" :value="v.id">v{{ v.version }}</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button
                                class="btn btn-primary w-100"
                                type="button"
                                :disabled="comparando || !v1 || !v2"
                                @click="comparar"
                            >
                                Comparar
                            </button>
                        </div>
                    </div>

                    <div v-if="diff.length" class="border p-2 diff-box">
                        <div
                            v-for="(seg, i) in diff"
                            :key="i"
                            :class="['diff-line', 'diff-' + seg.tipo]"
                        >
                            <span class="diff-mark">{{ diffMark(seg.tipo) }}</span>
                            {{ seg.texto }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, reactive, ref } from "vue";

import { consentimientos as api } from "../../../helpers/api.js";
import usePermissions from "../../../helpers/permissions.js";
import { alertSuccess, confirmAction, handleAxiosErrorToast } from "../../../helpers/alerts.js";

const props = defineProps({
    permisos: { type: Array, default: () => [] },
});

const { can } = usePermissions(props.permisos);

const modulos = ref([]);
const productos = ref([]);
const sedes = ref([]);

const moduloId = ref("");
const plantillas = ref([]);
const plantillaId = ref(null);
const asignaciones = ref([]);
const nuevaAsignacion = reactive({ producto_id: null, sede_id: null, requiere_firma_medico: true });
const asignando = ref(false);

const imagenes = ref([]);
const imgInput = ref(null);
const imgFile = ref(null);
const nuevaImagen = reactive({ nombre: "", es_para_marcar: false });
const subiendoImg = ref(false);
const verImagenUrl = api.verImagenUrl;

const cmpProductoId = ref(null);
const versiones = ref([]);
const v1 = ref(null);
const v2 = ref(null);
const comparando = ref(false);
const diff = ref([]);

function diffMark(tipo) {
    return tipo === "add" ? "+" : tipo === "del" ? "−" : " ";
}

async function cargarCombos() {
    try {
        const [rm, rp, rs] = await Promise.all([api.modulos(), api.productos(), api.sedesActivas()]);
        modulos.value = rm.data?.data ?? rm.data ?? [];
        productos.value = rp.data?.data ?? rp.data ?? [];
        sedes.value = rs.data?.data ?? rs.data ?? [];
    } catch (e) {
        handleAxiosErrorToast(e, "No fue posible cargar catálogos.");
    }
}

async function listarPlantillas() {
    plantillaId.value = null;
    asignaciones.value = [];
    if (!moduloId.value) return;
    try {
        const res = await api.plantillas({ config_modulo_id: moduloId.value, vigentes: "0" });
        plantillas.value = res.data?.data ?? [];
    } catch (e) {
        handleAxiosErrorToast(e, "No fue posible listar plantillas.");
    }
}

async function listarAsignaciones() {
    if (!plantillaId.value) return;
    try {
        const res = await api.asignaciones(plantillaId.value);
        asignaciones.value = res.data?.data ?? [];
    } catch (e) {
        handleAxiosErrorToast(e, "No fue posible listar asignaciones.");
    }
    await listarImagenes();
}

async function listarImagenes() {
    if (!plantillaId.value) {
        imagenes.value = [];
        return;
    }
    try {
        const res = await api.imagenes(plantillaId.value);
        imagenes.value = res.data?.data ?? [];
    } catch (e) {
        handleAxiosErrorToast(e, "No fue posible listar imágenes.");
    }
}

function onImg(e) {
    imgFile.value = e.target.files?.[0] ?? null;
}

async function subirImagen() {
    if (!imgFile.value) return;
    subiendoImg.value = true;
    try {
        const fd = new FormData();
        fd.append("imagen", imgFile.value);
        fd.append("nombre", nuevaImagen.nombre || imgFile.value.name);
        fd.append("es_para_marcar", nuevaImagen.es_para_marcar ? "1" : "0");
        await api.subirImagen(plantillaId.value, fd);
        imgFile.value = null;
        nuevaImagen.nombre = "";
        nuevaImagen.es_para_marcar = false;
        if (imgInput.value) imgInput.value.value = "";
        await listarImagenes();
        alertSuccess("Imagen subida");
    } catch (e) {
        handleAxiosErrorToast(e, "No fue posible subir la imagen.");
    } finally {
        subiendoImg.value = false;
    }
}

async function eliminarImagen(id) {
    const ok = await confirmAction({ title: "Quitar imagen", text: "¿Eliminar esta imagen?" });
    if (!ok) return;
    try {
        await api.eliminarImagen(id);
        await listarImagenes();
        alertSuccess("Imagen eliminada");
    } catch (e) {
        handleAxiosErrorToast(e, "No fue posible eliminar la imagen.");
    }
}

async function asignar() {
    if (!nuevaAsignacion.producto_id) return;
    asignando.value = true;
    try {
        await api.asignar(plantillaId.value, { ...nuevaAsignacion });
        nuevaAsignacion.producto_id = null;
        nuevaAsignacion.sede_id = null;
        nuevaAsignacion.requiere_firma_medico = true;
        await listarAsignaciones();
        alertSuccess("Asignación creada");
    } catch (e) {
        handleAxiosErrorToast(e, "No fue posible asignar.");
    } finally {
        asignando.value = false;
    }
}

async function desasignar(id) {
    const ok = await confirmAction({ title: "Quitar asignación", text: "¿Quitar este producto/sede?" });
    if (!ok) return;
    try {
        await api.desasignar(id);
        await listarAsignaciones();
        alertSuccess("Asignación eliminada");
    } catch (e) {
        handleAxiosErrorToast(e, "No fue posible quitar la asignación.");
    }
}

async function cargarVersiones() {
    versiones.value = [];
    v1.value = null;
    v2.value = null;
    diff.value = [];
    if (!cmpProductoId.value || !moduloId.value) {
        if (!moduloId.value) alertSuccess("Seleccione un módulo arriba primero");
        return;
    }
    try {
        const res = await api.plantillaVersiones(cmpProductoId.value, {
            config_modulo_id: moduloId.value,
        });
        versiones.value = res.data?.data ?? [];
    } catch (e) {
        handleAxiosErrorToast(e, "No fue posible cargar versiones.");
    }
}

async function comparar() {
    if (!v1.value || !v2.value) return;
    comparando.value = true;
    try {
        const res = await api.plantillaComparar(v1.value, v2.value);
        diff.value = res.data?.data?.diff ?? [];
    } catch (e) {
        handleAxiosErrorToast(e, "No fue posible comparar.");
    } finally {
        comparando.value = false;
    }
}

onMounted(cargarCombos);
</script>

<style scoped>
.diff-box {
    max-height: 50vh;
    overflow: auto;
    font-family: monospace;
    font-size: 12px;
    white-space: pre-wrap;
}
.diff-line {
    padding: 0 4px;
}
.diff-add {
    background: #e6ffed;
}
.diff-del {
    background: #ffeef0;
}
.diff-mark {
    display: inline-block;
    width: 1em;
    color: #666;
}
</style>

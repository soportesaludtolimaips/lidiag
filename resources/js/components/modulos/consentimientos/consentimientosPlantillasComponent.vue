<template>
    <div class="ci-plantillas row g-3">
        <!-- Encabezado de página -->
        <div class="col-12">
            <div class="ci-header">
                <div class="ci-header-icon"><i class="fa fa-file-text-o"></i></div>
                <div class="me-auto">
                    <h4 class="mb-0 fw-bold">Plantillas de consentimiento</h4>
                    <small class="text-muted">
                        Administra las plantillas, su formato de encabezado y la asignación por producto/sede.
                    </small>
                </div>
                <div class="ci-header-stat">
                    <span class="ci-header-num">{{ plantillasFiltradas.length }}</span>
                    <span class="ci-header-lbl">visibles</span>
                </div>
            </div>
        </div>

        <!-- Listado de plantillas -->
        <div class="col-12">
            <div class="card ci-card shadow-sm">
                <div class="card-body">
                    <div class="row g-2 align-items-end mb-3">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Módulo</label>
                            <select v-model="moduloId" class="form-select" @change="listarPlantillas">
                                <option v-for="m in modulos" :key="m.id" :value="m.id">
                                    {{ m.modulo || m.nom_modulo || "Módulo " + m.id }}
                                </option>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-semibold">Buscar</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="fa fa-search"></i></span>
                                <input
                                    v-model.trim="busqueda"
                                    class="form-control"
                                    type="search"
                                    placeholder="Nombre o código…"
                                />
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check mt-4">
                                <input
                                    id="soloVig"
                                    v-model="soloVigentes"
                                    class="form-check-input"
                                    type="checkbox"
                                    @change="listarPlantillas"
                                />
                                <label class="form-check-label" for="soloVig">Solo vigentes</label>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle ci-table mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 40%">Plantilla</th>
                                    <th class="text-center">Versión</th>
                                    <th>Código formato</th>
                                    <th class="text-center">Año</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-end">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="p in plantillasFiltradas"
                                    :key="p.id"
                                    :class="{ 'table-active': Number(plantillaId) === Number(p.id) }"
                                >
                                    <td class="fw-semibold">{{ p.nombre }}</td>
                                    <td class="text-center">v{{ p.version }}</td>
                                    <td>
                                        <span v-if="p.codigo" class="text-muted">{{ p.codigo }}</span>
                                        <span v-else class="text-muted fst-italic">—</span>
                                    </td>
                                    <td class="text-center">{{ p.anio_formato || "—" }}</td>
                                    <td class="text-center">
                                        <span
                                            v-if="p.vigente"
                                            class="badge bg-success-subtle text-success rounded-pill"
                                            >Vigente</span
                                        >
                                        <span v-else class="badge bg-secondary-subtle text-secondary rounded-pill"
                                            >Histórico</span
                                        >
                                    </td>
                                    <td class="text-end">
                                        <button
                                            class="btn btn-sm"
                                            :class="
                                                Number(plantillaId) === Number(p.id)
                                                    ? 'btn-primary'
                                                    : 'btn-outline-primary'
                                            "
                                            type="button"
                                            @click="seleccionarPlantilla(p.id)"
                                        >
                                            <i class="fa fa-cog me-1"></i>Gestionar
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="!plantillasFiltradas.length">
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="fa fa-inbox fa-2x d-block mb-2 opacity-50"></i>
                                        No hay plantillas para mostrar en este módulo.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detalle / gestión de la plantilla seleccionada -->
        <div v-if="plantillaId" class="col-12">
            <div class="card shadow-sm border-primary-subtle">
                <div class="card-header bg-primary-subtle">
                    <h6 class="mb-0 text-primary fw-bold">
                        <i class="fa fa-sliders me-1"></i>
                        Gestionar: {{ plantillaSeleccionada?.nombre }}
                    </h6>
                </div>
                <div class="card-body">
                    <!-- Datos del formato (encabezado del PDF) -->
                    <h6 class="ci-section">Datos del formato (encabezado del PDF)</h6>
                    <div class="row g-2 align-items-end mb-3 pb-3 border-bottom">
                        <div class="col-md-3">
                            <label class="form-label">Código del formato</label>
                            <input v-model.trim="formato.codigo" class="form-control" placeholder="Ej: MDTP28.F01" />
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Versión</label>
                            <input v-model.trim="formato.version_formato" class="form-control" placeholder="Ej: 05" />
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Año</label>
                            <input v-model.trim="formato.anio_formato" class="form-control" placeholder="Ej: 2025" />
                        </div>
                        <div class="col-md-3">
                            <button
                                class="btn btn-outline-primary w-100"
                                type="button"
                                :disabled="guardandoFormato || !can('consentimientos.configurar')"
                                @click="guardarFormato"
                            >
                                <i class="fa fa-save me-1"></i>Guardar formato
                            </button>
                        </div>
                    </div>

                    <!-- Asignación a productos/sedes -->
                    <h6 class="ci-section">Asignación a productos / sedes</h6>
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
                                :disabled="
                                    asignando || !nuevaAsignacion.producto_id || !can('consentimientos.configurar')
                                "
                                @click="asignar"
                            >
                                <i class="fa fa-plus me-1"></i>Asignar
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive mt-3">
                        <table class="table table-sm ci-table mb-0">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Sede</th>
                                    <th class="text-center">Firma médico</th>
                                    <th class="text-end">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="a in asignaciones" :key="a.id">
                                    <td>{{ a.producto?.nom_produc }}</td>
                                    <td>{{ a.sede?.nom_sede || "Todas" }}</td>
                                    <td class="text-center">
                                        <span
                                            class="badge rounded-pill"
                                            :class="
                                                a.requiere_firma_medico
                                                    ? 'bg-info-subtle text-info'
                                                    : 'bg-secondary-subtle text-secondary'
                                            "
                                            >{{ a.requiere_firma_medico ? "Sí" : "No" }}</span
                                        >
                                    </td>
                                    <td class="text-end">
                                        <button
                                            class="btn btn-sm btn-outline-danger"
                                            type="button"
                                            @click="desasignar(a.id)"
                                        >
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="!asignaciones.length">
                                    <td colspan="4" class="text-muted text-center py-3">Sin asignaciones</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Imágenes de la plantilla -->
                    <h6 class="ci-section mt-4">
                        Imágenes <small class="text-muted fw-normal">(JPG/PNG, máx 5MB)</small>
                    </h6>
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <input
                                ref="imgInput"
                                type="file"
                                accept="image/jpeg,image/png"
                                class="form-control"
                                @change="onImg"
                            />
                        </div>
                        <div class="col-md-3">
                            <input
                                v-model.trim="nuevaImagen.nombre"
                                class="form-control"
                                placeholder="Nombre/descripción"
                            />
                        </div>
                        <div class="col-md-3">
                            <div class="form-check mt-2">
                                <input
                                    id="epm"
                                    v-model="nuevaImagen.es_para_marcar"
                                    class="form-check-input"
                                    type="checkbox"
                                />
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
                                <i class="fa fa-upload me-1"></i>Subir
                            </button>
                        </div>
                    </div>

                    <div class="row g-2 mt-2">
                        <div v-for="img in imagenes" :key="img.id" class="col-md-3">
                            <div class="card h-100 shadow-sm">
                                <img
                                    :src="verImagenUrl(img.id)"
                                    class="card-img-top"
                                    style="max-height: 140px; object-fit: contain"
                                    :alt="img.nombre"
                                />
                                <div class="card-body p-2">
                                    <small class="d-block text-truncate">{{ img.nombre }}</small>
                                    <span v-if="img.es_para_marcar" class="badge bg-info-subtle text-info"
                                        >Marcable</span
                                    >
                                    <button
                                        class="btn btn-sm btn-outline-danger float-end"
                                        type="button"
                                        @click="eliminarImagen(img.id)"
                                    >
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div v-if="!imagenes.length" class="col-12 text-muted">Sin imágenes</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Comparar versiones -->
        <div class="col-12">
            <div class="card ci-card shadow-sm">
                <div class="card-header bg-white border-0 pb-0">
                    <h5 class="card-title mb-0">
                        <i class="fa fa-code-fork text-primary me-1"></i>Comparar versiones
                    </h5>
                </div>
                <div class="card-body">
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
                                <i class="fa fa-exchange me-1"></i>Comparar
                            </button>
                        </div>
                    </div>

                    <div v-if="diff.length" class="border rounded p-2 diff-box">
                        <div v-for="(seg, i) in diff" :key="i" :class="['diff-line', 'diff-' + seg.tipo]">
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
import { computed, onMounted, reactive, ref } from "vue";

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
const busqueda = ref("");
const soloVigentes = ref(true);
const asignaciones = ref([]);
const nuevaAsignacion = reactive({ producto_id: null, sede_id: null, requiere_firma_medico: true });
const asignando = ref(false);

const imagenes = ref([]);
const imgInput = ref(null);
const imgFile = ref(null);
const nuevaImagen = reactive({ nombre: "", es_para_marcar: false });
const subiendoImg = ref(false);
const verImagenUrl = api.verImagenUrl;

const formato = reactive({ codigo: "", version_formato: "", anio_formato: "" });
const guardandoFormato = ref(false);

const cmpProductoId = ref(null);
const versiones = ref([]);
const v1 = ref(null);
const v2 = ref(null);
const comparando = ref(false);
const diff = ref([]);

const plantillaSeleccionada = computed(() =>
    plantillas.value.find((p) => Number(p.id) === Number(plantillaId.value)),
);

const plantillasFiltradas = computed(() => {
    const q = busqueda.value.toLowerCase();
    if (!q) return plantillas.value;
    return plantillas.value.filter(
        (p) =>
            (p.nombre || "").toLowerCase().includes(q) || (p.codigo || "").toLowerCase().includes(q),
    );
});

function diffMark(tipo) {
    return tipo === "add" ? "+" : tipo === "del" ? "−" : " ";
}

async function cargarCombos() {
    try {
        const [rm, rp, rs] = await Promise.all([api.modulos(), api.productos(), api.sedesActivas()]);
        modulos.value = rm.data?.data ?? rm.data ?? [];
        productos.value = rp.data?.data ?? rp.data ?? [];
        sedes.value = rs.data?.data ?? rs.data ?? [];
        // Auto-selecciona el primer módulo y carga sus plantillas.
        if (modulos.value.length && !moduloId.value) {
            moduloId.value = modulos.value[0].id;
            await listarPlantillas();
        }
    } catch (e) {
        handleAxiosErrorToast(e, "No fue posible cargar catálogos.");
    }
}

async function listarPlantillas() {
    plantillaId.value = null;
    asignaciones.value = [];
    imagenes.value = [];
    if (!moduloId.value) return;
    try {
        const res = await api.plantillas({
            config_modulo_id: moduloId.value,
            vigentes: soloVigentes.value ? "1" : "0",
        });
        plantillas.value = res.data?.data ?? [];
    } catch (e) {
        handleAxiosErrorToast(e, "No fue posible listar plantillas.");
    }
}

async function seleccionarPlantilla(id) {
    plantillaId.value = id;
    const pl = plantillaSeleccionada.value;
    formato.codigo = pl?.codigo ?? "";
    formato.version_formato = pl?.version_formato ?? "";
    formato.anio_formato = pl?.anio_formato ?? "";
    try {
        const res = await api.asignaciones(id);
        asignaciones.value = res.data?.data ?? [];
    } catch (e) {
        handleAxiosErrorToast(e, "No fue posible listar asignaciones.");
    }
    await listarImagenes();
}

async function guardarFormato() {
    if (!plantillaId.value) return;
    guardandoFormato.value = true;
    try {
        await api.actualizarFormato(plantillaId.value, { ...formato });
        const pl = plantillaSeleccionada.value;
        if (pl) Object.assign(pl, formato);
        alertSuccess("Datos del formato guardados");
    } catch (e) {
        handleAxiosErrorToast(e, "No fue posible guardar el formato.");
    } finally {
        guardandoFormato.value = false;
    }
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
        await seleccionarPlantilla(plantillaId.value);
        alertSuccess("Asignación creada");
    } catch (e) {
        handleAxiosErrorToast(e, "No fue posible asignar.");
    } finally {
        asignando.value = false;
    }
}

async function desasignar(id) {
    const ok = await confirmAction({
        title: "Quitar asignación",
        text: "¿Quitar este producto/sede?",
    });
    if (!ok) return;
    try {
        await api.desasignar(id);
        await seleccionarPlantilla(plantillaId.value);
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
.ci-plantillas .card-title {
    font-size: 1.05rem;
}
/* Encabezado de página */
.ci-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 1.25rem;
    background: linear-gradient(135deg, #eef2ff 0%, #f8fafc 100%);
    border: 1px solid #e5e7eb;
    border-left: 4px solid var(--bs-primary, #0d6efd);
    border-radius: 0.6rem;
}
.ci-header-icon {
    width: 46px;
    height: 46px;
    border-radius: 12px;
    display: grid;
    place-items: center;
    background: var(--bs-primary, #0d6efd);
    color: #fff;
    font-size: 1.25rem;
    box-shadow: 0 4px 10px rgba(13, 110, 253, 0.25);
}
.ci-header-stat {
    text-align: center;
    line-height: 1;
}
.ci-header-num {
    display: block;
    font-size: 1.6rem;
    font-weight: 700;
    color: var(--bs-primary, #0d6efd);
}
.ci-header-lbl {
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #6b7280;
}
/* Tarjetas */
.ci-card {
    border: 1px solid #eef0f4;
    border-radius: 0.6rem;
}
.ci-section {
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.5rem;
    padding-bottom: 0.35rem;
    border-bottom: 2px solid #eef2ff;
}
/* Tabla */
.ci-table {
    border-collapse: separate;
    border-spacing: 0;
}
.ci-table thead th {
    font-size: 0.76rem;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: #6b7280;
    background: #f8fafc;
    border-bottom: 2px solid #e5e7eb;
    padding: 0.55rem 0.75rem;
    position: sticky;
    top: 0;
    z-index: 1;
}
.ci-table tbody td {
    padding: 0.55rem 0.75rem;
    border-bottom: 1px solid #f1f3f5;
    vertical-align: middle;
}
.ci-table tbody tr {
    transition: background 0.12s ease;
}
.ci-table tbody tr:hover {
    background: #f6f9ff;
}
.ci-table tbody tr.table-active {
    background: #eef4ff;
    box-shadow: inset 3px 0 0 var(--bs-primary, #0d6efd);
}
.diff-box {
    max-height: 50vh;
    overflow: auto;
    font-family: monospace;
    font-size: 12px;
    white-space: pre-wrap;
    background: #fbfbfb;
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

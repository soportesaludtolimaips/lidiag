<template>
    <div>
        <div class="row page-titles">
            <div class="col-md-6 align-self-center">
                <h3 class="text-themecolor">Ecografías</h3>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <button type="button" class="btn btn-link p-0 breadcrumb-btn">
                            Inicio
                        </button>
                    </li>
                    <li class="breadcrumb-item">
                        <button type="button" class="btn btn-link p-0 breadcrumb-btn">
                            Ecografías
                        </button>
                    </li>
                    <li class="breadcrumb-item active">Plantillas Consentimientos</li>
                </ol>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Plantillas (WYSIWYG)</h4>

                <div class="row">
                    <div class="col-md-4">
                        <label for="config_modulo_id">Módulo</label>
                        <select
                            class="form-control"
                            id="config_modulo_id"
                            v-model="filtros.config_modulo_id"
                        >
                            <option value="">—</option>
                            <option v-for="m in modulos" :key="m.id" :value="m.id">
                                {{ m.modulo }}
                            </option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="busqueda_q">Buscar</label>
                        <input
                            id="busqueda_q"
                            class="form-control"
                            v-model="q"
                            placeholder="CUPS / Producto / Nombre"
                        />
                    </div>
                    <div class="col-md-4 text-right">
                        <button
                            v-if="can('ecografias.consentimientos.plantillas.create')"
                            class="btn btn-info mt-4"
                            type="button"
                            data-toggle="modal"
                            data-target="#modal-plantilla"
                            @click="nuevo()"
                        >
                            <i class="fa fa-plus"></i> Nueva plantilla
                        </button>
                    </div>
                </div>

                <div class="mt-3">
                    <button
                        class="btn btn-success"
                        type="button"
                        @click="listar"
                        :disabled="loading"
                    >
                        <i class="fa" :class="loading ? 'fa-spinner fa-spin' : 'fa-search'"></i>
                        Listar
                    </button>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>CUPS</th>
                                <th>Nombre</th>
                                <th>Versión</th>
                                <th>Vigente</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="p in registros" :key="p.id">
                                <td>{{ p.producto?.nom_produc || "—" }}</td>
                                <td>{{ p.producto?.cod_cups || "—" }}</td>
                                <td>{{ p.nombre }}</td>
                                <td>{{ p.version }}</td>
                                <td>
                                    <span v-if="p.vigente" class="text-success">Sí</span>
                                    <span v-else class="text-muted">No</span>
                                </td>
                                <td class="text-nowrap">
                                    <button
                                        class="btn btn-sm btn-info m-r-5"
                                        type="button"
                                        @click="previewPdf(p)"
                                        :disabled="loadingPreviewId === p.id"
                                        title="Vista previa PDF"
                                        aria-label="Vista previa PDF"
                                    >
                                        <i
                                            class="fa"
                                            :class="
                                                loadingPreviewId === p.id
                                                    ? 'fa-spinner fa-spin'
                                                    : 'fa-file-pdf-o'
                                            "
                                        ></i>
                                    </button>

                                    <button
                                        v-if="can('ecografias.consentimientos.plantillas.edit')"
                                        class="btn btn-sm btn-warning m-r-5"
                                        type="button"
                                        data-toggle="modal"
                                        data-target="#modal-plantilla"
                                        @click="editar(p)"
                                        title="Editar plantilla"
                                        aria-label="Editar plantilla"
                                    >
                                        <i class="fa fa-pencil"></i>
                                    </button>

                                    <button
                                        v-if="can('ecografias.consentimientos.plantillas.index')"
                                        class="btn btn-sm btn-secondary m-r-5"
                                        type="button"
                                        @click="verVersiones(p)"
                                        title="Histórico de versiones"
                                        aria-label="Histórico de versiones"
                                    >
                                        <i class="fa fa-history"></i>
                                    </button>

                                    <button
                                        v-if="can('ecografias.consentimientos.plantillas.destroy')"
                                        class="btn btn-sm btn-danger"
                                        type="button"
                                        @click="desactivar(p)"
                                        title="Desactivar plantilla"
                                        aria-label="Desactivar plantilla"
                                    >
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="registros.length === 0">
                                <td colspan="6" class="text-center text-muted">Sin registros.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal Plantilla -->
        <div
            id="modal-plantilla"
            class="modal fade"
            tabindex="-1"
            role="dialog"
            aria-labelledby="modal-plantilla-title"
            aria-hidden="true"
            v-show="modalPlantillaVisible"
            :style="{ display: modalPlantillaVisible ? 'block' : 'none' }"
        >
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title" id="modal-plantilla-title">
                            {{ form.id ? "Actualizar (nueva versión)" : "Nueva plantilla" }}
                        </h4>
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
                            ×
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="form_producto_id">Producto</label>
                                <select
                                    class="form-control"
                                    id="form_producto_id"
                                    v-model="form.producto_id"
                                    :disabled="!!form.id"
                                >
                                    <option value="">—</option>
                                    <option v-for="p in productos" :key="p.id" :value="p.id">
                                        {{ p.cod_cups }} - {{ p.nom_produc }}
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="form_nombre">Nombre</label>
                                <input
                                    id="form_nombre"
                                    class="form-control"
                                    v-model="form.nombre"
                                />
                            </div>
                        </div>

                        <div class="mt-3">
                            <label for="form_html">Contenido</label>
                            <WysiwygEditor v-model="form.html" :rows="10" />
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button
                            class="btn btn-success"
                            type="button"
                            @click="guardar"
                            :disabled="loadingGuardar"
                        >
                            <i
                                class="fa"
                                :class="loadingGuardar ? 'fa-spinner fa-spin' : 'fa-check'"
                            ></i>
                            {{ loadingGuardar ? "Guardando…" : "Guardar" }}
                        </button>
                        <button class="btn btn-default" type="button" data-dismiss="modal">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Versiones -->
        <div
            id="modal-versiones"
            class="modal fade"
            tabindex="-1"
            role="dialog"
            aria-labelledby="modal-versiones-title"
            aria-hidden="true"
            v-show="modalVersionesVisible"
            :style="{ display: modalVersionesVisible ? 'block' : 'none' }"
        >
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title" id="modal-versiones-title">
                            Histórico de versiones
                        </h4>
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
                            ×
                        </button>
                    </div>
                    <div class="modal-body">
                        <div v-if="loadingVersions" class="text-muted">Cargando…</div>
                        <div v-else class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Versión</th>
                                        <th>Vigente</th>
                                        <th>Creada</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="v in versions" :key="v.id">
                                        <td>{{ v.version }}</td>
                                        <td>
                                            <span v-if="v.vigente" class="text-success">Sí</span>
                                            <span v-else class="text-muted">No</span>
                                        </td>
                                        <td>{{ v.created_at }}</td>
                                        <td class="text-nowrap">
                                            <button
                                                class="btn btn-sm btn-info m-r-5"
                                                type="button"
                                                @click="previewPdf(v)"
                                                title="Vista previa PDF"
                                                aria-label="Vista previa PDF"
                                            >
                                                <i class="fa fa-file-pdf-o"></i>
                                            </button>
                                            <button
                                                v-if="
                                                    !v.vigente &&
                                                    can(
                                                        'ecografias.consentimientos.plantillas.edit'
                                                    )
                                                "
                                                class="btn btn-sm btn-success"
                                                type="button"
                                                @click="activarVersion(v)"
                                            >
                                                <i class="fa fa-check"></i> Activar
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="versions.length === 0">
                                        <td colspan="4" class="text-center text-muted">
                                            Sin histórico.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-default" type="button" data-dismiss="modal">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup>
import { onMounted, ref } from "vue";

import usePermissions from "@/helpers/permissions.js";
import { confirmAction, toastMessage } from "@/helpers/alerts";
import WysiwygEditor from "@/components/shared/WysiwygEditor.vue";
import axios from "axios";

const props = defineProps({
    usuarioactual: { type: Object, default: () => ({}) },
    permisos: { type: Array, default: () => [] },
});
const { can } = usePermissions(props.permisos);

const q = ref("");
const loading = ref(false);
const loadingGuardar = ref(false);
const loadingPreviewId = ref(null);
const registros = ref([]);
const productos = ref([]);
const modulos = ref([]);
const filtros = ref({ config_modulo_id: "" });
const form = ref({ id: null, producto_id: "", nombre: "", html: "" });
const versions = ref([]);
const loadingVersions = ref(false);
const productoVersionsId = ref(null);
const modalPlantillaVisible = ref(false);
const modalVersionesVisible = ref(false);
/**
 * Carga la lista de productos de configuración disponibles para asociar a las plantillas.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-02-13
 */
async function listarProductos() {
    try {
        const res = await axios.get("/api/config-productos");
        productos.value = Array.isArray(res.data?.data) ? res.data.data : [];
    } catch (error) {
        toastMessage({
            heading: "Error",
            text: error.response?.data?.message || "No fue posible cargar los productos.",
            icon: "error",
        });
    }
}
/**
 * Carga la lista de módulos de configuración y selecciona por defecto el módulo de Ecografías si existe.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-02-13
 */
async function listarModulos() {
    try {
        const res = await axios.get("/api/config-modulos-listar");
        modulos.value = Array.isArray(res.data?.data) ? res.data.data : [];
        // default Ecografías (id=2) si existe
        if (!filtros.value.config_modulo_id) {
            const eco = modulos.value.find((m) => Number(m.id) === 2);
            if (eco) filtros.value.config_modulo_id = eco.id;
        }
    } catch (error) {
        toastMessage({
            heading: "Error",
            text: error.response?.data?.message || "No fue posible cargar los módulos.",
            icon: "error",
        });
    }
}
/**
 * Consulta las plantillas de consentimiento vigentes según módulo y texto de búsqueda.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-02-13
 */
async function listar() {
    if (!filtros.value.config_modulo_id) {
        return;
    }
    loading.value = true;
    try {
        const res = await axios.get("/api/consentimientos-plantillas", {
            params: {
                q: q.value,
                vigentes: 1,
                config_modulo_id: filtros.value.config_modulo_id,
            },
        });
        registros.value = Array.isArray(res.data?.data) ? res.data.data : [];
    } catch {
        registros.value = [];
    } finally {
        loading.value = false;
    }
}
/**
 * Reinicia el formulario para registrar una nueva plantilla de consentimiento.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-02-13
 */
function nuevo() {
    form.value = { id: null, producto_id: "", nombre: "", html: "" };
}
/**
 * Carga los datos de una plantilla existente en el formulario para editarla (nueva versión).
 *
 * @param {Object} p Registro de la plantilla que se desea editar.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-02-13
 */
function editar(p) {
    form.value = { id: p.id, producto_id: p.producto_id, nombre: p.nombre, html: p.html };
}
/**
 * Crea o actualiza una plantilla de consentimiento y cierra el modal tras guardar.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-02-13
 */
async function guardar() {
    if (!form.value.producto_id) return;
    if (!filtros.value.config_modulo_id) return;
    loadingGuardar.value = true;
    try {
        const payload = {
            config_modulo_id: filtros.value.config_modulo_id,
            producto_id: form.value.producto_id,
            nombre: form.value.nombre,
            html: form.value.html,
        };
        if (!form.value.id) {
            await axios.post("/api/consentimientos-plantillas", payload);
        } else {
            await axios.put(`/api/consentimientos-plantillas/${form.value.id}`, payload);
        }
        await listar();
        modalPlantillaVisible.value = false;
    } catch (error) {
        toastMessage({
            heading: "Error",
            text: error.response?.data?.message || "No fue posible guardar la plantilla.",
            icon: "error",
        });
    } finally {
        loadingGuardar.value = false;
    }
}
/**
 * Abre en una nueva pestaña la vista previa en PDF de la plantilla de consentimiento indicada.
 *
 * @param {Object} p Registro de la plantilla cuya vista previa se desea mostrar.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-02-13
 */
async function previewPdf(p) {
    try {
        loadingPreviewId.value = p.id;
        window.open(
            `/api/consentimientos-plantillas/${p.id}/preview-pdf`,
            "_blank",
            "noopener,noreferrer"
        );
    } finally {
        loadingPreviewId.value = null;
    }
}
/**
 * Carga el histórico de versiones de una plantilla y muestra el modal correspondiente.
 *
 * @param {Object} p Registro de la plantilla de la cual se consultan las versiones.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-02-13
 */
async function verVersiones(p) {
    if (!filtros.value.config_modulo_id) return;
    loadingVersions.value = true;
    versions.value = [];
    productoVersionsId.value = p.producto_id;
    try {
        const res = await axios.get(
            `/api/consentimientos-plantillas/producto/${p.producto_id}/versions`,
            {
                params: { config_modulo_id: filtros.value.config_modulo_id },
            }
        );
        versions.value = Array.isArray(res.data?.data) ? res.data.data : [];
        modalVersionesVisible.value = true;
    } catch (error) {
        toastMessage({
            heading: "Error",
            text: error.response?.data?.message || "No fue posible cargar las versiones.",
            icon: "error",
        });
    } finally {
        loadingVersions.value = false;
    }
}
/**
 * Marca una versión específica de plantilla como vigente y actualiza el listado e histórico.
 *
 * @param {Object} v Registro de la versión de plantilla que se desea activar.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-02-13
 */
async function activarVersion(v) {
    try {
        await axios.post(`/api/consentimientos-plantillas/${v.id}/vigente`);
        // refrescar modal y listado
        if (productoVersionsId.value) {
            const res = await axios.get(
                `/api/consentimientos-plantillas/producto/${productoVersionsId.value}/versions`,
                {
                    params: { config_modulo_id: filtros.value.config_modulo_id },
                }
            );
            versions.value = Array.isArray(res.data?.data) ? res.data.data : [];
        }
        await listar();
    } catch (error) {
        toastMessage({
            heading: "Error",
            text: error.response?.data?.message || "No fue posible activar la versión.",
            icon: "error",
        });
    }
}
/**
 * Solicita confirmación y elimina (desactiva) la plantilla de consentimiento indicada.
 *
 * @param {Object} p Registro de la plantilla que se desea desactivar.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-02-13
 */
async function desactivar(p) {
    const ok = await confirmAction({
        title: "¿Desactivar plantilla?",
        text: "La plantilla dejará de estar vigente.",
        icon: "warning",
    });
    if (!ok) return;
    try {
        await axios.delete(`/api/consentimientos-plantillas/${p.id}`);
        await listar();
    } catch (error) {
        toastMessage({
            heading: "Error",
            text: error.response?.data?.message || "No fue posible desactivar la plantilla.",
            icon: "error",
        });
    }
}

/**
 * Hook de ciclo de vida que carga productos, módulos y la lista inicial de plantillas al montar el componente.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-02-13
 */
onMounted(() => {
    listarProductos();
    listarModulos();
    listar();
});
</script>
<style scoped>
.breadcrumb-btn {
    vertical-align: baseline;
}
</style>

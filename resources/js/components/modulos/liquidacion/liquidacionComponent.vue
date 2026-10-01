<template>
    <div>
        <!-- BREADCRUMB -->
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">Liquidaci&oacute;n</h3>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="javascript:void(0)">Inicio</a></li>
                    <li class="breadcrumb-item active">Liquidaci&oacute;n</li>
                </ol>
            </div>
        </div>

        <!-- FILTROS -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Filtros de liquidaci&oacute;n</h4>
                        <h6 class="card-subtitle">
                            Seleccione la sede y el rango de fechas a liquidar. Ambas fechas son
                            obligatorias.
                        </h6>
                        <div class="row m-t-20">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="liq-sede">Sede</label>
                                    <select
                                        id="liq-sede"
                                        v-model="filtros.sede_id"
                                        class="form-control custom-select"
                                        :disabled="loadingSedes"
                                    >
                                        <option value="">...::: Seleccione una sede :::...</option>
                                        <option
                                            v-for="sede in sedes"
                                            :key="sede.id"
                                            :value="sede.id"
                                        >
                                            {{ sede.nom_sede }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="liq-inicio">Fecha inicio</label>
                                    <input
                                        id="liq-inicio"
                                        v-model="filtros.fecha_inicio"
                                        type="date"
                                        class="form-control"
                                    />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="liq-fin">Fecha fin</label>
                                    <input
                                        id="liq-fin"
                                        v-model="filtros.fecha_fin"
                                        type="date"
                                        class="form-control"
                                    />
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <div>
                                        <button
                                            type="button"
                                            class="btn btn-info m-r-5"
                                            :disabled="loading"
                                            @click="calcular"
                                            title="Calcular liquidación"
                                        >
                                            <i class="fa fa-calculator m-r-5"></i>
                                            {{ loading ? "Calculando..." : "Calcular" }}
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-success"
                                            :disabled="loading"
                                            @click="exportar"
                                            title="Exportar liquidación a Excel"
                                        >
                                            <i class="fa fa-file-excel-o"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- RESULTADOS -->
        <div v-if="resultado" class="row">
            <!-- LÍNEAS -->
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            L&iacute;neas liquidables
                            <span class="badge badge-info m-l-5">{{ lineas.length }}</span>
                        </h4>
                        <div class="table-responsive m-t-20">
                            <table
                                class="display nowrap table table-hover table-striped table-bordered"
                                cellspacing="0"
                                width="100%"
                            >
                                <thead>
                                    <tr>
                                        <th>M&eacute;dico</th>
                                        <th>CUPS</th>
                                        <th>Producto</th>
                                        <th>Clase</th>
                                        <th>Valor</th>
                                        <th>Fecha lectura</th>
                                        <th>Contrato</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(linea, i) in lineas" :key="i">
                                        <td>{{ linea.medico_name }}</td>
                                        <td>{{ linea.cod_cups }}</td>
                                        <td>{{ linea.nom_produc }}</td>
                                        <td>
                                            <span
                                                v-if="linea.clase === 'urgencias'"
                                                class="badge badge-warning"
                                            >
                                                Urgencias
                                            </span>
                                            <span v-else class="badge badge-info">Consulta</span>
                                        </td>
                                        <td>{{ formatMoney(linea.valor) }}</td>
                                        <td>{{ formatDate(linea.fecha_lectura) }}</td>
                                        <td>#{{ linea.contrato_id }}</td>
                                    </tr>
                                    <tr v-if="!lineas.length">
                                        <td colspan="7" class="text-center">
                                            Sin l&iacute;neas liquidables para los filtros
                                            seleccionados.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TOTALES POR MÉDICO -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Totales por m&eacute;dico</h4>
                        <div class="table-responsive m-t-20">
                            <table class="table table-hover table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>M&eacute;dico</th>
                                        <th>Consulta</th>
                                        <th>Urgencias</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in porMedico" :key="item.medico_id">
                                        <td>{{ item.medico_name }}</td>
                                        <td>{{ formatMoney(item.total_consulta) }}</td>
                                        <td>{{ formatMoney(item.total_urgencias) }}</td>
                                        <td>{{ formatMoney(item.total) }}</td>
                                    </tr>
                                    <tr v-if="!porMedico.length">
                                        <td colspan="4" class="text-center">Sin totales.</td>
                                    </tr>
                                </tbody>
                                <tfoot v-if="porMedico.length">
                                    <tr>
                                        <th>Gran total</th>
                                        <th colspan="3">{{ formatMoney(granTotal) }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- EXCLUSIONES -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            Exclusiones
                            <span class="badge badge-secondary m-l-5">{{ totalExcluidos }}</span>
                        </h4>
                        <h6 class="card-subtitle">
                            Lecturas que no se liquidaron, por categor&iacute;a.
                        </h6>
                        <ul class="list-group m-t-20">
                            <li
                                v-for="item in exclusiones"
                                :key="item.categoria"
                                class="list-group-item d-flex justify-content-between align-items-center"
                            >
                                {{ etiquetaExclusion(item.categoria) }}
                                <span class="badge badge-secondary badge-pill">{{
                                    item.conteo
                                }}</span>
                            </li>
                            <li v-if="!exclusiones.length" class="list-group-item text-center">
                                Sin exclusiones.
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from "vue";
import axios from "axios";
import { toastMessage } from "@/helpers/alerts.js";

/**
 * Etiquetas legibles para las categorías de exclusión del backend.
 * El servicio devuelve: sin_asignado, sin_contrato, producto_sin_tarifa,
 * medico_fuera_detalle y anulada.
 *
 * @type {Object<string, string>}
 */
const ETIQUETAS_EXCLUSION = {
    sin_asignado: "Sin médico asignado",
    sin_contrato: "Sin contrato vigente",
    producto_sin_tarifa: "Producto sin tarifa",
    medico_fuera_detalle: "Médico fuera del detalle",
    anulada: "Lectura anulada",
};

/**
 * Fecha de hoy en formato YYYY-MM-DD para los inputs type="date".
 *
 * @returns {string} Hoy en formato YYYY-MM-DD
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-09-30
 */
function fechaHoy() {
    return new Date().toISOString().slice(0, 10);
}

/**
 * Primer día del mes actual en formato YYYY-MM-DD.
 * Se usa como fecha de inicio por defecto para acotar el cálculo al mes actual:
 * el backend carga todo el rango en memoria, así que no se permiten rangos abiertos.
 *
 * @returns {string} Primer día del mes actual en formato YYYY-MM-DD
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-09-30
 */
function primerDiaMesActual() {
    const ahora = new Date();
    const mes = String(ahora.getMonth() + 1).padStart(2, "0");
    return `${ahora.getFullYear()}-${mes}-01`;
}

/**
 * Filtros de la liquidación (sede y rango de fechas cerrado).
 * Por defecto se sugiere el mes actual; ambas fechas son obligatorias.
 *
 * @type {import('vue').Ref<{sede_id: string|number, fecha_inicio: string, fecha_fin: string}>}
 */
const filtros = ref({
    sede_id: "",
    fecha_inicio: primerDiaMesActual(),
    fecha_fin: fechaHoy(),
});

/**
 * Sedes activas para el select de filtros.
 *
 * @type {import('vue').Ref<Array>}
 */
const sedes = ref([]);

/**
 * Resultado del cálculo: { lineas, exclusiones, totales } o null si aún no se calcula.
 *
 * @type {import('vue').Ref<Object|null>}
 */
const resultado = ref(null);

/**
 * Flag de carga del cálculo de liquidación.
 *
 * @type {import('vue').Ref<boolean>}
 */
const loading = ref(false);

/**
 * Flag de carga del listado de sedes.
 *
 * @type {import('vue').Ref<boolean>}
 */
const loadingSedes = ref(false);

/**
 * Líneas liquidables del resultado actual.
 *
 * @returns {Array} Líneas con médico, CUPS, producto, clase, valor, fecha y contrato
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-09-30
 */
const lineas = computed(() => resultado.value?.lineas || []);

/**
 * Totales agregados por médico del resultado actual.
 *
 * @returns {Array} Totales con medico_id, medico_name, total_consulta, total_urgencias y total
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-09-30
 */
const porMedico = computed(() => resultado.value?.totales?.por_medico || []);

/**
 * Gran total de la liquidación actual.
 *
 * @returns {number} Suma de los totales por médico
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-09-30
 */
const granTotal = computed(() => resultado.value?.totales?.gran_total ?? 0);

/**
 * Exclusiones del resultado actual con categoría y conteo.
 *
 * @returns {Array} Exclusiones con categoria, conteo y detalle
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-09-30
 */
const exclusiones = computed(() => resultado.value?.exclusiones || []);

/**
 * Total de lecturas excluidas (suma de conteos por categoría).
 *
 * @returns {number} Cantidad total de excluidos
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-09-30
 */
const totalExcluidos = computed(() =>
    exclusiones.value.reduce((acc, item) => acc + (item.conteo || 0), 0)
);

/**
 * Formatea una fecha ISO a formato local colombiano (dd/mm/yyyy).
 *
 * @param {string|null} dateStr - Fecha en formato ISO o null
 * @returns {string} Fecha formateada o cadena vacía
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-09-30
 */
function formatDate(dateStr) {
    if (!dateStr) return "";
    return new Date(dateStr).toLocaleDateString("es-CO");
}

/**
 * Formatea un número a moneda colombiana (COP).
 *
 * @param {number|null} value - Valor numérico a formatear
 * @returns {string} Cadena con formato de moneda o "$0"
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-09-30
 */
function formatMoney(value) {
    if (!value) return "$0";
    return new Intl.NumberFormat("es-CO", { style: "currency", currency: "COP" }).format(value);
}

/**
 * Devuelve la etiqueta legible de una categoría de exclusión.
 *
 * @param {string} categoria - Categoría devuelta por el backend
 * @returns {string} Etiqueta legible o la categoría original si no está mapeada
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-09-30
 */
function etiquetaExclusion(categoria) {
    return ETIQUETAS_EXCLUSION[categoria] || categoria;
}

/**
 * Carga las sedes activas para el select de filtros.
 * Reutiliza el endpoint de sedes activas usado en el resto de módulos
 * (contratoComponent no tiene select: lee la sede readonly de ST-sede).
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-09-30
 */
async function cargarSedes() {
    if (loadingSedes.value) return;
    loadingSedes.value = true;
    try {
        const res = await axios.get("/api/config-sedes/listar/sedes/activas");
        sedes.value = res.data.data || [];
    } catch (e) {
        console.error(e);
        toastMessage({ heading: "Error", text: "No fue posible cargar las sedes", icon: "error" });
    } finally {
        loadingSedes.value = false;
    }
}

/**
 * Valida que los filtros estén completos: sede y ambas fechas, con fin >= inicio.
 * El rango cerrado es obligatorio porque el backend carga todo el periodo en memoria.
 *
 * @returns {boolean} True si los filtros son válidos para calcular
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-09-30
 */
function filtrosValidos() {
    if (!filtros.value.sede_id) {
        toastMessage({ heading: "Atención", text: "Seleccione una sede", icon: "warning" });
        return false;
    }
    if (!filtros.value.fecha_inicio || !filtros.value.fecha_fin) {
        toastMessage({
            heading: "Atención",
            text: "Seleccione la fecha de inicio y la fecha de fin",
            icon: "warning",
        });
        return false;
    }
    if (filtros.value.fecha_fin < filtros.value.fecha_inicio) {
        toastMessage({
            heading: "Atención",
            text: "La fecha de fin no puede ser anterior a la fecha de inicio",
            icon: "warning",
        });
        return false;
    }
    return true;
}

/**
 * Calcula la liquidación con los filtros seleccionados.
 * Consume GET /api/liquidacion y asigna res.data.data (lineas, exclusiones, totales).
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-09-30
 */
async function calcular() {
    if (loading.value) return;
    if (!filtrosValidos()) return;
    loading.value = true;
    try {
        const res = await axios.get("/api/liquidacion", {
            params: {
                sede_id: filtros.value.sede_id,
                fecha_inicio: filtros.value.fecha_inicio,
                fecha_fin: filtros.value.fecha_fin,
            },
        });
        resultado.value = res.data.data;
        if (!resultado.value?.lineas?.length) {
            toastMessage({
                heading: "Información",
                text: "Sin líneas liquidables para los filtros seleccionados",
                icon: "info",
            });
        }
    } catch (e) {
        console.error(e);
        const msg = e.response?.data?.message || "Error al calcular la liquidación";
        toastMessage({ heading: "Error", text: msg, icon: "error" });
    } finally {
        loading.value = false;
    }
}

/**
 * Exporta la liquidación del periodo a Excel en una ventana nueva.
 * La ruta /api/liquidacion/exportar se crea en la Task 6: si da 404 antes,
 * es esperado (patrón window.open de contratoComponent.vue:1244).
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-09-30
 */
function exportar() {
    if (!filtrosValidos()) return;
    const params = new URLSearchParams({
        sede_id: String(filtros.value.sede_id),
        fecha_inicio: filtros.value.fecha_inicio,
        fecha_fin: filtros.value.fecha_fin,
    });
    window.open(`/api/liquidacion/exportar?${params.toString()}`, "_blank");
}

/**
 * Lifecycle onMounted: carga las sedes activas para el select de filtros.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-09-30
 */
onMounted(() => {
    cargarSedes();
});
</script>

<template>
    <div>
        <!-- BREADCRUMB -->
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">Productividad m&eacute;dica</h3>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="javascript:void(0)">Inicio</a></li>
                    <li class="breadcrumb-item active">Productividad</li>
                </ol>
            </div>
        </div>

        <!-- FILTROS -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Corte de mes</h4>
                        <h6 class="card-subtitle">
                            Seleccione la sede y el periodo. El reporte lee las líneas congeladas al
                            momento de cada lectura.
                        </h6>
                        <div class="row m-t-20">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="prod-sede">Sede</label>
                                    <select
                                        id="prod-sede"
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
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="prod-periodo">Periodo</label>
                                    <input
                                        id="prod-periodo"
                                        v-model="filtros.periodo"
                                        type="month"
                                        class="form-control"
                                    />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <div>
                                        <button
                                            type="button"
                                            class="btn btn-info m-r-5"
                                            :disabled="loading"
                                            @click="cargar"
                                            title="Generar reporte"
                                        >
                                            <i class="fa fa-bar-chart m-r-5"></i>
                                            {{ loading ? "Generando..." : "Generar" }}
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-success"
                                            :disabled="loading || !resultado || !medicos.length"
                                            @click="exportar"
                                            title="Exportar productividad a Excel"
                                        >
                                            <i class="fa fa-file-excel-o m-r-5"></i>
                                            Excel
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- RESULTADO -->
        <div v-if="resultado" class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            Productividad {{ resultado.periodo }}
                            <span class="badge badge-success m-l-5">{{
                                formatMoney(granTotal)
                            }}</span>
                        </h4>
                        <div v-if="!medicos.length" class="alert alert-info">
                            Sin producción registrada para este corte.
                        </div>
                        <div v-for="m in medicos" :key="m.medico_id" class="card m-t-20">
                            <div class="card-body">
                                <h5 class="card-title">
                                    {{ m.medico_name || "Sin asignado" }}
                                    <span class="badge badge-info m-l-5">{{
                                        formatMoney(m.total)
                                    }}</span>
                                    <span v-if="m.excluidas" class="badge badge-warning m-l-5">
                                        {{ m.excluidas }} excluidas
                                    </span>
                                </h5>
                                <div class="table-responsive m-t-10">
                                    <table
                                        class="display nowrap table table-hover table-striped table-bordered"
                                        cellspacing="0"
                                        width="100%"
                                    >
                                        <thead>
                                            <tr>
                                                <th>Fecha</th>
                                                <th>CUPS</th>
                                                <th>Producto</th>
                                                <th>Clase</th>
                                                <th>Valor</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="l in m.lineas" :key="l.id">
                                                <td>{{ formatDate(l.fecha_lectura) }}</td>
                                                <td>{{ l.producto?.cod_cups }}</td>
                                                <td>{{ l.producto?.nom_produc }}</td>
                                                <td>
                                                    <span
                                                        class="badge"
                                                        :class="
                                                            l.clase === 'urgencias'
                                                                ? 'badge-warning'
                                                                : 'badge-success'
                                                        "
                                                    >
                                                        {{ l.clase }}
                                                    </span>
                                                </td>
                                                <td>{{ formatMoney(l.valor) }}</td>
                                            </tr>
                                            <tr v-for="x in m.excluidas_detalle" :key="'x' + x.id">
                                                <td>{{ formatDate(x.fecha_lectura) }}</td>
                                                <td>{{ x.producto?.cod_cups }}</td>
                                                <td>{{ x.producto?.nom_produc }}</td>
                                                <td>
                                                    <span class="badge badge-secondary">
                                                        {{ x.exclusion }}
                                                    </span>
                                                </td>
                                                <td>-</td>
                                            </tr>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th colspan="4">Total consulta / urgencias</th>
                                                <th>
                                                    {{ formatMoney(m.total_consulta) }} /
                                                    {{ formatMoney(m.total_urgencias) }}
                                                </th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
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
 * Filtros del corte (sede y periodo YYYY-MM). El periodo sale del input month.
 *
 * @type {import('vue').Ref<{sede_id: string|number, periodo: string}>}
 */
const filtros = ref({
    sede_id: "",
    periodo: new Date().toISOString().slice(0, 7),
});

/**
 * Sedes activas para el select de filtros.
 *
 * @type {import('vue').Ref<Array>}
 */
const sedes = ref([]);

/**
 * Resultado del reporte: { periodo, medicos, gran_total } o null.
 *
 * @type {import('vue').Ref<Object|null>}
 */
const resultado = ref(null);

/**
 * Flag de carga del reporte.
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
 * Médicos del reporte actual con su detalle de producción.
 *
 * @returns {Array} Médicos con lineas, totales y excluidas
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-10-01
 */
const medicos = computed(() => resultado.value?.medicos || []);

/**
 * Gran total del corte actual.
 *
 * @returns {number} Suma de totales por médico
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-10-01
 */
const granTotal = computed(() => resultado.value?.gran_total ?? 0);

/**
 * Formatea una fecha ISO a formato local colombiano (dd/mm/yyyy).
 *
 * @param {string|null} dateStr - Fecha en formato ISO o null
 * @returns {string} Fecha formateada o cadena vacía
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-10-01
 */
function formatDate(dateStr) {
    if (!dateStr) return "";
    const cleanDate = String(dateStr).split(/[ T]/)[0];
    const parts = cleanDate.split("-");
    if (parts.length === 3) {
        const [year, month, day] = parts;
        return `${day.padStart(2, "0")}/${month.padStart(2, "0")}/${year}`;
    }
    const d = new Date(dateStr);
    return isNaN(d.getTime()) ? "" : d.toLocaleDateString("es-CO");
}

/**
 * Formatea un número a moneda colombiana (COP).
 *
 * @param {number|null} value - Valor numérico a formatear
 * @returns {string} Cadena con formato de moneda o "$0"
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-10-01
 */
function formatMoney(value) {
    const num = Number(value);
    if (value === null || value === undefined || isNaN(num)) return "$ 0";
    return new Intl.NumberFormat("es-CO", {
        style: "currency",
        currency: "COP",
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(num);
}

/**
 * Carga las sedes activas para el select de filtros.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-10-01
 */
async function cargarSedes() {
    if (loadingSedes.value) return;
    loadingSedes.value = true;
    try {
        const res = await axios.get("/api/config-sedes/listar/sedes/activas");
        sedes.value = res.data.data || [];
    } catch (error) {
        console.error(error);
        toastMessage({ heading: "Error", text: "No fue posible cargar las sedes", icon: "error" });
    } finally {
        loadingSedes.value = false;
    }
}

/**
 * Genera el reporte de productividad del corte seleccionado.
 * Consume GET /api/liquidacion/productividad (lee líneas congeladas).
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-10-01
 */
async function cargar() {
    if (loading.value) return;
    if (!filtros.value.sede_id) {
        toastMessage({ heading: "Atención", text: "Seleccione una sede", icon: "warning" });
        return;
    }
    if (!filtros.value.periodo) {
        toastMessage({ heading: "Atención", text: "Seleccione un periodo", icon: "warning" });
        return;
    }
    loading.value = true;
    try {
        const res = await axios.get("/api/liquidacion/productividad", {
            params: { sede_id: filtros.value.sede_id, periodo: filtros.value.periodo },
        });
        resultado.value = res.data.data;
        if (!resultado.value?.medicos?.length) {
            toastMessage({
                heading: "Información",
                text: "Sin producción registrada para este corte",
                icon: "info",
            });
        }
    } catch (error) {
        console.error(error);
        const msg = error.response?.data?.message || "Error al generar el reporte";
        toastMessage({ heading: "Error", text: msg, icon: "error" });
    } finally {
        loading.value = false;
    }
}

/**
 * Exporta el reporte de productividad a Excel en una nueva pestaña.
 *
 * Flujo del proceso:
 * 1. Valida que los filtros de sede y periodo estén diligenciados.
 * 2. Verifica que existan resultados calculados para exportar.
 * 3. Abre la URL del endpoint en una nueva pestaña para descargar el archivo .xlsx.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-10-21
 */
function exportar() {
    if (!filtros.value.sede_id) {
        toastMessage({ heading: "Atención", text: "Seleccione una sede", icon: "warning" });
        return;
    }
    if (!filtros.value.periodo) {
        toastMessage({ heading: "Atención", text: "Seleccione un periodo", icon: "warning" });
        return;
    }
    if (!resultado.value || !medicos.value.length) {
        toastMessage({
            heading: "Atención",
            text: "Genere primero el reporte con resultados antes de exportar.",
            icon: "warning",
        });
        return;
    }
    const params = new URLSearchParams({
        sede_id: String(filtros.value.sede_id),
        periodo: filtros.value.periodo,
    });
    window.open(`/api/liquidacion/productividad/exportar?${params.toString()}`, "_blank");
}

/**
 * Lifecycle onMounted: carga las sedes activas para el select de filtros.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-10-01
 */
onMounted(() => {
    cargarSedes();
});
</script>

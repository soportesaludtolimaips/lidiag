<template>
    <div>
        <!-- Filtros -->
        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Sede</label>
                        <select v-model="filtros.sede_id" class="form-select">
                            <option :value="null">Todas</option>
                            <option v-for="s in sedes" :key="s.id" :value="s.id">{{ s.nom_sede }}</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Producto</label>
                        <select v-model="filtros.producto_id" class="form-select">
                            <option :value="null">Todos</option>
                            <option v-for="p in productos" :key="p.id" :value="p.id">{{ p.nom_produc }}</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Desde</label>
                        <input v-model="filtros.fecha_inicio" type="date" class="form-control" />
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Hasta</label>
                        <input v-model="filtros.fecha_fin" type="date" class="form-control" />
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary w-100" type="button" :disabled="cargando" @click="cargar">
                            <span v-if="cargando">Cargando…</span>
                            <span v-else>Aplicar</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tarjetas resumen -->
        <div class="row g-3 mb-3">
            <div v-for="c in tarjetas" :key="c.label" class="col-6 col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <div class="h3 mb-0">{{ c.valor }}</div>
                        <small class="text-muted">{{ c.label }}</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <!-- Tiempos promedio -->
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-body">
                        <h6 class="card-title">Tiempos promedio (min)</h6>
                        <p class="mb-1">Firma paciente: <strong>{{ tiempos.minutos_promedio_firma_paciente ?? "—" }}</strong></p>
                        <p class="mb-0">Firma médico: <strong>{{ tiempos.minutos_promedio_firma_medico ?? "—" }}</strong></p>
                    </div>
                </div>
            </div>

            <!-- Productos populares -->
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-body">
                        <h6 class="card-title">Productos más usados</h6>
                        <ul class="list-group list-group-flush">
                            <li
                                v-for="p in populares"
                                :key="p.producto_id"
                                class="list-group-item d-flex justify-content-between px-0"
                            >
                                <span>{{ p.producto?.nom_produc || ("Producto " + p.producto_id) }}</span>
                                <span class="badge bg-primary">{{ p.total }}</span>
                            </li>
                            <li v-if="!populares.length" class="list-group-item text-muted px-0">Sin datos</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Por médico -->
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title">Por médico</h6>
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr><th>Médico</th><th>Total</th><th>Pendientes</th><th>Completos</th></tr>
                            </thead>
                            <tbody>
                                <tr v-for="m in porMedico" :key="m.medico_id">
                                    <td>{{ m.medico?.name || ("#" + m.medico_id) }}</td>
                                    <td>{{ m.total }}</td>
                                    <td>{{ m.pendientes }}</td>
                                    <td>{{ m.completos }}</td>
                                </tr>
                                <tr v-if="!porMedico.length">
                                    <td colspan="4" class="text-muted text-center">Sin datos</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from "vue";

import { consentimientos as api } from "../../../helpers/api.js";
import { handleAxiosErrorToast } from "../../../helpers/alerts.js";

const sedes = ref([]);
const productos = ref([]);
const filtros = reactive({ sede_id: null, producto_id: null, fecha_inicio: "", fecha_fin: "" });

const cargando = ref(false);
const resumen = reactive({
    total_generados: 0,
    completos: 0,
    expirados: 0,
    pendientes: 0,
    tasa_completado: 0,
});
const tiempos = reactive({
    minutos_promedio_firma_paciente: null,
    minutos_promedio_firma_medico: null,
});
const populares = ref([]);
const porMedico = ref([]);

const tarjetas = computed(() => [
    { label: "Generados", valor: resumen.total_generados },
    { label: "Completos", valor: resumen.completos },
    { label: "Expirados", valor: resumen.expirados },
    { label: "% Completado", valor: resumen.tasa_completado + "%" },
]);

function params() {
    const p = {};
    if (filtros.sede_id) p.sede_id = filtros.sede_id;
    if (filtros.producto_id) p.producto_id = filtros.producto_id;
    if (filtros.fecha_inicio) p.fecha_inicio = filtros.fecha_inicio;
    if (filtros.fecha_fin) p.fecha_fin = filtros.fecha_fin;
    return p;
}

async function cargarCombos() {
    try {
        const [rp, rs] = await Promise.all([api.productos(), api.sedesActivas()]);
        productos.value = rp.data?.data ?? rp.data ?? [];
        sedes.value = rs.data?.data ?? rs.data ?? [];
    } catch (e) {
        handleAxiosErrorToast(e, "No fue posible cargar catálogos.");
    }
}

async function cargar() {
    cargando.value = true;
    try {
        const p = params();
        const [rr, rt, rp, rm] = await Promise.all([
            api.estadResumen(p),
            api.estadTiempo(p),
            api.estadPopulares(p),
            api.estadMedico(p),
        ]);
        Object.assign(resumen, rr.data.data);
        Object.assign(tiempos, rt.data.data);
        populares.value = rp.data?.data ?? [];
        porMedico.value = rm.data?.data ?? [];
    } catch (e) {
        handleAxiosErrorToast(e, "No fue posible cargar estadísticas.");
    } finally {
        cargando.value = false;
    }
}

onMounted(async () => {
    await cargarCombos();
    await cargar();
});
</script>

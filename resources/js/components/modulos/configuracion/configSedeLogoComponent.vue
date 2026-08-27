<template>
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Logo e información por sede (encabezado de PDF)</h5>
            <p class="text-muted">
                El logo y la información aparecen en el encabezado de los PDF (consentimientos, etc.).
            </p>

            <div v-if="cargando" class="text-muted">Cargando…</div>

            <div v-else class="row g-3">
                <div v-for="sede in sedes" :key="sede.id" class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title">{{ sede.nom_sede }}</h6>

                            <div class="text-center mb-2">
                                <img
                                    v-if="sede.logo_1"
                                    :src="logoUrl(sede.logo_1)"
                                    alt="logo"
                                    style="max-height: 70px; max-width: 160px"
                                />
                                <div v-else class="text-muted small">Sin logo</div>
                            </div>

                            <div class="mb-2">
                                <label class="form-label">Logo (JPG/PNG, máx 2MB)</label>
                                <input
                                    type="file"
                                    accept="image/jpeg,image/png"
                                    class="form-control form-control-sm"
                                    @change="onFile(sede.id, $event)"
                                />
                            </div>

                            <div class="mb-2">
                                <label class="form-label">Encabezado (dirección, teléfono, NIT…)</label>
                                <textarea
                                    v-model="sede.encabezado"
                                    class="form-control form-control-sm"
                                    rows="3"
                                    placeholder="IPS ... S.A.S.&#10;Dirección&#10;Tel / NIT"
                                ></textarea>
                            </div>

                            <div class="mb-2">
                                <label class="form-label">Pie de página</label>
                                <textarea
                                    v-model="sede.pie_pagina"
                                    class="form-control form-control-sm"
                                    rows="2"
                                ></textarea>
                            </div>

                            <button
                                class="btn btn-primary btn-sm"
                                type="button"
                                :disabled="guardandoId === sede.id || !can('config.sedes.edit')"
                                @click="guardar(sede)"
                            >
                                <span v-if="guardandoId === sede.id">Guardando…</span>
                                <span v-else>Guardar</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref } from "vue";

import axios from "axios";

import usePermissions from "../../../helpers/permissions.js";
import { alertSuccess, handleAxiosErrorToast } from "../../../helpers/alerts.js";

const props = defineProps({
    permisos: { type: Array, default: () => [] },
});

const { can } = usePermissions(props.permisos);

const sedes = ref([]);
const cargando = ref(true);
const archivos = ref({});
const guardandoId = ref(null);

function logoUrl(logo) {
    return `/logos/${logo}?t=${Date.now()}`;
}

async function cargar() {
    cargando.value = true;
    try {
        const res = await axios.get("/api/config-sedes");
        sedes.value = res.data?.data ?? res.data ?? [];
    } catch (e) {
        handleAxiosErrorToast(e, "No fue posible cargar las sedes.");
    } finally {
        cargando.value = false;
    }
}

function onFile(sedeId, event) {
    archivos.value[sedeId] = event.target.files?.[0] ?? null;
}

async function guardar(sede) {
    guardandoId.value = sede.id;
    try {
        const fd = new FormData();
        if (archivos.value[sede.id]) fd.append("logo", archivos.value[sede.id]);
        fd.append("encabezado", sede.encabezado ?? "");
        fd.append("pie_pagina", sede.pie_pagina ?? "");

        const res = await axios.post(`/api/config-sedes/${sede.id}/branding`, fd, {
            headers: { "Content-Type": "multipart/form-data" },
        });
        if (res.data?.data?.logo_1) sede.logo_1 = res.data.data.logo_1;
        archivos.value[sede.id] = null;
        alertSuccess("Branding actualizado");
    } catch (e) {
        handleAxiosErrorToast(e, "No fue posible guardar.");
    } finally {
        guardandoId.value = null;
    }
}

onMounted(cargar);
</script>

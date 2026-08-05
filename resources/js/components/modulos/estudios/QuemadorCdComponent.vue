<template>
    <div class="quemador-cd">
        <loading-spinner :visible="loading" />

        <div v-if="!loading">
            <!-- Datos del estudio y paciente -->
            <div class="card border-info mb-3">
                <div class="card-header bg-info text-white py-2">
                    <i class="fa fa-file-medical"></i> Datos del Estudio
                </div>
                <div class="card-body py-2">
                    <div class="row">
                        <div class="col-md-6">
                            <small class="text-muted d-block">Paciente</small>
                            <strong>{{ estudio.pat_name || estudio.nom_pacien || "—" }}</strong>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">Documento</small>
                            <span>{{ estudio.pat_id || estudio.num_docu || "—" }}</span>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-6">
                            <small class="text-muted d-block">Estudio</small>
                            <span>{{ estudio.study_desc || estudio.nom_produc || "—" }}</span>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">Fecha del estudio</small>
                            <span>{{ estudio.study_datetime || estudio.fec_estudio || "—" }}</span>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-6">
                            <small class="text-muted d-block">Accession</small>
                            <span>{{ estudio.accession_no || "—" }}</span>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">Study UID</small>
                            <small class="text-monospace d-block" style="word-break: break-all">{{
                                estudio.study_iuid || "—"
                            }}</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Selector de unidad -->
            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="text-info mb-0">
                        <i class="fa fa-compact-disc"></i> Unidades de CD/DVD
                    </h6>
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-info"
                        @click="listarUnidades"
                        :disabled="loadingDrives"
                    >
                        <i class="fa fa-refresh"></i>
                    </button>
                </div>
                <div v-if="drives.length === 0" class="text-muted text-sm">
                    No se encontraron unidades de CD/DVD.
                </div>
                <div v-else>
                    <div v-for="drive in drives" :key="drive" class="form-check">
                        <input
                            class="form-check-input"
                            type="radio"
                            :id="`drive-${drive}`"
                            :value="drive"
                            v-model="selectedDrive"
                        />
                        <label class="form-check-label" :for="`drive-${drive}`">
                            Unidad {{ drive }}
                        </label>
                    </div>
                </div>
            </div>

            <div v-if="burnStatus" class="mt-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <small class="text-muted">{{ statusLabel }}</small>
                    <small class="text-muted">{{ burnStatus.progress }}%</small>
                </div>
                <div class="progress" style="height: 20px">
                    <div
                        class="progress-bar"
                        :class="progressBarClass"
                        role="progressbar"
                        :style="{ width: burnStatus.progress + '%' }"
                        :aria-valuenow="burnStatus.progress"
                        aria-valuemin="0"
                        aria-valuemax="100"
                    >
                        {{ statusLabel }}
                    </div>
                </div>
                <div v-if="burnStatus.error_message" class="alert alert-danger mt-2 mb-0 py-2">
                    <small>{{ burnStatus.error_message }}</small>
                </div>
            </div>

            <div class="form-actions mt-3">
                <button
                    type="button"
                    class="btn btn-success m-r-5"
                    @click="iniciarQuemado"
                    :disabled="!canBurn || burning"
                >
                    <i v-if="burning" class="fa fa-spinner fa-spin"></i>
                    <i v-else class="fa fa-compact-disc"></i>
                    {{ burning ? "Quemando..." : "Iniciar quemado" }}
                </button>

                <button
                    type="button"
                    class="btn btn-inverse"
                    @click="$emit('cerrar')"
                    :disabled="burning"
                >
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from "vue";
import LoadingSpinner from "@/components/comunes/LoadingSpinner.vue";
import axios from "axios";

const props = defineProps({
    estudio: { type: Object, required: true },
    usuarioactual: { type: Object, required: true },
});

const emit = defineEmits(["cerrar", "completado"]);

const loading = ref(true);
const loadingDrives = ref(false);
const burning = ref(false);
const drives = ref([]);
const selectedDrive = ref(null);
const burnStatus = ref(null);
const burnId = ref(null);
let pollTimer = null;

const canBurn = computed(() => {
    return selectedDrive.value && !burning.value;
});

const progressBarClass = computed(() => {
    if (!burnStatus.value) return "bg-info";
    const s = burnStatus.value.status;
    if (s === "burned") return "bg-success";
    if (s === "failed") return "bg-danger";
    if (s === "burning") return "bg-warning";
    return "bg-info";
});

const statusLabel = computed(() => {
    if (!burnStatus.value) return "";
    const s = burnStatus.value.status;
    const p = burnStatus.value.progress || 0;
    const labels = {
        pending: "Pendiente",
        processing: p <= 40 ? "Descargando archivos DICOM..." : "Preparando paquete...",
        burning: p <= 70 ? "Creando imagen de disco..." : "Quemando disco...",
        completed: "Paquete listo",
        burned: "Disco quemado exitosamente",
        failed: "Fallido",
    };
    return labels[s] || s;
});

function startPolling() {
    stopPolling();
    pollTimer = setInterval(async () => {
        if (!burnId.value) return;
        try {
            const res = await axios.get(`/api/dicom/burn-status/${burnId.value}`);
            if (res.data.status === "success") {
                const data = res.data.data;
                burnStatus.value = {
                    status: data.status,
                    progress: data.progress || 0,
                    error_message: data.error_message,
                };

                if (data.status === "burned" || data.status === "failed") {
                    stopPolling();
                    burning.value = false;
                    if (data.status === "burned") {
                        emit("completado");
                    }
                }
            }
        } catch (e) {
            console.error("Error polling status:", e);
        }
    }, 2000);
}

function stopPolling() {
    if (pollTimer) {
        clearInterval(pollTimer);
        pollTimer = null;
    }
}

async function listarUnidades() {
    loadingDrives.value = true;
    try {
        const res = await axios.get("/api/dicom/list-drives");
        if (res.data.status === "success") {
            drives.value = res.data.data || [];
            if (drives.value.length > 0 && !selectedDrive.value) {
                selectedDrive.value = drives.value[0];
            }
        }
    } catch (e) {
        console.error("Error listando unidades:", e);
        drives.value = [];
    } finally {
        loadingDrives.value = false;
    }
}

async function iniciarQuemado() {
    if (!canBurn.value) return;
    burning.value = true;
    burnStatus.value = { status: "processing", progress: 5 };

    try {
        const studyUid = props.estudio.study_iuid || props.estudio.study_iuid;
        if (!studyUid) {
            throw new Error("No hay Study Instance UID disponible para este estudio.");
        }

        const sedeId = sessionStorage.getItem("ST-sede");
        if (!sedeId) {
            throw new Error("No se encontró la sede seleccionada.");
        }

        const resGenerate = await axios.post("/api/dicom/generate-iso", {
            study_instance_uids: [String(studyUid)],
            user_id: props.usuarioactual.id || null,
            device_letter: selectedDrive.value,
            sede_id: parseInt(sedeId, 10),
        });

        if (resGenerate.data.status !== "success") {
            throw new Error(resGenerate.data.message || "Error al generar paquete DICOM.");
        }

        const { burn_id } = resGenerate.data.data;
        burnId.value = burn_id;
        burnStatus.value = { status: "processing", progress: 45 };

        const resBurn = await axios.post("/api/dicom/burn", {
            burn_id: burn_id,
            device_letter: selectedDrive.value,
        });

        if (resBurn.data.status === "success") {
            burnStatus.value = { status: "burned", progress: 100 };
            stopPolling();
            emit("completado");
        } else {
            throw new Error(resBurn.data.message || "Error al quemar disco.");
        }
    } catch (e) {
        console.error("Error en quemado:", e);
        burnStatus.value = {
            status: "failed",
            progress: 0,
            error_message: e.response?.data?.message || e.message,
        };
        stopPolling();
    } finally {
        burning.value = false;
    }
}

onMounted(async () => {
    await listarUnidades();
    loading.value = false;
});

onBeforeUnmount(() => {
    stopPolling();
});
</script>

<style scoped>
.quemador-cd {
    padding: 10px;
}
.progress {
    border-radius: 10px;
}
.progress-bar {
    transition: width 0.5s ease;
    font-size: 0.75rem;
    line-height: 20px;
}
</style>

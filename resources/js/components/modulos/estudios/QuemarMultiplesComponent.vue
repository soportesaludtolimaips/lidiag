<template>
    <div class="quemar-multiples">
        <loading-spinner :visible="loading" />

        <div v-if="!loading">
            <!-- Lista de estudios -->
            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="text-info mb-0">
                        <i class="fa fa-list"></i> Estudios seleccionados ({{
                            estudiosLocales.length
                        }})
                    </h6>
                    <small class="text-muted">Arrastra para reordenar</small>
                </div>

                <div v-if="estudiosLocales.length === 0" class="text-muted text-center py-3">
                    <i class="fa fa-inbox fa-2x m-b-10"></i>
                    <p>No hay estudios seleccionados.</p>
                </div>

                <draggable
                    v-else
                    v-model="estudiosLocales"
                    item-key="study_pk"
                    handle=".drag-handle"
                    animation="200"
                    ghost-class="ghost"
                >
                    <template #item="{ element, index }">
                        <div class="card border-light mb-2 study-card">
                            <div class="card-body py-2 px-3">
                                <div class="d-flex align-items-center">
                                    <i
                                        class="fa fa-arrows-alt text-muted drag-handle mr-2"
                                        style="cursor: grab"
                                        title="Arrastrar para reordenar"
                                    ></i>
                                    <div class="flex-grow-1 min-width-0">
                                        <div class="d-flex justify-content-between">
                                            <strong class="text-truncate" style="max-width: 200px">
                                                {{ element.pat_name || "—" }}
                                            </strong>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-link text-danger p-0 ml-2"
                                                @click="quitarEstudio(index)"
                                                title="Quitar estudio"
                                            >
                                                <i class="fa fa-times"></i>
                                            </button>
                                        </div>
                                        <small class="text-muted d-block text-truncate">
                                            {{ element.study_desc || "—" }}
                                        </small>
                                        <small class="text-muted">
                                            {{ element.study_datetime || "—" }}
                                            <span v-if="element.pat_id" class="ml-2">
                                                Doc: {{ element.pat_id }}
                                            </span>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </draggable>
            </div>

            <!-- Advertencia de pacientes diferentes -->
            <div v-if="pacientesDiferentes" class="alert alert-warning py-2 mb-3">
                <small>
                    <i class="fa fa-exclamation-triangle"></i>
                    Los estudios pertenecen a pacientes diferentes.
                </small>
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
                        <i :class="loadingDrives ? 'fa fa-spinner fa-spin' : 'fa fa-refresh'"></i>
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
                            :id="`drive-multi-${drive}`"
                            :value="drive"
                            v-model="selectedDrive"
                        />
                        <label class="form-check-label" :for="`drive-multi-${drive}`">
                            Unidad {{ drive }}
                        </label>
                    </div>
                </div>
            </div>

            <!-- Progress -->
            <div v-if="burnStatus" class="mt-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <small class="text-muted">{{ statusLabel }}</small>
                    <small class="text-muted">{{ progressPct }}%</small>
                </div>
                <div class="progress" style="height: 20px">
                    <div
                        class="progress-bar"
                        :class="progressBarClass"
                        role="progressbar"
                        :style="{ width: progressPct + '%' }"
                        :aria-valuenow="progressPct"
                        aria-valuemin="0"
                        aria-valuemax="100"
                    >
                        {{ statusLabel }}
                    </div>
                </div>
                <div
                    v-if="burnStatusError"
                    class="alert alert-danger mt-2 mb-0 py-2"
                    style="max-height: 120px; overflow-y: auto"
                >
                    <small>{{ burnStatusError }}</small>
                </div>
            </div>

            <!-- Info del paquete -->
            <div v-if="packageInfo" class="alert alert-info mt-2 mb-0 py-2">
                <small>
                    <i class="fa fa-info-circle"></i>
                    {{ packageInfo.totalFiles }} archivo(s) descargado(s)
                    <span v-if="packageInfo.failedDownloads > 0" class="text-warning">
                        ({{ packageInfo.failedDownloads }} fallido(s))
                    </span>
                </small>
            </div>

            <!-- Botones de acción -->
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
                    v-if="burnStatus && burnStatusStatus === 'failed'"
                    type="button"
                    class="btn btn-warning m-r-5"
                    @click="iniciarQuemado"
                    :disabled="!canBurn"
                >
                    <i class="fa fa-redo"></i> Reintentar
                </button>

                <button
                    type="button"
                    class="btn btn-outline-secondary"
                    @click="cerrar"
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
import draggable from "vuedraggable";
import LoadingSpinner from "@/components/comunes/LoadingSpinner.vue";
import { confirmAction, toastMessage } from "@/helpers/alerts.js";
import axios from "axios";

const DICOM_UID_RE = /^([0-9]+\.)*[0-9]+$/;

const STATUS_LABELS = {
    pending: "Pendiente",
    processing: "Descargando archivos DICOM...",
    burning: "Quemando disco...",
    completed: "Paquete listo",
    burned: "Disco quemado exitosamente",
    failed: "Fallido",
};

const MAX_POLL_MINUTES = 10;

const props = defineProps({
    estudios: { type: Array, required: true },
    usuarioactual: { type: Object, required: true },
});

const emit = defineEmits(["cerrar", "completado", "error"]);

const loading = ref(true);
const loadingDrives = ref(false);
const burning = ref(false);
const drives = ref([]);
const selectedDrive = ref(null);
const burnStatusStatus = ref(null);
const burnStatusProgress = ref(0);
const burnStatusError = ref(null);
const burnId = ref(null);
const packageInfo = ref(null);
const estudiosLocales = ref([]);
let pollTimer = null;
let pollTimeout = null;

const burnStatus = computed(() => {
    if (!burnStatusStatus.value) return null;
    return {
        status: burnStatusStatus.value,
        progress: burnStatusProgress.value,
        error_message: burnStatusError.value,
    };
});

const progressPct = computed(() => {
    const p = Number(burnStatusProgress.value);
    if (!Number.isFinite(p)) return 0;
    return Math.min(100, Math.max(0, Math.round(p)));
});

const canBurn = computed(() => {
    return selectedDrive.value && !burning.value && estudiosLocales.value.length > 0;
});

const progressBarClass = computed(() => {
    const s = burnStatusStatus.value;
    if (s === "burned") return "bg-success";
    if (s === "failed") return "bg-danger";
    if (s === "burning") return "bg-warning";
    return "bg-info";
});

const statusLabel = computed(() => {
    const s = burnStatusStatus.value;
    if (!s) return "";
    const p = progressPct.value;
    if (s === "processing") {
        return p <= 40 ? STATUS_LABELS.processing : "Preparando paquete...";
    }
    if (s === "burning") {
        return p <= 70 ? "Creando imagen de disco..." : STATUS_LABELS.burning;
    }
    return STATUS_LABELS[s] || s;
});

const pacientesDiferentes = computed(() => {
    const patIds = new Set(estudiosLocales.value.map((e) => e.pat_id).filter(Boolean));
    return patIds.size > 1;
});

function normalizeDeviceLetter(drive) {
    return String(drive).replace(/:$/, "").toUpperCase().trim();
}

function isValidDicomUid(uid) {
    return DICOM_UID_RE.test(String(uid));
}

function startPolling() {
    stopPolling();
    pollTimeout = setTimeout(
        () => {
            stopPolling();
            burnStatusStatus.value = "failed";
            burnStatusProgress.value = 0;
            burnStatusError.value = `La operación de quemado no respondió después de ${MAX_POLL_MINUTES} minutos.`;
            burning.value = false;
            emit("error", burnStatusError.value);
        },
        MAX_POLL_MINUTES * 60 * 1000
    );

    pollTimer = setInterval(async () => {
        if (!burnId.value) return;
        try {
            const res = await axios.get(`/api/dicom/burn-status/${burnId.value}`);
            if (res.data.status === "success") {
                const data = res.data.data;
                burnStatusStatus.value = data.status;
                burnStatusProgress.value = data.progress || 0;
                burnStatusError.value = data.error_message || null;

                if (data.status === "burned" || data.status === "failed") {
                    stopPolling();
                    burning.value = false;
                    if (data.status === "burned") {
                        emit("completado");
                    }
                }
            }
        } catch (_e) {
            toastMessage({
                heading: "Error de conexión",
                text: "No se pudo consultar el estado del quemado.",
                icon: "error",
            });
        }
    }, 2000);
}

function stopPolling() {
    if (pollTimer) {
        clearInterval(pollTimer);
        pollTimer = null;
    }
    if (pollTimeout) {
        clearTimeout(pollTimeout);
        pollTimeout = null;
    }
}

function resetBurnState() {
    burnStatusStatus.value = null;
    burnStatusProgress.value = 0;
    burnStatusError.value = null;
    burnId.value = null;
    packageInfo.value = null;
}

function quitarEstudio(index) {
    estudiosLocales.value.splice(index, 1);
}

async function listarUnidades() {
    loadingDrives.value = true;
    try {
        const res = await axios.get("/api/dicom/list-drives");
        if (res.data.status === "success") {
            drives.value = res.data.data || [];
            const sigueDisponible =
                selectedDrive.value && drives.value.includes(selectedDrive.value);
            if (!sigueDisponible) {
                selectedDrive.value = drives.value.length > 0 ? drives.value[0] : null;
            }
        }
    } catch (_e) {
        toastMessage({
            heading: "Error",
            text: "No fue posible consultar las unidades de CD/DVD del servidor.",
            icon: "error",
        });
        drives.value = [];
        selectedDrive.value = null;
    } finally {
        loadingDrives.value = false;
    }
}

async function iniciarQuemado() {
    if (!canBurn.value || burning.value) return;

    const totalEstudios = estudiosLocales.value.length;
    const ok = await confirmAction({
        title: "Quemar estudios en CD/DVD",
        text: `Se grabarán ${totalEstudios} estudio(s) en la unidad ${selectedDrive.value}. Esta acción no puede detenerse. ¿Continuar?`,
        icon: "warning",
    });
    if (!ok) return;

    burning.value = true;
    burnStatusStatus.value = "processing";
    burnStatusProgress.value = 5;
    burnStatusError.value = null;
    packageInfo.value = null;

    try {
        const uids = estudiosLocales.value
            .map((e) => String(e.study_iuid))
            .filter((uid) => uid && isValidDicomUid(uid));

        if (uids.length === 0) {
            throw new Error("No hay Study Instance UID válido para los estudios seleccionados.");
        }

        const invalidUids = estudiosLocales.value
            .filter((e) => e.study_iuid && !isValidDicomUid(e.study_iuid))
            .map((e) => e.study_iuid);

        if (invalidUids.length > 0) {
            throw new Error(
                `UIDs con formato inválido: ${invalidUids.join(", ")}. Deben ser OID (ej. 1.2.840...).`
            );
        }

        const sedeId = sessionStorage.getItem("ST-sede");
        if (!sedeId) {
            throw new Error("No se encontró la sede seleccionada.");
        }

        if (!props.usuarioactual?.id) {
            toastMessage({
                heading: "Advertencia",
                text: "No se identificó el usuario actual. El quemado se registrará sin usuario.",
                icon: "warning",
            });
        }

        const normalizedDrive = normalizeDeviceLetter(selectedDrive.value);

        const resGenerate = await axios.post("/api/dicom/generate-iso", {
            study_instance_uids: uids,
            user_id: props.usuarioactual?.id ?? null,
            device_letter: normalizedDrive,
            sede_id: parseInt(sedeId, 10),
        });

        if (resGenerate.data.status !== "success") {
            throw new Error(resGenerate.data.message || "Error al generar paquete DICOM.");
        }

        const { burn_id, total_files, failed_downloads } = resGenerate.data.data ?? {};
        if (!burn_id) {
            throw new Error("El servidor no devolvió el identificador del quemado.");
        }

        packageInfo.value = {
            totalFiles: total_files || 0,
            failedDownloads: failed_downloads || 0,
        };

        burnId.value = burn_id;
        burnStatusStatus.value = "processing";
        burnStatusProgress.value = 45;

        const resBurn = await axios.post("/api/dicom/burn", {
            burn_id: burn_id,
            device_letter: normalizedDrive,
        });

        if (resBurn.data.status !== "success") {
            throw new Error(resBurn.data.message || "Error al iniciar el quemado del disco.");
        }

        burnStatusStatus.value = "burning";
        burnStatusProgress.value = 70;
        startPolling();
    } catch (e) {
        const errMsg = e.response?.data?.message || e.message;
        burnStatusStatus.value = "failed";
        burnStatusProgress.value = 0;
        burnStatusError.value = errMsg;
        stopPolling();
        burning.value = false;
        emit("error", errMsg);
        toastMessage({
            heading: "Error en quemado",
            text: errMsg,
            icon: "error",
        });
    }
}

function cerrar() {
    stopPolling();
    resetBurnState();
    emit("cerrar");
}

onMounted(async () => {
    estudiosLocales.value = props.estudios.map((e) => ({ ...e }));
    await listarUnidades();
    loading.value = false;
});

onBeforeUnmount(() => {
    stopPolling();
});
</script>

<style scoped>
.quemar-multiples {
    padding: 10px;
}
.study-card {
    transition: box-shadow 0.2s;
}
.study-card:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}
.ghost {
    opacity: 0.5;
    background: #c8ebfb;
}
.min-width-0 {
    min-width: 0;
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

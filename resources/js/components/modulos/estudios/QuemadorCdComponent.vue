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
                        <i :class="loadingDrives ? 'fa fa-spinner fa-spin' : 'fa fa-refresh'"></i>
                    </button>
                </div>
                <div v-if="drives.length === 0" class="text-muted text-sm">
                    No se encontraron unidades de CD/DVD.
                </div>
                <div v-else>
                    <div
                        v-for="drive in drives"
                        :key="drive.drive"
                        class="drive-option"
                        :class="{ selected: selectedDrive === drive.drive }"
                    >
                        <div class="form-check d-flex align-items-center">
                            <input
                                class="form-check-input"
                                type="radio"
                                :id="`drive-${drive.drive}`"
                                :value="drive.drive"
                                v-model="selectedDrive"
                            />
                            <label
                                class="form-check-label flex-grow-1 d-flex align-items-center"
                                :for="`drive-${drive.drive}`"
                            >
                                Unidad {{ drive.drive }}
                                <span class="badge ml-2" :class="getDiscTypeBadge(drive).class">
                                    {{ getDiscTypeBadge(drive).text }}
                                </span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- CD capacity warning -->
                <div v-if="discWarning" class="alert alert-warning py-1 px-2 mt-2 mb-0">
                    <small>
                        <i class="fa fa-exclamation-triangle mr-1"></i>
                        {{ discWarning }}
                    </small>
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

            <!-- Información del paquete tras generate-iso -->
            <div v-if="packageInfo" class="alert alert-info mt-2 mb-0 py-2">
                <small>
                    <i class="fa fa-info-circle"></i>
                    {{ packageInfo.totalFiles }} archivo(s) descargado(s)
                    <span v-if="packageInfo.failedDownloads > 0" class="text-warning">
                        ({{ packageInfo.failedDownloads }} fallido(s))
                    </span>
                </small>
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
    estudio: { type: Object, required: true },
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
let pollTimer = null;
let pollTimeout = null;
let driveRefreshTimer = null;

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
    if (!selectedDrive.value || burning.value) return false;
    const driveInfo = getSelectedDriveInfo();
    if (driveInfo && !driveInfo.media_loaded) return false;
    return true;
});

const discWarning = computed(() => {
    const driveInfo = getSelectedDriveInfo();
    if (!driveInfo || !driveInfo.media_loaded) return null;
    if (driveInfo.disc_type === "CD") {
        return "El medio detectado es CD-R (~700 MB). Si el contenido excede esta capacidad, el quemado fallará.";
    }
    return null;
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

    let pollErrors = 0;
    pollTimer = setInterval(async () => {
        if (!burnId.value) return;
        try {
            const res = await axios.get(`/api/dicom/burn-status/${burnId.value}`);
            pollErrors = 0;
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
            pollErrors++;
            if (pollErrors === 3) {
                toastMessage({
                    heading: "Error de conexión",
                    text: "No se pudo consultar el estado del quemado.",
                    icon: "error",
                });
            }
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

async function listarUnidades(silent = false) {
    loadingDrives.value = true;
    try {
        const res = await axios.get("/api/dicom/list-drives");
        if (res.data.status === "success") {
            drives.value = res.data.data || [];
            const sigueDisponible =
                selectedDrive.value && drives.value.some((d) => d.drive === selectedDrive.value);
            if (!sigueDisponible) {
                const primerDisponible = drives.value.find((d) => d.media_loaded);
                selectedDrive.value = primerDisponible
                    ? primerDisponible.drive
                    : drives.value.length > 0
                      ? drives.value[0].drive
                      : null;
            }
        }
    } catch (_e) {
        if (!silent) {
            toastMessage({
                heading: "Error",
                text: "No fue posible consultar las unidades de CD/DVD del servidor.",
                icon: "error",
            });
        }
        drives.value = [];
        selectedDrive.value = null;
    } finally {
        loadingDrives.value = false;
    }
}

function getDiscTypeBadge(drive) {
    if (!drive.media_loaded) return { text: "Vacío", class: "badge-secondary" };
    if (drive.disc_type === "CD") return { text: "CD", class: "badge-info" };
    if (drive.disc_type === "DVD") return { text: "DVD", class: "badge-success" };
    return { text: "?", class: "badge-warning" };
}

function getSelectedDriveInfo() {
    return drives.value.find((d) => d.drive === selectedDrive.value) || null;
}

async function iniciarQuemado() {
    if (!canBurn.value || burning.value) return;

    const ok = await confirmAction({
        title: "Quemar estudio en CD/DVD",
        text: `Se grabará el estudio en la unidad ${selectedDrive.value}. Esta acción no puede detenerse. ¿Continuar?`,
        icon: "warning",
    });
    if (!ok) return;

    burning.value = true;
    burnStatusStatus.value = "processing";
    burnStatusProgress.value = 5;
    burnStatusError.value = null;
    packageInfo.value = null;

    try {
        const studyUid = props.estudio.study_iuid;
        if (!studyUid) {
            throw new Error("No hay Study Instance UID disponible para este estudio.");
        }

        if (!isValidDicomUid(studyUid)) {
            throw new Error(
                `El Study Instance UID no tiene un formato válido: "${studyUid}". Debe ser un OID (ej. 1.2.840...).`
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
            study_instance_uids: [String(studyUid)],
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
    } catch (error) {
        const errMsg = error.response?.data?.message || error.message;
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
    await listarUnidades();
    loading.value = false;
    driveRefreshTimer = setInterval(() => {
        if (!burning.value) {
            listarUnidades(true);
        }
    }, 5000);
});

onBeforeUnmount(() => {
    stopPolling();
    if (driveRefreshTimer) {
        clearInterval(driveRefreshTimer);
        driveRefreshTimer = null;
    }
});
</script>

<style scoped>
.quemador-cd {
    padding: 10px;
}
.drive-option {
    padding: 6px 8px;
    border-radius: 6px;
    border: 1px solid #e9ecef;
    margin-bottom: 4px;
    transition: all 0.2s;
}
.drive-option:hover {
    background: #f8f9fa;
}
.drive-option.selected {
    background: #e3f2fd;
    border-color: #2196f3;
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

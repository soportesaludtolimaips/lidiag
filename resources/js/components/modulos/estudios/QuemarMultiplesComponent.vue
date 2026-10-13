<template>
    <div class="quemar-multiples">
        <loading-spinner :visible="loading" />

        <div v-if="!loading">
            <!-- Lista de estudios -->
            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="text-dark mb-0">
                        <i class="fa fa-files-o text-info mr-1"></i>
                        <span class="font-weight-bold">Estudios</span>
                        <span class="badge badge-info ml-1">{{ estudiosLocales.length }}</span>
                    </h6>
                    <small class="text-muted" v-if="estudiosLocales.length > 1">
                        <i class="fa fa-arrows-alt mr-1"></i> Arrastrar
                    </small>
                </div>

                <!-- Resumen rápido -->
                <div
                    v-if="estudiosLocales.length > 0"
                    class="d-flex align-items-center mb-2 px-2 study-summary"
                >
                    <small class="text-muted">
                        <i class="fa fa-user-o mr-1"></i>
                        {{ pacientesUnicos }} paciente(s)
                    </small>
                    <span class="mx-2 text-muted">·</span>
                    <small class="text-muted">
                        <i class="fa fa-file-o mr-1"></i>
                        {{ estudiosLocales.length }} estudio(s)
                    </small>
                </div>

                <div
                    v-if="estudiosLocales.length === 0"
                    class="text-muted text-center py-4 empty-state"
                >
                    <i class="fa fa-inbox fa-3x mb-2 opacity-25"></i>
                    <p class="mb-0">No hay estudios seleccionados</p>
                    <small>Seleccione estudios de la tabla para quemar</small>
                </div>

                <draggable
                    v-else
                    v-model="estudiosLocales"
                    item-key="study_pk"
                    handle=".drag-handle"
                    animation="250"
                    ghost-class="ghost"
                >
                    <template #item="{ element, index }">
                        <div class="study-item mb-2">
                            <div class="d-flex align-items-start">
                                <!-- Drag handle + order number -->
                                <div class="study-order d-flex flex-column align-items-center mr-2">
                                    <i
                                        class="fa fa-grip-vertical text-muted drag-handle"
                                        title="Arrastrar para reordenar"
                                    ></i>
                                    <span class="order-badge mt-1">{{ index + 1 }}</span>
                                </div>

                                <!-- Patient initial circle -->
                                <div class="patient-avatar mr-2">
                                    {{ getInitials(element.pat_name) }}
                                </div>

                                <!-- Study info -->
                                <div class="flex-grow-1 min-width-0">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="min-width-0 flex-grow-1">
                                            <div class="study-patient-name text-truncate">
                                                {{ element.pat_name || "—" }}
                                            </div>
                                            <div class="study-desc text-truncate">
                                                {{ element.study_desc || "Sin descripción" }}
                                            </div>
                                        </div>
                                        <button
                                            type="button"
                                            class="btn btn-sm remove-btn ml-2"
                                            @click="quitarEstudio(index)"
                                            title="Quitar estudio"
                                        >
                                            <i class="fa fa-times"></i>
                                        </button>
                                    </div>
                                    <div class="study-meta mt-1">
                                        <span v-if="element.study_datetime">
                                            <i class="fa fa-calendar-o mr-1"></i
                                            >{{ element.study_datetime }}
                                        </span>
                                        <span v-if="element.pat_id" class="ml-2">
                                            <i class="fa fa-id-card-o mr-1"></i>{{ element.pat_id }}
                                        </span>
                                        <span v-if="element.accession_no" class="ml-2">
                                            <i class="fa fa-hashtag mr-1"></i
                                            >{{ element.accession_no }}
                                        </span>
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
                                :id="`drive-multi-${drive.drive}`"
                                :value="drive.drive"
                                v-model="selectedDrive"
                            />
                            <label
                                class="form-check-label flex-grow-1 d-flex align-items-center"
                                :for="`drive-multi-${drive.drive}`"
                            >
                                Unidad {{ drive.drive }}
                                <span class="badge ml-2" :class="getDiscTypeBadge(drive).class">
                                    {{ getDiscTypeBadge(drive).text }}
                                </span>
                                <small
                                    v-if="drive.recorder_name"
                                    class="text-muted ml-2 d-none d-md-inline"
                                >
                                    {{ drive.recorder_name }}
                                </small>
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
            <div v-if="burnStatus" class="mt-3 burn-progress-section">
                <!-- Step indicators -->
                <div class="d-flex justify-content-between mb-3 burn-steps">
                    <div
                        class="burn-step"
                        :class="{ active: stepIndex >= 1, current: stepIndex === 1 }"
                    >
                        <div class="step-circle">
                            <i v-if="stepIndex > 1" class="fa fa-check"></i>
                            <i v-else-if="stepIndex === 1" class="fa fa-spinner fa-spin"></i>
                            <span v-else>1</span>
                        </div>
                        <small>Descargar</small>
                    </div>
                    <div class="step-line" :class="{ active: stepIndex >= 2 }"></div>
                    <div
                        class="burn-step"
                        :class="{ active: stepIndex >= 2, current: stepIndex === 2 }"
                    >
                        <div class="step-circle">
                            <i v-if="stepIndex > 2" class="fa fa-check"></i>
                            <i v-else-if="stepIndex === 2" class="fa fa-spinner fa-spin"></i>
                            <span v-else>2</span>
                        </div>
                        <small>Preparar</small>
                    </div>
                    <div class="step-line" :class="{ active: stepIndex >= 3 }"></div>
                    <div
                        class="burn-step"
                        :class="{ active: stepIndex >= 3, current: stepIndex === 3 }"
                    >
                        <div class="step-circle">
                            <i v-if="stepIndex > 3" class="fa fa-check"></i>
                            <i v-else-if="stepIndex === 3" class="fa fa-spinner fa-spin"></i>
                            <span v-else>3</span>
                        </div>
                        <small>Grabar</small>
                    </div>
                    <div class="step-line" :class="{ active: stepIndex >= 4 }"></div>
                    <div
                        class="burn-step"
                        :class="{ active: stepIndex >= 4, current: stepIndex === 4 }"
                    >
                        <div class="step-circle">
                            <i v-if="stepIndex === 4" class="fa fa-check"></i>
                            <span v-else>4</span>
                        </div>
                        <small>Listo</small>
                    </div>
                </div>

                <!-- Progress bar -->
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <small class="font-weight-bold" :class="statusTextClass">
                        <i :class="statusIconClass" class="mr-1"></i>
                        {{ statusLabel }}
                    </small>
                    <small class="font-weight-bold" :class="statusTextClass">
                        {{ progressPct }}%
                    </small>
                </div>
                <div class="progress burn-progress">
                    <div
                        class="progress-bar burn-progress-bar"
                        :class="progressBarClass"
                        role="progressbar"
                        :style="{ width: progressPct + '%' }"
                        :aria-valuenow="progressPct"
                        aria-valuemin="0"
                        aria-valuemax="100"
                    ></div>
                </div>

                <!-- Detail message -->
                <div v-if="burnStatusDetail" class="mt-2 burn-detail">
                    <small class="text-muted">
                        <i class="fa fa-info-circle mr-1"></i>
                        {{ burnStatusDetail }}
                    </small>
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
    if (!selectedDrive.value || burning.value || estudiosLocales.value.length === 0) return false;
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
    if (s === "burned") return "bg-success burn-bar-success";
    if (s === "failed") return "bg-danger burn-bar-danger";
    if (s === "burning") return "bg-warning burn-bar-warning";
    return "bg-info burn-bar-info";
});

const statusTextClass = computed(() => {
    const s = burnStatusStatus.value;
    if (s === "burned") return "text-success";
    if (s === "failed") return "text-danger";
    if (s === "burning") return "text-warning";
    return "text-info";
});

const statusIconClass = computed(() => {
    const s = burnStatusStatus.value;
    const p = progressPct.value;
    if (s === "burned") return "fa fa-check-circle";
    if (s === "failed") return "fa fa-times-circle";
    if (s === "burning") return "fa fa-fire";
    if (s === "processing") return p <= 40 ? "fa fa-download" : "fa fa-cog";
    return "fa fa-spinner fa-spin";
});

const stepIndex = computed(() => {
    const s = burnStatusStatus.value;
    const p = progressPct.value;
    if (!s) return 0;
    if (s === "burned") return 4;
    if (s === "failed") return 0;
    if (s === "burning") return p <= 70 ? 3 : 3;
    if (s === "processing") return p <= 40 ? 1 : 2;
    return 0;
});

const burnStatusDetail = computed(() => {
    const s = burnStatusStatus.value;
    const p = progressPct.value;
    if (s === "processing" && p <= 40) {
        return "Descargando imágenes DICOM del servidor PACS...";
    }
    if (s === "processing" && p > 40) {
        return "Preparando imagen de disco...";
    }
    if (s === "burning" && p <= 70) {
        return "Creando imagen de disco virtual...";
    }
    if (s === "burning" && p > 70) {
        return "Escribiendo datos en el medio óptico...";
    }
    if (s === "burned") {
        return "El disco ha sido quemado exitosamente. Puede retirar el medio.";
    }
    if (s === "failed") {
        return "Error durante el proceso de quemado. Verifique el disco y la unidad.";
    }
    return null;
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

const pacientesUnicos = computed(() => {
    const patIds = new Set(estudiosLocales.value.map((e) => e.pat_id).filter(Boolean));
    return patIds.size || 1;
});

function getInitials(name) {
    if (!name) return "?";
    const parts = name.trim().split(/\s+/);
    if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase();
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
}

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
    estudiosLocales.value = props.estudios.map((e) => ({ ...e }));
    await listarUnidades();
    loading.value = false;
    // Auto-refresh drives every 5 seconds
    driveRefreshTimer = setInterval(() => {
        if (!burning.value) {
            listarUnidades();
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
.quemar-multiples {
    padding: 10px;
}

/* Empty state */
.empty-state {
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    background: #f8f9fa;
}

/* Summary bar */
.study-summary {
    background: #f8f9fa;
    border-radius: 6px;
    padding: 6px 10px;
    border-left: 3px solid #17a2b8;
}

/* Study item */
.study-item {
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 10px 12px;
    transition: all 0.2s ease;
}
.study-item:hover {
    border-color: #17a2b8;
    box-shadow: 0 2px 8px rgba(23, 162, 184, 0.12);
}

/* Order column */
.study-order {
    min-width: 24px;
}
.study-order .drag-handle {
    cursor: grab;
    font-size: 1rem;
    opacity: 0.4;
    transition: opacity 0.2s;
}
.study-item:hover .drag-handle {
    opacity: 0.8;
}
.order-badge {
    font-size: 0.65rem;
    font-weight: 700;
    color: #6c757d;
    background: #e9ecef;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-top: 2px;
}

/* Patient avatar */
.patient-avatar {
    width: 36px;
    height: 36px;
    min-width: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, #17a2b8, #20c997);
    color: #fff;
    font-size: 0.7rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    letter-spacing: 0.5px;
}

/* Study info */
.study-patient-name {
    font-weight: 600;
    font-size: 0.85rem;
    color: #212529;
    line-height: 1.2;
}
.study-desc {
    font-size: 0.75rem;
    color: #6c757d;
    line-height: 1.3;
}
.study-meta {
    font-size: 0.7rem;
    color: #adb5bd;
}
.study-meta i {
    font-size: 0.65rem;
}

/* Remove button */
.remove-btn {
    color: #dc3545;
    opacity: 0.3;
    transition: all 0.2s;
    padding: 2px 4px !important;
    border-radius: 4px;
}
.remove-btn:hover {
    opacity: 1;
    background: rgba(220, 53, 69, 0.1);
}

/* Drive options */
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

/* Ghost (drag placeholder) */
.ghost {
    opacity: 0.4;
    background: #c8ebfb;
    border: 2px dashed #17a2b8;
    border-radius: 8px;
}

.min-width-0 {
    min-width: 0;
}

/* Burn steps */
.burn-steps {
    align-items: flex-start;
}
.burn-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
}
.burn-step .step-circle {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 600;
    background: #e9ecef;
    color: #6c757d;
    transition: all 0.3s ease;
}
.burn-step.active .step-circle {
    background: #17a2b8;
    color: #fff;
}
.burn-step.current .step-circle {
    background: #007bff;
    color: #fff;
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.3);
}
.burn-step small {
    font-size: 0.65rem;
    color: #6c757d;
}
.burn-step.active small {
    color: #17a2b8;
    font-weight: 600;
}
.step-line {
    flex: 1;
    height: 2px;
    background: #e9ecef;
    margin-top: 13px;
    transition: background 0.3s ease;
}
.step-line.active {
    background: #17a2b8;
}

/* Progress bar */
.burn-progress {
    height: 24px;
    border-radius: 12px;
    overflow: hidden;
    background: #e9ecef;
}
.burn-progress-bar {
    transition: width 0.6s ease;
    border-radius: 12px;
    font-size: 0.75rem;
    line-height: 24px;
    font-weight: 600;
}
.burn-bar-info {
    background: linear-gradient(90deg, #17a2b8, #20c997) !important;
}
.burn-bar-warning {
    background: linear-gradient(90deg, #ffc107, #fd7e14) !important;
}
.burn-bar-success {
    background: linear-gradient(90deg, #28a745, #20c997) !important;
}
.burn-bar-danger {
    background: linear-gradient(90deg, #dc3545, #e83e8c) !important;
}

/* Detail text */
.burn-detail {
    padding: 8px 12px;
    background: #f8f9fa;
    border-radius: 6px;
    border-left: 3px solid #17a2b8;
}

/* Progress section animation */
.burn-progress-section {
    animation: fadeIn 0.3s ease;
}
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-5px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>

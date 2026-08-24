<template>
    <div class="descargar-zip-container">
        <!-- Error (prioridad máxima: visible incluso tras downloadReady) -->
        <div v-if="error" class="text-center py-4">
            <div class="mb-3">
                <i class="fa fa-exclamation-triangle fa-3x text-danger"></i>
            </div>
            <h5 class="text-danger">Error al generar el ZIP</h5>
            <p class="text-muted">{{ error }}</p>

            <button class="btn btn-outline-info" @click="startGeneration">
                <i class="fa fa-refresh"></i> Reintentar
            </button>
        </div>

        <!-- Progress Section -->
        <div v-else-if="generating" class="text-center py-4">
            <div class="mb-3">
                <i class="fa fa-file-archive-o fa-3x text-info"></i>
            </div>
            <h5 class="text-info">Generando archivo ZIP</h5>
            <p class="text-muted">{{ statusMessage }}</p>

            <!-- Progress Bar -->
            <div class="progress mb-3" style="height: 25px">
                <div
                    class="progress-bar progress-bar-striped progress-bar-animated bg-info"
                    role="progressbar"
                    :style="{ width: progress + '%' }"
                    :aria-valuenow="progress"
                    aria-valuemin="0"
                    aria-valuemax="100"
                >
                    {{ progress }}%
                </div>
            </div>

            <small class="text-muted">
                <i class="fa fa-info-circle"></i>
                Esto puede tomar unos minutos dependiendo del tamaño del estudio.
            </small>
        </div>

        <!-- Download Ready -->
        <div v-else-if="downloadReady" class="text-center py-4">
            <div class="mb-3">
                <i class="fa fa-check-circle fa-3x text-success"></i>
            </div>
            <h5 class="text-success">ZIP listo para descargar</h5>
            <p class="text-muted">{{ filename }}</p>

            <button class="btn btn-info btn-lg" @click="downloadZip" :disabled="downloading">
                <i v-if="downloading" class="fa fa-spinner fa-spin"></i>
                <i v-else class="fa fa-download"></i>
                Descargar ZIP
            </button>

            <div class="mt-3">
                <small class="text-muted">
                    <i class="fa fa-archive"></i> {{ totalFiles }} archivos incluidos
                </small>
            </div>
        </div>

        <!-- Initial State -->
        <div v-else class="text-center py-4">
            <div class="mb-3">
                <i class="fa fa-file-archive-o fa-3x text-info"></i>
            </div>
            <h5>Descargar estudio como ZIP</h5>
            <p class="text-muted">
                Se descargarán las imágenes DICOM del estudio junto con el visor MicroDicom.
            </p>

            <button class="btn btn-info btn-lg" @click="startGeneration" :disabled="generating">
                <i class="fa fa-download"></i> Generar ZIP
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from "vue";
import axios from "axios";

const props = defineProps({
    estudio: {
        type: Object,
        required: true,
    },
    usuarioactual: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(["cerrar", "completado"]);

const generating = ref(false);
const downloading = ref(false);
const downloadReady = ref(false);
const error = ref(null);
const progress = ref(0);
const statusMessage = ref("");
const filename = ref("");
const totalFiles = ref(0);
const zipId = ref(null);
let pollingInterval = null;
let fallosConsecutivos = 0;

/**
 * Genera el ZIP del estudio en el backend e inicia el sondeo de progreso.
 * Reinicia todos los estados previos (listo/error).
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-08-05
 */
function startGeneration() {
    generating.value = true;
    downloading.value = false;
    downloadReady.value = false;
    error.value = null;
    progress.value = 0;
    statusMessage.value = "Iniciando...";
    fallosConsecutivos = 0;

    const sedeId = sessionStorage.getItem("ST-sede");
    if (!sedeId) {
        error.value = "No se encontró la sede seleccionada.";
        generating.value = false;
        return;
    }

    const payload = {
        study_instance_uid: props.estudio.study_iuid,
        sede_id: parseInt(sedeId, 10),
        patient_name: props.estudio.nom_pacien || "estudio",
    };

    axios
        .post("/api/dicom/generate-zip", payload)
        .then((response) => {
            if (response.data.status === "success") {
                zipId.value = response.data.data.zip_id;
                filename.value = response.data.data.filename;
                totalFiles.value = response.data.data.total_files;
                startPolling();
            } else {
                error.value = response.data.message || "Error al generar ZIP";
                generating.value = false;
            }
        })
        .catch((err) => {
            error.value = err.response?.data?.message || "Error de conexión al generar ZIP";
            generating.value = false;
        });
}

/**
 * Inicia el sondeo periódico del estado de generación del ZIP (cada segundo).
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-08-05
 */
function startPolling() {
    if (pollingInterval) {
        clearInterval(pollingInterval);
    }
    fallosConsecutivos = 0;

    pollingInterval = setInterval(() => {
        checkStatus();
    }, 1000);
}

/**
 * Detiene el sondeo de generación del ZIP.
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-08-05
 */
function stopPolling() {
    if (pollingInterval) {
        clearInterval(pollingInterval);
        pollingInterval = null;
    }
}

/**
 * Consulta el estado del ZIP en el backend.
 * Tras 5 fallos consecutivos de red detiene el sondeo y muestra error;
 * un estado `failed` del backend también termina con error.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-08-05
 */
function checkStatus() {
    if (!zipId.value) return;

    axios
        .get(`/api/dicom/zip-status/${zipId.value}`)
        .then((response) => {
            if (response.data.status === "success") {
                fallosConsecutivos = 0;
                const data = response.data.data;

                // Estado terminal fallido reportado por el backend
                if (data.status === "failed") {
                    stopPolling();
                    generating.value = false;
                    error.value = data.error_message || "La generación del ZIP falló.";
                    return;
                }

                progress.value = data.progress || 0;
                statusMessage.value = data.status || "Procesando...";

                if (data.progress >= 100) {
                    stopPolling();
                    generating.value = false;
                    downloadReady.value = true;
                    filename.value = data.filename || filename.value;
                }
            }
        })
        .catch(() => {
            fallosConsecutivos++;
            if (fallosConsecutivos >= 5) {
                stopPolling();
                generating.value = false;
                error.value = "Se perdió la conexión con el servidor mientras se generaba el ZIP.";
            }
        });
}

/**
 * Descarga el archivo ZIP generado vía fetch + blob.
 * Codifica el nombre del archivo en la URL, bloquea doble clic y revoca el
 * object URL de forma diferida para no abortar la descarga en algunos navegadores.
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-08-05
 */
function downloadZip() {
    if (downloading.value) return;
    downloading.value = true;

    const url = `/api/dicom/download-zip/${encodeURIComponent(filename.value)}`;

    // Get XSRF token from cookie for Sanctum
    let xsrfToken = null;
    const match = document.cookie.match(new RegExp("(^|;)\\s*XSRF-TOKEN\\s*=\\s*([^;]+)"));
    if (match) {
        xsrfToken = decodeURIComponent(match[2]);
    }

    fetch(url, {
        credentials: "same-origin",
        headers: xsrfToken ? { "X-XSRF-TOKEN": xsrfToken } : {},
    })
        .then((res) => {
            if (!res.ok) throw new Error("Error al descargar");
            return res.blob();
        })
        .then((blob) => {
            const blobUrl = URL.createObjectURL(blob);
            const link = document.createElement("a");
            link.href = blobUrl;
            link.download = filename.value;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            setTimeout(() => URL.revokeObjectURL(blobUrl), 2000);
            emit("completado");
        })
        .catch((err) => {
            error.value = err.message || "Error al descargar el ZIP";
        })
        .finally(() => {
            downloading.value = false;
        });
}

/**
 * Detiene el sondeo y notifica al padre que se cierra el componente.
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-08-05
 */
function cerrar() {
    stopPolling();
    emit("cerrar");
}

onMounted(() => {
    startGeneration();
});

/**
 * Limpieza al desmontar: detiene el intervalo de sondeo para evitar fugas.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-08-05
 */
onBeforeUnmount(() => {
    stopPolling();
});
</script>

<style scoped>
.descargar-zip-container {
    padding: 15px;
}

.progress {
    border-radius: 15px;
    overflow: hidden;
}

.progress-bar {
    transition: width 0.5s ease-in-out;
}
</style>

<template>
    <div class="descargar-zip-container">
        <!-- Progress Section -->
        <div v-if="generating" class="text-center py-4">
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

            <button class="btn btn-info btn-lg" @click="downloadZip">
                <i class="fa fa-download"></i> Descargar ZIP
            </button>

            <div class="mt-3">
                <small class="text-muted">
                    <i class="fa fa-archive"></i> {{ totalFiles }} archivos incluidos
                </small>
            </div>
        </div>

        <!-- Error -->
        <div v-else-if="error" class="text-center py-4">
            <div class="mb-3">
                <i class="fa fa-exclamation-triangle fa-3x text-danger"></i>
            </div>
            <h5 class="text-danger">Error al generar ZIP</h5>
            <p class="text-muted">{{ error }}</p>

            <button class="btn btn-outline-info" @click="startGeneration">
                <i class="fa fa-refresh"></i> Reintentar
            </button>
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
import { ref, onMounted } from "vue";
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
const downloadReady = ref(false);
const error = ref(null);
const progress = ref(0);
const statusMessage = ref("");
const filename = ref("");
const totalFiles = ref(0);
const zipId = ref(null);
let pollingInterval = null;

onMounted(() => {
    startGeneration();
});

function startGeneration() {
    generating.value = true;
    downloadReady.value = false;
    error.value = null;
    progress.value = 0;
    statusMessage.value = "Iniciando...";

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

function startPolling() {
    if (pollingInterval) {
        clearInterval(pollingInterval);
    }

    pollingInterval = setInterval(() => {
        checkStatus();
    }, 1000);
}

function checkStatus() {
    if (!zipId.value) return;

    axios
        .get(`/api/dicom/zip-status/${zipId.value}`)
        .then((response) => {
            if (response.data.status === "success") {
                const data = response.data.data;
                progress.value = data.progress || 0;
                statusMessage.value = data.status || "Procesando...";

                if (data.progress >= 100) {
                    clearInterval(pollingInterval);
                    generating.value = false;
                    downloadReady.value = true;
                    filename.value = data.filename || filename.value;
                }
            }
        })
        .catch(() => {
            // Silently ignore polling errors
        });
}

function downloadZip() {
    const url = `/api/dicom/download-zip/${filename.value}`;

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
            URL.revokeObjectURL(blobUrl);
            emit("completado");
        })
        .catch((err) => {
            error.value = err.message || "Error al descargar el ZIP";
        });
}

function cerrar() {
    if (pollingInterval) {
        clearInterval(pollingInterval);
    }
    emit("cerrar");
}
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

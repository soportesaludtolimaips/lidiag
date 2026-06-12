import { ref, onBeforeUnmount } from "vue";
import axios from "axios";
import { handleAxiosErrorToast } from "@/helpers/alerts";

export function useAudioUploadFlow({ showToast } = {}) {
    const audiosCargadosPendientes = ref([]);
    const audioSeleccionados = ref([]);
    const modoTranscripcion = ref("agregar");
    const transcribiendo = ref(false);
    const insertandoAudios = ref(false);
    const progresoTranscripcion = ref(0);
    const eliminandoAudioId = ref(null);
    const audiosSubiendo = ref(0);

    function formatSize(bytes) {
        if (!bytes || bytes === 0) return "0 B";
        const units = ["B", "KB", "MB", "GB"];
        const i = Math.floor(Math.log(bytes) / Math.log(1024));
        const val = (bytes / Math.pow(1024, i)).toFixed(i > 0 ? 1 : 0);
        return val + " " + units[i];
    }

    function formatDuracion(segundos) {
        if (!segundos) return "0:00";
        const mins = Math.floor(segundos / 60);
        const secs = Math.floor(segundos % 60);
        return `${mins}:${secs.toString().padStart(2, "0")}`;
    }

    function previewTranscripcion(texto, maxLen) {
        if (!texto) return "";
        if (texto.length <= maxLen) return texto;
        return texto.substring(0, maxLen);
    }

    function badgeClaseEstado(estado) {
        const map = {
            "Transcrito": "badge-success",
            "Completado": "badge-success",
            "Procesando...": "badge-warning",
            "Pendiente": "badge-secondary",
            "Error": "badge-danger",
        };
        return map[estado] || "badge-secondary";
    }

    function obtenerAudioUrl(audio) {
        if (audio._blobUrl) return audio._blobUrl;
        if (audio.serverUrl) return audio.serverUrl;
        return null;
    }

    function insertarTranscripcion(texto, lecturaActual) {
        if (!texto) return texto;
        if (modoTranscripcion.value === "reemplazar") {
            return texto;
        }
        if (lecturaActual && lecturaActual.trim().length > 0) {
            return lecturaActual + "\n\n" + texto;
        }
        return texto;
    }

    function insertarAudioIndividual(audio) {
        if (!audio || !audio.transcripcion) return null;
        if (typeof showToast === "function") {
            showToast({
                heading: "Ok!!!",
                text: `Transcripcion de "${audio.nombre_original || 'Audio'}" insertada en la lectura`,
                position: "top-right",
                loaderBg: "#ff6849",
                icon: "success",
                hideAfter: 3000,
                stack: 6,
            });
        }
        return audio.transcripcion;
    }

    async function transcribirAudiosSeleccionados() {
        if (audioSeleccionados.value.length === 0) return null;
        insertandoAudios.value = true;
        let procesados = 0;
        let textoCompleto = "";
        for (const audioId of audioSeleccionados.value) {
            const audio = audiosCargadosPendientes.value.find((a) => a.id === audioId);
            if (!audio || !audio.transcripcion) continue;
            if (textoCompleto.length > 0) {
                textoCompleto += "\n\n--- " + (audio.nombre_original || "Audio") + " ---\n\n";
            }
            textoCompleto += audio.transcripcion;
            procesados++;
        }
        if (typeof showToast === "function") {
            showToast({
                heading: "Ok!!!",
                text: `${procesados} audio(s) insertados en la lectura`,
                position: "top-right",
                loaderBg: "#ff6849",
                icon: "success",
                hideAfter: 3500,
                stack: 6,
            });
        }
        audioSeleccionados.value = [];
        insertandoAudios.value = false;
        return textoCompleto;
    }

    function limpiarAudiosCargados() {
        for (const audio of audiosCargadosPendientes.value) {
            if (audio._blobUrl) {
                try { URL.revokeObjectURL(audio._blobUrl); } catch (e) { }
            }
        }
        audiosCargadosPendientes.value = [];
        audioSeleccionados.value = [];
        progresoTranscripcion.value = 0;
        transcribiendo.value = false;
        insertandoAudios.value = false;
    }

    async function uploadAudioFile(file, blobUrl, estudioId, estudioProductoId) {
        console.log("[uploadAudioFile] INICIO", { file: file?.name, estudioId, estudioProductoId });
        if (!estudioProductoId) {
            console.warn("[uploadAudioFile] No hay estudio_producto_id");
            if (typeof showToast === "function") {
                showToast({
                    heading: "Upsss!!!",
                    text: "No hay un estudio seleccionado",
                    position: "top-right",
                    loaderBg: "#ff6849",
                    icon: "warning",
                    hideAfter: 3500,
                    stack: 6,
                });
            }
            return false;
        }
        audiosSubiendo.value++;
        const formData = new FormData();
        formData.append("audio", file);
        formData.append("estudio_id", estudioId);
        formData.append("estudio_producto_id", estudioProductoId);
        try {
            console.log("[uploadAudioFile] Enviando POST a /api/cargar-audio-transcribir");
            const res = await axios.post("/api/cargar-audio-transcribir", formData, {
                headers: { "Content-Type": "multipart/form-data" },
                timeout: 300000,
            });
            console.log("[uploadAudioFile] Respuesta del servidor:", { status: res?.status, success: res?.data?.success, audio_id: res?.data?.audio_id, audio_file: res?.data?.audio_file, tieneTranscripcion: !!res?.data?.transcripcion });
            if (res.data && res.data.success) {
                audiosCargadosPendientes.value.push({
                    id: res.data.audio_id,
                    nombre_original: file.name,
                    _blobUrl: blobUrl || null,
                    serverUrl: res.data.audio_file ? '/audios/' + res.data.audio_file : null,
                    transcripcion_estado: "Transcrito",
                    transcripcion: res.data.transcripcion,
                    duracion: res.data.duracion,
                    tamano: file.size,
                });
                console.log("[uploadAudioFile] Audio agregado a audiosCargadosPendientes, total:", audiosCargadosPendientes.value.length);
                if (typeof showToast === "function") {
                    showToast({
                        heading: "Ok!!!",
                        text: `Audio "${file.name}" cargado y transcrito exitosamente`,
                        position: "top-right",
                        loaderBg: "#ff6849",
                        icon: "success",
                        hideAfter: 3500,
                        stack: 6,
                    });
                }
                return true;
            } else {
                console.warn("[uploadAudioFile] El servidor respondió pero success=false", res?.data);
                throw new Error(res.data.message || "Error al cargar el audio");
            }
        } catch (error) {
            console.error("[uploadAudioFile] Error capturado:", { message: error.message, response: error?.response?.data, status: error?.response?.status });
            if (blobUrl) URL.revokeObjectURL(blobUrl);
            const msg = error.response && error.response.data && error.response.data.message
                ? error.response.data.message
                : "Error al cargar y transcribir el audio";
            if (typeof showToast === "function") {
                showToast({
                    heading: "Upsss!!!",
                    text: msg,
                    position: "top-right",
                    loaderBg: "#ff6849",
                    icon: "error",
                    hideAfter: 5000,
                    stack: 6,
                });
            }
            return false;
        } finally {
            audiosSubiendo.value--;
            if (audiosSubiendo.value < 0) audiosSubiendo.value = 0;
            console.log("[uploadAudioFile] FIN, audiosSubiendo restante:", audiosSubiendo.value);
        }
    }

    async function handleAudioFileSelect(event, estudioId, estudioProductoId) {
        const files = event.target.files;
        console.log("[handleAudioFileSelect] Archivos seleccionados:", files?.length, { estudioId, estudioProductoId });
        if (!files || files.length === 0) {
            console.warn("[handleAudioFileSelect] No hay archivos");
            return;
        }
        const maxSize = 100 * 1024 * 1024;
        const allowedTypes = [
            "audio/mpeg", "audio/wav", "audio/x-wav", "audio/mp4",
            "audio/webm", "audio/ogg", "audio/x-flac", "audio/flac",
        ];
        const validFiles = [];
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            if (file.size > maxSize) {
                if (typeof showToast === "function") {
                    showToast({
                        heading: "Upsss!!!",
                        text: `El archivo "${file.name}" excede el tamano maximo de 100MB`,
                        position: "top-right",
                        loaderBg: "#ff6849",
                        icon: "warning",
                        hideAfter: 5000,
                        stack: 6,
                    });
                }
                continue;
            }
            if (!allowedTypes.includes(file.type)) {
                if (typeof showToast === "function") {
                    showToast({
                        heading: "Upsss!!!",
                        text: `El archivo "${file.name}" no es un formato de audio valido`,
                        position: "top-right",
                        loaderBg: "#ff6849",
                        icon: "warning",
                        hideAfter: 5000,
                        stack: 6,
                    });
                }
                continue;
            }
            validFiles.push(file);
        }
        if (validFiles.length === 0) {
            event.target.value = "";
            return;
        }
        let idx = 0;
        const worker = async () => {
            while (idx < validFiles.length) {
                const file = validFiles[idx++];
                const blobUrl = URL.createObjectURL(file);
                try {
                    await uploadAudioFile(file, blobUrl, estudioId, estudioProductoId);
                } catch (e) {
                    console.error("[handleAudioFileSelect] Error en worker para", file.name, e);
                }
            }
        };
        console.log("[handleAudioFileSelect] Iniciando carga de", validFiles.length, "archivos con concurrencia 3");
        await Promise.all(Array.from({ length: 3 }, () => worker()));
        event.target.value = "";
    }

    async function eliminarAudioCargado(audioId, onSuccess) {
        if (!confirm("¿Estas seguro de eliminar este audio?")) return;
        eliminandoAudioId.value = audioId;
        try {
            const res = await axios.delete("/api/eliminar-audio-cargado/" + audioId);
            if (res.data && res.data.success) {
                const audioEliminado = audiosCargadosPendientes.value.find((a) => a.id === audioId);
                if (audioEliminado && audioEliminado._blobUrl) {
                    URL.revokeObjectURL(audioEliminado._blobUrl);
                }
                audiosCargadosPendientes.value = audiosCargadosPendientes.value.filter((a) => a.id !== audioId);
                audioSeleccionados.value = audioSeleccionados.value.filter((id) => id !== audioId);
                if (typeof showToast === "function") {
                    showToast({
                        heading: "Ok!!!",
                        text: "Audio eliminado correctamente",
                        position: "top-right",
                        loaderBg: "#ff6849",
                        icon: "success",
                        hideAfter: 3500,
                        stack: 6,
                    });
                }
                if (typeof onSuccess === "function") onSuccess();
            } else {
                throw new Error(res.data.message || "Error al eliminar");
            }
        } catch (error) {
            const msg = error.response && error.response.data && error.response.data.message
                ? error.response.data.message
                : "Error al eliminar el audio";
            if (typeof showToast === "function") {
                showToast({
                    heading: "Upsss!!!",
                    text: msg,
                    position: "top-right",
                    loaderBg: "#ff6849",
                    icon: "error",
                    hideAfter: 3500,
                    stack: 6,
                });
            }
        } finally {
            eliminandoAudioId.value = null;
        }
    }

    onBeforeUnmount(() => {
        limpiarAudiosCargados();
    });

    return {
        audiosCargadosPendientes,
        audioSeleccionados,
        modoTranscripcion,
        transcribiendo,
        insertandoAudios,
        progresoTranscripcion,
        eliminandoAudioId,
        audiosSubiendo,

        handleAudioFileSelect,
        uploadAudioFile,
        transcribirAudiosSeleccionados,
        insertarAudioIndividual,
        insertarTranscripcion,
        eliminarAudioCargado,
        limpiarAudiosCargados,
        obtenerAudioUrl,
        previewTranscripcion,
        badgeClaseEstado,
        formatDuracion,
        formatSize,
    };
}

import { ref, onBeforeUnmount } from "vue";
import axios from "axios";
import { confirmAction } from "../helpers/alerts";
/**
 * Gestiona el flujo de carga, transcripcion y eliminacion de audios asociados a un estudio, incluyendo la seleccion de archivos, el envio al servidor y el seguimiento del progreso.
 *
 * @param {Object} [options] Opciones de configuracion del composable.
 * @param {Function} [options.showToast] Funcion para mostrar notificaciones (toast) al usuario.
 *
 * @returns {Object} Conjunto de referencias reactivas (Ref) y funciones para gestionar la carga y transcripcion de audios.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-06-12
 */
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
            Transcrito: "badge-success",
            Completado: "badge-success",
            "Procesando...": "badge-warning",
            Pendiente: "badge-secondary",
            Error: "badge-danger",
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
                text: `Transcripcion de "${audio.nombre_original || "Audio"}" insertada en la lectura`,
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
        let omitidos = 0;
        let textoCompleto = "";
        for (const audioId of audioSeleccionados.value) {
            const audio = audiosCargadosPendientes.value.find((a) => a.id === audioId);
            if (!audio || !audio.transcripcion) {
                omitidos++;
                continue;
            }
            if (textoCompleto.length > 0) {
                textoCompleto += "\n\n--- " + (audio.nombre_original || "Audio") + " ---\n\n";
            }
            textoCompleto += audio.transcripcion;
            procesados++;
        }
        if (typeof showToast === "function") {
            showToast({
                heading: "Ok!!!",
                text:
                    `${procesados} audio(s) insertados en la lectura` +
                    (omitidos > 0 ? `. ${omitidos} omitido(s) (transcripción pendiente)` : ""),
                position: "top-right",
                loaderBg: "#ff6849",
                icon: procesados > 0 ? "success" : "warning",
                hideAfter: 4500,
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
                try {
                    URL.revokeObjectURL(audio._blobUrl);
                } catch {}
            }
        }
        audiosCargadosPendientes.value = [];
        audioSeleccionados.value = [];
        progresoTranscripcion.value = 0;
        transcribiendo.value = false;
        insertandoAudios.value = false;
    }

    async function uploadAudioFile(
        file,
        blobUrl,
        estudioId,
        estudioProductoId,
        onTranscriptionReady
    ) {
        if (!estudioProductoId) {
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
            const res = await axios.post("/api/cargar-audio-transcribir", formData, {
                headers: { "Content-Type": "multipart/form-data" },
                timeout: 300000,
            });
            if (res.data && res.data.success) {
                const d = res.data.data || {};
                audiosCargadosPendientes.value.push({
                    id: d.audio_id,
                    nombre_original: file.name,
                    _blobUrl: blobUrl || null,
                    serverUrl: d.audio_file ? "/audios/" + d.audio_file : null,
                    transcripcion_estado:
                        d.transcripcion_estado === "transcrito" ? "Transcrito" : "Pendiente",
                    transcripcion: d.transcripcion,
                    duracion: d.duracion,
                    tamano: file.size,
                });
                if (typeof showToast === "function") {
                    const toastMsg =
                        d.transcripcion_estado === "transcrito"
                            ? `Audio "${file.name}" cargado y transcrito exitosamente`
                            : `Audio "${file.name}" cargado correctamente (transcripción pendiente)`;
                    showToast({
                        heading: "Ok!!!",
                        text: toastMsg,
                        position: "top-right",
                        loaderBg: "#ff6849",
                        icon: "success",
                        hideAfter: 3500,
                        stack: 6,
                    });
                }
                if (typeof onTranscriptionReady === "function" && d.transcripcion) {
                    onTranscriptionReady(d.transcripcion);
                }
                return true;
            } else {
                throw new Error(res.data.message || "Error al cargar el audio");
            }
        } catch (error) {
            if (blobUrl) URL.revokeObjectURL(blobUrl);
            const msg =
                error.response && error.response.data && error.response.data.message
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
        }
    }

    async function handleAudioFileSelect(
        event,
        estudioId,
        estudioProductoId,
        onTranscriptionReady
    ) {
        const files = event.target.files;
        if (!files || files.length === 0) {
            return;
        }
        const maxSize = 100 * 1024 * 1024;
        const allowedTypes = [
            "audio/mpeg",
            "audio/wav",
            "audio/x-wav",
            "audio/mp4",
            "audio/webm",
            "audio/ogg",
            "audio/x-flac",
            "audio/flac",
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
                    await uploadAudioFile(
                        file,
                        blobUrl,
                        estudioId,
                        estudioProductoId,
                        onTranscriptionReady
                    );
                } catch {}
            }
        };
        await Promise.all(Array.from({ length: 3 }, () => worker()));
        event.target.value = "";
    }

    async function eliminarAudioCargado(audioId, onSuccess) {
        const confirmado = await confirmAction({
            title: "Eliminar audio",
            text: "¿Estás seguro de eliminar este audio?",
            icon: "warning",
        });
        if (!confirmado) return;
        eliminandoAudioId.value = audioId;
        try {
            const res = await axios.delete("/api/eliminar-audio-cargado/" + audioId);
            if (res.data && res.data.success) {
                const audioEliminado = audiosCargadosPendientes.value.find((a) => a.id === audioId);
                if (audioEliminado && audioEliminado._blobUrl) {
                    URL.revokeObjectURL(audioEliminado._blobUrl);
                }
                audiosCargadosPendientes.value = audiosCargadosPendientes.value.filter(
                    (a) => a.id !== audioId
                );
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
            const msg =
                error.response && error.response.data && error.response.data.message
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

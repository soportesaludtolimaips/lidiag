<template>
    <div>
        <button
            type="button"
            @click="toggleSpeechRecognition"
            :aria-label="recognizing ? 'Detener grabación' : 'Iniciar grabación'"
            :aria-pressed="String(recognizing)"
            class="mic-btn"
        >
            <img title="Grabadora" :src="microfono" />
        </button>
        <div class="row">
            <div class="col-7">
                <div class="form-group">
                    <textarea
                        v-model="registro.lectura"
                        class="textarea_editor form-control audio-textarea"
                        rows="15"
                        id="lectura"
                        placeholder="Ingrese aquí la lectura del estudio ..."
                    ></textarea>
                </div>
            </div>
        </div>
        <audio v-if="audioUrl" :src="audioUrl" controls></audio>

        <button
            type="button"
            class="btn btn-success m-r-5"
            @click="sendToBackend()"
            :disabled="loadingEnviar"
        >
            <i v-if="loadingEnviar" class="fa fa-spinner fa-spin"></i>
            <i v-else class="fa fa-check"></i>
            Guardar
        </button>
    </div>
</template>
<script setup>
import { onBeforeUnmount, onMounted, ref } from "vue";

import { useDictationFlow } from "@/composables/useDictationFlow";
import { handleAxiosErrorToast, toastMessage } from "@/helpers/alerts.js";
import axios from "axios";

const props = defineProps({
    estudioId: { type: [Number, String], default: null },
    estudioProductoId: { type: [Number, String], default: null },
});
const emit = defineEmits(["audio-saved"]);

const dictationFlow = useDictationFlow();

const id = ref(props.estudioId ? Number(props.estudioId) : 0);
const registro = ref({
    lectura: "",
    estudio_producto_id: props.estudioProductoId ? Number(props.estudioProductoId) : 0,
});
const loadingEnviar = ref(false);

async function sendToBackend() {
    if (loadingEnviar.value) return;
    loadingEnviar.value = true;
    await dictationFlow.stopRecording();

    if (!audioBlob || !(audioBlob instanceof Blob)) {
        toastMessage({
            heading: "Upss!!!",
            text: "No hay audio para enviar.",
            icon: "warning",
        });
        loadingEnviar.value = false;
        return;
    }

    if (!id.value || !registro.value.estudio_producto_id) {
        toastMessage({
            heading: "Upss!!!",
            text: "Faltan IDs del estudio para enviar el audio.",
            icon: "warning",
        });
        loadingEnviar.value = false;
        return;
    }

    const formData = new FormData();
    formData.append("audio", audioBlob, "audio.webm");
    formData.append("text", registro.value.lectura);
    formData.append("estudio_id", id.value);
    formData.append("estudio_producto_id", registro.value.estudio_producto_id);

    try {
        const res = await axios.post("/upload-audio", formData);
        toastMessage({
            heading: "Completado",
            text: res?.data?.message || "Audio enviado correctamente.",
            icon: "success",
        });
    } catch (error) {
        handleAxiosErrorToast(error, "Error al enviar audio.");
    } finally {
        loadingEnviar.value = false;
    }
}

onMounted(() => {
    if (!("webkitSpeechRecognition" in window) && !("SpeechRecognition" in window)) {
        toastMessage({
            heading: "Upss!!!",
            text: "Tu navegador no soporta reconocimiento de voz. Usa Chrome o Edge.",
            icon: "warning",
        });
    }
    dictationFlow.setCallbacks({
        onText: (text) => {
            registro.value.lectura += text;
        },
        showToast: (cfg) => $.toast(cfg),
        onState: () => {},
        onAudioSaved: () => {
            emit("audio-saved");
        },
    });
});

onBeforeUnmount(() => {
    dictationFlow.stopAll();
    dictationFlow.teardownKeyboardShortcut();
    dictationFlow.clearAudioBuffer();
});
</script>
<style scoped>
.mic-btn {
    width: 44px;
    height: 44px;
    padding: 0;
    border: none;
    background: none;
    cursor: pointer;
}
.audio-textarea {
    height: 700px;
}
</style>

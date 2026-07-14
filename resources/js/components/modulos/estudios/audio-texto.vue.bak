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

<script>
import { useDictationFlow } from "@/composables/useDictationFlow";
import { handleAxiosErrorToast, toastMessage } from "@/helpers/alerts.js";

export default {
    props: {
        estudioId: { type: [Number, String], default: null },
        estudioProductoId: { type: [Number, String], default: null },
    },
    setup() {
        const dictationFlow = useDictationFlow();
        return {
            dictationFlow,
            recognizing: dictationFlow.recognizing,
            recording: dictationFlow.recording,
            microfono: dictationFlow.microfono,
            uppercase: dictationFlow.uppercase,
            audioUrl: dictationFlow.audioUrl,
            audioBlob: dictationFlow.audioBlob,
            toggleSpeechRecognition: dictationFlow.toggleSpeechRecognition,
        };
    },
    data() {
        return {
            id: this.estudioId ? Number(this.estudioId) : 0,
            registro: {
                lectura: "",
                estudio_producto_id: this.estudioProductoId ? Number(this.estudioProductoId) : 0,
            },
            loadingEnviar: false,
        };
    },
    methods: {
        async sendToBackend() {
            if (this.loadingEnviar) return;
            this.loadingEnviar = true;
            await this.dictationFlow.stopRecording();

            if (!this.audioBlob || !(this.audioBlob instanceof Blob)) {
                toastMessage({
                    heading: "Upss!!!",
                    text: "No hay audio para enviar.",
                    icon: "warning",
                });
                this.loadingEnviar = false;
                return;
            }

            if (!this.id || !this.registro.estudio_producto_id) {
                toastMessage({
                    heading: "Upss!!!",
                    text: "Faltan IDs del estudio para enviar el audio.",
                    icon: "warning",
                });
                this.loadingEnviar = false;
                return;
            }

            const formData = new FormData();
            formData.append("audio", this.audioBlob, "audio.webm");
            formData.append("text", this.registro.lectura);
            formData.append("estudio_id", this.id);
            formData.append("estudio_producto_id", this.registro.estudio_producto_id);

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
                this.loadingEnviar = false;
            }
        },
    },
    mounted() {
        if (!("webkitSpeechRecognition" in window) && !("SpeechRecognition" in window)) {
            toastMessage({
                heading: "Upss!!!",
                text: "Tu navegador no soporta reconocimiento de voz. Usa Chrome o Edge.",
                icon: "warning",
            });
        }
        this.dictationFlow.setCallbacks({
            onText: (text) => {
                this.registro.lectura += text;
            },
            showToast: (cfg) => $.toast(cfg),
            onState: () => {},
            onAudioSaved: () => {
                this.$emit("audio-saved");
            },
        });
    },
    beforeUnmount() {
        this.dictationFlow.stopAll();
        this.dictationFlow.teardownKeyboardShortcut();
        this.dictationFlow.clearAudioBuffer();
    },
};
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

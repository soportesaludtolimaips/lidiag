<template>
    <div>
        <a class="" @click="toggleSpeechRecognition" style="width: 30px; height: 30px;">
            <img title="Grabadora" :src="microfono">
        </a>
        <div class="row">
            <div class="col-7">
                <div class="form-group">
                    <textarea v-model="registro.lectura" class="textarea_editor form-control" rows="15" id="lectura"
                        style="height: 700px;" placeholder="Ingrese aquí la lectura del estudio ..."></textarea>
                </div>
            </div>
        </div>
        <audio v-if="audioUrl" :src="audioUrl" controls></audio>

        <button type="button" class="btn btn-success m-r-5" @click="sendToBackend()" :disabled="loadingEnviar">
            <i v-if="loadingEnviar" class="fa fa-spinner fa-spin"></i>
            <i v-else class="fa fa-check"></i>
            Guardar
        </button>
    </div>
</template>

<script>
export default {
    props: {
        estudioId: { type: [Number, String], default: null },
        estudioProductoId: { type: [Number, String], default: null },
    },
    data() {
        return {
            recognizing: false,
            recording: false,
            microfono: 'admin-wrap/assets/images/mic.gif',
            recognition: null,
            mediaRecorder: null,
            audioChunks: [],
            audioUrl: null,
            uppercase: false,
            id: this.estudioId ? Number(this.estudioId) : 0,
            registro: {
                lectura: "",
                estudio_producto_id: this.estudioProductoId ? Number(this.estudioProductoId) : 0,
            },
            loadingEnviar: false,
            commands: {
                "iniciar mayúscula sostenida": () => { this.uppercase = true; return ""; },
                "iniciar mayúsculas sostenida": () => { this.uppercase = true; return ""; },
                "iniciar mayúsculas": () => { this.uppercase = true; return ""; },
                "iniciar mayúscula": () => { this.uppercase = true; return ""; },
                "mayúsculas sostenida": () => { this.uppercase = true; return ""; },
                "mayúscula sostenida": () => { this.uppercase = true; return ""; },
                "activar mayúsculas": () => { this.uppercase = true; return ""; },
                "activar mayúscula": () => { this.uppercase = true; return ""; },
                "habilitar mayúsculas": () => { this.uppercase = true; return ""; },
                "habilitar mayúscula": () => { this.uppercase = true; return ""; },
                "activare mayúsculas": () => { this.uppercase = true; return ""; },
                "activare mayúscula": () => { this.uppercase = true; return ""; },
                "desactivar mayúsculas": () => { this.uppercase = false; return ""; },
                "desactivar mayúscula": () => { this.uppercase = false; return ""; },
                "Deshabilitar mayúsculas": () => { this.uppercase = false; return ""; },
                "Deshabilitar mayúscula": () => { this.uppercase = false; return ""; },
                "terminar mayúsculas": () => { this.uppercase = false; return ""; },
                "terminar mayúscula": () => { this.uppercase = false; return ""; },
                "fin mayúsculas": () => { this.uppercase = false; return ""; },
                "fin mayúscula": () => { this.uppercase = false; return ""; },
                "finaliza mayúsculas": () => { this.uppercase = false; return ""; },
                "finaliza mayúscula": () => { this.uppercase = false; return ""; },
                "finalizar mayúsculas": () => { this.uppercase = false; return ""; },
                "finalizar mayúscula": () => { this.uppercase = false; return ""; },

                "punto aparte": () => ".\n\n",
                "salto de línea": () => "\n",
                "doble salto de línea": () => "\n\n",
                "enter": () => "\n",
                "doble enter": () => "\n\n",
                "línea en blanco": () => "\n\n",
                "punto": () => ".",
                "coma": () => ",",
                "dos puntos": () => ":",
                "punto y coma": () => ";",

                "dar espacio": () => " ",
                "un espacio": () => " ",
                "dos espacio": () => "  ",
                "doble espacio": () => "  ",
                "abrir paréntesis": () => "(",
                "cerrar paréntesis": () => ")",
                "abrir corchetes": () => "[",
                "cerrar corchetes": () => "]",
                "abrir llaves": () => "{",
                "cerrar llaves": () => "}",
                "abrir comillas": () => "\"",
                "cerrar comillas": () => "\"",
                "abrir comillas simples": () => "'",
                "cerrar comillas simples": () => "'",
                "guion": () => "-",
                "guion bajo": () => "_",
                "barra diagonal": () => "/",
                "barra invertida": () => "\\",
                "nueva oración": () => ". "
            },

        };
    },
    methods: {
        toggleSpeechRecognition() {
            if (!this.recognizing) {
                this.startRecording();
            } else if (this.mediaRecorder) {
                if (this.mediaRecorder.state === "recording") {
                    this.mediaRecorder.pause();
                    this.microfono = 'admin-wrap/assets/images/mic.gif';
                } else if (this.mediaRecorder.state === "paused" || this.mediaRecorder.state === "inactive") {
                    this.mediaRecorder.resume();
                    this.microfono = 'admin-wrap/assets/images/mic-animate.gif';
                }
            }
        },
        startRecording() {
            if (!this.recognition) {
                this.recognition = new (window.SpeechRecognition || window.webkitSpeechRecognition || window.mozSpeechRecognition)();
                this.recognition.lang = 'es-ES';
                this.recognition.continuous = true;
                this.recognition.interimResults = true;
                this.recognition.maxAlternatives = 1;

                // Implementación de SpeechGrammarList para optimizar los comandos
                const speechRecognitionList = new (window.SpeechGrammarList || window.webkitSpeechGrammarList)();
                const commandsList = Object.keys(this.commands);
                const grammar = `#JSGF V1.0; grammar commands; public <command> = ${commandsList.join(" | ")};`;
                speechRecognitionList.addFromString(grammar, 1);
                this.recognition.grammars = speechRecognitionList;

                this.recognition.onresult = (event) => {
                    let finalText = '';
                    for (let i = event.resultIndex; i < event.results.length; i++) {
                        if (event.results[i].isFinal) {
                            let text = event.results[i][0].transcript.trim();

                            text = this.processCommands(text); // Aplica los comandos correctamente

                            finalText += text + ' ';
                        }
                    }
                    this.registro.lectura += finalText;
                };

                this.recognition.onend = () => {
                    if (this.recognizing) {
                        setTimeout(() => this.recognition.start(), 500);
                    }
                };

                this.recognition.onerror = (event) => {
                    console.error('Error en reconocimiento:', event.error);
                    if (event.error === 'no-speech' || event.error === 'network') {
                        this.recognition.stop();
                    }
                };
            }

            this.recognition.start();
            this.recognizing = true;
            this.microfono = 'admin-wrap/assets/images/mic-animate.gif';

            navigator.mediaDevices.getUserMedia({ audio: true }).then(stream => {
                try {
                    this.mediaRecorder = new MediaRecorder(stream, {
                        mimeType: "audio/webm;codecs=opus",
                        audioBitsPerSecond: 64000, // Reduce la calidad (valores entre 16kbps y 128kbps)
                    });
                } catch (e) {
                    // Fallback for browsers that don't support the mimeType
                    this.mediaRecorder = new MediaRecorder(stream);
                }
                this.audioChunks = [];

                this.mediaRecorder.ondataavailable = (event) => {
                    this.audioChunks.push(event.data);
                };

                this.mediaRecorder.onstop = () => {
                    this.audioBlob = new Blob(this.audioChunks, { type: 'audio/webm' });
                    this.audioUrl = URL.createObjectURL(this.audioBlob);
                };

                this.mediaRecorder.start();
                this.recording = true;
            }).catch(error => console.error('Error al acceder al micrófono:', error));
        },
        stopRecording() {
            return new Promise((resolve) => {
                if (this.mediaRecorder && this.recording) {
                    this.mediaRecorder.onstop = () => {
                        this.audioBlob = new Blob(this.audioChunks, { type: "audio/webm" });
                        this.audioUrl = URL.createObjectURL(this.audioBlob);
                        resolve(); // Resuelve la promesa después de generar el Blob
                    };
                    this.mediaRecorder.stop();
                    this.recording = false;
                    this.microfono = 'admin-wrap/assets/images/mic.gif';
                } else {
                    resolve(); // Si ya está detenida, simplemente resuelve la promesa
                }
            });
        },
        processCommands(text) {
            if (typeof this.uppercase === "undefined") {
                this.uppercase = false;
            }

            Object.keys(this.commands).forEach(command => {
                text = text.replace(new RegExp(`\\b${command}\\b`, "gi"), this.commands[command]);
            });

            return this.uppercase ? text.toUpperCase() : text;
        },
        async sendToBackend() {
            if (this.loadingEnviar) return;
            this.loadingEnviar = true;
            await this.stopRecording(); // Esperar a que el blob esté listo

            if (!this.audioBlob || !(this.audioBlob instanceof Blob)) {
                try {
                    $.toast({
                        heading: "Upss!!!",
                        text: "No hay audio para enviar.",
                        position: "top-right",
                        loaderBg: "#ff6849",
                        icon: "warning",
                        hideAfter: 3500,
                        stack: 6,
                    });
                } catch (e) { }
                this.loadingEnviar = false;
                return;
            }

            const formData = new FormData();
            formData.append('audio', this.audioBlob, 'audio.webm');
            formData.append('text', this.registro.lectura);
            formData.append('estudio_id', this.id);
            formData.append('estudio_producto_id', this.registro.estudio_producto_id);

            try {
                if (!this.id || !this.registro.estudio_producto_id) {
                    try {
                        $.toast({
                            heading: "Upss!!!",
                            text: "Faltan IDs del estudio para enviar el audio.",
                            position: "top-right",
                            loaderBg: "#ff6849",
                            icon: "warning",
                            hideAfter: 3500,
                            stack: 6,
                        });
                    } catch (e) { }
                    return;
                }
                const res = await axios.post('/upload-audio', formData);
                try {
                    $.toast({
                        heading: "Ok!!!",
                        text: res?.data?.message || "Audio enviado correctamente.",
                        position: "top-right",
                        loaderBg: "#ff6849",
                        icon: "success",
                        hideAfter: 3500,
                        stack: 6,
                    });
                } catch (e) { }
            } catch (error) {
                console.error('Error al enviar audio:', error);
                try {
                    $.toast({
                        heading: "Upss!!!",
                        text: error?.response?.data?.message || "Error al enviar audio.",
                        position: "top-right",
                        loaderBg: "#ff6849",
                        icon: "error",
                        hideAfter: 3500,
                        stack: 6,
                    });
                } catch (e) { }
            } finally {
                this.loadingEnviar = false;
            }
        },
    }
};
</script>

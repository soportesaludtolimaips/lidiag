<template>
    <div>
        <button @click="startRecording" :disabled="isRecording">Iniciar Grabación</button>
        <button @click="stopRecording" :disabled="!isRecording">Detener Grabación</button>
        <p>{{ transcribedText }}</p>
    </div>
</template>

<script>
export default {
    data() {
        return {
            isRecording: false,
            transcribedText: '',
            recognition: null,
            mediaRecorder: null,
            chunks: []
        };
    },
    methods: {
        startRecording() {
            this.isRecording = true;
            this.transcribedText = '';
            this.chunks = [];

            if ('SpeechRecognition' in window || 'webkitSpeechRecognition' in window) {
                this.recognition = new (window.SpeechRecognition || window.webkitSpeechRecognition)();
                this.recognition.lang = 'es-ES';
                this.recognition.continuous = true;
                this.recognition.interimResults = true;

                this.recognition.onresult = (event) => {
                    const transcript = Array.from(event.results)
                        .map((result) => result[0].transcript)
                        .join('');

                    this.transcribedText = transcript;
                };

                this.recognition.start();
            } else {
                console.warn('SpeechRecognition no es compatible. Utilizando servicio externo.');
                // Aquí puedes implementar la lógica para enviar el audio a un servicio externo
                // Por ejemplo, puedes usar Google Cloud Speech-to-Text o Microsoft Azure Speech SDK.
            }

            // Iniciar grabación de audio
            navigator.mediaDevices.getUserMedia({ audio: true })
                .then((stream) => {
                    this.mediaRecorder = new MediaRecorder(stream);

                    this.mediaRecorder.ondataavailable = (event) => {
                        if (event.data.size > 0) {
                            this.chunks.push(event.data);
                        }
                    };

                    this.mediaRecorder.onstop = () => {
                        if (this.recognition) {
                            this.recognition.stop();
                        }

                        const blob = new Blob(this.chunks, { type: 'audio/wav' });
                        const audioUrl = URL.createObjectURL(blob);
                        console.log('Audio URL:', audioUrl);

                        const formData = new FormData();
                        formData.append('audio', audioUrl, 'audio.wav');
                        formData.append('estudio_id', this.id);

                        axios.post('/upload-audio', formData)
                            .then((response) => {
                                console.log('Audio enviado con éxito:', response.data);
                            })
                            .catch((error) => {
                                console.error('Error al enviar audio:', error);
                            });
                    };

                    this.mediaRecorder.start();
                })
                .catch((error) => {
                    console.error('Error al acceder al micrófono:', error);
                });
        },
        stopRecording() {
            this.isRecording = false;

            if (this.recognition) {
                this.recognition.stop();
            }

            if (this.mediaRecorder) {
                this.mediaRecorder.stop();
            }
        }
    }
};
</script>

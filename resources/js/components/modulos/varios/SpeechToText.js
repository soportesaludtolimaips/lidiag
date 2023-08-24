
export const recognition_motivo = new webkitSpeechRecognition();
export var recognizing_motivo = false;
//export const escucha_motivo = document.querySelector('#start_img');

if (!('webkitSpeechRecognition' in window)) {
    alert("¡API no soportada!");
} else {


    recognition_motivo.lang = "es-CO";
    recognition_motivo.continuous = true;
    recognition_motivo.interimResults = true;

    recognition_motivo.onstart = function () {
        recognizing_motivo = true;
        console.log("empezando a escuchar");
    }

    recognition_motivo.onresult = function (event) {

        for (var i = event.resultIndex; i < event.results.length; i++) {
            if (event.results[i].isFinal)
                //document.getElementById("motivo_consulta").value += event.results[i][0].transcript;
                console.log(event.results[i][0].transcript);
        }
    }

    recognition_motivo.onerror = function (event) { }
    recognition_motivo.onend = function () {
        recognizing_motivo = false;
        //escucha.src = '../../../public/dist/img/mic.gif';
        console.log("terminó de escuchar, llegó a su fin");
    }
}

export default procesar() {
    if (recognizing_motivo == false) {
        recognition_motivo.start();
        recognizing_motivo = true;
        //escucha_motivo.src = '../../../public/dist/img/mic-animate.gif';
    } else {
        recognition_motivo.stop();
        recognizing_motivo = false;
        //escucha_motivo.src = '../../../public/dist/img/mic.gif';
    }
}
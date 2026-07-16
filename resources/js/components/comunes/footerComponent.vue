<template>
    <footer class="footer">
        © 2017 Adminwrap by wrappixel.com

        <div class="float-right">
            <h4 class="text-info">Usted esta en la sede: {{ nomSede }}</h4>
            <h4 class="text-info" v-if="mostrarMedico">
                {{
                    nomMedico
                        ? "Usted esta en el médico: " + nomMedico
                        : "No ha seleccionado médico"
                }}
            </h4>
        </div>
    </footer>
</template>
<script setup>
import { getCurrentInstance, onMounted, ref } from "vue";

const { proxy } = getCurrentInstance();
const emitter = proxy.emitter;

const nomSede = ref(sessionStorage.getItem("ST-nomsede"));
const nomMedico = ref(sessionStorage.getItem("ST-nomMedico"));
const mostrarMedico = ref("");

function establecerSede() {
    nomSede.value = sessionStorage.getItem("ST-nomsede");
}
function establecerMedico() {
    nomMedico.value = sessionStorage.getItem("ST-nomMedico");
}

onMounted(() => {
    establecerSede();
    emitter.on("sedeSeleccionada", (data) => {
        sessionStorage.setItem("ST-sede", data.idSede);
        sessionStorage.setItem("ST-nomsede", data.nomSede);
        nomSede.value = data.nomSede;
    });

    emitter.on("medicoSeleccionado", (data) => {
        sessionStorage.setItem("ST-medico", data.idMedico);
        sessionStorage.setItem("ST-nomMedico", data.nomMedico);
        nomMedico.value = data.nomMedico;
    });

    const url = window.location.pathname;
    if (url != "/ecografias-pendientes-lecturas" && url != "/estudios.leer.estudio.listar") {
        mostrarMedico.value = false;
    }
});
</script>

<template>
    <footer class="footer">
        © 2017 Adminwrap by wrappixel.com

        <div class="float-right">
            <h4 class="text-info">Usted esta en la sede: {{ nomSede }}</h4>
            <h4 class="text-info" v-if="mostrarMedico">
                {{
                    nomMedicoEcografia
                        ? "Médico ecografía: " + nomMedicoEcografia
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
const nomMedicoEcografia = ref(localStorage.getItem("ST-ecografia-nomMedico"));
const mostrarMedico = ref(false);

function establecerSede() {
    nomSede.value = sessionStorage.getItem("ST-nomsede");
}
function establecerMedico() {
    nomMedicoEcografia.value = localStorage.getItem("ST-ecografia-nomMedico");
}

onMounted(() => {
    establecerSede();
    establecerMedico();

    emitter.on("sedeSeleccionada", (data) => {
        sessionStorage.setItem("ST-sede", data.idSede);
        sessionStorage.setItem("ST-nomsede", data.nomSede);
        nomSede.value = data.nomSede;
    });

    emitter.on("medicoSeleccionado", (data) => {
        localStorage.setItem("ST-ecografia-medico", data.idMedico);
        localStorage.setItem("ST-ecografia-nomMedico", data.nomMedico);
        nomMedicoEcografia.value = data.nomMedico;
    });

    const url = window.location.pathname;
    const rutasEcografias = [
        "/ecografias.admisiones",
        "/ecografias.admisionadas",
        "/ecografias-pendientes-lecturas",
        "/ecografias-consentimientos",
        "/ecografias-consentimientos-plantillas",
    ];
    mostrarMedico.value = rutasEcografias.includes(url);
});
</script>

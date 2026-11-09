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
import { getCurrentInstance, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { useRoute } from "vue-router";

const { proxy } = getCurrentInstance();
const emitter = proxy.emitter;
const route = useRoute();

const nomSede = ref(sessionStorage.getItem("ST-nomsede"));
const nomMedicoEcografia = ref(localStorage.getItem("ST-ecografia-nomMedico"));
const mostrarMedico = ref(false);

const rutasEcografias = [
    "/ecografias.admisiones",
    "/ecografias.admisionadas",
    "/ecografias-pendientes-lecturas",
    "/ecografias-consentimientos",
    "/ecografias-consentimientos-plantillas",
];

/**
 * Actualiza el nombre de la sede desde sessionStorage.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2024-04-04
 */
function establecerSede() {
    nomSede.value = sessionStorage.getItem("ST-nomsede");
}
/**
 * Actualiza el nombre del médico de ecografía desde localStorage.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2024-04-04
 */
function establecerMedico() {
    nomMedicoEcografia.value = localStorage.getItem("ST-ecografia-nomMedico");
}

function onSedeSeleccionada(data) {
    if (data?.idSede) sessionStorage.setItem("ST-sede", data.idSede);
    if (data?.nomSede) {
        sessionStorage.setItem("ST-nomsede", data.nomSede);
        nomSede.value = data.nomSede;
    }
}

function onMedicoSeleccionado(data) {
    if (data?.idMedico) localStorage.setItem("ST-ecografia-medico", data.idMedico);
    if (data?.nomMedico) {
        localStorage.setItem("ST-ecografia-nomMedico", data.nomMedico);
        nomMedicoEcografia.value = data.nomMedico;
    }
}

watch(
    () => route?.path,
    (path) => {
        mostrarMedico.value = rutasEcografias.includes(path || window.location.pathname);
    },
    { immediate: true }
);

/**
 * Lifecycle hook que inicializa sede y médico, y escucha eventos del emitter
 * para actualizar la interfaz cuando se selecciona una sede o médico.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2024-04-04
 */
onMounted(() => {
    establecerSede();
    establecerMedico();

    if (emitter) {
        emitter.on("sedeSeleccionada", onSedeSeleccionada);
        emitter.on("medicoSeleccionado", onMedicoSeleccionado);
    }
});

onBeforeUnmount(() => {
    if (emitter) {
        emitter.off("sedeSeleccionada", onSedeSeleccionada);
        emitter.off("medicoSeleccionado", onMedicoSeleccionado);
    }
});
</script>

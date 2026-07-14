<template>
    <div>
        <h5 class="card-title text-info"><i class="fa fa-user"></i> Datos del Paciente</h5>
        <hr />
        <div class="row">
            <div class="col-md-3">
                <dl>
                    <dt># Documento</dt>
                    <dd>{{ registro.num_docu || "" }}</dd>
                </dl>
            </div>
            <div class="col-md-4">
                <dl>
                    <dt>Nombres</dt>
                    <dd>{{ registro.nom_pacien || registro.nombres || "" }}</dd>
                </dl>
            </div>
            <div class="col-md-2">
                <dl>
                    <dt>Sexo</dt>
                    <dd>{{ registro.sexo || "" }}</dd>
                </dl>
            </div>
            <div class="col-md-2">
                <dl>
                    <dt>Fec. Nacimi</dt>
                    <dd>{{ fechaFormateada }}</dd>
                </dl>
            </div>
            <div class="col-md-1">
                <dl>
                    <dt>Edad</dt>
                    <dd>{{ edadCalculada }}</dd>
                </dl>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <dl>
                    <dt>Dirección</dt>
                    <dd>{{ registro.direccion || "" }}</dd>
                </dl>
            </div>
            <div class="col-md-3">
                <dl>
                    <dt>Télefono</dt>
                    <dd>{{ registro.tel || "" }}</dd>
                </dl>
            </div>
            <div class="col-md-5">
                <dl>
                    <dt>E - Mail</dt>
                    <dd>{{ registro.email || "" }}</dd>
                </dl>
            </div>
        </div>
    </div>
</template>
<script setup>
import { computed } from "vue";

import { normalizarFecha, calcularEdad } from "@/helpers/dateUtils.js";

const props = defineProps({
    registro: {
        type: Object,
        required: true,
    },
});

const fechaFormateada = computed(() => {
    const val = props.registro.fec_naci;
    if (!val) return "";
    return normalizarFecha(val) || val;
});
const edadCalculada = computed(() => {
    const val = props.registro.fec_naci;
    if (!val) return "";
    const edad = calcularEdad(val);
    return edad !== null ? edad : "";
});
</script>

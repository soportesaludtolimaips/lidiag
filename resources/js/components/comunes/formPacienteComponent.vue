<!-- eslint-disable vue/no-mutating-props -->
<template>
    <div>
        <h5 class="card-title text-info"><i class="fa fa-user"></i> Datos del Paciente</h5>
        <hr />
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label class="control-label" for="num_docu">
                        # Documento <span class="text-danger">*</span>
                    </label>
                    <input
                        type="text"
                        id="num_docu"
                        name="num_docu"
                        v-model="registro.num_docu"
                        class="form-control"
                        :class="getInputClass(statusNumDocu)"
                        placeholder="# de Documento"
                        @blur="touch('num_docu')"
                    />
                    <div class="invalid-feedback d-block" v-if="statusNumDocu.isInvalid">
                        {{ statusNumDocu.message }}
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="form-group">
                    <label class="control-label" for="nombres">
                        Nombres <span class="text-danger">*</span>
                    </label>
                    <input
                        type="text"
                        id="nombres"
                        name="nombres"
                        v-model="registro.nombres"
                        class="form-control"
                        :class="getInputClass(statusNombres)"
                        placeholder="Nombres completos"
                        @blur="touch('nombres')"
                    />
                    <div class="invalid-feedback d-block" v-if="statusNombres.isInvalid">
                        {{ statusNombres.message }}
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label class="control-label" for="sexo">Sexo</label>
                    <select
                        id="sexo"
                        name="sexo"
                        v-model="registro.sexo"
                        class="form-control"
                        :class="getInputClass(statusSexo)"
                        @blur="touch('sexo')"
                    >
                        <option value="">—</option>
                        <option value="M">M</option>
                        <option value="F">F</option>
                        <option value="O">Otro</option>
                        <option
                            v-if="registro.sexo && !['M', 'F', 'O', ''].includes(registro.sexo)"
                            :value="registro.sexo"
                        >
                            {{ registro.sexo }}
                        </option>
                    </select>
                    <div class="invalid-feedback d-block" v-if="statusSexo.isInvalid">
                        {{ statusSexo.message }}
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label class="control-label" for="fec_naci">Fec. Nacimiento</label>
                    <input
                        type="date"
                        id="fec_naci"
                        name="fec_naci"
                        :value="registro.fec_naci"
                        @input="registro.fec_naci = $event.target.value"
                        @blur="touch('fec_naci')"
                        class="form-control"
                        :class="getInputClass(statusFecNaci)"
                    />
                    <div class="invalid-feedback d-block" v-if="statusFecNaci.isInvalid">
                        {{ statusFecNaci.message }}
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label class="control-label" for="edad">Edad</label>
                    <input
                        type="number"
                        id="edad"
                        name="edad"
                        :value="edad"
                        class="form-control"
                        readonly
                    />
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label class="control-label" for="direccion">Dirección</label>
                    <input
                        type="text"
                        id="direccion"
                        name="direccion"
                        v-model="registro.direccion"
                        class="form-control"
                        :class="getInputClass(statusDireccion)"
                        placeholder="Dirección de residencia"
                        @blur="touch('direccion')"
                    />
                    <div class="invalid-feedback d-block" v-if="statusDireccion.isInvalid">
                        {{ statusDireccion.message }}
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label class="control-label" for="telefono">Teléfono</label>
                    <input
                        type="tel"
                        id="telefono"
                        name="telefono"
                        v-model="registro.tel"
                        class="form-control"
                        :class="getInputClass(statusTel)"
                        placeholder="Ej: 3001234567"
                        @blur="touch('tel')"
                    />
                    <div class="invalid-feedback d-block" v-if="statusTel.isInvalid">
                        {{ statusTel.message }}
                    </div>
                </div>
            </div>
            <div class="col-md-5">
                <div class="form-group">
                    <label class="control-label" for="email">E-Mail</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        v-model="registro.email"
                        class="form-control"
                        :class="getInputClass(statusEmail)"
                        placeholder="ejemplo@correo.com"
                        @blur="touch('email')"
                    />
                    <div class="invalid-feedback d-block" v-if="statusEmail.isInvalid">
                        {{ statusEmail.message }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup>
import { computed, watch } from "vue";

import { useFormValidation } from "@/composables/useFormValidation";
import { normalizarFecha, calcularEdad } from "@/helpers/dateUtils.js";

const props = defineProps({
    registro: {
        type: Object,
        required: true,
    },
    errores: {
        type: Object,
        default: () => ({}),
    },
    mostrarErrores: {
        type: Boolean,
        default: true,
    },
});

const { touch, getFieldStatus, getInputClass, validators } = useFormValidation();

/**
 * Estado reactivo del campo número de documento.
 */
const statusNumDocu = computed(() =>
    getFieldStatus(
        "num_docu",
        props.registro.num_docu,
        [validators.required, validators.documento],
        props.mostrarErrores ? props.errores : {}
    )
);

/**
 * Estado reactivo del campo nombres del paciente.
 */
const statusNombres = computed(() =>
    getFieldStatus(
        "nombres",
        props.registro.nombres,
        [
            validators.required,
            validators.minLength(3, "El nombre debe tener al menos 3 caracteres"),
        ],
        props.mostrarErrores ? props.errores : {}
    )
);

/**
 * Estado reactivo del campo sexo.
 */
const statusSexo = computed(() =>
    getFieldStatus("sexo", props.registro.sexo, [], props.mostrarErrores ? props.errores : {})
);

/**
 * Estado reactivo del campo fecha de nacimiento.
 */
const statusFecNaci = computed(() =>
    getFieldStatus(
        "fec_naci",
        props.registro.fec_naci,
        [validators.fechaNoFutura],
        props.mostrarErrores ? props.errores : {}
    )
);

/**
 * Estado reactivo del campo dirección.
 */
const statusDireccion = computed(() =>
    getFieldStatus(
        "direccion",
        props.registro.direccion,
        [],
        props.mostrarErrores ? props.errores : {}
    )
);

/**
 * Estado reactivo del campo teléfono.
 */
const statusTel = computed(() =>
    getFieldStatus(
        "tel",
        props.registro.tel,
        [validators.telefono],
        props.mostrarErrores ? props.errores : {}
    )
);

/**
 * Estado reactivo del campo email.
 */
const statusEmail = computed(() =>
    getFieldStatus(
        "email",
        props.registro.email,
        [validators.email],
        props.mostrarErrores ? props.errores : {}
    )
);

/**
 * Calcula la edad del paciente a partir de la fecha de nacimiento del registro.
 *
 * @returns {number|null} Edad calculada o null si no hay fecha válida.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-06-01
 */
const edad = computed(() => calcularEdad(props.registro.fec_naci));

/**
 * Observa cambios en el objeto registro para normalizar pat_birthdate
 * y asignarlo a fec_naci cuando esté disponible.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-06-01
 */
watch(
    () => props.registro,
    (val) => {
        if (val?.pat_birthdate) {
            const normalizada = normalizarFecha(val.pat_birthdate);
            if (normalizada) {
                val.fec_naci = normalizada;
            }
        }
    },
    { deep: true, immediate: true }
);
</script>

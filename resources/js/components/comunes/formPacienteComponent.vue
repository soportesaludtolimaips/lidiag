<template>
    <div>
        <h5 class="card-title text-info">
            <i class="fa fa-user"></i> Datos del Paciente
        </h5>
        <hr>
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label class="control-label"># Documento</label>
                    <input type="text" id="num_docu" name="num_docu" v-model="registro.num_docu" class="form-control"
                        placeholder="# de Documento" />
                    <span class="text-danger" v-if="mostrarErrores && errores.num_docu">
                        {{ errores.num_docu[0] }}
                    </span>
                </div>
            </div>
            <div class="col-md-8">
                <div class="form-group">
                    <label class="control-label">Nombres</label>
                    <input type="text" id="nombres" name="nombres" v-model="registro.nombres" class="form-control"
                        placeholder="Nombres" />
                    <span class="text-danger" v-if="mostrarErrores && errores.nombres">
                        {{ errores.nombres[0] }}
                    </span>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label class="control-label">Sexo</label>
                    <input type="text" id="sexo" name="sexo" v-model="registro.sexo" class="form-control" />
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label class="control-label">Fec. Nacimi</label>
                    <input type="date" id="fec_naci" name="fec_naci" :value="registro.fec_naci" @input="registro.fec_naci = $event.target.value" class="form-control" />
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label class="control-label">Edad</label>
                    <input type="number" id="edad" name="edad" :value="edad" class="form-control" readonly />
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label class="control-label">Dirección</label>
                    <input type="text" id="direccion" name="direccion" v-model="registro.direccion"
                        class="form-control" />
                    <span class="text-danger" v-if="mostrarErrores && errores.direccion">
                        {{ errores.direccion[0] }}
                    </span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label class="control-label">Télefono</label>
                    <input type="number" id="telefono" name="telefono" v-model="registro.tel" class="form-control" />
                    <span class="text-danger" v-if="mostrarErrores && errores.tel">
                        {{ errores.tel[0] }}
                    </span>
                </div>
            </div>
            <div class="col-md-5">
                <div class="form-group">
                    <label class="control-label">E - Mail</label>
                    <input type="email" id="email" name="email" v-model="registro.email" class="form-control" />
                    <span class="text-danger" v-if="mostrarErrores && errores.email">
                        {{ errores.email[0] }}</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { normalizarFecha, calcularEdad } from "@/helpers/dateUtils.js";

export default {
    props: {
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
    },
    computed: {
        edad() {
            return calcularEdad(this.registro.fec_naci);
        },
    },
    watch: {
        registro: {
            handler: function (val) {
                if (val.pat_birthdate) {
                    const normalizada = normalizarFecha(val.pat_birthdate);
                    if (normalizada) {
                        val.fec_naci = normalizada;
                    }
                }
            },
            deep: true,
            immediate: true,
        },
    },
};
</script>

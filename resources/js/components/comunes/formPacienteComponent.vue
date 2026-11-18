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
                        <i
                            v-if="buscandoDocumento"
                            class="fa fa-spinner fa-spin text-info ml-1"
                        ></i>
                    </label>
                    <input
                        type="text"
                        id="num_docu"
                        name="num_docu"
                        v-model="registro.num_docu"
                        class="form-control"
                        :class="getInputClass(statusNumDocu)"
                        placeholder="# de Documento"
                        @blur="onNumDocuBlur()"
                        @keyup.enter="buscarPacienteJerarquico()"
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
/* eslint-disable vue/no-mutating-props */
import axios from "axios";
import { computed, ref, watch } from "vue";

import { useFormValidation } from "@/composables/useFormValidation";
import { toastMessage } from "@/helpers/alerts.js";
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

const buscandoDocumento = ref(false);
let ultimoDocumentoBuscado = "";

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
 * Ejecuta la validación táctil del campo documento e inicia la búsqueda en cascada.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-11-18
 */
async function onNumDocuBlur() {
    touch("num_docu");
    await buscarPacienteJerarquico();
}

/**
 * Búsqueda jerárquica de pacientes:
 * 1. Búsqueda local en la base de datos de LIDiag (/api/pacientes/listar).
 * 2. Si no existe, verifica si la sede cuenta con interfaz externa (ej. SAHI) y consulta la información.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-11-18
 */
async function buscarPacienteJerarquico() {
    const doc = String(props.registro.num_docu || "").trim();
    if (!doc || doc.length < 3) return;
    if (doc === ultimoDocumentoBuscado) return;
    if (buscandoDocumento.value) return;

    try {
        buscandoDocumento.value = true;
        const sedeId =
            props.registro.sede_id ||
            sessionStorage.getItem("ST-sede") ||
            localStorage.getItem("ST-sede") ||
            1;

        // 1. Búsqueda Local en LIDiag
        const resLocal = await axios.post("/api/pacientes/listar", {
            bus_documento: doc,
            sede_id: sedeId,
            limite: 1,
        });

        const listaLocal = resLocal.data?.data?.data || resLocal.data?.data || [];
        if (Array.isArray(listaLocal) && listaLocal.length > 0) {
            const pac = listaLocal[0];
            props.registro.paciente_id = pac.id;
            const nombres = `${pac.nombres || ""} ${pac.seg_nom || ""}`.trim();
            const apellidos = `${pac.pri_ape || ""} ${pac.seg_ape || ""}`.trim();
            props.registro.nombres =
                pac.nombre_completo || `${nombres} ${apellidos}`.trim() || pac.nombres;
            if (pac.fec_naci) {
                props.registro.fec_naci = normalizarFecha(pac.fec_naci) || pac.fec_naci;
            }
            if (pac.sexo) {
                const s = String(pac.sexo).trim().toUpperCase();
                props.registro.sexo =
                    s === "1" || s === "M" ? "M" : s === "2" || s === "F" ? "F" : s;
            }
            if (pac.direccion) props.registro.direccion = pac.direccion;
            if (pac.tel || pac.cel) props.registro.tel = pac.tel || pac.cel;
            if (pac.email) props.registro.email = pac.email;
            if (pac.tipo_documento_id) props.registro.tipo_documento_id = pac.tipo_documento_id;

            ultimoDocumentoBuscado = doc;
            toastMessage({
                heading: "Paciente encontrado",
                text: `Datos de ${props.registro.nombres} cargados desde la base de datos local.`,
                icon: "info",
                hideAfter: 3500,
            });
            return;
        }

        // 2. Búsqueda en Interfaz Externa (Fallback por Sede)
        let tieneInterface = false;
        let softwareInterface = null;
        try {
            const resSede = await axios.get(`/api/config-sedes/${sedeId}/buscarPorId`);
            const dataSede = resSede.data?.data;
            if (dataSede?.interface_software) {
                tieneInterface = true;
                softwareInterface = String(dataSede.interface_software).toLowerCase();
            }
        } catch (_) {
            tieneInterface = false;
        }

        if (tieneInterface && softwareInterface === "sahi") {
            try {
                const resSahi = await axios.get(
                    `/api/interface-sahi/buscar-paciente/${encodeURIComponent(doc)}`
                );
                if (resSahi.status === 200 && resSahi.data?.data) {
                    const c = resSahi.data.data;
                    const nombres = `${c.PrimerNombre || ""} ${c.SegundoNombre || ""}`.trim();
                    const apellidos = `${c.PrimerApellido || ""} ${c.SegundoApellido || ""}`.trim();
                    props.registro.nombres = `${nombres} ${apellidos}`.trim();
                    if (c.FecNacimiento) {
                        props.registro.fec_naci =
                            normalizarFecha(c.FecNacimiento) || c.FecNacimiento;
                    }
                    if (c.IdSexo) {
                        const s = String(c.IdSexo).trim().toUpperCase();
                        props.registro.sexo =
                            s === "1" || s === "M" ? "M" : s === "2" || s === "F" ? "F" : s;
                    }
                    if (c.DirCasa) props.registro.direccion = c.DirCasa;
                    if (c.TelCasa) props.registro.tel = c.TelCasa;

                    ultimoDocumentoBuscado = doc;
                    toastMessage({
                        heading: "Paciente localizado en SAHI",
                        text: `Información demográfica obtenida desde el sistema institucional (SAHI).`,
                        icon: "success",
                        hideAfter: 4000,
                    });
                    return;
                }
            } catch (errSahi) {
                if (errSahi.response?.status === 404) {
                    toastMessage({
                        heading: "No registrado",
                        text: "El paciente no se encuentra en LIDiag ni en el sistema externo de la sede. Puede diligenciar los datos manualmente.",
                        icon: "warning",
                        hideAfter: 4500,
                    });
                }
            }
        } else if (!tieneInterface) {
            toastMessage({
                heading: "Paciente no encontrado",
                text: "No existe en la base de datos local y la sede no cuenta con interfaz externa conectada.",
                icon: "info",
                hideAfter: 4000,
            });
        }

        ultimoDocumentoBuscado = doc;
    } catch (_) {
        // Fallback silencioso para no bloquear el llenado manual
    } finally {
        buscandoDocumento.value = false;
    }
}

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
 * y el sexo cuando provenga en formatos numéricos o códigos externos (ej. SAHI: 1 = M, 2 = F).
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-06-01
 */
watch(
    () => props.registro,
    (val) => {
        if (!val) return;

        if (val.pat_birthdate) {
            const normalizada = normalizarFecha(val.pat_birthdate);
            if (normalizada) {
                val.fec_naci = normalizada;
            }
        }

        // Normalización de sexo (SAHI: 1 = Masculino, 2 = Femenino, o IdSexo / pat_sex)
        const rawSexo = val.sexo ?? val.IdSexo ?? val.pat_sex;
        if (rawSexo !== undefined && rawSexo !== null) {
            const strSexo = String(rawSexo).trim().toUpperCase();
            if (strSexo === "1" || strSexo === "M" || strSexo === "MASCULINO") {
                if (val.sexo !== "M") val.sexo = "M";
            } else if (strSexo === "2" || strSexo === "F" || strSexo === "FEMENINO") {
                if (val.sexo !== "F") val.sexo = "F";
            } else if (strSexo === "O" || strSexo === "OTRO" || strSexo === "3") {
                if (val.sexo !== "O") val.sexo = "O";
            }
        }
    },
    { deep: true, immediate: true }
);
</script>

<template>
    <div>
        <h4 class="card-title">Números WhatsApp por sede</h4>
        <h6 class="card-subtitle">
            Administración de los números de teléfono asociados a cada sede para el envío de
            mensajes.
        </h6>

        <div v-if="loadingNumeros" class="text-center m-t-40">
            <i class="fa fa-spinner fa-spin fa-2x"></i>
            <p class="text-muted m-t-10">Cargando números...</p>
        </div>

        <div v-else-if="errorNumeros" class="alert alert-danger m-t-20">
            <i class="fa fa-exclamation-triangle"></i>
            {{ errorNumeros }}
        </div>

        <div v-else-if="registrosNumeros.length === 0" class="text-center m-t-40">
            <p class="text-muted">
                No hay números WhatsApp configurados. Cree un nuevo registro para comenzar.
            </p>
        </div>

        <div class="table-responsive m-t-40" v-else>
            <table
                id="tblWhatsappNumbers"
                ref="dtNumeros"
                class="display nowrap table table-hover table-striped table-bordered"
                cellspacing="0"
                width="100%"
            >
                <thead>
                    <tr>
                        <th>Sede</th>
                        <th>Phone Number ID</th>
                        <th>Número</th>
                        <th>Descripción</th>
                        <th>Activo</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in registrosNumeros" :key="item.id">
                        <td>{{ item.sede ? item.sede.nom_sede : "—" }}</td>
                        <td>{{ item.phone_number_id }}</td>
                        <td>{{ item.numero }}</td>
                        <td>{{ item.descripcion || "—" }}</td>
                        <td>
                            <span
                                class="label"
                                :class="item.activo ? 'label-success' : 'label-danger'"
                            >
                                {{ item.activo ? "Activo" : "Inactivo" }}
                            </span>
                        </td>
                        <td class="text-nowrap">
                            <button
                                type="button"
                                class="btn waves-effect waves-light btn-rounded btn-outline-warning btn-sm m-r-5"
                                @click="editar(item)"
                            >
                                <i class="fa fa-pencil"></i>
                            </button>
                            <button
                                type="button"
                                class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm"
                                @click="eliminarRegistro(item.id)"
                                :disabled="loadingEliminarId === item.id"
                            >
                                <i
                                    class="fa"
                                    :class="
                                        loadingEliminarId === item.id
                                            ? 'fa-spinner fa-spin'
                                            : 'fa-trash'
                                    "
                                ></i>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div
            class="right-sidebar"
            :class="{ 'shw-rside': sidebarVisible }"
            style="overflow-x: hidden; overflow-y: scroll; height: 100%"
        >
            <div class="slimscrollright">
                <div class="rpanel-title">
                    {{ tituloModal }}
                    <span>
                        <i class="ti-close right-side-toggle" @click="cerrarFormSidebar"></i>
                    </span>
                </div>
                <div class="r-panel-body">
                    <form action="#">
                        <div class="form-body">
                            <div class="row p-t-20">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label"
                                            >Sede <span class="text-danger">*</span></label
                                        >
                                        <select
                                            v-model="form.sede_id"
                                            class="form-control"
                                            :class="{ 'is-invalid': errores.sede_id }"
                                        >
                                            <option value="">Seleccione una sede</option>
                                            <option v-for="s in sedes" :key="s.id" :value="s.id">
                                                {{ s.nom_sede }}
                                            </option>
                                        </select>
                                        <span class="text-danger" v-if="errores.sede_id">{{
                                            errores.sede_id[0]
                                        }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label"
                                            >Phone Number ID
                                            <span class="text-danger">*</span></label
                                        >
                                        <input
                                            type="text"
                                            v-model="form.phone_number_id"
                                            class="form-control"
                                            :class="{ 'is-invalid': errores.phone_number_id }"
                                            placeholder="ID de Meta"
                                        />
                                        <span class="text-danger" v-if="errores.phone_number_id">{{
                                            errores.phone_number_id[0]
                                        }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="control-label"
                                            >Número <span class="text-danger">*</span></label
                                        >
                                        <input
                                            type="text"
                                            v-model="form.numero"
                                            class="form-control"
                                            :class="{ 'is-invalid': errores.numero }"
                                            placeholder="573001234567"
                                        />
                                        <span class="text-danger" v-if="errores.numero">{{
                                            errores.numero[0]
                                        }}</span>
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <div class="form-group">
                                        <label class="control-label">Descripción</label>
                                        <input
                                            type="text"
                                            v-model="form.descripcion"
                                            class="form-control"
                                            placeholder="Ej: Ecografías"
                                        />
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <div class="checkbox checkbox-success m-t-25">
                                            <input
                                                type="checkbox"
                                                id="form_activo_numero"
                                                v-model="form.activo"
                                                :true-value="1"
                                                :false-value="0"
                                            />
                                            <label for="form_activo_numero"> Activo </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-actions mt-3">
                                <button
                                    type="button"
                                    class="btn btn-success m-r-5"
                                    @click="guardarRegistro()"
                                    v-if="!actualizar"
                                    :disabled="loadingGuardar"
                                >
                                    <i
                                        class="fa"
                                        :class="loadingGuardar ? 'fa-spinner fa-spin' : 'fa-check'"
                                    ></i>
                                    Guardar
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-warning m-r-5"
                                    v-if="actualizar"
                                    @click="guardarRegistro()"
                                    :disabled="loadingGuardar"
                                >
                                    <i
                                        class="fa"
                                        :class="loadingGuardar ? 'fa-spinner fa-spin' : 'fa-pencil'"
                                    ></i>
                                    Actualizar
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-inverse"
                                    @click="cerrarFormSidebar()"
                                >
                                    Cancelar
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup>
import { onBeforeUnmount, onMounted, ref } from "vue";
import axios from "axios";

import { useConfigCrud } from "@/composables/useConfigCrud.js";
import { useConfigForm } from "@/composables/useConfigForm.js";
import { useDataTable } from "@/composables/useDataTable.js";

const props = defineProps({
    sedes: {
        type: Array,
        default: () => [],
    },
});

const INIT_FORM_NUMEROS = {
    sede_id: "",
    phone_number_id: "",
    numero: "",
    descripcion: "",
    activo: 1,
};

const {
    form,
    errores,
    tituloModal,
    actualizar,
    id,
    setErrors,
    resetForm,
    prepararNuevo,
    prepararEdicion,
} = useConfigForm({ numeros: INIT_FORM_NUMEROS });
const { loadingGuardar, loadingEliminarId, guardar, eliminar } = useConfigCrud({
    resolveUrl: () => "/api/config-sede-whatsapp-numbers",
    validate: validarNumeros,
});
const registrosNumeros = ref([]);
const loadingNumeros = ref(false);
const errorNumeros = ref("");
const dtNumeros = ref(null);
const dtNumerosApi = useDataTable(() => dtNumeros.value || "#tblWhatsappNumbers");
const sidebarVisible = ref(false);

function validarNumeros(payload) {
    const errs = {};
    if (!payload.sede_id) errs.sede_id = ["La sede es obligatoria."];
    if (!payload.phone_number_id) errs.phone_number_id = ["El Phone Number ID es obligatorio."];
    if (!payload.numero) errs.numero = ["El número es obligatorio."];
    return errs;
}
async function listarNumeros() {
    if (loadingNumeros.value) return;
    loadingNumeros.value = true;
    errorNumeros.value = "";
    try {
        const res = await axios.get("/api/config-sede-whatsapp-numbers");
        registrosNumeros.value = res.data.data;
        dtNumerosApi.refresh(true);
    } catch (error) {
        errorNumeros.value =
            error?.response?.data?.message || "No fue posible cargar los números WhatsApp.";
        registrosNumeros.value = [];
    } finally {
        loadingNumeros.value = false;
    }
}
async function guardarRegistro() {
    if (loadingGuardar.value) return;
    setErrors({});
    const payload = {
        ...form.value,
        activo: form.value.activo === 1 || form.value.activo === true,
    };
    const result = await guardar(payload, { actualizar: actualizar.value, id: id.value });
    if (result.errores) {
        setErrors(result.errores);
        return;
    }
    if (result.ok) {
        listarNumeros();
        cerrarFormSidebar();
        limpiar();
    }
}
async function eliminarRegistro(registroId) {
    const deleted = await eliminar(registroId);
    if (deleted) {
        listarNumeros();
    }
}
function mostrarRegistro(data = {}) {
    if (actualizar.value) {
        prepararEdicion("Actualizar número WhatsApp", data, (d) => ({
            sede_id: d.sede_id || "",
            phone_number_id: d.phone_number_id || "",
            numero: d.numero || "",
            descripcion: d.descripcion || "",
            activo: d.activo ? 1 : 0,
        }));
    } else {
        prepararNuevo("numeros", "Nuevo número WhatsApp");
    }
    abrirFormSidebar();
}
function editar(item) {
    actualizar.value = true;
    mostrarRegistro(item);
}
function nuevo() {
    actualizar.value = false;
    mostrarRegistro();
}
function limpiar() {
    resetForm("numeros");
}
function abrirFormSidebar() {
    sidebarVisible.value = true;
}
function cerrarFormSidebar() {
    sidebarVisible.value = false;
}

defineExpose({ nuevo, listar: listarNumeros });

onMounted(() => {
    listarNumeros();
});

onBeforeUnmount(() => {
    dtNumerosApi.destroy();
});
</script>
<style scoped>
.right-sidebar {
    position: fixed;
    right: -70%;
    width: 70%;
    display: none;
    z-index: 1100;
    background: #ffffff;
    top: 0px;
    padding-bottom: 20px;
    height: 100%;
    -webkit-box-shadow: 5px 1px 40px rgba(0, 0, 0, 0.1);
    box-shadow: 5px 1px 40px rgba(0, 0, 0, 0.1);
    -webkit-transition: all 0.3s ease;
    -o-transition: all 0.3s ease;
    transition: all 0.3s ease;
}

.shw-rside {
    right: 0px;
    width: 70%;
    display: block;
    overflow-y: auto;
}
</style>

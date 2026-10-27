<template>
    <div>
        <!-- ============================================================== -->
        <!-- Bread crumb and right sidebar toggle -->
        <!-- ============================================================== -->
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">Seguridad</h3>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a role="button" tabindex="0">Inicio</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a role="button" tabindex="0">Seguridad</a>
                    </li>
                    <li class="breadcrumb-item active">Perfil</li>
                </ol>
            </div>
            <div class="col-md-7 align-self-center text-right d-none d-md-block">
                <button
                    type="button"
                    class="btn btn-sm btn-outline-secondary btn-action-clinical m-r-5"
                    :disabled="loadingObtener"
                    title="Recargar datos del perfil (F2)"
                    @click="obtenerRegistro()"
                >
                    <i class="fa fa-refresh mr-1" :class="{ 'fa-spin': loadingObtener }"></i>
                    Refrescar <span class="badge badge-light border ml-1">F2</span>
                </button>
            </div>
        </div>
        <!-- ============================================================== -->
        <!-- End Bread crumb and right sidebar toggle -->
        <!-- ============================================================== -->

        <!-- ============================================================== -->
        <!-- Start Page Content -->
        <!-- ============================================================== -->

        <div class="row">
            <!-- Column -->
            <div class="col-lg-4 col-xlg-3 col-md-5">
                <div class="card">
                    <div class="card-body">
                        <div class="text-center m-t-30">
                            <img
                                v-if="avatarPreview"
                                :src="avatarPreview"
                                :alt="registro.name"
                                class="img-circle"
                                width="150"
                            />
                            <img
                                v-else-if="registro.imagen_perfil"
                                :src="'/files/' + registro.imagen_perfil"
                                :alt="registro.name"
                                class="img-circle"
                                width="150"
                            />
                            <img
                                v-else
                                :src="'/admin-wrap/assets/images/users/avatar_default.png'"
                                :alt="registro.name"
                                class="img-circle"
                                width="150"
                            />
                            <h4 class="card-title m-t-10">{{ registro.name }}</h4>

                            <div class="row">
                                <div class="col-md-12">
                                    <div
                                        class="fileupload btn btn-danger btn-rounded waves-effect waves-light"
                                    >
                                        <span>
                                            <i class="ion-upload m-r-5"></i>Seleccionar archivo
                                        </span>
                                        <input
                                            type="file"
                                            class="upload"
                                            accept="image/*"
                                            @change="obtenerArchivo"
                                        />
                                    </div>
                                </div>
                            </div>
                            <hr />
                            <h6 class="card-subtitle m-b-10">Firma digital</h6>
                            <img
                                v-if="firmaPreview"
                                :src="firmaPreview"
                                alt="Firma"
                                class="m-b-10"
                                style="max-width: 100%; max-height: 80px"
                            />
                            <img
                                v-else-if="registro.firma && !firmaError"
                                :src="'/files/' + registro.firma"
                                alt="Firma"
                                class="m-b-10"
                                style="max-width: 100%; max-height: 80px"
                                @error="firmaError = true"
                            />
                            <p
                                v-else-if="registro.firma && firmaError"
                                class="text-warning small m-b-10"
                            >
                                <i class="fa fa-exclamation-triangle"></i> Firma no disponible
                            </p>
                            <p v-else class="text-muted small m-b-10">Sin firma</p>
                            <div class="row">
                                <div class="col-md-12">
                                    <div
                                        class="fileupload btn btn-info btn-rounded waves-effect waves-light btn-sm"
                                    >
                                        <span>
                                            <i class="ion-upload m-r-5"></i>Seleccionar firma
                                        </span>
                                        <input
                                            type="file"
                                            class="upload"
                                            accept="image/*"
                                            @change="obtenerFirma"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <hr />
                    </div>
                    <div class="card-body">
                        <small class="text-muted">Email address</small>
                        <h6>{{ registro.email }}</h6>
                    </div>
                </div>
            </div>
            <!-- Column -->
            <!-- Column -->
            <div class="col-lg-8 col-xlg-9 col-md-7">
                <div class="card">
                    <!-- Nav tabs -->
                    <ul class="nav nav-tabs profile-tab" role="tablist">
                        <li class="nav-item">
                            <a
                                class="nav-link"
                                :class="{ active: tabActiva === 'settings' }"
                                role="tab"
                                href="javascript:void(0)"
                                @click="tabActiva = 'settings'"
                            >
                                <i class="fa fa-user m-r-5"></i> Datos Personales
                            </a>
                        </li>
                        <li class="nav-item">
                            <a
                                class="nav-link"
                                :class="{ active: tabActiva === 'password' }"
                                role="tab"
                                href="javascript:void(0)"
                                @click="tabActiva = 'password'"
                            >
                                <i class="fa fa-key m-r-5"></i> Cambiar Contraseña
                            </a>
                        </li>
                    </ul>
                    <!-- Tab panes -->
                    <div class="tab-content">
                        <!-- Pestaña 1: Datos Personales -->
                        <div
                            class="tab-pane"
                            :class="{ active: tabActiva === 'settings' }"
                            id="settings"
                            role="tabpanel"
                            v-show="tabActiva === 'settings'"
                        >
                            <div class="card-body">
                                <form class="form-horizontal form-material">
                                    <div class="form-group">
                                        <label class="col-md-6"># Documento</label>
                                        <div class="col-md-6">
                                            <input
                                                type="text"
                                                id="num_docu"
                                                name="num_docu"
                                                v-model="registro.num_docu"
                                                placeholder="Ingrese aqui el número de documento"
                                                class="form-control form-control-line"
                                            />
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-12">Nombres</label>
                                        <div class="col-md-12">
                                            <input
                                                type="text"
                                                id="name"
                                                name="name"
                                                v-model="registro.name"
                                                placeholder="Ingrese aqui los nombres del usuario"
                                                class="form-control form-control-line"
                                            />
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="example-email" class="col-md-12">Email</label>
                                        <div class="col-md-12">
                                            <input
                                                type="email"
                                                id="email"
                                                v-model="registro.email"
                                                placeholder="Ingrese aqui el email"
                                                class="form-control form-control-line"
                                            />
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-sm-12">
                                            <button
                                                type="button"
                                                class="btn btn-success btn-action-clinical m-r-5"
                                                :disabled="loadingActualizar"
                                                title="Guardar cambios del perfil (Ctrl+S)"
                                                @click="actualizarPerfil()"
                                            >
                                                <i
                                                    v-if="loadingActualizar"
                                                    class="fa fa-spinner fa-spin mr-1"
                                                ></i>
                                                <i v-else class="fa fa-pencil mr-1"></i> Actualizar
                                                Perfil
                                                <span class="badge badge-light text-dark ml-1">
                                                    Ctrl+S
                                                </span>
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Pestaña 2: Cambiar Contraseña -->
                        <div
                            class="tab-pane"
                            :class="{ active: tabActiva === 'password' }"
                            id="passwordTab"
                            role="tabpanel"
                            v-show="tabActiva === 'password'"
                        >
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <i class="fa fa-shield mr-1"></i>
                                    Para cambiar su contraseña, ingrese su
                                    <strong>contraseña actual</strong> y defina una
                                    <strong>nueva contraseña segura</strong>.
                                </div>

                                <form
                                    class="form-horizontal form-material"
                                    @submit.prevent="cambiarPasswordUsuario()"
                                >
                                    <!-- Contraseña actual -->
                                    <div class="form-group">
                                        <label class="col-md-12 control-label"
                                            >Contraseña actual
                                            <span class="text-danger">*</span></label
                                        >
                                        <div class="col-md-12">
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"
                                                        ><i class="ti-lock"></i
                                                    ></span>
                                                </div>
                                                <input
                                                    :type="
                                                        mostrarPassword.current
                                                            ? 'text'
                                                            : 'password'
                                                    "
                                                    id="current_password"
                                                    v-model="passwordForm.current_password"
                                                    placeholder="Ingrese su contraseña actual"
                                                    class="form-control form-control-line"
                                                    autocomplete="current-password"
                                                />
                                                <div class="input-group-append">
                                                    <button
                                                        type="button"
                                                        class="btn btn-secondary waves-effect"
                                                        @click="
                                                            mostrarPassword.current =
                                                                !mostrarPassword.current
                                                        "
                                                        title="Mostrar u ocultar contraseña"
                                                    >
                                                        <i
                                                            :class="
                                                                mostrarPassword.current
                                                                    ? 'fa fa-eye-slash'
                                                                    : 'fa fa-eye'
                                                            "
                                                        ></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <span
                                                class="text-danger"
                                                v-if="erroresPassword.current_password"
                                            >
                                                {{ erroresPassword.current_password[0] }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Nueva contraseña -->
                                    <div class="form-group">
                                        <label class="col-md-12 control-label"
                                            >Nueva contraseña
                                            <span class="text-danger">*</span></label
                                        >
                                        <div class="col-md-12">
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"
                                                        ><i class="ti-key"></i
                                                    ></span>
                                                </div>
                                                <input
                                                    :type="
                                                        mostrarPassword.nueva ? 'text' : 'password'
                                                    "
                                                    id="new_password"
                                                    v-model="passwordForm.password"
                                                    placeholder="Ingrese la nueva contraseña"
                                                    class="form-control form-control-line"
                                                    autocomplete="new-password"
                                                />
                                                <div class="input-group-append">
                                                    <button
                                                        type="button"
                                                        class="btn btn-secondary waves-effect"
                                                        @click="
                                                            mostrarPassword.nueva =
                                                                !mostrarPassword.nueva
                                                        "
                                                        title="Mostrar u ocultar contraseña"
                                                    >
                                                        <i
                                                            :class="
                                                                mostrarPassword.nueva
                                                                    ? 'fa fa-eye-slash'
                                                                    : 'fa fa-eye'
                                                            "
                                                        ></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <span
                                                class="text-danger"
                                                v-if="erroresPassword.password"
                                            >
                                                {{ erroresPassword.password[0] }}
                                            </span>

                                            <!-- Checklist interactivo de requisitos -->
                                            <div class="p-3 mt-2 bg-light rounded border">
                                                <small
                                                    class="font-weight-bold d-block text-muted mb-2"
                                                >
                                                    <i class="fa fa-info-circle mr-1"></i>
                                                    Requisitos de seguridad institucionales:
                                                </small>
                                                <div class="row small">
                                                    <div
                                                        class="col-md-6 mb-1"
                                                        :class="
                                                            reglasPassword.longitud
                                                                ? 'text-success'
                                                                : 'text-muted'
                                                        "
                                                    >
                                                        <i
                                                            class="fa"
                                                            :class="
                                                                reglasPassword.longitud
                                                                    ? 'fa-check-circle text-success'
                                                                    : 'fa-circle-o'
                                                            "
                                                        ></i>
                                                        Mínimo 8 caracteres
                                                    </div>
                                                    <div
                                                        class="col-md-6 mb-1"
                                                        :class="
                                                            reglasPassword.mayuscula
                                                                ? 'text-success'
                                                                : 'text-muted'
                                                        "
                                                    >
                                                        <i
                                                            class="fa"
                                                            :class="
                                                                reglasPassword.mayuscula
                                                                    ? 'fa-check-circle text-success'
                                                                    : 'fa-circle-o'
                                                            "
                                                        ></i>
                                                        Una letra mayúscula (A-Z)
                                                    </div>
                                                    <div
                                                        class="col-md-6 mb-1"
                                                        :class="
                                                            reglasPassword.minuscula
                                                                ? 'text-success'
                                                                : 'text-muted'
                                                        "
                                                    >
                                                        <i
                                                            class="fa"
                                                            :class="
                                                                reglasPassword.minuscula
                                                                    ? 'fa-check-circle text-success'
                                                                    : 'fa-circle-o'
                                                            "
                                                        ></i>
                                                        Una letra minúscula (a-z)
                                                    </div>
                                                    <div
                                                        class="col-md-6 mb-1"
                                                        :class="
                                                            reglasPassword.numero
                                                                ? 'text-success'
                                                                : 'text-muted'
                                                        "
                                                    >
                                                        <i
                                                            class="fa"
                                                            :class="
                                                                reglasPassword.numero
                                                                    ? 'fa-check-circle text-success'
                                                                    : 'fa-circle-o'
                                                            "
                                                        ></i>
                                                        Al menos un número (0-9)
                                                    </div>
                                                    <div
                                                        class="col-md-12"
                                                        :class="
                                                            reglasPassword.simbolo
                                                                ? 'text-success'
                                                                : 'text-muted'
                                                        "
                                                    >
                                                        <i
                                                            class="fa"
                                                            :class="
                                                                reglasPassword.simbolo
                                                                    ? 'fa-check-circle text-success'
                                                                    : 'fa-circle-o'
                                                            "
                                                        ></i>
                                                        Un carácter especial (!@#$%^&*_-)
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Confirmar nueva contraseña -->
                                    <div class="form-group">
                                        <label class="col-md-12 control-label"
                                            >Confirmar nueva contraseña
                                            <span class="text-danger">*</span></label
                                        >
                                        <div class="col-md-12">
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"
                                                        ><i class="ti-check"></i
                                                    ></span>
                                                </div>
                                                <input
                                                    :type="
                                                        mostrarPassword.confirmar
                                                            ? 'text'
                                                            : 'password'
                                                    "
                                                    id="confirmarPassword"
                                                    v-model="passwordForm.confirmarPassword"
                                                    placeholder="Confirme la nueva contraseña"
                                                    class="form-control form-control-line"
                                                    autocomplete="new-password"
                                                />
                                                <div class="input-group-append">
                                                    <button
                                                        type="button"
                                                        class="btn btn-secondary waves-effect"
                                                        @click="
                                                            mostrarPassword.confirmar =
                                                                !mostrarPassword.confirmar
                                                        "
                                                        title="Mostrar u ocultar contraseña"
                                                    >
                                                        <i
                                                            :class="
                                                                mostrarPassword.confirmar
                                                                    ? 'fa fa-eye-slash'
                                                                    : 'fa fa-eye'
                                                            "
                                                        ></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <span
                                                class="text-danger"
                                                v-if="erroresPassword.confirmarPassword"
                                            >
                                                {{ erroresPassword.confirmarPassword[0] }}
                                            </span>
                                            <span
                                                class="text-danger small d-block mt-1"
                                                v-if="
                                                    passwordForm.confirmarPassword &&
                                                    passwordForm.password !==
                                                        passwordForm.confirmarPassword
                                                "
                                            >
                                                <i class="fa fa-times-circle"></i> Las contraseñas
                                                no coinciden.
                                            </span>
                                            <span
                                                class="text-success small d-block mt-1"
                                                v-if="
                                                    passwordForm.confirmarPassword &&
                                                    passwordForm.password ===
                                                        passwordForm.confirmarPassword &&
                                                    reglasPassword.todas
                                                "
                                            >
                                                <i class="fa fa-check-circle"></i> Las contraseñas
                                                coinciden correctamente.
                                            </span>
                                        </div>
                                    </div>

                                    <div class="form-group m-t-20">
                                        <div class="col-sm-12">
                                            <button
                                                type="button"
                                                class="btn btn-info btn-action-clinical m-r-5"
                                                :disabled="loadingCambiarPassword"
                                                title="Guardar nueva contraseña (Ctrl+S)"
                                                @click="cambiarPasswordUsuario()"
                                            >
                                                <i
                                                    v-if="loadingCambiarPassword"
                                                    class="fa fa-spinner fa-spin mr-1"
                                                ></i>
                                                <i v-else class="fa fa-key mr-1"></i> Cambiar
                                                Contraseña
                                                <span class="badge badge-light text-dark ml-1"
                                                    >Ctrl+S</span
                                                >
                                            </button>
                                            <button
                                                type="button"
                                                class="btn btn-inverse btn-action-clinical"
                                                @click="limpiarPasswordForm()"
                                                :disabled="loadingCambiarPassword"
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
            </div>
            <!-- Column -->
        </div>
        <!-- ============================================================== -->
        <!-- End PAge Content -->
        <!-- ============================================================== -->
    </div>
</template>
<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import axios from "axios";
import { alertError, swalSuccess, toastMessage } from "@/helpers/alerts";
import { useKeyboardShortcuts } from "@/composables/useKeyboardShortcuts.js";

const props = defineProps({ usuarioactual: { type: Object, default: () => ({}) } });

const id = ref(props.usuarioactual.id);
const tabActiva = ref("settings");
const registro = ref({
    id: props.usuarioactual.id,
    num_docu: props.usuarioactual.num_docu ?? "",
    dependencia_id: props.usuarioactual.dependencia_id,
    name: props.usuarioactual.name ?? "",
    email: props.usuarioactual.email ?? "",
    estado: 0,
    imagen_perfil: null,
    firma: null,
});
const firmaPreview = ref("");
const firmaError = ref(false);
const avatarPreview = ref("");
const archivoAvatar = ref(null);
const archivoFirma = ref(null);
const loadingObtener = ref(false);
const loadingActualizar = ref(false);

const loadingCambiarPassword = ref(false);
const erroresPassword = ref({});
const mostrarPassword = ref({
    current: false,
    nueva: false,
    confirmar: false,
});
const passwordForm = ref({
    current_password: "",
    password: "",
    confirmarPassword: "",
});

const reglasPassword = computed(() => {
    const pwd = passwordForm.value.password || "";
    const longitud = pwd.length >= 8;
    const mayuscula = /[A-Z]/.test(pwd);
    const minuscula = /[a-z]/.test(pwd);
    const numero = /[0-9]/.test(pwd);
    const simbolo = /[^A-Za-z0-9]/.test(pwd);
    const todas = longitud && mayuscula && minuscula && numero && simbolo;
    return { longitud, mayuscula, minuscula, numero, simbolo, todas };
});

function limpiarPasswordForm() {
    passwordForm.value = {
        current_password: "",
        password: "",
        confirmarPassword: "",
    };
    erroresPassword.value = {};
    mostrarPassword.value = {
        current: false,
        nueva: false,
        confirmar: false,
    };
}

async function obtenerRegistro() {
    if (loadingObtener.value) return;
    loadingObtener.value = true;
    try {
        const res = await axios.get("/api/seguridad-usuarios/" + id.value);
        const u = res.data?.data?.usuario || res.data?.usuario || {};

        registro.value.num_docu = u.num_docu || "";
        registro.value.name = u.name || "";
        registro.value.email = u.email || "";
        registro.value.firma = u.firma || null;
        registro.value.imagen_perfil = u.imagen_perfil || null;
    } catch (_error) {
        toastMessage({
            heading: "Error",
            text: "No se pudieron cargar los datos del perfil.",
            icon: "error",
        });
    } finally {
        loadingObtener.value = false;
    }
}

async function actualizarPerfil() {
    if (loadingActualizar.value) return;
    loadingActualizar.value = true;
    try {
        const formData = new FormData();
        const r = registro.value;
        formData.append("name", r.name || "");
        formData.append("email", r.email || "");
        formData.append("num_docu", r.num_docu || "");
        if (archivoAvatar.value) formData.append("imagen_perfil", archivoAvatar.value);
        if (archivoFirma.value) formData.append("firma", archivoFirma.value);
        const res = await axios.post("/api/seguridad-actualizar-perfil/" + id.value, formData, {
            headers: { "Content-Type": "multipart/form-data" },
        });
        archivoAvatar.value = null;
        archivoFirma.value = null;
        avatarPreview.value = "";
        firmaPreview.value = "";
        await obtenerRegistro();

        toastMessage({
            heading: "Ok!!!",
            text:
                res.data?.message || res.data?.data?.message || "Perfil actualizado correctamente.",
            position: "top-right",
            loaderBg: "#ff6849",
            icon: "success",
            hideAfter: 3500,
            stack: 6,
        });
    } catch (_error) {
        const errores = _error?.response?.data?.errors;
        toastMessage({
            heading: "Error",
            text: errores
                ? Object.values(errores)[0][0]
                : _error?.response?.data?.message || "No se pudo actualizar el perfil.",
            icon: "error",
        });
    } finally {
        loadingActualizar.value = false;
    }
}

/**
 * Procesa el cambio de contraseña del usuario autenticado previa validación
 * de su contraseña actual y requisitos institucionales de seguridad.
 *
 * Flujo del proceso:
 * 1. Valida los campos y el cumplimiento de las 5 reglas de seguridad en cliente.
 * 2. Emite la solicitud POST a /api/seguridad-cambiar-password.
 * 3. En caso de éxito, limpia el formulario y notifica con SweetAlert modal interactivo.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-27
 */
async function cambiarPasswordUsuario() {
    if (loadingCambiarPassword.value) return;
    erroresPassword.value = {};

    if (!passwordForm.value.current_password) {
        erroresPassword.value = { current_password: ["La contraseña actual es obligatoria."] };
        return;
    }
    if (!passwordForm.value.password) {
        erroresPassword.value = { password: ["La nueva contraseña es obligatoria."] };
        return;
    }
    if (!reglasPassword.value.todas) {
        erroresPassword.value = {
            password: [
                "La nueva contraseña debe cumplir con todos los requisitos de seguridad institucionales.",
            ],
        };
        return;
    }
    if (passwordForm.value.password !== passwordForm.value.confirmarPassword) {
        erroresPassword.value = {
            confirmarPassword: ["La confirmación de la nueva contraseña no coincide."],
        };
        return;
    }

    loadingCambiarPassword.value = true;
    try {
        const payload = {
            current_password: passwordForm.value.current_password,
            password: passwordForm.value.password,
            confirmarPassword: passwordForm.value.confirmarPassword,
        };

        const res = await axios.post("/api/seguridad-cambiar-password", payload);

        limpiarPasswordForm();

        swalSuccess(
            "¡Contraseña Actualizada!",
            res.data?.message || "Su contraseña ha sido actualizada correctamente."
        );
    } catch (err) {
        erroresPassword.value = err?.response?.data?.errors || {};
        const msg =
            err?.response?.data?.message ||
            (erroresPassword.value && Object.values(erroresPassword.value)[0]?.[0]) ||
            "No fue posible cambiar la contraseña.";
        alertError("Error", msg, "error");
    } finally {
        loadingCambiarPassword.value = false;
    }
}

function obtenerArchivo(e) {
    const file = e.target.files[0];
    if (file) {
        if (avatarPreview.value) URL.revokeObjectURL(avatarPreview.value);
        archivoAvatar.value = file;
        avatarPreview.value = URL.createObjectURL(file);
    }
}

function obtenerFirma(e) {
    const file = e.target.files[0];
    if (file) {
        if (firmaPreview.value) URL.revokeObjectURL(firmaPreview.value);
        archivoFirma.value = file;
        firmaPreview.value = URL.createObjectURL(file);
    }
}

onMounted(() => {
    obtenerRegistro();
});

onBeforeUnmount(() => {
    if (avatarPreview.value) URL.revokeObjectURL(avatarPreview.value);
    if (firmaPreview.value) URL.revokeObjectURL(firmaPreview.value);
});

useKeyboardShortcuts({
    onSearch: () => {
        if (!loadingObtener.value) {
            obtenerRegistro();
        }
    },
    onSave: () => {
        if (tabActiva.value === "password") {
            if (!loadingCambiarPassword.value) {
                cambiarPasswordUsuario();
            }
        } else {
            if (!loadingActualizar.value) {
                actualizarPerfil();
            }
        }
    },
});
</script>
<style scoped>
.btn-action-clinical {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}
.btn-action-clinical:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow:
        0 4px 6px -1px rgba(0, 0, 0, 0.1),
        0 2px 4px -1px rgba(0, 0, 0, 0.06);
}
</style>

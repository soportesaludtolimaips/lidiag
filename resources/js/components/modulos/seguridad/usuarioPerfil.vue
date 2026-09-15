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
                                v-if="!registro.imagen_perfil"
                                src="@/admin-wrap/assets/images/users/avatar_default.png"
                                :alt="registro.name"
                                class="img-circle"
                                width="150"
                            />
                            <img
                                v-else
                                :src="registro.imagen_perfil"
                                :alt="registro.name"
                                class="img-circle"
                                width="150"
                            />
                            <h4 class="card-title m-t-10">{{ registro.name }}</h4>
                            <h6 class="card-subtitle">{{ registro.tipo_user }}</h6>

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
                            <a class="nav-link active" data-toggle="tab" href="#settings" role="tab"
                                >Ajustes</a
                            >
                        </li>
                    </ul>
                    <!-- Tab panes -->
                    <div class="tab-content">
                        <!--second tab -->
                        <div class="tab-pane active" id="settings" role="tabpanel">
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
                                        <label class="col-md-12">Password</label>
                                        <div class="col-md-12">
                                            <input
                                                type="password"
                                                id="password"
                                                name="password"
                                                v-model="registro.password"
                                                class="form-control form-control-line"
                                            />
                                            <small class="text-muted"
                                                >Dejar en blanco si no desea cambiar la
                                                contraseña</small
                                            >
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-sm-12">
                                            <button
                                                type="button"
                                                class="btn btn-success m-r-5"
                                                :disabled="loadingActualizar"
                                                @click="actualizarPerfil()"
                                            >
                                                <i
                                                    v-if="loadingActualizar"
                                                    class="fa fa-spinner fa-spin"
                                                ></i>
                                                <i v-else class="fa fa-pencil"></i> Actualizar
                                                Perfil
                                            </button>
                                            <!-- <button type="button" class="btn btn-inverse">
                                                <i class="fa fa-reply"></i> Cancel
                                            </button> -->
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
import { onBeforeUnmount, onMounted, ref } from "vue";

import axios from "axios";
import { toastMessage, alertError } from "@/helpers/alerts";

const props = defineProps({ usuarioactual: Object });

const id = ref(props.usuarioactual.id);
const registro = ref({
    id: props.usuarioactual.id,
    num_docu: "",
    dependencia_id: props.usuarioactual.dependencia_id,
    name: "",
    email: "",
    password: "",
    estado: 0,
    imagen_perfil: null,
    firma: null,
});
const firmaPreview = ref("");
const firmaError = ref(false);
const errores = ref({});
const loadingObtener = ref(false);
const loadingActualizar = ref(false);

/**
 * Solicita al backend los datos del perfil del usuario actual y los asigna al formulario.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
async function obtenerRegistro() {
    if (loadingObtener.value) return;
    loadingObtener.value = true;
    try {
        const res = await axios.get("/api/seguridad-usuarios/" + id.value);

        if (res.status == 200) {
            registro.value.num_docu = res.data.data.usuario.num_docu;
            registro.value.name = res.data.data.usuario.name;
            registro.value.email = res.data.data.usuario.email;
            registro.value.firma = res.data.data.usuario.firma;
            registro.value.imagen_perfil = res.data.data.usuario.imagen_perfil || null;
        }
    } catch (error) {
        errores.value = error?.response?.data?.errors || {};
    } finally {
        loadingObtener.value = false;
    }
}
/**
 * Envía los datos del perfil (incluida firma y avatar) para actualizarlos en el backend.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
async function actualizarPerfil() {
    if (loadingActualizar.value) return;
    loadingActualizar.value = true;
    try {
        const formData = new FormData();
        Object.keys(registro.value).forEach((key) => {
            if (registro.value[key] !== undefined && registro.value[key] !== null) {
                if (key === "password" && !registro.value.password) return;
                formData.append(key, registro.value[key]);
            }
        });
        const res = await axios.post("/api/seguridad-actualizar-perfil/" + id.value, formData, {
            headers: { "Content-Type": "multipart/form-data" },
        });
        if (res.status == 200) {
            errores.value = {};
            firmaPreview.value = "";
            await obtenerRegistro();

            toastMessage({
                heading: "Ok!!!",
                text: res.data.data.message,
                position: "top-right",
                loaderBg: "#ff6849",
                icon: "success",
                hideAfter: 3500,
                stack: 6,
            });
        }
    } catch (error) {
        errores.value = error?.response?.data?.errors || {};
    } finally {
        loadingActualizar.value = false;
    }
}
/**
 * Captura el archivo de imagen de perfil seleccionado por el usuario.
 *
 * @param Event $e Evento change del input de tipo file.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
function obtenerArchivo(e) {
    registro.value.imagen_perfil = e.target.files[0];
}
/**
 * Captura el archivo de firma seleccionado y genera una vista previa temporal en memoria.
 *
 * @param Event $e Evento change del input de tipo file.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
function obtenerFirma(e) {
    const file = e.target.files[0];
    if (file) {
        if (firmaPreview.value) URL.revokeObjectURL(firmaPreview.value);
        firmaPreview.value = URL.createObjectURL(file);
    }
    registro.value.firma = file;
}

/**
 * Hook de ciclo de vida que se ejecuta al montar el componente; carga el registro del perfil.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
onMounted(() => {
    obtenerRegistro();
});

/**
 * Hook de ciclo de vida que libera la URL de objeto de la vista previa de firma al desmontar.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
onBeforeUnmount(() => {
    if (firmaPreview.value) URL.revokeObjectURL(firmaPreview.value);
});
</script>
<style scoped>
.right-sidebar {
    position: fixed;
    right: -25%;
    width: 25%;
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
    width: 25%;
    display: block;
}
</style>

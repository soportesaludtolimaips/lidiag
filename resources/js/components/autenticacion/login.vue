<template>
    <div>
        <div class="login-box card">
            <div class="card-body">
                <form class="form-horizontal form-material" id="loginform" action="index.html">
                    <a href="javascript:void(0)" class="text-center db">
                        <img src="admin-wrap-login/assets/images/logo-icon.png" alt="Home" /><br />
                        <img src="admin-wrap-login/assets/images/logo-text.png" alt="Home" />
                    </a>

                    <div class="alert alert-warning" v-if="erroreLogin">
                        <i class="fa fa-ban"></i>
                        {{ erroreLogin }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="form-group m-t-40">
                        <div class="col-xs-12">
                            <input
                                id="email"
                                type="text"
                                v-model="datosLogin.email"
                                class="form-control"
                                name="email"
                                autocomplete="email"
                                autofocus
                                placeholder="E - Mail"
                            />
                            <span class="text-danger" v-if="errores.email">
                                {{ errores.email[0] }}
                            </span>
                        </div>
                    </div>
                    <div class="form-group m-t-40">
                        <div class="col-xs-12">
                            <input
                                id="password"
                                type="password"
                                v-model="datosLogin.password"
                                class="form-control"
                                name="password"
                                required
                                autocomplete="current-password"
                                placeholder="Contraseña"
                            />
                            <span class="text-danger" v-if="errores.password">
                                {{ errores.password[0] }}
                            </span>
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-md-12">
                            <div class="checkbox checkbox-primary pull-left p-t-0">
                                <input
                                    id="checkbox-signup"
                                    type="checkbox"
                                    class="filled-in chk-col-light-blue"
                                />
                                <label for="checkbox-signup"> Recuerdame </label>
                            </div>
                            <a
                                href="javascript:void(0)"
                                id="to-recover"
                                class="text-dark pull-right"
                                ><i class="fa fa-lock m-r-5"></i> Olvidé mi contraseña?</a
                            >
                        </div>
                    </div>
                    <div class="form-group text-center m-t-20">
                        <div class="col-xs-12">
                            <button
                                type="button"
                                class="btn btn-info btn-lg btn-block text-uppercase btn-rounded"
                                @click="iniciarSesion()"
                            >
                                Iniciar sesión
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
<script setup>
import { ref } from "vue";
import axios from "axios";

const datosLogin = ref({ email: "", password: "" });
const errores = ref({});
const erroreLogin = ref("");

async function iniciarSesion() {
    try {
        const res = await axios.post("/login", {
            email: datosLogin.value.email,
            password: datosLogin.value.password,
        });

        // Si el backend responde JSON, usamos el redirect sugerido.
        const redirect = res?.data?.redirect || "/dashboard";
        window.location.href = redirect;
    } catch (error) {
        const status = error?.response?.status;
        if (status === 401) {
            erroreLogin.value = "Estas credenciales no coinciden con nuestros registros";
            return;
        }

        errores.value = error?.response?.data?.errors || {};
    }
}
</script>
<style></style>

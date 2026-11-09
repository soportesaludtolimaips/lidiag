<template>
    <div class="row justify-content-center py-5">
        <div class="col-md-8 col-lg-6 text-center">
            <div class="card shadow-sm p-4">
                <div class="card-body">
                    <h1 class="display-1 font-weight-bold text-warning mb-0">404</h1>
                    <h3 class="font-weight-bold text-dark mt-2 mb-3">Página No Encontrada</h3>
                    <p class="text-muted mb-4">
                        La ruta que intenta acceder no existe o fue reubicada en la plataforma
                        LIDiag.
                    </p>
                    <router-link
                        to="/dashboard"
                        class="btn btn-primary btn-rounded waves-effect waves-light px-4"
                    >
                        <i class="fa fa-home mr-1"></i> Ir al Dashboard
                    </router-link>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
/**
 * Componente 404 Not Found para rutas desconocidas en Vue Router.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-11-09
 */
</script>

<template>
    <div class="row justify-content-center py-5">
        <div class="col-md-8 col-lg-6 text-center">
            <div class="card shadow-sm p-4 border-danger">
                <div class="card-body">
                    <h1 class="display-1 font-weight-bold text-danger mb-0">403</h1>
                    <h3 class="font-weight-bold text-dark mt-2 mb-3">Acceso Restringido</h3>
                    <p class="text-muted mb-4">
                        Su usuario actual no posee los permisos clínicos o administrativos
                        requeridos por Spatie para acceder a este módulo.
                    </p>
                    <router-link
                        to="/dashboard"
                        class="btn btn-outline-danger btn-rounded waves-effect waves-light px-4"
                    >
                        <i class="fa fa-arrow-left mr-1"></i> Regresar al Dashboard
                    </router-link>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
/**
 * Componente 403 Forbidden para accesos no autorizados en Vue Router.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-11-09
 */
</script>

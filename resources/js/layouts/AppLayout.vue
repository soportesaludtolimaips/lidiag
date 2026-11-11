<template>
    <div id="main-wrapper">
        <!-- Topbar Header -->
        <AppHeader />

        <!-- Left Sidebar Navigation -->
        <AppSidebar />

        <!-- Page Content Wrapper -->
        <div class="page-wrapper">
            <div class="container-fluid">
                <router-view v-slot="{ Component }">
                    <transition name="fade-page" mode="out-in">
                        <component
                            :is="Component"
                            :usuarioactual="user"
                            :permisos="permissions"
                            :rolesusuario="roles[0] || ''"
                        />
                    </transition>
                </router-view>
            </div>

            <!-- Footer -->
            <footer-component />
        </div>
    </div>
</template>

<script setup>
import AppHeader from "./AppHeader.vue";
import AppSidebar from "./AppSidebar.vue";
import { useAuth } from "../composables/useAuth";

const { user, permissions, roles } = useAuth();

/**
 * Shell Principal de la SPA LIDiag (AppLayout).
 * Aloja el Header, Sidebar interactivo, vista activa con RouterView
 * y Footer de la plataforma sin recargas de página.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-11-09
 */
</script>

<style scoped>
.fade-page-enter-active,
.fade-page-leave-active {
    transition: opacity 0.15s ease;
}

.fade-page-enter-from,
.fade-page-leave-to {
    opacity: 0;
}
</style>

<template>
    <header class="topbar">
        <nav class="navbar top-navbar navbar-expand-md navbar-light">
            <!-- Logo -->
            <div class="navbar-header">
                <router-link class="navbar-brand" to="/dashboard">
                    <!-- Logo icon -->
                    <b>
                        <img
                            :src="'/admin-wrap/assets/images/logo-icon.png'"
                            alt="homepage"
                            class="dark-logo"
                        />
                        <img
                            :src="'/admin-wrap/assets/images/logo-light-icon.png'"
                            alt="homepage"
                            class="light-logo"
                        />
                    </b>
                    <!-- Logo text -->
                    <span>
                        <img
                            :src="'/admin-wrap/assets/images/logo-text.png'"
                            alt="homepage"
                            class="dark-logo"
                        />
                        <img
                            :src="'/admin-wrap/assets/images/logo-light-text.png'"
                            class="light-logo"
                            alt="homepage"
                        />
                    </span>
                </router-link>
            </div>

            <!-- Navbar collapse -->
            <div class="navbar-collapse">
                <ul class="navbar-nav mr-auto">
                    <!-- Toggle para móvil -->
                    <li class="nav-item">
                        <a
                            class="nav-link nav-toggler hidden-md-up waves-effect waves-dark"
                            href="javascript:void(0)"
                            @click.prevent="toggleMobileNav"
                        >
                            <i class="sl-icon-menu"></i>
                        </a>
                    </li>
                    <!-- Toggle mini-sidebar para escritorio -->
                    <li class="nav-item">
                        <a
                            class="nav-link sidebartoggler hidden-sm-down waves-effect waves-dark"
                            href="javascript:void(0)"
                            @click.prevent="toggleMiniSidebar"
                        >
                            <i class="sl-icon-menu"></i>
                        </a>
                    </li>
                    <!-- Quick Search / Command Palette (Ctrl+K) -->
                    <li class="nav-item d-flex align-items-center ml-2">
                        <command-palette-component />
                    </li>
                </ul>

                <!-- User profile, sedes, médicos, notificaciones y modo oscuro -->
                <user-profile-and-search-component
                    :usuarioactual="user"
                    perfil-url="/seguridad.perfil"
                />
            </div>
        </nav>
    </header>
</template>

<script setup>
import { useAuth } from "../composables/useAuth";

const { user } = useAuth();

function toggleMiniSidebar() {
    if (typeof document === "undefined") return;
    const body = document.body;
    const isMini = body.classList.contains("mini-sidebar");

    if (isMini) {
        body.classList.remove("mini-sidebar");
        const span = document.querySelector(".navbar-brand span");
        if (span) span.style.display = "";
    } else {
        body.classList.add("mini-sidebar");
        const span = document.querySelector(".navbar-brand span");
        if (span) span.style.display = "none";
    }

    if (typeof window !== "undefined") {
        window.dispatchEvent(new Event("resize"));
    }
}

function toggleMobileNav() {
    if (typeof document === "undefined") return;
    document.body.classList.toggle("show-sidebar");
}
</script>

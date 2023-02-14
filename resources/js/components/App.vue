<template>
    <div>
        <!-- ============================================================== -->
        <!-- Topbar header - style you can find in pages.scss -->
        <!-- ============================================================== -->
        <header class="topbar">
            <nav class="navbar top-navbar navbar-expand-md navbar-light">
                <!-- ============================================================== -->
                <!-- Logo -->
                <!-- ============================================================== -->
                <logoComponent :ruta="ruta" />
                <!-- ============================================================== -->
                <!-- End Logo -->
                <!-- ============================================================== -->
                <div class="navbar-collapse">
                    <!-- ============================================================== -->
                    <!-- toggle and nav items -->
                    <!-- ============================================================== -->
                    <toggleAndNavItemsComponent />
                    <!-- ============================================================== -->
                    <!-- User profile and search -->
                    <!-- ============================================================== -->
                    <userProfileAndSearchComponent :ruta="ruta" />
                </div>
            </nav>
        </header>
        <!-- ============================================================== -->
        <!-- End Topbar header -->
        <!-- ============================================================== -->
        <!-- ============================================================== -->
        <!-- Left Sidebar - style you can find in sidebar.scss  -->
        <!-- ============================================================== -->
        <sidebarComponent />
        <!-- ============================================================== -->
        <!-- End Left Sidebar - style you can find in sidebar.scss  -->
        <!-- ============================================================== -->
        <!-- ============================================================== -->
        <!-- Page wrapper  -->
        <!-- ============================================================== -->
        <div class="page-wrapper">

            <!-- ============================================================== -->
            <!-- Container fluid  -->
            <!-- ============================================================== -->
            <div class="container-fluid">
                <router-view></router-view>
            </div>
            <!-- ============================================================== -->
            <!-- End Container fluid  -->
            <!-- ============================================================== -->

            <!-- ============================================================== -->
            <!-- footer -->
            <!-- ============================================================== -->
            <footerComponent />
            <!-- ============================================================== -->
            <!-- End footer -->
            <!-- ============================================================== -->
        </div>
        <!-- ============================================================== -->
        <!-- End Page wrapper  -->
        <!-- ============================================================== -->
    </div>
</template>
<script>

import logoComponent from './comunes/logoComponent.vue';
import toggleAndNavItemsComponent from './comunes/toggleAndNavItemsComponent.vue'
import userProfileAndSearchComponent from './comunes/userProfileAndSearchComponent.vue'
import sidebarComponent from './comunes/sidebarComponent.vue'
import footerComponent from './comunes/footerComponent.vue'

export default {
    props: ['ruta'],
    components: { logoComponent, toggleAndNavItemsComponent, userProfileAndSearchComponent, sidebarComponent, footerComponent }
}
</script>

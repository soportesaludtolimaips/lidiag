import { createWebHistory, createRouter } from 'vue-router'

const routes = [
    { path: '/login', name: 'login', component: () => import('./components/autenticacion/login.vue') },
    { path: '/', name: 'dashboard', component: () => import('./components/dashboard/index.vue') },

    /**
     * Configuración
     */
    { path: '/configAdminSalud', name: 'configAdminSalud', component: () => import('./components/modulos/configuracion/configAdminSaludComponent.vue') },
    { path: '/configDiagnosticos', name: 'configDiagnosticos', component: () => import('./components/modulos/configuracion/configDiagnosticoComponent.vue') },
    { path: '/configPrioridades', name: 'configPrioridades', component: () => import('./components/modulos/configuracion/configPrioridadComponent.vue') },
    { path: '/configProductos', name: 'configProductos', component: () => import('./components/modulos/configuracion/configProductoComponent.vue') },
    { path: '/configSedes', name: 'configSedes', component: () => import('./components/modulos/configuracion/configSedeComponent.vue') },

    /**
     * Estudios
     */
    { path: '/AsignarEstudios', name: 'AsignarEstudios', component: () => import('./components/modulos/estudios/AsignarEstudioComponent.vue') },
    { path: '/leerEstudios', name: 'leerEstudios', component: () => import('./components/modulos/estudios/leerEstudioComponent.vue') },
    { path: '/transcribirEstudios', name: 'transcribirEstudios', component: () => import('./components/modulos/estudios/transcribirEstudioComponent.vue') },

    /**
     * Seguridad
     */
    { path: '/seguridad-usuarios', name: 'seguridadUsuarios', component: () => import('./components/modulos/seguridad/usuarioComponent.vue') },
    {
        path: '/seguridad/usuario/perfil/:id', name: 'seguridad.usuario.perfil', component: () => import('./components/modulos/seguridad/usuarioPerfil.vue'),
        props: true
    },
    { path: '/seguridad-roles', name: 'seguridad.roles', component: () => import('./components/modulos/seguridad/roleComponente.vue') },
]

const router = createRouter({
    history: createWebHistory(),
    routes, // short for `routes: routes`
})

export default router
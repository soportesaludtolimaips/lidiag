
import { createRouter, createWebHistory } from 'vue-router'

/* 
export default new Route({
    routes: [
        { path: '/', component: require('./components/plantilla/Content') },
    ]
})
 */

const ContentComponent = () => import('./components/plantilla/ContentComponent.vue');

/**
 * Configuracion
 */
const configAdminSaludComponent = () => import('./components/modulos/configuracion/configAdminSaludComponent.vue');
const configDiagnosticoComponent = () => import('./components/modulos/configuracion/configDiagnosticoComponent.vue');
const configPrioridadComponent = () => import('./components/modulos/configuracion/configPrioridadComponent.vue');
const configProductoComponent = () => import('./components/modulos/configuracion/configProductoComponent.vue');
const configSucursalComponent = () => import('./components/modulos/configuracion/configSucursalComponent.vue');

/**
 * Estudios
 */

const AsignarEstudioComponent = () => import('./components/modulos/estudios/AsignarEstudioComponent.vue');
const leerEstudioComponent = () => import('./components/modulos/estudios/leerEstudioComponent.vue');
const transcribirEstudioComponent = () => import('./components/modulos/estudios/transcribirEstudioComponent.vue');

const routes = [
    { path: '/', name: 'plantilla', component: ContentComponent },

    /**
     * Configuracion
     */

    { path: '/configAdminSalud', name: 'configAdminSalud', component: configAdminSaludComponent },
    { path: '/configDiagnosticos', name: 'configDiagnosticos', component: configDiagnosticoComponent },
    { path: '/configPrioridades', name: 'configPrioridades', component: configPrioridadComponent },
    { path: '/configProductos', name: 'configProductos', component: configProductoComponent },
    { path: '/configSucursales', name: 'configSucursales', component: configSucursalComponent },

    /**
     * Estudios
     */
    { path: '/AsignarEstudios', name: 'AsignarEstudios', component: AsignarEstudioComponent },
    { path: '/leerEstudios', name: 'leerEstudios', component: leerEstudioComponent },
    { path: '/transcribirEstudios', name: 'transcribirEstudios', component: transcribirEstudioComponent },

]

const router = createRouter({
    history: createWebHistory(),
    routes
});

export default router;
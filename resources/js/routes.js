
import { createRouter, createWebHistory } from 'vue-router'

/**
 *
  * Inicio de la importacion de los complementos para agregarlos a las rutas
  *
*/

const ContentComponent = () => import('./components/plantilla/ContentComponent.vue');

/**
 * Configuracion
 */
const configAdminSaludComponent = () => import('./components/modulos/configuracion/configAdminSaludComponent.vue');
const configDiagnosticoComponent = () => import('./components/modulos/configuracion/configDiagnosticoComponent.vue');
const configPrioridadComponent = () => import('./components/modulos/configuracion/configPrioridadComponent.vue');
const configProductoComponent = () => import('./components/modulos/configuracion/configProductoComponent.vue');
const configSedeComponent = () => import('./components/modulos/configuracion/configSedeComponent.vue');

/**
 * Estudios
 */

const AsignarEstudioComponent = () => import('./components/modulos/estudios/AsignarEstudioComponent.vue');
const leerEstudioComponent = () => import('./components/modulos/estudios/leerEstudioComponent.vue');
const transcribirEstudioComponent = () => import('./components/modulos/estudios/transcribirEstudioComponent.vue');

/**
 * Seguridad
 */

const usuarioComponent = () => import('./components/modulos/seguridad/usuarioComponent.vue');
const usuarioPerfil = () => import('./components/modulos/seguridad/usuarioPerfil.vue');

const routes = [
    { path: '/', name: 'plantilla', component: ContentComponent },

    /**
     * Configuracion
     */

    { path: '/configAdminSalud', name: 'configAdminSalud', component: configAdminSaludComponent },
    { path: '/configDiagnosticos', name: 'configDiagnosticos', component: configDiagnosticoComponent },
    { path: '/configPrioridades', name: 'configPrioridades', component: configPrioridadComponent },
    { path: '/configProductos', name: 'configProductos', component: configProductoComponent },
    { path: '/configSedes', name: 'configSedes', component: configSedeComponent },

    /**
     * Estudios
     */
    { path: '/AsignarEstudios', name: 'AsignarEstudios', component: AsignarEstudioComponent },
    { path: '/leerEstudios', name: 'leerEstudios', component: leerEstudioComponent },
    { path: '/transcribirEstudios', name: 'transcribirEstudios', component: transcribirEstudioComponent },

    /**
     * Seguridad
     */

    { path: '/seguridadUsaurios', name: 'seguridadUsaurios', component: usuarioComponent },
    {
        path: '/seguridad/usaurio/perfil/:id',
        name: 'seguridad.usaurio.verperfil',
        component: usuarioPerfil,
        props: true
    },

]

const router = createRouter({
    history: createWebHistory(),
    routes
});

export default router;
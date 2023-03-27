
import { createRouter, createWebHistory } from 'vue-router'

/**
 *
  * Inicio la importación de los componentes para agregarlos a las rutas
  *
*/

//const LoginComponent = () => import('./components/Auth.vue');
//const ContentComponent = () => import('./components/plantilla/ContentComponent.vue');

/**
 * Configuracion
 */
/* const configAdminSaludComponent = () => import('./components/modulos/configuracion/configAdminSaludComponent.vue');
const configDiagnosticoComponent = () => import('./components/modulos/configuracion/configDiagnosticoComponent.vue');
const configPrioridadComponent = () => import('./components/modulos/configuracion/configPrioridadComponent.vue');
const configProductoComponent = () => import('./components/modulos/configuracion/configProductoComponent.vue');
const configSedeComponent = () => import('./components/modulos/configuracion/configSedeComponent.vue');
 */

/**
 * Estudios
 */
/* const AsignarEstudioComponent = () => import('./components/modulos/estudios/AsignarEstudioComponent.vue');
const leerEstudioComponent = () => import('./components/modulos/estudios/leerEstudioComponent.vue');
const transcribirEstudioComponent = () => import('./components/modulos/estudios/transcribirEstudioComponent.vue'); */

/**
 * Seguridad
 */


/* const usuarioComponent = () => import('./components/modulos/seguridad/usuarioComponent.vue');
const usuarioPerfil = () => import('./components/modulos/seguridad/usuarioPerfil.vue');
const usuarioEditarComponent = () => import('./components/modulos/seguridad/usuarioEditarComponent.vue'); */

const routes = [
    { path: '/', name: 'plantilla', component: () => import('./components/plantilla/ContentComponent.vue') },
    { path: '/login', name: 'login', component: () => import('./components/Auth.vue') },

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
    routes
});

export default router;
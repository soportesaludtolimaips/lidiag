/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

import './bootstrap';
import { createApp } from 'vue';

import mitt from 'mitt'


const emitter = mitt();

/**
 * Next, we will create a fresh Vue application instance. You may then begin
 * registering components with the application instance so they are ready
 * to use in your application's views. An example is included for you.
 */

const app = createApp({});
app.config.globalProperties.emitter = emitter;
/**
 * Principales
 */

import dashboardComponent from './components/dashboard/dashboardComponent.vue';
app.component('dashboard-component', dashboardComponent);


/**
 * Cofiguración
 */
import configAdminSaludComponent from './components/modulos/configuracion/configAdminSaludComponent.vue';
app.component('config-admin-salud-component', configAdminSaludComponent);
import configDiagnosticoComponent from './components/modulos/configuracion/configDiagnosticoComponent.vue';
app.component('config-diagnostico-component', configDiagnosticoComponent);
import configPrioridadComponent from './components/modulos/configuracion/configPrioridadComponent.vue';
app.component('config-prioridad-component', configPrioridadComponent);
import configProductoComponent from './components/modulos/configuracion/configProductoComponent.vue';
app.component('config-producto-component', configProductoComponent);
import configSedeComponent from './components/modulos/configuracion/configSedeComponent.vue';
app.component('config-sede-component', configSedeComponent);


/**
 * Estudios
 */
import AsignarEstudioComponent from './components/modulos/estudios/AsignarEstudioComponent.vue';
app.component('asignar-studio-component', AsignarEstudioComponent);
import leerEstudioComponent from './components/modulos/estudios/leerEstudioComponent.vue';
app.component('leer-estudio-component', leerEstudioComponent);
import leerEstudioAdministrativoComponent from './components/modulos/estudios/leerEstudioAdministrativoComponent.vue';
app.component('leer-y-transcribir-administrativo-component', leerEstudioAdministrativoComponent);
import transcribirEstudioComponent from './components/modulos/estudios/transcribirEstudioComponent.vue';
app.component('transcribir-estudio-component', transcribirEstudioComponent);
import notificarEstudioComponent from './components/modulos/estudios/notificarEstudioComponent.vue';
app.component('notificar-estudio-component', notificarEstudioComponent);



/**
 * Seguridad
*/
import usuarioComponent from './components/modulos/seguridad/usuarioComponent.vue';
app.component('usuario-component', usuarioComponent);
import usuarioPerfil from './components/modulos/seguridad/usuarioPerfil.vue';
app.component('usuario-perfil-component', usuarioPerfil);
import roleComponente from './components/modulos/seguridad/roleComponente.vue';
app.component('role-componente', roleComponente);

/**
 * Comunes
 */
import userProfileAndSearchComponent from './components/comunes/userProfileAndSearchComponent.vue';
app.component('user-profile-and-search-component', userProfileAndSearchComponent);

import footerComponent from './components/comunes/footerComponent.vue';
app.component('footer-component', footerComponent);

/**
 * The following block of code may be used to automatically register your
 * Vue components. It will recursively scan this directory for the Vue
 * components and automatically register them with their "basename".
 *
 * Eg. ./components/ExampleComponent.vue -> <example-component></example-component>
 */

// Object.entries(import.meta.glob('./**/*.vue', { eager: true })).forEach(([path, definition]) => {
//     app.component(path.split('/').pop().replace(/\.\w+$/, ''), definition.default);
// });

/**
 * Finally, we will attach the application instance to a HTML element with
 * an "id" attribute of "app". This element is included with the "auth"
 * scaffolding. Otherwise, you will need to add an element yourself.
 */


/* import route from './routes'

app.use(route); */



app.mount('#app');
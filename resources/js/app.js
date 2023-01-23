/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

import './bootstrap';
import { createApp, VueElement } from 'vue';

/**
 * Next, we will create a fresh Vue application instance. You may then begin
 * registering components with the application instance so they are ready
 * to use in your application's views. An example is included for you.
 */

const app = createApp({});

import configAdminSaludComponent from './components/configuracion/configAdminSaludComponent.vue';
app.component('config-admin-salud-component', configAdminSaludComponent);

import configDiagnosticoComponent from './components/configuracion/configDiagnosticoComponent.vue';
app.component('config-diagnostico-component', configDiagnosticoComponent);

import configPrioridadComponent from './components/configuracion/configPrioridadComponent.vue';
app.component('config-prioridad-component', configPrioridadComponent);

import configProductoComponent from './components/configuracion/configProductoComponent.vue';
app.component('config-producto-component', configProductoComponent);

import configSucursalComponent from './components/configuracion/configSucursalComponent.vue';
app.component('config-sucursal-component', configSucursalComponent);



import AsignarEstudioComponent from './components/estudios/AsignarEstudioComponent.vue';
app.component('asignar-estudio-component', AsignarEstudioComponent);

import leerEstudioComponent from './components/estudios/leerEstudioComponent.vue';
app.component('leer-estudio-component', leerEstudioComponent);



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

import auth from './mixins/auth';
app.mixin(auth);

app.mount('#app');

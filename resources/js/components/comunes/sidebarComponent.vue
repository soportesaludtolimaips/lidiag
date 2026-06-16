<template>
    <aside class="left-sidebar">
        <!-- Sidebar scroll-->
        <div class="scroll-sidebar">
            <!-- Sidebar navigation-->
            <nav class="sidebar-nav">
                <ul id="sidebarnav">
                    <li class="nav-small-cap">--- PERSONAL</li>
                    <li>
                        <a class="has-arrow waves-effect waves-dark" href="#" aria-expanded="false">
                            <i class="icon-Car-Wheel"></i>
                            <span class="hide-menu">Dashboard
                                <span class="label label-rounded label-danger">4</span>
                            </span>
                        </a>
                        <ul aria-expanded="false" class="collapse">
                            <li><a href="index.html">Minimal </a></li>
                            <li><a href="index2.html">Analytical</a></li>
                            <li><a href="index3.html">Demographical</a></li>
                            <li><a href="index4.html">Modern</a></li>
                        </ul>
                    </li>

                    <li class="nav-small-cap">--- ESTUDIOS & LECTURAS</li>
                    <li>
                        <a class="has-arrow waves-effect waves-dark" href="#" aria-expanded="false">
                            <i class="fa fa-edit"></i>
                            <span class="hide-menu">Estudios</span>
                        </a>
                        <ul aria-expanded="false" class="collapse">
                            <li><router-link to="/AsignarEstudios">Asignar estudio</router-link></li>
                            <li><router-link to="/transcribirEstudios">Transcribir</router-link></li>
                            <li><router-link to="/leerEstudios">Mis estudios pendientes</router-link></li>
                            <li><router-link to="/notificarEstudios">Notificar estudio</router-link></li>
                        </ul>
                    </li>
                    <li class="nav-small-cap">--- PARAMETRIZACIÓN</li>
                    <li>
                        <a class="has-arrow waves-effect waves-dark" href="#" aria-expanded="false">
                            <i class="icon-Gears"></i>
                            <span class="hide-menu">Configuraciones</span>
                        </a>
                        <ul aria-expanded="false" class="collapse">
                            <li><router-link to="/configAdminSalud">Admin. De Salud</router-link></li>
                            <li><router-link to="/configDiagnosticos">Diagnosticos</router-link></li>
                            <li><router-link to="/configPrioridades">Prioridades</router-link></li>
                            <li><router-link to="/configProductos">Productos</router-link></li>
                            <li><router-link to="/configSedes">Sedes</router-link></li>
                            <li><router-link to="/configServicioCitas">Servicios de Citas</router-link></li>
                            <li><router-link to="/configServicioProducto">Productos por Servicio</router-link></li>
                            <li><router-link to="/config.tipos.plantillas.listar">Tipos de Plantillas</router-link></li>
                            <li><router-link to="/configPlantillas">Plantillas</router-link></li>
                            <li><router-link to="/configTiposDocumentos">Tipos de Documentos</router-link></li>
                            <li><router-link to="/configConsentimientoInformado">Consentimiento Informado</router-link></li>
                            <li><router-link to="/configWhatsapp">WhatsApp Bot</router-link></li>
                            <li><router-link to="/configCitaRechazoMotivos">Motivos de Rechazo</router-link></li>
                        </ul>
                    </li>
                    <li>
                        <a class="has-arrow waves-effect waves-dark" href="#" aria-expanded="false">
                            <i class="icon-Key-Lock"></i>
                            <span class="hide-menu">Seguridad</span>
                        </a>
                        <ul aria-expanded="false" class="collapse">
                            <li><router-link to="/seguridad-usuarios">Usuarios</router-link></li>
                            <li><router-link to="/seguridad-roles">Roles</router-link></li>
                        </ul>
                    </li>
                </ul>
            </nav>
            <!-- End Sidebar navigation -->
        </div>
        <!-- End Sidebar scroll-->
    </aside>
</template>
<script>
export default {

};
</script>
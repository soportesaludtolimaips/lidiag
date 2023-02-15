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
                        </ul>
                    </li>

                    <li class="nav-small-cap">--- PARAMETRIZACIÓN</li>
                    <li>
                        <a class="has-arrow waves-effect waves-dark" href="#" aria-expanded="false">
                            <i class="icon-Double-Circle"></i>
                            <span class="hide-menu">Apps</span>
                        </a>
                        <ul aria-expanded="false" class="collapse">
                            <li><a href="app-calendar.html">Calendar</a></li>
                            <li><a href="app-chat.html">Chat app</a></li>
                            <li><a href="app-ticket.html">Support Ticket</a></li>
                            <li><a href="app-contact.html">Contact / Employee</a></li>
                            <li><a href="app-contact2.html">Contact Grid</a></li>
                            <li><a href="app-contact-detail.html">Contact Detail</a></li>
                        </ul>
                    </li>
                    <li> <a class="has-arrow waves-effect waves-dark" href="#" aria-expanded="false"><i
                                class="icon-Box-Full"></i><span class="hide-menu">Inbox</span></a>
                        <ul aria-expanded="false" class="collapse">
                            <li><a href="app-email.html">Mailbox</a></li>
                            <li><a href="app-email-detail.html">Mailbox Detail</a></li>
                            <li><a href="app-compose.html">Compose Mail</a></li>
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
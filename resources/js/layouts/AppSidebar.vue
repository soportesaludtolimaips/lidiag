<template>
    <aside class="left-sidebar">
        <!-- Sidebar scroll-->
        <div class="scroll-sidebar">
            <!-- Sidebar navigation-->
            <nav class="sidebar-nav">
                <ul id="sidebarnav">
                    <!-- ============================================================== -->
                    <!-- PRINCIPAL -->
                    <!-- ============================================================== -->
                    <li class="nav-small-cap"><span>Principal</span></li>
                    <li :class="{ active: isRouteActive('/dashboard') }">
                        <router-link
                            to="/dashboard"
                            class="waves-effect waves-dark"
                            active-class="active"
                        >
                            <i class="icon-Car-Wheel"></i>
                            <span class="hide-menu">Dashboard</span>
                        </router-link>
                    </li>

                    <!-- ============================================================== -->
                    <!-- GESTIÓN CLÍNICA -->
                    <!-- ============================================================== -->
                    <li
                        v-if="
                            can([
                                'gestion.pacientes.index',
                                'gestion.pacientes.create',
                                'gestion.pacientes.delete',
                                'gestion.pacientes.update',
                                'estudio.asignar',
                                'estudio.transcribir',
                                'estudio.leerEstudio',
                                'estudios.notificar.index',
                                'estudios.reporteLectura.index',
                                'dosimetria.dashboard',
                                'dosimetria.registros',
                                'dosimetria.configurar',
                                'tele.ragiologia.index',
                                'tele.ragiologia.enviar',
                                'tele.ragiologia.transcribir',
                                'ecografias.index',
                                'ecografias.admision',
                                'ecografias.anular',
                                'ecografias.leer',
                                'ecografias.reporte',
                            ])
                        "
                        class="nav-small-cap"
                    >
                        <span>Gestión Clínica</span>
                    </li>

                    <!-- Pacientes -->
                    <li
                        v-if="
                            can([
                                'gestion.pacientes.index',
                                'gestion.pacientes.create',
                                'gestion.pacientes.delete',
                                'gestion.pacientes.update',
                            ])
                        "
                        :class="{ active: isRouteActive('/pacientes') }"
                    >
                        <a
                            class="has-arrow waves-effect waves-dark"
                            :class="{ active: isRouteActive('/pacientes') }"
                            href="javascript:void(0)"
                            :aria-expanded="openMenus.pacientes ? 'true' : 'false'"
                            @click.prevent="toggleMenu('pacientes')"
                        >
                            <i class="icon-User"></i>
                            <span class="hide-menu">Pacientes</span>
                        </a>
                        <ul
                            class="collapse"
                            :class="{ in: openMenus.pacientes }"
                            :aria-expanded="openMenus.pacientes ? 'true' : 'false'"
                        >
                            <li :class="{ active: isRouteActive('/pacientes/admision') }">
                                <router-link to="/pacientes/admision" active-class="active">
                                    <i class="fa fa-user-plus text-info"></i> Admisión de Pacientes
                                </router-link>
                            </li>
                        </ul>
                    </li>

                    <!-- Asignación de Estudios -->
                    <li
                        v-if="
                            can([
                                'estudio.index',
                                'estudio.asignar',
                                'estudio.transcribir',
                                'estudio.leerEstudio',
                                'estudios.notificar.index',
                                'estudios.reporteLectura.index',
                            ])
                        "
                        :class="{ active: isRouteActive('/estudios') }"
                    >
                        <a
                            class="has-arrow waves-effect waves-dark"
                            :class="{ active: isRouteActive('/estudios') }"
                            href="javascript:void(0)"
                            :aria-expanded="openMenus.estudios ? 'true' : 'false'"
                            @click.prevent="toggleMenu('estudios')"
                        >
                            <i class="icon-Bookmark"></i>
                            <span class="hide-menu">Estudios</span>
                        </a>
                        <ul
                            class="collapse"
                            :class="{ in: openMenus.estudios }"
                            :aria-expanded="openMenus.estudios ? 'true' : 'false'"
                        >
                            <li
                                v-if="can('estudio.asignar')"
                                :class="{ active: isRouteActive('/estudios.asignar.listar') }"
                            >
                                <router-link to="/estudios.asignar.listar" active-class="active">
                                    <i class="fa fa-upload text-info"></i> Asignar Estudios
                                </router-link>
                            </li>
                            <li
                                v-if="can('estudio.asignar')"
                                :class="{ active: isRouteActive('/estudios.asignados.listar') }"
                            >
                                <router-link to="/estudios.asignados.listar" active-class="active">
                                    <i class="fa fa-check text-info"></i> Estudios Asignados
                                </router-link>
                            </li>
                            <li
                                v-if="can('estudio.transcribir')"
                                :class="{ active: isRouteActive('/estudios.transcribir.listar') }"
                            >
                                <router-link
                                    to="/estudios.transcribir.listar"
                                    active-class="active"
                                >
                                    <i class="fa fa-search text-info"></i> Pendientes por
                                    transcribir
                                </router-link>
                            </li>
                            <li
                                v-if="can('estudio.leerEstudio')"
                                :class="{ active: isRouteActive('/estudios.leer.estudio.listar') }"
                            >
                                <router-link
                                    to="/estudios.leer.estudio.listar"
                                    active-class="active"
                                >
                                    <i class="fa fa-search text-info"></i> Pendientes por leer
                                </router-link>
                            </li>
                            <li
                                v-if="can('estudios.notificar.index')"
                                :class="{ active: isRouteActive('/estudios.notificar.index') }"
                            >
                                <router-link to="/estudios.notificar.index" active-class="active">
                                    <i class="fa fa-envelope-o text-info"></i> Notificar estudio
                                </router-link>
                            </li>
                        </ul>
                    </li>

                    <!-- Control Radiación / Dosimetría -->
                    <li
                        v-if="
                            can([
                                'dosimetria.dashboard',
                                'dosimetria.registros',
                                'dosimetria.configurar',
                            ])
                        "
                        :class="{ active: isRouteActive('/dosimetria') || isRouteActive('/dosis') }"
                    >
                        <a
                            class="has-arrow waves-effect waves-dark"
                            :class="{
                                active: isRouteActive('/dosimetria') || isRouteActive('/dosis'),
                            }"
                            href="javascript:void(0)"
                            :aria-expanded="openMenus.dosimetria ? 'true' : 'false'"
                            @click.prevent="toggleMenu('dosimetria')"
                        >
                            <i class="fa fa-radiation text-warning"></i>
                            <span class="hide-menu">Control Radiación</span>
                        </a>
                        <ul
                            class="collapse"
                            :class="{ in: openMenus.dosimetria }"
                            :aria-expanded="openMenus.dosimetria ? 'true' : 'false'"
                        >
                            <li
                                v-if="can('dosimetria.dashboard')"
                                :class="{
                                    active:
                                        isRouteActive('/dosimetria/dashboard') ||
                                        isRouteActive('/dosis/dashboard'),
                                }"
                            >
                                <router-link to="/dosimetria/dashboard" active-class="active">
                                    <i class="fa fa-tachometer text-info"></i> Dashboard NRD
                                </router-link>
                            </li>
                        </ul>
                    </li>

                    <!-- Tele Radiología -->
                    <li
                        v-if="
                            can([
                                'tele.ragiologia.index',
                                'tele.ragiologia.enviar',
                                'tele.ragiologia.transcribir',
                            ])
                        "
                        :class="{
                            active:
                                isRouteActive('/tele-radiologia') ||
                                isRouteActive('/estudios.tele-radiologia'),
                        }"
                    >
                        <a
                            class="has-arrow waves-effect waves-dark"
                            :class="{
                                active:
                                    isRouteActive('/tele-radiologia') ||
                                    isRouteActive('/estudios.tele-radiologia'),
                            }"
                            href="javascript:void(0)"
                            :aria-expanded="openMenus.teleradiologia ? 'true' : 'false'"
                            @click.prevent="toggleMenu('teleradiologia')"
                        >
                            <i class="fa fa-cloud-upload"></i>
                            <span class="hide-menu">Tele Radiología</span>
                        </a>
                        <ul
                            class="collapse"
                            :class="{ in: openMenus.teleradiologia }"
                            :aria-expanded="openMenus.teleradiologia ? 'true' : 'false'"
                        >
                            <li
                                v-if="can('tele.ragiologia.index')"
                                :class="{ active: isRouteActive('/tele-radiologia') }"
                            >
                                <router-link to="/tele-radiologia" active-class="active">
                                    <i class="fa fa-upload text-info"></i> Enviar estudios
                                </router-link>
                            </li>
                            <li
                                v-if="can('tele.ragiologia.transcribir')"
                                :class="{ active: isRouteActive('/tele-radiologia-transcribir') }"
                            >
                                <router-link
                                    to="/tele-radiologia-transcribir"
                                    active-class="active"
                                >
                                    <i class="fa fa-pencil-square-o text-info"></i> Transcribir
                                    estudios
                                </router-link>
                            </li>
                        </ul>
                    </li>

                    <!-- Ecografías -->
                    <li
                        v-if="
                            can([
                                'ecografias.index',
                                'ecografias.admision',
                                'ecografias.anular',
                                'ecografias.leer',
                                'ecografias.reporte',
                                'ecografias.consentimientos.index',
                                'ecografias.consentimientos.plantillas.index',
                            ])
                        "
                        :class="{
                            active:
                                isRouteActive('/ecografias') ||
                                isRouteActive('/config-consentimientos'),
                        }"
                    >
                        <a
                            class="has-arrow waves-effect waves-dark"
                            :class="{
                                active:
                                    isRouteActive('/ecografias') ||
                                    isRouteActive('/config-consentimientos'),
                            }"
                            href="javascript:void(0)"
                            :aria-expanded="openMenus.ecografias ? 'true' : 'false'"
                            @click.prevent="toggleMenu('ecografias')"
                        >
                            <i class="fa fa-stethoscope"></i>
                            <span class="hide-menu">Ecografías</span>
                        </a>
                        <ul
                            class="collapse"
                            :class="{ in: openMenus.ecografias }"
                            :aria-expanded="openMenus.ecografias ? 'true' : 'false'"
                        >
                            <li
                                v-if="can('ecografias.admision')"
                                :class="{ active: isRouteActive('/ecografias.admisiones') }"
                            >
                                <router-link to="/ecografias.admisiones" active-class="active">
                                    <i class="fa fa-address-book-o text-info"></i> Admisionar para
                                    procedimientos
                                </router-link>
                            </li>
                            <li
                                v-if="can('ecografias.index')"
                                :class="{ active: isRouteActive('/ecografias.admisionadas') }"
                            >
                                <router-link to="/ecografias.admisionadas" active-class="active">
                                    <i class="fa fa-list text-info"></i> Ecografías admisionadas
                                </router-link>
                            </li>
                            <li
                                v-if="can('ecografias.leer')"
                                :class="{
                                    active: isRouteActive('/ecografias-pendientes-lecturas'),
                                }"
                            >
                                <router-link
                                    to="/ecografias-pendientes-lecturas"
                                    active-class="active"
                                >
                                    <i class="fa fa-pencil text-info"></i> Realizar lectura
                                </router-link>
                            </li>
                            <li
                                v-if="can('ecografias.consentimientos.index')"
                                :class="{ active: isRouteActive('/ecografias-consentimientos') }"
                            >
                                <router-link to="/ecografias-consentimientos" active-class="active">
                                    <i class="fa fa-id-card-o text-info"></i> Consentimientos
                                    (Firmas/PDF)
                                </router-link>
                            </li>
                            <li
                                v-if="can('ecografias.consentimientos.plantillas.index')"
                                :class="{
                                    active: isRouteActive('/ecografias-consentimientos-plantillas'),
                                }"
                            >
                                <router-link
                                    to="/ecografias-consentimientos-plantillas"
                                    active-class="active"
                                >
                                    <i class="fa fa-file-text-o text-info"></i> Plantillas
                                    Consentimientos
                                </router-link>
                            </li>
                        </ul>
                    </li>

                    <!-- ============================================================== -->
                    <!-- CITAS Y AGENDA -->
                    <!-- ============================================================== -->
                    <li
                        v-if="
                            can([
                                'citas.index',
                                'agendamiento.agendamiento.index',
                                'agendamiento.agendamiento.create',
                                'agendamiento.agendamiento.delete',
                                'agendamiento.agendamiento.update',
                                'agendamiento.agendamiento.interectuar',
                                'agendamiento.solicitudes.index',
                                'agendamiento.solicitudes.create',
                                'agendamiento.solicitudes.delete',
                                'agendamiento.solicitudes.update',
                                'agendamiento.disponibilidad.index',
                            ])
                        "
                        class="nav-small-cap"
                    >
                        <span>Citas y Agenda</span>
                    </li>

                    <!-- Citas -->
                    <li v-if="can('citas.index')" :class="{ active: isRouteActive('/citas') }">
                        <a
                            class="has-arrow waves-effect waves-dark"
                            :class="{ active: isRouteActive('/citas') }"
                            href="javascript:void(0)"
                            :aria-expanded="openMenus.citas ? 'true' : 'false'"
                            @click.prevent="toggleMenu('citas')"
                        >
                            <i class="fa fa-calendar"></i>
                            <span class="hide-menu">Citas</span>
                        </a>
                        <ul
                            class="collapse"
                            :class="{ in: openMenus.citas }"
                            :aria-expanded="openMenus.citas ? 'true' : 'false'"
                        >
                            <li :class="{ active: isRouteActive('/citas/revision') }">
                                <router-link to="/citas/revision" active-class="active">
                                    <i class="fa fa-inbox text-info"></i> Solicitudes Pendientes
                                    <citas-notification-badge-component />
                                </router-link>
                            </li>
                            <li
                                :class="{
                                    active:
                                        isRouteActive('/citas') &&
                                        !isRouteActive('/citas/revision') &&
                                        !isRouteActive('/citas/gestion') &&
                                        !isRouteActive('/citas/recomendaciones'),
                                }"
                            >
                                <router-link to="/citas" active-class="active" exact>
                                    <i class="fa fa-list text-info"></i> Listado de Citas
                                </router-link>
                            </li>
                            <li :class="{ active: isRouteActive('/citas/gestion') }">
                                <router-link to="/citas/gestion" active-class="active">
                                    <i class="fa fa-dashboard text-info"></i> Tablero de Gestión
                                </router-link>
                            </li>
                            <li :class="{ active: isRouteActive('/citas/recomendaciones') }">
                                <router-link to="/citas/recomendaciones" active-class="active">
                                    <i class="fa fa-commenting text-info"></i> Recomendaciones
                                </router-link>
                            </li>
                        </ul>
                    </li>

                    <!-- Agenda -->
                    <li
                        v-if="
                            can([
                                'agendamiento.agendamiento.index',
                                'agendamiento.agendamiento.create',
                                'agendamiento.agendamiento.delete',
                                'agendamiento.agendamiento.update',
                                'agendamiento.agendamiento.interectuar',
                                'agendamiento.solicitudes.index',
                                'agendamiento.solicitudes.create',
                                'agendamiento.solicitudes.delete',
                                'agendamiento.solicitudes.update',
                                'agendamiento.disponibilidad.index',
                            ])
                        "
                        :class="{ active: isRouteActive('/agenda') }"
                    >
                        <a
                            class="has-arrow waves-effect waves-dark"
                            :class="{ active: isRouteActive('/agenda') }"
                            href="javascript:void(0)"
                            :aria-expanded="openMenus.agenda ? 'true' : 'false'"
                            @click.prevent="toggleMenu('agenda')"
                        >
                            <i class="icon-Address-Book"></i>
                            <span class="hide-menu">Agenda</span>
                        </a>
                        <ul
                            class="collapse"
                            :class="{ in: openMenus.agenda }"
                            :aria-expanded="openMenus.agenda ? 'true' : 'false'"
                        >
                            <li
                                :class="{
                                    active:
                                        isRouteActive('/agenda') &&
                                        !isRouteActive('/agenda-disponibilidad'),
                                }"
                            >
                                <router-link to="/agenda" active-class="active" exact>
                                    <i class="fa fa-calendar-check-o text-info"></i> Agenda Médica
                                </router-link>
                            </li>
                            <li
                                v-if="can('agendamiento.disponibilidad.index')"
                                :class="{ active: isRouteActive('/agenda-disponibilidad') }"
                            >
                                <router-link to="/agenda-disponibilidad" active-class="active">
                                    <i class="fa fa-clock-o text-info"></i> Disponibilidad
                                </router-link>
                            </li>
                        </ul>
                    </li>

                    <!-- ============================================================== -->
                    <!-- GESTIÓN Y REPORTES -->
                    <!-- ============================================================== -->
                    <li
                        v-if="
                            can([
                                'reportes.indicadores',
                                'reportes.detallados',
                                'contratos.index',
                                'contratos.create',
                                'liquidacion.index',
                            ])
                        "
                        class="nav-small-cap"
                    >
                        <span>Gestión y Reportes</span>
                    </li>

                    <!-- Reportes -->
                    <li
                        v-if="
                            can([
                                'reportes.indicadores',
                                'reportes.detallados',
                                'reportes.produccion.index',
                                'reportes.pac.index',
                            ])
                        "
                        :class="{ active: isRouteActive('/reportes') }"
                    >
                        <a
                            class="has-arrow waves-effect waves-dark"
                            :class="{ active: isRouteActive('/reportes') }"
                            href="javascript:void(0)"
                            :aria-expanded="openMenus.reportes ? 'true' : 'false'"
                            @click.prevent="toggleMenu('reportes')"
                        >
                            <i class="icon-Book"></i>
                            <span class="hide-menu">Reportes</span>
                        </a>
                        <ul
                            class="collapse"
                            :class="{ in: openMenus.reportes }"
                            :aria-expanded="openMenus.reportes ? 'true' : 'false'"
                        >
                            <li :class="{ active: isRouteActive('/reportes-produccion') }">
                                <router-link to="/reportes-produccion" active-class="active">
                                    <i class="fa fa-bar-chart text-info"></i> Producción
                                </router-link>
                            </li>
                            <li :class="{ active: isRouteActive('/reportes-estudios-pac') }">
                                <router-link to="/reportes-estudios-pac" active-class="active">
                                    <i class="fa fa-database text-info"></i> Estudios PACS
                                </router-link>
                            </li>
                        </ul>
                    </li>

                    <!-- Liquidación y Productividad -->
                    <li
                        v-if="can('liquidacion.index')"
                        :class="{
                            active:
                                isRouteActive('/liquidacion') || isRouteActive('/productividad'),
                        }"
                    >
                        <a
                            class="has-arrow waves-effect waves-dark"
                            :class="{
                                active:
                                    isRouteActive('/liquidacion') ||
                                    isRouteActive('/productividad'),
                            }"
                            href="javascript:void(0)"
                            :aria-expanded="openMenus.liquidacion ? 'true' : 'false'"
                            @click.prevent="toggleMenu('liquidacion')"
                        >
                            <i class="icon-Calculator"></i>
                            <span class="hide-menu">Liquidación</span>
                        </a>
                        <ul
                            class="collapse"
                            :class="{ in: openMenus.liquidacion }"
                            :aria-expanded="openMenus.liquidacion ? 'true' : 'false'"
                        >
                            <li :class="{ active: isRouteActive('/liquidacion') }">
                                <router-link to="/liquidacion" active-class="active">
                                    <i class="fa fa-money text-info"></i> Liquidación
                                </router-link>
                            </li>
                            <li :class="{ active: isRouteActive('/productividad') }">
                                <router-link to="/productividad" active-class="active">
                                    <i class="fa fa-line-chart text-info"></i> Productividad
                                </router-link>
                            </li>
                        </ul>
                    </li>

                    <!-- Contratación -->
                    <li
                        v-if="can(['contratos.index', 'contratos.create'])"
                        :class="{ active: isRouteActive('/contratos') }"
                    >
                        <a
                            class="has-arrow waves-effect waves-dark"
                            :class="{ active: isRouteActive('/contratos') }"
                            href="javascript:void(0)"
                            :aria-expanded="openMenus.contratos ? 'true' : 'false'"
                            @click.prevent="toggleMenu('contratos')"
                        >
                            <i class="icon-Contrast"></i>
                            <span class="hide-menu">Contratación</span>
                        </a>
                        <ul
                            class="collapse"
                            :class="{ in: openMenus.contratos }"
                            :aria-expanded="openMenus.contratos ? 'true' : 'false'"
                        >
                            <li :class="{ active: isRouteActive('/contratos') }">
                                <router-link to="/contratos" active-class="active">
                                    <i class="fa fa-file-text-o text-info"></i> Contratos
                                </router-link>
                            </li>
                        </ul>
                    </li>

                    <!-- ============================================================== -->
                    <!-- ADMINISTRACIÓN Y SOPORTE -->
                    <!-- ============================================================== -->
                    <li
                        v-if="
                            can([
                                'helpdesk.tickets.index',
                                'helpdesk.tickets.create',
                                'helpdesk.tickets.show',
                                'helpdesk.reportes.index',
                                'helpdesk.notificaciones.index',
                                'config.admin.salud.index',
                                'config.diagnosticos.index',
                                'config.prioridades.index',
                                'config.productos.index',
                                'config.sedes.index',
                                'config.servicio.citas.index',
                                'config.plantillas.index',
                                'config.tipo.documento.index',
                                'config.consentimiento_informado.index',
                                'config.whatsapp.saludo.index',
                                'ecografias.consentimientos.index',
                                'ecografias.consentimientos.plantillas.index',
                                'helpdesk.config.categorias.index',
                                'helpdesk.config.prioridades.index',
                                'helpdesk.config.estados.index',
                                'seguridad.usuarios.index',
                                'seguridad.roles.index',
                                'seguridad.auditoria.index',
                                'seguridad.backups.index',
                                'seguridad.tokens.index',
                            ])
                        "
                        class="nav-small-cap"
                    >
                        <span>Administración</span>
                    </li>

                    <!-- Helpdesk -->
                    <li
                        v-if="
                            can([
                                'helpdesk.tickets.index',
                                'helpdesk.tickets.create',
                                'helpdesk.tickets.show',
                                'helpdesk.reportes.index',
                                'helpdesk.notificaciones.index',
                            ])
                        "
                        :class="{ active: isRouteActive('/helpdesk') }"
                    >
                        <a
                            class="has-arrow waves-effect waves-dark"
                            :class="{ active: isRouteActive('/helpdesk') }"
                            href="javascript:void(0)"
                            :aria-expanded="openMenus.helpdesk ? 'true' : 'false'"
                            @click.prevent="toggleMenu('helpdesk')"
                        >
                            <i class="icon-Support"></i>
                            <span class="hide-menu">Helpdesk</span>
                        </a>
                        <ul
                            class="collapse"
                            :class="{ in: openMenus.helpdesk }"
                            :aria-expanded="openMenus.helpdesk ? 'true' : 'false'"
                        >
                            <li
                                v-if="can('helpdesk.tickets.index')"
                                :class="{
                                    active:
                                        isRouteActive('/helpdesk-tickets') &&
                                        !isRouteActive('/helpdesk-tickets/crear'),
                                }"
                            >
                                <router-link to="/helpdesk-tickets" active-class="active">
                                    <i class="fa fa-ticket text-info"></i> Mis tickets
                                </router-link>
                            </li>
                            <li
                                v-if="can('helpdesk.tickets.create')"
                                :class="{ active: isRouteActive('/helpdesk-tickets/crear') }"
                            >
                                <router-link to="/helpdesk-tickets/crear" active-class="active">
                                    <i class="fa fa-plus text-info"></i> Crear ticket
                                </router-link>
                            </li>
                            <li
                                v-if="can('helpdesk.reportes.index')"
                                :class="{ active: isRouteActive('/helpdesk-reportes') }"
                            >
                                <router-link to="/helpdesk-reportes" active-class="active">
                                    <i class="fa fa-bar-chart text-info"></i> Reportes Helpdesk
                                </router-link>
                            </li>
                            <li
                                v-if="can('helpdesk.notificaciones.index')"
                                :class="{ active: isRouteActive('/helpdesk-notificaciones') }"
                            >
                                <router-link to="/helpdesk-notificaciones" active-class="active">
                                    <i class="fa fa-bell text-info"></i> Notificaciones
                                </router-link>
                            </li>
                        </ul>
                    </li>

                    <!-- Configuraciones / Parametrización -->
                    <li
                        v-if="
                            can([
                                'config.admin.salud.index',
                                'config.diagnosticos.index',
                                'config.prioridades.index',
                                'config.productos.index',
                                'config.sedes.index',
                                'config.servicio.citas.index',
                                'config.plantillas.index',
                                'config.tipo.documento.index',
                                'config.consentimiento_informado.index',
                                'config.whatsapp.saludo.index',
                                'ecografias.consentimientos.index',
                                'ecografias.consentimientos.plantillas.index',
                                'helpdesk.config.categorias.index',
                                'helpdesk.config.prioridades.index',
                                'helpdesk.config.estados.index',
                                'citas.index',
                            ])
                        "
                        :class="{ active: isRouteActive('/config') }"
                    >
                        <a
                            class="has-arrow waves-effect waves-dark"
                            :class="{ active: isRouteActive('/config') }"
                            href="javascript:void(0)"
                            :aria-expanded="openMenus.config ? 'true' : 'false'"
                            @click.prevent="toggleMenu('config')"
                        >
                            <i class="icon-Gears"></i>
                            <span class="hide-menu">Configuraciones</span>
                        </a>
                        <ul
                            class="collapse"
                            :class="{ in: openMenus.config }"
                            :aria-expanded="openMenus.config ? 'true' : 'false'"
                        >
                            <li
                                v-if="can('config.admin.salud.index')"
                                :class="{ active: isRouteActive('/config-adminsalud-listar') }"
                            >
                                <router-link to="/config-adminsalud-listar" active-class="active">
                                    <i class="fa fa-medkit text-info"></i> Admin. Salud
                                </router-link>
                            </li>
                            <li
                                v-if="can('config.diagnosticos.index')"
                                :class="{ active: isRouteActive('/config-diagnosticos-listar') }"
                            >
                                <router-link to="/config-diagnosticos-listar" active-class="active">
                                    <i class="fa fa-stethoscope text-info"></i> Diagnósticos
                                </router-link>
                            </li>
                            <li
                                v-if="can('config.prioridades.index')"
                                :class="{ active: isRouteActive('/config-prioridades-listar') }"
                            >
                                <router-link to="/config-prioridades-listar" active-class="active">
                                    <i class="fa fa-flag text-info"></i> Prioridades
                                </router-link>
                            </li>
                            <li
                                v-if="can('config.productos.index')"
                                :class="{ active: isRouteActive('/config-productos-listar') }"
                            >
                                <router-link to="/config-productos-listar" active-class="active">
                                    <i class="fa fa-cube text-info"></i> Productos
                                </router-link>
                            </li>
                            <li
                                v-if="can('config.sedes.index')"
                                :class="{ active: isRouteActive('/config-sedes-listar') }"
                            >
                                <router-link to="/config-sedes-listar" active-class="active">
                                    <i class="fa fa-hospital-o text-info"></i> Sedes
                                </router-link>
                            </li>
                            <li
                                v-if="can('config.servicio.citas.index')"
                                :class="{ active: isRouteActive('/config-servicio-citas-listar') }"
                            >
                                <router-link
                                    to="/config-servicio-citas-listar"
                                    active-class="active"
                                >
                                    <i class="fa fa-calendar-plus-o text-info"></i> Servicios de
                                    Citas
                                </router-link>
                            </li>
                            <li
                                v-if="can('config.servicio.citas.index')"
                                :class="{ active: isRouteActive('/config-servicio-producto') }"
                            >
                                <router-link to="/config-servicio-producto" active-class="active">
                                    <i class="fa fa-cubes text-info"></i> Productos por Servicio
                                </router-link>
                            </li>
                            <li
                                v-if="can('config.plantillas.index')"
                                :class="{
                                    active: isRouteActive('/config-tipos-plantillas.listar'),
                                }"
                            >
                                <router-link
                                    to="/config-tipos-plantillas.listar"
                                    active-class="active"
                                >
                                    <i class="fa fa-folder-open-o text-info"></i> Tipos de
                                    Plantillas
                                </router-link>
                            </li>
                            <li
                                v-if="can('config.plantillas.index')"
                                :class="{ active: isRouteActive('/config-plantillas-listar') }"
                            >
                                <router-link to="/config-plantillas-listar" active-class="active">
                                    <i class="fa fa-file-text-o text-info"></i> Plantillas
                                </router-link>
                            </li>
                            <li
                                v-if="can('config.tipo.documento.index')"
                                :class="{
                                    active: isRouteActive('/config-tipos-documentos-listar'),
                                }"
                            >
                                <router-link
                                    to="/config-tipos-documentos-listar"
                                    active-class="active"
                                >
                                    <i class="fa fa-id-card-o text-info"></i> Tipos de Documentos
                                </router-link>
                            </li>
                            <li
                                v-if="can('config.consentimiento_informado.index')"
                                :class="{
                                    active: isRouteActive(
                                        '/config-consentimiento-informado-listar'
                                    ),
                                }"
                            >
                                <router-link
                                    to="/config-consentimiento-informado-listar"
                                    active-class="active"
                                >
                                    <i class="fa fa-check-square-o text-info"></i> Consentimiento
                                    Informado
                                </router-link>
                            </li>
                            <li
                                v-if="can('config.whatsapp.saludo.index')"
                                :class="{ active: isRouteActive('/config-whatsapp-listar') }"
                            >
                                <router-link to="/config-whatsapp-listar" active-class="active">
                                    <i class="fa fa-whatsapp text-info"></i> WhatsApp Bot
                                </router-link>
                            </li>
                            <li
                                v-if="can('citas.index')"
                                :class="{ active: isRouteActive('/config-cita-rechazo-motivos') }"
                            >
                                <router-link
                                    to="/config-cita-rechazo-motivos"
                                    active-class="active"
                                >
                                    <i class="fa fa-ban text-info"></i> Motivos de Rechazo
                                </router-link>
                            </li>
                            <li
                                v-if="can('helpdesk.config.categorias.index')"
                                :class="{
                                    active: isRouteActive('/config-helpdesk-categorias-listar'),
                                }"
                            >
                                <router-link
                                    to="/config-helpdesk-categorias-listar"
                                    active-class="active"
                                >
                                    <i class="fa fa-tags text-info"></i> Helpdesk: Categorías
                                </router-link>
                            </li>
                            <li
                                v-if="can('helpdesk.config.prioridades.index')"
                                :class="{
                                    active: isRouteActive('/config-helpdesk-prioridades-listar'),
                                }"
                            >
                                <router-link
                                    to="/config-helpdesk-prioridades-listar"
                                    active-class="active"
                                >
                                    <i class="fa fa-exclamation-circle text-info"></i> Helpdesk:
                                    Prioridades
                                </router-link>
                            </li>
                            <li
                                v-if="can('helpdesk.config.estados.index')"
                                :class="{
                                    active: isRouteActive('/config-helpdesk-estados-listar'),
                                }"
                            >
                                <router-link
                                    to="/config-helpdesk-estados-listar"
                                    active-class="active"
                                >
                                    <i class="fa fa-tasks text-info"></i> Helpdesk: Estados
                                </router-link>
                            </li>
                        </ul>
                    </li>

                    <!-- Seguridad -->
                    <li
                        v-if="
                            can([
                                'seguridad.usuarios.index',
                                'seguridad.roles.index',
                                'seguridad.auditoria.index',
                                'seguridad.backups.index',
                                'seguridad.tokens.index',
                            ])
                        "
                        :class="{ active: isRouteActive('/seguridad') }"
                    >
                        <a
                            class="has-arrow waves-effect waves-dark"
                            :class="{ active: isRouteActive('/seguridad') }"
                            href="javascript:void(0)"
                            :aria-expanded="openMenus.seguridad ? 'true' : 'false'"
                            @click.prevent="toggleMenu('seguridad')"
                        >
                            <i class="icon-Key-Lock"></i>
                            <span class="hide-menu">Seguridad</span>
                        </a>
                        <ul
                            class="collapse"
                            :class="{ in: openMenus.seguridad }"
                            :aria-expanded="openMenus.seguridad ? 'true' : 'false'"
                        >
                            <li
                                v-if="can('seguridad.usuarios.index')"
                                :class="{ active: isRouteActive('/seguridad.usuarios.listar') }"
                            >
                                <router-link to="/seguridad.usuarios.listar" active-class="active">
                                    <i class="fa fa-user-circle-o text-info"></i> Usuarios
                                </router-link>
                            </li>
                            <li
                                v-if="can('seguridad.roles.index')"
                                :class="{ active: isRouteActive('/seguridad.roles.listar') }"
                            >
                                <router-link to="/seguridad.roles.listar" active-class="active">
                                    <i class="fa fa-key text-info"></i> Roles
                                </router-link>
                            </li>
                            <li
                                v-if="can('seguridad.auditoria.index')"
                                :class="{ active: isRouteActive('/seguridad-auditoria') }"
                            >
                                <router-link to="/seguridad-auditoria" active-class="active">
                                    <i class="fa fa-shield text-info"></i> Auditoria
                                </router-link>
                            </li>
                            <li
                                v-if="can('seguridad.backups.index')"
                                :class="{ active: isRouteActive('/seguridad-backups') }"
                            >
                                <router-link to="/seguridad-backups" active-class="active">
                                    <i class="fa fa-database text-info"></i> Monitoreo Backups
                                </router-link>
                            </li>
                            <li
                                v-if="can('seguridad.tokens.index')"
                                :class="{ active: isRouteActive('/seguridad-tokens') }"
                            >
                                <router-link to="/seguridad-tokens" active-class="active">
                                    <i class="fa fa-ticket text-info"></i> Tokens de Sedes
                                </router-link>
                            </li>
                        </ul>
                    </li>
                </ul>
            </nav>
            <!-- End Sidebar navigation -->
        </div>
        <!-- End Sidebar scroll-->
    </aside>
</template>

<script setup>
import { reactive, watch } from "vue";
import { useRoute } from "vue-router";
import { useAuth } from "../composables/useAuth";

const route = useRoute();
const { can } = useAuth();

const openMenus = reactive({
    pacientes: false,
    estudios: false,
    dosimetria: false,
    teleradiologia: false,
    ecografias: false,
    citas: false,
    agenda: false,
    reportes: false,
    liquidacion: false,
    contratos: false,
    helpdesk: false,
    config: false,
    seguridad: false,
});

function toggleMenu(menuKey) {
    openMenus[menuKey] = !openMenus[menuKey];
}

function isRouteActive(prefix) {
    if (!route?.path) return false;
    return route.path.startsWith(prefix);
}

// Auto-expandir acordeón si la ruta activa pertenece al grupo
watch(
    () => route.path,
    (newPath) => {
        if (!newPath) return;
        if (newPath.startsWith("/pacientes")) openMenus.pacientes = true;
        if (newPath.startsWith("/estudios")) openMenus.estudios = true;
        if (newPath.startsWith("/dosimetria") || newPath.startsWith("/dosis"))
            openMenus.dosimetria = true;
        if (newPath.startsWith("/tele-radiologia")) openMenus.teleradiologia = true;
        if (newPath.startsWith("/ecografias") || newPath.startsWith("/config-consentimientos"))
            openMenus.ecografias = true;
        if (newPath.startsWith("/citas")) openMenus.citas = true;
        if (newPath.startsWith("/agenda")) openMenus.agenda = true;
        if (newPath.startsWith("/reportes")) openMenus.reportes = true;
        if (newPath.startsWith("/liquidacion") || newPath.startsWith("/productividad"))
            openMenus.liquidacion = true;
        if (newPath.startsWith("/contratos")) openMenus.contratos = true;
        if (newPath.startsWith("/helpdesk")) openMenus.helpdesk = true;
        if (newPath.startsWith("/config")) openMenus.config = true;
        if (newPath.startsWith("/seguridad")) openMenus.seguridad = true;
    },
    { immediate: true }
);
</script>

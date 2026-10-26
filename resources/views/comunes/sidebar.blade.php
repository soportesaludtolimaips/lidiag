@if (Auth::check())
    <aside class="left-sidebar">
        <!-- Sidebar scroll-->
        <div class="scroll-sidebar">
            <!-- Sidebar navigation-->
            <nav class="sidebar-nav">
                <ul id="sidebarnav">
                    <li class="nav-small-cap"><span>Personal</span></li>
                    <li class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <a class="waves-effect waves-dark {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}" aria-expanded="false">
                            <i class="icon-Car-Wheel"></i>
                            <span class="hide-menu">Dashboard</span>
                        </a>
                    </li>
                    @if (auth()->user()->hasAnyPermission(['gestion.pacientes.index', 'gestion.pacientes.create', 'gestion.pacientes.delete', 'gestion.pacientes.update']))
                        <li class="nav-small-cap"><span>Pacientes</span></li>
                        <li class="{{ request()->routeIs('gestion.pacientes.*') ? 'active' : '' }}">
                            <a class="has-arrow waves-effect waves-dark {{ request()->routeIs('gestion.pacientes.*') ? 'active' : '' }}" href="#" aria-expanded="{{ request()->routeIs('gestion.pacientes.*') ? 'true' : 'false' }}">
                                <i class="icon-User"></i>
                                <span class="hide-menu">Pacientes</span>
                            </a>
                            <ul aria-expanded="{{ request()->routeIs('gestion.pacientes.*') ? 'true' : 'false' }}" class="collapse {{ request()->routeIs('gestion.pacientes.*') ? 'in' : '' }}">
                                <li>
                                    <a href="{{ route('gestion.pacientes.admision') }}" class="{{ request()->routeIs('gestion.pacientes.admision') ? 'active' : '' }}">
                                        <i class="fa fa-user-plus text-info"></i> Admisionar Paciente
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('gestion.pacientes.admision') }}" class="{{ request()->routeIs('gestion.pacientes.admision') ? 'active' : '' }}">
                                        <i class="fa fa-users text-info"></i> Listar Pacientes
                                    </a>
                                </li>
                            </ul>
                        </li>
                    @endif

                    @if (auth()->user()->hasAnyPermission(['estudio.asignar', 'estudio.transcribir', 'estudio.leerEstudio', 'estudios.notificar.index', 'estudios.reporteLectura.index']))
                        <li class="nav-small-cap"><span>Estudios</span></li>
                        <li class="{{ request()->routeIs('estudios.*') ? 'active' : '' }}">
                            <a class="has-arrow waves-effect waves-dark {{ request()->routeIs('estudios.*') ? 'active' : '' }}" href="#" aria-expanded="{{ request()->routeIs('estudios.*') ? 'true' : 'false' }}">
                                <i class="icon-Bookmark"></i>
                                <span class="hide-menu">Asignación de estudios</span>
                            </a>
                            <ul aria-expanded="{{ request()->routeIs('estudios.*') ? 'true' : 'false' }}" class="collapse {{ request()->routeIs('estudios.*') ? 'in' : '' }}">
                                @can('estudio.asignar')
                                    <li class="{{ request()->routeIs('estudios.asignar.*') ? 'active' : '' }}">
                                        <a class="{{ request()->routeIs('estudios.asignar.*') ? 'active' : '' }}" href="{{ route('estudios.asignar.listar') }}">
                                            <i class="fa fa-upload text-info"></i> Asignar Estudios
                                        </a>
                                    </li>
                                    <li class="{{ request()->routeIs('estudios.asignados.*') ? 'active' : '' }}">
                                        <a class="{{ request()->routeIs('estudios.asignados.*') ? 'active' : '' }}" href="{{ route('estudios.asignados.listar') }}">
                                            <i class="fa fa-check text-info"></i> Estudios Asignados
                                        </a>
                                    </li>
                                @endcan
                                @can('estudio.transcribir')
                                    <li class="{{ request()->routeIs('estudios.transcribir.*') ? 'active' : '' }}">
                                        <a class="{{ request()->routeIs('estudios.transcribir.*') ? 'active' : '' }}" href="{{ route('estudios.transcribir.listar') }}">
                                            <i class="fa fa-search text-info"></i> Pendientes por transcribir
                                        </a>
                                    </li>
                                @endcan
                                @canany('estudio.leerEstudio')
                                    <li class="{{ request()->routeIs('estudios.leer.*') ? 'active' : '' }}">
                                        <a class="{{ request()->routeIs('estudios.leer.*') ? 'active' : '' }}" href="{{ route('estudios.leer.estudio.listar') }}">
                                            <i class="fa fa-search text-info"></i> Pendientes por leer
                                        </a>
                                    </li>
                                @endcanany
                                @canany('estudios.notificar.index')
                                    <li class="{{ request()->routeIs('estudios.notificar.*') ? 'active' : '' }}">
                                        <a class="{{ request()->routeIs('estudios.notificar.*') ? 'active' : '' }}" href="{{ route('estudios.notificar.index') }}">
                                            <i class="fa fa-envelope-o text-info"></i> Notificar estudio
                                        </a>
                                    </li>
                                @endcanany
                            </ul>
                        </li>
                    @endif

                    @if (auth()->user()->hasAnyPermission(['dosimetria.dashboard', 'dosimetria.registros', 'dosimetria.configurar']))
                        <li class="nav-small-cap"><span>Dosis</span></li>
                        <li class="{{ (request()->routeIs('dosimetria.*') || request()->routeIs('dosis.*')) ? 'active' : '' }}">
                            <a class="has-arrow waves-effect waves-dark {{ (request()->routeIs('dosimetria.*') || request()->routeIs('dosis.*')) ? 'active' : '' }}" href="#" aria-expanded="{{ (request()->routeIs('dosimetria.*') || request()->routeIs('dosis.*')) ? 'true' : 'false' }}">
                                <i class="fa fa-radiation text-warning"></i>
                                <span class="hide-menu">Control Radiación</span>
                            </a>
                            <ul aria-expanded="{{ (request()->routeIs('dosimetria.*') || request()->routeIs('dosis.*')) ? 'true' : 'false' }}" class="collapse {{ (request()->routeIs('dosimetria.*') || request()->routeIs('dosis.*')) ? 'in' : '' }}">
                                @can('dosimetria.dashboard')
                                    <li class="{{ (request()->routeIs('dosimetria.dashboard') || request()->routeIs('dosis.dashboard')) ? 'active' : '' }}">
                                        <a class="{{ (request()->routeIs('dosimetria.dashboard') || request()->routeIs('dosis.dashboard')) ? 'active' : '' }}" href="{{ route('dosimetria.dashboard') }}">
                                            <i class="fa fa-tachometer text-info"></i> Dashboard NRD
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </li>
                    @endif

                    @if (auth()->user()->hasAnyPermission(['tele.ragiologia.index', 'tele.ragiologia.enviar', 'tele.ragiologia.transcribir']))
                        <li class="nav-small-cap"><span>Tele Radiología</span></li>
                        <li class="{{ request()->routeIs('teleradiologia.*') || request()->routeIs('tele.ragiologia.*') ? 'active' : '' }}">
                            <a class="has-arrow waves-effect waves-dark {{ request()->routeIs('teleradiologia.*') || request()->routeIs('tele.ragiologia.*') ? 'active' : '' }}" href="#" aria-expanded="{{ request()->routeIs('teleradiologia.*') || request()->routeIs('tele.ragiologia.*') ? 'true' : 'false' }}">
                                <i class="fa fa-cloud-upload"></i>
                                <span class="hide-menu">Tele Radiología</span>
                            </a>
                            <ul aria-expanded="{{ request()->routeIs('teleradiologia.*') || request()->routeIs('tele.ragiologia.*') ? 'true' : 'false' }}" class="collapse {{ request()->routeIs('teleradiologia.*') || request()->routeIs('tele.ragiologia.*') ? 'in' : '' }}">
                                @can('tele.ragiologia.index')
                                    <li class="{{ request()->routeIs('teleradiologia.index') ? 'active' : '' }}">
                                        <a class="{{ request()->routeIs('teleradiologia.index') ? 'active' : '' }}" href="{{ route('teleradiologia.index') }}">
                                            <i class="fa fa-upload text-info"></i> Enviar estudios
                                        </a>
                                    </li>
                                @endcan
                                @can('tele.ragiologia.transcribir')
                                    <li class="{{ request()->routeIs('teleradiologia.transcribir') ? 'active' : '' }}">
                                        <a class="{{ request()->routeIs('teleradiologia.transcribir') ? 'active' : '' }}" href="{{ route('teleradiologia.transcribir') }}">
                                            <i class="fa fa-upload text-info"></i> Transcribir estudios
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </li>
                    @endif

                    @if (auth()->user()->hasAnyPermission(['ecografias.index', 'ecografias.admision', 'ecografias.anular', 'ecografias.leer', 'ecografias.reporte']))
                        <li class="nav-small-cap"><span>Ecografías</span></li>
                        <li class="{{ request()->routeIs('ecografias.*') ? 'active' : '' }}">
                            <a class="has-arrow waves-effect waves-dark {{ request()->routeIs('ecografias.*') ? 'active' : '' }}" href="#" aria-expanded="{{ request()->routeIs('ecografias.*') ? 'true' : 'false' }}">
                                <i class="fa fa-etsy"></i>
                                <span class="hide-menu">Ecografías</span>
                            </a>
                            <ul aria-expanded="{{ request()->routeIs('ecografias.*') ? 'true' : 'false' }}" class="collapse {{ request()->routeIs('ecografias.*') ? 'in' : '' }}">
                                @can('ecografias.admision')
                                    <li class="{{ request()->routeIs('ecografias.admisiones') ? 'active' : '' }}">
                                        <a class="{{ request()->routeIs('ecografias.admisiones') ? 'active' : '' }}" href="{{ route('ecografias.admisiones') }}">
                                            <i class="fa fa-address-book-o text-info"></i> Admisionar para procedimientos
                                        </a>
                                    </li>
                                @endcan
                                @can('ecografias.index')
                                    <li class="{{ request()->routeIs('ecografias.adminisionadas') ? 'active' : '' }}">
                                        <a class="{{ request()->routeIs('ecografias.adminisionadas') ? 'active' : '' }}" href="{{ route('ecografias.adminisionadas') }}">
                                            <i class="fa fa-address-book-o text-info"></i> Ecografías admisionadas
                                        </a>
                                    </li>
                                @endcan
                                @can('ecografias.leer')
                                    <li class="{{ request()->routeIs('ecografias.pendientes.lecturas') ? 'active' : '' }}">
                                        <a class="{{ request()->routeIs('ecografias.pendientes.lecturas') ? 'active' : '' }}" href="{{ route('ecografias.pendientes.lecturas') }}">
                                            <i class="fa fa-pencil text-info"></i> Realizar lectura
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </li>
                    @endif
                    @if (auth()->user()->hasAnyPermission(['citas.index']))
                        <li class="nav-small-cap"><span>Citas</span></li>
                        <li class="{{ request()->routeIs('citas.*') || request()->routeIs('agenda.*') ? 'active' : '' }}">
                            <a class="has-arrow waves-effect waves-dark {{ request()->routeIs('citas.*') || request()->routeIs('agenda.*') ? 'active' : '' }}" href="#" aria-expanded="{{ request()->routeIs('citas.*') || request()->routeIs('agenda.*') ? 'true' : 'false' }}">
                                <i class="fa fa-calendar"></i>
                                <span class="hide-menu">Citas</span>
                            </a>
                            <ul aria-expanded="{{ request()->routeIs('citas.*') || request()->routeIs('agenda.*') ? 'true' : 'false' }}" class="collapse {{ request()->routeIs('citas.*') || request()->routeIs('agenda.*') ? 'in' : '' }}">
                                @can('citas.index')
                                    <li class="{{ request()->routeIs('citas.revision') ? 'active' : '' }}">
                                        <a class="{{ request()->routeIs('citas.revision') ? 'active' : '' }}" href="{{ route('citas.revision') }}">
                                            <i class="fa fa-inbox text-info"></i> Solicitudes Pendientes
                                            <citas-notification-badge-component></citas-notification-badge-component>
                                        </a>
                                    </li>
                                    <li class="{{ request()->routeIs('citas.index') ? 'active' : '' }}">
                                        <a class="{{ request()->routeIs('citas.index') ? 'active' : '' }}" href="{{ route('citas.index') }}">
                                            <i class="fa fa-list text-info"></i> Listado de Citas
                                        </a>
                                    </li>
                                    <li class="{{ request()->routeIs('citas.gestion') ? 'active' : '' }}">
                                        <a class="{{ request()->routeIs('citas.gestion') ? 'active' : '' }}" href="{{ route('citas.gestion') }}">
                                            <i class="fa fa-dashboard text-info"></i> Tablero de Gestión
                                        </a>
                                    </li>
                                    <li class="{{ request()->routeIs('citas.recomendaciones') ? 'active' : '' }}">
                                        <a class="{{ request()->routeIs('citas.recomendaciones') ? 'active' : '' }}" href="{{ route('citas.recomendaciones') }}">
                                            <i class="fa fa-commenting text-info"></i> Recomendaciones
                                        </a>
                                    </li>
                                @endcan
                                @can('agendamiento.disponibilidad.index')
                                    <li class="{{ request()->routeIs('agenda.disponibilidad.*') ? 'active' : '' }}">
                                        <a class="{{ request()->routeIs('agenda.disponibilidad.*') ? 'active' : '' }}" href="{{ route('agenda.disponibilidad.index') }}">
                                            <i class="fa fa-calendar text-info"></i> Disponibilidad
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </li>
                    @endif
                    @if (auth()->user()->hasAnyPermission(['agendamiento.agendamiento.index', 'agendamiento.agendamiento.create', 'agendamiento.agendamiento.delete', 'agendamiento.agendamiento.update', 'agendamiento.agendamiento.interectuar', 'agendamiento.solicitudes.index', 'agendamiento.solicitudes.create', 'agendamiento.solicitudes.delete', 'agendamiento.solicitudes.update']))
                        <li class="nav-small-cap"><span>Agendamiento</span></li>
                        <li class="{{ request()->routeIs('agendamiento.*') ? 'active' : '' }}">
                            <a class="has-arrow waves-effect waves-dark {{ request()->routeIs('agendamiento.*') ? 'active' : '' }}" href="#" aria-expanded="{{ request()->routeIs('agendamiento.*') ? 'true' : 'false' }}">
                                <i class="icon-Address-Book"></i>
                                <span class="hide-menu">Agenda</span>
                            </a>
                            <ul aria-expanded="{{ request()->routeIs('agendamiento.*') ? 'true' : 'false' }}" class="collapse {{ request()->routeIs('agendamiento.*') ? 'in' : '' }}">
                                @can('agendamiento.agendamiento.index')
                                    <li class="{{ request()->routeIs('agendamiento.*') ? 'active' : '' }}">
                                        <a class="{{ request()->routeIs('agendamiento.*') ? 'active' : '' }}" href="{{ route('citas.index') }}">
                                            <i class="fa fa-calendar"></i> Asignacion
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </li>
                    @endif

                    @if (auth()->user()->hasAnyPermission(['reportes.indicadores', 'reportes.detallados']))
                        <li class="nav-small-cap"><span>Reportes</span></li>
                        <li class="{{ request()->routeIs('reportes.*') ? 'active' : '' }}">
                            <a href="{{ route('reportes.produccion') }}" class="waves-effect waves-dark {{ request()->routeIs('reportes.*') ? 'active' : '' }}" aria-expanded="false">
                                <i class="icon-Book"></i>
                                <span class="hide-menu">Producción</span>
                            </a>
                        </li>
                    @endif

                    @if (auth()->user()->hasAnyPermission(['helpdesk.tickets.index', 'helpdesk.tickets.create', 'helpdesk.tickets.show', 'helpdesk.reportes.index', 'helpdesk.notificaciones.index']))
                        <li class="nav-small-cap"><span>Soporte</span></li>
                        <li class="{{ request()->routeIs('helpdesk.*') ? 'active' : '' }}">
                            <a class="has-arrow waves-effect waves-dark {{ request()->routeIs('helpdesk.*') ? 'active' : '' }}" href="#" aria-expanded="{{ request()->routeIs('helpdesk.*') ? 'true' : 'false' }}">
                                <i class="icon-Support"></i>
                                <span class="hide-menu">Helpdesk</span>
                            </a>
                            <ul aria-expanded="{{ request()->routeIs('helpdesk.*') ? 'true' : 'false' }}" class="collapse {{ request()->routeIs('helpdesk.*') ? 'in' : '' }}">
                                @can('helpdesk.tickets.index')
                                    <li class="{{ request()->routeIs('helpdesk.tickets.*') ? 'active' : '' }}">
                                        <a class="{{ request()->routeIs('helpdesk.tickets.*') ? 'active' : '' }}" href="{{ route('helpdesk.tickets.listar') }}">
                                            <i class="fa fa-ticket text-info"></i> Mis tickets
                                        </a>
                                    </li>
                                @endcan
                                @can('helpdesk.tickets.create')
                                    <li class="{{ request()->routeIs('helpdesk.tickets.crear') ? 'active' : '' }}">
                                        <a class="{{ request()->routeIs('helpdesk.tickets.crear') ? 'active' : '' }}" href="{{ route('helpdesk.tickets.crear') }}">
                                            <i class="fa fa-plus text-info"></i> Crear ticket
                                        </a>
                                    </li>
                                @endcan
                                @can('helpdesk.reportes.index')
                                    <li class="{{ request()->routeIs('helpdesk.reportes') ? 'active' : '' }}">
                                        <a class="{{ request()->routeIs('helpdesk.reportes') ? 'active' : '' }}" href="{{ route('helpdesk.reportes') }}">
                                            <i class="fa fa-bar-chart text-info"></i> Reportes Helpdesk
                                        </a>
                                    </li>
                                @endcan
                                @can('helpdesk.notificaciones.index')
                                    <li class="{{ request()->routeIs('helpdesk.notificaciones') ? 'active' : '' }}">
                                        <a class="{{ request()->routeIs('helpdesk.notificaciones') ? 'active' : '' }}" href="{{ route('helpdesk.notificaciones') }}">
                                            <i class="fa fa-bell text-info"></i> Notificaciones
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </li>
                    @endif

                    @if (auth()->user()->hasAnyPermission(['contratos.index', 'contratos.create']))
                        <li class="nav-small-cap"><span>Contratación</span></li>
                        <li class="{{ request()->routeIs('contratos.*') ? 'active' : '' }}">
                            <a class="has-arrow waves-effect waves-dark {{ request()->routeIs('contratos.*') ? 'active' : '' }}" href="#" aria-expanded="{{ request()->routeIs('contratos.*') ? 'true' : 'false' }}">
                                <i class="icon-Contrast"></i>
                                <span class="hide-menu">Contratación</span>
                            </a>
                            <ul aria-expanded="{{ request()->routeIs('contratos.*') ? 'true' : 'false' }}" class="collapse {{ request()->routeIs('contratos.*') ? 'in' : '' }}">
                                @can('contratos.index')
                                    <li class="{{ request()->routeIs('contratos.*') ? 'active' : '' }}">
                                        <a class="{{ request()->routeIs('contratos.*') ? 'active' : '' }}" href="{{ route('contratos.listar') }}">
                                            <i class="fa fa-file-text-o text-info"></i> Contratos
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </li>
                    @endif

                    @if (auth()->user()->hasAnyPermission(['liquidacion.index']))
                        <li class="nav-small-cap"><span>Liquidación</span></li>
                        <li class="{{ request()->routeIs('liquidacion.*') || request()->routeIs('productividad.*') ? 'active' : '' }}">
                            <a class="has-arrow waves-effect waves-dark {{ request()->routeIs('liquidacion.*') || request()->routeIs('productividad.*') ? 'active' : '' }}" href="#" aria-expanded="{{ request()->routeIs('liquidacion.*') || request()->routeIs('productividad.*') ? 'true' : 'false' }}">
                                <i class="icon-Calculator"></i>
                                <span class="hide-menu">Liquidación</span>
                            </a>
                            <ul aria-expanded="{{ request()->routeIs('liquidacion.*') || request()->routeIs('productividad.*') ? 'true' : 'false' }}" class="collapse {{ request()->routeIs('liquidacion.*') || request()->routeIs('productividad.*') ? 'in' : '' }}">
                                @can('liquidacion.index')
                                    <li class="{{ request()->routeIs('liquidacion.*') ? 'active' : '' }}">
                                        <a class="{{ request()->routeIs('liquidacion.*') ? 'active' : '' }}" href="{{ route('liquidacion.ver') }}">
                                            <i class="fa fa-money text-info"></i> Liquidación
                                        </a>
                                    </li>
                                    <li class="{{ request()->routeIs('productividad.*') ? 'active' : '' }}">
                                        <a class="{{ request()->routeIs('productividad.*') ? 'active' : '' }}" href="{{ route('productividad.ver') }}">
                                            <i class="fa fa-bar-chart text-info"></i> Productividad
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </li>
                    @endif

                    @if (auth()->user()->hasAnyPermission(['config.admin.salud.index', 'config.diagnosticos.index', 'config.prioridades.index', 'config.productos.index', 'config.sedes.index', 'config.servicio.citas.index', 'config.plantillas.index', 'config.tipo.documento.index', 'config.consentimiento_informado.index', 'config.whatsapp.saludo.index', 'ecografias.consentimientos.index', 'ecografias.consentimientos.plantillas.index', 'helpdesk.config.categorias.index', 'helpdesk.config.prioridades.index', 'helpdesk.config.estados.index']))
                        <li class="nav-small-cap"><span>Parametrización</span></li>
                        <li class="{{ request()->routeIs('config.*') ? 'active' : '' }}">
                            <a class="has-arrow waves-effect waves-dark {{ request()->routeIs('config.*') ? 'active' : '' }}" href="#" aria-expanded="{{ request()->routeIs('config.*') ? 'true' : 'false' }}">
                                <i class="icon-Gears"></i>
                                <span class="hide-menu">Configuraciones</span>
                            </a>
                            <ul aria-expanded="{{ request()->routeIs('config.*') ? 'true' : 'false' }}" class="collapse {{ request()->routeIs('config.*') ? 'in' : '' }}">
                                @can('config.admin.salud.index')
                                    <li class="{{ request()->routeIs('config.adminsalud.*') ? 'active' : '' }}">
                                        <a class="{{ request()->routeIs('config.adminsalud.*') ? 'active' : '' }}" href="{{ route('config.adminsalud.listar') }}">Admin. Salud</a>
                                    </li>
                                @endcan
                                @can('config.diagnosticos.index')
                                    <li class="{{ request()->routeIs('config.diagnosticos.*') ? 'active' : '' }}">
                                        <a class="{{ request()->routeIs('config.diagnosticos.*') ? 'active' : '' }}" href="{{ route('config.diagnosticos.listar') }}">Diagnosticos</a>
                                    </li>
                                @endcan
                                @can('config.prioridades.index')
                                    <li class="{{ request()->routeIs('config.prioridades.*') ? 'active' : '' }}">
                                        <a class="{{ request()->routeIs('config.prioridades.*') ? 'active' : '' }}" href="{{ route('config.prioridades.listar') }}">Prioridades</a>
                                    </li>
                                @endcan
                                @can('config.productos.index')
                                    <li class="{{ request()->routeIs('config.productos.*') ? 'active' : '' }}">
                                        <a class="{{ request()->routeIs('config.productos.*') ? 'active' : '' }}" href="{{ route('config.productos.listar') }}">Productos</a>
                                    </li>
                                @endcan
                                @can('config.sedes.index')
                                    <li class="{{ request()->routeIs('config.sedes.*') ? 'active' : '' }}">
                                        <a class="{{ request()->routeIs('config.sedes.*') ? 'active' : '' }}" href="{{ route('config.sedes.listar') }}">Sedes</a>
                                    </li>
                                @endcan
                                @can('config.servicio.citas.index')
                                    <li class="{{ request()->routeIs('config.servicio.citas.*') ? 'active' : '' }}">
                                        <a class="{{ request()->routeIs('config.servicio.citas.*') ? 'active' : '' }}" href="{{ route('config.servicio.citas.listar') }}">Servicios de Citas</a>
                                    </li>
                                    <li class="{{ request()->routeIs('config.servicio.producto') ? 'active' : '' }}">
                                        <a class="{{ request()->routeIs('config.servicio.producto') ? 'active' : '' }}" href="{{ route('config.servicio.producto') }}">Productos por Servicio</a>
                                    </li>
                                @endcan
                                @can('config.plantillas.index')
                                    <li class="{{ request()->routeIs('config.tipos.plantillas.*') ? 'active' : '' }}">
                                        <a class="{{ request()->routeIs('config.tipos.plantillas.*') ? 'active' : '' }}" href="{{ route('config.tipos.plantillas.listar') }}">Tipos de Plantillas</a>
                                    </li>
                                    <li class="{{ request()->routeIs('config.plantillas.*') ? 'active' : '' }}">
                                        <a class="{{ request()->routeIs('config.plantillas.*') ? 'active' : '' }}" href="{{ route('config.plantillas.listar') }}">Plantillas</a>
                                    </li>
                                @endcan
                                @can('config.tipo.documento.index')
                                    <li class="{{ request()->routeIs('config.tipos.documentos.*') ? 'active' : '' }}">
                                        <a class="{{ request()->routeIs('config.tipos.documentos.*') ? 'active' : '' }}" href="{{ route('config.tipos.documentos.listar') }}">Tipos de Documentos</a>
                                    </li>
                                @endcan
                                @can('config.consentimiento_informado.index')
                                    <li class="{{ request()->routeIs('config.consentimiento.informado.*') ? 'active' : '' }}">
                                        <a class="{{ request()->routeIs('config.consentimiento.informado.*') ? 'active' : '' }}" href="{{ route('config.consentimiento.informado.listar') }}">Consentimiento Informado</a>
                                    </li>
                                @endcan
                                @can('ecografias.consentimientos.index')
                                    <li class="{{ request()->routeIs('config.consentimientos.index') ? 'active' : '' }}">
                                        <a class="{{ request()->routeIs('config.consentimientos.index') ? 'active' : '' }}" href="{{ route('config.consentimientos.index') }}">Consentimientos (Firmas/PDF)</a>
                                    </li>
                                @endcan
                                @can('ecografias.consentimientos.plantillas.index')
                                    <li class="{{ request()->routeIs('config.consentimientos.plantillas.*') ? 'active' : '' }}">
                                        <a class="{{ request()->routeIs('config.consentimientos.plantillas.*') ? 'active' : '' }}" href="{{ route('config.consentimientos.plantillas.index') }}">Plantillas Consentimientos</a>
                                    </li>
                                @endcan
                                @can('config.whatsapp.saludo.index')
                                    <li class="{{ request()->routeIs('config.whatsapp.*') ? 'active' : '' }}">
                                        <a class="{{ request()->routeIs('config.whatsapp.*') ? 'active' : '' }}" href="{{ route('config.whatsapp.listar') }}">
                                            <i class="fa fa-whatsapp text-info"></i> WhatsApp Bot
                                        </a>
                                    </li>
                                @endcan
                                @can('citas.index')
                                    <li class="{{ request()->routeIs('config.cita.rechazo.motivos') ? 'active' : '' }}">
                                        <a class="{{ request()->routeIs('config.cita.rechazo.motivos') ? 'active' : '' }}" href="{{ route('config.cita.rechazo.motivos') }}">
                                            <i class="fa fa-ban text-info"></i> Motivos de Rechazo
                                        </a>
                                    </li>
                                @endcan

                                @if (auth()->user()->hasAnyPermission(['helpdesk.config.categorias.index', 'helpdesk.config.prioridades.index', 'helpdesk.config.estados.index']))
                                    <li class="nav-small-cap"><span>Helpdesk Config</span></li>
                                    @can('helpdesk.config.categorias.index')
                                        <li class="{{ request()->routeIs('config.helpdesk.categorias.*') ? 'active' : '' }}">
                                            <a class="{{ request()->routeIs('config.helpdesk.categorias.*') ? 'active' : '' }}" href="{{ route('config.helpdesk.categorias.listar') }}">
                                                Categorías Helpdesk
                                            </a>
                                        </li>
                                    @endcan
                                    @can('helpdesk.config.prioridades.index')
                                        <li class="{{ request()->routeIs('config.helpdesk.prioridades.*') ? 'active' : '' }}">
                                            <a class="{{ request()->routeIs('config.helpdesk.prioridades.*') ? 'active' : '' }}" href="{{ route('config.helpdesk.prioridades.listar') }}">
                                                Prioridades Helpdesk
                                            </a>
                                        </li>
                                    @endcan
                                    @can('helpdesk.config.estados.index')
                                        <li class="{{ request()->routeIs('config.helpdesk.estados.*') ? 'active' : '' }}">
                                            <a class="{{ request()->routeIs('config.helpdesk.estados.*') ? 'active' : '' }}" href="{{ route('config.helpdesk.estados.listar') }}">
                                                Estados Helpdesk
                                            </a>
                                        </li>
                                    @endcan
                                @endif
                            </ul>
                        </li>
                    @endif
                    @if (auth()->user()->hasAnyPermission(['seguridad.usuarios.index', 'seguridad.roles.index', 'seguridad.auditoria.index', 'seguridad.backups.index']))
                        <li class="nav-small-cap"><span>Seguridad</span></li>
                        <li class="{{ request()->routeIs('seguridad.*') ? 'active' : '' }}">
                            <a class="has-arrow waves-effect waves-dark {{ request()->routeIs('seguridad.*') ? 'active' : '' }}" href="#" aria-expanded="{{ request()->routeIs('seguridad.*') ? 'true' : 'false' }}">
                                <i class="icon-Key-Lock"></i>
                                <span class="hide-menu">Seguridad</span>
                            </a>
                            <ul aria-expanded="{{ request()->routeIs('seguridad.*') ? 'true' : 'false' }}" class="collapse {{ request()->routeIs('seguridad.*') ? 'in' : '' }}">
                                @can('seguridad.usuarios.index')
                                    <li class="{{ request()->routeIs('seguridad.usuarios.*') ? 'active' : '' }}">
                                        <a class="{{ request()->routeIs('seguridad.usuarios.*') ? 'active' : '' }}" href="{{ route('seguridad.usuarios.listar') }}">
                                            <i class="fa fa-user-circle-o text-info"></i> Usuarios
                                        </a>
                                    </li>
                                @endcan
                                @can('seguridad.roles.index')
                                    <li class="{{ request()->routeIs('seguridad.roles.*') ? 'active' : '' }}">
                                        <a class="{{ request()->routeIs('seguridad.roles.*') ? 'active' : '' }}" href="{{ route('seguridad.roles.listar') }}">
                                            <i class="fa fa-key text-info"></i> Roles
                                        </a>
                                    </li>
                                @endcan
                                @can('seguridad.auditoria.index')
                                    <li class="{{ request()->routeIs('seguridad.auditoria') ? 'active' : '' }}">
                                        <a class="{{ request()->routeIs('seguridad.auditoria') ? 'active' : '' }}" href="{{ route('seguridad.auditoria') }}">
                                            <i class="fa fa-key text-info"></i> Auditoria
                                        </a>
                                    </li>
                                @endcan
                                @can('seguridad.backups.index')
                                    <li class="{{ request()->routeIs('seguridad.backups') ? 'active' : '' }}">
                                        <a class="{{ request()->routeIs('seguridad.backups') ? 'active' : '' }}" href="{{ route('seguridad.backups') }}">
                                            <i class="fa fa-database text-info"></i> Monitoreo Backups
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </li>
                    @endif
                </ul>
            </nav>
            <!-- End Sidebar navigation -->
        </div>
        <!-- End Sidebar scroll-->
    </aside>
@else
    <script>
        window.location.href = "{{ route('login') }}";
    </script>
@endif

@if (Auth::check())
    <aside class="left-sidebar">
        <!-- Sidebar scroll-->
        <div class="scroll-sidebar">
            <!-- Sidebar navigation-->
            <nav class="sidebar-nav">
                <ul id="sidebarnav">
                    <li class="nav-small-cap">--- PERSONAL</li>
                    <li>
                        <a class="has-arrow waves-effect waves-dark" href="{{ route('dashboard') }}" aria-expanded="false">
                            <i class="icon-Car-Wheel"></i>
                            <span class="hide-menu">Dashboard
                                <span class="label label-rounded label-danger">4</span>
                            </span>
                        </a>
                        {{-- <ul aria-expanded="false" class="collapse">
                        <li><a href="index.html">Minimal </a></li>
                        <li><a href="index2.html">Analytical</a></li>
                        <li><a href="index3.html">Demographical</a></li>
                        <li><a href="index4.html">Modern</a></li>
                    </ul> --}}
                    </li>
                    @if (auth()->user()->hasAnyPermission(['gestion.pacientes.index', 'gestion.pacientes.create', 'gestion.pacientes.delete', 'gestion.pacientes.update']))
                        <li class="nav-small-cap">--- PACIENTES</li>
                        <li>
                            <a class="has-arrow waves-effect waves-dark" href="#" aria-expanded="false">
                                <i class="icon-User"></i>
                                <span class="hide-menu">Pacientes</span>
                            </a>
                            <ul aria-expanded="false" class="collapse">

                                <li>
                                    <a href="">
                                        <i class="fa fa-user-plus text-info"></i> Admisionar Paciente
                                    </a>
                                </li>

                                <li>
                                    <a href="">
                                        <i class="fa fa-users text-info"></i> Listar Pacientes
                                    </a>
                                </li>

                            </ul>
                        </li>
                    @endif

                    @if (auth()->user()->hasAnyPermission(['estudio.asignar', 'estudio.transcribir', 'estudio.leerEstudio', 'estudios.notificar.index', 'estudios.reporteLectura.index']))
                        <li class="nav-small-cap">--- ESTUDIOS</li>
                        <li>
                            <a class="has-arrow waves-effect waves-dark" href="#" aria-expanded="false">
                                <i class="icon-Bookmark"></i>
                                <span class="hide-menu">Asignación de estudios</span>
                            </a>
                            <ul aria-expanded="false" class="collapse">
                                @can('estudio.asignar')
                                    <li>
                                        <a href="{{ route('estudios.asignar.listar') }}">
                                            <i class="fa fa-upload text-info"></i> Asignar Estudios
                                        </a>
                                        <a href="{{ route('estudios.asignados.listar') }}">
                                            <i class="fa fa-check text-info"></i> Estudios Asignados
                                        </a>
                                    </li>
                                @endcan
                                @can('estudio.transcribir')
                                    <li>
                                        <a href="{{ route('estudios.transcribir.listar') }}">
                                            <i class="fa fa-search text-info"></i> Pendientes por transcribir
                                        </a>
                                    </li>
                                @endcan
                                @canany('estudio.leerEstudio')
                                    <li>
                                        <a href="{{ route('estudios.leer.estudio.listar') }}">
                                            <i class="fa fa-search text-info"></i> Pendientes por leer
                                        </a>
                                    </li>
                                @endcanany
                                @canany('estudios.notificar.index')
                                    <li>
                                        <a href="{{ route('estudios.notificar.index') }}">
                                            <i class="fa fa-envelope-o text-info"></i> Notificar estudio
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </li>
                    @endif

                    @if (auth()->user()->hasAnyPermission(['tele.ragiologia.index', 'tele.ragiologia.enviar', 'tele.ragiologia.transcribir']))
                        <li class="nav-small-cap">--- Tele Radiología</li>
                        <li>
                            <a class="has-arrow waves-effect waves-dark" href="#" aria-expanded="false">
                                <i class="fa fa-cloud-upload"></i>
                                <span class="hide-menu">Tele Radiología</span>
                            </a>
                            <ul aria-expanded="false" class="collapse">
                                @can('tele.ragiologia.index')
                                    <li>
                                        <a href="{{ route('teleradiologia.index') }}">
                                            <i class="fa fa-upload text-info"></i> Enviar estudios
                                        </a>
                                    </li>
                                @endcan
                                @can('tele.ragiologia.transcribir')
                                    <li>
                                        <a href="{{ route('teleradiologia.transcribir') }}">
                                            <i class="fa fa-upload text-info"></i> Transcribir estudios
                                        </a>
                                    </li>
                                @endcan
                                {{-- @can('tele.ragiologia.index')
                                    <li>
                                        <a href="{{ route('tele.ragiologia.enviar') }}">
                                            <i class="fa fa-check text-info"></i> Estudios Enviados
                                        </a>
                                    </li>
                                @endcan --}}
                                {{-- @can('tele.ragiologia.transcribir')
                                    <li>
                                        <a href="{{ route('teleradiologia.listar.estudio.transcribir') }}">
                                            <i class="fa fa-search text-info"></i> Pendientes por transcribir
                                        </a>
                                    </li>
                                @endcan --}}
                            </ul>
                        </li>
                    @endif

                    @if (auth()->user()->hasAnyPermission(['ecografias.index', 'ecografias.admision', 'ecografias.anular', 'ecografias.leer', 'ecografias.reporte']))
                        <li class="nav-small-cap">--- ECOGRAFIAS</li>
                        <li>
                            <a class="has-arrow waves-effect waves-dark" href="#" aria-expanded="false">
                                <i class="fa fa-etsy"></i>
                                <span class="hide-menu">Asignación de estudios</span>
                            </a>
                            <ul aria-expanded="false" class="collapse">
                                <li>
                                    @can('ecografias.admision')
                                        <a href="{{ route('ecografias.admisiones') }}">
                                            <i class="fa fa-address-book-o  text-info"></i> Admisionar para procedimientos
                                        </a>
                                    @endcan
                                    @can('ecografias.index')
                                        <a href="{{ route('ecografias.adminisionadas') }}">
                                            <i class="fa fa-address-book-o  text-info"></i> Ecografías admisionadas
                                        </a>
                                    @endcan
                                    @can('ecografias.leer')
                                        <a href="{{ route('ecografias.pendientes.lecturas') }}">
                                            <i class="fa fa-pencil text-info"></i> Realizar lectura
                                        </a>
                                    @endcan
                                </li>
                            </ul>
                        </li>
                    @endif
                    @if (auth()->user()->hasAnyPermission(['citas.index']))
                        <li class="nav-small-cap">--- CITAS</li>
                        <li>
                            <a class="has-arrow waves-effect waves-dark" href="#" aria-expanded="false">
                                <i class="fa fa-calendar"></i>
                                <span class="hide-menu">Citas</span>
                            </a>
                            <ul aria-expanded="false" class="collapse">
                                @can('citas.index')
                                    <li>
                                        <a href="{{ route('citas.revision') }}">
                                            <i class="fa fa-inbox text-info"></i> Solicitudes Pendientes
                                            <citas-notification-badge-component></citas-notification-badge-component>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('citas.index') }}">
                                            <i class="fa fa-list text-info"></i> Listado de Citas
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('citas.gestion') }}">
                                            <i class="fa fa-dashboard text-info"></i> Tablero de Gestión
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('citas.recomendaciones') }}">
                                            <i class="fa fa-commenting text-info"></i> Recomendaciones
                                        </a>
                                    </li>
                                @endcan
                                @can('agendamiento.disponibilidad.index')
                                    <li>
                                        <a href="{{ route('agenda.disponibilidad.index') }}">
                                            <i class="fa fa-calendar text-info"></i> Disponibilidad
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </li>
                    @endif
                    @if (auth()->user()->hasAnyPermission(['agendamiento.agendamiento.index', 'agendamiento.agendamiento.create', 'agendamiento.agendamiento.delete', 'agendamiento.agendamiento.update', 'agendamiento.agendamiento.interectuar', 'agendamiento.solicitudes.index', 'agendamiento.solicitudes.create', 'agendamiento.solicitudes.delete', 'agendamiento.solicitudes.update']))
                        <li class="nav-small-cap">--- Agendamiento</li>
                        <li>
                            <a class="has-arrow waves-effect waves-dark" href="#" aria-expanded="false">
                                <i class="icon-Address-Book"></i>
                                <span class="hide-menu">Agenda</span>
                            </a>
                            <ul aria-expanded="false" class="collapse">
                                @can('agendamiento.agendamiento.index')
                                    <li>
                                        <a href="{{ route('citas.index') }}">
                                            <i class="fa fa-calendar"></i> Asignacion
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </li>
                    @endif

                    @if (auth()->user()->hasAnyPermission(['reportes.indicadores', 'reportes.detallados']))
                        <li class="nav-small-cap">--- REPORTES</li>
                        <li>
                            <a href="{{ route('reportes.produccion') }}" class="waves-effect waves-dark" aria-expanded="false">
                                <i class="icon-Book"></i>
                                <span class="hide-menu">Producción</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('reportes.estudios.pac') }}" class="waves-effect waves-dark" aria-expanded="false">
                                <i class="icon-Book"></i>
                                <span class="hide-menu">Estudios PAC</span>
                            </a>
                        </li>
                    @endif

                    @if (auth()->user()->hasAnyPermission(['helpdesk.tickets.index', 'helpdesk.tickets.create', 'helpdesk.tickets.show', 'helpdesk.reportes.index', 'helpdesk.notificaciones.index']))
                        <li class="nav-small-cap">--- SOPORTE</li>
                        <li>
                            <a class="has-arrow waves-effect waves-dark" href="#" aria-expanded="false">
                                <i class="icon-Support"></i>
                                <span class="hide-menu">Helpdesk</span>
                            </a>
                            <ul aria-expanded="false" class="collapse">
                                @can('helpdesk.tickets.index')
                                    <li>
                                        <a href="{{ route('helpdesk.tickets.listar') }}">
                                            <i class="fa fa-ticket text-info"></i> Mis tickets
                                        </a>
                                    </li>
                                @endcan
                                @can('helpdesk.tickets.create')
                                    <li>
                                        <a href="{{ route('helpdesk.tickets.crear') }}">
                                            <i class="fa fa-plus text-info"></i> Crear ticket
                                        </a>
                                    </li>
                                @endcan
                                @can('helpdesk.reportes.index')
                                    <li>
                                        <a href="{{ route('helpdesk.reportes') }}">
                                            <i class="fa fa-bar-chart text-info"></i> Reportes Helpdesk
                                        </a>
                                    </li>
                                @endcan
                                @can('helpdesk.notificaciones.index')
                                    <li>
                                        <a href="{{ route('helpdesk.notificaciones') }}">
                                            <i class="fa fa-bell text-info"></i> Notificaciones
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </li>
                    @endif

                    @if (auth()->user()->hasAnyPermission(['contratos.index', 'contratos.create']))
                        <li class="nav-small-cap">--- CONTRATACIÓN</li>
                        <li>
                            <a class="has-arrow waves-effect waves-dark" href="#" aria-expanded="false">
                                <i class="icon-Contract"></i>
                                <span class="hide-menu">Contratación</span>
                            </a>
                            <ul aria-expanded="false" class="collapse">
                                @can('contratos.index')
                                    <li>
                                        <a href="{{ route('contratos.listar') }}">
                                            <i class="fa fa-file-text-o text-info"></i> Contratos
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </li>
                    @endif

                    @if (auth()->user()->hasAnyPermission(['config.admin.salud.index', 'config.diagnosticos.index', 'config.prioridades.index', 'config.productos.index', 'config.sedes.index', 'config.servicio.citas.index', 'config.plantillas.index', 'config.tipo.documento.index', 'config.consentimiento_informado.index', 'config.whatsapp.saludo.index', 'ecografias.consentimientos.index', 'ecografias.consentimientos.plantillas.index', 'helpdesk.config.categorias.index', 'helpdesk.config.prioridades.index', 'helpdesk.config.estados.index']))
                        <li class="nav-small-cap">--- PARAMETRIZACIÓN</li>
                        <li>
                            <a class="has-arrow waves-effect waves-dark" href="#" aria-expanded="false">
                                <i class="icon-Gears"></i>
                                <span class="hide-menu">Configuraciones</span>
                            </a>
                            <ul aria-expanded="false" class="collapse">
                                @can('config.admin.salud.index')
                                    <li>
                                        <a href="{{ route('config.adminsalud.listar') }}">Admin. Salud</a>
                                    </li>
                                @endcan
                                @can('config.diagnosticos.index')
                                    <li>
                                        <a href="{{ route('config.diagnosticos.listar') }}">Diagnosticos</a>
                                    </li>
                                @endcan
                                @can('config.prioridades.index')
                                    <li>
                                        <a href="{{ route('config.prioridades.listar') }}">Prioridades</a>
                                    </li>
                                @endcan
                                @can('config.productos.index')
                                    <li>
                                        <a href="{{ route('config.productos.listar') }}">Productos</a>
                                    </li>
                                @endcan
                                @can('config.sedes.index')
                                    <li>
                                        <a href="{{ route('config.sedes.listar') }}">Sedes</a>
                                    </li>
                                @endcan
                                @can('config.servicio.citas.index')
                                    <li>
                                        <a href="{{ route('config.servicio.citas.listar') }}">Servicios de Citas</a>
                                    </li>
                                    <li>
                                        <a href="{{ route('config.servicio.producto') }}">Productos por Servicio</a>
                                    </li>
                                @endcan
                                @can('config.plantillas.index')
                                    <li>
                                        <a href="{{ route('config.tipos.plantillas.listar') }}">Tipos de Plantillas</a>
                                    </li>
                                    <li>
                                        <a href="{{ route('config.plantillas.listar') }}">Plantillas</a>
                                    </li>
                                @endcan
                                @can('config.tipo.documento.index')
                                    <li>
                                        <a href="{{ route('config.tipos.documentos.listar') }}">Tipos de Documentos</a>
                                    </li>
                                @endcan
                                @can('config.consentimiento_informado.index')
                                    <li>
                                        <a href="{{ route('config.consentimiento.informado.listar') }}">Consentimiento Informado</a>
                                    </li>
                                @endcan
                                @can('ecografias.consentimientos.index')
                                    <li>
                                        <a href="{{ route('config.consentimientos.index') }}">Consentimientos (Firmas/PDF)</a>
                                    </li>
                                @endcan
                                @can('ecografias.consentimientos.plantillas.index')
                                    <li>
                                        <a href="{{ route('config.consentimientos.plantillas.index') }}">Plantillas Consentimientos</a>
                                    </li>
                                @endcan
                                @can('config.whatsapp.saludo.index')
                                    <li>
                                        <a href="{{ route('config.whatsapp.listar') }}">
                                            <i class="fa fa-whatsapp text-info"></i> WhatsApp Bot
                                        </a>
                                    </li>
                                @endcan
                                @can('citas.index')
                                    <li>
                                        <a href="{{ route('config.cita.rechazo.motivos') }}">
                                            <i class="fa fa-ban text-info"></i> Motivos de Rechazo
                                        </a>
                                    </li>
                                @endcan

                                @if (auth()->user()->hasAnyPermission(['helpdesk.config.categorias.index', 'helpdesk.config.prioridades.index', 'helpdesk.config.estados.index']))
                                    <li class="nav-small-cap">--- Helpdesk</li>
                                    @can('helpdesk.config.categorias.index')
                                        <li>
                                            <a href="{{ route('config.helpdesk.categorias.listar') }}">
                                                Categorías Helpdesk
                                            </a>
                                        </li>
                                    @endcan
                                    @can('helpdesk.config.prioridades.index')
                                        <li>
                                            <a href="{{ route('config.helpdesk.prioridades.listar') }}">
                                                Prioridades Helpdesk
                                            </a>
                                        </li>
                                    @endcan
                                    @can('helpdesk.config.estados.index')
                                        <li>
                                            <a href="{{ route('config.helpdesk.estados.listar') }}">
                                                Estados Helpdesk
                                            </a>
                                        </li>
                                    @endcan
                                @endif
                            </ul>
                        </li>
                    @endif
                    @if (auth()->user()->hasAnyPermission(['seguridad.usuarios.index', 'seguridad.roles.index']))
                        <li class="nav-small-cap">--- Seguridad</li>
                        <li>
                            <a class="has-arrow waves-effect waves-dark" href="#" aria-expanded="false">
                                <i class="icon-Key-Lock"></i>
                                <span class="hide-menu">Seguridad</span>
                            </a>
                            <ul aria-expanded="false" class="collapse">
                                @can('seguridad.usuarios.index')
                                    <li>
                                        <a href="{{ route('seguridad.usuarios.listar') }}">
                                            <i class="fa fa-user-circle-o text-info"></i> Usuarios
                                        </a>
                                    </li>
                                @endcan
                                @can('seguridad.roles.index')
                                    <li>
                                        <a href="{{ route('seguridad.roles.listar') }}">
                                            <i class="fa fa-key text-info"></i> Roles
                                        </a>
                                    </li>
                                @endcan
                                @can('seguridad.auditoria.index')
                                    <li>
                                        <a href="{{ route('seguridad.auditoria') }}">
                                            <i class="fa fa-key text-info"></i> Auditoria
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

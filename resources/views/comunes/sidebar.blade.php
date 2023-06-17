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
                @if (auth()->user()->hasAnyPermission(['subir-documento.index', 'consultar-documento.index']))
                    <li class="nav-small-cap">--- ESTUDIOS</li>
                    <li>
                        <a class="has-arrow waves-effect waves-dark" href="#" aria-expanded="false">
                            <i class="icon-Bookmark"></i>
                            <span class="hide-menu">SGC</span>
                        </a>
                        <ul aria-expanded="false" class="collapse">
                            @can('subir-documento.index')
                                <li>
                                    <a href="{{ route('sgc.cargue.documento.listar') }}">
                                        <i class="fa fa-upload text-info"></i> Asignar Estudios
                                    </a>
                                </li>
                            @endcan
                            @can('consultar-documento.index')
                                <li>
                                    <a href="{{ route('sgc.consulta.documento.listar') }}">
                                        <i class="fa fa-search text-info"></i> Consulta de documentos
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endif

                @if (auth()->user()->hasAnyPermission(['config.admin.salud.index', 'config.diagnosticos.index', 'config.prioridades.index', 'config.productos.index', 'config.sedes.index']))
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
                        </ul>
                    </li>
                @endif
                @if (auth()->user()->hasAnyPermission(['seguridad.usuarios.inde', 'seguridad.roles.index']))
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
                        </ul>
                    </li>
                @endif
            </ul>
        </nav>
        <!-- End Sidebar navigation -->
    </div>
    <!-- End Sidebar scroll-->
</aside>

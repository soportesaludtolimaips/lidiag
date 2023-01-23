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
                        <li><a href="{{ route('estudios.asignar') }}">Asignar estudio</a></li>
                        <li><a href="{{ route('estudios.leer') }}">Mis estudios pendientes</a></li>
                    </ul>
                </li>

                <li class="nav-small-cap">--- CONFIGURACIÓN</li>
                <li>
                    <a class="has-arrow waves-effect waves-dark" href="#" aria-expanded="false">
                        <i class="fa fa-gears"></i>
                        <span class="hide-menu">Configuración</span>
                    </a>
                    <ul aria-expanded="false" class="collapse">
                        <li><a href="{{ route('config.admin.salud') }}">Admin. De Salud</a></li>
                        <li><a href="{{ route('config.diagnosticos') }}">Díagnosticos</a></li>
                        <li><a href="{{ route('config.productos') }}">Productos</a></li>
                        <li><a href="{{ route('config.sucursales') }}">Sedes</a></li>
                    </ul>
                </li>
            </ul>
        </nav>
        <!-- End Sidebar navigation -->
    </div>
    <!-- End Sidebar scroll-->
</aside>

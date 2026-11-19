<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Activar cuenta de usuario en LIDiag">
    <meta name="author" content="LIDiag">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('admin-wrap-login/assets/images/favicon.png') }}">
    <title>{{ config('app.name', 'LIDiag') }} - Activar Cuenta</title>
    <link href="{{ asset('admin-wrap-login/assets/node_modules/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('admin-wrap-login/css/pages/login-register-lock.css') }}" rel="stylesheet">
    <link href="{{ asset('admin-wrap-login/css/style.css') }}" rel="stylesheet">
    <link href="{{ asset('admin-wrap-login/css/colors/default.css') }}" id="theme" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
</head>

<body>
    <!-- Preloader -->
    <div class="preloader">
        <div class="loader">
            <div class="loader__figure"></div>
            <p class="loader__label">Lidiag</p>
        </div>
    </div>

    <!-- Main wrapper -->
    <section id="wrapper" class="login-register login-sidebar" style="background-image:url({{ asset('admin-wrap-login/assets/images/background/login-register.jpg') }});
        -moz-background-size: 100% 100%;
        -o-background-size: 100% 100%;
        -webkit-background-size: 100% 100%;
        background-size: 100% 100%;
        float: left;">

        <div class="login-box card">
            <div class="card-body">
                <form class="form-horizontal form-material" method="POST" action="{{ route('usuario.activar.post') }}">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">

                    <a href="javascript:void(0)" class="text-center db">
                        <img src="{{ asset('admin-wrap-login/assets/images/logo-icon.png') }}" alt="Home" /><br />
                        <img src="{{ asset('admin-wrap-login/assets/images/logo-text.png') }}" alt="Home" />
                    </a>

                    <h3 class="box-title m-t-30 text-center font-weight-bold">Activar Cuenta</h3>
                    <p class="text-center text-muted m-b-20">Establece tu contraseña definitiva para completar la activación de tu cuenta.</p>

                    @if (session('success'))
                        <div class="alert alert-success">
                            <i class="fa fa-check-circle mr-1"></i> {{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger">
                            <i class="fa fa-exclamation-triangle mr-1"></i> {{ session('error') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-warning">
                            <i class="fa fa-ban mr-1"></i> <strong>Por favor revisa los errores:</strong>
                            <ul class="m-b-0 p-l-20">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="form-group m-t-20">
                        <div class="col-xs-12">
                            <label class="font-weight-bold" for="email">Correo Electrónico</label>
                            <input
                                class="form-control"
                                type="email"
                                name="email"
                                id="email"
                                value="{{ $email ?? old('email') }}"
                                required
                                {{ $email ? 'readonly' : '' }}
                                placeholder="tu@correo.com"
                            />
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-xs-12">
                            <label class="font-weight-bold" for="password">Nueva Contraseña</label>
                            <input
                                class="form-control"
                                type="password"
                                name="password"
                                id="password"
                                required
                                placeholder="Mínimo 8 caracteres (mayús, minús, núm, símbolo)"
                            />
                            <small class="text-muted d-block m-t-5">
                                <i class="fa fa-shield text-info"></i> Mínimo 8 caracteres, al menos una mayúscula, minúscula, número y símbolo.
                            </small>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-xs-12">
                            <label class="font-weight-bold" for="password_confirmation">Confirmar Contraseña</label>
                            <input
                                class="form-control"
                                type="password"
                                name="password_confirmation"
                                id="password_confirmation"
                                required
                                placeholder="Repite tu nueva contraseña"
                            />
                        </div>
                    </div>

                    <div class="form-group text-center m-t-30">
                        <div class="col-xs-12">
                            <button class="btn btn-info btn-lg btn-block text-uppercase waves-effect waves-light" type="submit">
                                <i class="fa fa-check mr-1"></i> Activar y Continuar
                            </button>
                        </div>
                    </div>

                    <div class="form-group m-b-0">
                        <div class="col-sm-12 text-center">
                            <p class="text-muted">¿Ya tienes tu cuenta activa? <a href="{{ route('login') }}" class="text-info m-l-5"><b>Iniciar Sesión</b></a></p>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- Scripts -->
    <script src="{{ asset('admin-wrap-login/assets/node_modules/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('admin-wrap-login/assets/node_modules/bootstrap/js/popper.min.js') }}"></script>
    <script src="{{ asset('admin-wrap-login/assets/node_modules/bootstrap/js/bootstrap.min.js') }}"></script>
    <script type="text/javascript">
        $(function() {
            $(".preloader").fadeOut();
        });
    </script>
</body>

</html>

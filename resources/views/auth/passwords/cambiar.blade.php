<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('admin-wrap-login/assets/images/favicon.png') }}">
    <title>{{ config('app.name', 'LIDiag') }} - Cambiar contraseña</title>
    <link href="{{ asset('admin-wrap-login/assets/node_modules/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('admin-wrap-login/css/pages/login-register-lock.css') }}" rel="stylesheet">
    <link href="{{ asset('admin-wrap-login/css/style.css') }}" rel="stylesheet">
    <link href="{{ asset('admin-wrap-login/css/colors/default.css') }}" id="theme" rel="stylesheet">
</head>

<body>
    <div class="preloader">
        <div class="loader">
            <div class="loader__figure"></div>
            <p class="loader__label">Lidiag</p>
        </div>
    </div>

    <section id="wrapper" class="login-register login-sidebar" style="background-image:url({{ asset('admin-wrap-login/assets/images/background/login-register.jpg') }});
        -moz-background-size: 100% 100%;
        -o-background-size: 100% 100%;
        -webkit-background-size: 100% 100%;
        background-size: 100% 100%;
        float: left;">

        <div class="login-box card">
            <div class="card-body">
                <form class="form-horizontal form-material" method="POST" action="{{ route('password.cambiar') }}">
                    @csrf

                    <a href="javascript:void(0)" class="text-center db">
                        <img src="{{ asset('admin-wrap-login/assets/images/logo-icon.png') }}" alt="Home" /><br />
                        <img src="{{ asset('admin-wrap-login/assets/images/logo-text.png') }}" alt="Home" />
                    </a>

                    <h3 class="box-title m-t-40 text-center">Cambiar contraseña</h3>
                    <p class="text-center">Debe cambiar su contraseña para continuar.</p>

                    @if (session('success'))
                        <div class="alert alert-success">
                            <i class="fa fa-check"></i> {{ session('success') }}
                        </div>
                    @endif

                    @foreach ($errors->all() as $error)
                        <div class="alert alert-warning">
                            <i class="fa fa-ban"></i> {{ $error }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endforeach

                    <div class="form-group m-t-40">
                        <div class="col-xs-12">
                            <input id="current_password" type="password" class="form-control" name="current_password" required placeholder="Contraseña actual">
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-xs-12">
                            <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required minlength="8" placeholder="Nueva contraseña">
                            @error('password')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-xs-12">
                            <input id="password_confirmation" type="password" class="form-control" name="password_confirmation" required minlength="8" placeholder="Confirmar nueva contraseña">
                        </div>
                    </div>
                    <div class="form-group text-center m-t-20">
                        <div class="col-xs-12">
                            <button class="btn btn-info btn-lg btn-block text-uppercase btn-rounded" type="submit">
                                Cambiar contraseña
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>

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

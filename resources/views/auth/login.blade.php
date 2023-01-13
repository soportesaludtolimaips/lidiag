<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!-- Tell the browser to be responsive to screen width -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <!-- Favicon icon -->
    <link rel="icon" type="image/png" sizes="16x16"
        href="{{ asset('admin-wrap-login/assets/images/favicon.png') }}">
    <title>{{ config('app.name', 'LIDiag - Reportes') }}</title>
    <!-- Bootstrap Core CSS -->
    <link href="{{ asset('admin-wrap-login/assets/node_modules/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <!-- page css -->
    <link href="{{ asset('admin-wrap-login/css/pages/login-register-lock.css') }}" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="{{ asset('admin-wrap-login/css/style.css') }}" rel="stylesheet">

    <!-- You can change the theme colors from here -->
    <link href="{{ asset('admin-wrap-login/css/colors/default.css') }}" id="theme" rel="stylesheet">
    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
    <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
<![endif]-->
</head>

<body>
    <!-- ============================================================== -->
    <!-- Preloader - style you can find in spinners.css -->
    <!-- ============================================================== -->
    <div class="preloader">
        <div class="loader">
            <div class="loader__figure"></div>
            <p class="loader__label">Lidiag - Reportes</p>
        </div>
    </div>
    <!-- ============================================================== -->
    <!-- Main wrapper - style you can find in pages.scss -->
    <!-- ============================================================== -->
    <section id="wrapper" class="login-register login-sidebar"
        style="background-image:url(admin-wrap-login/assets/images/background/login-register.jpg);
        -moz-background-size: 100% 100%;
        -o-background-size: 100% 100%;
        -webkit-background-size: 100% 100%;
        background-size: 100% 100%;
        -moz-border-image: url('img/fondo.jpg') 0;
        float: left;">

        <div class="login-box card">
            <div class="card-body">
                <form class="form-horizontal form-material" method="POST" action="{{ route('login') }}">
                    @csrf
                    <a href="javascript:void(0)" class="text-center db">
                        <img src="{{ asset('admin-wrap-login/assets/images/logo-icon.png') }}" alt="Home" /><br />
                        <img src="{{ asset('admin-wrap-login/assets/images/logo-text.png') }}" alt="Home" />
                    </a>
                    <div class="form-group m-t-40">
                        <div class="col-xs-12">
                            <input id="email" type="text"
                                class="form-control @error('email') is-invalid @enderror" name="email"
                                value="{{ old('email', 'administrador@administrador.com') }}" required
                                autocomplete="email" autofocus placeholder="Email">
                            @error('email')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>
                    <div class="form-group m-t-40">
                        <div class="col-xs-12">
                            <input id="password" type="password"
                                class="form-control @error('password') is-invalid @enderror" name="password" required
                                autocomplete="current-password" placeholder="Contraseña" value="123456">
                            @error('password')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-md-12">
                            <div class="checkbox checkbox-primary pull-left p-t-0">
                                <input id="checkbox-signup" type="checkbox" class="filled-in chk-col-light-blue">
                                <label for="checkbox-signup"> Recuerdame </label>
                            </div>
                            <a href="javascript:void(0)" id="to-recover" class="text-dark pull-right"><i
                                    class="fa fa-lock m-r-5"></i> Olvidé mi contraseña?</a>
                        </div>
                    </div>
                    <div class="form-group text-center m-t-20">
                        <div class="col-xs-12">
                            <button class="btn btn-info btn-lg btn-block text-uppercase btn-rounded" type="submit">
                                Iniciar sesión
                            </button>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </section>
    <!-- ============================================================== -->
    <!-- End Wrapper -->
    <!-- ============================================================== -->
    <!-- ============================================================== -->
    <!-- All Jquery -->
    <!-- ============================================================== -->
    <script src="{{ asset('admin-wrap-login/assets/node_modules/jquery/jquery.min.js') }}"></script>
    <!-- Bootstrap tether Core JavaScript -->
    <script src="{{ asset('admin-wrap-login/assets/node_modules/bootstrap/js/popper.min.js') }}"></script>
    <script src="{{ asset('admin-wrap-login/assets/node_modules/bootstrap/js/bootstrap.min.js') }}"></script>
    <!--Custom JavaScript -->
    <script type="text/javascript">
        $(function() {
            $(".preloader").fadeOut();
        });
        $(function() {
            $('[data-toggle="tooltip"]').tooltip()
        });
        // ==============================================================
        // Login and Recover Password
        // ==============================================================
        $('#to-recover').on("click", function() {
            $("#loginform").slideUp();
            $("#recoverform").fadeIn();
        });
    </script>

</body>

</html>

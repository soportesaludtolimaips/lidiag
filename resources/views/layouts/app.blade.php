<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!-- Tell the browser to be responsive to screen width -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <!-- Favicon icon -->
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('admin-wrap/assets/images/favicon.png') }}">
    <title>{{ config('appName') }}</title>
    <!-- Bootstrap Core CSS -->
    <link href="{{ asset('admin-wrap/assets/node_modules/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('admin-wrap/assets/node_modules/html5-editor/bootstrap-wysihtml5.css') }}" rel="stylesheet" />
    <link href="{{ asset('admin-wrap/assets/node_modules/bootstrap-material-datetimepicker/css/bootstrap-material-datetimepicker.css') }}" rel="stylesheet">
    <!-- Page plugins css -->
    <link href="{{ asset('admin-wrap/assets/node_modules/clockpicker/dist/jquery-clockpicker.min.css') }}" rel="stylesheet">
    <!--alerts CSS -->
    <link href="{{ asset('admin-wrap/assets/node_modules/bootstrap-datepicker/bootstrap-datepicker.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('admin-wrap/assets/node_modules/select2/dist/css/select2.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('admin-wrap/assets/node_modules/switchery/dist/switchery.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('admin-wrap/assets/node_modules/bootstrap-select/bootstrap-select.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('admin-wrap/assets/node_modules/bootstrap-tagsinput/dist/bootstrap-tagsinput.css') }}" rel="stylesheet" />
    <link href="{{ asset('admin-wrap/assets/node_modules/bootstrap-touchspin/dist/jquery.bootstrap-touchspin.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('admin-wrap/assets/node_modules/multiselect/css/multi-select.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('admin-wrap/assets/node_modules/sweetalert/sweetalert.css') }}" rel="stylesheet" type="text/css">
    <!-- toast CSS -->
    <link href="{{ asset('admin-wrap/assets/node_modules/toast-master/css/jquery.toast.css') }}" rel="stylesheet">
    <!-- chartist CSS -->
    <link href="{{ asset('admin-wrap/assets/node_modules/morrisjs/morris.css') }}" rel="stylesheet">
    <!--c3 CSS -->
    <link href="{{ asset('admin-wrap/assets/node_modules/c3-master/c3.min.css') }}" rel="stylesheet">
    <!-- Color picker plugins css -->
    <link href="{{ asset('admin-wrap/assets/node_modules/jquery-asColorPicker-master/css/asColorPicker.css') }}" rel="stylesheet">
    <!-- Date picker plugins css -->
    <link href="{{ asset('admin-wrap/assets/node_modules/bootstrap-datepicker/bootstrap-datepicker.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- Calendar CSS -->
    {{-- <link href="{{ asset('admin-wrap/assets/node_modules/calendar/dist/fullcalendar.css') }}" rel="stylesheet" /> --}}
    <!-- Daterange picker plugins css -->
    <link href="{{ asset('admin-wrap/assets/node_modules/timepicker/bootstrap-timepicker.min.css') }}" rel="stylesheet">
    <link href="{{ asset('admin-wrap/assets/node_modules/bootstrap-daterangepicker/daterangepicker.css') }}" rel="stylesheet">
    <link href="{{ asset('admin-wrap/assets/node_modules/calendar/dist/fullcalendar.css') }}" rel="stylesheet" />

    <!-- page css -->
    <link href="{{ asset('admin-wrap/css/pages/form-icheck.css') }}" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="{{ asset('admin-wrap/css/style.css') }}" rel="stylesheet">
    <!-- You can change the theme colors from here -->
    <link href="{{ asset('admin-wrap/css/colors/default.css') }}" id="theme" rel="stylesheet">
    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
    <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
<![endif]-->



    <script src="https://cdn.jsdelivr.net/npm/howler"></script>
    <script src="https://cdn.jsdelivr.net/npm/animejs"></script>
    <script>
        (function() {
            try {
                if (localStorage.getItem("lidiag_dark_mode") === "true") {
                    document.documentElement.classList.add("theme-dark");
                }
            } catch (e) {}
        })();
    </script>
</head>

<body class="fix-header card-no-border fix-sidebar">
    <script>
        (function() {
            try {
                if (localStorage.getItem("lidiag_dark_mode") === "true") {
                    document.body.classList.add("theme-dark");
                }
            } catch (e) {}
        })();
    </script>

    <!-- ============================================================== -->
    <!-- Preloader - style you can find in spinners.css -->
    <!-- ============================================================== -->
    <div class="preloader">
        <div class="loader">
            <div class="loader__figure"></div>
            <p class="loader__label">{{ config('app.name') }}</p>
        </div>
    </div>
    <!-- ============================================================== -->
    <!-- Main wrapper - style you can find in pages.scss -->
    <!-- ============================================================== -->

    <div id="app">


        <!-- ============================================================== -->
        <!-- Topbar header - style you can find in pages.scss -->
        <!-- ============================================================== -->
        <header class="topbar">
            <nav class="navbar top-navbar navbar-expand-md navbar-light">
                <!-- ============================================================== -->
                <!-- Logo -->
                <!-- ============================================================== -->
                @include('comunes.logo')
                <!-- ============================================================== -->
                <!-- End Logo -->
                <!-- ============================================================== -->
                <div class="navbar-collapse">
                    <!-- ============================================================== -->
                    <!-- toggle and nav items -->
                    <!-- ============================================================== -->
                    @include('comunes.toggleAndNavItems')
                    <!-- ============================================================== -->
                    <!-- User profile and search -->
                    <!-- ============================================================== -->
                    {{-- @include('comunes.userProfileAndSearch') --}}
                    <user-profile-and-search-component
                        :usuarioactual='@json(Auth::user())'
                        perfil-url="{{ route('seguridad.perfil') }}"
                    />
                </div>
            </nav>
        </header>
        <!-- ============================================================== -->
        <!-- End Topbar header -->
        <!-- ============================================================== -->
        <!-- ============================================================== -->
        <!-- Left Sidebar - style you can find in sidebar.scss  -->
        <!-- ============================================================== -->
        @include('comunes.sidebar')
        <!-- ============================================================== -->
        <!-- End Left Sidebar - style you can find in sidebar.scss  -->
        <!-- ============================================================== -->
        <!-- ============================================================== -->
        <!-- Page wrapper  -->
        <!-- ============================================================== -->
        <div class="page-wrapper">

            <!-- ============================================================== -->
            <!-- Container fluid  -->
            <!-- ============================================================== -->
            <div class="container-fluid">
                @yield('content')
            </div>

            <!-- ============================================================== -->
            <!-- End Container fluid  -->
            <!-- ============================================================== -->

            <!-- ============================================================== -->
            <!-- footer -->
            <!-- ============================================================== -->
            {{-- @include('comunes.footer') --}}
            <footer-component />
            <!-- ============================================================== -->
            <!-- End footer -->
            <!-- ============================================================== -->
        </div>
        <!-- ============================================================== -->
        <!-- End Page wrapper  -->
        <!-- ============================================================== -->
    </div>
    <!-- ============================================================== -->
    <!-- End Wrapper -->
    <!-- ============================================================== -->
    <!-- ============================================================== -->
    <!-- All Jquery -->
    <!-- ============================================================== -->
    <script src="{{ asset('admin-wrap/assets/node_modules/jquery/jquery.min.js') }}"></script>
    <!-- Bootstrap tether Core JavaScript -->
    <script src="{{ asset('admin-wrap/assets/node_modules/bootstrap/js/popper.min.js') }}"></script>
    <script src="{{ asset('admin-wrap/assets/node_modules/bootstrap/js/bootstrap.min.js') }}"></script>
    <!-- slimscrollbar scrollbar JavaScript -->
    <script src="{{ asset('admin-wrap/assets/node_modules/ps/perfect-scrollbar.jquery.min.js') }}"></script>
    <!--Wave Effects -->
    <script src="{{ asset('admin-wrap/js/waves.js') }}"></script>
    <!--Menu sidebar -->
    <script src="{{ asset('admin-wrap/js/sidebarmenu.js') }}"></script>
    <!--stickey kit -->
    <script src="{{ asset('admin-wrap/assets/node_modules/sticky-kit-master/dist/sticky-kit.min.js') }}"></script>
    <script src="{{ asset('admin-wrap/assets/node_modules/sparkline/jquery.sparkline.min.js') }}"></script>
    <!--Custom JavaScript -->
    <script src="{{ asset('admin-wrap/js/custom.min.js') }}"></script>
    <script src="{{ asset('admin-wrap/assets/node_modules/toast-master/js/jquery.toast.js') }}"></script>
    <script src="{{ asset('admin-wrap/js/toastr.js') }}"></script>

    <!-- wysuhtml5 Plugin JavaScript -->
    <script src="{{ asset('admin-wrap/assets/node_modules/html5-editor/wysihtml5-0.3.0.js') }}"></script>
    <script src="{{ asset('admin-wrap/assets/node_modules/html5-editor/bootstrap-wysihtml5.js') }}"></script>

    <!--morris JavaScript -->
    <script src="{{ asset('admin-wrap/assets/node_modules/raphael/raphael-min.js') }}"></script>
    <script src="{{ asset('admin-wrap/assets/node_modules/morrisjs/morris.min.js') }}"></script>
    <!--c3 JavaScript -->
    <script src="{{ asset('admin-wrap/assets/node_modules/d3/d3.min.js') }}"></script>
    <script src="{{ asset('admin-wrap/assets/node_modules/c3-master/c3.min.js') }}"></script>
    <!-- Popup message jquery -->
    <script src="{{ asset('admin-wrap/assets/node_modules/toast-master/js/jquery.toast.js') }}"></script>

    <!-- This is data table -->
    <script src="{{ asset('admin-wrap/assets/node_modules/datatables/jquery.dataTables.min.js') }}"></script>
    <!-- start - This is for export functionality only -->
    <script src="https://cdn.datatables.net/buttons/1.2.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.2.2/js/buttons.flash.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/2.5.0/jszip.min.js"></script>
    <script src="https://cdn.rawgit.com/bpampuch/pdfmake/0.1.18/build/pdfmake.min.js"></script>
    <script src="https://cdn.rawgit.com/bpampuch/pdfmake/0.1.18/build/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.2.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.2.2/js/buttons.print.min.js"></script>
    <!-- end - This is for export functionality only -->
    <!-- Custom Theme JavaScript -->

    <!-- Calendar JavaScript -->
    {{-- <script src="{{ asset('admin-wrap/assets/node_modules/calendar/jquery-ui.min.js') }}"></script>
    <script src="{{ asset('admin-wrap/assets/node_modules/moment/moment.js') }}"></script>
    <script src='{{ asset('admin-wrap/assets/node_modules/calendar/dist/fullcalendar.min.js') }}'></script>
    <script src="{{ asset('admin-wrap/assets/node_modules/calendar/dist/cal-init.js') }}"></script> --}}

    <!-- Sweet-Alert  -->
    <script src="{{ asset('admin-wrap/assets/node_modules/sweetalert/sweetalert.min.js') }}"></script>
    <script src="{{ asset('admin-wrap/assets/node_modules/sweetalert/jquery.sweet-alert.custom.js') }}"></script>
    <!-- icheck -->
    <script src="{{ asset('admin-wrap/assets/node_modules/icheck/icheck.min.js') }}"></script>
    <script src="{{ asset('admin-wrap/assets/node_modules/icheck/icheck.init.js') }}"></script>
    @vite(['resources/js/app.js'])
    <script src="{{ asset('admin-wrap/assets/node_modules/switchery/dist/switchery.min.js') }}"></script>
    <script src="{{ asset('admin-wrap/assets/node_modules/select2/dist/js/select2.full.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('admin-wrap/assets/node_modules/bootstrap-select/bootstrap-select.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('admin-wrap/assets/node_modules/bootstrap-tagsinput/dist/bootstrap-tagsinput.min.js') }}"></script>
    <script src="{{ asset('admin-wrap/assets/node_modules/bootstrap-touchspin/dist/jquery.bootstrap-touchspin.js') }}" type="text/javascript"></script>
    <script type="text/javascript" src="{{ asset('admin-wrap/assets/node_modules/multiselect/js/jquery.multi-select.js') }}"></script>
    <script>
        jQuery(function() {
            // Switchery
            var elems = Array.prototype.slice.call(document.querySelectorAll('.js-switch'));
            $('.js-switch').each(function() {
                new Switchery($(this)[0], $(this).data());
            });

            $('.selectpicker').selectpicker();
            //Bootstrap-TouchSpin
            $(".vertical-spin").TouchSpin({
                verticalbuttons: true,
                verticalupclass: 'ti-plus',
                verticaldownclass: 'ti-minus'
            });
            var vspinTrue = $(".vertical-spin").TouchSpin({
                verticalbuttons: true
            });
            if (vspinTrue) {
                $('.vertical-spin').prev('.bootstrap-touchspin-prefix').remove();
            }
            $("input[name='tch1']").TouchSpin({
                min: 0,
                max: 100,
                step: 0.1,
                decimals: 2,
                boostat: 5,
                maxboostedstep: 10,
                postfix: '%'
            });
            $("input[name='tch2']").TouchSpin({
                min: -1000000000,
                max: 1000000000,
                stepinterval: 50,
                maxboostedstep: 10000000,
                prefix: '$'
            });
            $("input[name='tch3']").TouchSpin();
            $("input[name='tch3_22']").TouchSpin({
                initval: 40
            });
            $("input[name='tch5']").TouchSpin({
                prefix: "pre",
                postfix: "post"
            });
            // For multiselect
            $('#pre-selected-options').multiSelect();
            $('#optgroup').multiSelect({
                selectableOptgroup: true
            });
            $('#public-methods').multiSelect();
            $('#select-all').on('click', function() {
                $('#public-methods').multiSelect('select_all');
                return false;
            });
            $('#deselect-all').on('click', function() {
                $('#public-methods').multiSelect('deselect_all');
                return false;
            });
            $('#refresh').on('click', function() {
                $('#public-methods').multiSelect('refresh');
                return false;
            });
            $('#add-option').on('click', function() {
                $('#public-methods').multiSelect('addOption', {
                    value: 42,
                    text: 'test 42',
                    index: 0
                });
                return false;
            });

        });
    </script>

    <!-- ============================================================== -->
    <!-- Style switcher -->
    <!-- ============================================================== -->
    <script src="{{ asset('admin-wrap/assets/node_modules/styleswitcher/jQuery.style.switcher.js') }}"></script>


</body>

</html>

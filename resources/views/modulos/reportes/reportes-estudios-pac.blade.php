@extends('layouts.app')

@section('content')
    @php
        $user = auth()->user()->load('roles', 'permissions');
        $permisos = auth()->user()->getAllPermissions()->pluck('name');
    @endphp
    <reportes-estudios-p-a-c-component :usuarioactual='@json($user)' :permisos='@json($permisos)' />
@endsection

@section('javascript')
    <!-- ============================================================== -->
    <!-- Plugins for this page -->
    <!-- ============================================================== -->
    <script src="{{ asset('admin-wrap/assets/node_modules/moment/moment.js') }}"></script>
    <script src="{{ asset('admin-wrap/assets/node_modules/bootstrap-material-datetimepicker/js/bootstrap-material-datetimepicker.js') }}"></script>
    <script src="{{ asset('admin-wrap/assets/node_modules/bootstrap-datepicker/bootstrap-datepicker.min.js') }}"></script>
    <script src="{{ asset('admin-wrap/assets/node_modules/bootstrap-daterangepicker/daterangepicker.js') }}"></script>
    <script>
        jQuery('.mydatepicker, #datepicker').datepicker();
        jQuery('#datepicker-autoclose').datepicker({
            autoclose: true,
            todayHighlight: true
        });
        jQuery('#date-range').datepicker({
            toggleActive: true
        });
        jQuery('#datepicker-inline').datepicker({
            todayHighlight: true
        });
        $('.input-daterange-datepicker').daterangepicker({
            buttonClasses: ['btn', 'btn-sm'],
            applyClass: 'btn-danger',
            cancelClass: 'btn-inverse'
        });
    </script>
@endsection

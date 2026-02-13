<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>{{ config('app.name') }}</title>

    <link href="{{ asset('admin-wrap/assets/node_modules/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('admin-wrap/css/style.css') }}" rel="stylesheet">
    <link href="{{ asset('admin-wrap/css/colors/default.css') }}" id="theme" rel="stylesheet">
</head>

<body class="fix-header card-no-border">
    <div id="app">
        @yield('content')
    </div>

    <script src="{{ asset('admin-wrap/assets/node_modules/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('admin-wrap/assets/node_modules/bootstrap/js/popper.min.js') }}"></script>
    <script src="{{ asset('admin-wrap/assets/node_modules/bootstrap/js/bootstrap.min.js') }}"></script>
    @vite(['resources/js/app.js'])
</body>

</html>


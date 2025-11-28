@extends('layouts.app')

@section('content')
    @php
        $user = auth()->user()->load('roles', 'permissions');
        $permisos = auth()->user()->getAllPermissions()->pluck('name');
    @endphp

    <ecografias-admisionadas-component :usuarioactual="{{ Auth::user() }}" :permisos="{{ $permisos }}" />
@endsection

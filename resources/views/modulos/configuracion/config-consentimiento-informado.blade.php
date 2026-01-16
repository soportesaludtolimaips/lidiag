@extends('layouts.app')

@section('content')
    @php
        $user = auth()->user()->load('roles', 'permissions');
        $permisos = auth()->user()->getAllPermissions()->pluck('name');
    @endphp

    
    <config-ConsentimientoIn-formadoComponent :usuarioactual="{{ Auth::user() }}" :permisos="{{ $permisos }}"/>
@endsection

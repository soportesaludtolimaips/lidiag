@extends('layouts.app')

@section('content')
    @php
        $user = auth()->user()->load('roles', 'permissions');
        $permisos = auth()->user()->getAllPermissions()->pluck('name');
    @endphp

    <oftalmologia-admisionadas-component :usuarioactual='@json(Auth::user())' :permisos='@json($permisos)' />
@endsection

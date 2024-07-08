@extends('layouts.app')

@section('content')
    {{ Auth::user()->getAllPermissions()->pluck('name') }}
    <notificar-estudio-component :usuarioactual="{{ Auth::user() }}" :permisos="{{ Auth::user()->getAllPermissions()->pluck('name') }}" />
@endsection

@extends('layouts.app')

@section('content')
    <notificar-estudio-component :usuarioactual="{{ Auth::user() }}" :permisos="{{ Auth::user()->getAllPermissions()->pluck('name') }}" />
@endsection

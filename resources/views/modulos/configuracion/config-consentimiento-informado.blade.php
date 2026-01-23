@extends('layouts.app')

@section('content')
    <config-Consentimiento-Informados-Component :usuarioactual="{{ Auth::user() }}" :permisos="{{ Auth::user()->getAllPermissions()->pluck('name') }}" />
@endsection

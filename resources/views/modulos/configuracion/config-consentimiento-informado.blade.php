@extends('layouts.app')

@section('content')
    <config-Consentimiento-Informados-Component
        :usuarioactual='@json(Auth::user())'
        :permisos='@json(Auth::user()->getAllPermissions()->pluck("name"))'
    />
@endsection

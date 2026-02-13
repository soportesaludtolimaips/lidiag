@extends('layouts.app')

@section('content')
    <config-consentimiento-informados-component
        :usuarioactual='@json(Auth::user())'
        :permisos='@json(Auth::user()->getAllPermissions()->pluck("name")->values())'
    />
@endsection

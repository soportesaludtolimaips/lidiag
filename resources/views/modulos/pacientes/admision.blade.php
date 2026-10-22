@extends('layouts.app')

@section('content')
    <admision-paciente-component
        :usuarioactual='@json(Auth::user())'
        :permisos='@json(Auth::user()->getAllPermissions()->pluck("name")->toArray())'
    />
@endsection

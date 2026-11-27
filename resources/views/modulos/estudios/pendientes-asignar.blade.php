@extends('layouts.app')

@section('content')
    <pendientes-asignar-component
        :usuarioactual='@json(Auth::user())'
        :permisos='@json(Auth::user()->getAllPermissions()->pluck("name")->toArray())'
    />
@endsection

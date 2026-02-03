@extends('layouts.app')

@section('content')
    <dashboard-component
        :usuarioactual='@json(Auth::user())'
        rolesusuario="{{ auth()->user()->roles()->first()->name }}"
        :permisos='@json(Auth::user()->getAllPermissions()->pluck("name")->toArray())'
    ></dashboard-component>
@endsection

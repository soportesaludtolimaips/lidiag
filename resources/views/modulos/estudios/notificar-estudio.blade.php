@extends('layouts.app')

@section('content')
    <notificar-estudio-component
        :usuarioactual='@json(Auth::user())'
        :permisos='@json(Auth::user()->getAllPermissions()->pluck("name")->toArray())'
    />
@endsection

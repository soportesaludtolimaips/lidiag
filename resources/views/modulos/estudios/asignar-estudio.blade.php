@extends('layouts.app')

@section('content')
    <asignar-studio-component
        :usuarioactual='@json(Auth::user())'
        :permisos='@json(Auth::user()->getAllPermissions()->pluck("name")->toArray())'
    />
@endsection

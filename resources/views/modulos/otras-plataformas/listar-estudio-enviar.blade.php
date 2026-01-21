@extends('layouts.app')

@section('content')
    <otras-plataformas-asignar-estudio-component :usuarioactual="{{ Auth::user() }}" />
@endsection

@extends('layouts.app')

@section('content')
    <otras-plataformas-asignar-estudio-component :usuarioactual='@json(Auth::user())' />
@endsection

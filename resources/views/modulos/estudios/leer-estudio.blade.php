@extends('layouts.app')

@section('content')
    <leer-estudio-component :usuarioactual="{{ Auth::user() }}" />
@endsection

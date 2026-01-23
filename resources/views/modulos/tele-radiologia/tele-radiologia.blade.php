@extends('layouts.app')

@section('content')
    <tele-radiologia-enviar-estudio-component :usuarioactual="{{ Auth::user() }}" />
@endsection

@extends('layouts.app')

@section('content')
    <tele-radiologia-enviar-estudio-component :usuarioactual='@json(Auth::user())' />
@endsection

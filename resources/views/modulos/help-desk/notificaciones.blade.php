@extends('layouts.app')

@section('content')
    <helpdesk-notificaciones-component :usuarioactual='@json(Auth::user())' />
@endsection


@extends('layouts.app')

@section('content')
    <helpdesk-crear-ticket-component :usuarioactual='@json(Auth::user())' />
@endsection


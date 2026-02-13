@extends('layouts.app')

@section('content')
    <helpdesk-tickets-component :usuarioactual='@json(Auth::user())' />
@endsection


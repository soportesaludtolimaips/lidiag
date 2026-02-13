@extends('layouts.app')

@section('content')
    <helpdesk-detalle-ticket-component
        :usuarioactual='@json(Auth::user())'
        ticket-id="{{ request()->route('ticket_id') }}"
    />
@endsection


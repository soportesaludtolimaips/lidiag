@extends('layouts.app')

@section('content')
    <helpdesk-config-prioridades-component :usuarioactual='@json(Auth::user())' />
@endsection


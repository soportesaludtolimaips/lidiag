@extends('layouts.app')

@section('content')
    <helpdesk-config-estados-component :usuarioactual='@json(Auth::user())' />
@endsection


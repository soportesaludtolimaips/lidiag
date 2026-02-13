@extends('layouts.app')

@section('content')
    <helpdesk-reportes-component :usuarioactual='@json(Auth::user())' />
@endsection


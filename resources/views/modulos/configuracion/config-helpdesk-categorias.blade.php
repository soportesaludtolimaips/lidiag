@extends('layouts.app')

@section('content')
    <helpdesk-config-categorias-component :usuarioactual='@json(Auth::user())' />
@endsection


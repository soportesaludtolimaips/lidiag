@extends('layouts.app')

@section('content')
    <gestion-citas-component :usuarioactual='@json(Auth::user())' />
@endsection
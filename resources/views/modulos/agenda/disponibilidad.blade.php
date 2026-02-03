@extends('layouts.app')

@section('content')
    <agenda-disponibilidad-component :usuarioactual='@json(Auth::user())' />
@endsection

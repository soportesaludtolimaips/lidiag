@extends('layouts.app')

@section('content')
    <agenda-disponibilidad-component :usuarioactual="{{ Auth::user() }}" />
@endsection

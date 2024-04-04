@extends('layouts.app')

@section('content')
    <dashboard-component :usuarioactual="{{ Auth::user() }}" rolesusuario="{{ auth()->user()->roles()->first()->name }}"></dashboard-component>
@endsection

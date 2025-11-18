@extends('layouts.app')

@section('content')
    <ecografias-lectura-component :usuarioactual="{{ Auth::user() }}" />
@endsection

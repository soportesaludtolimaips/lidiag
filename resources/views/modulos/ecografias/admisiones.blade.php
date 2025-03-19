@extends('layouts.app')

@section('content')
    <ecografias-admisiones-component :usuarioactual="{{ Auth::user() }}" />
@endsection

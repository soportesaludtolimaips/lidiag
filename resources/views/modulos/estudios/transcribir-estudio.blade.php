@extends('layouts.app')

@section('content')
    <transcribir-estudio-component :usuarioactual="{{ Auth::user() }}" />
@endsection

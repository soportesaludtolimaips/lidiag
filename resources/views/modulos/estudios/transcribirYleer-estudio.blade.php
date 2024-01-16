@extends('layouts.app')

@section('content')
    <leer-y-transcribir-administrativo-component :usuarioactual="{{ Auth::user() }}" />
@endsection

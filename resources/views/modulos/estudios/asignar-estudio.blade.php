@extends('layouts.app')

@section('content')
    <asignar-studio-component :usuarioactual="{{ Auth::user() }}" />
@endsection

@extends('layouts.app')

@section('content')
    <otras-plataformas-component :usuarioactual="{{ Auth::user() }}" />
@endsection

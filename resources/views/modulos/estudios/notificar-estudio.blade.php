@extends('layouts.app')

@section('content')
    <notificar-estudio-component :usuarioactual="{{ Auth::user() }}" />
@endsection

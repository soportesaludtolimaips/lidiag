@extends('layouts.app')

@section('content')
    <tele-radiologia-transcribir-component :usuarioactual="{{ Auth::user() }}" />
@endsection

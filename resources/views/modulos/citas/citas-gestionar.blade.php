@extends('layouts.app')

@section('content')
    <citas-gestionar-component :usuarioactual="{{ Auth::user() }}" />
@endsection

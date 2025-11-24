@extends('layouts.app')

@section('content')
    <tele-radiologia-component :usuarioactual="{{ Auth::user() }}" />
@endsection

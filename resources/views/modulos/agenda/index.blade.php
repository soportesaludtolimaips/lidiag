@extends('layouts.app')

@section('content')
    <agenda-component :usuarioactual="{{ Auth::user() }}" />
@endsection

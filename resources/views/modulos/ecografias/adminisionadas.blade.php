@extends('layouts.app')

@section('content')
    <ecografias-admisionadas-component :usuarioactual="{{ Auth::user() }}" />
@endsection

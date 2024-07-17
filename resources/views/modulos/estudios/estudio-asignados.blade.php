@extends('layouts.app')

@section('content')
    <estudio-asignados-component :usuarioactual="{{ Auth::user() }}" />
@endsection

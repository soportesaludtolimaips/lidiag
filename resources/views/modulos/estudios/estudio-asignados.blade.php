@extends('layouts.app')

@section('content')
    <estudio-asignados-component :usuarioactual='@json(Auth::user())' />
@endsection

@extends('layouts.app')

@section('content')
    <transcribir-estudio-component :usuarioactual='@json(Auth::user())' />
@endsection

@extends('layouts.app')

@section('content')
    <liquidacion-component :usuarioactual='@json(Auth::user())' />
@endsection

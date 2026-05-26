@extends('layouts.app')

@section('content')
    <citas-component :usuarioactual='@json(Auth::user())' />
@endsection
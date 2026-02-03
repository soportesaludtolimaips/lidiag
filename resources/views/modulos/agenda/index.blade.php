@extends('layouts.app')

@section('content')
    <agenda-component :usuarioactual='@json(Auth::user())' />
@endsection

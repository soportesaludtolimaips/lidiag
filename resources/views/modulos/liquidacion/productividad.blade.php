@extends('layouts.app')

@section('content')
    <productividad-component :usuarioactual='@json(Auth::user())' />
@endsection

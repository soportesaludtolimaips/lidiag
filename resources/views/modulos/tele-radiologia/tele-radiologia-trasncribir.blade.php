@extends('layouts.app')

@section('content')
    <tele-radiologia-transcribir-component :usuarioactual='@json(Auth::user())' />
@endsection

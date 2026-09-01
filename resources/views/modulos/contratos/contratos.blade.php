@extends('layouts.app')

@section('content')
    <contrato-component :usuarioactual='@json(Auth::user())' />
@endsection

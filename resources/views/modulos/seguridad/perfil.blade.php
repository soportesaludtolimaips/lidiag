@extends('layouts.app')

@section('content')
    <seguridad-perfil :usuarioactual='@json(Auth::user())'></seguridad-perfil>
@endsection

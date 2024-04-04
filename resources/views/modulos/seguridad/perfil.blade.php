@extends('layouts.app')

@section('content')
    <seguridad-perfil :usuarioactual="{{ Auth::user() }}"></seguridad-perfil>
@endsection

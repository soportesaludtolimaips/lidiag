@extends('layouts.app')

@section('content')
    <ecografias-consentimientos-component :usuarioactual='@json(Auth::user())' :permisos='@json(Auth::user()->getAllPermissions()->pluck("name")->values())' />
@endsection


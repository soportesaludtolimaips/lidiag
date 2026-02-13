@extends('layouts.app')

@section('content')
    <ecografias-consentimientos-plantillas-component :usuarioactual='@json(Auth::user())' :permisos='@json(Auth::user()->getAllPermissions()->pluck("name")->values())' />
@endsection


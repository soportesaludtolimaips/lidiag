@extends('layouts.app')

@section('content')
    <consentimientos-generar-component :permisos='@json(Auth::user()->getAllPermissions()->pluck("name")->values())' />
@endsection

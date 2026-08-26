@extends('layouts.app')

@section('content')
    <consentimientos-plantillas-component :permisos='@json(Auth::user()->getAllPermissions()->pluck("name")->values())' />
@endsection

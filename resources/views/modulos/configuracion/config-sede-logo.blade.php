@extends('layouts.app')

@section('content')
    <config-sede-logo-component :permisos='@json(Auth::user()->getAllPermissions()->pluck("name")->values())' />
@endsection

@extends('layouts.app')

@section('content')
    <consentimientos-medico-component :permisos='@json(Auth::user()->getAllPermissions()->pluck("name")->values())' />
@endsection

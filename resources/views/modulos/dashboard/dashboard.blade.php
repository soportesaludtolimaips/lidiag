@extends('layouts.app')

@section('content')
    <dashboard-component :usuarioactual="{{ Auth::user() }}"></dashboard-component>
@endsection

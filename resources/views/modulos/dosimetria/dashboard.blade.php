@extends('layouts.app')

@section('content')
    <dashboard-dosimetria
        :usuarioactual='@json(Auth::user())'
        :permisos='@json(Auth::user()->getAllPermissions()->pluck("name")->toArray())'
    />
@endsection

@extends('layouts.app')

@section('content')
    @if (Auth::check())
        <notificar-estudio-component :usuarioactual="{{ Auth::user() }}" :permisos="{{ Auth::user()->getAllPermissions()->pluck('name') }}" />
    @else
        <script>
            window.location.href = "{{ route('login') }}";
        </script>
    @endif
@endsection

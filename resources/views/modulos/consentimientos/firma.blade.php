@extends('layouts.external')

@section('content')
    <div class="container p-3">
        <consentimiento-firma-component token="{{ request()->route('token') }}" />
    </div>
@endsection

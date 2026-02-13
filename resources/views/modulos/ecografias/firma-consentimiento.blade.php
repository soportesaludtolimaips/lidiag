@extends('layouts.external')

@section('content')
    <div class="container p-3">
        <ecografia-firma-consentimiento-component token="{{ request()->route('token') }}" />
    </div>
@endsection


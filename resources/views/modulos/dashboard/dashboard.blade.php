@extends('layouts.app')

@section('content')
    @if(auth()->user()->roles->isEmpty())
        <div class="row">
            <div class="col-12">
                <div class="alert alert-warning alert-rounded d-flex align-items-center shadow-sm py-3 px-4 mb-4" role="alert" style="border-left: 5px solid #ffb22b; background-color: #fff9e6;">
                    <i class="fa fa-exclamation-triangle fa-3x mr-3 text-warning"></i>
                    <div>
                        <h4 class="alert-heading mb-1 font-weight-bold text-dark">
                            Usuario sin Rol Asignado
                        </h4>
                        <p class="mb-0 text-dark">
                            La cuenta <strong>{{ auth()->user()->name }}</strong> (<code>{{ auth()->user()->email }}</code>) no tiene ningún rol asignado en la plataforma.
                            Por esta razón, el menú y las funciones operativas del sistema se encuentran restringidos.
                            Por favor, comuníquese con un usuario <strong>Administrador</strong> para que le configure el rol correspondiente desde el módulo de <strong>Seguridad &gt; Usuarios</strong>.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <dashboard-component
        :usuarioactual='@json(Auth::user())'
        rolesusuario="{{ auth()->user()->roles->pluck('name')->first() ?? '' }}"
        :permisos='@json(Auth::user()->getAllPermissions()->pluck("name")->toArray())'
    ></dashboard-component>
@endsection

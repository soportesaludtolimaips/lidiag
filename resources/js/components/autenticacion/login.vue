<template>
	<div>
		<section id="wrapper" class="login-register login-sidebar" style="background-image:url(admin-wrap-login/assets/images/background/login-register.jpg);
        -moz-background-size: 100% 100%;
        -o-background-size: 100% 100%;
        -webkit-background-size: 100% 100%;
        background-size: 100% 100%;
        -moz-border-image: url('img/fondo.jpg') 0;
        float: left;">

        <div class="login-box card">
            <div class="card-body">
                <form class="form-horizontal form-material" method="POST" action="{{ route('login') }}">
                    @csrf
                    <a href="javascript:void(0)" class="text-center db">
                        <img src="{{ asset('admin-wrap-login/assets/images/logo-icon.png') }}" alt="Home" /><br />
                        <img src="{{ asset('admin-wrap-login/assets/images/logo-text.png') }}" alt="Home" />
                    </a>
                    <div class="form-group m-t-40">
                        <div class="col-xs-12">
                            <input id="email" type="text" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email', 'administrador@administrador.com') }}" required autocomplete="email" autofocus placeholder="Email">
                            @error('email')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>
                    <div class="form-group m-t-40">
                        <div class="col-xs-12">
                            <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password" placeholder="Contraseña" value="123456">
                            @error('password')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-md-12">
                            <div class="checkbox checkbox-primary pull-left p-t-0">
                                <input id="checkbox-signup" type="checkbox" class="filled-in chk-col-light-blue">
                                <label for="checkbox-signup"> Recuerdame </label>
                            </div>
                            <a href="javascript:void(0)" id="to-recover" class="text-dark pull-right"><i class="fa fa-lock m-r-5"></i> Olvidé mi contraseña?</a>
                        </div>
                    </div>
                    <div class="form-group text-center m-t-20">
                        <div class="col-xs-12">
                            <button class="btn btn-info btn-lg btn-block text-uppercase btn-rounded" type="submit">
                                Iniciar sesión
                            </button>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </section>
	</div>
</template>
<script>
export default {

}
</script>

<style>

</style>
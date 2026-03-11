@extends('adminlte::auth.auth-page', ['auth_type' => 'login'])

@section('adminlte_css_pre')
    <link rel="stylesheet" href="{{ asset('vendor/icheck-bootstrap/icheck-bootstrap.min.css') }}">
@stop

@php( $login_url = View::getSection('login_url') ?? config('adminlte.login_url', 'login') )
@php( $register_url = View::getSection('register_url') ?? config('adminlte.register_url', 'register') )
@php( $password_reset_url = View::getSection('password_reset_url') ?? config('adminlte.password_reset_url', 'password/reset') )

@if (config('adminlte.use_route_url', false))
    @php( $login_url = $login_url ? route($login_url) : '' )
    @php( $register_url = $register_url ? route($register_url) : '' )
    @php( $password_reset_url = $password_reset_url ? route($password_reset_url) : '' )
@else
    @php( $login_url = $login_url ? url($login_url) : '' )
    @php( $register_url = $register_url ? url($register_url) : '' )
    @php( $password_reset_url = $password_reset_url ? url($password_reset_url) : '' )
@endif

@php($defaultAuthError = 'These credentials do not match our records.')
@php($friendlyAuthError = 'Invalid email or password.')
@php($accountDisabledError = 'You account access is disabled')
@php($emailError = $errors->first('email'))
@php($passwordError = $errors->first('password'))
@php($formError = null)

@if ($emailError === $defaultAuthError || $passwordError === $defaultAuthError)
    @php($formError = $friendlyAuthError)
@elseif ($emailError === $accountDisabledError)
    @php($formError = $accountDisabledError)
@endif

@section('auth_header')
    <p class="mb-0">{{ __('adminlte::adminlte.login_message') }}</p>
@stop

@section('auth_body')
    <form action="{{ $login_url }}" method="post">
        @csrf

        @if ($formError)
            <div class="alert alert-dismissable alert-danger auth-form-alert">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <strong>{{ $formError }}</strong>
            </div>
        @endif

        {{-- Email field --}}
        <div class="auth-field mb-3">
            <div class="input-group">
                <input type="email" name="email" class="form-control @if($emailError && $emailError !== $defaultAuthError && $emailError !== $accountDisabledError) is-invalid @endif"
                       value="{{ old('email') }}" placeholder="{{ __('adminlte::adminlte.email') }}" autofocus>

                <div class="input-group-append">
                    <div class="input-group-text">
                        <span class="fas fa-envelope {{ config('adminlte.classes_auth_icon', '') }}"></span>
                    </div>
                </div>
            </div>

            @if($emailError && $emailError !== $defaultAuthError && $emailError !== $accountDisabledError)
                <span class="invalid-feedback d-block auth-inline-error" role="alert">
                    <strong>{{ $emailError }}</strong>
                </span>
            @endif
        </div>

        {{-- Password field --}}
        <div class="auth-field mb-3">
            <div class="input-group">
                <input type="password" name="password" class="form-control @if($passwordError && $passwordError !== $defaultAuthError) is-invalid @endif"
                       placeholder="{{ __('adminlte::adminlte.password') }}">

                <div class="input-group-append">
                    <div class="input-group-text">
                        <span class="fas fa-lock {{ config('adminlte.classes_auth_icon', '') }}"></span>
                    </div>
                </div>
            </div>

            @if($passwordError && $passwordError !== $defaultAuthError)
                <span class="invalid-feedback d-block auth-inline-error" role="alert">
                    <strong>{{ $passwordError }}</strong>
                </span>
            @endif
        </div>

        {{-- Login field --}}
        <div class="row">
            <div class="col-7">
                <div class="icheck-primary" title="{{ __('adminlte::adminlte.remember_me_hint') }}">
                    <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>

                    <label for="remember">
                        {{ __('adminlte::adminlte.remember_me') }}
                    </label>
                </div>
            </div>

            <div class="col-5">
                <button type=submit class="btn btn-block {{ config('adminlte.classes_auth_btn', 'btn-flat btn-primary') }}">
                    <span class="fas fa-sign-in-alt"></span>
                    {{ __('adminlte::adminlte.sign_in') }}
                </button>
            </div>
        </div>

    </form>
@stop

@section('auth_footer')
    {{-- Password reset link --}}
    @if($password_reset_url)
        <p class="my-0">
            <a href="{{ $password_reset_url }}">
                Forget password ?
            </a>
        </p>
    @endif
@stop

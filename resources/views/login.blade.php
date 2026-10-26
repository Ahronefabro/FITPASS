@extends('layouts.guest')

@section('title', 'Log in')

@section('form')
    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="login-head">
            <span class="badge-icon"><span class="material-symbols-outlined">lock</span></span>
            <h1>LOG IN</h1>
            <i></i>
            <small>Authorized personnel only</small>
        </div>

        @if (session('status')) <p class="notice">{{ session('status') }}</p> @endif
        @error('username') <p class="error">{{ $message }}</p> @enderror

        <div class="field">
            <input name="username" value="{{ old('username') }}" placeholder="Username" required autofocus>
        </div>
        <div class="field">
            @include('partials.password-field', ['name' => 'password', 'placeholder' => 'Password'])
        </div>
        <button class="login-btn">LOG IN</button>

        <p class="login-switch"><a href="{{ route('password.request') }}">Forgot password?</a></p>
    </form>
@endsection

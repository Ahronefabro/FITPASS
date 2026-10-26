@extends('layouts.guest')

@section('title', 'Forgot password')

@section('form')
    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="login-head">
            <span class="badge-icon"><span class="material-symbols-outlined">key</span></span>
            <h1>FORGOT PASSWORD</h1>
            <i></i>
            <small>Enter the email on your account and we'll send you a reset link</small>
        </div>

        @if (session('status')) <p class="notice">{{ session('status') }}</p> @endif
        @error('email') <p class="error">{{ $message }}</p> @enderror

        <div class="field">
            <input type="email" name="email" value="{{ old('email') }}" placeholder="Email" required autofocus>
        </div>
        <button class="login-btn">SEND RESET LINK</button>

        <p class="login-switch"><a href="{{ route('login') }}">Back to log in</a></p>
    </form>
@endsection

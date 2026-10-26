@extends('layouts.guest')

@section('title', 'Reset password')

@section('form')
    <form method="POST" action="{{ $action ?? route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="login-head">
            <span class="badge-icon"><span class="material-symbols-outlined">lock_reset</span></span>
            <h1>NEW PASSWORD</h1>
            <i></i>
            <small>Choose a password of at least 8 characters</small>
        </div>

        @if (session('status')) <p class="notice">{{ session('status') }}</p> @endif
        @foreach (['email', 'password'] as $field)
            @error($field) <p class="error">{{ $message }}</p> @enderror
        @endforeach

        <div class="field">
            <input type="email" name="email" value="{{ old('email', $email) }}" placeholder="Email" required>
        </div>
        <div class="field">
            @include('partials.password-field', ['name' => 'password', 'placeholder' => 'New password'])
        </div>
        <div class="field">
            @include('partials.password-field', ['name' => 'password_confirmation', 'placeholder' => 'Confirm new password'])
        </div>
        <button class="login-btn">RESET PASSWORD</button>

        {{-- Members log in on the app, not here. --}}
        @unless ($member ?? false)
            <p class="login-switch"><a href="{{ route('login') }}">Back to log in</a></p>
        @endunless
    </form>
@endsection

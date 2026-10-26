@extends('layouts.app')

@section('title', 'Account Settings')

@section('content')
<h1 class="page-title">ACCOUNT SETTINGS</h1>

<div class="account-grid">
    <form class="account-card" method="POST" action="{{ route('account.username') }}">
        @csrf @method('PATCH')
        <h2>Change username</h2>

        <label>Username
            <input type="text" name="username" value="{{ old('username', auth()->user()->username) }}" required>
        </label>
        @error('username', 'username') <p class="field-error">{{ $message }}</p> @enderror

        <label>Current password
            <input type="password" name="current_password" required autocomplete="current-password">
        </label>
        @error('current_password', 'username') <p class="field-error">{{ $message }}</p> @enderror

        <button class="btn-primary" type="submit">Save username</button>
    </form>

    <form class="account-card" method="POST" action="{{ route('account.password') }}">
        @csrf @method('PATCH')
        <h2>Change password</h2>

        <label>Current password
            <input type="password" name="current_password" required autocomplete="current-password">
        </label>
        @error('current_password', 'password') <p class="field-error">{{ $message }}</p> @enderror

        <label>New password
            <input type="password" name="password" required autocomplete="new-password">
        </label>
        @error('password', 'password') <p class="field-error">{{ $message }}</p> @enderror

        <label>Confirm new password
            <input type="password" name="password_confirmation" required autocomplete="new-password">
        </label>

        <button class="btn-primary" type="submit">Save password</button>
    </form>
</div>
@endsection
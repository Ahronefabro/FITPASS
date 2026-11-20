@extends(\App\Auth\Portal::forRole(auth()->user()->role) === 'staff' ? 'staff.layout' : 'layouts.app')

@section('title', 'Account Settings')

@section('content')
<h1 class="page-title">ACCOUNT SETTINGS</h1>

<div class="account-grid">
    <form class="account-card" method="POST" action="{{ route('account.details') }}">
        @csrf @method('PATCH')
        <h2>Account details</h2>

        <label>Username
            <input type="text" name="username" value="{{ old('username', auth()->user()->username) }}" autocomplete="username" required>
        </label>
        @error('username', 'details') <p class="field-error">{{ $message }}</p> @enderror

        <label>Email
            <input type="email" name="email" value="{{ old('email', auth()->user()->email) }}" autocomplete="email" required>
        </label>
        <p class="field-hint">"Forgot password?" sends the reset link here, so use an address you can open.</p>
        @error('email', 'details') <p class="field-error">{{ $message }}</p> @enderror

        <label>Current password
            @include('partials.password-input', ['name' => 'current_password', 'autocomplete' => 'current-password'])
        </label>
        @error('current_password', 'details') <p class="field-error">{{ $message }}</p> @enderror

        <button class="btn-primary" type="submit">Save details</button>
    </form>

    <form class="account-card" method="POST" action="{{ route('account.password') }}">
        @csrf @method('PATCH')
        <h2>Change password</h2>

        <label>Current password
            @include('partials.password-input', ['name' => 'current_password', 'autocomplete' => 'current-password'])
        </label>
        @error('current_password', 'password') <p class="field-error">{{ $message }}</p> @enderror

        <label>New password
            @include('partials.password-input', ['name' => 'password', 'autocomplete' => 'new-password'])
        </label>
        @error('password', 'password') <p class="field-error">{{ $message }}</p> @enderror

        <label>Confirm new password
            @include('partials.password-input', ['name' => 'password_confirmation', 'autocomplete' => 'new-password'])
        </label>

        <button class="btn-primary" type="submit">Save password</button>
    </form>
</div>
@endsection
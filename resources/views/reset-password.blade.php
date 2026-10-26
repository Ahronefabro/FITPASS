<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset password · FitPass Gym</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined&display=block">
    <link rel="stylesheet" href="{{ asset('css/fitpass.css') }}">
</head>
<body class="login">
    <div class="login-brand">
        <div>
            <div class="logo"><img src="{{ asset('images/logo.png') }}" alt="FitPass Gym" onerror="this.remove()"></div>
            <div class="wordmark"><b>FIT<em>PASS</em></b><small>GYM</small></div>
        </div>
    </div>

    <div class="login-form">
        <form method="POST" action="{{ route('password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div class="login-head">
                <span class="badge-icon"><span class="material-symbols-outlined">lock_reset</span></span>
                <h1>NEW PASSWORD</h1>
                <i></i>
                <small>At least 8 characters</small>
            </div>

            @error('email') <p class="error">{{ $message }}</p> @enderror
            @error('password') <p class="error">{{ $message }}</p> @enderror

            <div class="field">
                <input type="email" name="email" value="{{ old('email', $email) }}" placeholder="Email" required>
            </div>
            <div class="field">
                <input type="password" name="password" placeholder="New password" required autofocus>
            </div>
            <div class="field">
                <input type="password" name="password_confirmation" placeholder="Confirm new password" required>
            </div>
            <button class="login-btn">SET PASSWORD</button>

            <p class="login-switch"><a href="{{ route('login') }}">Back to log in</a></p>
        </form>
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Log in · FitPass Gym</title>
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
                <input type="password" name="password" placeholder="Password" required>
                <button type="button" title="Show or hide password"
                    onclick="const i = this.previousElementSibling, show = i.type === 'password'; i.type = show ? 'text' : 'password'; this.firstElementChild.textContent = show ? 'visibility' : 'visibility_off';">
                    <span class="material-symbols-outlined">visibility_off</span>
                </button>
            </div>
            <button class="login-btn">LOG IN</button>

            <p class="login-switch"><a href="{{ route('password.request') }}">Forgot password?</a></p>
        </form>
    </div>
</body>
</html>

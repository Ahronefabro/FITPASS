<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot password · FitPass Gym</title>
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
        <form method="POST" action="{{ route('password.email') }}">
            @csrf

            <div class="login-head">
                <span class="badge-icon"><span class="material-symbols-outlined">key</span></span>
                <h1>FORGOT PASSWORD</h1>
                <i></i>
                <small>We'll email you a link to set a new one</small>
            </div>

            @if (session('status')) <p class="notice">{{ session('status') }}</p> @endif
            @error('email') <p class="error">{{ $message }}</p> @enderror

            <div class="field">
                <input type="email" name="email" value="{{ old('email') }}" placeholder="Email" required autofocus>
            </div>
            <button class="login-btn">SEND RESET LINK</button>

            <p class="login-switch"><a href="{{ route('login') }}">Back to log in</a></p>
        </form>
    </div>
</body>
</html>
